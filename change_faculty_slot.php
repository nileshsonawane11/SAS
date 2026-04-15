<?php
include './Backend/auth_guard.php';
include './Backend/config.php';
$owner = $user_data['_id'] ?? 0;
error_reporting(E_ALL);

/* ========== READ JSON ========== */
$data = json_decode(file_get_contents("php://input"), true);

$action = $data['action'] ?? '';
$td          = $data['td'] ?? '';
$fid          = (int)($data['fid'] ?? 0);
$date         = $data['date'] ?? '';
$slot         = $data['slot'] ?? '';
$s_id         = ($data['s_id'] ?? '');
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
    WHERE faculty_id=? AND s_id=? AND Created_by = ?
");
$stmt->bind_param("isi", $fid, $s_id, $owner);
$stmt->execute();
$res = $stmt->get_result();

if (!$row = $res->fetch_assoc()) {
    echo json_encode(['status'=>'error','msg'=>'Faculty schedule not found']);
    exit;
}

$schedule = json_decode($row['schedule'], true) ?: [];

if ($action === 'delete') {

    /* ---------- DELETE ONLY REQUIRED SLOT ---------- */
    if (isset($schedule[$date][$slot])) {

        unset($schedule[$date][$slot]);

        // If date has no slots left → remove date
        if (empty($schedule[$date])) {
            unset($schedule[$date]);
        }

    } else {
        echo json_encode([
            'status' => 404,
            'msg' => 'Slot not found'
        ]);
        exit;
    }

    /* ---------- SAVE BACK TO DB ---------- */
    $newSchedule = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $update = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule = ?
        WHERE faculty_id = ?
          AND s_id = ? 
          AND Created_by = ?
        LIMIT 1
    ");
    $update->bind_param("sisi", $newSchedule, $fid, $s_id, $owner);

    if ($update->execute()) {
        echo json_encode([
            'status' => 'ok'
        ]);
    } else {
        echo json_encode([
            'status' => 500,
            'msg' => 'Update failed'
        ]);
    }

    exit;
}

/* ========== PRESENT = YES ========== */
if ($present === 'yes') {

    $schedule[$date][$slot]['present'] = true;
    $schedule[$date][$slot]['reason'] = '';
    $schedule[$date][$slot]['other_reason'] = '';

    if($td == '' && empty($td)){
        $schedule[$date][$slot]['assigned'] = true;
        $schedule[$date][$slot]['block_type'] = 'buffer';
    }

    $json = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=? AND Created_by = ?
    ");
    $up->bind_param("sisi", $json, $fid, $s_id, $owner);
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
        WHERE faculty_id=? AND s_id=? AND Created_by = ?
    ");
    $up->bind_param("sisi", $json, $fid, $s_id, $owner);
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
            INSERT INTO faculty (faculty_name, status, duties, Created_by)
            VALUES (?, 'ON', 0, ?)
        ");
        $stmt->bind_param("si", $new_faculty, $owner);
        $stmt->execute();
        $replace_id = $stmt->insert_id;
    }

    /* ---- Load / create replacement schedule ---- */
    $stmt = $conn->prepare("
        SELECT schedule FROM block_supervisor_list
        WHERE faculty_id=? AND s_id=? AND Created_by = ?
    ");
    $stmt->bind_param("isi", $replace_id, $s_id, $owner);
    $stmt->execute();
    $r = $stmt->get_result();

    if ($nr = $r->fetch_assoc()) {
        $newSch = json_decode($nr['schedule'], true) ?: [];
    } else {
        $newSch = [];
        $ins = $conn->prepare("
            INSERT INTO block_supervisor_list (faculty_id, s_id, schedule, Created_by)
            VALUES (?, ?, '{}', ?)
        ");
        $ins->bind_param("isi", $replace_id, $s_id, $owner);
        $ins->execute();
    }

    /* ---- Assign slot to replacement ---- */
    $newSch[$date][$slot] = [
        'assigned' => true,
        'present'  => true,
        'replaced' => $fid,
        'block_type' => 'buffer'
    ];

    $jsonNew = json_encode($newSch, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=? AND Created_by = ?
    ");
    $up->bind_param("sisi", $jsonNew, $replace_id, $s_id, $owner);
    $up->execute();

    /* ---- Remove slot from old faculty ---- */
    unset($schedule[$date][$slot]);

    $jsonOld = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    $up = $conn->prepare("
        UPDATE block_supervisor_list
        SET schedule=?
        WHERE faculty_id=? AND s_id=? AND Created_by = ?
    ");
    $up->bind_param("sisi", $jsonOld, $fid, $s_id, $owner);
    $up->execute();

    $conn->commit();

    echo json_encode(['status'=>200,'msg'=>'Faculty replaced successfully']);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}