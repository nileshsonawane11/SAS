<?php
include './config.php';
require './auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;

$data = json_decode(file_get_contents("php://input"), true);

$action = $data['action'] ?? '';
$fid    = (int)($data['fid'] ?? 0);
$s_id   = ($data['s_id'] ?? '');

if ($action === 'delete_faculty') {

    if (!$fid) {
        echo json_encode([
            'status' => 400,
            'msg' => 'Invalid faculty id'
        ]);
        exit;
    }

    /* ===============================
       FETCH SCHEDULE JSON
       =============================== */
    $stmt = $conn->prepare("
        SELECT schedule 
        FROM block_supervisor_list 
        WHERE faculty_id = ? AND s_id = ? AND Created_by = ?
        LIMIT 1
    ");
    $stmt->bind_param("isi", $fid, $s_id, $owner);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo json_encode([
            'status' => 404,
            'msg' => 'Faculty not found'
        ]);
        exit;
    }

    $row = $res->fetch_assoc();
    $scheduleJson = $row['schedule'];

    /* ===============================
       CHECK SCHEDULE CONDITIONS
       =============================== */
    $hasRealBlock = false;

    if (!empty($scheduleJson)) {
        $schedule = json_decode($scheduleJson, true);

        if (is_array($schedule)) {
            foreach ($schedule as $date => $slots) {
                foreach ($slots as $slot => $info) {
                    if (($info['block_type'] ?? '') === 'real') {
                        $hasRealBlock = true;
                        break 2;
                    }
                }
            }
        }
    }

    if ($hasRealBlock) {
        echo json_encode([
            'status' => 403,
            'msg' => 'Cannot delete faculty: real schedule exists'
        ]);
        exit;
    }

    /* ===============================
       SAFE TO DELETE FACULTY
       =============================== */
    $del = $conn->prepare("
        DELETE FROM block_supervisor_list 
        WHERE faculty_id = ? AND s_id = ? AND Created_by = ?
        LIMIT 1
    ");
    $del->bind_param("isi", $fid, $s_id, $owner);

    if ($del->execute()) {
        echo json_encode([
            'status' => 'ok',
            'msg' => 'Faculty deleted successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 500,
            'msg' => 'Delete failed'
        ]);
    }

    exit;
}
?>