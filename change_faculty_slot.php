<?php
// require './Backend/auth_guard.php';
include "./Backend/config.php";

/* ================= READ JSON ================= */
$data = json_decode(file_get_contents("php://input"), true);

$fid        = (int)$data['fid'] ?? '';
$date       = $data['date'] ?? '';
$slot       = $data['slot'] ?? '';
$s_id       = $data['s_id'] ?? '';
$present    = $data['present'] ?? 'yes';
$reason     = $data['reason'] ?? '';
$replace_id = $data['replace_id'] ?? '';
$other_reason = $data['other_reason'] ?? '';
$new_faculty = $data['new_faculty'] ?? '';

/* ================= VALIDATE INPUT ================= */

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
    $schedule[$date][$slot]['other_reason']  = '';

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
$schedule[$date][$slot]['other_reason']  = $other_reason;

/* ================= NO REPLACEMENT ================= */
if ($reason !== 'replace') {

    $jsonSchedule = json_encode($schedule, JSON_UNESCAPED_UNICODE);

    if ($jsonSchedule === false) {
        echo json_encode([
            'status' => 'error',
            'msg' => 'JSON error: ' . json_last_error_msg()
        ]);
        exit;
    }

    $jsonSchedule = mysqli_real_escape_string($conn, $jsonSchedule);

    $sql = "
        UPDATE block_supervisor_list
        SET schedule = '$jsonSchedule'
        WHERE faculty_id = '$fid'
        AND s_id = '$s_id'
    ";

    if (!mysqli_query($conn, $sql)) {
        echo json_encode([
            'status' => 'error',
            'msg' => mysqli_error($conn)
        ]);
        exit;
    }

    if (mysqli_affected_rows($conn) === 0) {
        echo json_encode([
            'status' => 'warn',
            'msg' => 'No matching row found',
            'data'=>$schedule
        ]);
        exit;
    }

    echo json_encode(['status'=>'ok','msg'=>'Absence recorded','data'=>$schedule]);
    exit;
}

/* ================= REPLACE FACULTY ================= */
if (!$replace_id) {

    if (!empty($new_faculty)) {

         // ✅ Define variables FIRST
        $email   = '';
        $mobile  = '';
        $courses = '';
        $status  = 'ON';
        $duties  = 0;
        $role    = '';

        $stmt = $conn->prepare("
            INSERT INTO faculty 
            (faculty_name, email, mobile, courses, status, duties, role)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "sssssis",
            $new_faculty,
            $email,
            $mobile,
            $courses,
            $status,
            $duties,
            $role
        );

        if ($stmt->execute()) {
            $replace_id = $conn->insert_id;   // or $stmt->insert_id
        }else{
            echo json_encode(['status'=>'error','msg'=>'Faculty creation failed']);
            exit;
        }

    } else {
        echo json_encode(['status'=>'error','msg'=>'Replacement faculty missing']);
        exit;
    }
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
