<?php
header('Content-Type: application/json');
require_once "./config.php"; // provides $conn

/* ================= RESPONSE HELPER ================= */
function respond($success, $message, $extra = []) {
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra));
    exit;
}

/* ================= READ INPUT ================= */
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['faculty_id']) || empty($data['s_id'])) {
    respond(false, "Missing faculty_id or s_id");
}

$faculty_id = (int)$data['faculty_id'];
$s_id       = trim($data['s_id']);

/* ================= BASIC VALIDATION ================= */
if ($faculty_id <= 0) {
    respond(false, "Invalid faculty ID");
}

/* ================= DB TRANSACTION ================= */
$conn->begin_transaction();

try {

    /* ================= CHECK FACULTY EXISTS ================= */
    $checkFaculty = $conn->prepare("
        SELECT id
        FROM faculty
        WHERE id = ? AND status = 'ON'
    ");
    $checkFaculty->bind_param("i", $faculty_id);
    $checkFaculty->execute();
    $checkFaculty->store_result();

    if ($checkFaculty->num_rows === 0) {
        throw new Exception("Faculty not found or inactive");
    }

    /* ================= PREVENT DUPLICATE ================= */
    $check = $conn->prepare("
        SELECT id
        FROM block_supervisor_list
        WHERE faculty_id = ? AND s_id = ?
        FOR UPDATE
    ");
    $check->bind_param("is", $faculty_id, $s_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        throw new Exception("Faculty already exists in supervision list");
    }

    /* ================= INSERT ================= */
    $emptySchedule = json_encode(new stdClass()); // {}

    $insert = $conn->prepare("
        INSERT INTO block_supervisor_list (faculty_id, s_id, schedule)
        VALUES (?, ?, ?)
    ");

    $insert->bind_param("iss", $faculty_id, $s_id, $emptySchedule);

    if (!$insert->execute()) {
        throw new Exception("Failed to add faculty to supervision list");
    }

    $conn->commit();

    /* ================= SUCCESS ================= */
    respond(true, "Faculty added to supervision list", [
        "faculty_id" => $faculty_id,
        "s_id"       => $s_id
    ]);

} catch (Exception $e) {

    $conn->rollback();

    respond(false, $e->getMessage());
}
