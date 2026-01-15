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
$pdf->SetTitle('Attendance Sheet');
$pdf->SetMargins(5, 10, 5);
$pdf->setPrintHeader(false);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();

/* ================= TITLE ================= */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 8, 'Attendance Sheet', 0, 1, 'C');

$pdf->Ln(2);
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 6, "Date: $date    Time: $slot", 0, 1, 'C');

$pdf->Ln(5);

/* ================= TABLE HEADER ================= */
$pdf->SetFont('helvetica', 'B', 9);

$pdf->Cell(7, 12, 'Sr', 1, 0, 'C');
$pdf->Cell(65, 12, 'Faculty', 1, 0, 'C');
$pdf->Cell(13, 12, 'Dept', 1, 0, 'C');
$pdf->Cell(13, 12, 'Block', 1, 0, 'C');
$pdf->Cell(45, 12, 'Answersheet', 1, 0, 'C');
$pdf->Cell(30, 12, 'Rep.Time', 1, 0, 'C');
$pdf->Cell(25, 12, 'Signature', 1, 1, 'C');

/* ================= DATA ================= */
$pdf->SetFont('helvetica', '', 9);

$res = mysqli_query($conn, "
    SELECT f.faculty_name, f.dept_code, bsl.schedule
    FROM block_supervisor_list bsl
    JOIN faculty f ON f.id = bsl.faculty_id
    WHERE bsl.s_id='$s_id'
");

$rows = [];

while ($row = mysqli_fetch_assoc($res)) {

    $sch = json_decode($row['schedule'], true);

    if (!isset($sch[$date][$slot])) continue;

    $block = '';
    if (!empty($sch[$date][$slot]['block'])) {
        $block = trim($sch[$date][$slot]['block']);
    }

    $rows[] = [
        'faculty' => $row['faculty_name'],
        'dept'    => $row['dept_code'],
        'block'   => $block
    ];
}

$blockSort = [];
$blankSort = [];

foreach ($rows as $i => $r) {
    $blockSort[$i] = $r['block'];
    $blankSort[$i] = ($r['block'] === '' || $r['block'] === '-');
}

array_multisort(
    $blankSort, SORT_ASC,
    $blockSort, SORT_NATURAL, SORT_ASC,
    $rows
);

$pdf->SetFont('helvetica', '', 9);
$sr = 1;

foreach ($rows as $r) {

    $pdf->Cell(7, 12, $sr++, 1, 0, 'C');

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Faculty (MultiCell safe)
    $pdf->MultiCell(65, 12, $r['faculty'], 1, 'L');
    $pdf->SetXY($x + 65, $y);

    $pdf->Cell(13, 12, $r['dept'], 1, 0, 'C');
    $pdf->Cell(13, 12, $r['block'], 1, 0, 'C');
    $pdf->Cell(45, 12, '-', 1, 0, 'C');
    $pdf->Cell(30, 12, '', 1, 0, 'C');
    $pdf->Cell(25, 12, '', 1, 1);
}

/* ================= OUTPUT ================= */
$pdf->Output("Attendance_{$date}_{$slot}.pdf", 'I');
exit;
