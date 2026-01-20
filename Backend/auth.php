<?php
/* ================= BOOTSTRAP ================= */
session_start();

/* NEVER expose PHP errors in JSON */
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=UTF-8');

require_once './config.php';

define('REMEMBER_SECRET', 'CHANGE_THIS_TO_LONG_RANDOM_SECRET_123!@#');

/* ================= UTILITIES ================= */

function respond($status, $msg, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'msg'    => $msg
    ], $extra));
    exit;
}

function clean($v) {
    return trim(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
}

/* Device fingerprint */
function device_fingerprint() {
    return hash(
        'sha256',
        ($_SERVER['HTTP_USER_AGENT'] ?? '') .
        ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '')
    );
}

function rate_limit() {
    $_SESSION['tries'] = $_SESSION['tries'] ?? 0;
    if ($_SESSION['tries'] >= 5) {
        respond('error', 'Too many failed attempts. Try again later.');
    }
}

/* ================= REQUEST VALIDATION ================= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond('error', 'Invalid request');
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    respond('error', 'Invalid JSON');
}

$action = $data['action'] ?? '';
if (!$action) {
    respond('error', 'Action missing');
}

/* ================= REGISTER ================= */

if ($action === 'register') {

    foreach (['inst','name','email','password'] as $f) {
        if (empty($data[$f])) respond('error','All fields required');
    }

    $inst  = clean($data['inst']);
    $name  = clean($data['name']);
    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    $pass  = $data['password'];

    if (!$email) respond('error','Invalid email');

    if (
        strlen($pass) < 8 ||
        !preg_match('/[A-Z]/', $pass) ||
        !preg_match('/[0-9]/', $pass)
    ) {
        respond('error','Weak password');
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows) respond('error','Email already registered');

    $stmt->close();

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO users (institute_name,name,email,password)
        VALUES (?,?,?,?)
    ");
    $stmt->bind_param("ssss",$inst,$name,$email,$hash);
    $stmt->execute();
    $stmt->close();

    respond('ok','Registration successful');
}

/* ================= LOGIN ================= */

if ($action === 'login') {

    rate_limit();

    if (empty($data['email']) || empty($data['password'])) {
        respond('error','Email & password required');
    }

    $email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) respond('error','Invalid email');

    $stmt = $conn->prepare("
        SELECT id,password FROM users WHERE email=? LIMIT 1
    ");
    $stmt->bind_param("s",$email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user || !password_verify($data['password'],$user['password'])) {
        $_SESSION['tries']++;
        respond('error','Invalid credentials');
    }

    /* ---------- LOGIN SUCCESS ---------- */
    session_regenerate_id(true);

    $_SESSION['tries'] = 0;
    $_SESSION['uid'] = $user['id'];
    $_SESSION['login_time'] = time();

    $_SESSION['fingerprint'] = device_fingerprint();
    $_SESSION['ip_prefix'] = substr(
        $_SERVER['REMOTE_ADDR'], 0, strrpos($_SERVER['REMOTE_ADDR'], '.')
    );

    /* ---------- REMEMBER ME ---------- */
    if (!empty($data['remember'])) {

        $payload = json_encode([
            'uid' => $user['id'],
            'fp'  => $_SESSION['fingerprint'],
            'ip'  => $_SESSION['ip_prefix'],
            'exp' => time() + 2592000
        ]);

        $sig = hash_hmac('sha256', $payload, REMEMBER_SECRET);
        $cookie = base64_encode($payload.'::'.$sig);

        setcookie(
            'REMEMBERME',
            $cookie,
            time()+2592000,
            '/',
            '',
            isset($_SERVER['HTTPS']),
            true
        );
    }

    respond('ok','Login successful');
}

/* ================= FALLBACK ================= */
respond('error','Invalid action');