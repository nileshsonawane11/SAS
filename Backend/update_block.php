<?php
include './config.php';

$s_id  = $_POST['s_id'];
$fid   = $_POST['faculty_id'];
$date  = $_POST['date'];
$slot  = $_POST['slot'];
$block = trim($_POST['block']);

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

echo "UPDATED";