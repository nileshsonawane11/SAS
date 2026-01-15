<?php
include './config.php';

header('Content-Type: application/json');

$s_id = $_POST['s_id'] ?? '';

if (!$s_id) {
    echo json_encode(['status'=>400, 'msg'=>'Invalid ID']);
    exit;
}

/* ===============================
   DELETE FROM DATABASE
================================ */
$stmt = mysqli_prepare($conn, "DELETE FROM schedule WHERE id = ?");
mysqli_stmt_bind_param($stmt, "s", $s_id);
mysqli_stmt_execute($stmt);

if (mysqli_stmt_affected_rows($stmt) <= 0) {
    echo json_encode(['status'=>404, 'msg'=>'Schedule not found']);
    exit;
}

/* ===============================
   DELETE CSV FILE (IF EXISTS)
================================ */
$file = "../upload/$s_id.csv";

if (file_exists($file)) {
    unlink($file);
}

/* ===============================
   SUCCESS
================================ */
echo json_encode(['status'=>200, 'msg'=>'Schedule deleted successfully']);
