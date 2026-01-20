<?php
include './config.php';

$response = [];
$data = [];

/* ===============================
   1. HANDLE IMAGE (SIGNATURE)
   =============================== */
$uploadDir = "../upload/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES)) {
    foreach ($_FILES as $key => $file) {

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                "status" => "error",
                "message" => "Error uploading file: " . $key
            ]);
            exit;
        }

        // Validate file type (allow only images)
        $allowedExt = ['png', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid file type upload ['png', 'jpg', 'jpeg']"
            ]);
            exit;
        }

        // Validate file size (e.g., max 2MB)
        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($file['size'] > $maxSize) {
            echo json_encode([
                "status" => "error",
                "message" => "File Size must be less than 2MB"
            ]);
            exit;
        }

        // Generate new unique filename
        $newName = 1 . "_$key." . $ext;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to move uploaded file: " . $key
            ]);
            exit;
        }

        // Store filename in JSON
        $data[$key] = $newName;
    }
}

/* ===============================
   2. HANDLE TEXT + RADIO VALUES
   =============================== */
foreach ($_POST as $key => $value) {
    $data[$key] = trim($value);
}

$json = json_encode($data, JSON_UNESCAPED_UNICODE);

$stmt = mysqli_prepare(
    $conn,
    "UPDATE admin_panel SET letter_json = ? WHERE id = 1"
);

mysqli_stmt_bind_param($stmt, "s", $json);
mysqli_stmt_execute($stmt);

echo json_encode([
    "status" => 200,
    "message" => "Document saved successfully",
    "saved_json" => $data
]);
