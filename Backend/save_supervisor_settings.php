<?php
header('Content-Type: application/json');
include './config.php';
error_reporting(1);

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status'=>'error','message'=>'Invalid JSON data']);
    exit;
}

// --- Validation ---
// Define allowed numeric fields
$numericFields = ['block_capacity', 'reliever', 'extra_faculty', 'teaching_staff', 'non_teaching_staff'];

// Define allowed text fields
$textFields = ['duties_restriction', 'role_restriction', 'sub_restriction', 'dept_restriction'];

// Initialize validated array
$validated = [];

// Validate numeric fields
foreach ($numericFields as $field) {
    if (!isset($data[$field]) || !is_numeric($data[$field])) {
        echo json_encode(['status'=>'error','message'=>"Invalid or missing value for $field"]);
        exit;
    }
    // Optionally, limit numeric range
    $val = (float)$data[$field];
    if ($val > 100 && ($field === 'teaching_staff' || $field === 'non_teaching_staff' || $field === 'extra_faculty')) $val = 1;
    $validated[$field] = $val;
}

// Validate text fields
foreach ($textFields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['status'=>'error','message'=>"Invalid or missing value for $field"]);
        exit;
    }
    // Escape for SQL
    $validated[$field] = mysqli_real_escape_string($conn, trim($data[$field]));
}

// --- Prepare SQL ---
$sql = "
UPDATE admin_panel SET
    duties_restriction = '{$validated['duties_restriction']}',
    block_capacity = '{$validated['block_capacity']}',
    reliever = '{$validated['reliever']}',
    extra_faculty = '{$validated['extra_faculty']}',
    role_restriction = '{$validated['role_restriction']}',
    teaching_staff = '{$validated['teaching_staff']}',
    non_teaching_staff = '{$validated['non_teaching_staff']}',
    sub_restriction = '{$validated['sub_restriction']}',
    dept_restriction = '{$validated['dept_restriction']}'
WHERE id = 1
";

// Execute query
if (mysqli_query($conn, $sql)) {
    echo json_encode(['status'=>200,'message'=>'Updated successfully']);
} else {
    echo json_encode(['status'=>'error','message'=>'DB update failed: '.mysqli_error($conn)]);
}
