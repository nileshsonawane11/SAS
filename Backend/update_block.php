<?php
include './config.php';

$s_id  = $_POST['s_id'];
$fid   = $_POST['faculty_id'];
$date  = $_POST['date'];
$slot  = $_POST['slot'];
$block = strtoupper(trim($_POST['block']));

$check = mysqli_query($conn, "
    SELECT faculty_id, schedule
    FROM block_supervisor_list
    WHERE s_id='$s_id'
");

while ($row = mysqli_fetch_assoc($check)) {
    $otherFid = $row['faculty_id'];
    $sch = json_decode($row['schedule'], true);

    if (
        isset($sch[$date][$slot]['block']) &&
        strtoupper($sch[$date][$slot]['block']) === strtoupper($block) &&
        $otherFid != $fid
    ) {
        echo json_encode([
            'status' => 400,
            'msg' => "Block $block already assigned to another faculty"
        ]);
        exit;
    }
}

$res = mysqli_query($conn,"
SELECT schedule FROM block_supervisor_list
WHERE s_id='$s_id' AND faculty_id='$fid'
");

$row = mysqli_fetch_assoc($res);
$schedule = json_decode($row['schedule'], true);

$schedule[$date][$slot]['assigned'] = true;
$schedule[$date][$slot]['block'] = $block; 

mysqli_query($conn,"
UPDATE block_supervisor_list
SET schedule='".json_encode($schedule)."'
WHERE s_id='$s_id' AND faculty_id='$fid'
");

echo json_encode([
    'status' => 200,
    'msg' => "Updated"
]);