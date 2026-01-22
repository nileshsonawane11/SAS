<?php
// require './Backend/auth_guard.php';
include './Backend/config.php';

/* ===============================
   VALIDATE SCHEDULE ID
================================ */
$s_id = $_GET['s'] ?? '';
if (!$s_id) die('Invalid Schedule');

/* ===============================
   FETCH ASSIGNMENTS
================================ */
$sql = "
SELECT 
    bsl.faculty_id,
    bsl.schedule,
    bsl.block_name,
    f.faculty_name,
    f.dept_code,
    f.role,
    s.blocks
FROM block_supervisor_list bsl
JOIN schedule s ON s.id = bsl.s_id
JOIN faculty f ON f.id = bsl.faculty_id
WHERE bsl.s_id = ?
ORDER BY f.faculty_name
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $s_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

/* ===============================
   BUILD STRUCTURES
================================ */
$facultyAssignments = [];
$facultyName = [];
$facultyDept = [];
$facultyRole = []; // NEW: Store faculty role
$allDatesSlots = [];
$dutyCount = [];
$conflicts = [];
$slots_blocks = [];

while ($row = mysqli_fetch_assoc($res)) {

    $fid = $row['faculty_id'];
    $schedule = json_decode($row['schedule'], true);
    $slots_blocks = json_decode($row['blocks'], true);

    $facultyName[$fid] = $row['faculty_name'];
    $facultyDept[$fid] = $row['dept_code'];
    $facultyRole[$fid] = $row['role'];
    $dutyCount[$fid] = 0;

    // ⚠️ IMPORTANT: Ensure faculty is added even if schedule is empty
    if (!isset($facultyAssignments[$fid])) {
        $facultyAssignments[$fid] = [];
    }

    // If schedule is empty, still show faculty
    if (empty($schedule)) {
        continue;
    }

    foreach ($schedule as $date => $slots) {
        foreach ($slots as $slot => $v) {

            /* CONFLICT CHECK */
            if (isset($facultyAssignments[$fid][$date][$slot])) {
                $conflicts[$fid][$date][$slot] = true;
            }

            $facultyAssignments[$fid][$date][$slot]['assigned'] = true;

            if (isset($slots[$slot]['block'])) {
                $facultyAssignments[$fid][$date][$slot]['block'] = $slots[$slot]['block'];
            }
            if (isset($slots[$slot]['block_type'])) {
                $facultyAssignments[$fid][$date][$slot]['block_type'] = $slots[$slot]['block_type'];
            }
            if (isset($slots[$slot]['present'])) {
                $facultyAssignments[$fid][$date][$slot]['present'] = $slots[$slot]['present'];
            }
            if (isset($slots[$slot]['sub'])) {
                $facultyAssignments[$fid][$date][$slot]['sub'] = $slots[$slot]['sub'];
            }

            $allDatesSlots[$date][$slot] = true;
            $dutyCount[$fid]++;
        }
    }
}

// echo "<pre>";
// print_r(count($facultyAssignments));
// echo "</pre>";

/* SORT DATES & SLOTS */
ksort($allDatesSlots);
foreach ($allDatesSlots as &$slots) {
    krsort($slots);
}
unset($slots);

/* ===============================
   FILTERING
================================ */
$filterDept = $_GET['dept'] ?? '';
$search = strtolower($_GET['search'] ?? '');
$filterDate = $_GET['date'] ?? '';
$filterSlot = $_GET['slot'] ?? '';
$filterRole = $_GET['role'] ?? ''; // NEW: Role filter

/* ===============================
   EXPORT HANDLER
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export'])) {
    header("Location: ./export_pdfs.php?s=$s_id");
    exit;
}

$today = date('d-M-Y');
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Supervision Allocation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body,
        html {
            height: 100%;
            background-color: #fff;
        }

        body:fullscreen {
            overflow: auto !important;
            height: 100%;
        }

        .container-fluid {
            background-color: #fff;
        }

        table.supervision {
            border-collapse: collapse;
            width: 100%;
            font-size: 12px;
        }
        thead{
            position: sticky;
            top: 0;
            z-index: 10;
            background: #ffaeff;
        }
        .supervision th,
        .supervision td {
            border: 1px solid #000;
            padding: 4px;
            text-align: center;
        }

        .supervision th {
            background: #ffaeff;
        }

        td.left {
            text-align: left;
            cursor: pointer;
        }

        .today {
            background: #ffeeba !important;
        }

        .overload {
            background: #ffcccc;
        }

        .conflict {
            background: #ff6666;
            color: #fff;
        }

        .signature {
            width: 120px;
        }

        .th-btn {
            font-size: 13px;
            padding: 5px;
            width: 100%;
        }

        .context-menu {
            position: absolute;
            background: #fff;
            border: 1px solid #333;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, .2);
            display: none;
            z-index: 9999;
            max-height: 300px;
            overflow: auto;
            font-size: 13px;
        }

        .context-menu div {
            padding: 6px 10px;
            cursor: pointer;
        }

        .context-menu div:hover {
            background: #f0f0f0;
        }

        .inputfield {
            font-size: 15px;
            width: 100%;
        }

        .col-md-1,
        .col-md-2 {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .dialog-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        /* ================= DIALOG BOX ================= */
        .dialog {
            background: #ffffff;
            width: 420px;
            max-width: 95%;
            border-radius: 14px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, .25);
            animation: pop .25s ease-out;
            overflow: hidden;
            font-family: 'Inter', system-ui, sans-serif;
        }

        /* ================= HEADER ================= */
        .dialog-header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: #fff;
            padding: 14px 18px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Close button */
        .dialog-header span {
            cursor: pointer;
            font-size: 20px;
            opacity: .9;
        }

        /* ================= BODY ================= */
        .dialog-body {
            padding: 18px;
        }

        .dialog-body label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }

        /* Radio groups */
        .radio-group {
            display: flex;
            gap: 16px;
            margin-bottom: 14px;
        }

        .radio-group label {
            font-weight: 500;
            cursor: pointer;
        }

        /* ================= INPUTS ================= */
        .dialog input[type="text"],
        .dialog select,
        .dialog textarea {
            width: 100%;
            padding: 9px 12px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            font-size: 14px;
            transition: .2s;
        }

        .dialog input:focus,
        .dialog select:focus,
        .dialog textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 2px rgba(37, 99, 235, .15);
        }

        /* Search */
        #facultySearch {
            margin-bottom: 8px;
        }

        /* Faculty list */
        #facultyList {
            height: 130px;
        }

        /* ================= BUTTONS ================= */
        .dialog-footer {
            padding: 14px 18px;
            background: #f9fafb;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: #2563eb;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1e40af;
        }

        .btn-cancel {
            background: #e5e7eb;
        }

        .btn-cancel:hover {
            background: #d1d5db;
        }

        .dialog-header .f_name {
            font-size: 16px;
            opacity: 1;
        }

        /* ================= SECTIONS ================= */
        .box {
            margin-top: 12px;
            padding: 12px;
            background: #f1f5f9;
            border-radius: 10px;
        }

        .export-btn {
            position: fixed;
            bottom: 15px;
            right: 15px;
        }

        .mb-3 {
            padding-right: calc(var(--bs-gutter-x) * 1);
            padding-left: calc(var(--bs-gutter-x) * 1);
            margin: 0 !important;
            padding-bottom: 10px;
        }

        .swap-target {
            outline: 2px dashed #f59e0b;
            background: #fff7ed;
            position: relative;
        }

        table {
            margin-bottom: 4rem !important;
        }

        .cell {
            position: relative
        }

        .cell .con-tool {
            display: none;
        }

        .cell .con-tool::after {
            content: "Click to swap here";
            position: absolute;
            top: -28px;
            left: 50%;
            transform: translateX(-50%);
            background: #111;
            color: #fff;
            z-index: 999;
            padding: 4px 8px;
            font-size: 12px;
            border-radius: 6px;
            white-space: nowrap;
            cursor: pointer;
        }

        .swap-source {
            outline: 2px solid #2563eb;
            background: #dbeafe;
        }

        td[data-present="false"] {
            background: #ff002657;
            border: 2px solid #d50000;
        }

        /* td[data-present="false"] {
    background: #ffebee;
} */
        .header {
            display: flex;
            align-items: center;
            margin: 20px 0;
            width: 100%;
            justify-content: space-between;
        }

        .or {
            display: block;
            width: 100%;
            text-align: center;
            margin: 10px 0px;
        }

        #swap-rect {
            position: absolute;
            border: 2px solid #0bf517ff;
            background: rgba(175, 245, 11, 0.08);
            pointer-events: none;
            z-index: 9998;
            display: none;
        }

        .tbl_row:hover {
            background: #efefef;
        }

        .fullscreen,
        .exit-fullscreen {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: none;
            background: #111827;
            /* dark slate */
            color: #ffffff;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
            transition: all 0.25s ease;
            z-index: 9999;
        }

        /* Exit button slightly shifted */
        .exit-fullscreen {
            right: 64px;
        }

        /* Hover effect */
        .fullscreen:hover,
        .exit-fullscreen:hover {
            background: #2563eb;
            /* blue */
            transform: scale(1.08);
        }

        /* Active click */
        .fullscreen:active,
        .exit-fullscreen:active {
            transform: scale(0.95);
        }

        /* Icon size */
        .fullscreen i,
        .exit-fullscreen i {
            font-size: 18px;
        }

        /* Hide exit button initially */
        .exit-fullscreen {
            display: none;
        }

        /* ================= ANIMATION ================= */
        @keyframes pop {
            from {
                transform: scale(.92);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
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
        
        /* NEW: Role indicator styles */
        .role-badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            margin-left: 5px;
            font-weight: bold;
        }
        .role-ts {
            background-color: #4f46e5;
            color: white;
        }
        .role-nts {
            background-color: #059669;
            color: white;
        }
        .role-cell {
            width: 80px;
            text-align: center;
        }
        .add-cell {
            cursor: pointer;
            text-align: center;
            padding: 12px;
            background: #f9f9f9;
            border: 2px dashed #b0b0b0;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        .add-cell:hover {
            background: #f1f1f1;
            border-color: #888;
            color: #222;
        }
    </style>
</head>

<body>

    <div class="container-fluid mt-3 mb-3">

        <!-- ================= FILTERS ================= -->
        <div class="header">
            <form method="get" class="row g-2 mb-3">
                <input type="hidden" name="s" value="<?= htmlspecialchars($s_id) ?>">

                <div class="col-md-2">
                    <input class="inputfield form-control" name="search" placeholder="Search Faculty"
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <select class="inputfield form-select" name="dept">
                        <option value="">All Departments</option>
                        <?php foreach (array_unique($facultyDept) as $d): ?>
                            <option <?= $filterDept == $d ? 'selected' : '' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="inputfield form-select" name="role">
                        <option value="">All Roles</option>
                        <option value="TS" <?= $filterRole == 'TS' ? 'selected' : '' ?>>Teaching Staff</option>
                        <option value="NTS" <?= $filterRole == 'NTS' ? 'selected' : '' ?>>Non-Teaching Staff</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="inputfield form-select" name="date">
                        <option value="">All Dates</option>
                        <?php foreach ($allDatesSlots as $d => $_): ?>
                            <option value="<?= $d ?>" <?= ($_GET['date'] ?? '') === $d ? 'selected' : '' ?>>
                                <?= $d ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select class="inputfield form-select" name="slot">
                        <option value="">All Slots</option>
                        <?php
                        $allSlots = [];
                        foreach ($allDatesSlots as $slots) {
                            foreach ($slots as $s => $_) $allSlots[$s] = true;
                        }
                        foreach (array_keys($allSlots) as $s):
                        ?>
                            <option value="<?= $s ?>" <?= ($_GET['slot'] ?? '') === $s ? 'selected' : '' ?>>
                                <?= $s ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-1">
                    <button class="btn btn-primary">Filter</button>
                </div>
                <div class="col-md-1">
                    <button class="btn" type="reset">
                        <a href="?s=<?= $s_id ?>" class="btn btn-secondary">
                            Clear
                        </a>
                    </button>
                </div>

            </form>
            <div class="col-md-1">
                <button type="button" onclick="openFullscreen()" class="fullscreen"><i class="fas fa-solid fa-expand"></i></button>
                <button type="button" onclick="closeFullscreen()" class="exit-fullscreen"><i class="fas fa-solid fa-compress"></i></button>
            </div>
        </div>
        
        <!-- ================= STATISTICS ================= -->
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded p-2 me-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Total Faculty</h6>
                                        <p class="mb-0 fw-bold">
                                            <?= count($facultyAssignments) ?>
                                            <small class="text-muted">
                                                (TS: <?= count(array_filter($facultyRole, fn($r) => $r === 'TS')) ?>, 
                                                NTS: <?= count(array_filter($facultyRole, fn($r) => $r === 'NTS')) ?>)
                                            </small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-success text-white rounded p-2 me-3">
                                        <i class="fas fa-clipboard-list"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Total Duties</h6>
                                        <p class="mb-0 fw-bold"><?= array_sum($dutyCount) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-warning text-white rounded p-2 me-3">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Total Days</h6>
                                        <p class="mb-0 fw-bold"><?= count($allDatesSlots) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info text-white rounded p-2 me-3">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Total Slots</h6>
                                        <p class="mb-0 fw-bold">
                                            <?= 
                                                array_sum(array_map('count', $allDatesSlots))
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ================= TABLE ================= -->
        <table class="supervision">
            <thead>               
            <tr>
                <th rowspan="4">Sr</th>
                <th rowspan="4">Supervisor</th>
                <th rowspan="4">Dept</th>
                <th rowspan="4">Role</th> <!-- NEW: Role column -->

                <?php foreach ($allDatesSlots as $date => $slots): ?>
                    <?php if ($filterDate && $filterDate !== $date) continue; ?>
                    <?php
                    // count only visible slots
                    $visibleSlots = 0;
                    foreach ($slots as $slot => $_) {
                        if ($filterSlot && $filterSlot !== $slot) continue;
                        $visibleSlots++;
                    }

                    // skip date if no visible slots
                    if ($visibleSlots === 0) continue;
                    ?>
                    <th colspan="<?= $visibleSlots ?>" class="<?= $date == $today ? 'today' : '' ?>">
                        <?= htmlspecialchars($date) ?>
                    </th>
                <?php endforeach; ?>
                <th rowspan="4" class="signature">Duties Allocated</th>
                <th rowspan="4" class="signature">Total Duties</th>
            </tr>

            <tr>
                <?php foreach ($allDatesSlots as $date => $slots): ?>
                    <?php foreach ($slots as $slot => $_): ?>
                        <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>
                        <?php if ($filterDate && $filterDate !== $date) continue; ?>
                        <th><?= htmlspecialchars($slot) ?></th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>

            <tr>
                <?php foreach ($allDatesSlots as $date => $slots): ?>
                    <?php if ($filterDate && $filterDate !== $date) continue; ?>

                    <?php foreach ($slots as $slot => $_): ?>
                        <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>

                        <th>
                            <button class="btn btn-success th-btn"
                                onclick="printAttendance('<?= $date ?>','<?= $slot ?>')">
                                Attendance
                            </button>
                        </th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr>

            <!-- <tr>
                <?php foreach ($allDatesSlots as $date => $slots): ?>
                    <?php if ($filterDate && $filterDate !== $date) continue; ?>

                    <?php foreach ($slots as $slot => $_): ?>
                        <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>

                        <th>
                            <button class="btn btn-success th-btn"
                                onclick="openBlockSheet('<?= $date ?>','<?= $slot ?>')">
                                Block List
                            </button>
                        </th>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </tr> -->
            </thead> 
            <?php 
                $sr = 1;
                $duties_grabd_total = 0;
                $blocks_grabd_total = 0;
                $blocks_required_grand_total = [];
                $allocated_blocks_grabd_total = 0;
                $filtered_faculty_count = 0;
            ?>
             <tr>
                    <td colspan="12" class="add-cell" onclick="openFacultyMenu(event,this)">
                        <span class="add-icon">➕</span>
                        <span class="add-text">Add Faculty</span>
                    </td>
                </tr>
            <?php foreach ($facultyAssignments as $fid => $assignments): ?>

                <?php
                $sup_count = 0; 
                $blocks_assign = 0;
                
                // Apply filters
                if ($filterDept && $facultyDept[$fid] != $filterDept) continue;
                if ($filterRole && $facultyRole[$fid] != $filterRole) continue; // NEW: Role filter
                if ($search && strpos(strtolower($facultyName[$fid]), $search) === false) continue;
                
                $filtered_faculty_count++;
                ?>
                <tr class="tbl_row<?= $dutyCount[$fid] > 6 ? 'overload' : '' ?>">
                    <td><?= $sr++ ?></td>
                    <td class="left faculty-cell"
                        data-fid="<?= $fid ?>"
                        oncontextmenu="openFacultyMenu(event,this)">
                        <?= htmlspecialchars($facultyName[$fid]) ?>
                       
                    </td>
                    <td><?= htmlspecialchars($facultyDept[$fid]) ?></td>
                    <td class="role-cell">
                         <span class="role-badge <?= $facultyRole[$fid] === 'TS' ? 'role-ts' : 'role-nts' ?>">
                            <?= $facultyRole[$fid] ?>
                        </span>
                    </td>

                    <?php foreach ($allDatesSlots as $date => $slots): ?>
                        <?php if ($filterDate && $filterDate !== $date) continue; ?>

                        <?php foreach ($slots as $slot => $v): ?>
                            <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>
                            <?php
                            $class = '';
                            if (isset($conflicts[$fid][$date][$slot])) $class = 'conflict';

                            // Get block number if exists
                            $blockNumber = $assignments[$date][$slot]['block'] ?? '';
                            $blockType = $assignments[$date][$slot]['block_type'] ?? '';
                            $hasBlock = ($blockType == 'real');
                            ?>

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
                                <?php if ($assignments[$date][$slot]['assigned'] ?? false): ?>
                                    <?php if ($hasBlock): ?>
                                        <strong><?php echo !empty($blockNumber) ? $blockNumber : '✓'; ?></strong>
                                    <?php else: ?>
                                        *
                                    <?php endif; ?>
                                    <?php $sup_count++; ?>
                                    <?php if ($hasBlock) $blocks_assign++; ?>
                                    <?php else: ?>
                                <?php endif; ?>
                                <div class="con-tool"></div>
                            </td>

                        <?php endforeach; ?>
                    <?php endforeach; ?><div id="facultyMenu" class="context-menu"></div>
                    <?php 
                        $duties_grabd_total += $sup_count; 
                        $allocated_blocks_grabd_total += $blocks_assign;
                    ?>
                    <td><?= $blocks_assign ?></td>
                    <td><?= $sup_count ?></td>
                </tr>

            <?php endforeach; ?>

            <?php 
            // Calculate totals for displayed slots only
            $displayed_slots_total = 0;
            foreach ($slots_blocks as $date => $times): ?>
                <?php if ($filterDate && $filterDate !== $date) continue; ?>
                <?php foreach ($times as $slot => $_): ?>
                    <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>
                    <?php 
                        $displayed_slots_total += (int)($slots_blocks[$date][$slot]['blocks'] ?? 0);
                        $blocks_grabd_total += (int)($slots_blocks[$date][$slot]['blocks'] ?? 0); 
                        $blocks_required_grand_total[$date][$slot] = (int)($slots_blocks[$date][$slot]['total_required'] ?? 0); 
                    ?>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <tr class="grand-total">
                <td colspan="3">Total Blocks : </td>
                <td><?= $blocks_grabd_total ?></td>
                
                <?php foreach ($slots_blocks as $date => $times): ?>
                    <?php if ($filterDate && $filterDate !== $date) continue; ?>
                    <?php foreach ($times as $slot => $_): ?>
                        <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>
                        <td><?= ($slots_blocks[$date][$slot]['blocks'] ?? 0)."<br> / ".($blocks_required_grand_total[$date][$slot] ?? 0); ?></td>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                
                <td >
                    <?= $allocated_blocks_grabd_total ?>
                </td>
                <td>
                    <?= $duties_grabd_total ?>
                </td>
            </tr>
            
            <!-- Summary Row -->
            <tr class="table-info">
                <td colspan="4" class="text-end"><strong>Display Summary:</strong></td>
                <td colspan="<?= count($allDatesSlots) ?>">
                    Showing <?= $filtered_faculty_count ?> faculty 
                    <?= $filterDept ? "from $filterDept department" : "" ?>
                    <?= $filterRole ? "($filterRole)" : "" ?>
                    <?= $filterDate ? "on $filterDate" : "" ?>
                    <?= $filterSlot ? "at $filterSlot" : "" ?>
                    <?= $search ? "matching '$search'" : "" ?>
                </td>
                <td colspan="2" class="text-center">
                    <small>TS: Teaching Staff | NTS: Non-Teaching Staff</small>
                </td>
            </tr>
        </table>

        <!-- ================= ACTIONS ================= -->
        <form method="POST" class="mt-3 text-end">
            <button class="btn btn-success export-btn" name="export">
                <i class="fas fa-file-pdf"></i> Export PDF
            </button>
        </form>

        <!-- ================= MODALS ================= -->
        <div class="modal fade" id="replaceFacultyModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title">Replace Supervisor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">Select replacement faculty</label>
                        <select id="newFaculty" class="form-select"></select>
                    </div>

                    <div class="modal-footer">
                        <button type="button"
                                class="btn btn-secondary"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="button"
                                data-type=""
                                class="btn btn-success replace-add-faculty"
                                onclick="replaceFaculty()">
                            Replace
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= DIALOG ================= -->
        <div id="dialog" class="dialog-overlay">
            <div class="dialog">
                <div class="dialog-header">
                    <span class="f_name">Slot Attendance</span>
                    <span onclick="closeDialog()">×</span>
                </div>

                <div class="dialog-body">

                    <label>Present?</label>
                    <div class="radio-group">
                        <label><input type="radio" name="present" value="yes" checked> Yes</label>
                        <label><input type="radio" name="present" value="no"> No</label>
                    </div>

                    <div id="reasonBox" hidden>
                        <label>Reason</label>
                        <div class="radio-group">
                            <label><input type="radio" name="reason" value="late"> Late</label>
                            <label><input type="radio" name="reason" value="replace"> Self-replace</label>
                            <label><input type="radio" name="reason" value="other"> Other</label>
                        </div>
                    </div>

                    <div id="replaceBox" class="box" hidden>
                        <input id="facultySearch" type="text" placeholder="Search faculty">
                        <select id="facultyList"></select>
                        <span class="or"> ___ OR ___ </span>
                        <input id="replace_new_faculty" type="text" placeholder="Enter new faculty">
                    </div>

                    <div id="otherBox" class="box" hidden>
                        <textarea name="othertxt" id="othertxt"></textarea>
                    </div>

                </div>

                <div class="dialog-footer">
                    <button class="btn btn-cancel" onclick="closeDialog()">Cancel</button>
                    <button class="btn btn-warning" onclick="enableSwapMode()">Swap</button>
                    <button class="btn btn-primary" onclick="submitStatus()">Submit</button>
                </div>
            </div>
        </div>
        <div id="swap-rect"></div>
    </div>
    <script>
        let S_ID = '<?= $s_id ?>';
        let cell = null;
        let curr = {};
        let swapMode = false;
        let swapSource = null;
        let swapTarget = null;
        let ispresent = true;

        //full screen
        let elem = document.querySelector('body');
        const fsBtn = document.querySelector(".fullscreen");
        const exitBtn = document.querySelector(".exit-fullscreen");

        //full screen
        function openFullscreen() {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) { // Safari
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { // IE11
                elem.msRequestFullscreen();
            }
            fsBtn.style.display = "none";
            exitBtn.style.display = "flex";
        }

        function closeFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            fsBtn.style.display = "flex";
            exitBtn.style.display = "none";
        }

        /* ================= CELL CURSOR ================= */
        document.querySelectorAll('.cell').forEach(td => {
            if (td.innerText.trim() !== '' && td.innerText.trim() !== '-') {
                td.style.cursor = 'pointer';
            }
        });

        function drawSwapRectangle(source, target) {
            const rect = document.getElementById("swap-rect");

            const s = source.getBoundingClientRect();
            const t = target.getBoundingClientRect();

            const left = Math.min(s.left, t.left);
            const top = Math.min(s.top, t.top);
            const right = Math.max(s.right, t.right);
            const bottom = Math.max(s.bottom, t.bottom);

            rect.style.left = left + window.scrollX + "px";
            rect.style.top = top + window.scrollY + "px";
            rect.style.width = (right - left) + "px";
            rect.style.height = (bottom - top) + "px";
            rect.style.display = "block";
        }

        /* ================= ENABLE SWAP ================= */
        function enableSwapMode() {
            if (!cell || cell.innerText.trim() === '' || cell.innerText.trim() === '-') {
                alert("No faculty selected for swap");
                return;
            }

            swapMode = true;
            swapSource = cell;
            swapSource.classList.add("swap-source");

            closeDialog();
            alert("Click another FILLED cell in the other SLOT to swap");
        }

        /* ================= CELL CLICK HANDLER ================= */
        document.querySelectorAll(".cell").forEach(td => {
            td.addEventListener("click", e => {

                /* ---------- SWAP MODE ---------- */
                if (swapMode) {
                    e.stopPropagation();

                    if (td === swapSource) return;

                    // if (td.innerText.trim() === '' || td.innerText.trim() === '-') {
                    //     alert("Empty cell cannot be swapped");
                    //     return;
                    // }

                    // 🔄 Clear previous target if exists
                    if (swapTarget && swapTarget !== td) {
                        swapTarget.classList.remove("swap-target");
                        swapTarget.querySelector('.con-tool').style.display = 'none';
                    }

                    // ✅ Set new target
                    swapTarget = td;
                    td.classList.add("swap-target");
                    td.querySelector('.con-tool').style.display = 'block';

                    // 🟨 Draw rectangle between source & target
                    drawSwapRectangle(swapSource, swapTarget);

                    return;
                }

                /* ---------- NORMAL BLOCK UPDATE ---------- */
                if (td.innerText.trim() !== '' && td.innerText.trim() !== '-') {
                    let block = prompt("Enter Block No:");
                    if (block === null) return;

                    fetch("./Backend/update_block.php", {
                            method: "POST",
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                s_id: td.dataset.sid,
                                faculty_id: td.dataset.fid,
                                date: td.dataset.date,
                                slot: td.dataset.slot,
                                block: block
                            })
                        })
                        .then(r => r.json())
                        .then((data) => {
                            console.log(data);
                            if (data.status == 200) {
                                location.reload();
                                td.innerHTML = block.trim() ? `<strong>${block}</strong>` : "*";
                            } else {
                                alert(data.msg);
                            }
                        });
                }
            });
        });

        document.querySelectorAll('.con-tool').forEach(toolkit => {
            toolkit.addEventListener("click", e => {
                e.stopPropagation();
                const cell = toolkit.closest('td.cell');
                if (cell) {
                    if (!confirm("Confirm faculty swap?")) {
                        resetSwapUI();
                        return;
                    }

                    performSwap(cell);
                    return;
                }
            })
        });

        /* ================= PERFORM SWAP ================= */
        function performSwap(target) {

            fetch("swap_faculty_slot.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        from: {
                            fid: swapSource.dataset.fid,
                            date: swapSource.dataset.date,
                            slot: swapSource.dataset.slot,
                            s_id: swapSource.dataset.sid
                        },
                        to: {
                            fid: target.dataset.fid,
                            date: target.dataset.date,
                            slot: target.dataset.slot,
                            s_id: target.dataset.sid
                        }
                    })
                })
                .then(res => res.json())
                .then(data => {
                    console.log(data);
                    if (!data.success) {
                        alert(data.message || "Swap failed");
                        resetSwapUI();
                        return;
                    } else {
                        location.reload();
                    }

                    /* UI SWAP */
                    const tempHTML = swapSource.innerHTML;
                    swapSource.innerHTML = target.innerHTML;
                    target.innerHTML = tempHTML;

                    const tempData = {
                        ...swapSource.dataset
                    };
                    Object.assign(swapSource.dataset, target.dataset);
                    Object.assign(target.dataset, tempData);

                    resetSwapUI();
                });
        }

        /* ================= RESET SWAP ================= */
        function resetSwapUI() {
            swapMode = false;

            document.querySelectorAll(".swap-source")
                .forEach(c => c.classList.remove("swap-source"));

            document.querySelectorAll(".swap-target")
                .forEach(c => c.classList.remove("swap-target"));

            document.querySelectorAll(".con-tool")
                .forEach(c => c.style.display = 'none');

            document.getElementById("swap-rect").style.display = "none";

            swapTarget = null;
            swapSource = null;
        }

        /* ================= DIALOG ================= */
        function resetDialog() {
            document.querySelector('[name=present][value=yes]').checked = true;
            document.querySelector('[name=present][value=no]').checked = false;
            document.getElementById("reasonBox").hidden = true;
            document.getElementById("replaceBox").hidden = true;
            document.getElementById("otherBox").hidden = true;
            document.querySelectorAll('[name=reason]').forEach(r => r.checked = false);
            facultySearch.value = '';
            facultyList.innerHTML = '';
            ispresent = true;
        }

        function openDialog(e, td) {
            e.preventDefault();
            if (td.innerText.trim() === '' || td.innerText.trim() === '-') return;

            cell = td;
            resetDialog();

            curr = {
                fid: td.dataset.fid,
                date: td.dataset.date,
                slot: td.dataset.slot,
                s_id: td.dataset.sid,
                f_name: td.closest('tr').querySelector('.left').innerText.trim(),
                present: td.dataset.present === 'true'
            };

            document.querySelector('.f_name').innerText = curr.f_name;
            document.querySelector('[name=present][value=yes]').checked = curr.present;
            document.querySelector('[name=present][value=no]').checked = !curr.present;

            document.getElementById("dialog").style.display = "flex";
            loadFaculty();
        }

        function closeDialog() {
            document.getElementById("dialog").style.display = "none";
        }

        /* ================= PRESENT / REASON ================= */
        document.querySelectorAll('[name=present]').forEach(r => {
            r.onchange = () => {
                document.getElementById("reasonBox").hidden = r.value !== "no";
                ispresent = r.value !== "no";

                if (ispresent) {
                    document.getElementById("replaceBox").hidden = true;
                    document.getElementById("otherBox").hidden = true;
                }
            };
        });

        document.querySelectorAll('[name=reason]').forEach(r => {
            r.onchange = () => {
                if (!ispresent) {
                    document.getElementById("replaceBox").hidden = r.value !== "replace";
                    document.getElementById("otherBox").hidden = r.value !== "other";
                    if (r.value === "replace") loadFaculty();
                    if (r.value === "other") {
                        document.getElementById("othertxt").focus();
                    }
                }
            };
        });

        /* ================= LOAD FACULTY ================= */
        const facultySearch = document.getElementById("facultySearch");
        const facultyList = document.getElementById("facultyList");

        function loadFaculty() {
            fetch(`get_available_faculty.php?date=${curr.date}&slot=${curr.slot}&s=${curr.s_id}`)
                .then(r => r.json())
                .then(data => {
                    facultyList.innerHTML = `<option value="">Select Faculty</option>`;
                    data.forEach(f => {
                        facultyList.innerHTML += `<option value="${f.id}">${f.name} (${f.role})</option>`;
                    });
                });
        }

        facultySearch.addEventListener("keyup", () => {
            let q = facultySearch.value.toLowerCase();
            [...facultyList.options].forEach(o => {
                o.hidden = !o.text.toLowerCase().includes(q);
            });
        });

        function printAttendance(date, slot) {
            window.open(
                `./Backend/attendance_slot.php?s=<?= $s_id ?>&date=${date}&slot=${slot}`,
                '_blank'
            );
        }

        function openBlockSheet(date, slot) {
            window.open(
                `./Backend/slot_block_list.php?s=<?= $s_id ?>&date=${date}&slot=${slot}`,
                '_blank'
            );
        }

        /* Submit */
        function submitStatus() {
            let data = {
                fid: curr.fid,
                date: curr.date,
                slot: curr.slot,
                s_id: curr.s_id,
                present: document.querySelector('[name=present]:checked').value,
                reason: document.querySelector('[name=reason]:checked')?.value || '',
                replace_id: facultyList.value || '',
                other_reason: document.getElementById('othertxt')?.value || '',
                new_faculty: document.getElementById('replace_new_faculty')?.value || ''
            };
            console.log(data);
            fetch("change_faculty_slot.php", {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                })
                .then(res => res.json())
                .then((data) => {
                    console.log(data)
                    if (data.status == 200) {
                        location.reload();
                        // safest 
                    }
                    if (data.status === 'ok') {
                        location.reload();
                        // safest 
                    }
                });
            closeDialog();
        }

        //replace faculty directly
        let selectedFacultyId = null;

        function openFacultyMenu(e, el) {
            e.preventDefault();

            selectedFacultyId = el.dataset.fid;

            loadReplacementFaculty(el);
        }

        function loadReplacementFaculty(el) {
            fetch('./Backend/get_available_faculty.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    faculty_id: selectedFacultyId,
                    s_id : S_ID,
                    role_filter: '<?= $filterRole ?>' // Pass role filter
                })
            })
            .then(res => res.json())
            .then(data => {
                // console.log(data);
                let task_btn = document.querySelector('.replace-add-faculty');
                if(el.classList.contains('faculty-cell')){
                    task_btn.dataset.type = "replace";
                    task_btn.innerText = 'Replace';
                }else if(el.classList.contains('add-cell')){
                    task_btn.dataset.type = "add";
                    task_btn.innerText = 'Add';
                }
                showFacultyReplaceDialog(data);
            });
        }

        function showFacultyReplaceDialog(list) {
            const select = document.getElementById('newFaculty');
            select.innerHTML = '';

            if (list.length === 0) {
                select.innerHTML = `<option disabled>No available faculty</option>`;
            }else{
                list.forEach(f => {
                    const opt = document.createElement('option');
                    opt.value = f.id;
                    opt.textContent = `${f.faculty_name} (${f.dept_code}) - ${f.role}`;
                    select.appendChild(opt);
                });
            }

            const modal = new bootstrap.Modal(
                document.getElementById('replaceFacultyModal')
            );
            modal.show();
        }

        function replaceFaculty() {
            let task_btn = document.querySelector('.replace-add-faculty');
            let task_type = task_btn.dataset.type;
            task_btn.innerText = 'Processing...';
            task_btn.disabled = true;
            
            const newFacultyId = document.getElementById('newFaculty').value;

            if(task_type == 'replace'){
                fetch('./Backend/replace_faculty.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        old_fid: selectedFacultyId,
                        new_fid: newFacultyId,
                        s_id: S_ID
                    })
                })
                .then(res => res.json())
                .then(resp => {

                    if (!resp.success) {
                        alert(resp.error);
                        return;
                    }

                    task_btn.innerText = '';
                    task_btn.disabled = false;
                    bootstrap.Modal
                        .getInstance(document.getElementById('replaceFacultyModal'))
                        .hide();

                    location.reload();
                });
            }else if(task_type == 'add'){
                fetch('./Backend/add_to_supervision.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        faculty_id: newFacultyId,
                        s_id: S_ID
                    })
                })
                .then(res => res.json())
                .then(resp => {
                    console.log(resp)
                    if (!resp.success) {
                        alert(resp.error);
                        return;
                    }

                    bootstrap.Modal
                        .getInstance(document.getElementById('replaceFacultyModal'))
                        .hide();

                    location.reload();
                });
            }
        }
        
        // Quick filter shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+T for Teaching staff filter
            if (e.ctrlKey && e.key === 't') {
                e.preventDefault();
                window.location.href = '?s=<?= $s_id ?>&role=TS';
            }
            // Ctrl+N for Non-teaching staff filter
            if (e.ctrlKey && e.key === 'n') {
                e.preventDefault();
                window.location.href = '?s=<?= $s_id ?>&role=NTS';
            }
            // Ctrl+A for All roles
            if (e.ctrlKey && e.key === 'a') {
                e.preventDefault();
                window.location.href = '?s=<?= $s_id ?>';
            }
        });

        // ++++++++++++++++++++++++++++++++++++
        
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>