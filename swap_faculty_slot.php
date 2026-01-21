<?php
header('Content-Type: application/json');
require_once "./Backend/config.php"; // must define $conn

/* ================= RESPONSE HELPER ================= */
function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
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

/* ================= NO-OP PROTECTION ================= */
if (
    $fromFid === $toFid &&
    $fromDate === $toDate &&
    $fromSlot === $toSlot
) {
    respond(false, "Nothing to change");
}

/* ================= DB TRANSACTION ================= */
$conn->begin_transaction();

try {

    /* ================= FETCH FACULTY SCHEDULES ================= */
    $stmt = $conn->prepare("
        SELECT faculty_id, schedule
        FROM block_supervisor_list
        WHERE faculty_id IN (?, ?) AND s_id = ?
        FOR UPDATE
    ");

    $stmt->bind_param("iis", $fromFid, $toFid, $s_id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows < 1) {
        throw new Exception("Faculty schedule not found");
    }

    $rows = [
        $fromFid => [],
        $toFid   => []
    ];

    while ($row = $res->fetch_assoc()) {
        $rows[$row['faculty_id']] =
            json_decode($row['schedule'], true) ?? [];
    }

    /* ================= SLOT STATES ================= */
    $fromData = $rows[$fromFid][$fromDate][$fromSlot] ?? null;
    $toData   = $rows[$toFid][$toDate][$toSlot] ?? null;

    $fromHas = !empty($fromData['assigned']);
    $toHas   = !empty($toData['assigned']);

    /* ================= RULE ENFORCEMENT ================= */

    // Empty ➜ Empty
    if (!$fromHas && !$toHas) {
        throw new Exception("Both source and target slots are empty");
    }

    /* ================= APPLY MOVE / SWAP ================= */

    // Slot ➜ Slot (SWAP)
    if ($fromHas && $toHas) {

        $rows[$fromFid][$fromDate][$fromSlot] = $toData;
        $rows[$toFid][$toDate][$toSlot]       = $fromData;

    }
    // Slot ➜ Empty (MOVE)
    elseif ($fromHas && !$toHas) {

        $rows[$toFid][$toDate][$toSlot] = $fromData;
        unset($rows[$fromFid][$fromDate][$fromSlot]);

    }
    // Empty ➜ Slot (REVERSE MOVE)
    elseif (!$fromHas && $toHas) {

        $rows[$fromFid][$fromDate][$fromSlot] = $toData;
        unset($rows[$toFid][$toDate][$toSlot]);

    }

    /* ================= CLEAN EMPTY DATES ================= */
    foreach ([$fromFid, $toFid] as $fid) {
        foreach ($rows[$fid] as $date => $slots) {
            if (empty($slots)) {
                unset($rows[$fid][$date]);
            }
        }
    }

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
    respond(true, "Faculty assignment updated successfully", [
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