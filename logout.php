<?php
    session_start();

    $_SESSION = [];

    // Destroy session
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    // Remove remember me cookie
    if (isset($_COOKIE['REMEMBERME'])) {
        setcookie(
            'REMEMBERME',
            '',
            time() - 2592000,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }

    echo json_encode([
        "status" => 200
    ]);
    exit;
?>