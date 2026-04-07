<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
error_reporting(1);
ini_set('display_errors', 1);

require 'vendor/autoload.php';
include './Backend/config.php';
$data = file_get_contents('php://input');
$data = json_decode($data,true);
session_start();
$recipient_email = $data['email'];
$name = ($data['s_name']);
$for = $data['for'];
$otp_type = 'otp'.$for;


if ($for == 'registration') {
    $message_text = "You are required to enter the following code to verify your account on <b>AssignPro – Supervision Allocation System</b>. The code is valid for <b>10 minutes</b>. Please enter the code in the verification field to continue.";
} 
elseif ($for == 'forgot') {
    $message_text = "You requested a password reset for your <b>AssignPro – Supervision Allocation System</b> account. Please use the code below to reset your password. The code is valid for <b>10 minutes</b>. If you didn't request this, you can safely ignore this email.";
} 
else {
    $message_text = "Your OTP verification code for <b>AssignPro – Supervision Allocation System</b> is provided below.";
}

function get_email_content($otp){
    global $message_text;
    global $name;
    return "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>AssignPro Verification</title>

        <style>

        body{
            font-family: Arial, sans-serif;
            background:#f0f0f0;
            padding:20px;
        }

        .container{
            max-width:600px;
            margin:auto;
            background:#fff;
            border-radius:10px;
            overflow:hidden;
            box-shadow:0 0 15px rgba(0,0,0,0.2);
        }

        .header{
            background:#0056b3;
            color:white;
            text-align:center;
            padding:20px;
            font-size:22px;
            letter-spacing:1px;
        }

        .image-header{
            text-align:center;
            padding:20px;
        }

        .image-header img{
            max-width:100px;
        }

        .title{
            font-size:22px;
            font-weight:bold;
            text-align:center;
            color:#28a745;
            margin: 20px;
        }

        .content{
            padding:20px;
            background:#f9f9f9;
            font-size:16px;
            line-height:1.6;
        }

        .otp-text{
            text-align:center;
            margin-top:20px;
            font-weight:bold;
        }

        .otp-box{
            font-size:30px;
            letter-spacing:10px;
            text-align:center;
            margin:15px 0;
            font-weight:bold;
        }

        .footer{
            text-align:center;
            font-size:13px;
            padding:20px;
            color:#555;
        }

        .footer a{
            color:#007bff;
            text-decoration:none;
        }

        hr{
            border:none;
            height:1px;
            background:#ccc;
        }

        </style>

        </head>

        <body>

        <div class='container'>

        <div class='header'>
        AssignPro Supervision Allocation System
        </div>

        <div class='title'>
        Verify Your Identity
        </div>

        <div class='content'>

        Hello $name,<br><br>

        $message_text

        <div class='otp-text'>Your Verification Code</div>

        <div class='otp-box'>$otp</div>

        </div>

        <hr>

        <div class='footer'>

        <p>If this request was not made by you, please ignore this email or contact the system administrator.</p>

        <p>
        </p>

        <br>

        <p>© 2026 AssignPro. All rights reserved.</p>

        <p>Supervision Allocation System</p>

        <p>Nashik, Maharashtra, India</p>

        </div>

        </div>

        </body>
        </html>
    ";
}

function generateOTP($otp_type, $recipient_email) {
    $otp = rand(1000, 9999);
    $_SESSION['recipient_email'] = $recipient_email;
    $_SESSION[$otp_type][$recipient_email] = $otp;
    $_SESSION['otp_created'][$recipient_email] = time();
}

$sql1 = "SELECT * FROM users WHERE email='$recipient_email' LIMIT 1";
$result1 = mysqli_query($conn, $sql1);

if($for == "registration"){
    if (mysqli_num_rows($result1) > 0) {
        while ($row = mysqli_fetch_assoc($result1)) {
            if ($row['email'] === $recipient_email){
                echo json_encode(["status" => "error", "message" => "Email already Used","for" => "$for"]);
                exit();
            }
        }
    }else{

        generateOTP($otp_type, $recipient_email);

        flush();
        ob_flush();
        $otp = $_SESSION[$otp_type][$recipient_email];
        $email_content = get_email_content($otp);
                                                                                       
        // Send email using PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'livestrike.in@gmail.com'; // Change to your email
            $mail->Password = 'sdie phiv vbgk qymy'; // Use App Password if required
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('livestrike.in@gmail.com', 'LiveStrike'); // Sender

            // $mail->isSMTP();
            // $mail->Host = 'smtp.hostinger.com';
            // $mail->SMTPAuth = true;
            // $mail->Username = 'admin@livestrike.in'; // Change to your email
            // $mail->Password = 'Livestrike@123'; // Use App Password if required
            // $mail->SMTPSecure = 'ssl';
            // $mail->Port = 465;

            // $mail->setFrom('admin@livestrike.in', 'LiveStrike'); // Sender
            $mail->addAddress($recipient_email); // Recipient
            $mail->Subject = 'Assign - Pro OTP Verification';
            $mail->isHTML(true);                                                                    
            $mail->Body = $email_content;

            if ($mail->send()) {
                echo json_encode(["status" => "success", "message" => "Email sent successfully","for" => "$for"]);
                exit();
            }
        } catch (Exception $e) {
            $otp_error = 'Email failed: ' . $mail->ErrorInfo;
            echo json_encode(["status" => "error", "message" => "$otp_error"]);
            exit();
        }
    }
}elseif($for == 'forgot'){

    if (mysqli_num_rows($result1) > 0) {
        while ($row = mysqli_fetch_assoc($result1)) {
        
            if ($row['email'] === $recipient_email){
                generateOTP($otp_type, $recipient_email);

                flush();
                ob_flush();
                $otp = $_SESSION[$otp_type][$recipient_email];
                $email_content = get_email_content($otp);
                                                                                           
                // Send email using PHPMailer
                $mail = new PHPMailer(true);

                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'livestrike.in@gmail.com'; // Change to your email
                    $mail->Password = 'sdie phiv vbgk qymy'; // Use App Password if required
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('livestrike.in@gmail.com', 'LiveStrike'); // Sender

                    // $mail->isSMTP();
                    // $mail->Host = 'smtp.hostinger.com';
                    // $mail->SMTPAuth = true;
                    // $mail->Username = 'admin@livestrike.in'; // Change to your email
                    // $mail->Password = 'Livestrike@123'; // Use App Password if required
                    // $mail->SMTPSecure = 'ssl';
                    // $mail->Port = 465;

                    // $mail->setFrom('admin@livestrike.in', 'LiveStrike'); // Sender
                    $mail->addAddress($recipient_email); // Recipient
                    $mail->Subject = 'Assign - Pro OTP Verification';
                    $mail->isHTML(true);                                                                    
                    $mail->Body = $email_content;

                    if ($mail->send()) {
                        echo json_encode(["status" => "success", "message" => "Email sent successfully","for" => "$for"]);
                        exit();
                    }
                } catch (Exception $e) {
                    $otp_error = 'Email failed: ' . $mail->ErrorInfo;
                    echo json_encode(["status" => "error", "message" => "$otp_error"]);
                    exit();
                }
            }else{
                echo json_encode(["status" => "error", "message" => "Email not registered","for" => "$for"]);
                exit();
            }
        
        }
    }else{
        echo json_encode(["status" => "error", "message" => "Email not registered","for" => "$for"]);
        exit();
    }

}else{
    echo json_encode(["status" => "error", "message" => "Invalid Credentials"]);
    exit();
}

?>