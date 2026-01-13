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
        'field'   => 'slot_file'
    ]);
    exit;
}

$fileTmp  = $_FILES['excel_file']['tmp_name'];
$fileName = $_FILES['excel_file']['name'];
$ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

/* ---------------- LOAD FILE ---------------- */

try {

    if (in_array($ext, ['csv', 'xls', 'xlsx'])) {
        $spreadsheet = IOFactory::load($fileTmp);
    } else {
        echo json_encode([
            'message' => 'Invalid file type. Upload CSV, XLS or XLSX only.',
            'status'  => 400,
            'field'   => 'slot_file'
        ]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode([
        'message' => 'Failed to read file',
        'status'  => 400,
        'field'   => 'slot_file'
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
        'field'   => 'slot_file'
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
    'exam name',
    'mode',
    'start time',
    'end time'
];

foreach ($requiredColumns as $col) {
    if (!isset($map[$col])) {
        echo json_encode([
            'message' => "Missing required column: $col",
            'status'  => 400,
            'field'   => 'slot_file'
        ]);
        exit;
    }
}

/* ---------------- INSERT DATA ---------------- */

$inserted = 0;
$skipped  = 0;

for ($i = 1; $i < count($rows); $i++) {

    $row = $rows[$i];

    $exam_name  = trim($row[$map['exam name']] ?? '');
    $mode       = ucfirst(strtolower(trim($row[$map['mode']] ?? '')));
    $start_time = trim($row[$map['start time']] ?? '');
    $end_time   = trim($row[$map['end time']] ?? '');

    /* Basic validation */
    if (!$exam_name || !$mode || !$start_time || !$end_time) {
        $skipped++;
        continue;
    }

    /* Mode validation */
    if (!in_array($mode, ['Online', 'Offline'])) {
        $skipped++;
        continue;
    }

    /* Start & End time must not be same */
    if ($start_time === $end_time) {
        $skipped++;
        continue;
    }

    /* Duplicate slot check */
    $check = $conn->prepare("
        SELECT id FROM exam_slots
        WHERE exam_name = ?
        AND mode = ?
        AND start_time = ?
        AND end_time = ?
    ");

    $check->bind_param("ssss", $exam_name, $mode, $start_time, $end_time);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $skipped++;
        continue;
    }

    /* Insert slot */
    $stmt = $conn->prepare("
        INSERT INTO exam_slots
        (exam_name, mode, start_time, end_time)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssss",
        $exam_name,
        $mode,
        $start_time,
        $end_time
    );

    if ($stmt->execute()) {
        $inserted++;
    }
}

/* ---------------- RESPONSE ---------------- */

echo json_encode([
    'message'  => 'Slot import completed successfully!',
    'status'   => 200,
    'inserted' => $inserted,
    'skipped'  => $skipped
]);
exit;
