<?php
include './config.php';

/* ---------------- SETTINGS ---------------- */
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);
error_reporting(0);

/* ---------------- READ JSON ---------------- */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    'status'  => 400,
    'message' => 'Invalid request',
    'field'   => 'slot_form'
];

/* ---------------- INPUTS ---------------- */
$action          = $data['action'] ?? '';
$slot_id         = $data['slot_id'] ?? '';
$exam_name       = trim($data['exam_name'] ?? '');
$slot_mode       = trim($data['slot_mode'] ?? '');
$slot_start_time = trim($data['slot_start_time'] ?? '');
$slot_end_time   = trim($data['slot_end_time'] ?? '');

/* ---------------- VALIDATION ---------------- */
if ($action !== 'delete') {
    if (
        $exam_name === '' ||
        $slot_mode === '' ||
        $slot_start_time === '' ||
        $slot_end_time === ''
    ) {
        echo json_encode([
            'status'  => 422,
            'message' => 'All fields are required',
            'field'   => 'slot_form'
        ]);
        exit;
    }

    if ($slot_start_time === $slot_end_time) {
        echo json_encode([
            'status'  => 422,
            'message' => 'Start time and End time cannot be the same',
            'field'   => 'slot_form'
        ]);
        exit;
    }
}
/* =========================================================
   ADD SLOT
========================================================= */
if ($action === 'add') {

    // 🔍 Duplicate check (same exam + mode + time)
    $check = mysqli_query($conn, "
        SELECT id FROM exam_slots
        WHERE exam_name = '$exam_name'
        AND mode = '$slot_mode'
        AND start_time = '$slot_start_time'
        AND end_time = '$slot_end_time'
    ");

    if (mysqli_num_rows($check) > 0) {
        echo json_encode([
            'status'  => 409,
            'message' => 'This exam slot already exists',
            'field'   => 'slot_form'
        ]);
        exit;
    }

    $sql = "
        INSERT INTO exam_slots
            (exam_name, mode, start_time, end_time)
        VALUES
            ('$exam_name', '$slot_mode', '$slot_start_time', '$slot_end_time')
    ";

    if (mysqli_query($conn, $sql)) {
        $response = [
            'status'  => 200,
            'message' => 'Slot added successfully',
            'field'   => 'slot_form'
        ];
    } else {
        $response = [
            'status'  => 500,
            'message' => 'Failed to add slot',
            'field'   => 'slot_form'
        ];
    }
}

/* =========================================================
   UPDATE SLOT
========================================================= */
if ($action === 'update') {

    if ($slot_id === '') {
        echo json_encode([
            'status'  => 400,
            'message' => 'Invalid slot ID',
            'field'   => 'slot_form'
        ]);
        exit;
    }

    // 🔍 Duplicate check except current slot
    $check = mysqli_query($conn, "
        SELECT id FROM exam_slots
        WHERE exam_name = '$exam_name'
        AND mode = '$slot_mode'
        AND start_time = '$slot_start_time'
        AND end_time = '$slot_end_time'
        AND id != '$slot_id'
    ");

    if (mysqli_num_rows($check) > 0) {
        echo json_encode([
            'status'  => 409,
            'message' => 'This exam slot already exists',
            'field'   => 'slot_form'
        ]);
        exit;
    }

    $sql = "
        UPDATE exam_slots SET
            exam_name = '$exam_name',
            mode = '$slot_mode',
            start_time = '$slot_start_time',
            end_time = '$slot_end_time'
        WHERE id = '$slot_id'
    ";

    if (mysqli_query($conn, $sql)) {
        $response = [
            'status'  => 200,
            'message' => 'Slot updated successfully',
            'field'   => 'slot_form'
        ];
    } else {
        $response = [
            'status'  => 500,
            'message' => 'Failed to update slot',
            'field'   => 'slot_form'
        ];
    }
}

/* =========================================================
   DELETE SLOT
========================================================= */
if ($action === 'delete') {

    if ($slot_id === '') {
        echo json_encode([
            'status'  => 400,
            'message' => 'Invalid slot ID',
            'field'   => 'slot_form'
        ]);
        exit;
    }

    if (mysqli_query($conn, "DELETE FROM exam_slots WHERE id = '$slot_id'")) {
        $response = [
            'status'  => 200,
            'message' => 'Slot deleted successfully',
            'field'   => 'slot_form'
        ];
    } else {
        $response = [
            'status'  => 500,
            'message' => 'Failed to delete slot',
            'field'   => 'slot_form'
        ];
    }
}

/* ---------------- RESPONSE ---------------- */
echo json_encode($response);
