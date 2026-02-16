<?php
require './Backend/auth_guard.php';
include "./Backend/config.php";
require_once('tcpdf/tcpdf.php'); // Path to tcpdf.php
$owner = $user_data['_id'] ?? 0 ;

$result = mysqli_query($conn, "SELECT letter_json, duty_rate FROM admin_panel WHERE admin = '$owner'");
$row = mysqli_fetch_assoc($result);
$rate_per_duty = $row['duty_rate'] ?? 0;
$letter_data = json_decode($row['letter_json'] ?? [], true);

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
$reference = $letter_data['reference'] ?? '';

$ref_no = html_entity_decode(
    trim(preg_replace('/<(br|\/p)>/i', "\n", strip_tags($ref_no))),
    ENT_QUOTES,
    'UTF-8'
);

function print_letter_head($count = 0,$suff = ''){
    global $pdf;
    global $logo;
    global $institute_name;
    global $section_name;
    global $institute_address;
    global $order_by;
    global $ref_no;
    global $reference;

    $pdf->setPrintHeader(false);

    $startY   = $pdf->GetY();
    $leftM    = 20;
    $logoW    = 30;
    $margin   = 0;

    /* ---------- LOGO ---------- */
    if (!empty($logo)){
        $logoPath = "./upload/$logo";
        $pdf->Image($logoPath, $leftM+10, $startY-3, $logoW);
        $margin = $logoW + 3;
        $pdf->SetXY($leftM + $margin, $startY);
    } else {
        $pdf->SetXY($leftM, $startY);
    }

    /* ---------- LETTERHEAD ---------- */
    $pdf->SetFont('times','B',15);
    $pdf->writeHTML($institute_name, true, false, true, false, 'C');
    $pdf->Ln(3);

    $startY = $pdf->GetY();
    if ($margin) $pdf->SetXY($leftM + $logoW + 3, $startY);

    $pdf->SetFont('times','',11);
    $pdf->writeHTML($section_name, true, false, true, false, 'C');
    $pdf->Ln(3);

    $startY = $pdf->GetY();
    if ($margin) $pdf->SetXY($leftM + $logoW + 3, $startY);

    $pdf->SetFont('times','',11);
    $pdf->writeHTML($institute_address, true, false, true, false, 'C');
    $pdf->Ln(3);

    $pdf->Ln(2);
    $pdf->Cell(0,0,'','T',1);

    /* ---------- REF NO (SAFE) ---------- */
    $displayRefNo = $ref_no;

    if ($count > 0) {
        $displayRefNo .= $suff."".intval($count);
    }

    /* ---------- DATE ---------- */
    $pdf->SetFont('times','B',11);
    $pageWidth = $pdf->getPageWidth()
                - $pdf->getMargins()['left']
                - $pdf->getMargins()['right'];

    $pdf->Ln(-4);
    $pdf->Cell(($pageWidth / 2), 0, 'Outword No. : '.$displayRefNo, 0, 0, 'L');
    $pdf->Cell(($pageWidth / 2), 0, 'Date : ' . date('d-m-Y'), 0, 1, 'R');

    $pdf->SetFont('times','',10);
    $pdf->writeHTML($order_by, true, false, true, false, 'L');

    $pdf->SetFont('times','B',11);
    $pdf->Ln(3);
    $pdf->writeHTML("Reference : ".$reference, true, false, true, false, 'L');
}

// Fetch all committee members
$res = mysqli_query($conn, "SELECT * FROM committee WHERE Created_by = '$owner' AND status = 1 ORDER BY id ASC");

// Create new PDF document
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('Committee System');
$pdf->SetAuthor('Your Organization');
$pdf->SetTitle('Committee Billing');
$pdf->SetSubject('Billing');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15, true);

// Add a page
$pdf->AddPage();

print_letter_head();
$pdf->Ln(5);

// Table header
$pdf->SetFont('helvetica', 'B', 12);
$tbl_header = '
<table border="1" cellpadding="5">
    <tr style="background-color:#7c3aed;color:#fff;text-align:center">
        <th width="30">Sr</th>
        <th width="180">Name</th>
        <th width="80">Designation</th>
        <th width="70">Dept</th>
        <th width="50">Rate</th>
        <th width="40">Days</th>
        <th width="60">Amount</th>
    </tr>
';
$tbl_footer = '</table>';
$tbl = '';

// Table content
$totalAmount = 0;
while($row = mysqli_fetch_assoc($res)){
    $amount = $row['rate'] * $row['duty'];
    $totalAmount += $amount;
    $sr = 0;
    $tbl .= '
    <tr>
        <td>'.++$sr.'</td>
        <td>'.$row['member_name'].'</td>
        <td>'.$row['designation'].'</td>
        <td>'.$row['department'].'</td>
        <td>'.$row['rate'].'</td>
        <td>'.$row['duty'].'</td>
        <td>'.$amount.'</td>
    </tr>
    ';
}

// Add total row
$tbl .= '
<tr style="font-weight:bold;">
    <td colspan="6" align="right">Total Amount</td>
    <td colspan="2">'.$totalAmount.'</td>
</tr>
';

// Output the table
$pdf->SetFont('helvetica', '', 11);
$pdf->writeHTML($tbl_header.$tbl.$tbl_footer, true, false, false, false, '');

// Output PDF
$pdf->Output('committee_bill.pdf', 'I');
?>