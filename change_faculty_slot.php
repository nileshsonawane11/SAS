<?php
include "./Backend/config.php";

/* ================= READ JSON ================= */
$data = json_decode(file_get_contents("php://input"), true);

$fid        = $data['fid'] ?? '';
$date       = $data['date'] ?? '';
$slot       = $data['slot'] ?? '';
$s_id       = $data['s_id'] ?? '';
$present    = $data['present'] ?? 'yes';
$reason     = $data['reason'] ?? '';
$replace_id = $data['replace_id'] ?? '';

if (!$fid || !$date || !$slot || !$s_id) {
    http_response_code(400);
    echo json_encode(['status'=>'error','msg'=>'Invalid input']);
    exit;
}

/* ================= LOAD CURRENT FACULTY ================= */
$res = mysqli_query($conn,"
    SELECT schedule FROM block_supervisor_list
    WHERE faculty_id='$fid' AND s_id='$s_id'
");

$row = mysqli_fetch_assoc($res);
$schedule = json_decode($row['schedule'] ?? '{}', true);

/* ================= PRESENT = YES ================= */
if ($present === 'yes') {

    $schedule[$date][$slot]['present'] = true;
    $schedule[$date][$slot]['reason']  = '';

    mysqli_query($conn,"
        UPDATE block_supervisor_list
        SET schedule='".json_encode($schedule)."'
        WHERE faculty_id='$fid' AND s_id='$s_id'
    ");

    echo json_encode(['status'=>'ok','msg'=>'Marked present']);
    exit;
}

/* ================= PRESENT = NO ================= */
$schedule[$date][$slot]['present'] = false;
$schedule[$date][$slot]['reason']  = $reason;

/* ================= NO REPLACEMENT ================= */
if ($reason !== 'replace') {

    mysqli_query($conn,"
        UPDATE block_supervisor_list
        SET schedule='".json_encode($schedule)."'
        WHERE faculty_id='$fid' AND s_id='$s_id'
    ");

    echo json_encode(['status'=>'ok','msg'=>'Absence recorded']);
    exit;
}

/* ================= REPLACE FACULTY ================= */
if (!$replace_id) {
    echo json_encode(['status'=>'error','msg'=>'Replacement faculty missing']);
    exit;
}

/* ---------- Load / Create New Faculty Slot ---------- */
$r = mysqli_query($conn,"
    SELECT schedule FROM block_supervisor_list
    WHERE faculty_id='$replace_id' AND s_id='$s_id'
");

if ($nr = mysqli_fetch_assoc($r)) {
    $newSch = json_decode($nr['schedule'], true);
} else {
    $newSch = [];
    mysqli_query($conn,"
        INSERT INTO block_supervisor_list (faculty_id, s_id, schedule)
        VALUES ('$replace_id','$s_id','{}')
    ");
}

/* ---------- Assign Slot ---------- */
$newSch[$date][$slot] = [
    'assigned' => true,
    'present'  => true,
    'replaced' => $fid
];

mysqli_query($conn,"
    UPDATE block_supervisor_list
    SET schedule='".json_encode($newSch)."'
    WHERE faculty_id='$replace_id' AND s_id='$s_id'
");

/* ---------- Remove Slot From Old Faculty ---------- */
unset($schedule[$date][$slot]);

mysqli_query($conn,"
    UPDATE block_supervisor_list
    SET schedule='".json_encode($schedule)."'
    WHERE faculty_id='$fid' AND s_id='$s_id'
");

echo json_encode(['status'=>200,'msg'=>'Faculty replaced successfully']);
exit;
