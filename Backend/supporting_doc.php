<?php

require "config.php";  // gives $conn (mysqli)
session_start();

$action = $_GET['action'] ?? '';
$owner_id = $_SESSION['uid']['_id'];


if ($action === "add") {
    addDocument($conn);
}

if ($action === "delete") {
    deleteDocument($conn);
}


/* ============================================================
    ADD DOCUMENT  (FormData upload)
   ============================================================ */
function addDocument($conn) {
    global $owner_id;

    if (!isset($_FILES["doc"])) {
        echo json_encode(["message" => "No file uploaded $owner_id"]);
        return;
    }

    $docType  = $_POST["doc_type"];
    $file     = $_FILES["doc"];

    $uploadDir = "../upload/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Save file as: owner_id_time_originalname.ext
    $newName = $owner_id . "_" . $docType . "_" . basename($file["name"]);
    $targetPath = $uploadDir . $newName;

    move_uploaded_file($file["tmp_name"], $targetPath);

    // Update admin_panel
    $docType = mysqli_real_escape_string($conn, $docType);
    $newName = mysqli_real_escape_string($conn, $newName);

    $sql = "
        UPDATE admin_panel 
        SET $docType = '$newName'
        WHERE admin = '$owner_id'
    ";

    mysqli_query($conn, $sql);

    echo json_encode(["message" => "Uploaded & Saved"]);
}



/* ============================================================
    DELETE DOCUMENT
   ============================================================ */
function deleteDocument($conn) {
    global $owner_id;

    if (!isset($_POST["doc_name"])) {
        echo json_encode(["message" => "Invalid request"]);
        return;
    }

    $docType  = $_POST["doc_type"];
    $docName  = $_POST["doc_name"];

    $filePath = "../upload/" . $docName;

    // Delete from folder
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Remove from database
    $sql = "
        UPDATE admin_panel 
        SET $docType = NULL 
        WHERE admin = '$owner_id'
    ";

    mysqli_query($conn, $sql);

    echo json_encode(["message" => "Document Deleted"]);
}

?>