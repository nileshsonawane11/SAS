<?php
require './config.php';

$data = json_decode(file_get_contents("php://input"), true);
error_reporting(1);
$old_fid = (int)$data['faculty_id'];
$s_id    = $data['s_id'];

/*
We exclude any faculty who already exists
for the SAME s_id on ANY date+slot
where old faculty is assigned.
*/

$sql = "
SELECT f.id, f.faculty_name, f.dept_code, f.role
FROM faculty f
WHERE f.id != $old_fid
AND f.id NOT IN (
    SELECT faculty_id
    FROM block_supervisor_list
    WHERE s_id = '$s_id'
)
ORDER BY f.faculty_name
";

$res = mysqli_query($conn, $sql);

$list = [];
while ($row = mysqli_fetch_assoc($res)) {
    $list[] = $row;
}

echo json_encode($list);
