<?php
header('Content-Type: application/json');
include './config.php';

$data = json_decode(file_get_contents('php://input'), true);

$action       = $data['action'] ?? '';
$staff_id     = $data['staff_id'] ?? '';
$faculty_name = trim($data['faculty_name'] ?? '');
$courses      = trim($data['courses'] ?? '');
$dept_code    = trim($data['dept_code'] ?? '');
$role         = trim($data['role'] ?? '');
$duties       = trim($data['duties'] ?? '');
$status       = trim($data['status'] ?? '');

if (!$faculty_name || !$dept_code || !$status || !$duties) {
    echo json_encode(['status'=>400,'message'=>'All required fields must be filled','field'=>'staff_form']);
    exit;
}

/* ---------------- ADD STAFF ---------------- */
if ($action === 'add') {

    $stmt = $conn->prepare("
        INSERT INTO faculty 
        (faculty_name, courses, dept_code, role, duties, status)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssis",
        $faculty_name,
        $courses,
        $dept_code,
        $role,
        $duties,
        $status
    );

    if ($stmt->execute()) {
        echo json_encode(['status'=>200,'message'=>'Staff added successfully','field'=>'staff_form']);
    } else {
        echo json_encode(['status'=>500,'message'=>'Failed to add staff','field'=>'staff_form']);
    }
    exit;
}

/* ---------------- UPDATE STAFF ---------------- */
if ($action === 'update' && $staff_id) {

    $stmt = $conn->prepare("
        UPDATE faculty SET
            faculty_name = ?,
            courses = ?,
            dept_code = ?,
            role = ?,
            duties = ?,
            status = ?
        WHERE id = ?
    ");
    $stmt->bind_param(
        "ssssisi",
        $faculty_name,
        $courses,
        $dept_code,
        $role,
        $duties,
        $status,
        $staff_id
    );

    if ($stmt->execute()) {
        echo json_encode(['status'=>200,'message'=>'Staff updated successfully','field'=>'staff_form']);
    } else {
        echo json_encode(['status'=>500,'message'=>'Failed to update staff','field'=>'staff_form']);
    }
    exit;
}

echo json_encode(['status'=>400,'message'=>'Invalid request','field'=>'staff_form']);