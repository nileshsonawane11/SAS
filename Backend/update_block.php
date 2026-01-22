<?php
include './config.php';
error_reporting(E_ALL);

$s_id  = $_POST['s_id'];
$fid   = $_POST['faculty_id'];
$date  = $_POST['date'];
$slot  = $_POST['slot'];
$block = strtoupper(trim($_POST['block']));

/* ===============================
   1. CHECK BLOCK CONFLICT
================================ */
$checkStmt = $conn->prepare("
    SELECT faculty_id, schedule
    FROM block_supervisor_list
    WHERE s_id = ?
");
$checkStmt->bind_param("i", $s_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

while ($row = $result->fetch_assoc()) {

    $otherFid = $row['faculty_id'];
    $sch = json_decode($row['schedule'], true);

    if (
        isset($sch[$date][$slot]['block']) &&
        strtoupper($sch[$date][$slot]['block']) === $block &&
        $otherFid != $fid && ($sch[$date][$slot]['block']) !== ''
    ) {
        echo json_encode([
            'status' => 400,
            'msg' => "Block $block already assigned to another faculty"
        ]);
        exit;
    }
}

/* ===============================
   2. FETCH CURRENT SCHEDULE
================================ */
$getStmt = $conn->prepare("
    SELECT schedule
    FROM block_supervisor_list
    WHERE s_id = ? AND faculty_id = ?
");
$getStmt->bind_param("ii", $s_id, $fid);
$getStmt->execute();
$res = $getStmt->get_result();

$row = $res->fetch_assoc();
$schedule = json_decode($row['schedule'], true);

/* ===============================
   3. UPDATE JSON
================================ */
$schedule[$date][$slot]['assigned'] = true;
$schedule[$date][$slot]['block']    = $block;

$jsonSchedule = json_encode($schedule, JSON_UNESCAPED_UNICODE);

/* ===============================
   4. UPDATE DATABASE (SAFE)
================================ */
$updateStmt = $conn->prepare("
    UPDATE block_supervisor_list
    SET schedule = ?
    WHERE s_id = ? AND faculty_id = ?
");
$updateStmt->bind_param("sii", $jsonSchedule, $s_id, $fid);
$updateStmt->execute();

/* ===============================
   5. RESPONSE
================================ */
echo json_encode([
    'status' => 200,
    'msg' => "Updated"
]);