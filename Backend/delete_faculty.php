<?php
header('Content-Type: application/json');
include './config.php'; // Your DB connection

// Get raw JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['status'=>400,'message'=>'Faculty ID missing']);
    exit;
}

$id = intval($input['id']);

// Delete query
$stmt = $conn->prepare("DELETE FROM faculty WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo json_encode(['status'=>200,'message'=>'Faculty deleted']);
} else {
    echo json_encode(['status'=>500,'message'=>'Failed to delete faculty']);
}
