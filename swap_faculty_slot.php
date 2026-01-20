<?php
header('Content-Type: application/json');
// require './Backend/auth_guard.php';
require_once "./Backend/config.php"; // must define $conn

/* ================= RESPONSE HELPER ================= */
function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

function hasSlot($schedule, $date, $slot) {
    return (
        isset($schedule[$date]) &&
        isset($schedule[$date][$slot]) &&
        !empty($schedule[$date][$slot]['assigned'])
    );
}

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['from'], $data['to'])) {
    respond(false, "Invalid request payload");
}

$from = $data['from'];
$to   = $data['to'];

/* ================= REQUIRED FIELDS ================= */
$required = ['fid', 'date', 'slot', 's_id'];

foreach ($required as $r) {
    if (empty($from[$r]) || empty($to[$r])) {
        respond(false, "Missing required field: $r");
    }
}

/* ================= EXTRACT VALUES ================= */
$fromFid  = (int)$from['fid'];
$toFid    = (int)$to['fid'];

$fromDate = $from['date'];
$fromSlot = $from['slot'];

$toDate   = $to['date'];
$toSlot   = $to['slot'];

$s_id     = $from['s_id'];

/* ================= BASIC SAFETY ================= */
if (
    $fromDate === $toDate &&
    $fromSlot === $toSlot
) {
    respond(false, "Cannot swap the same slot");
}

if (
    $fromFid === $toFid
) {
    respond(false, "Cannot swap the same faculty");
}

/* ================= DB TRANSACTION ================= */
$conn->begin_transaction();

try {

    /* ================= FETCH BOTH FACULTY SCHEDULES ================= */
    $stmt = $conn->prepare("
        SELECT faculty_id, schedule
        FROM block_supervisor_list
        WHERE faculty_id IN (?, ?) AND s_id = ?
        FOR UPDATE
    ");

    $stmt->bind_param("iis", $fromFid, $toFid, $s_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows !== 2) {
        throw new Exception("One or both faculty records not found");
    }

    /* ================= DECODE SCHEDULES ================= */
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[$row['faculty_id']] =
            json_decode($row['schedule'], true) ?? [];
    }

    /* ================= VALIDATE FROM SLOT ================= */
    if (
        empty($rows[$fromFid][$fromDate][$fromSlot]) ||
        empty($rows[$fromFid][$fromDate][$fromSlot]['assigned'])
    ) {
        throw new Exception("Source slot is not assigned");
    }

    /* ================= VALIDATE TO SLOT ================= */
    if (
        empty($rows[$toFid][$toDate][$toSlot]) ||
        empty($rows[$toFid][$toDate][$toSlot]['assigned'])
    ) {
        throw new Exception("Target slot is not assigned");
    }

    /* ================= CONFLICT PREVENTION ================= */
    /* From faculty already has target slot */
    if (hasSlot($rows[$fromFid], $toDate, $toSlot)) {
        throw new Exception("Swap not allowed: source faculty already has $toDate ($toSlot)");
    }

    /* To faculty already has source slot */
    if (hasSlot($rows[$toFid], $fromDate, $fromSlot)) {
        throw new Exception("Swap not allowed: target faculty already has $fromDate ($fromSlot)");
    }

    /* ================= PERFORM SWAP ================= */
    $temp = $rows[$fromFid][$fromDate][$fromSlot];

    $rows[$fromFid][$toDate][$toSlot] =
        $rows[$toFid][$toDate][$toSlot];

    $rows[$toFid][$fromDate][$fromSlot] = $temp;

    unset($rows[$toFid][$toDate][$toSlot]);
    unset($rows[$fromFid][$fromDate][$fromSlot]);

    /* ================= UPDATE DATABASE ================= */
    $update = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule = ?
        WHERE faculty_id = ? AND s_id = ?
    ");

    foreach ([$fromFid, $toFid] as $fid) {
        $json = json_encode($rows[$fid], JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new Exception("JSON encoding failed for faculty $fid");
        }

        $update->bind_param("sis", $json, $fid, $s_id);

        if (!$update->execute()) {
            throw new Exception("Database update failed for faculty $fid");
        }
    }

    $conn->commit();

    /* ================= SUCCESS ================= */
    respond(true, "Faculty slots swapped successfully", [
        "from" => [
            "fid"  => $fromFid,
            "date" => $fromDate,
            "slot" => $fromSlot
        ],
        "to" => [
            "fid"  => $toFid,
            "date" => $toDate,
            "slot" => $toSlot
        ]
    ]);

} catch (Exception $e) {

    $conn->rollback();

    respond(false, $e->getMessage(), [
        "debug" => [
            "from" => $from,
            "to"   => $to
        ]
    ]);
}