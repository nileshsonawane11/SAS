<?php
include './Backend/config.php';

$s_id = $_GET['s'] ?? '';
/* =====================================================
   LOAD FACULTY
   ===================================================== */
$faculty = [];
$q = mysqli_query($conn, "
    SELECT id, faculty_name, dept_code, duties, role
    FROM faculty
    WHERE status='ON' AND duties>0
    ORDER BY duties DESC, faculty_name
");

while ($f = mysqli_fetch_assoc($q)) {
    $faculty[] = $f;
}

$facultyCount = count($faculty);
if ($facultyCount === 0) die("No faculty available");

/* faculty name → dept map */
$facultyMap = [];
$facultyRole = [];
$facultyName = [];

foreach ($faculty as $f) {
    $facultyMap[$f['id']] = $f['dept_code'];
    $facultyRole[$f['id']] = $f['role'];
    $facultyName[$f['id']] = $f['faculty_name'];
}

/* =====================================================
   READ CSV FROM FOLDER
   ===================================================== */
$csvFile = __DIR__ . "/upload/$s_id.csv";
if (!file_exists($csvFile)) die("CSV not found");

$handle = fopen($csvFile, "r");
$header = array_map('strtolower', fgetcsv($handle));
$col = array_flip($header);

$records = [];
while (($row = fgetcsv($handle)) !== false) {
    if($row[$col['online']] == '0'){
       $records[] = [
            'date'  => date('d-M-Y', strtotime($row[$col['exam_date']])),
            'slot'  => date('h:i A', strtotime($row[$col['start_time']])) . " - " . date('h:i A', strtotime($row[$col['end_time']])),
            'stud'  => (int)$row[$col['stud_count']]
        ]; 
    }
}
fclose($handle);

/* =====================================================
   GROUP BY DATE + SLOT
   ===================================================== */
$slots = [];

foreach ($records as $r) {
    $blocks = (int)ceil($r['stud'] / 30);
    $slots[$r['date']][$r['slot']] =
        ($slots[$r['date']][$r['slot']] ?? 0) + $blocks;
}

// /* sort dates & slots */
ksort($slots);
foreach ($slots as &$t) {
    krsort($t);
}

/* =====================================================
   SLOT-BASED FACULTY ASSIGNMENT
   ===================================================== */
$assigned = [];               // busy map
$facultyAssignments = [];     // final output
$facultyIndex = 0;            // round-robin pointer

foreach ($slots as $date => $times) {

    foreach ($times as $slot => $totalBlocks) {

        /* faculty required */
        $extra = (int)ceil($totalBlocks / 5);
        $buffer = (int)ceil($totalBlocks * 0.10);
        $totalFaculty = $totalBlocks + $extra + $buffer;

        $teachReq = (int)ceil($totalFaculty * 0.7);
        $nonReq   = $totalFaculty - $teachReq;

        $assignedCount = 0;
        $attempts = 0;

        while ($assignedCount < $totalFaculty && $attempts < $facultyCount * 2) {

            $f =& $faculty[$facultyIndex];
            $facultyIndex = ($facultyIndex + 1) % $facultyCount;
            $attempts++;

            /* ❌ same date + slot */
            if (isset($assigned[$f['id']][$date][$slot]['assigned'])) continue;

            /* ❌ duties exhausted */
            if ($f['duties'] <= 0) continue;

            /* ❌ role quota */
            if ($f['role'] === 'Teaching' && $teachReq <= 0) continue;
            if ($f['role'] === 'Non-teaching' && $nonReq <= 0) continue;

            /* ✅ assign */
            $assigned[$f['id']][$date][$slot]['assigned'] = true;
            $facultyAssignments[$f['id']][$date][$slot]['assigned'] = true;
            $facultyAssignments[$f['id']][$date][$slot]['present'] = true;

            $assignedCount++;
            $f['duties']--;

            if ($f['role'] === 'Teaching') $teachReq--;
            else $nonReq--;
        }
    }
}

/* =====================================================
   PREPARE DATE-SLOT HEADER STRUCTURE
   ===================================================== */
$allDatesSlots = [];
foreach ($slots as $date => $times) {
    foreach ($times as $slot => $_) {
        $allDatesSlots[$date][$slot] = true;
    }
}

// echo "<pre>";
// print_r($facultyAssignments);
// echo "</pre>";

// ksort($allDatesSlots);
// foreach ($allDatesSlots as &$t) ksort($t);

    foreach ($facultyAssignments as $key => $value) {

        $schedule = mysqli_real_escape_string($conn, json_encode($value));

        $sql = "
            INSERT INTO block_supervisor_list (faculty_id, s_id, schedule)
            VALUES ('$key', '$s_id', '$schedule')
        ";

        try {
            mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {

            // Duplicate key error code = 1062
            if ($e->getCode() == 1062) {
                $errors[] = "Already Assigned";
            } else {
                throw $e; // real error → crash
            }
        }
    }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back'])) {
    header("Location: ./slot_allocation.php?s=$s_id");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    header("Location: ./export_pdfs.php?s=$s_id");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Supervision Allocation</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  </head>
<style>
table.supervision {
    border-collapse: collapse;
    width: 100%;
    font-size: 12px;
}
.supervision th, .supervision td {
    border: 1px solid #000;
    padding: 4px;
    text-align: center;
}
.supervision th {
    background: #f2f2f2;
}
.left { text-align: left; }
.sr { width: 40px; }
.dept { width: 60px; }
.signature { width: 120px; }
form{
    margin: 20px;
}
</style>
</head>

<body>
<div class="container-fluid mt-3 mb-3">
    <table class="supervision">

        <!-- HEADER ROW 1 -->
        <tr>
            <th rowspan="2" class="sr">Sr</th>
            <th rowspan="2">Supervisor</th>
            <th rowspan="2" class="dept">Dept</th>

            <?php foreach ($allDatesSlots as $date => $times): ?>
                <th colspan="<?= count($times) ?>"><?= htmlspecialchars($date) ?></th>
            <?php endforeach; ?>

            <th rowspan="2" class="signature">Signature</th>
        </tr>

        <!-- HEADER ROW 2 -->
        <tr>
        <?php foreach ($allDatesSlots as $date => $times): ?>
            <?php foreach ($times as $slot => $_): ?>
                <th><?= htmlspecialchars($slot) ?></th>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tr>

        <!-- BODY -->
        <?php $sr = 1; 
        // echo "<pre>";print_r($facultyAssignments);echo "</pre>";?>
        <?php foreach ($facultyAssignments as $f_id => $assignments): ?>
        <tr>
            <td><?= $sr++ ?></td>
            <td class="left"><?= $facultyName[$f_id] ?></td>
            <td><?= $facultyMap[$f_id] ?? '-' ?></td>

            <?php foreach ($allDatesSlots as $date => $times): ?>
                <?php foreach ($times as $slot => $_): ?>
                    <td><?= isset($assignments[$date][$slot]['assigned']) ? "✓" : "" ?></td>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <td></td>
        </tr>
        <?php endforeach; 

        $data = [
            'facultyAssignments' => $facultyAssignments,
            'facultyMap'         => $facultyMap,
            'facultyRole'        => $facultyRole,
            'allDatesSlots'      => $allDatesSlots,
            'facultyName'    => $facultyName
        ];

        file_put_contents(
            './cache.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );
        ?>
    </table>
</div>

<form action="" method="POST">
    <div class="d-flex justify-content-between">
        <input class="btn btn-success" name="back" type="submit" value="Go To Home">
        <input class="btn btn-success" name="export" type="submit" value="Export PDF">
    </div>
</form>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>