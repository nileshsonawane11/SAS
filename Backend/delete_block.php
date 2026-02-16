<?php
header('Content-Type: application/json');
include './config.php'; // DB connection
require './auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

/* Validate input */
if (!isset($input['ids']) || !is_array($input['ids']) || count($input['ids']) === 0) {
    echo json_encode([
        'status'  => 400,
        'message' => 'Block IDs missing'
    ]);
    exit;
}

/* Sanitize IDs */
$ids = array_map('intval', $input['ids']);

/* Create placeholders (?, ?, ?) */
$placeholders = implode(',', array_fill(0, count($ids), '?'));

/* Prepare statement */
$sql = "DELETE FROM Blocks WHERE id IN ($placeholders) AND Created_by = ?";
$stmt = $conn->prepare($sql);

$params = array_merge($ids, [$owner]);   // all values
$types = str_repeat('i', count($params)); // all are integers

// bind_param requires references
$bindArgs = [];
$bindArgs[] = & $types;

foreach ($params as $key => $value) {
    $bindArgs[] = & $params[$key]; // references!
}

call_user_func_array([$stmt, 'bind_param'], $bindArgs);

/* Execute */
if ($stmt->execute()) {
    echo json_encode([
        'status'  => 200,
        'message' => 'Blocks deleted successfully',
        'deleted' => $stmt->affected_rows
    ]);
} else {
    echo json_encode([
        'status'  => 500,
        'message' => 'Failed to delete blocks'
    ]);
}

$stmt->close();
$conn->close();