<?php
header('Content-Type: application/json');
include './config.php'; // DB connection

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
$sql = "DELETE FROM Blocks WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);

/* Bind dynamically */
$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...$ids);

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