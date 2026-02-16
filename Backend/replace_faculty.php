<?php
require 'config.php';
require './auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;

$data = json_decode(file_get_contents("php://input"), true);

$old = (int)$data['old_fid'];
$new = (int)$data['new_fid'];
$s_id = $data['s_id'];

if(empty($old) || empty($new)){
    echo json_encode(['success' => false, 'error' => 'Faculty Is Unavailable']);
    return;
}

mysqli_begin_transaction($conn);

try {
    $sql = "
    UPDATE block_supervisor_list
    SET faculty_id = $new
    WHERE faculty_id = $old
    and s_id = '$s_id' AND Created_by = '$owner'
    ";
    mysqli_query($conn, $sql);

    mysqli_commit($conn);
    echo json_encode(['success' => true]);
    return;

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => 'Replacement failed']);
    return;
}
