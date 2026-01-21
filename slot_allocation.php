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
    :root {
        --primary: #7c3aed;
        --primary-dark: #6d28d9;
        --primary-light: #8b5cf6;
        --primary-gradient: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        --secondary-gradient: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        --sidebar-bg: #1e293b;
        --sidebar-active: #334155;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
        --text-light: #64748b;
        --border: #b4b4b4;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --transition: all 0.3s ease;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --info: #3b82f6;
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: #f8fafc;
        color: var(--text-dark);
        margin: 0;
        padding: 0;
        overflow-x: hidden;
    }

    .container-fluid {
        padding: 20px;
        background: #f8fafc;
        min-height: 100vh;
    }

    /* Header Section */
    .header {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        justify-content: space-between;
    }

    /* Filter Form */
    .header form {
        display: flex;
        flex-wrap: wrap;
        /* gap: 12px; */
        flex: 1;
    }

    .inputfield {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        min-width: 180px;
        flex: 1;
    }

    .inputfield:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    .form-select, .form-control {
        padding: 10px 14px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 14px;
        transition: var(--transition);
        background: white;
        width: 100%;
    }

    .form-select:focus, .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    /* Buttons */
    .btn {
        padding: 10px 20px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-primary {
        background: var(--primary-gradient);
        color: white;
    }

    .btn-primary:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .btn-secondary {
        background: #e2e8f0;
        color: var(--text-dark);
    }

    .btn-secondary:hover {
        background: #cbd5e1;
    }

    .btn-success {
        background: var(--success);
        color: white;
    }

    .btn-warning {
        background: var(--warning);
        color: white;
    }

    .btn-info {
        background: var(--info);
        color: white;
    }

    /* Statistics Cards */
    .card {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
        transition: var(--transition);
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: var(--shadow-lg);
    }

    .card-body {
        padding: 0;
    }

    .bg-primary, .bg-success, .bg-warning, .bg-info {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .bg-primary { background: var(--primary); }
    .bg-success { background: var(--success); }
    .bg-warning { background: var(--warning); }
    .bg-info { background: var(--info); }

    /* Supervision Table */
    .supervision {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 30px !important;
    }

    .supervision thead {
        background: var(--primary);
        color: white;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .supervision th, .supervision td {
        border: 1px solid var(--border);
        padding: 10px;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    .supervision th {
        font-weight: 600;
        padding: 12px 10px;
        background: var(--primary);
        color: white;
    }

    .supervision td {
        background: white;
        transition: var(--transition);
    }

    .supervision tr:hover td {
        background: #f8fafc;
    }

    /* Cell States */
    .today {
        background: #fff7ed !important;
        font-weight: 600;
    }

    .overload {
        background: #fef2f2 !important;
    }

    .conflict {
        background: #fee2e2 !important;
        color: #dc2626;
        font-weight: 600;
    }

    .grand-total {
        background: #f1f5f9 !important;
        font-weight: 600;
        font-size: 14px;
    }

    .table-info {
        background: #eff6ff !important;
        font-size: 12px;
    }

    /* Faculty Name Cell */
    .left {
        text-align: left !important;
        font-weight: 500;
        cursor: pointer;
        padding-left: 15px !important;
        min-width: 200px;
    }

    .faculty-cell:hover {
        background: #f1f5f9;
    }

    /* Role Badges */
    .role-badge {
        display: inline-block;
        padding: 3px 8px;
        font-size: 11px;
        border-radius: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .role-ts {
        background: rgba(79, 70, 229, 0.1);
        color: #4f46e5;
        border: 1px solid rgba(79, 70, 229, 0.2);
    }

    .role-nts {
        background: rgba(5, 150, 105, 0.1);
        color: #059669;
        border: 1px solid rgba(5, 150, 105, 0.2);
    }

    .role-cell {
        width: 80px;
        min-width: 80px;
    }

    /* Cell Actions */
    .cell {
        position: relative;
        cursor: pointer;
        transition: var(--transition);
        min-width: 60px;
    }

    .cell:hover {
        background: #f1f5f9 !important;
        transform: scale(1.05);
        z-index: 5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .cell[data-present="false"] {
        background: #fef2f2 !important;
        color: #dc2626;
        border: 1px solid #fca5a5 !important;
    }

    /* Swap Visual Effects */
    .swap-source {
        outline: 2px solid var(--primary);
        background: #dbeafe !important;
        position: relative;
        z-index: 10;
    }

    .swap-target {
        outline: 2px dashed var(--warning);
        background: #fff7ed !important;
        position: relative;
        z-index: 10;
    }

    #swap-rect {
        position: absolute;
        border: 2px solid #0bf517ff;
        background: rgba(175, 245, 11, 0.08);
        pointer-events: none;
        z-index: 9998;
        display: none;
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

    /* Add Faculty Cell */
    .add-cell {
        cursor: pointer;
        text-align: center;
        padding: 15px !important;
        background: #f8fafc;
        border: 2px dashed #cbd5e1;
        color: var(--text-light);
        font-weight: 600;
        font-size: 14px;
        transition: var(--transition);
    }

    .add-cell:hover {
        background: #f1f5f9;
        border-color: var(--primary);
        color: var(--primary);
        transform: translateY(-2px);
    }

    .add-icon {
        margin-right: 8px;
        font-size: 16px;
    }

    /* Split Cell for Block Display */
    .split-cell {
        display: flex;
        flex-direction: column;
        gap: 2px;
        padding: 8px 4px !important;
    }

    .split-cell span {
        display: block;
        line-height: 1.2;
    }

    .split-cell span:first-child {
        font-weight: 600;
        color: var(--primary);
    }

    .split-cell span:last-child {
        font-size: 11px;
        color: var(--text-light);
    }

    /* Fullscreen Buttons */
    .fullscreen, .exit-fullscreen {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-sm);
        border: none;
        background: var(--primary);
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .fullscreen:hover, .exit-fullscreen:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: var(--shadow-lg);
    }

    .exit-fullscreen {
        display: none;
    }

    /* Export Button */
    .export-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 24px;
        background: var(--success);
        color: white;
        border-radius: 25px;
        box-shadow: var(--shadow-lg);
        z-index: 100;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .export-btn:hover {
        background: #0da271;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
    }

    /* Modal/Dialog Styling */
    .dialog-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 10000;
        padding: 20px;
    }

    .dialog {
        background: white;
        border-radius: var(--radius);
        width: 100%;
        max-width: 500px;
        max-height: 90vh;
        overflow-y: auto;
        box-shadow: var(--shadow-lg);
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dialog-header {
        background: var(--primary-gradient);
        color: white;
        padding: 20px;
        font-size: 18px;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-radius: var(--radius) var(--radius) 0 0;
    }

    .dialog-body {
        padding: 20px;
    }

    .dialog-footer {
        padding: 15px 20px;
        background: #f8fafc;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        border-radius: 0 0 var(--radius) var(--radius);
    }

    /* Context Menu */
    .context-menu {
        position: fixed;
        background: white;
        border-radius: var(--radius-sm);
        box-shadow: var(--shadow-lg);
        min-width: 180px;
        z-index: 10000;
        overflow: hidden;
        border: 1px solid var(--border);
    }

    .context-menu div {
        padding: 10px 15px;
        cursor: pointer;
        transition: var(--transition);
        font-size: 14px;
        border-bottom: 1px solid var(--border);
    }

    .context-menu div:last-child {
        border-bottom: none;
    }

    .context-menu div:hover {
        background: #f1f5f9;
        color: var(--primary);
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .supervision {
            font-size: 12px;
        }
        
        .supervision th, 
        .supervision td {
            padding: 8px 6px;
        }
        
        .left {
            min-width: 150px;
        }
    }

    @media (max-width: 992px) {
        .container-fluid {
            padding: 15px;
        }
        
        .header {
            flex-direction: column;
            align-items: stretch;
        }
        
        /* .header form {
            flex-direction: column;
        } */
        
        .inputfield {
            min-width: 100%;
        }
        
        .supervision {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .left {
            min-width: 180px;
            position: sticky;
            left: 0;
            background: white;
            z-index: 5;
        }
        
        .role-cell {
            position: sticky;
            left: 180px;
            background: white;
            z-index: 5;
        }
    }

    @media (max-width: 768px) {
        .container-fluid {
            padding: 10px;
        }
        
        .header {
            padding: 15px;
        }
        
        .supervision {
            font-size: 11px;
        }
        
        .supervision th, 
        .supervision td {
            padding: 6px 4px;
        }
        
        .card-body .row {
            flex-direction: column;
            gap: 15px;
        }
        
        .card-body .col-md-3 {
            width: 100%;
        }
        
        .btn {
            padding: 8px 16px;
            font-size: 13px;
        }
        
        .export-btn {
            bottom: 15px;
            right: 15px;
            padding: 10px 20px;
            font-size: 13px;
        }
    }

    @media (max-width: 576px) {
        .container-fluid {
            padding: 8px;
        }
        
        .header {
            padding: 12px;
        }
        
        .supervision {
            font-size: 10px;
        }
        
        .supervision th, 
        .supervision td {
            padding: 4px 3px;
        }
        
        .left {
            min-width: 120px;
            font-size: 11px;
            padding-left: 10px !important;
        }
        
        .role-cell {
            left: 120px;
            min-width: 60px;
            width: 60px;
        }
        
        .role-badge {
            padding: 2px 6px;
            font-size: 9px;
        }
        
        .btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .fullscreen, .exit-fullscreen {
            width: 36px;
            height: 36px;
            font-size: 14px;
        }
        
        .export-btn {
            bottom: 10px;
            right: 10px;
            padding: 8px 16px;
            font-size: 12px;
        }
    }

    /* Print Styles */
    @media print {
        body {
            background: white;
            font-size: 12px;
        }
        
        .container-fluid {
            padding: 0;
        }
        
        .header,
        .card,
        .btn,
        .fullscreen,
        .exit-fullscreen,
        .export-btn,
        .add-cell {
            display: none !important;
        }
        
        .supervision {
            box-shadow: none;
            border: 1px solid #000;
        }
        
        .supervision th {
            background: #f0f0f0 !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .cell:hover,
        .swap-source,
        .swap-target {
            outline: none !important;
            background: white !important;
        }
        
        .conflict {
            background: #f0f0f0 !important;
        }
        
        .today {
            background: #fff8e1 !important;
        }
    }

    /* Utility Classes */
    .text-end {
        text-align: right;
    }
    
    .text-center {
        text-align: center;
    }
    
    .fw-bold {
        font-weight: 600;
    }
    
    .mb-0 {
        margin-bottom: 0;
    }
    
    .text-muted {
        color: var(--text-light);
    }
    
    .mt-3 {
        margin-top: 15px;
    }
    
    .d-flex {
        display: flex;
    }
    
    .align-items-center {
        align-items: center;
    }
    
    .me-3 {
        margin-right: 15px;
    }
    
    /* Animation for table rows */
    .tbl_row {
        animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Scrollbar Styling */
    .dialog::-webkit-scrollbar,
    .supervision::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }

    .dialog::-webkit-scrollbar-track,
    .supervision::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .dialog::-webkit-scrollbar-thumb,
    .supervision::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .dialog::-webkit-scrollbar-thumb:hover,
    .supervision::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Tooltip for hover effects */
    [title] {
        position: relative;
    }

    [title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: white;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 1000;
        pointer-events: none;
    }

    /* Loading indicator */
    .loading {
        position: relative;
        color: transparent !important;
    }

    .loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
    }

    @keyframes spin {
        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
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
                    <button class="btn" type="reset" style = "padding: 0;">
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

            <tr>
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
            </tr>
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
                            $hasBlock = !empty($blockNumber);
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
                                        <strong>✓</strong>
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
                    <?php endforeach; ?>
                    <!-- <div id="facultyMenu" class="context-menu"></div> -->
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
            <!-- <tr class="table-info">
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
            </tr> -->
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