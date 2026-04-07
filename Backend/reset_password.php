<?php
ob_start();
session_start();
error_reporting(0);
header('Content-Type: application/json');
include './config.php';
$data = file_get_contents('php://input');
$data = json_decode($data, true);

$email = mysqli_real_escape_string($conn, $data['email']);
$otp = mysqli_real_escape_string($conn, $data['otp']);
$password = mysqli_real_escape_string($conn, $data['password']);
$password2 = mysqli_real_escape_string($conn, $data['password2']);

if (empty($data)) {
    echo json_encode(['status' => 400, 'message' => 'Invalid JSON','field' => 'empty']);
    exit();
}

if($email == "" || $otp == "" || $password == "" || $password2 == "")
{
    echo json_encode(['status' => 400, 'message' => 'All fields are required', 'field' => 'empty']);
    exit();
}

if($password !== $password2 ){
    echo json_encode(['status' => 400, 'message' => 'Passwords do not match', 'field' => 'password']);
    exit();
}else if(!preg_match('/[A-Z]/', $password) || 
!preg_match('/[a-z]/', $password) || 
!preg_match('/[0-9]/', $password) || 
!preg_match('/[\W]/', $password)){
    echo json_encode(['status' => 400, 'message' => 'Password must include uppercase, lowercase, number, and special character', 'field' => 'password']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 409, 'message' => 'Enter Valid Email', 'field' => 'email']);
    exit();
}

$recipient_email = $_SESSION['recipient_email'];
$otp_check = $_SESSION['otpforgot'][$recipient_email];
if($otp != $otp_check) {
    echo json_encode(['status' => 409, 'message' => 'Invalid OTP', 'field' => 'otp']);
    exit();
}elseif(strlen($otp) < 4 || strlen($otp) > 4) {
    echo json_encode(['status' => 409, 'message' => 'Invalid OTP', 'field' => 'otp']);
    exit();
}

$otp_validity = 600; // 10 minutes (600 seconds)

if (isset($_SESSION['otp_created'][$recipient_email])) {

    $otp_created_time = $_SESSION['otp_created'][$recipient_email];
    $current_time = time();

    if (($current_time - $otp_created_time) > $otp_validity) {
        unset($_SESSION['otp_created'][$recipient_email]);
        respond('error','Invalid OTP');
    }

}

$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Check if email exists
$sql1 = "SELECT password FROM users WHERE email='$email' LIMIT 1";
$result1 = mysqli_query($conn, $sql1);

if (mysqli_num_rows($result1) > 0) {
    $row = mysqli_fetch_assoc($result1);

    // Check if new password matches current password
    if (password_verify($password, $row['password'])) {
        echo json_encode(['status' => 400, 'message' => 'New password cannot be the same as the current password.', 'field' => 'password']);
        exit();
    }

    // Update password if different
    $sql = "UPDATE users SET password='$password_hash' WHERE email='$email'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        echo json_encode(['status' => 200, 'message' => 'Password updated successfully', 'field' => 'email']);
    } else {
        echo json_encode(['status' => 400, 'message' => 'An error occurred while updating password', 'field' => 'email']);
    }
} else {
    echo json_encode(['status' => 404, 'message' => 'User not found', 'field' => 'email']);
}
exit();

?>