<?php
require_once('../tcpdf/tcpdf.php');
include "./config.php";

$date = $_GET['date'];
$slot = $_GET['slot'];
$s_id = $_GET['s'];

/* ================= TCPDF SETUP ================= */
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Supervision System');
$pdf->SetAuthor('Exam Cell');
$pdf->SetTitle('Slot Wise Block Allocation');
$pdf->setPrintHeader(false);
$pdf->SetMargins(10, 15, 10);   // extra top margin
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

/* ================= TITLE ================= */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Slot-Wise Block Allocation', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, "Date: $date    Time: $slot", 0, 1, 'C');

$pdf->Ln(8);
/* ================= FETCH DATA ================= */
$res = mysqli_query($conn,"
    SELECT f.faculty_name, f.dept_code, bsl.schedule
    FROM block_supervisor_list bsl
    JOIN faculty f ON f.id = bsl.faculty_id
    WHERE bsl.s_id = '$s_id'
");

/* ================= BUILD SORTABLE ARRAY ================= */
$rows = [];

while ($row = mysqli_fetch_assoc($res)) {

    $sch = json_decode($row['schedule'], true);
    if (!isset($sch[$date][$slot])) continue;

    $block = '';
    if (is_array($sch[$date][$slot]) && !empty($sch[$date][$slot]['block'])) {
        $block = trim($sch[$date][$slot]['block']);
    }

    $rows[] = [
        'block'   => $block,
        'faculty' => $row['faculty_name'],
        'dept'    => $row['dept_code']
    ];
}

/* ================= NATURAL SORT BY BLOCK ================= */
/* ================= PREPARE SORT KEYS ================= */
$blockSort = [];
$blankSort = [];

foreach ($rows as $i => $r) {
    // Natural sort key
    $blockSort[$i] = $r['block'];

    // Blank blocks should go last (1 = blank, 0 = real)
    $blankSort[$i] = ($r['block'] === '-' || $r['block'] === '');
}

/* ================= SORT ================= */
array_multisort(
    $blankSort, SORT_ASC,        // real blocks first
    $blockSort, SORT_NATURAL, SORT_ASC,
    $rows
);

/* ================= TABLE HEADER (NO TOP BORDER) ================= */
$pdf->SetFont('helvetica', 'B', 10);

$pdf->Cell(15, 8, 'Sr', 1, 0, 'C');
$pdf->Cell(35, 8, 'Block', 1, 0, 'C');
$pdf->Cell(95, 8, 'Faculty', 1, 0, 'C');
$pdf->Cell(40, 8, 'Dept', 1, 1, 'C');

/* ================= TABLE BODY ================= */
$pdf->SetFont('helvetica', '', 10);
$sr = 1;

foreach ($rows as $r) {
    $pdf->Cell(15, 8, $sr++, 1, 0, 'C');
    $pdf->Cell(35, 8, $r['block'], 1, 0, 'C');
    $pdf->Cell(95, 8, $r['faculty'], 1, 0);
    $pdf->Cell(40, 8, $r['dept'], 1, 1, 'C');
}

/* ================= OUTPUT ================= */
$pdf->Output("Block_Allocation_{$date}_{$slot}.pdf", 'I');
exit;