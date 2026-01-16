<?php
require_once __DIR__ . '/tcpdf/tcpdf.php';

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

function print_sign(){
    global $pdf;
    global $signaturePath;

    if (file_exists($signaturePath)) {

        $imgWidth  = 60;   // width of signature
        $imgHeight = 25;   // height of signature

        // Page width & margins
        $pageWidth   = $pdf->getPageWidth();
        $rightMargin = $pdf->getMargins()['right'];

        // X = page width − right margin − image width
        $x = $pageWidth - $rightMargin - $imgWidth + 15;

        // Y position (adjust as needed)
        $y = $pdf->GetY() - 18;

        $pdf->Image($signaturePath, $x, $y, $imgWidth, $imgHeight);
    }
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
$signaturePath = "./upload/$signature";
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
    $pdf->SetFont('helvetica', '', 9);
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
$pdf->SetFont('helvetica','B',15);
$pdf->Cell(0,8,$institute_name,0,1,'C');
$pdf->SetFont('helvetica','',11);
$pdf->Cell(0,6,'Examination Supervision Allotment Sheet',0,1,'C');
$pdf->Ln(4);

/* -------- LEGEND -------- */
$pdf->Ln(6);
$pdf->SetFont('helvetica','',9);
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
$pdf->SetFont('helvetica','B',9);
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
$pdf->SetFont('helvetica','',9);
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
$pdf->Ln(8);
$pdf->SetFont('helvetica','B',10);$pdf->Ln();
print_sign();
$pdf->Cell(0,6,$official_designation,0,1,'R');

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

    /* -------- LETTERHEAD -------- */
    $pdf->SetFont('helvetica','B',15);
    $pdf->Cell(0,8,$institute_name,0,1,'C');
    $pdf->SetFont('helvetica','',11);
    $pdf->Cell(0,6,'Examination Supervision Allotment Sheet',0,1,'C');
    $pdf->Ln(4);

    foreach ($roleFaculty as $role => $facultyList) {

        $pdf->AddPage();

        /* ================= LETTERHEAD ================= */
        $pdf->SetFont('helvetica','B',15);
        $pdf->Cell(0,8,$institute_name,0,1,'C');

        $pdf->SetFont('helvetica','',11);
        $pdf->Cell(0,6,"{$role} Faculty – Examination Supervision",0,1,'C');
        $pdf->Ln(4);

        /* -------- LEGEND -------- */
        $pdf->Ln(6);
        $pdf->SetFont('helvetica','',9);
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
        $pdf->SetFont('helvetica','B',9);

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
        $pdf->SetFont('helvetica','',9);
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
        $pdf->Ln(8);
        $pdf->SetFont('helvetica','B',10);$pdf->Ln();
        print_sign();
        $pdf->Cell(0,6,$official_designation,0,1,'R');
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

    /* -------- LETTERHEAD -------- */
    $pdf->SetFont('helvetica','B',15);
    $pdf->Cell(0,8,$institute_name,0,1,'C');
    $pdf->SetFont('helvetica','',11);
    $pdf->Cell(0,6,'Examination Supervision Allotment Sheet',0,1,'C');
    $pdf->Ln(4);

    /* ---- LOOP EACH DEPARTMENT ---- */
    foreach ($deptFaculty as $dept => $facultyList) {

        $pdf->AddPage();

        /* ================= LETTERHEAD ================= */
        $pdf->SetFont('helvetica','B',15);
        $pdf->Cell(0,8,$institute_name,0,1,'C');

        $pdf->SetFont('helvetica','',11);
        $pdf->Cell(0,6,"{$dept} Department – Examination Supervision",0,1,'C');
        $pdf->Ln(4);

        /* -------- LEGEND -------- */
        $pdf->Ln(6);
        $pdf->SetFont('helvetica','',9);
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
        $pdf->SetFont('helvetica','B',9);

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
        $pdf->SetFont('helvetica','',9);
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
        $pdf->Ln(8);
        $pdf->SetFont('helvetica','B',10);$pdf->Ln();
        print_sign();
        $pdf->Cell(0,6,$official_designation,0,1,'R');
    }

    $pdf->Output('Department_Wise_Supervision.pdf','I');
    exit;
}

/* ==========================================================
   ================= INDIVIDUAL LETTER ======================
========================================================== */
if ($action === 'individual') {

    $pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(15,20,15);
    $pdf->SetAutoPageBreak(true,20);
    $pdf->setPrintHeader(false);

    uksort($facultyAssignments, function ($a, $b) use ($facultyMap) {
        return strcmp($facultyMap[$a], $facultyMap[$b]);
    });

    foreach ($facultyAssignments as $name => $dates) {

        $pdf->AddPage();

        /* ---------- LETTERHEAD ---------- */
        $pdf->SetFont('helvetica','B',14);
        $pdf->Cell(0,8,$institute_name,0,1,'C');

        $pdf->SetFont('helvetica','',11);
        $pdf->Cell(0,6,$section_name,0,1,'C');
        $pdf->Ln(6);
        $pdf->Cell(0,0,'','T',1);
        /* ---------- DATE ---------- */
        $pdf->SetFont('helvetica','',10);
        $pageWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];

        $pdf->Cell(($pageWidth / 2), 6, 'Ref.No : ____________', 0, 0, 'L');
        $pdf->Cell(($pageWidth / 2), 6, 'Date : ' . date('d-m-Y'), 0, 1, 'R');

        $pdf->Ln(8);

        /* ---------- ADDRESS ---------- */
        $f_dept = '';
        if (!empty($facultyMap[$name])) {
            $f_dept = "$department ".$facultyMap[$name]."\n";
        }
        $pdf->MultiCell(0,6,
            "To,\n".
            "$facultyName[$name]\n".
            "$f_dept".
            $college_address,
            0,'L'
        );
        $pdf->Ln(6);

        /* ---------- SUBJECT ---------- */
        $pdf->SetFont('helvetica','B',11);
        $pdf->Cell(0,7,"Subject : $subject_name",0,1);
        $pdf->Ln(3);

        /* ---------- BODY ---------- */
        $pdf->SetFont('helvetica','',11);
        $pdf->MultiCell(0,7,
            "$body_para_1\n\n".
            "$body_para_2\n\n".
            "$body_para_3",
            0,'J'
        );
        $pdf->Ln(6);

        if($show_table == 'yes'){
            /* ---------- TABLE HEADER (FULL WIDTH) ---------- */
            $pdf->SetFont('helvetica','B',10);
            $pdf->Cell(20,8,'Sr.No',1,0,'C');
            $pdf->Cell(80,8,'Date',1,0,'C');
            $pdf->Cell(75,8,'Slot',1,1,'C');

            $pdf->SetFont('helvetica','',10);

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
        $pdf->SetFont('helvetica','',11);
        $pdf->Cell(0,6,$closing_text,0,1,'R');
        $pdf->Ln(30);

        print_sign();
        $pdf->SetFont('helvetica','B',11);
        $pdf->Cell(0,6,$official_designation,0,1,'R');
        $pdf->SetFont('helvetica','',10);
        $pdf->Cell(0,6,$off_name,0,1,'R');
        $pdf->SetFont('helvetica','',10);
        $pdf->Cell(0,6,$off_address,0,1,'R');
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