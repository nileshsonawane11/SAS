<?php
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
    f.dept_code
FROM block_supervisor_list bsl
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
$allDatesSlots = [];
$dutyCount = [];
$conflicts = [];

while ($row = mysqli_fetch_assoc($res)) {

    $fid = $row['faculty_id'];
    $schedule = json_decode($row['schedule'], true);

    $facultyName[$fid] = $row['faculty_name'];
    $facultyDept[$fid] = $row['dept_code'];
    $dutyCount[$fid] = 0;

    foreach ($schedule as $date => $slots) {
        foreach ($slots as $slot => $v) {
            /* CONFLICT CHECK */
            if (isset($facultyAssignments[$fid][$date][$slot])) {
                $conflicts[$fid][$date][$slot] = true;
            }

            $facultyAssignments[$fid][$date][$slot]['assigned'] = true;
            if(isset($slots[$slot]['block'])){
                $facultyAssignments[$fid][$date][$slot]['block'] = $slots[$slot]['block'];
            }
            if(isset($slots[$slot]['present'])){
                $facultyAssignments[$fid][$date][$slot]['present'] = $slots[$slot]['present'];
            }
            $allDatesSlots[$date][$slot] = true;
            $dutyCount[$fid]++;
        }
    }
}

// echo "<pre>";
// print_r($facultyAssignments);
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

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
    background: #ffcdff;
}
.left { text-align: left; }
.today { background: #ffeeba !important; }
.overload { background: #ffcccc; }
.conflict { background: #ff6666; color: #fff; }
.signature { width: 120px; }
.th-btn{
    font-size: 13px;
    padding: 5px;
    width: 100%;
}
.context-menu{
    position:absolute;
    background:#fff;
    border:1px solid #333;
    box-shadow:2px 2px 10px rgba(0,0,0,.2);
    display:none;
    z-index:9999;
    max-height:300px;
    overflow:auto;
    font-size:13px;
}
.context-menu div{
    padding:6px 10px;
    cursor:pointer;
}
.context-menu div:hover{
    background:#f0f0f0;
}
.inputfield{
    font-size: 15px;
    width: 100%;
}
.col-md-1,
.col-md-2{
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
    box-shadow: 0 20px 40px rgba(0,0,0,.25);
    animation: pop .25s ease-out;
    overflow: hidden;
    font-family: 'Inter', system-ui, sans-serif;
}

/* ================= HEADER ================= */
.dialog-header {
    background: linear-gradient(135deg,#2563eb,#1e40af);
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
    box-shadow: 0 0 0 2px rgba(37,99,235,.15);
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
.dialog-header .f_name{
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
.export-btn{
    position: fixed;
    bottom: 15px;
    right: 15px;
}
.mb-3 {
    margin-bottom: 4rem !important;
    padding-right: calc(var(--bs-gutter-x) * 1);
    padding-left: calc(var(--bs-gutter-x) * 1);
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
</style>
</head>

<body>

<div class="container-fluid mt-3 mb-3">

<!-- ================= FILTERS ================= -->
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
                <option <?= $filterDept==$d?'selected':'' ?>><?= $d ?></option>
            <?php endforeach; ?>
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

<!-- ================= TABLE ================= -->
<table class="supervision">

<tr>
    <th rowspan="4">Sr</th>
    <th rowspan="4">Supervisor</th>
    <th rowspan="4">Dept</th>

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
        <th colspan="<?= $visibleSlots ?>" class="<?= $date==$today?'today':'' ?>">
            <?= htmlspecialchars($date) ?>
        </th>
    <?php endforeach; ?>

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

<?php $sr=1; ?>
<?php foreach ($facultyAssignments as $fid => $assignments): ?>

<?php
if ($filterDept && $facultyDept[$fid] != $filterDept) continue;
if ($search && strpos(strtolower($facultyName[$fid]), $search) === false) continue;
?>

<tr class="<?= $dutyCount[$fid] > 6 ? 'overload':'' ?>">
    <td><?= $sr++ ?></td>
    <td class="left"><?= htmlspecialchars($facultyName[$fid]) ?></td>
    <td><?= htmlspecialchars($facultyDept[$fid]) ?></td>

    <?php foreach ($allDatesSlots as $date => $slots): ?>
        <?php if ($filterDate && $filterDate !== $date) continue; ?>

        <?php foreach ($slots as $slot => $v): ?>
            <?php if ($filterSlot && $filterSlot !== $slot) continue; ?>
            <?php
            $class = '';
            if (isset($conflicts[$fid][$date][$slot])) $class='conflict';
            
            // echo "<pre>";print_r($assignments);echo "</pre>";
            ?>
            
            <td class="<?= $class ?> cell"
                data-fid="<?= $fid ?>"
                data-date="<?= $date ?>"
                data-slot="<?= $slot ?>"
                data-sid="<?= $s_id ?>"
                data-present="<?= isset($assignments[$date][$slot]['present']) ? 'true' : 'false' ?>"
                oncontextmenu="openDialog(event,this)"
                onclick="updateBlock(<?= $fid ?>,'<?= $date ?>','<?= $slot ?>',this)">
                <?= ($assignments[$date][$slot]['assigned'] ?? false)
                    ? (!empty($assignments[$date][$slot]['block'])
                        ? "<strong>{$assignments[$date][$slot]['block']}</strong>"
                        : "✓")
                    : ""
                ?>
            </td>

        <?php endforeach; ?>
    <?php endforeach; ?><div id="facultyMenu" class="context-menu"></div>

</tr>

<?php endforeach; ?>

</table>

<!-- ================= ACTIONS ================= -->
<form method="POST" class="mt-3 text-end">
    <button class="btn btn-success export-btn" name="export">
        Export PDF
    </button>
</form>

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
                    <label><input type="radio" name="reason" value="replace"> Replace</label>
                    <label><input type="radio" name="reason" value="other"> Other</label>
                </div>
            </div>

            <div id="replaceBox" class="box" hidden>
                <input id="facultySearch" type="text" placeholder="Search faculty">
                <select id="facultyList"></select>
            </div>

        </div>

        <div class="dialog-footer">
            <button class="btn btn-cancel" onclick="closeDialog()">Cancel</button>
            <button class="btn btn-primary" onclick="submitStatus()">Submit</button>
        </div>
    </div>
</div>


</div>
<script>

document.querySelectorAll('.cell').forEach((el)=>{
    if(el.innerText != ''){
        el.style.cursor = "pointer";
    }
})

function updateBlock(fid, date, slot,event) {
    console.log(event.innerText)
    if((event.innerText).trim() != ""){
        let block = prompt("Enter Block No:");
        if (!block) return;

        fetch("./Backend/update_block.php", {
            method: "POST",
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                s_id: "<?= $s_id ?>",
                faculty_id: fid,
                date: date,
                slot: slot,
                block: block
            })
        })
        .then(r => r.text())
        .then((data) => {
            if(block.trim() != ''){
               event.innerHTML = "<strong>"+block+"</strong>"; 
            }else{
                event.innerHTML = "✓";
            }
            
        });
    }
}

function printAttendance(date, slot) {
    window.open(
        `./Backend/attendance_slot.php?s=<?= $s_id ?>&date=${date}&slot=${slot}`,
        '_blank'
    );
}

function openBlockSheet(date, slot){
    window.open(
        `./Backend/slot_block_list.php?s=<?= $s_id ?>&date=${date}&slot=${slot}`,
        '_blank'
    );
}

let selectedCell = null;

function openFacultyMenu(e, cell) {
    e.preventDefault(); // block browser menu
    selectedCell = cell;
    if(selectedCell.innerText != ''){
        const date = cell.dataset.date;
        const slot = cell.dataset.slot;


        fetch(`get_available_faculty.php?date=${encodeURIComponent(date)}&slot=${encodeURIComponent(slot)}&s=<?= $s_id; ?>`)
        .then(res => res.json())
        .then(data => {

            const menu = document.getElementById('facultyMenu');
            menu.innerHTML = '';

            if (data.length === 0) {
                menu.innerHTML = '<div>No available faculty</div>';
            } else {
                data.forEach(f => {
                    const div = document.createElement('div');
                    div.textContent = `${f.sr}. ${f.name} (${f.dept})`;
                    div.onclick = () => assignFaculty(f.id);
                    menu.appendChild(div);
                });
            }

            menu.style.left = e.pageX + 'px';
            menu.style.top  = e.pageY + 'px';
            menu.style.display = 'block';
        });
    }
}

// hide menu on click
document.addEventListener('click', () => {
    document.getElementById('facultyMenu').style.display = 'none';
});

function assignFaculty(newFid) {

    const date = selectedCell.dataset.date;
    const slot = selectedCell.dataset.slot;
    const oldFid = selectedCell.dataset.fid;


    fetch('change_faculty_slot.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: `old_fid=${oldFid}&new_fid=${newFid}&date=${date}&slot=${slot}&s=<?= $s_id; ?>`
    })
    .then(res => res.json())
    .then(r => {
        console.log(r);
        if (r.status === 'ok') {
            location.reload(); // safest
        } else {
            alert(r.message);
        }
    });
}


//=======================================================

let cell = null;
let curr = {};

function resetDialog() {

    /* Present = YES (default) */
    document.querySelector('[name=present][value=yes]').checked = true;
    document.querySelector('[name=present][value=no]').checked = false;

    /* Hide conditional sections */
    document.getElementById("reasonBox").hidden = true;
    document.getElementById("replaceBox").hidden = true;

    /* Clear reason radios */
    document.querySelectorAll('[name=reason]').forEach(r => r.checked = false);

    /* Clear replace inputs */
    document.getElementById("facultySearch").value = '';
    document.getElementById("facultyList").innerHTML = '';
    document.querySelector('.f_name').innerText = 'Slot Attendance';

    /* Clear other reason */
}

function openDialog(e, td){
    e.preventDefault();
    cell = td;
    if(cell.innerText != ''){
        resetDialog()

        curr = {
            fid: td.dataset.fid,
            date: td.dataset.date,
            slot: td.dataset.slot,
            s_id: td.dataset.sid,
            f_name : td.closest('tr').querySelector('.left').innerText.trim(),
            present : td.dataset.present === 'true'
        };

        console.log(curr)

        document.getElementById("dialog").style.display = "flex";
        document.querySelector('.f_name').innerText = curr.f_name;
        document.querySelector('[name=present][value=yes]').checked = curr.present;
        document.querySelector('[name=present][value=no]').checked = !curr.present;
        document.getElementById("reasonBox").hidden = true;
        document.getElementById("replaceBox").hidden = true;
        loadFaculty();
    }
}

function closeDialog(){
    document.getElementById("dialog").style.display = "none";
}

/* Present / Absent */
document.querySelectorAll('[name=present]').forEach(r=>{
    r.onchange = () => {
        document.getElementById("reasonBox").hidden = r.value !== "no";
    };
});

/* Reason */
document.querySelectorAll('[name=reason]').forEach(r=>{
    r.onchange = ()=>{
        document.getElementById("replaceBox").hidden = r.value !== "replace";

        if(r.value === "replace") loadFaculty();
    };
});

/* Load faculty */
function loadFaculty(){
    fetch(`get_available_faculty.php?date=${curr.date}&slot=${curr.slot}&s=${curr.s_id}`)
    .then(r=>r.json())
    .then(data=>{
        let list = document.getElementById("facultyList");
        list.innerHTML = "";

        data.forEach(f=>{
            list.innerHTML += `<option value="${f.id}">${f.name}</option>`;
        });
    });
}

/* Search */
facultySearch.onkeyup = function(){
    let q = this.value.toLowerCase();
    [...facultyList.options].forEach(o=>{
        o.hidden = !o.text.toLowerCase().includes(q);
    });
};

/* Submit */
function submitStatus(){
    let data = {
        fid: curr.fid,
        date: curr.date,
        slot: curr.slot,
        s_id: curr.s_id,
        present: document.querySelector('[name=present]:checked').value,
        reason: document.querySelector('[name=reason]:checked')?.value || '',
        replace_id: facultyList.value || ''
    };

    fetch("change_faculty_slot.php",{
        method:"POST",
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(data)
    })
    .then(res=>res.json())
    .then((data)=>{
        console.log(data)
        if(data.status == 200){
            location.reload(); // safest
        }
        if (data.status === 'ok') {
            location.reload(); // safest
        }
    });

    closeDialog();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
