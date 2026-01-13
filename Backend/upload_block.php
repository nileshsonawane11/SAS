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
        'field'   => 'block_file'
    ]);
    exit;
}

$fileTmp  = $_FILES['excel_file']['tmp_name'];
$fileName = $_FILES['excel_file']['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

/* ---------------- LOAD FILE ---------------- */

try {

    if (in_array($ext, ['csv','xls','xlsx'])) {
        $spreadsheet = IOFactory::load($fileTmp);
    } else {
        echo json_encode([
            'message' => 'Invalid file type. Upload CSV, XLS or XLSX only.',
            'status'  => 400,
            'field'   => 'block_file'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'message' => 'Failed to read file',
        'status'  => 400,
        'field'   => 'block_file'
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
        'field'   => 'block_file'
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
    'block no.',
    'place',
    'capacity',
    'double sit'
];

foreach ($requiredColumns as $col) {
    if (!isset($map[$col])) {
        echo json_encode([
            'message' => "Missing required column: $col",
            'status'  => 400,
            'field'   => 'block_file'
        ]);
        exit;
    }
}

/* ---------------- INSERT DATA ---------------- */

$inserted = 0;
$skipped  = 0;

for ($i = 1; $i < count($rows); $i++) {

    $row = $rows[$i];

    $block_no   = trim($row[$map['block no.']] ?? '');
    $place      = trim($row[$map['place']] ?? '');
    $capacity   = trim($row[$map['capacity']] ?? '');
    $double_sit = ucfirst(trim($row[$map['double sit']] ?? ''));

    // Basic validation
    if (!$block_no || !$place || !$capacity || !$double_sit) {
        $skipped++;
        continue;
    }

    // Duplicate block check
    $check = $conn->prepare("SELECT id FROM blocks WHERE block_no = ?");
    $check->bind_param("s", $block_no);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $skipped++;
        continue;
    }

    // Insert block
    $stmt = $conn->prepare("
        INSERT INTO blocks 
        (block_no, place, capacity, double_sit)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssis",
        $block_no,
        $place,
        $capacity,
        $double_sit
    );

    if ($stmt->execute()) {
        $inserted++;
    }
}

/* ---------------- RESPONSE ---------------- */

echo json_encode([
    'message'  => 'Block import completed!',
    'status'   => 200,
    'inserted' => $inserted,
    'skipped'  => $skipped
]);
exit;
