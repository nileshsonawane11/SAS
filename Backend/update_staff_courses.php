<?php
include './config.php';
require './auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);



$staff_id = intval($data['staff_id']);
$courses  = implode(',', array_values($data['courses'])); // store as CSV

$stmt = mysqli_prepare(
    $conn,
    "UPDATE faculty SET courses = ? WHERE id = ? AND Created_by = ?"
);

mysqli_stmt_bind_param($stmt, "sii", $courses, $staff_id, $owner);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 200]);
} else {
    echo json_encode(['status' => 500, 'message' => 'DB error']);
}