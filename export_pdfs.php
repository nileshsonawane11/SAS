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
$reference = $letter_data['reference'] ?? '';


$ref_no = html_entity_decode(
    trim(preg_replace('/<(br|\/p)>/i', "\n", strip_tags($ref_no))),
    ENT_QUOTES,
    'UTF-8'
);

$body_para_3 = preg_replace('/\s*<div>\s*/i', '<br>', $body_para_3);
$body_para_3 = preg_replace('/\s*<\/div>\s*/i', '', $body_para_3);

$closing_text = preg_replace('/\s*<div>\s*/i', '<br>', $closing_text);
$closing_text = preg_replace('/\s*<\/div>\s*/i', '', $closing_text);

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
    $pdf->writeHTML($official_designation, true, false, true, false, 'R');

    $pdf->SetFont('times', '', 11);
    $pdf->writeHTML($off_name, true, false, true, false, 'R');

    $pdf->writeHTML($off_address, true, false, true, false, 'R');
}


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
    f.dept_name,
    f.role
FROM block_supervisor_list bsl
JOIN faculty f ON f.id = bsl.faculty_id
WHERE bsl.s_id = '$s_id'
ORDER BY f.role DESC, f.dept_code ASC
";

$res = mysqli_query($conn, $sql);
$res2 = mysqli_fetch_assoc(mysqli_query($conn, "SELECT blocks from schedule where id = '$s_id'"));
$blocks_json = json_decode($res2['blocks'], true);
/* ===============================
   BUILD STRUCTURES
================================ */
$facultyAssignments = [];
$facultyName = [];
$facultyMap = [];
$facultydept = [];
$facultyRole = [];
$allDatesSlots = [];

while ($row = mysqli_fetch_assoc($res)) {

    $fid = $row['faculty_id'];
    $schedule = json_decode($row['schedule'], true);
    $facultyName[$fid] = $row['faculty_name'];
    $facultyMap[$fid] = $row['dept_code'];
    $facultyRole[$fid] = $row['role'];
    $facultydept[$fid] = $row['dept_name'];

    foreach ($schedule as $date => $slots) {
        foreach ($slots as $slot => $v) {
            $facultyAssignments[$fid][$date][$slot] = $v;
            $allDatesSlots[$date][$slot] = true;
        }
    }
}

// echo "<pre>";
// print_r($blocks_json);
// echo "</pre>";
// return;

/* SORT DATES & SLOTS */
uksort($allDatesSlots, function ($a, $b) {
    return strtotime($a) <=> strtotime($b);
});

/* =====================================================
   SLOT START TIME PARSER
   ===================================================== */
function slotStartTimestamp($slot) {
    // Extract "10:30 AM" from "10:30 AM - 12:00 PM"
    if (preg_match('/^([\d:]+\s*(AM|PM))/i', $slot, $m)) {
        return strtotime($m[1]);
    }
    return PHP_INT_MAX;
}

/* =====================================================
   SORT SLOTS INSIDE EACH DATE (ASCENDING TIME)
   ===================================================== */
foreach ($allDatesSlots as $date => $slots) {

    // Remove duplicate slots
    $slots = array_unique($slots);

    // Sort by start time
    usort($slots, function ($a, $b) {
        return slotStartTimestamp($a) <=> slotStartTimestamp($b);
    });

    $allDatesSlots[$date] = array_values($slots);
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

/* =====================================================
   BUILD DATE → SLOT MATRIX FROM FACULTY DATA
   ===================================================== */
$dateSlotMap = [];

foreach ($facultyAssignments as $dates) {
    foreach ($dates as $date => $slots) {
        foreach ($slots as $slot => $_) {
            $dateSlotMap[$date][] = $slot;
        }
    }
}

/* =====================================================
   SORT DATE → SLOT MAP (CORRECT WAY)
   ===================================================== */
uksort($dateSlotMap, function ($a, $b) {
    return strtotime($a) <=> strtotime($b);
});

foreach ($dateSlotMap as $date => $slots) {

    $slots = array_unique($slots);

    usort($slots, function ($a, $b) {
        return slotStartTimestamp($a) <=> slotStartTimestamp($b);
    });

    $dateSlotMap[$date] = array_values($slots);
}

/* =====================================================
   GENERATE SLOT CHARACTER MAP (A, B, C…)
   ===================================================== */
$slotCharMap = [];
$char = 'A';

foreach ($dateSlotMap as $slots) {
    foreach ($slots as $slot) {
        if (!isset($slotCharMap[$slot])) {
            $slotCharMap[$slot] = $char++;
        }
    }
}

/* ================= ACTION ================= */
if (isset($_GET['action'])) {

$action = $_GET['action'];

/* ==========================================================
   ===================== OVERALL MATRIX =====================
========================================================== */
if ($action === 'overall') {

    $pdf = createPDF("Overall Supervision");

    /* ================= LETTERHEAD ================= */
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

    /* ================= TOP INFO ROW ================= */
    $pdf->Ln(4);
    $pdf->SetFont('times','B',10);

    $totalCols = 4; // Sr + Name + Dept + Role
    foreach ($dateSlotMap as $slots) {
        $totalCols += count($slots);
    }
    $totalCols += 2; // Blocks + Duties

    /* ================= WIDTH CALC ================= */
    $pageWidth = 297 - 12;

    $srW   = 10;
    $nameW = 60;
    $deptW = 18;
    $roleW = 22;
    $blockW = 18;
    $dutyW  = 18;

    $slotCount = 0;
    foreach ($dateSlotMap as $slots) $slotCount += count($slots);

    $slotW = ($pageWidth - ($srW + $nameW + $deptW + $roleW + $blockW + $dutyW)) / $slotCount;

    /* ================= HEADER ROW 1 (DATES) ================= */
    $pdf->SetFont('times','B',9);

    $pdf->Cell($srW,12,'#',1,0,'C');
    $pdf->Cell($nameW,12,'Supervisor',1,0,'C');
    $pdf->Cell($deptW,12,'Dept',1,0,'C');
    $pdf->Cell($roleW,12,'Role',1,0,'C');

    foreach ($dateSlotMap as $date => $slots) {
        $pdf->Cell(count($slots) * $slotW,6,date('d-M-Y',strtotime($date)),1,0,'C');
    }

    $pdf->Cell($blockW,12,'Blocks',1,0,'C');
    $pdf->Cell($dutyW,12,'Duties',1,1,'C');

    /* ================= HEADER ROW 2 (SLOTS) ================= */
    $pdf->Ln(-6);

    $pdf->Cell($srW,6,'',0);
    $pdf->Cell($nameW,6,'',0);
    $pdf->Cell($deptW,6,'',0);
    $pdf->Cell($roleW,6,'',0);

    foreach ($dateSlotMap as $slots) {
        foreach ($slots as $slot) {
            $pdf->Cell($slotW,6,$slotCharMap[$slot],1,0,'C');
        }
    }

    $pdf->Cell($blockW,6,'',0);
    $pdf->Cell($dutyW,6,'',0);
    $pdf->Ln();

    /* ================= BODY ================= */
    $pdf->SetFont('times','',9);

    $sr = 1;
    $duties_grand_total = 0;
    $blocks_grand_total = 0;

    foreach ($facultyAssignments as $f_id => $assignments) {

        $rowH = 8;
        $sup_count = 0;
        $blocks_assign = 0;

        $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
        $pdf->MultiCell($nameW,$rowH,$facultyName[$f_id] ?? 'Unknown',1,'L',false,0);
        $pdf->Cell($deptW,$rowH,$facultyMap[$f_id] ?? '-',1,0,'C');
        $pdf->Cell($roleW,$rowH,$facultyRole[$f_id] ?? '-',1,0,'C');

        foreach ($dateSlotMap as $date => $slots) {
            foreach ($slots as $slot) {

                $assigned = isset($assignments[$date][$slot]);
                $blockInfo = $assigned ? $assignments[$date][$slot] : null;
                $blockType = $blockInfo['block_type'] ?? '';
                $hasBlock  = !empty($blockInfo['block']);

                if ($assigned) {
                    $sup_count++;
                    if ($hasBlock) $blocks_assign++;

                    $symbol = ($blockType === 'real') ? '✓' : '*';
                } else {
                    $symbol = '';
                }

                $pdf->SetFont('dejavusans','',10);
                $pdf->Cell($slotW,$rowH,$symbol,1,0,'C');
                $pdf->SetFont('times','',9);
            }
        }

        $pdf->SetFont('times','B',9);
        $pdf->Cell($blockW,$rowH,$blocks_assign,1,0,'C');
        $pdf->Cell($dutyW,$rowH,$sup_count,1,1,'C');
        $pdf->SetFont('times','',9);

        $duties_grand_total += $sup_count;
        $blocks_grand_total += $blocks_assign;
    }

    /* ================= GRAND TOTAL ROW ================= */
    $pdf->SetFont('times','B',10);

    $pdf->Cell(
        $srW + $nameW + $deptW,
        8,
        'Total Blocks Required',
        1,
        0,
        'R'
    );
    $pdf->Cell($roleW,8,$blocks_grand_total,1,0,'C');

    foreach ($blocks_json as $date => $slots) {
        foreach ($slots as $slot) {
            $pdf->Cell(
                $slotW,
                8,
                ($slot['blocks'] ?? 0)
                . ' / ' .
                ($slot['total_required'] ?? 0),
                1,
                0,
                'C'
            );
        }
    }

    $pdf->Cell($blockW,8,$blocks_grand_total,1,0,'C');
    $pdf->Cell($dutyW,8,$duties_grand_total,1,1,'C');

    /* ================= OUTPUT ================= */
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
            $pdf->SetFont('times','',9);
            $rowH = 8;

            $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
            $pdf->MultiCell($nameW,$rowH,$facultyName [$name],1,'L',false,0);
            $pdf->Cell($deptW,$rowH,$facultyMap[$name] ?? '-',1,0,'C');

            foreach ($dateSlotMap as $date => $slots) {
                foreach ($slots as $slot) {
                    $val = !empty(($dates[$date][$slot]['block']))
                        ? '✓'
                        : (isset($dates[$date][$slot]['assigned']) ? '*' : '');
                    $pdf->SetFont('dejavusans', '', 10);
                    $pdf->Cell($slotW, $rowH, $val, 1, 0, 'C');
                }
            }

            $pdf->Cell($signW,$rowH,'',1);
            $pdf->Ln();
        }

        /* ================= FOOTER ================= */
        $pdf->SetFont('times','B',10);$pdf->Ln(5);
        print_sign();
    }


    $pdf->Output('Role_Wise_Supervision.pdf','I');
    exit;
}

/* ================= DEPARTMENT (SAME AS OVERALL FORMAT) ================= */
if ($action === 'department') {

    /* ---- GROUP FACULTY BY DEPARTMENT ---- */
    $deptFaculty = [];

    foreach ($facultyAssignments as $name => $dates) {

        $rawDept = $facultyMap[$name] ?? 'UNKNOWN';

        // Normalize department for grouping (AE-LA → AE)
        $baseDept = explode('-', $rawDept)[0];

        // Store original dept also
        $dates['_original_dept'] = $rawDept;

        $deptFaculty[$baseDept][$name] = $dates;
    }

    /* ---- CREATE PDF ONCE ---- */
    $pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(6,14,6);
    $pdf->SetAutoPageBreak(true,10);
    $pdf->setPrintHeader(false);

    $count = 0;
    $suff  = "D";

    /* ---- LOOP EACH DEPARTMENT ---- */
    foreach ($deptFaculty as $dept => $facultyList) {

        $pdf->AddPage();
        $count++;

        /* ================= LETTERHEAD ================= */
        print_letter_head($count,$suff);

        $pdf->SetFont('times','B',11);
        $pdf->Cell(0,6,"{$dept} Department – Examination Supervision",0,1,'C');
        $pdf->Ln(4);

        /* -------- LEGEND -------- */
        $pdf->Ln(6);
        $pdf->SetFont('times','',9);
        $pdf->Cell(20,6,'Slot Legend:',1,0);

        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$char,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);

        $pdf->Cell(20,6,'Slot Time:',1,0);
        foreach ($slotCharMap as $slot => $char) {
            $pdf->Cell(40,6,$slot,1,0,'C');
        }
        $pdf->Cell(30,6,'',0,1);
        $pdf->Cell(30,6,'',0,1);

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
            $pdf->SetFont('times','',10);
            $rowH = 8;
            $originalDept = $dates['_original_dept'] ?? $dept;

            $pdf->Cell($srW,$rowH,$sr++,1,0,'C');
            $pdf->MultiCell($nameW,$rowH,$facultyName[$name],1,'L',false,0);
            $pdf->Cell($deptW,$rowH,$originalDept,1,0,'C');

            foreach ($dateSlotMap as $date => $slots) {
                foreach ($slots as $slot) {

                    $val = !empty($dates[$date][$slot]['block'])
                        ? '✓'
                        : (isset($dates[$date][$slot]['assigned']) ? '✓' : '');

                    $pdf->SetFont('dejavusans','',10);
                    $pdf->Cell($slotW,$rowH,$val,1,0,'C');
                }
            }

            $pdf->Cell($signW,$rowH,'',1);
            $pdf->Ln();
        }

        /* ================= FOOTER ================= */
        $pdf->SetFont('times','B',10);
        $pdf->Ln(10);
        print_sign();
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

    $ref = 0;

    foreach ($facultyAssignments as $name => $dates) {

        $pdf->AddPage();
        $ref++;
        print_letter_head($ref);
        $pdf->Ln(5);

        $pdf->SetFont('times','',11);
        /* ---------- ADDRESS ---------- */
        $f_dept = '';
        if (!empty($facultyMap[$name]) && $facultyRole[$name] == 'TS') {
            $f_dept = "";
        }else{
            $f_dept = $facultyMap[$name].",<br>";
        }

        if (!empty($facultydept[$name]) && $facultyRole[$name] == 'TS') {
            $f_department = "Lecturer In ".ucwords(strtolower($facultydept[$name])).",<br>";
        }else{
            $f_department = "";
        }

        $html = "To,<br>".$facultyName[$name].",<br>".
            "$f_dept".
            "$f_department".
            $college_address;
        $pdf->writeHTML($html, true, false, true, false, 'L');
        $pdf->Ln(2);

        /* ---------- SUBJECT ---------- */
        $pdf->SetFont('times','B',12);
        $pdf->writeHTML("Subject : $subject_name", true, false, true, false, 'L');
        $pdf->Ln(2);

        /* ---------- BODY ---------- */
        $pdf->SetFont('times','',11);
        $html = 
        $body_para_1 . '<br><br>' .
        $body_para_2 . '<br>' .
        $body_para_3;

        $pdf->writeHTML($html, true, false, true, false, 'L');
        $pdf->Ln(1);

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
        $pdf->Ln(3);
        $pdf->SetFont('times','',11);
        $pdf->writeHTML($closing_text, true, false, true, false, 'l');
        $pdf->Ln(10);
        print_sign();
    }

    /* ---------- OUTPUT ---------- */
    $pdf->Output('All_Faculty_Appointment_Letters.pdf','I');
    exit;
}

if ($action === 'faculty_signature') {
    $pdf = new TCPDF('P','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(15,15,15);
    $pdf->SetAutoPageBreak(true,20);
    $pdf->setPrintHeader(false);

    $pdf->AddPage();
    print_letter_head();

    $pdf->Ln(5);
    $pdf->SetFont('times','B',11);
    $pdf->Cell(10,8,'Sr',1,0,'C');
    $pdf->Cell(100,8,'Staff Name',1,0,'C');
    $pdf->Cell(30,8,'Dept',1,0,'C');
    $pdf->Cell(40,8,'Sign',1,1,'C');

    $sr = 1;
    $Faculty_sql = "
        SELECT *
        FROM faculty
        WHERE status = 'ON'
        ORDER BY role DESC, dept_code ASC
    ";    
    $faculty_result = mysqli_query($conn,$Faculty_sql);
    while($row = mysqli_fetch_assoc($faculty_result)){
        $pdf->SetFont('times','',11);
        $pdf->Cell(10,8,$sr++,1,0,'C');
        $pdf->Cell(100,8,$row['faculty_name'],1,0,'C');
        $pdf->Cell(30,8,$row['dept_code'],1,0,'C');
        $pdf->Cell(40,8,'',1,1,'C');
    }
    $pdf->Ln(20);

    print_sign();

    $pdf->Output('Faculty_List.pdf','I');
    exit;
}

if ($action === 'exam_analysis') {

    $csvFile = __DIR__ . "/upload/{$s_id}.csv";

    if (!file_exists($csvFile)) {
        die("CSV file not found");
    }

    /* =====================================================
       READ CSV & BUILD ANALYSIS STRUCTURES
    ===================================================== */
    $analysis      = []; // course => [data][date][slot] + total
    $dateSlotMap  = []; // date => slot => time-range
    $slotTotals   = []; // date => slot => total count

    $handle = fopen($csvFile, "r");
    fgetcsv($handle);

    while (($row = fgetcsv($handle)) !== false) {

        $sub_code   = trim($row[8]);
        $stud_count = (int)$row[10];
        $date       = trim($row[11]);
        $slot       = trim($row[13]);
        $start      = substr(trim($row[14]), 0, 5);
        $end        = substr(trim($row[15]), 0, 5);
        $is_online  = (int)trim($row[9]);

        if ($is_online === 1) continue;
        if ($sub_code === '' || $slot === '' || $date === '') continue;

        /* ---- Date + Slot map ---- */
        $dateSlotMap[$date][$slot] = "{$start} - {$end}";

        /* ---- Analysis ---- */
        if (!isset($analysis[$sub_code])) {
            $analysis[$sub_code] = [
                'data'  => [],
                'total' => 0
            ];
        }

        $analysis[$sub_code]['data'][$date][$slot] =
            ($analysis[$sub_code]['data'][$date][$slot] ?? 0) + $stud_count;

        $analysis[$sub_code]['total'] += $stud_count;

        /* ---- SLOT TOTALS ---- */
        $slotTotals[$date][$slot] =
            ($slotTotals[$date][$slot] ?? 0) + $stud_count;
    }
    fclose($handle);

    /* =====================================================
       SORT DATES & SLOTS
    ===================================================== */
    ksort($dateSlotMap);
    foreach ($dateSlotMap as &$slots) {
        ksort($slots);
    }
    unset($slots);

    /* =====================================================
       CREATE PDF
    ===================================================== */
    $pdf = new TCPDF('L','mm','A4',true,'UTF-8',false);
    $pdf->SetMargins(8,12,8);
    $pdf->SetAutoPageBreak(true,10);
    $pdf->setPrintHeader(false);
    $pdf->AddPage();

    /* =====================================================
       TITLE
    ===================================================== */
    $pdf->SetFont('times','B',14);
    $pdf->Cell(0,8,'EXAM REGISTRATION ANALYSIS REPORT',0,1,'C');
    $pdf->SetFont('times','',11);
    $pdf->Cell(0,6,'Progressive Test – EVEN 2025',0,1,'C');
    $pdf->Ln(6);

    /* =====================================================
       WIDTH CALCULATION
    ===================================================== */
    $pageWidth = 297 - 16;
    $srW = 10; $courseW = 50; $totalW = 22;

    $totalSlots = 0;
    foreach ($dateSlotMap as $slots) {
        $totalSlots += count($slots);
    }

    $slotW = ($pageWidth - ($srW + $courseW + $totalW)) / $totalSlots;

    /* =====================================================
       TABLE HEADER – DATES
    ===================================================== */
    $pdf->SetFont('times','B',9);
    $pdf->SetFillColor(230,230,230);

    $pdf->Cell($srW,12,'Sr',1,0,'C',true);
    $pdf->Cell($courseW,12,'Course Code',1,0,'C',true);

    foreach ($dateSlotMap as $date => $slots) {
        $pdf->Cell(count($slots) * $slotW, 6, date('d-M-Y', strtotime($date)), 1, 0, 'C', true);
    }

    $pdf->Cell($totalW,12,'TOTAL',1,0,'C',true);
    $pdf->Ln(6);

    /* =====================================================
       TABLE HEADER – SLOT TIME
    ===================================================== */
    $pdf->SetFont('times','',9);
    $pdf->Cell($srW,6,'',0);
    $pdf->Cell($courseW,6,'',0);

    foreach ($dateSlotMap as $slots) {
        foreach ($slots as $timeRange) {
            $pdf->Cell($slotW,6,$timeRange,1,0,'C');
        }
    }

    $pdf->Cell($totalW,6,'',0);
    $pdf->Ln();

    /* =====================================================
       TABLE BODY
    ===================================================== */
    $pdf->SetFont('times','',9);
    $sr = 1;
    $grandTotal = 0;

    foreach ($analysis as $course => $data) {

        $pdf->Cell($srW,8,$sr++,1,0,'C');
        $pdf->Cell($courseW,8,$course,1,0,'L');

        foreach ($dateSlotMap as $date => $slots) {
            foreach ($slots as $slot => $time) {
                $pdf->Cell($slotW,8,$data['data'][$date][$slot] ?? '',1,0,'C');
            }
        }

        $pdf->SetFont('times','B',9);
        $pdf->Cell($totalW,8,$data['total'],1,0,'C');
        $pdf->SetFont('times','',9);

        $grandTotal += $data['total'];
        $pdf->Ln();
    }

    /* =====================================================
       SLOT TOTAL ROW (NEW)
    ===================================================== */
    $pdf->SetFont('times','B',10);
    $pdf->Cell($srW + $courseW,8,'TOTAL',1,0,'C');

    foreach ($dateSlotMap as $date => $slots) {
        foreach ($slots as $slot => $_) {
            $pdf->Cell($slotW,8,$slotTotals[$date][$slot] ?? '',1,0,'C');
        }
    }

    $pdf->Cell($totalW,8,$grandTotal,1,1,'C');

    /* =====================================================
       OUTPUT
    ===================================================== */
    $pdf->Output("Exam_Analysis_{$s_id}.pdf",'I');
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
        <button name="action" value="faculty_signature">📝 Faculty List with Signature</button>
        <button name="action" value="exam_analysis"> Exam Analysis</button>
    </form>
</div>

</body>
</html>