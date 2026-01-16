<?php
include './config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);



$staff_id = intval($data['staff_id']);
$courses  = implode(',', array_values($data['courses'])); // store as CSV

$stmt = mysqli_prepare(
    $conn,
    "UPDATE faculty SET courses = ? WHERE id = ?"
);

mysqli_stmt_bind_param($stmt, "si", $courses, $staff_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 200]);
} else {
    echo json_encode(['status' => 500, 'message' => 'DB error']);
}