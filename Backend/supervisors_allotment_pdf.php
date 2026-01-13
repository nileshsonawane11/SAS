<?php
include '../tcpdf/tcpdf.php';
include 'config.php';

$s = $_GET['s'] ?? '';
if (!$s) die('Invalid Schedule');

/* ======================================================
   1. FETCH DATES & TIME SLOTS (DYNAMIC)
====================================================== */

$dateTimeMap = [];

$q = mysqli_query($conn, "
    SELECT DISTINCT schedule_date, schedule_time
    FROM block_schedule
    WHERE s_id = '$s'
    ORDER BY schedule_date, schedule_time
");

while ($r = mysqli_fetch_assoc($q)) {
    $date = date('d-M', strtotime($r['schedule_date']));
    $time = trim($r['schedule_time']);
    $dateTimeMap[$date][] = $time;
}

foreach ($dateTimeMap as $d => $times) {
    $dateTimeMap[$d] = array_values(array_unique($times));
}

/* ======================================================
   2. FETCH SUPERVISOR ALLOTMENTS (MAIN + EXTRA)
====================================================== */

$supervisors = [];

$q = mysqli_query($conn, "
    SELECT 
        f.id AS fid,
        f.faculty_name,
        f.dept_code,
        bs.block_no,
        bsv.is_extra,
        DATE_FORMAT(bs.schedule_date,'%d-%b') AS d,
        bs.schedule_time
    FROM block_supervisor bsv
    JOIN block_schedule bs ON bs.id = bsv.block_schedule_id
    JOIN faculty f ON f.id = bsv.faculty_id
    WHERE bs.s_id = '$s'
    ORDER BY f.dept_code, bsv.is_extra, bs.schedule_date, bs.schedule_time
");

while ($r = mysqli_fetch_assoc($q)) {

    $fid  = $r['fid'];
    $date = $r['d'];
    $time = trim($r['schedule_time']);

    if (!isset($supervisors[$fid])) {
        $supervisors[$fid] = [
            'name'  => $r['faculty_name'],
            'dept'  => $r['dept_code'],
            'slots' => []
        ];
    }

    $value = ($r['is_extra'] == 1) ? 'Extra' : $r['block_no'];

    // Store multiple duties in an array
    if (!isset($supervisors[$fid]['slots'][$date][$time])) {
        $supervisors[$fid]['slots'][$date][$time] = [];
    }
    $supervisors[$fid]['slots'][$date][$time][] = $value;
}

/* ======================================================
   3. CREATE PDF
====================================================== */

$pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
$pdf->SetMargins(6,14,6);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true,10);

/* ================= LETTERHEAD ================= */

$pdf->SetFont('helvetica','B',15);
$pdf->Cell(0,8,'GOVERNMENT POLYTECHNIC, NASHIK',0,1,'C');

$pdf->SetFont('helvetica','',11);
$pdf->Cell(0,6,'Examination Supervision Allotment Sheet',0,1,'C');
$pdf->Ln(4);

/* ======================================================
   4. TABLE DIMENSIONS (AUTO FIT PAGE)
====================================================== */

$totalPageWidth = 297 - 12; // A4 landscape minus margins

$srW   = 10;
$deptW = 14;
$signW = 26;
$nameW = 70;

$totalSlots = 0;
foreach ($dateTimeMap as $times) {
    $totalSlots += count($times);
}

$slotW = ($totalPageWidth - ($srW + $nameW + $deptW + $signW)) / $totalSlots;

/* ======================================================
   5. TABLE HEADER
====================================================== */

$pdf->SetFont('helvetica','B',9);

/* ---- HEADER ROW 1 (DATES) ---- */

$pdf->Cell($srW,12,'Sr',1,0,'C');
$pdf->Cell($nameW,12,'Supervisor',1,0,'C');
$pdf->Cell($deptW,12,'Dept',1,0,'C');

foreach ($dateTimeMap as $date => $times) {
    $pdf->Cell(count($times)*$slotW,6,$date,1,0,'C');
}

$pdf->Cell($signW,12,'Signature',1,0,'C');
$pdf->Ln(6);

/* ---- HEADER ROW 2 (TIMES) ---- */

$pdf->Cell($srW,6,'',0);
$pdf->Cell($nameW,6,'',0);
$pdf->Cell($deptW,6,'',0);

foreach ($dateTimeMap as $times) {
    foreach ($times as $t) {
        $pdf->Cell($slotW,6,$t,1,0,'C');
    }
}

$pdf->Cell($signW,6,'',0);
$pdf->Ln();

/* ======================================================
   6. TABLE BODY
====================================================== */

$pdf->SetFont('helvetica','',9);
$sr = 1;

foreach ($supervisors as $sup) {

    $rowHeight = 8;

    $pdf->Cell($srW,$rowHeight,$sr++,1,0,'C');

    $x = $pdf->GetX();
    $y = $pdf->GetY();
    $pdf->MultiCell($nameW,$rowHeight,$sup['name'],1,'L',false,0);

    $pdf->Cell($deptW,$rowHeight,$sup['dept'],1,0,'C');

    foreach ($dateTimeMap as $date => $times) {
        foreach ($times as $t) {
            $vals = $sup['slots'][$date][$t] ?? [];
            $val  = !empty($vals) ? implode(', ', $vals) : '';
            $pdf->Cell($slotW,$rowHeight,$val,1,0,'C');
        }
    }

    $pdf->Cell($signW,$rowHeight,'',1);
    $pdf->Ln();
}

/* ======================================================
   7. FOOTER
====================================================== */

$pdf->Ln(10);
$pdf->SetFont('helvetica','B',10);
$pdf->Cell(0,6,'Exam Superintendent',0,1,'R');

$pdf->Output('Supervisor_allotment_List.pdf','I');
