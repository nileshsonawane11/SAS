<?php
include './Backend/config.php';

$s_id = $_GET['s'] ?? '';

/* =====================================================
   LOAD Admin Rules
   ===================================================== */
$admin_rules = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admin_panel WHERE id = 1"));
$duties_restriction = $admin_rules['duties_restriction'];
$role_restriction = $admin_rules['role_restriction'];
$sub_restriction = $admin_rules['sub_restriction'];
$dept_restriction = $admin_rules['dept_restriction'];
$block_capacity = (int)$admin_rules['block_capacity'];
$reliever = (int)$admin_rules['reliever'];
$extra_faculty = (float)$admin_rules['extra_faculty'];
$teaching_staff = (float)$admin_rules['teaching_staff'];
$non_teaching_staff = (float)$admin_rules['non_teaching_staff'];

/* =====================================================
   LOAD FACULTY
   ===================================================== */
$schedule_result = mysqli_query($conn,"SELECT * FROM schedule WHERE id = '$s_id'");
if(mysqli_num_rows($schedule_result) > 0){
    $schedule_row = mysqli_fetch_assoc($schedule_result);
    
    if($schedule_row['scheduled']){
        header("Location: ./slot_allocation.php?s=$s_id");
        exit;
    }

    $task_name = $schedule_row['task_name'];
    $task_type = $schedule_row['task_type'];

    $block_rows = mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM blocks ORDER BY CAST(block_no AS UNSIGNED)"));

    $blocks = [];

    foreach ($block_rows as $row) {
        $blockNo   = $row[1];
        $doubleSit = $row[4];

        if ($doubleSit === 'Yes' && $task_type == 'PT') {
            $blocks[] = $blockNo . 'L';
            $blocks[] = $blockNo . 'R';
        } else {
            $blocks[] = $blockNo;
        }
    }
}

$faculty = [];
$q = mysqli_query($conn, "
    SELECT id, faculty_name, dept_code, duties, role, courses
    FROM faculty
    WHERE status='ON' AND duties>0
    ORDER BY duties DESC, faculty_name ASC
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
            'sub_code'  => $row[$col['sub_code']],
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
$slotStudents = [];
$slotSubjects = [];

foreach ($records as $r) {

    $date = $r['date'];
    $slot = $r['slot'];
    $stud = (int)$r['stud'];
    $sub  = $r['sub_code'];

    // Total students
    $slotStudents[$date][$slot] =
        ($slotStudents[$date][$slot] ?? 0) + $stud;

    $slotSubjects[$r['date']][$r['slot']][$r['sub_code']] =
        ($slotSubjects[$r['date']][$r['slot']][$r['sub_code']] ?? 0)
        + (int)$r['stud'];
}

/* STEP 2: Convert students → blocks (30 students per block) */
foreach ($slotSubjects as $date => $slotData) {
    foreach ($slotData as $slot => $subjects) {

        // Sort subjects by student count DESC (dominant first)
        arsort($subjects);

        $totalStudents = array_sum($subjects);
        $totalBlocks = (int)ceil($totalStudents / $block_capacity);

        $blockSubjects = [];
        $remaining = $subjects;

        for ($b = 0; $b < $totalBlocks; $b++) {

            $capacity = $block_capacity;
            $blockSub = [];

            foreach ($remaining as $sub => $count) {
                if ($count <= 0 || $capacity <= 0) continue;

                $take = min($count, $capacity);
                $remaining[$sub] -= $take;
                $capacity -= $take;

                $blockSub[] = $sub;
            }

            // Single subject → plain
            if (count($blockSub) === 1) {
                $blockSubjects[] = $blockSub[0];
            } else {
                // Multiple subjects → quoted CSV inside
                $blockSubjects[] =
                    "'" . implode("','", $blockSub) . "'";
            }
        }

        $slots[$date][$slot] = [
            'blocks'       => $totalBlocks,
            'sub_code' => $blockSubjects
        ];
    }
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
        $extra = (int)ceil($totalBlocks['blocks'] / $reliever);
        $buffer = (int)ceil($totalBlocks['blocks'] * $extra_faculty);
        $totalFaculty = $totalBlocks['blocks'] + $extra + $buffer;

        $teachReq = (int)ceil($totalFaculty * $teaching_staff);
        $nonReq   = $totalFaculty - $teachReq;

        $assignedCount = 0;
        $attempts = 0;

        while ($assignedCount < $totalFaculty && $attempts < $facultyCount * 2) {

            $f =& $faculty[$facultyIndex];
            $facultyIndex = ($facultyIndex + 1) % $facultyCount;
            $attempts++;

            /* ❌ same date + slot */
            if (isset($assigned[$f['id']][$date][$slot]['assigned'])) continue;

            // ===================== Check subject and Code ====================

            $blockSubRaw = $totalBlocks['sub_code'][$assignedCount] ?? '';
            // Clean quotes and split into array
            $blockSubs = explode(',', str_replace("'", "", $blockSubRaw));

            // Faculty courses (CSV → array)
            $facultyCourses = explode(',', $f['courses'] ?? '');

            // Faculty dept
            $facultyDept = strtoupper(trim($f['dept_code'])) ?? '';

            $match = false;

            /* RULE 1: Buffer faculty */
            if (empty($facultyCourses) && empty($facultyDept)) {
                $match = false;
            } else {

                foreach ($blockSubs as $sub) {

                    $subPrefix = strtoupper(substr($sub, 0, 2));

                    /* RULE 2: Course-based match */
                    // echo "$sub => ";print_r($facultyCourses);echo "<br>";
                    if (!empty($facultyCourses) && !empty($sub) && in_array($sub, $facultyCourses) && $sub_restriction) {
                        $match = true;
                        break;
                    }

                    /* RULE 3: Dept-based match */
                    if (!empty($facultyDept) && $facultyDept === $subPrefix && $dept_restriction) {
                        $match = true;
                        break;
                    }
                }
            }

            if ($match) continue;
            // ======================================================================

            /* ❌ duties exhausted */
            if ($f['duties'] <= 0) continue;

            /* ❌ role quota */
            if ($f['role'] === 'TS' && $teachReq <= 0) continue;
            if ($f['role'] === 'NTS' && $nonReq <= 0) continue;

            /* ✅ assign */
            $assigned[$f['id']][$date][$slot]['assigned'] = true;
            $facultyAssignments[$f['id']][$date][$slot]['assigned'] = true;
            $facultyAssignments[$f['id']][$date][$slot]['present'] = true;
            $facultyAssignments[$f['id']][$date][$slot]['sub'] = implode(',',$blockSubs);


            static $lastBlockNo = 0; // remembers previous block number

            if ($blockSubRaw !== '') {

                if (isset($blocks[$assignedCount])) {

                    // Take block number from array
                    $block_no = $blocks[$assignedCount];
                    $lastBlockNo = (int)$block_no;

                } else {

                    // Continue from previous block number
                    $block_no = ++$lastBlockNo;
                }

                $facultyAssignments[$f['id']][$date][$slot]['block'] = $block_no;
            }

            $assignedCount++;

            if($duties_restriction == 1){
                $f['duties']--;
            }

            if($role_restriction == 1){
                if ($f['role'] === 'TS') $teachReq--;
                else $nonReq--; 
            }
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
// print_r($admin_rules);
// echo "</pre>";

// ksort($allDatesSlots);
// foreach ($allDatesSlots as &$t) ksort($t);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
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
    mysqli_query($conn, "Update Schedule set scheduled = 1 where id = '$s_id'");
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
thead{
    position: sticky;
    top: 0;
}
</style>
</head>

<body>
    <form action="" method="POST">
    <div class="d-flex justify-content-between">
        <div></div>
        <input class="btn btn-success" name="save" type="submit" value="Save">
    </div>
</form>
<div class="container-fluid mt-3 mb-3">
    <table class="supervision">
        <thead>
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
        </thead>
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
                    <td class="<?= $class ?> cell"
                        data-fid="<?= $fid ?>"
                        data-date="<?= $date ?>"
                        data-slot="<?= $slot ?>"
                        data-sid="<?= $s_id ?>"
                        data-present="<?= ($assignments[$date][$slot]['assigned'] ?? false)
                            ? (!empty($assignments[$date][$slot]['present'])
                                ? "true"
                                : "false")
                            : ""
                        ?>"
                        oncontextmenu="openDialog(event,this)">
                        <?= ($assignments[$date][$slot]['assigned'] ?? false)
                            ? (!empty($assignments[$date][$slot]['block'])
                                ? "<strong>{$assignments[$date][$slot]['block']}</strong>"
                                : "✓")
                            : ""
                        ?>
                        <?= $assignments[$date][$slot]['sub'] ?? '' ?>
                        <div class="con-tool"></div>
                    </td>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>