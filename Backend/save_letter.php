<?php
include './config.php';

/* ===============================
   0. LOAD EXISTING DATA
   =============================== */
$data = [];

$res = mysqli_query($conn, "SELECT letter_json FROM admin_panel WHERE id = 1");
if ($row = mysqli_fetch_assoc($res)) {
    $data = json_decode($row['letter_json'], true) ?? [];
}

/* ===============================
   1. HANDLE IMAGE (SIGNATURE)
   =============================== */
$uploadDir = "../upload/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!empty($_FILES)) {
    foreach ($_FILES as $key => $file) {

        // ❗ No new file → KEEP OLD
        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                "status" => "error",
                "message" => "Error uploading file: $key"
            ]);
            exit;
        }

        $allowedExt = ['png', 'jpg', 'jpeg'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) {
            echo json_encode([
                "status" => "error",
                "message" => "Invalid file type"
            ]);
            exit;
        }

        if ($file['size'] > (2 * 1024 * 1024)) {
            echo json_encode([
                "status" => "error",
                "message" => "File Size must be less than 2MB"
            ]);
            exit;
        }

        // Optional: delete old file
        if (!empty($data[$key])) {
            $oldPath = $uploadDir . $data[$key];
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $newName = "1_{$key}." . $ext;

        if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to move uploaded file: $key"
            ]);
            exit;
        }

        // ✅ Override only this image
        $data[$key] = $newName;
    }
}

/* ===============================
   2. HANDLE TEXT + RADIO VALUES
   =============================== */
foreach ($_POST as $key => $value) {
    $data[$key] = trim($value);
}

/* ===============================
   3. SAVE
   =============================== */
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