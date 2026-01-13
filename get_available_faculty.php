<?php
include './Backend/config.php';

$date = $_GET['date'];
$slot = $_GET['slot'];
$s = $_GET['s'];

$assigned = [];

$res = mysqli_query($conn,"
    SELECT schedule, faculty_id
    FROM block_supervisor_list WHERE s_id = '$s'
");

while($r=mysqli_fetch_assoc($res)){
    $sch = json_decode($r['schedule'],true);
    if(isset($sch[$date]) && !(empty($sch[$date]))){
        $assigned[] = $r['faculty_id'];
    }
}

$where = $assigned ? "WHERE id NOT IN(".implode(',',$assigned).") AND status = 'ON'" : "";

$q = mysqli_query($conn,"
    SELECT id, faculty_name, dept_code
    FROM faculty
    $where
    ORDER BY faculty_name
");

$sr=1;
$out = [];
while($f=mysqli_fetch_assoc($q)){
    $out[] = [
        'sr'   => $sr++,
        'id'   => $f['id'],
        'name' => $f['faculty_name'],
        'dept' => $f['dept_code']
    ];
}

echo json_encode($out);