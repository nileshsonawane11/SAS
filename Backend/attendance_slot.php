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
$pdf->SetMargins(10, 10, 10);
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

$pdf->Cell(10, 8, 'Sr', 1, 0, 'C');
$pdf->Cell(65, 8, 'Faculty', 1, 0, 'C');
$pdf->Cell(20, 8, 'Dept', 1, 0, 'C');
$pdf->Cell(20, 8, 'Block', 1, 0, 'C');
$pdf->Cell(45, 8, 'Answersheet', 1, 0, 'C');
$pdf->Cell(30, 8, 'Signature', 1, 1, 'C');

/* ================= DATA ================= */
$pdf->SetFont('helvetica', '', 9);

$sql = "
SELECT f.faculty_name, f.dept_code, bsl.schedule
FROM block_supervisor_list bsl
JOIN faculty f ON f.id = bsl.faculty_id
WHERE bsl.s_id='$s_id'
";

$res = mysqli_query($conn, $sql);
$sr = 1;

while ($row = mysqli_fetch_assoc($res)) {

    $sch = json_decode($row['schedule'], true);

    if (!isset($sch[$date][$slot])) continue;

    $block = '';
    if (is_array($sch[$date][$slot]) && !empty($sch[$date][$slot]['block'])) {
        $block = trim($sch[$date][$slot]['block']);
    }

    $pdf->Cell(10, 8, $sr++, 1, 0, 'C');
    $pdf->Cell(65, 8, $row['faculty_name'], 1, 0);
    $pdf->Cell(20, 8, $row['dept_code'], 1, 0, 'C');
    $pdf->Cell(20, 8, $block, 1, 0, 'C');
    $pdf->Cell(45, 8, '-', 1, 0, 'C');
    $pdf->Cell(30, 8, '', 1, 1);

    $pdf->Cell(10, 8, '', 1, 0, 'C');
    $pdf->Cell(65, 8, '', 1, 0);
    $pdf->Cell(20, 8, '', 1, 0, 'C');
    $pdf->Cell(20, 8, '', 1, 0, 'C');
    $pdf->Cell(45, 8, '-', 1, 0, 'C');
    $pdf->Cell(30, 8, '', 1, 1);
}

/* ================= OUTPUT ================= */
$pdf->Output("Attendance_{$date}_{$slot}.pdf", 'I');
exit;
