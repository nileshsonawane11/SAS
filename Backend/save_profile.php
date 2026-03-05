<?php
header('Content-Type: application/json');
include './config.php';
require './auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['staff_id'])) {
    echo json_encode([
        'status' => 400,
        'message' => 'Invalid request'
    ]);
    exit;
}

if($data['for'] == 'staff'){

    /* ===============================
    1. SANITIZE + MAP INPUT
    =============================== */
    $staff_id   = intval($data['staff_id']);

    $faculty_name = trim($data['staffName'] ?? '');
    $email        = trim($data['email'] ?? '');
    $mobile       = trim($data['mobile'] ?? '');
    $adhar        = trim($data['aadharr'] ?? '');
    $dept_name    = trim($data['deptName'] ?? '');
    $dept_code    = trim($data['deptCode'] ?? '');
    $role         = trim($data['role'] ?? '');
    $duties       = trim($data['duties'] ?? '');
    $ac_no        = trim($data['account'] ?? '');
    $ifsc_code    = trim($data['ifsc'] ?? '');
    $status       = trim($data['status'] ?? 'OFF');

    /* Normalize status */
    $status = ($status === 'ON') ? 'ON' : 'OFF';
    $role_arr = [
        'Teaching' => 'TS',
        'Non-Teaching' => 'NTS'
    ];
    $role = $role_arr[$role];

    /* ===============================
    2. BASIC VALIDATION
    =============================== */
    if ($faculty_name === '' || $mobile === '') {
        echo json_encode([
            'status' => 422,
            'message' => 'Required fields missing'
        ]);
        exit;
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 422,
            'message' => 'Invalid email'
        ]);
        exit;
    }

    $sql = "
    UPDATE faculty SET
        dept_name     = ?,
        dept_code     = ?,
        faculty_name  = ?,
        duties        = ?,
        role          = ?,
        email         = ?,
        mobile        = ?,
        adhar         = ?,
        `AC-NO`       = ?,
        IFSC_code     = ?,
        status        = ?
    WHERE id = ? AND Created_by = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 500,
            'message' => 'Prepare failed'
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssii",
        $dept_name,
        $dept_code,
        $faculty_name,
        $duties,
        $role,
        $email,
        $mobile,
        $adhar,
        $ac_no,
        $ifsc_code,
        $status,
        $staff_id,
        $owner
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 200,
            'message' => 'Profile updated successfully'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => 500,
            'message' => 'Database update failed'
        ]);
        return;
    }
}

if($data['for'] == 'committee'){

    /* ===============================
    1. SANITIZE + MAP INPUT
    =============================== */
    $staff_id   = intval($data['staff_id']);

    $faculty_name = trim($data['staffName'] ?? '');
    $email        = trim($data['email'] ?? '');
    $mobile       = trim($data['mobile'] ?? '');
    $adhar        = trim($data['aadharr'] ?? '');
    $dept_name    = trim($data['deptName'] ?? '');
    $dept_code    = trim($data['deptCode'] ?? '');
    $designation   = trim($data['designation'] ?? '');
    $role         = trim($data['role'] ?? '');
    $duties       = trim($data['duties'] ?? '');
    $ac_no        = trim($data['account'] ?? '');
    $ifsc_code    = trim($data['ifsc'] ?? '');
    $status       = trim($data['status'] ?? 0);
    $rate       = trim($data['rate'] ?? 0);

    /* Normalize status */
    $status = ($status === 'ON') ? 1 : 0;
    $role_arr = [
        'Teaching' => 'TS',
        'Non-Teaching' => 'NTS',
        '' => NULL
    ];
    $role = $role_arr[$role];

    /* ===============================
    2. BASIC VALIDATION
    =============================== */
    if ($faculty_name === '' || $mobile === '') {
        echo json_encode([
            'status' => 422,
            'message' => 'Required fields missing'
        ]);
        exit;
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 422,
            'message' => 'Invalid email'
        ]);
        exit;
    }

    $sql = "
    UPDATE committee SET
        dept_name     = ?,
        department     = ?,
        member_name  = ?,
        duty        = ?,
        designation = ?,
        role          = ?,
        email         = ?,
        mobile        = ?,
        adhar         = ?,
        `AC-NO`       = ?,
        IFSC_code     = ?,
        status        = ?,
        rate        = ?
    WHERE id = ? AND Created_by = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 500,
            'message' => 'Prepare failed'
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssiii",
        $dept_name,
        $dept_code,
        $faculty_name,
        $duties,
        $designation,
        $role,
        $email,
        $mobile,
        $adhar,
        $ac_no,
        $ifsc_code,
        $status,
        $rate,
        $staff_id,
        $owner
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 200,
            'message' => 'Profile updated successfully'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => 500,
            'message' => 'Database update failed'
        ]);
        return;
    }
}

if($data['for'] == 'peon'){

    /* ===============================
    1. SANITIZE + MAP INPUT
    =============================== */
    $staff_id   = intval($data['staff_id']);

    $faculty_name = trim($data['staffName'] ?? '');
    $email        = trim($data['email'] ?? '');
    $mobile       = trim($data['mobile'] ?? '');
    $adhar        = trim($data['aadharr'] ?? '');
    $dept_name    = trim($data['deptName'] ?? '');
    $dept_code    = trim($data['deptCode'] ?? '');
    $role         = trim($data['role'] ?? '');
    $duties       = trim($data['duties'] ?? '');
    $ac_no        = trim($data['account'] ?? '');
    $ifsc_code    = trim($data['ifsc'] ?? '');
    $status       = trim($data['status'] ?? 0);
    $rate       = trim($data['rate'] ?? 0);

    /* Normalize status */
    $status = ($status === 'ON') ? 1 : 0;
    $role_arr = [
        'Teaching' => 'TS',
        'Non-Teaching' => 'NTS',
        '' => NULL
    ];
    $role = $role_arr[$role];

    /* ===============================
    2. BASIC VALIDATION
    =============================== */
    if ($faculty_name === '' || $mobile === '') {
        echo json_encode([
            'status' => 422,
            'message' => 'Required fields missing'
        ]);
        exit;
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 422,
            'message' => 'Invalid email'
        ]);
        exit;
    }

    $sql = "
    UPDATE peons SET
        dept_name     = ?,
        dept     = ?,
        name  = ?,
        duties       = ?,
        role          = ?,
        email         = ?,
        mobile        = ?,
        adhar         = ?,
        `AC-NO`       = ?,
        IFSC_code     = ?,
        status        = ?,
        rate        = ?
    WHERE id = ? AND Created_by = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 500,
            'message' => 'Prepare failed'
        ]);
        exit;
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssiii",
        $dept_name,
        $dept_code,
        $faculty_name,
        $duties,
        $role,
        $email,
        $mobile,
        $adhar,
        $ac_no,
        $ifsc_code,
        $status,
        $rate,
        $staff_id,
        $owner
    );

    if (mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 200,
            'message' => 'Profile updated successfully'
        ]);
        return;
    } else {
        echo json_encode([
            'status' => 500,
            'message' => 'Database update failed'
        ]);
        return;
    }
}

mysqli_stmt_close($stmt);