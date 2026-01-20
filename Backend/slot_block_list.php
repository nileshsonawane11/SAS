<?php
require_once('../tcpdf/tcpdf.php');
include "./config.php";

$date = $_GET['date'];
$slot = $_GET['slot'];
$s_id = $_GET['s'];
if (!$s_id) die('Invalid Schedule');
$result = mysqli_query($conn, "SELECT letter_json FROM admin_panel WHERE id = 1");
$row = mysqli_fetch_assoc($result);
$letter_data = json_decode($row['letter_json'], true);

$institute_name = $letter_data['college_name'] ?? '';
$section_name = $letter_data['section_name'] ?? '';
$department = $letter_data['department'] ?? '';
$college_address = $letter_data['college_address'] ?? '';
$subject_name = $letter_data['subject_name'] ?? '';
$body_para_1 = $letter_data['body_para_1'] ?? '';
$body_para_2 = $letter_data['body_para_2'] ?? '';
$body_para_3 = $letter_data['body_para_3'] ?? '';
$show_table = $letter_data['show_table'] ?? 'no';
$closing_text = $letter_data['closing_text'] ?? '';
$off_name = $letter_data['off_name'] ?? '';
$official_designation = $letter_data['official_designation'] ?? '';
$off_address = $letter_data['off_address'] ?? '';
$signature = $letter_data['signature'] ?? '';
$logo = $letter_data['logo'] ?? '';
$institute_address = $letter_data['institute_address'] ?? '';
$order_by = $letter_data['order_by'] ?? '';
$ref_no = $letter_data['ref_no'] ?? '';

$ref_no = html_entity_decode(
    trim(preg_replace('/<(br|\/p)>/i', "\n", strip_tags($ref_no))),
    ENT_QUOTES,
    'UTF-8'
);

$body_para_3 = preg_replace('/\s*<div>\s*/i', '<br>', $body_para_3);
$body_para_3 = preg_replace('/\s*<\/div>\s*/i', '', $body_para_3);

// $signaturePath = "./upload/$signature";
// $logoPath = "./upload/$logo";

function print_sign() {
    global $pdf;
    global $signature;
    global $official_designation;
    global $off_name;
    global $off_address;

    $signaturePath = "../upload/$signature";

    // ---- Estimate total height needed (image + text) ----
    $requiredHeight = 35; // adjust if you change font sizes

    $currentY = $pdf->GetY();
    $pageHeight = $pdf->getPageHeight();
    $bottomMargin = $pdf->getMargins()['bottom'];

    // If not enough space → move to next page first
    if ($currentY + $requiredHeight > ($pageHeight - $bottomMargin)) {
        $pdf->AddPage();
    }

    // ---- Print signature image if valid ----
    if (
        !empty($signature) &&
        is_file($signaturePath) &&
        filesize($signaturePath) > 0
    ) {

        $imgWidth  = 60;
        $imgHeight = 25;

        $pageWidth   = $pdf->getPageWidth();
        $rightMargin = $pdf->getMargins()['right'];

        $x = $pageWidth - $rightMargin - $imgWidth+8;
        $y = $pdf->GetY();

        $pdf->Image($signaturePath, $x, $y, $imgWidth, $imgHeight);

        // Move cursor below image
        $pdf->Ln($imgHeight-5);
    }

    // ---- Text block (always stays together) ----
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 6, strip_tags($official_designation), 0, 1, 'R');

    $pdf->SetFont('times', '', 11);
    $pdf->Cell(0, 5, strip_tags($off_name), 0, 1, 'R');

    $pdf->Cell(0, 5, strip_tags($off_address), 0, 1, 'R');
}


function print_letter_head(){
    global $pdf;
    global $logo;
    global $institute_name;
    global $section_name;
    global $institute_address;
    global $order_by;
    global $ref_no;

    $pdf->setPrintHeader(false);
    
    $startY   = $pdf->GetY();
    $leftM    = 20;
    $logoW    = 30;
    $margin   = 0;

    if (!empty($logo)){
        $logoPath = "../upload/$logo";
        // Show logo
        $pdf->Image($logoPath, $leftM+10, $startY-3, $logoW);
        $margin = $logoW + 3;
        // Text shifted right (center relative to page)
        $pdf->SetXY($leftM + $margin, $startY);

    } else {

        // No logo → true center text
        $pdf->SetXY($leftM + $margin, $startY);
    }
        
    /* ---------- LETTERHEAD ---------- */
    $pdf->SetFont('times','B',15);
    $pdf->writeHTML($institute_name, true, false, true, false, 'C');$pdf->Ln(3);
    $startY   = $pdf->GetY();
    $margin ? $pdf->SetXY($leftM + $logoW + 3, $startY) : '';

    $pdf->SetFont('times','',11);
    $pdf->writeHTML($section_name, true, false, true, false, 'C');$pdf->Ln(3);
    $startY   = $pdf->GetY();
    $margin ? $pdf->SetXY($leftM + $logoW + 3, $startY) : '';

    $pdf->SetFont('times','',11);
    $pdf->writeHTML($institute_address, true, false, true, false, 'C');$pdf->Ln(3);

    $pdf->Ln(2);
    $pdf->Cell(0,0,'','T',1);

    /* ---------- DATE ---------- */
    $pdf->SetFont('times','B',11);
    $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];
    $pdf->Ln(-4);
    $pdf->Cell(($pageWidth / 2), 0, 'Ref.No : '.$ref_no, 0, 0, 'L');
    $pdf->Cell(($pageWidth / 2), 0, 'Date : ' . date('d-m-Y'), 0, 1, 'R');
    $pdf->writeHTML("$order_by", true, false, true, false, 'L');
}

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
print_letter_head();

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 11);
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