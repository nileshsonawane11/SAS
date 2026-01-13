<?php
header('Content-Type: application/json');
error_reporting(1);
include './config.php';

// Read JSON input
// $data = json_decode(file_get_contents("php://input"), true);

// Get values
$task_name = trim($_POST['task_name'] ?? '');
$task_type = trim($_POST['task_type'] ?? '');
$time_table = $_FILES['time_table'];
$id = md5($task_name . $task_type . date('h:i:s'));

// Validation
if ($task_name == '') {
    echo json_encode([
        'status' => 400,
        'field'  => 'task_error',
        'message'=> 'Task name is required'
    ]);
    exit;
}

if ($task_type == '') {
    echo json_encode([
        'status' => 400,
        'field'  => 'task_error',
        'message'=> 'Task type is required'
    ]);
    exit;
}

if(!isset($time_table)){
    echo json_encode([
        'status' => 400,
        'field'  => 'task_error',
        'message'=> 'Time Table is required'
    ]);
    exit;
}

$fileTmp  = $time_table['tmp_name'];
$fileName = $time_table['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($ext !== 'csv') {
    echo json_encode([
        'status' => 400,
        'field'  => 'task_error',
        'message'=> 'Invalid file type. Upload CSV only.'
    ]);
    exit;
}

/* ---------------- DUPLICATE CHECK ---------------- */
$check = $conn->prepare(
    "SELECT id FROM schedule WHERE task_name = ? AND task_type = ? LIMIT 1"
);
$check->bind_param("ss", $task_name, $task_type);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo json_encode([
        'status' => 409,
        'field'  => 'task_error',
        'message'=> 'This task already exists'
    ]);
    exit;
}
$check->close();
/* ------------------------------------------------- */

// Insert query
$stmt = $conn->prepare(
    "INSERT INTO schedule (id, task_name, task_type) VALUES (?, ?, ?)"
);
$stmt->bind_param("sss", $id, $task_name, $task_type);

if ($stmt->execute()) {
    move_uploaded_file($fileTmp,"../upload/$id.$ext");
    echo json_encode([
        'status' => 200,
        'message'=> 'Schedule added successfully',
        'id'      => $id
    ]);
} else {
    echo json_encode([
        'status' => 500,
        'message'=> 'Database error'
    ]);
}

$stmt->close();
$conn->close();