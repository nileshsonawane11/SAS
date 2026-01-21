<?php
include "./Backend/config.php";
error_reporting(E_ALL);

/* ========== READ JSON ========== */
$data = json_decode(file_get_contents("php://input"), true);

$fid          = (int)($data['fid'] ?? 0);
$date         = $data['date'] ?? '';
$slot         = $data['slot'] ?? '';
$s_id         = (int)($data['s_id'] ?? 0);
$present      = $data['present'] ?? 'yes';
$reason       = $data['reason'] ?? '';
$replace_id   = (int)($data['replace_id'] ?? 0);
$other_reason = $data['other_reason'] ?? '';
$new_faculty  = trim($data['new_faculty'] ?? '');

if (!$fid || !$date || !$slot || !$s_id) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'Invalid input']);
    exit;
}

/* ========== LOAD CURRENT SCHEDULE ========== */
$stmt = $conn->prepare("
    SELECT schedule FROM block_supervisor_list
    WHERE faculty_id=? AND s_id=?
");
$stmt->bind_param("ii", $fid, $s_id);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(['status'=>'error','msg'=>'Faculty schedule not found']);
    exit;
}

$schedule = json_decode($row['schedule'], true) ?: [];

/* ========== PRESENT = YES ========== */
if ($present === 'yes') {

    $schedule[$date][$slot]['present'] = true;
    $schedule[$date][$slot]['reason'] = '';
    $schedule[$date][$slot]['other_reason'] = '';

    $json = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=?
    ");
    $up->bind_param("sii", $json, $fid, $s_id);
    $up->execute();

    echo json_encode(['status'=>'ok','msg'=>'Marked present']);
    exit;
}

/* ========== PRESENT = NO ========== */
$schedule[$date][$slot]['present'] = false;
$schedule[$date][$slot]['reason'] = $reason;
$schedule[$date][$slot]['other_reason'] = $other_reason;

/* ========== NO REPLACEMENT ========== */
if ($reason !== 'replace') {

    $json = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=?
    ");
    $up->bind_param("sii", $json, $fid, $s_id);
    $up->execute();

    echo json_encode(['status'=>'ok','msg'=>'Absence recorded']);
    exit;
}

/* ========== REPLACEMENT FLOW (TRANSACTION) ========== */
$conn->begin_transaction();

try {

    /* ---- Create faculty if needed ---- */
    if (!$replace_id) {

        if (!$new_faculty) {
            throw new Exception("Replacement faculty missing");
        }

        $stmt = $conn->prepare("
            INSERT INTO faculty (faculty_name, status, duties)
            VALUES (?, 'ON', 0)
        ");
        $stmt->bind_param("s", $new_faculty);
        $stmt->execute();
        $replace_id = $stmt->insert_id;
    }

    /* ---- Load / create replacement schedule ---- */
    $stmt = $conn->prepare("
        SELECT schedule FROM block_supervisor_list
        WHERE faculty_id=? AND s_id=?
    ");
    $stmt->bind_param("ii", $replace_id, $s_id);
    $stmt->execute();
    $r = $stmt->get_result();

    if ($nr = $r->fetch_assoc()) {
        $newSch = json_decode($nr['schedule'], true) ?: [];
    } else {
        $newSch = [];
        $ins = $conn->prepare("
            INSERT INTO block_supervisor_list (faculty_id, s_id, schedule)
            VALUES (?, ?, '{}')
        ");
        $ins->bind_param("ii", $replace_id, $s_id);
        $ins->execute();
    }

    /* ---- Assign slot to replacement ---- */
    $newSch[$date][$slot] = [
        'assigned' => true,
        'present'  => true,
        'replaced' => $fid
    ];

    $jsonNew = json_encode($newSch, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=?
    ");
    $up->bind_param("sii", $jsonNew, $replace_id, $s_id);
    $up->execute();

    /* ---- Remove slot from old faculty ---- */
    unset($schedule[$date][$slot]);

    $jsonOld = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=?
    ");
    $up->bind_param("sii", $jsonOld, $fid, $s_id);
    $up->execute();

    $conn->commit();

    echo json_encode(['status'=>200,'msg'=>'Faculty replaced successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}