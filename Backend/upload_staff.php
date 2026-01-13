<?php
header('Content-Type: application/json');
require '../vendor/autoload.php';
include './config.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

/* ---------------- FILE CHECK ---------------- */

if (!isset($_FILES['excel_file'])) {
    echo json_encode([
        'message' => 'No File Uploaded!',
        'status'  => 400,
        'field'   => 'staff_file'
    ]);
    exit;
}

$fileTmp  = $_FILES['excel_file']['tmp_name'];
$fileName = $_FILES['excel_file']['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

/* ---------------- LOAD FILE ---------------- */

try {

    if ($ext === 'csv') {

        $spreadsheet = IOFactory::load($fileTmp);

    } elseif (in_array($ext, ['xls', 'xlsx'])) {

        $spreadsheet = IOFactory::load($fileTmp);

    } else {
        echo json_encode([
            'message' => 'Invalid file type. Upload CSV, XLS or XLSX only.',
            'status'  => 400,
            'field'   => 'staff_file'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'message' => 'Failed to read file',
        'status'  => 400,
        'field'   => 'staff_file'
    ]);
    exit;
}

$sheet = $spreadsheet->getActiveSheet();
$rows  = $sheet->toArray();

/* ---------------- EMPTY FILE CHECK ---------------- */

if (count($rows) < 2) {
    echo json_encode([
        'message' => 'File is empty or invalid',
        'status'  => 400,
        'field'   => 'staff_file'
    ]);
    exit;
}

/* ---------------- HEADER MAPPING ---------------- */

$headers = $rows[0];
$map = [];

foreach ($headers as $index => $header) {
    $map[strtolower(trim($header))] = $index;
}

/* ---------------- REQUIRED COLUMNS ---------------- */

$requiredColumns = [
    'department code',
    'department name',
    'courses',
    'faculty name',
    'duties',
    'email id.',
    'mobile no.',
    'status',
    'role'
];

foreach ($requiredColumns as $col) {
    if (!isset($map[$col])) {
        echo json_encode([
            'message' => "Missing required column: $col",
            'status'  => 400,
            'field'   => 'staff_file'
        ]);
        exit;
    }
}

/* ---------------- INSERT DATA ---------------- */

$inserted = 0;
$skipped  = 0;

for ($i = 1; $i < count($rows); $i++) {

    $row = $rows[$i];

    $dept_name    = strtoupper(trim($row[$map['department name']] ?? ''));
    $dept_code    = strtoupper(trim($row[$map['department code']] ?? ''));
    $faculty_name = trim($row[$map['faculty name']] ?? '');
    $duties       = trim($row[$map['duties']] ?? 0);
    $email        = trim($row[$map['email id.']] ?? '');
    $mobile       = trim($row[$map['mobile no.']] ?? '');
    $status       = strtoupper(trim($row[$map['status']] ?? ''));
    $courses      = trim($row[$map['courses']] ?? '');
    $role         = ucfirst(trim($row[$map['role']] ?? ''));

    if (!$email || !$faculty_name) {
        $skipped++;
        continue;
    }

    // Duplicate check
    $check = $conn->prepare("SELECT id FROM faculty WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $skipped++;
        continue;
    }

    // Insert
    $stmt = $conn->prepare("
        INSERT INTO faculty 
        (dept_code, dept_name, faculty_name, email, mobile, courses, status, duties, role)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssis",
        $dept_code,
        $dept_name,
        $faculty_name,
        $email,
        $mobile,
        $courses,
        $status,
        $duties,
        $role
    );

    if ($stmt->execute()) {
        $inserted++;
    }
}

/* ---------------- RESPONSE ---------------- */

echo json_encode([
    'message'  => 'Import completed!',
    'status'   => 200,
    'inserted' => $inserted,
    'skipped'  => $skipped
]);
exit;