<?php
header('Content-Type: application/json');
include './config.php';
error_reporting(1);

$data = json_decode(file_get_contents("php://input"), true);

$block_no    = trim($data['block_no'] ?? '');
$course_code = trim($data['course_code'] ?? '');
$date        = $data['date'] ?? '';
$time        = $data['time'] ?? '';
$s           = trim($data['s_id'] ?? '');

if ($block_no == '') {
    echo json_encode([
        'status' => 400,
        'field' => 'block_error',
        'message' => 'Block is required'
    ]);
    exit;
}

if ($course_code == '') {
    echo json_encode([
        'status' => 400,
        'field' => 'block_error',
        'message' => 'Course code is required'
    ]);
    exit;
}

if ($date == '') {
    echo json_encode([
        'status' => 400,
        'field' => 'date',
        'field' => 'block_error',
        'message' => 'Date is required'
        
    ]);
    exit;
}

if ($time == '') {
    echo json_encode([
        'status' => 400,
        'field' => 'time',
        'field' => 'block_error',
        'message' => 'Time is required'
    ]);
    exit;
}

/* Prevent duplicate block at same date & time */
$check = $conn->prepare(
    "SELECT id FROM block_schedule 
     WHERE block_no = ? AND schedule_date = ? AND schedule_time = ?"
);
$check->bind_param("sss", $block_no, $date, $time);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        'status' => 409,
        'field' => 'block_error',
        'message' => 'Block already scheduled at this time'
    ]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO block_schedule (block_no, course_code, schedule_date, schedule_time, s_id)
     VALUES (?, ?, ?, ?, ?)"
);
$stmt->bind_param("sssss", $block_no, $course_code, $date, $time, $s);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 200,
        'message' => 'Block scheduled successfully'
    ]);
} else {
    echo json_encode([
        'status' => 500,
        'message' => 'Database error'
    ]);
}