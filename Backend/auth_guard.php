<?php
session_start();
require_once __DIR__ . '/config.php';
$user_data = '';

define('REMEMBER_SECRET','CHANGE_THIS_TO_LONG_RANDOM_SECRET_123!@#');

// Simple logout function
function force_logout() {
    $_SESSION = [];
    session_destroy();

    if (!empty($_COOKIE['REMEMBERME'])) {
        setcookie('REMEMBERME','',time()-2592000,'/');
    }

    header('Location: index.php');
    exit;
}

// Check if user already has a valid session
if (isset($_SESSION['uid'])) {

    $timeout = 1800; // 30 minutes

    if (!isset($_SESSION['login_time']) || (time() - $_SESSION['login_time'] > $timeout)) {
        session_regenerate_id(true);
        $_SESSION['login_time'] = time();
    } else {
        $_SESSION['login_time'] = time();
    }

    // Regenerate session ID safely

    $user_data = $_SESSION['uid']; 

    return;
}

// If no session, check remember-me cookie
if (!empty($_COOKIE['REMEMBERME'])) {

    $raw = base64_decode($_COOKIE['REMEMBERME']);
    if ($raw && strpos($raw,'::') !== false) {

        list($payload, $sig) = explode('::', $raw, 2);

        // verify signature
        if (hash_equals(hash_hmac('sha256',$payload,REMEMBER_SECRET), $sig)) {
            $data = json_decode($payload,true);

            if ($data && $data['exp'] > time()) {
                // restore session
                $_SESSION['uid'] = $data['uid'];
                $_SESSION['login_time'] = time();
                $user_id = $_SESSION['uid'];
                return; // allow page
            }
        }
    }
}

// Not authorized → redirect to login
force_logout();