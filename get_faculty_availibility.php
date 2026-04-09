<?php
require './Backend/auth_guard.php';
include './Backend/config.php';
$owner = $user_data['_id'] ?? 0;

$s_id = $_GET['s'] ?? '';

$schedule_row = [];
$faculty = [];

/* =====================================================
   LOAD FACULTY
   ===================================================== */
$schedule_result = mysqli_query($conn,"SELECT * FROM schedule WHERE id = '$s_id' AND Created_by = '$owner'");
if(mysqli_num_rows($schedule_result) > 0){
    $schedule_row = mysqli_fetch_assoc($schedule_result);
    
    if($schedule_row['scheduled']){
        header("Location: ./slot_allocation.php?s=$s_id");
        exit;
    }

    $task_name = $schedule_row['task_name'];
    $task_type = $schedule_row['task_type'];

}

// echo "<pre>";
// print_r($prev_facultyLoad);
// echo "</pre>";return;

$q = mysqli_query($conn, "
    SELECT id, faculty_name, dept_code, role, duties
    FROM faculty
    WHERE status='ON' AND duties > 0 AND Created_by = '$owner'
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

// echo "<pre>";
// print_r($records);
// echo "</pre>";

// echo "<pre>";
// print_r($faculty);
// echo "</pre>";

$dates = [];
$slotsByDate = [];

foreach ($records as $item) {
    $date = $item['date'];
    $slot = $item['slot'];

    $dates[$date] = $date;
    $slotsByDate[$date][$slot] = $slot;
}

// store all slots in session (needed for false values)
$_SESSION['all_slots'] = $slotsByDate;


// Sort by date
uksort($slotsByDate, function ($a, $b) {
    return strtotime($a) <=> strtotime($b);
});

// Sort slots inside each date
foreach ($slotsByDate as &$slot) {
    uksort($slot, function ($a, $b) {
        $startA = strtotime(explode(' - ', $a)[0]);
        $startB = strtotime(explode(' - ', $b)[0]);
        return $startA <=> $startB;
    });
}
unset($slot);



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $availability = $_POST['availability'] ?? [];

    // Step 1: mark all received as TRUE
    foreach ($availability as $fid => $dates) {
        foreach ($dates as $date => $slots) {
            foreach ($slots as $slot => $val) {
                $availability[$fid][$date][$slot] = true;
            }
        }
    }

    // Step 2: fill missing (unchecked) as FALSE
    // foreach ($_SESSION['all_slots'] as $date => $slots) {
    //     foreach ($slots as $slot) {
    //         foreach ($faculty as $f) {
    //             $fid = $f['id'];

    //             if (!isset($availability[$fid][$date][$slot])) {
    //                 $availability[$fid][$date][$slot] = false;
    //             }
    //         }
    //     }
    // }

    // save to session
    $_SESSION['faculty_availability'] = $availability;

    header("Location: ./allocate.php?s=$s_id");
    exit;
}

$sr = 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body {
            font-family:  sans-serif;
            background: #f8fafc;
            color: var(--text-dark);
            margin: 0;
            padding: 0;
            overflow-x: scroll;
        }

        form {
            margin: 20px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            font-size: 11px;
        }

        thead th, td {
            border: 1px solid #ddd;
            padding: 6px;
        }

        thead {
            background: #1f2937;
            color: #fff;
            position: sticky;
            font-size: 13px;
            top: 0;
            z-index: 2;
        }

        tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        tbody tr:hover {
            background: #e0f2fe;
        }

        input, select {
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background: #2563eb;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
        }

        button:hover {
            background: #1e40af;
        }

        .slot-checkbox {
            transform: scale(1.2);
            cursor: pointer;
        }
        /* hide default checkbox */
        .custom-checkbox input {
            display: none;
        }

        /* base box */
        .custom-checkbox .checkmark {
            width: 15px;
            height: 15px;
            display: inline-block;
            border-radius: 50%;
            border: 2px solid #ccc;
            position: relative;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        /* CHECKED → GREEN BOX ✔ */
        .custom-checkbox input:checked + .checkmark {
            background-color: #22c55e;
            border-color: #16a34a;
        }

        /* tick mark */
        .custom-checkbox input:checked + .checkmark::after {
            content: "✔";
            color: white;
            font-size: 11px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* UNCHECKED → RED CIRCLE ❌ */
        .custom-checkbox input:not(:checked) + .checkmark {
            background-color: #fee2e2;
            border-radius: 50%;
            border-color: #dc2626;
        }

        /* cross mark */
        .custom-checkbox input:not(:checked) + .checkmark::after {
            content: "✖";
            color: #dc2626;
            font-size: 11px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        /* hover effect */
        .custom-checkbox:hover .checkmark {
            transform: scale(1.1);
        }

        /* optional: center alignment */
        td {
            text-align: center;
        }
        .full-cell {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* Ensure td has height */
        td.slot-cell {
            height: 40px;
            padding: 0;
        }
    </style>
</head>
<body>
    <?php include 'back_button.php'; ?>
    <form method="POST">
        <div style="margin-bottom:15px; margin-top: 50px; display:flex; gap:10px; flex-wrap:wrap;">

            <input type="text" id="searchName" placeholder="Search Name..."
                style="padding:6px; width:200px;">

            <select id="filterRole">
                <option value="">All Roles</option>
                <option value="TS">Teaching (TS)</option>
                <option value="NTS">Non-Teaching (NTS)</option>
            </select>

            <select id="filterDept">
                <option value="">All Departments</option>
                <?php
                $uniqueDepts = array_unique($facultyMap);
                foreach ($uniqueDepts as $dept):
                ?>
                    <option value="<?= $dept ?>"><?= $dept ?></option>
                <?php endforeach; ?>
            </select>

        </div>

        <table border="1" cellpadding="8">
            <thead>
                <tr>
                    <th rowspan="2" class="sr">#</th>
                    <th rowspan="2">Faculty Name</th>
                    <th rowspan="2" class="dept">Dept</th>
                    <th rowspan="2" class="dept">Role</th>

                    <?php foreach ($slotsByDate as $date => $times): ?>
                        <th colspan="<?= count($times) ?>"><?= htmlspecialchars($date) ?></th>
                    <?php endforeach; ?>

                    <th rowspan="2" class="duty">Duties</th>

                </tr>
                <tr>
                    <?php foreach ($slotsByDate as $date => $times): ?>
                        <?php foreach ($times as $slot => $_): ?>
                            <th><?= htmlspecialchars($slot) ?></th>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tr>
            </thead>
            

            <?php foreach ($faculty as $f): ?>
                <tr class="faculty-row"
                    data-name="<?= strtolower($facultyName[$f['id']]) ?>"
                    data-role="<?= $facultyRole[$f['id']] ?>"
                    data-dept="<?= $facultyMap[$f['id']] ?>"
                    data-duties="<?= $facultyDuties[$f['id']] ?>">

                    <td><?= $sr++ ?></td>
                    <td class="left"><?= htmlspecialchars($facultyName[$f['id']] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($facultyMap[$f['id']] ?? '-') ?></td>
                    <td><?= htmlspecialchars($facultyRole[$f['id']] ?? '-') ?></td>
                    <?php $globalIndex = 0; ?>

                    <?php foreach ($slotsByDate as $date => $slots): ?>
                        <?php foreach ($slots as $slot): ?>
                            <td align="center" class="slot-cell">
                                <label class="custom-checkbox full-cell">
                                    <input type="checkbox"
                                        class="slot-checkbox"
                                        data-fid="<?= $f['id'] ?>"
                                        name="availability[<?= $f['id'] ?>][<?= $date ?>][<?= $slot ?>]"
                                        value="1"
                                        checked>

                                    <span class="checkmark"></span>
                                </label>
                            </td>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                    <td><?= htmlspecialchars($facultyDuties[$f['id']] ?? '-') ?></td>
                </tr>
            <?php endforeach; ?>

        </table>

        <br>
        <button type="submit">Save</button>
    </form>

    <script>
        const nameInput = document.getElementById('searchName');
        const roleFilter = document.getElementById('filterRole');
        const deptFilter = document.getElementById('filterDept');

        const rows = document.querySelectorAll('.faculty-row');

        function filterTable() {
            let name = nameInput.value.toLowerCase();
            let role = roleFilter.value;
            let dept = deptFilter.value;

            rows.forEach(row => {
                let rName = row.dataset.name;
                let rRole = row.dataset.role;
                let rDept = row.dataset.dept;

                let show = true;

                if (name && !rName.includes(name)) show = false;
                if (role && rRole !== role) show = false;
                if (dept && rDept !== dept) show = false;

                row.style.display = show ? '' : 'none';
            });
        }

        // attach events
        [nameInput, roleFilter, deptFilter].forEach(el => {
            el.addEventListener('input', filterTable);
        });

    </script>

</body>
</html>