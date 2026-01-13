<?php
include 'config.php';

/* ---------------- SETTINGS ---------------- */
header('Content-Type: application/json');
mysqli_report(MYSQLI_REPORT_OFF);
error_reporting(0);

/* ---------------- READ JSON ---------------- */
$data = json_decode(file_get_contents("php://input"), true);

$response = [
    'status' => 400,
    'message' => 'Invalid request',
    'field'   => 'block_form'
];

/* ---------------- INPUTS ---------------- */
$action     = $data['action']     ?? '';
$block_id   = $data['block_id']   ?? '';
$block_no   = trim($data['block_no'] ?? '');
$place      = trim($data['place'] ?? '');
$capacity   = trim($data['capacity'] ?? '');
$double_sit = trim($data['double_sit'] ?? '');

/* ---------------- VALIDATION ---------------- */
if ($block_no === '' || $double_sit === '') {
    echo json_encode([
        'status'  => 422,
        'message' => 'All fields are required',
        'field'   => 'block_form'
    ]);
    exit;
}

/* =========================================================
   ADD BLOCK
========================================================= */
if ($action === 'add') {

    // 🔍 Check duplicate block number
    $check = mysqli_query(
        $conn,
        "SELECT id FROM blocks WHERE block_no = '$block_no'"
    );

    if (mysqli_num_rows($check) > 0) {
        echo json_encode([
            'status'  => 409,
            'message' => 'Block number already exists',
            'field'   => 'block_form'
        ]);
        exit;
    }

    $sql = "INSERT INTO blocks (block_no, place, capacity, double_sit)
            VALUES ('$block_no', '$place', '$capacity', '$double_sit')";

    if (mysqli_query($conn, $sql)) {
        $response = [
            'status'  => 200,
            'message' => 'Block added successfully',
            'field'   => 'block_form'
        ];
    } else {
        $response = [
            'status'  => 500,
            'message' => 'Failed to add block',
            'field'   => 'block_form'
        ];
    }
}

/* =========================================================
   UPDATE BLOCK
========================================================= */
if ($action === 'update') {

    if ($block_id === '') {
        echo json_encode([
            'status'  => 400,
            'message' => 'Invalid block ID',
            'field'   => 'block_form'
        ]);
        exit;
    }

    // 🔍 Check duplicate except current block
    $check = mysqli_query(
        $conn,
        "SELECT id FROM blocks 
         WHERE block_no = '$block_no' AND id != '$block_id'"
    );

    if (mysqli_num_rows($check) > 0) {
        echo json_encode([
            'status'  => 409,
            'message' => 'Block number already exists',
            'field'   => 'block_form'
        ]);
        exit;
    }

    $sql = "UPDATE blocks SET
                block_no   = '$block_no',
                place      = '$place',
                capacity   = '$capacity',
                double_sit = '$double_sit'
            WHERE id = '$block_id'";

    if (mysqli_query($conn, $sql)) {
        $response = [
            'status'  => 200,
            'message' => 'Block updated successfully',
            'field'   => 'block_form'
        ];
    } else {
        $response = [
            'status'  => 500,
            'message' => 'Failed to update block',
            'field'   => 'block_form'
        ];
    }
}

/* ---------------- RESPONSE ---------------- */
echo json_encode($response);
