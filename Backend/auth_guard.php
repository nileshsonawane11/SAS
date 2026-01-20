<?php
session_start();
require_once __DIR__ . '/config.php';

define('REMEMBER_SECRET','CHANGE_THIS_TO_LONG_RANDOM_SECRET_123!@#');

// Simple logout function
function force_logout() {
    $_SESSION = [];
    session_destroy();

    if (!empty($_COOKIE['REMEMBERME'])) {
        setcookie('REMEMBERME','',time()-3600,'/');
    }

    header('Location: index.php');
    exit;
}

// Check if user already has a valid session
if (isset($_SESSION['uid'])) {

    // Session timeout: 30 min
    if (isset($_SESSION['login_time']) && time() - $_SESSION['login_time'] <= 1800) {
        $_SESSION['login_time'] = time(); // refresh timer
        return; // session valid, allow page
    }

    force_logout(); // expired
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
                return; // allow page
            }
        }
    }
}

// Not authorized → redirect to login
force_logout();