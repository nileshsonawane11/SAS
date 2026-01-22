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
$schedule_row = [];

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
$facultyDuties = [];

foreach ($faculty as $f) {
    $facultyMap[$f['id']] = $f['dept_code'];
    $facultyRole[$f['id']] = $f['role'];
    $facultyName[$f['id']] = $f['faculty_name'];
    $facultyDuties[$f['id']] = $f['duties'];
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
            'date'      => date('d-M-Y', strtotime($row[$col['exam_date']])),
            'slot'      => date('h:i A', strtotime($row[$col['start_time']])) . " - " . date('h:i A', strtotime($row[$col['end_time']])),
            'stud'      => (int)$row[$col['stud_count']],
            'exam_type' => $row[$col['exam_type']] ?? ''
        ]; 
    }
}
fclose($handle);

/* =====================================================
   GROUP BY DATE + SLOT WITH ENHANCED LOGIC
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

    // Track total students per slot
    $slotStudents[$date][$slot] = ($slotStudents[$date][$slot] ?? 0) + $stud;
    
    // Track subjects with their student counts
    if (!isset($slotSubjects[$date][$slot][$sub])) {
        $slotSubjects[$date][$slot][$sub] = 0;
    }
    $slotSubjects[$date][$slot][$sub] += $stud;
}

/* Convert students → blocks with improved distribution */
foreach ($slotSubjects as $date => $slotData) {
    foreach ($slotData as $slot => $subjects) {
        // Sort subjects by student count DESC
        arsort($subjects);

        $totalStudents = array_sum($subjects);
        $totalBlocks = (int)ceil($totalStudents / $block_capacity);
        
        // Calculate extra requirements
        $extra = ($reliever > 0) ? (int)ceil($totalBlocks / $reliever) : 0;
        $buffer = (int)ceil($totalBlocks * $extra_faculty);
        $common = ($common_duties == 1) ? 1 : 0;
        
        $totalRequired = $totalBlocks + $extra + $buffer + $common;

        $blockSubjects = [];
        $remaining = $subjects;
        $subjectDistribution = [];

        // First pass: allocate full subjects to blocks where possible
        for ($b = 0; $b < $totalBlocks; $b++) {
            $capacity = $block_capacity;
            $blockSub = [];

            foreach ($remaining as $sub => $count) {
                if ($count <= 0 || $capacity <= 0) continue;

                // Try to allocate whole subject if possible
                if ($count <= $capacity) {
                    $take = $count;
                    $blockSub[] = $sub;
                    $remaining[$sub] = 0;
                    $capacity -= $take;
                } else {
                    $take = min($count, $capacity);
                    $remaining[$sub] -= $take;
                    $capacity -= $take;
                    $blockSub[] = $sub;
                }
                
                if ($capacity <= 0) break;
            }

            // Store block subject assignment
            if (count($blockSub) === 1) {
                $blockSubjects[] = $blockSub[0];
                $subjectDistribution[] = [$blockSub[0]];
            } else {
                $blockSubjects[] = "'" . implode("','", $blockSub) . "'";
                $subjectDistribution[] = $blockSub;
            }
        }

        // Add extra, buffer, and common duty blocks
        for ($i = 0; $i < ($extra + $buffer + $common); $i++) {
            $blockSubjects[] = ''; // No specific subject for extra duties
            $subjectDistribution[] = [];
        }

        $slots[$date][$slot] = [
            'blocks'       => $totalBlocks,
            'total_required' => $totalRequired,
            'sub_code'     => $blockSubjects,
            'subject_dist' => $subjectDistribution,
            'extra'        => $extra,
            'buffer'       => $buffer,
            'common'       => $common
        ];
        
        $all_blocks_no += $totalRequired;
    }
}

// Sort dates and slots
ksort($slots);
foreach ($slots as &$t) {
    ksort($t);
}

/* =====================================================
   ENHANCED ALGORITHM: INTELLIGENT DISTRIBUTION
   ===================================================== */

// Create a flat list of all blocks to assign
$allBlocks = [];
$blockCounter = 0;

foreach ($slots as $date => $times) {
    foreach ($times as $slot => $slotData) {
        $totalRequired = $slotData['total_required'];
        
        for ($i = 0; $i < $totalRequired; $i++) {
            $is_real_block = ($i < $slotData['blocks']);
            $block_subjects = $slotData['subject_dist'][$i] ?? [];
            
            $allBlocks[] = [
                'id' => $blockCounter++,
                'date' => $date,
                'slot' => $slot,
                'sub' => !empty($block_subjects) ? "'" . implode("','", $block_subjects) . "'" : '',
                'subjects_array' => $block_subjects,
                'is_real_block' => $is_real_block,
                'block_type' => $is_real_block ? 'real' : 
                               ($i < $slotData['blocks'] + $slotData['extra'] ? 'extra' : 
                               ($i < $slotData['blocks'] + $slotData['extra'] + $slotData['buffer'] ? 'buffer' : 'common')),
                'priority' => $is_real_block ? 1 : 
                             ($slotData['subject_dist'][$i] ? 2 : 3) // Higher priority for blocks with subjects
            ];
        }
    }
}

// Calculate target blocks per faculty
$totalBlocks = count($allBlocks);
$targetPerFaculty = floor($totalBlocks / $facultyCount);
$extraBlocks = $totalBlocks % $facultyCount;

// Initialize tracking arrays
$facultyLoad = array_fill_keys(array_column($faculty, 'id'), 0);
$facultyAssignments = [];
$slotAssignments = [];
$facultyAvailability = []; // Track faculty availability per slot

foreach ($faculty as $f) {
    $facultyAvailability[$f['id']] = [];
}

// Helper function to check if faculty can take a block
function canTakeBlock($faculty, $block, $slotAssignments, $facultyAvailability, 
                     $duties_restriction, $sub_restriction, $dept_restriction, $role_restriction, 
                     $teaching_staff, $non_teaching_staff, &$teachReq, &$nonReq) {
    
    $fid = $faculty['id'];
    
    // Check slot conflict
    if (isset($slotAssignments[$fid][$block['date']][$block['slot']])) {
        return false;
    }
    
    // Check duties restriction
    if ($duties_restriction == 1 && $faculty['duties'] <= 0) {
        return false;
    }
    
    // Check role quota
    if ($role_restriction == 1) {
        if ($faculty['role'] === 'TS' && $teachReq <= 0) return false;
        if ($faculty['role'] === 'NTS' && $nonReq <= 0) return false;
    }
    
    // Check subject/department restriction
    if (!empty($block['subjects_array'])) {
        $facultyCourses = array_filter(explode(',', $faculty['courses'] ?? ''));
        $facultyDept = strtoupper(trim($faculty['dept_code'] ?? ''));
        
        foreach ($block['subjects_array'] as $sub) {
            $sub = trim($sub);
            $subPrefix = strtoupper(substr($sub, 0, 2));
            
            // FIXED LOGIC: sub_restriction should prevent assignment if true AND faculty teaches the subject
            if ($sub_restriction == 1 && !empty($facultyCourses) && in_array($sub, $facultyCourses)) {
                return false;
            }
            
            // FIXED LOGIC: dept_restriction should prevent assignment if true AND department matches
            if ($dept_restriction == 1 && !empty($facultyDept) && $facultyDept === $subPrefix) {
                return false;
            }
        }
    }
    
    return true;
}

// Sort blocks by priority (real blocks with subjects first)
usort($allBlocks, function($a, $b) {
    // First by block type priority
    $typePriority = ['real' => 1, 'extra' => 2, 'buffer' => 3, 'common' => 4];
    if ($typePriority[$a['block_type']] != $typePriority[$b['block_type']]) {
        return $typePriority[$a['block_type']] - $typePriority[$b['block_type']];
    }
    
    // Then by whether it has subjects
    $aHasSubjects = !empty($a['subjects_array']);
    $bHasSubjects = !empty($b['subjects_array']);
    if ($aHasSubjects != $bHasSubjects) {
        return $bHasSubjects - $aHasSubjects; // Subjects first
    }
    
    // Then by date and slot
    if ($a['date'] != $b['date']) {
        return strtotime($a['date']) - strtotime($b['date']);
    }
    
    return strcmp($a['slot'], $b['slot']);
});

// Track role-based requirements per slot
$slotRoleRequirements = [];
foreach ($slots as $date => $times) {
    foreach ($times as $slot => $slotData) {
        $totalFaculty = $slotData['total_required'];
        $teachReq = (int)ceil($totalFaculty * $teaching_staff);
        $nonReq = $totalFaculty - $teachReq;
        $slotRoleRequirements[$date][$slot] = ['teach' => $teachReq, 'non' => $nonReq];
    }
}

// NEW: Track block number assignments per slot
$slotBlockNumbers = [];

// Main assignment loop
foreach ($allBlocks as $block) {
    $assignedFlag = false;
    $date = $block['date'];
    $slot = $block['slot'];
    
    // Initialize block number counter for this slot if not exists
    if (!isset($slotBlockNumbers[$date][$slot])) {
        $slotBlockNumbers[$date][$slot] = [
            'block_index' => 0,
            'last_numeric' => 0
        ];
    }
    
    // Get role requirements for this slot
    $teachReq = $slotRoleRequirements[$date][$slot]['teach'];
    $nonReq = $slotRoleRequirements[$date][$slot]['non'];
    
    // Sort faculty by load (least loaded first)
    usort($faculty, function($a, $b) use ($facultyLoad) {
        if ($facultyLoad[$a['id']] == $facultyLoad[$b['id']]) {
            return $b['duties'] - $a['duties']; // Higher duties first if load equal
        }
        return $facultyLoad[$a['id']] - $facultyLoad[$b['id']];
    });
    
    foreach ($faculty as &$f) {
        // Check if faculty already has enough assignments
        if ($facultyLoad[$f['id']] >= $targetPerFaculty + ($extraBlocks > 0 ? 1 : 0)) {
            continue;
        }
        
        if (canTakeBlock($f, $block, $slotAssignments, $facultyAvailability, 
                        $duties_restriction, $sub_restriction, $dept_restriction, $role_restriction,
                        $teaching_staff, $non_teaching_staff, $teachReq, $nonReq)) {
            
            $fid = $f['id'];
            
            // Determine block number - START FROM BEGINNING FOR EACH SLOT
            $blockNo = '';
            if ($block['is_real_block']) {
                // Check if we have predefined blocks available
                if ($slotBlockNumbers[$date][$slot]['block_index'] < count($blocks)) {
                    $blockNo = $blocks[$slotBlockNumbers[$date][$slot]['block_index']];
                    $slotBlockNumbers[$date][$slot]['block_index']++;
                    
                    // Update last numeric value from this block
                    if (preg_match('/^(\d+)/', $blockNo, $matches)) {
                        $slotBlockNumbers[$date][$slot]['last_numeric'] = (int)$matches[1];
                    }
                } else {
                    // After predefined blocks run out, continue numeric sequence from last used
                    $slotBlockNumbers[$date][$slot]['last_numeric']++;
                    $blockNo = (string)$slotBlockNumbers[$date][$slot]['last_numeric'];
                    
                    // Check for L/R suffix for PT exams
                    if ($task_type == 'PT') {
                        // Check if this should be L or R based on even/odd
                        if ($slotBlockNumbers[$date][$slot]['last_numeric'] % 2 == 0) {
                            $blockNo .= 'R';
                        } else {
                            $blockNo .= 'L';
                        }
                    }
                }
            }
            // Extra duties remain empty
            
            // Create assignment record
            $facultyAssignments[$fid][$date][$slot] = [
                'assigned' => true,
                'present'  => true,
                'sub'      => $block['sub'],
                'block_type' => $block['block_type'],
                'subjects' => $block['subjects_array']
            ];
            
            // Update tracking
            $facultyLoad[$fid]++;
            $slotAssignments[$fid][$date][$slot] = true;
            
            // Update role requirements
            if ($role_restriction == 1) {
                if ($f['role'] === 'TS') {
                    $slotRoleRequirements[$date][$slot]['teach']--;
                } else {
                    $slotRoleRequirements[$date][$slot]['non']--;
                }
            }
            
            // Update duties if restricted
            if ($duties_restriction == 1) {
                $f['duties']--;
            }
            
            $assignedFlag = true;
            break;
        }
    }
    
    // If no faculty found with constraints, relax and try again
    if (!$assignedFlag) {
        usort($faculty, function($a, $b) use ($facultyLoad) {
            return $facultyLoad[$a['id']] - $facultyLoad[$b['id']];
        });
        
        foreach ($faculty as &$f) {
            if ($facultyLoad[$f['id']] >= $targetPerFaculty + ($extraBlocks > 0 ? 1 : 0)) {
                continue;
            }
            
            // Relaxed check: only slot conflict
            if (!isset($slotAssignments[$f['id']][$date][$slot])) {
                $fid = $f['id'];
                
                $blockNo = '';
                if ($block['is_real_block']) {
                    // Use the same block number logic as above
                    if ($slotBlockNumbers[$date][$slot]['block_index'] < count($blocks)) {
                        $blockNo = $blocks[$slotBlockNumbers[$date][$slot]['block_index']];
                        $slotBlockNumbers[$date][$slot]['block_index']++;
                    } else {
                        $slotBlockNumbers[$date][$slot]['last_numeric']++;
                        $blockNo = (string)$slotBlockNumbers[$date][$slot]['last_numeric'];
                        
                        // Check for L/R suffix for PT exams
                        if ($task_type == 'PT') {
                            if ($slotBlockNumbers[$date][$slot]['last_numeric'] % 2 == 0) {
                                $blockNo .= 'R';
                            } else {
                                $blockNo .= 'L';
                            }
                        }
                    }
                }
                
                $facultyAssignments[$fid][$date][$slot] = [
                    'assigned' => true,
                    'present'  => true,
                    'sub'      => $block['sub'],
                    'block_type' => $block['block_type'],
                    'subjects' => $block['subjects_array']
                ];
                
                $facultyLoad[$fid]++;
                $slotAssignments[$fid][$date][$slot] = true;
                
                if ($duties_restriction == 1) {
                    $f['duties']--;
                }
                
                $assignedFlag = true;
                break;
            }
        }
    }
}

// Balance distribution
$minLoad = min($facultyLoad);
$maxLoad = max($facultyLoad);

while ($maxLoad - $minLoad > 1) {
    $mostLoaded = null;
    $leastLoaded = null;
    
    foreach ($faculty as $f) {
        $fid = $f['id'];
        if ($mostLoaded === null || $facultyLoad[$fid] > $facultyLoad[$mostLoaded]) {
            $mostLoaded = $fid;
        }
        if ($leastLoaded === null || $facultyLoad[$fid] < $facultyLoad[$leastLoaded]) {
            $leastLoaded = $fid;
        }
    }
    
    $moved = false;
    
    if (isset($facultyAssignments[$mostLoaded])) {
        foreach ($facultyAssignments[$mostLoaded] as $date => $slots) {
            foreach ($slots as $slot => $assignment) {
                // Check if least loaded can take this slot
                if (!isset($slotAssignments[$leastLoaded][$date][$slot])) {
                    // Move assignment
                    $facultyAssignments[$leastLoaded][$date][$slot] = $assignment;
                    $slotAssignments[$leastLoaded][$date][$slot] = true;
                    
                    // Remove from most loaded
                    unset($facultyAssignments[$mostLoaded][$date][$slot]);
                    unset($slotAssignments[$mostLoaded][$date][$slot]);
                    
                    if (empty($facultyAssignments[$mostLoaded][$date])) {
                        unset($facultyAssignments[$mostLoaded][$date]);
                    }
                    
                    // Update loads
                    $facultyLoad[$mostLoaded]--;
                    $facultyLoad[$leastLoaded]++;
                    
                    $moved = true;
                    break 2;
                }
            }
            if ($moved) break;
        }
    }
    
    if (!$moved) break;
    
    $minLoad = min($facultyLoad);
    $maxLoad = max($facultyLoad);
}

// Fill in empty assignments for all faculty
foreach ($faculty as $f) {
    $fid = $f['id'];
    if (!isset($facultyAssignments[$fid])) {
        $facultyAssignments[$fid] = [];
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

// Calculate statistics
$total_real_blocks = 0;
$total_required_blocks = 0;

foreach ($slots as $date => $times) {
    foreach ($times as $slot => $slotData) {
        $total_real_blocks += (int)$slotData['blocks'];
        $total_required_blocks += (int)$slotData['total_required'];
    }
}

$facultyIds = array_keys($facultyAssignments);
$facultyCount = count($facultyIds);
$avg_duties = $facultyCount > 0 ? ceil($total_required_blocks / $facultyCount) : 0;

// Calculate actual loads for display
$duties_grand_total = 0;
$blocks_grand_total = 0;
$allocated_blocks_grand_total = 0;

// Sort by date
uksort($allDatesSlots, function ($a, $b) {
    return strtotime($a) <=> strtotime($b);
});

// Sort slots inside each date
foreach ($allDatesSlots as &$slot) {
    uksort($slot, function ($a, $b) {
        $startA = strtotime(explode(' - ', $a)[0]);
        $startB = strtotime(explode(' - ', $b)[0]);
        return $startA <=> $startB;
    });
}
unset($slot);

uksort($slots, function ($a, $b) {
    return strtotime($a) <=> strtotime($b);
});

// Sort slots inside each date
foreach ($slots as &$slot) {
    uksort($slot, function ($a, $b) {
        $startA = strtotime(explode(' - ', $a)[0]);
        $startB = strtotime(explode(' - ', $b)[0]);
        return $startA <=> $startB;
    });
}
unset($slot);

/* =====================================================
   HANDLE FORM SUBMISSIONS
   ===================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save'])) {
        // Clear existing assignments
        mysqli_query($conn, "DELETE FROM block_supervisor_list WHERE s_id = '$s_id'");
        
        // Save new assignments
        foreach ($facultyAssignments as $key => $value) {
            $schedule = mysqli_real_escape_string($conn, json_encode($value));
            
            $sql = "
                INSERT INTO block_supervisor_list (faculty_id, s_id, schedule)
                VALUES ('$key', '$s_id', '$schedule')
                ON DUPLICATE KEY UPDATE schedule = VALUES(schedule)
            ";
            
            mysqli_query($conn, $sql);
        }
        
        // Save slot configuration
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
        
        // Show success message
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                Schedule saved successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
    
    if (isset($_POST['back'])) {
        header("Location: ./slot_allocation.php?s=$s_id");
        exit;
    }
    
    if (isset($_POST['export'])) {
        header("Location: ./export_pdfs.php?s=$s_id");
        exit;
    }
    
    if (isset($_POST['recalculate'])) {
        // Clear session data if needed
        if (isset($_SESSION['allocation_data'])) {
            unset($_SESSION['allocation_data']);
        }
        // Refresh the page
        header("Location: ./allocate.php?s=$s_id");
        exit;
    }
}

if($schedule_row['scheduled'] && !empty($schedule_row['scheduled'])){
    header("Location: ./slot_allocation.php?s=$s_id");
    exit;
}

// Cache data for export/display
$data = [
    'facultyAssignments' => $facultyAssignments,
    'facultyMap'         => $facultyMap,
    'facultyRole'        => $facultyRole,
    'allDatesSlots'      => $allDatesSlots,
    'facultyName'        => $facultyName,
    'slots'              => $slots,
    'slotBlockNumbers'   => $slotBlockNumbers // For debugging
];

file_put_contents(
    './cache.json',
    json_encode($data, JSON_PRETTY_PRINT)
);

// echo "<pre>";
// print_r($slots);
// echo "</pre>";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Supervision Allocation - <?= htmlspecialchars($task_name ?? 'Schedule') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
    vertical-align: middle;
}
.supervision th {
    background: #f2f2f2;
    position: sticky;
    top: 0;
    z-index: 10;
}
.left { text-align: left; }
.sr { width: 40px; }
.dept { width: 60px; }
.signature { width: 120px; }
form{
    margin: 20px;
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
.assigned {
    background-color: #d4edda;
    font-weight: bold;
}
.assigned-real {
    background-color: #d4edda;
}
.assigned-extra {
    background-color: #fff3cd;
}
.assigned-buffer {
    background-color: #cce5ff;
}
.assigned-common {
    background-color: #d1ecf1;
}
.empty {
    background-color: #f8f9fa;
}
.cell {
    cursor: pointer;
    transition: all 0.2s;
}
.cell:hover {
    transform: scale(1.05);
    box-shadow: 0 0 5px rgba(0,0,0,0.2);
}
.subject-info {
    font-size: 9px;
    color: #666;
    margin-top: 2px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.block-number {
    font-weight: bold;
    color: #0d6efd;
}
.summary-card {
    background: #f8f9fa;
    border-left: 4px solid #0d6efd;
}
.block-sequence-info {
    font-size: 10px;
    color: #666;
    margin-top: 10px;
    padding: 5px;
    background: #f8f9fa;
    border-radius: 3px;
}
</style>
</head>

<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row mt-3 mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h4><?= htmlspecialchars($task_name ?? 'Supervision Allocation') ?></h4>
                    <div class="btn-group">
                        <form action="" method="POST" class="d-inline">
                            <button type="submit" name="back" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="submit" name="recalculate" class="btn btn-warning">
                                <i class="bi bi-arrow-clockwise"></i> Recalculate
                            </button>
                            <button type="submit" name="save" class="btn btn-success">
                                <i class="bi bi-save"></i> Save Schedule
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6 class="card-title">Faculty Statistics</h6>
                        <p class="mb-1">Total Faculty: <strong><?= $facultyCount ?></strong></p>
                        <p class="mb-1">Min Load: <strong><?= min($facultyLoad) ?></strong></p>
                        <p class="mb-0">Max Load: <strong><?= max($facultyLoad) ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6 class="card-title">Block Requirements</h6>
                        <p class="mb-1">Real Blocks: <strong><?= $total_real_blocks ?></strong></p>
                        <p class="mb-1">Total Required: <strong><?= $total_required_blocks ?></strong></p>
                        <p class="mb-0">Avg/Faculty: <strong><?= $avg_duties ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6 class="card-title">Distribution</h6>
                        <p class="mb-1">Teaching Staff: <strong><?= $teaching_staff * 100 ?>%</strong></p>
                        <p class="mb-1">Extra Faculty: <strong><?= $extra_faculty * 100 ?>%</strong></p>
                        <p class="mb-0">Reliever Ratio: <strong>1:<?= $reliever ?></strong></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card summary-card">
                    <div class="card-body">
                        <h6 class="card-title">Rules Applied</h6>
                        <p class="mb-1">Dept Restriction: <strong><?= $dept_restriction ? 'Yes' : 'No' ?></strong></p>
                        <p class="mb-1">Subject Restriction: <strong><?= $sub_restriction ? 'Yes' : 'No' ?></strong></p>
                        <p class="mb-0">Duties Restriction: <strong><?= $duties_restriction ? 'Yes' : 'No' ?></strong></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Legend -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="alert alert-light">
                    <strong>Legend:</strong>
                    <span class="badge bg-success">Real Block</span>
                    <span class="badge bg-warning">Extra Duty</span>
                    <span class="badge bg-info">Buffer</span>
                    <span class="badge bg-primary">Common Duty</span>
                    <span class="badge bg-secondary float-end">Block Sequence: Restarts for each slot</span>
                </div>
            </div>
        </div>

        <!-- Main Table -->
        <div class="table-responsive">
            <table class="supervision">
                <thead>
                    <tr class="supervision-require">
                        <th colspan="<?= count($allDatesSlots) * 2 + 6 ?>">
                            Max Duties Required Per Faculty: <?= $avg_duties; ?>
                            <small class="float-end">Schedule: <?= htmlspecialchars($task_name ?? '') ?> | Task Type: <?= $task_type ?></small>
                        </th>
                    </tr>
                    <tr>
                        <th rowspan="2" class="sr">#</th>
                        <th rowspan="2">Supervisor</th>
                        <th rowspan="2" class="dept">Dept</th>
                        <th rowspan="2" class="dept">Role</th>

                        <?php foreach ($allDatesSlots as $date => $times): ?>
                            <th colspan="<?= count($times) ?>"><?= htmlspecialchars($date) ?></th>
                        <?php endforeach; ?>

                        <th rowspan="2" class="signature">Blocks</th>
                        <th rowspan="2" class="signature">Duties</th>
                    </tr>

                    <tr>
                    <?php foreach ($allDatesSlots as $date => $times): ?>
                        <?php foreach ($times as $slot => $_): ?>
                            <th><?= htmlspecialchars($slot) ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $sr = 1; 
                    $duties_grand_total = 0;
                    $blocks_grand_total = 0;
                    $blocks_required_grand_total = [];
                    $allocated_blocks_grand_total = 0;
                    ?>
                    
                    <?php foreach ($facultyAssignments as $f_id => $assignments): ?>
                        <?php 
                        $sup_count = 0; 
                        $blocks_assign = 0;
                        ?>
                        <tr>
                            <td><?= $sr++ ?></td>
                            <td class="left"><?= htmlspecialchars($facultyName[$f_id] ?? 'Unknown') ?></td>
                            <td><?= htmlspecialchars($facultyMap[$f_id] ?? '-') ?></td>
                            <td><?= htmlspecialchars($facultyRole[$f_id] ?? '-') ?></td>

                            <?php foreach ($allDatesSlots as $date => $times): ?>
                                <?php foreach ($times as $slot => $_): ?>
                                    <?php
                                    $isAssigned = isset($assignments[$date][$slot]);
                                    $blockInfo = $isAssigned ? $assignments[$date][$slot] : null;
                                    $blockType = $blockInfo['block_type'] ?? '';
                                    $class = $isAssigned ? "assigned assigned-$blockType" : 'empty';
                                    $hasBlockNumber = ($blockType == 'real');
                                    
                                    // Check if this is a real block
                                    $isRealBlock = ($blockType === 'real');
                                    ?>
                                    <td class="<?= $class ?> cell" 
                                        data-bs-toggle="tooltip" 
                                        title="<?= 
                                            $isAssigned 
                                                ? (($isRealBlock)
                                                    ? "Assigned" 
                                                    : "Extra Duty")
                                                : 'Not assigned'
                                        ?>">
                                        <?php if ($isAssigned): ?>
                                            <?php if ($hasBlockNumber): ?>
                                                <div class="block-number" title="Assigned">
                                                    <?php if ($isRealBlock): ?>
                                                        <small class="d-block">✓</small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <div><i class="bi bi-check-circle"></i></div>
                                            <?php endif; ?>
                                            <?php $sup_count++; ?>
                                            <?php if ($hasBlockNumber) $blocks_assign++; ?>
                                        <?php else: ?>
                                            <div class="text-muted"></div>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            
                            <?php 
                                $duties_grand_total += $sup_count; 
                                $allocated_blocks_grand_total += $blocks_assign;
                            ?>
                            <td><strong><?= $blocks_assign ?></strong></td>
                            <td><strong><?= $sup_count ?></strong></td>
                        </tr>
                    <?php endforeach; ?>

                    <?php 
                    // Calculate block totals per slot
                    $slot_totals = [];
                    foreach ($slots as $date => $times) {
                        foreach ($times as $slot => $slotData) {
                            $slot_totals[$date][$slot] = (int)$slotData['blocks'];
                            $blocks_grand_total += (int)$slotData['blocks'];
                            $blocks_required_grand_total[$date][$slot] = (int)$slotData['total_required'];
                        }
                    }
                    ?>

                    <tr class="grand-total">
                        <td colspan="3">Total Blocks Required:</td>
                        <td><strong><?= $blocks_grand_total?></strong></td>
                        
                        <?php foreach ($allDatesSlots as $date => $times): ?>
                            <?php foreach ($times as $slot => $_): ?>
                                <td><strong><?= ($slot_totals[$date][$slot] ?? 0).'<br> / '.($blocks_required_grand_total[$date][$slot] ?? 0) ?></strong></td>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        
                        <td><strong><?= $allocated_blocks_grand_total ?></strong></td>
                        <td><strong><?= $duties_grand_total ?></strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Footer Info -->
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Allocation Information</h6>
                    <p class="mb-1">• Real blocks are assigned based on actual student counts (<?= $block_capacity ?> students per block)</p>
                    <p class="mb-1">• Extra duties are calculated based on reliever ratio (1:<?= $reliever ?>) and extra faculty percentage (<?= $extra_faculty * 100 ?>%)</p>
                    <p class="mb-0">• Teaching/Non-teaching staff ratio: <?= $teaching_staff * 100 ?>% teaching, <?= $non_teaching_staff * 100 ?>% non-teaching</p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
    // Initialize tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
        
        // Add click functionality to cells
        document.querySelectorAll('.cell').forEach(function(cell) {
            cell.addEventListener('click', function() {
                var title = this.getAttribute('data-bs-title') || this.title;
                if (title) {
                    alert(title);
                }
            });
        });
    });
    
    // Handle keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            document.querySelector('button[name="save"]').click();
        }
        // Ctrl+E to export
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            document.querySelector('button[name="export"]').click();
        }
        // Ctrl+B to go back
        if (e.ctrlKey && e.key === 'b') {
            e.preventDefault();
            document.querySelector('button[name="back"]').click();
        }
    });
    </script>
</body>
</html>