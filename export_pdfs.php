<?php
// require './Backend/auth_guard.php';
require_once __DIR__ . '/tcpdf/tcpdf.php';
error_reporting(1);
include './Backend/config.php';

$s_id = $_GET['s'] ?? '';
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

    $signaturePath = "./upload/$signature";

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
        $logoPath = "./upload/$logo";
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
/* ===============================
   FETCH ASSIGNMENTS + FACULTY
================================ */

$sql = "
SELECT 
    bsl.faculty_id,
    bsl.schedule,
    bsl.block_name,
    f.faculty_name,
    f.dept_code,
    f.role
FROM block_supervisor_list bsl
JOIN faculty f ON f.id = bsl.faculty_id
WHERE bsl.s_id = '$s_id'
ORDER BY f.faculty_name
";

$res = mysqli_query($conn, $sql);

/* ===============================
   BUILD STRUCTURES
================================ */
$facultyAssignments = [];
$facultyName = [];
$facultyMap = [];
$facultyRole = [];
$allDatesSlots = [];

while ($row = mysqli_fetch_assoc($res)) {

    $fid = $row['faculty_id'];
    $schedule = json_decode($row['schedule'], true);

    $facultyName[$fid] = $row['faculty_name'];
    $facultyMap[$fid] = $row['dept_code'];
    $facultyRole[$fid] = $row['role'];

    foreach ($schedule as $date => $slots) {
        foreach ($slots as $slot => $v) {
            $facultyAssignments[$fid][$date][$slot] = true;
            $allDatesSlots[$date][$slot] = true;
        }
    }
}

/* SORT DATES & SLOTS */
ksort($allDatesSlots);
foreach ($allDatesSlots as &$slots) {
    krsort($slots);
}
unset($slots);

/* ================= PDF HELPER ================= */
function createPDF($title, $landscape = true) {

    $pdf = new TCPDF($landscape ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('SAS');
    $pdf->SetTitle($title);
    $pdf->SetMargins(6, 14, 6);
    $pdf->SetAutoPageBreak(true, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage();
    $pdf->SetFont('times', '', 9);
    return $pdf;
}

/* ================= BUILD DATE → SLOT MATRIX ================= */
$dateSlotMap = [];

foreach ($facultyAssignments as $dates) {
    foreach ($dates as $date => $slots) {
        foreach ($slots as $slot => $_) {
            $dateSlotMap[$date][] = $slot;
        }
    }
}

// Generate slot mapping
$slotCharMap = [];
$char = 'A';
foreach ($dateSlotMap as $slots) {
    foreach ($slots as $slot) {
        if (!isset($slotCharMap[$slot])) $slotCharMap[$slot] = $char++;
    }
}

foreach ($dateSlotMap as $d => $slots) {
    $dateSlotMap[$d] = array_values(array_unique($slots));
}
ksort($dateSlotMap);

/* ================= ACTION ================= */
if (isset($_GET['action'])) {

$action = $_GET['action'];

/* ==========================================================
   ===================== OVERALL MATRIX =====================
========================================================== */
if ($action === 'overall') {

$pdf = createPDF("Overall Supervision");

/* -------- LETTERHEAD -------- */
print_letter_head();

/* -------- LEGEND -------- */
$pdf->Ln(6);
$pdf->SetFont('times','',9);
$pdf->Cell(20,6,'Slot Legend:',1,0);

$legendText = '';
foreach ($slotCharMap as $slot => $char) {
    $pdf->Cell(40,6,$char,1,0,'C');
}
$pdf->Cell(30,6,'',0,1);
$pdf->Cell(20,6,'Slot Time:',1,0);
foreach ($slotCharMap as $slot => $char) {
    $pdf->Cell(40,6,$slot,1,0,'C');
}
$pdf->Cell(30,6,'',0,1);$pdf->Cell(30,6,'',0,1);

/* -------- TABLE WIDTH CALC -------- */
$totalWidth = 297 - 12;
$srW = 10; $nameW = 60; $deptW = 16; $roleW = 22; $signW = 26;

$totalSlots = 0;
foreach ($dateSlotMap as $slots) $totalSlots += count($slots);

$slotW = ($totalWidth - ($srW+$nameW+$deptW+$roleW+$signW)) / $totalSlots;

/* -------- HEADER ROW 1 (DATES) -------- */
$pdf->SetFont('times','B',9);
$pdf->Cell($srW,12,'Sr',1,0,'C');
$pdf->Cell($nameW,12,'Faculty',1,0,'C');
$pdf->Cell($deptW,12,'Dept',1,0,'C');
$pdf->Cell($roleW,12,'Role',1,0,'C');

foreach ($dateSlotMap as $date => $slots) {
    $pdf->Cell(count($slots)*$slotW,6,date('d-M',strtotime($date)),1,0,'C');
}

$pdf->Cell($signW,12,'Signature',1,1,'C');
$pdf->Ln(-6);

/* -------- HEADER ROW 2 (SLOTS) -------- */
$pdf->Cell($srW,6,'',0,0);
$pdf->Cell($nameW,6,'',0,0);
$pdf->Cell($deptW,6,'',0,0);
$pdf->Cell($roleW,6,'',0,0);

foreach ($dateSlotMap as $slots){
    foreach ($slots as $slot){
        $pdf->Cell($slotW,6,$slotCharMap[$slot],1,0,'C');
        // $pdf->setY(6);
    }
}

$pdf->Cell($signW,6,'',0,0);
$pdf->Ln();

/* -------- BODY -------- */
$pdf->SetFont('times','',9);
$sr = 1;

foreach ($facultyAssignments as $name => $dates) {

    $rowH = 8;

    $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
    $pdf->MultiCell($nameW,$rowH,trim($facultyName[$name]),1,'L',false,0);
    $pdf->Cell($deptW,$rowH,$facultyMap[$name],1,0,'C');
    $pdf->Cell($roleW,$rowH,$facultyRole[$name],1,0,'C');

    foreach ($dateSlotMap as $date => $slots) {
        foreach ($slots as $slot) {
            $val = isset($dates[$date][$slot]) ? '1' : '';
            $pdf->Cell($slotW,$rowH,$val,1,0,'C');
        }
    }

    $pdf->Cell($signW,$rowH,'',1,1);
}

/* -------- FOOTER -------- */

$pdf->Output("Overall_Supervision_Matrix.pdf","I");
exit;
}

/* ==========================================================
   ===================== ROLE MATRIX ========================
========================================================== */
if ($action === 'role') {

    /* ---- GROUP FACULTY BY ROLE ---- */
    $roleFaculty = [];

    foreach ($facultyAssignments as $name => $dates) {
        $role = $facultyRole[$name] ?? 'UNKNOWN';
        $roleFaculty[$role][$name] = $dates;
    }

    /* ---- CREATE PDF ONCE ---- */
    $pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(6,14,6);
    $pdf->SetAutoPageBreak(true,10);
    $pdf->setPrintHeader(false);

    foreach ($roleFaculty as $role => $facultyList) {

        $pdf->AddPage();

        /* ================= LETTERHEAD ================= */
        print_letter_head();
        $role_arr = [
            'TS' => 'Teaching',
            'NTS' => 'Non-Teaching',
            ''   => ''
        ];
        $role = $role_arr[$role];

        $pdf->SetFont('times','B',11);
        $pdf->Cell(0,6,"{$role} Staff – Examination Supervision",0,1,'C');
        $pdf->Ln(4);

        /* -------- LEGEND -------- */
        $pdf->Ln(6);
        $pdf->SetFont('times','',9);
        $pdf->Cell(20,6,'Slot Legend:',1,0);

        $legendText = '';
        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$char,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);
        $pdf->Cell(20,6,'Slot Time:',1,0);
        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$slot,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);$pdf->Cell(30,6,'',0,1);

        /* ================= WIDTH CALC ================= */
        $totalPageWidth = 297 - 12;

        $srW   = 10;
        $nameW = 60;
        $deptW = 18;
        $signW = 26;

        $totalSlots = 0;
        foreach ($dateSlotMap as $slots) {
            $totalSlots += count($slots);
        }

        $slotW = ($totalPageWidth - ($srW + $nameW + $deptW + $signW)) / $totalSlots;

        /* ================= HEADER (DATES) ================= */
        $pdf->SetFont('times','B',9);

        $pdf->Cell($srW,12,'Sr',1,0,'C');
        $pdf->Cell($nameW,12,'Supervisor',1,0,'C');
        $pdf->Cell($deptW,12,'Dept',1,0,'C');

        foreach ($dateSlotMap as $date => $slots) {
            $pdf->Cell(count($slots)*$slotW,6,date('d-M',strtotime($date)),1,0,'C');
        }

        $pdf->Cell($signW,12,'Signature',1,0,'C');
        $pdf->Ln(6);

        /* ================= HEADER (SLOTS) ================= */
        $pdf->Cell($srW,6,'',0);
        $pdf->Cell($nameW,6,'',0);
        $pdf->Cell($deptW,6,'',0);

        foreach ($dateSlotMap as $slots) {
            foreach ($slots as $slot) {
                $pdf->Cell($slotW,6,$slotCharMap[$slot],1,0,'C');
            }
        }

        $pdf->Cell($signW,6,'',0);
        $pdf->Ln();

        /* ================= BODY ================= */
        $pdf->SetFont('times','',9);
        $sr = 1;

        foreach ($facultyList as $name => $dates) {

            $rowH = 8;

            $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
            $pdf->MultiCell($nameW,$rowH,$facultyName [$name],1,'L',false,0);
            $pdf->Cell($deptW,$rowH,$facultyMap[$name] ?? '-',1,0,'C');

            foreach ($dateSlotMap as $date => $slots) {
                foreach ($slots as $slot) {
                    $val = isset($dates[$date][$slot]) ? '1' : '';
                    $pdf->Cell($slotW,$rowH,$val,1,0,'C');
                }
            }

            $pdf->Cell($signW,$rowH,'',1);
            $pdf->Ln();
        }

        /* ================= FOOTER ================= */
        $pdf->SetFont('times','B',10);$pdf->Ln();
    }

    $pdf->Output('Role_Wise_Supervision.pdf','I');
    exit;
}

/* ================= DEPARTMENT (SAME AS OVERALL FORMAT) ================= */
if ($action === 'department') {

    /* ---- GROUP FACULTY BY DEPARTMENT ---- */
    $deptFaculty = [];

    foreach ($facultyAssignments as $name => $dates) {
        $dept = $facultyMap[$name] ?? 'UNKNOWN';
        $deptFaculty[$dept][$name] = $dates;
    }

    /* ---- CREATE PDF ONCE ---- */
    $pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(6,14,6);
    $pdf->SetAutoPageBreak(true,10);
    $pdf->setPrintHeader(false);

    /* ---- LOOP EACH DEPARTMENT ---- */
    foreach ($deptFaculty as $dept => $facultyList) {

        $pdf->AddPage();

        /* ================= LETTERHEAD ================= */
        print_letter_head();

        $pdf->SetFont('times','B',11);
        $pdf->Cell(0,6,"{$dept} Department – Examination Supervision",0,1,'C');
        $pdf->Ln(4);

        /* -------- LEGEND -------- */
        $pdf->Ln(6);
        $pdf->SetFont('times','',9);
        $pdf->Cell(20,6,'Slot Legend:',1,0);

        $legendText = '';
        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$char,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);
        $pdf->Cell(20,6,'Slot Time:',1,0);
        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$slot,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);$pdf->Cell(30,6,'',0,1);

        /* ================= WIDTH CALC ================= */
        $totalPageWidth = 297 - 12;

        $srW   = 10;
        $nameW = 60;
        $deptW = 18;
        $signW = 26;

        $totalSlots = 0;
        foreach ($dateSlotMap as $slots) {
            $totalSlots += count($slots);
        }

        $slotW = ($totalPageWidth - ($srW + $nameW + $deptW + $signW)) / $totalSlots;

        /* ================= HEADER (DATES) ================= */
        $pdf->SetFont('times','B',9);

        $pdf->Cell($srW,12,'Sr',1,0,'C');
        $pdf->Cell($nameW,12,'Supervisor',1,0,'C');
        $pdf->Cell($deptW,12,'Dept',1,0,'C');

        foreach ($dateSlotMap as $date => $slots) {
            $pdf->Cell(count($slots)*$slotW,6,date('d-M',strtotime($date)),1,0,'C');
        }

        $pdf->Cell($signW,12,'Signature',1,0,'C');
        $pdf->Ln(6);

        /* ================= HEADER (SLOTS) ================= */
        $pdf->Cell($srW,6,'',0);
        $pdf->Cell($nameW,6,'',0);
        $pdf->Cell($deptW,6,'',0);

        foreach ($dateSlotMap as $slots) {
            foreach ($slots as $slot) {
                $pdf->Cell($slotW,6,$slotCharMap[$slot],1,0,'C');
            }
        }

        $pdf->Cell($signW,6,'',0);
        $pdf->Ln();

        /* ================= BODY ================= */
        $pdf->SetFont('times','',9);
        $sr = 1;

        foreach ($facultyList as $name => $dates) {

            $rowH = 8;

            $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
            $pdf->MultiCell($nameW,$rowH,$facultyName[$name],1,'L',false,0);
            $pdf->Cell($deptW,$rowH,$dept,1,0,'C');

            foreach ($dateSlotMap as $date => $slots) {
                foreach ($slots as $slot) {
                    $val = isset($dates[$date][$slot]) ? '1' : '';
                    $pdf->Cell($slotW,$rowH,$val,1,0,'C');
                }
            }

            $pdf->Cell($signW,$rowH,'',1);
            $pdf->Ln();
        }

        /* ================= FOOTER ================= */
        $pdf->SetFont('times','B',10);$pdf->Ln();
    }

    $pdf->Output('Department_Wise_Supervision.pdf','I');
    exit;
}

/* ==========================================================
   ================= INDIVIDUAL LETTER ======================
========================================================== */
if ($action === 'individual') {

    $pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(15,15,15);
    $pdf->SetAutoPageBreak(true,20);
    $pdf->setPrintHeader(false);

    uksort($facultyAssignments, function ($a, $b) use ($facultyMap) {
        return strcmp($facultyMap[$a], $facultyMap[$b]);
    });

    foreach ($facultyAssignments as $name => $dates) {

        $pdf->AddPage();
    
        print_letter_head();
        $pdf->Ln(8);

        $pdf->SetFont('times','',11);
        /* ---------- ADDRESS ---------- */
        $f_dept = '';
        if (!empty($facultyMap[$name])) {
            $f_dept = "$department ".$facultyMap[$name]."<br>";
        }
        $html = "To,<br>".$facultyName[$name]."<br>".
            "$f_dept".
            $college_address;
        $pdf->writeHTML($html, true, false, true, false, 'L');
        $pdf->Ln(6);

        /* ---------- SUBJECT ---------- */
        $pdf->SetFont('times','B',12);
        $pdf->writeHTML("Subject : $subject_name", true, false, true, false, 'L');
        $pdf->Ln(5);

        /* ---------- BODY ---------- */
        $pdf->SetFont('times','',11);
        $html = 
        $body_para_1 . '<br><br>' .
        $body_para_2 . '<br><br>' .
        $body_para_3;

        $pdf->writeHTML($html, true, false, true, false, 'L');
        $pdf->Ln(6);

        if($show_table == 'yes'){
            /* ---------- TABLE HEADER (FULL WIDTH) ---------- */
            $pdf->SetFont('times','B',10);
            $pdf->Cell(20,8,'Sr.No',1,0,'C');
            $pdf->Cell(80,8,'Date',1,0,'C');
            $pdf->Cell(75,8,'Slot',1,1,'C');

            $pdf->SetFont('times','',10);

            $sr = 1;
            $srW   = 20;
            $dateW = 80;
            $slotW = 75;
            $rowH  = 8;

            foreach ($dates as $date => $slots) {

                $slotCount  = count($slots);
                $totalH     = $slotCount * $rowH;

                /* ---- STORE START POSITION ---- */
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                /* ---- SR.NO (MERGED) ---- */
                $pdf->Cell($srW, $totalH, $sr++, 1,0, 'C');

                /* ---- DATE (MERGED) ---- */
                $pdf->Cell(
                    $dateW,
                    $totalH,
                    date('d-M-Y', strtotime($date)),
                    1,
                    0,
                    'C'
                );

                /* ---- MOVE TO SLOT COLUMN (IMPORTANT FIX) ---- */
                // $pdf->SetXY($x + $srW + $dateW, $y);

                /* ---- SLOT ROWS (NORMAL CELLS) ---- */
                foreach ($slots as $slot => $_) {
                    $pdf->setX(115);
                    $pdf->Cell($slotW, $rowH, $slot, 1, 1, 'C');
                }
            }
        }

        /* ---------- FOOTER ---------- */
        $pdf->Ln(12);
        $pdf->SetFont('times','',12);
        $pdf->writeHTML($closing_text, true, false, true, false, 'R');

        print_sign();
    }

    /* ---------- OUTPUT ---------- */
    $pdf->Output('All_Faculty_Appointment_Letters.pdf','I');
    exit;
}
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>PDF Export Panel</title>
    <style>
        body { font-family: Arial; background:#f4f6f8; padding:30px; }
        .box { background:#fff; padding:20px; width:450px; margin:auto; border-radius:8px; }
        button {
            width:100%;
            padding:12px;
            margin:8px 0;
            font-size:15px;
            cursor:pointer;
        }
        table{
            border: solid 1px black;
        }
        td,th,tr{
            border: solid 1px black;
        }
    </style>
</head>
<body>

<div class="box">
    <h3>📄 Supervision PDF Exports</h3>

    <form method="get">
        <input type="hidden" name="s" value="<?= htmlspecialchars($s_id ?? '') ?>">
        <button name="action" value="overall">📘 Overall Supervision PDF</button>
        <button name="action" value="department">🏢 Department-Wise PDFs</button>
        <button name="action" value="role">👨‍🏫 Teaching / Non-Teaching PDFs</button>
        <button name="action" value="individual">✉ Individual Appointment Letters</button>
    </form>
</div>

</body>
</html>