<?php
include './config.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['ids'])) {
    $ids = array_map('intval', $data['ids']);
    $idList = implode(',', $ids);

    $sql = "DELETE FROM faculty WHERE id IN ($idList)";

    if (mysqli_query($conn, $sql)) {
        echo json_encode(['status' => 200]);
    } else {
        echo json_encode(['status' => 400]);
    }
}

mysqli_close($conn);
