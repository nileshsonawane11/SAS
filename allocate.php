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
$common_duties = $admin_rules['strict_duties'];
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
$all_blocks_no = 0;

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
$facultyLoad = [];            // total blocks per faculty
$all_blocks_no = 0;

/* ================= INIT ================= */
foreach ($faculty as $f) {
    $facultyLoad[$f['id']] = 0;
}

$facultyCount = count($faculty);

/* ================= HELPER ================= */
function sortFacultyByLoad(&$faculty, $facultyLoad) {
    usort($faculty, function ($a, $b) use ($facultyLoad) {
        return $facultyLoad[$a['id']] <=> $facultyLoad[$b['id']];
    });
}

$slot_count = 0;

foreach ($slots as $date => $times) {
    
    foreach ($times as $slot => $totalBlocks) {
        $slot_count++;
        /* faculty required */
        $extra = ($reliever > 0) ? (int)ceil($totalBlocks['blocks'] / $reliever) : 0;
        $buffer = (int)ceil($totalBlocks['blocks'] * $extra_faculty);
        $totalFaculty = $totalBlocks['blocks'] + $extra + $buffer;

        $all_blocks_no += $totalFaculty;
    }
}
$avg_duties = ceil($all_blocks_no / $facultyCount);
$overall_duties = $facultyCount * $avg_duties;
$extra_duties = $overall_duties - $all_blocks_no;
$extra_per_slot = ceil($extra_duties / $slot_count);

/* ================= MAIN LOOP ================= */
foreach ($slots as $date => $times) {

    foreach ($times as $slot => $totalBlocks) {

        /* faculty required */
        $extra = ($reliever > 0) ? (int)ceil($totalBlocks['blocks'] / $reliever) : 0;
        $buffer = (int)ceil($totalBlocks['blocks'] * $extra_faculty);
        $totalFaculty = $totalBlocks['blocks'] + $extra + $buffer;

        if($common_duties == 1){
           $totalFaculty += $extra_per_slot;
        }

        $all_blocks_no += $totalFaculty;

        $teachReq = (int)ceil($totalFaculty * $teaching_staff);
        $nonReq   = $totalFaculty - $teachReq;

        $assignedCount = 0;
        $attempts = 0;

        while ($assignedCount < $totalFaculty && $attempts < $facultyCount * 3) {

            $attempts++;

            /* 👈 ALWAYS PICK LEAST-LOADED FACULTY */
            sortFacultyByLoad($faculty, $facultyLoad);

            $assignedThisLoop = false;

            foreach ($faculty as &$f) {

                /* ❌ same date + slot */
                if (isset($assigned[$f['id']][$date][$slot])) continue;

                /* ❌ duties exhausted */
                if ($f['duties'] <= 0 && $duties_restriction == 1) continue;

                /* ❌ role quota */
                if ($f['role'] === 'TS' && $teachReq <= 0 && $role_restriction == 1) continue;
                if ($f['role'] === 'NTS' && $nonReq <= 0 && $role_restriction == 1) continue;

                /* ================= SUBJECT / DEPT CHECK ================= */
                $blockSubRaw = $totalBlocks['sub_code'][$assignedCount] ?? '';
                $blockSubs = explode(',', str_replace("'", "", $blockSubRaw));

                $facultyCourses = array_filter(explode(',', $f['courses'] ?? ''));
                $facultyDept = strtoupper(trim($f['dept_code'] ?? ''));

                $restricted = false;

                foreach ($blockSubs as $sub) {
                    $sub = trim($sub);
                    $subPrefix = strtoupper(substr($sub, 0, 2));

                    if ($sub_restriction && !empty($facultyCourses) && in_array($sub, $facultyCourses)) {
                        $restricted = true;
                        break;
                    }

                    if ($dept_restriction && !empty($facultyDept) && $facultyDept === $subPrefix) {
                        $restricted = true;
                        break;
                    }
                }

                if ($restricted) continue;
                /* ======================================================== */

                /* ✅ ASSIGN */
                $assigned[$f['id']][$date][$slot] = true;

                $facultyAssignments[$f['id']][$date][$slot] = [
                    'assigned' => true,
                    'present'  => true,
                    'sub'      => ''
                ];

                /* BLOCK NUMBER */
                static $lastBlockNo = 0;

                if ($blockSubRaw !== '') {
                    if (isset($blocks[$assignedCount])) {
                        $block_no = $blocks[$assignedCount];
                        $lastBlockNo = (int)$block_no;
                    } else {
                        $block_no = ++$lastBlockNo;
                    }
                    $facultyAssignments[$f['id']][$date][$slot]['block'] = $block_no;
                }

                /* UPDATE COUNTS */
                $facultyLoad[$f['id']]++;
                $assignedCount++;
                $assignedThisLoop = true;

                if ($duties_restriction == 1) {
                    $f['duties']--;
                }

                if ($role_restriction == 1) {
                    if ($f['role'] === 'TS') $teachReq--;
                    else $nonReq--;
                }

                break; // ✅ move to next block
            }

            if (!$assignedThisLoop) break;
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
// print_r($slots);
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
    $block_json = json_encode($slots, JSON_UNESCAPED_UNICODE);

    $stmt = mysqli_prepare(
        $conn,
        "UPDATE Schedule SET scheduled = ?, blocks = ? WHERE id = ?"
    );

    $scheduled = 1;
    mysqli_stmt_bind_param($stmt, "isi", $scheduled, $block_json, $s_id);

    if (!mysqli_stmt_execute($stmt)) {
        echo json_encode([
            'status' => 'error',
            'message' => mysqli_error($conn)
        ]);
        exit;
    }

    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['back'])) {
    header("Location: ./slot_allocation.php?s=$s_id");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    header("Location: ./export_pdfs.php?s=$s_id");
    exit;
}

if($schedule_row['scheduled']){
    header("Location: ./slot_allocation.php?s=$s_id");
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
.supervision-require th{
    text-align: left;
    background: #ffdfdf;
}
.split-cell {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
}
.split-cell span {
    flex: 1;
    text-align: center;
}

.split-cell span:first-child {
    border-right: 1px solid #000;
    padding-right: 6px;
}
.grand-total{
    font-weight: bold;
    background: #dddddd;
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
            <tr class="supervision-require">
                <th colspan="10">Max Duties Required Per Faculty : <?= $avg_duties; ?></th>
            </tr>
            <tr>
                <th rowspan="2" class="sr">Sr</th>
                <th rowspan="2">Supervisor</th>
                <th rowspan="2" class="dept">Dept</th>

                <?php foreach ($allDatesSlots as $date => $times): ?>
                    <th colspan="<?= count($times) ?>"><?= htmlspecialchars($date) ?></th>
                <?php endforeach; ?>

                <th rowspan="2" class="signature">Duties</th>
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
        $duties_grabd_total = 0;
        $blocks_grabd_total = 0;
        // echo "<pre>";print_r($facultyAssignments);echo "</pre>";?>
        <?php foreach ($facultyAssignments as $f_id => $assignments): ?>
            <?php $sup_count = 0; ?>
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
                        <?php ($assignments[$date][$slot]['assigned'] ?? false)
                            ? $sup_count++
                            : ""
                        ?>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php $duties_grabd_total += $sup_count; ?>
                <td><?= $sup_count ?></td>
            </tr>
            <?php endforeach; ?>
            <tr class="grand-total">
                <td colspan="3">Total Blocks</td>
                <?php foreach($slots as $date => $times): ?>
                    <?php foreach ($times as $slot => $_): ?>
                        <?php $blocks_grabd_total += (int)$slots[$date][$slot]['blocks'];?>
                        <td><?= $slots[$date][$slot]['blocks'] ?></td>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <td class="split-cell">
                    <span><?= $blocks_grabd_total ?></span>
                    <span><?= $duties_grabd_total ?></span>
                </td>
            </tr>
        <?php
        

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