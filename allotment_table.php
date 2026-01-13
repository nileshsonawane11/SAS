<?php 
    include "./Backend/config.php";
    $type = $_GET['type'] ?? '';
    $s = $_GET['s'] ?? '';

    function facultyBusy($conn, $facultyId, $date, $time, $s) {
        $q = mysqli_query($conn, "
            SELECT 1
            FROM block_supervisor bsv
            JOIN block_schedule bs ON bs.id = bsv.block_schedule_id
            WHERE bsv.faculty_id = $facultyId
            AND bs.schedule_date = '$date'
            AND bs.schedule_time = '$time'
            AND bsv.s_id = '$s'
            LIMIT 1
        ");

        return mysqli_num_rows($q) > 0;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocks</title>
    <style>
        :root{
            --gradient-bg: linear-gradient(120deg, rgb(245 200 255), rgba(174, 0, 255, 1));
            --menu-bg: #34495E;
            --btn-bg:linear-gradient(270deg, rgb(232 127 255), rgba(174, 0, 255, 1));
        }
        *{
            margin: 0;
            padding: 0;
            font-family:Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }
        body{
            height: 100vh;
            width: 100%;
            display: flex;
            flex-direction: row;
        }
        .container{
            width: 100%;
            overflow: hidden;
            position: relative;
        }
        .nav{
            height: 50px;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 10px;
        }
        .nav-part{
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .nav-part h3{
            padding-left: 30px;
        }
        .nav-part:first-child{
            font-size: 27px;
        }
        .nav-part:nth-child(2){
            font-size: 20px;
        }
        .nav-part:nth-child(3){
            padding-right: 30px;
        }
        .card{
            background: var(--gradient-bg);
            height: 85vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-x: auto;
        }
        button{
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            border: none;
            font-size: 20px;
            padding: 10px;
            gap: 10px;
            color: #ffff;
            background: var(--btn-bg);
            cursor: pointer;
        }
        .block-data{
            padding: 15px;
            border-radius: 15px;
            display: grid;
            grid-template-columns: 10% 60% 15% 15%;
            background: #fff;
            justify-items: center;
            align-items: center;
        }
        .block-data:first-child{
            position: sticky;
            top: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .courses{
            display: flex;
            align-content: center;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            flex-direction: row;
            gap: 15px;
        }
        .blk-no{
            padding: 5px;
            font-size: 20px;
            height: 40px;
            font-weight: 600;
            display: flex;
            width: 40px;
            background: var(--gradient-bg);
            border-radius: 50%;
            color: #fff;
            align-items: center;
            justify-content: center;
        }
        .c-list{
            padding: 5px;
            border-radius: 7px;
        }
        .input-form{
            position: absolute;
            bottom: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
            transform: translate(0px, 400px);
            align-items: center;
            transition: 0.3s ease-in-out all;
        }
        .input-form.active{
            transform: translate(0px, 0px);
        }
        .data-form{
            width: 100%;
            background: #fff;
            height: 400px;
        }
        .indicator{
            background: #ffffff;
            height: 25px;
            width: 8%;
            display: flex;
            border-radius: 15px 15px 0 0;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 1px solid black;
        }
        .indicator svg{
            transform: rotate(180deg);
            transition: 0.3s ease-in-out all;
        }
        .indicator svg.active{
            transform: rotate(0deg);
        }
        .form-row{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }
        .inputfield{
            display: flex;
            flex-direction: column;
            gap: 5px;
            padding: 25px 40px;
        }
        .inputfield input{
            padding-left: 10px;
        }
        select{
            cursor: pointer;
        }
        .inputfield input,
        .inputfield select,
        .inputfield textarea{
            height: 40px;
            width: 100%;
            padding: 0 10px;
            font-size: 17px;
            border: 1px solid #000;
            border-radius: 5px;
            outline: none;
            box-sizing: border-box;
        }
        .inputfield textarea{
            max-width: 100%;
            min-width: 100%;
            max-height: 200px;
            min-height: 40px;
        }
        .required{
            color: #ff0000ff;
        }
        .Add_block{
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            border: none;
            font-size: 20px;
            padding: 10px;
            gap: 10px;
            color: #ffff;
            background: var(--btn-bg);
            cursor: pointer;
        }
        .staff-form-body{
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 20px;
            padding: 40px;
        }
        .table-wrapper {
            max-height: 535px;      /* height of scroll area */
            overflow-y: auto;
            border: 1px solid #ddd;
        }
        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }
        .staff-table thead th {
            position: sticky;
            top: 0;
            background: #d371ff;
            z-index: 2;
        }
        .staff-table th,
        .staff-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        .staff-table td {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        .icon-cell {
            cursor: pointer;
            color: #0015ffff;
            text-decoration: underline;
        }
        .extra-row td{
            background-color: #fffcb2;
        }
        .print-btn-container{
            display: flex;
            flex-direction: row;
            justify-content: flex-end;
            align-items: center;
        }
        .print-btn{
            width: max-content;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            border-radius: 15px;
            border: none;
            font-size: 15px;
            padding: 10px;
            gap: 10px;
            color: #ffff;
            background: #460097;
            cursor: pointer;
        }
        .print-btn a{
           color: #ffff; 
        }
        a{
            text-decoration: none;
            color: #000;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <div class="nav-part"><h3 class="heading">Task Name</h3></div>
            <div class="nav-part"><svg width="25" height="25" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 23.75C2.5 25.875 4.125 27.5 6.25 27.5H23.75C25.875 27.5 27.5 25.875 27.5 23.75V13.75H2.5V23.75ZM23.75 5H21.25V3.75C21.25 3 20.75 2.5 20 2.5C19.25 2.5 18.75 3 18.75 3.75V5H11.25V3.75C11.25 3 10.75 2.5 10 2.5C9.25 2.5 8.75 3 8.75 3.75V5H6.25C4.125 5 2.5 6.625 2.5 8.75V11.25H27.5V8.75C27.5 6.625 25.875 5 23.75 5Z" fill="black"/></svg>
                <span>
                    <?php $date = DateTime::createFromFormat('d-M-y', date('d-M-y'));
                        echo strtolower($date->format('l, F j, Y'));
                    ?>
                </span>
            </div>
            <div class="nav-part"></div>
        </div>
        <div class="card">
            <div class="table-wrapper">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Sr.No</th>
                            <th>Block No.</th>
                            <th>Dept</th>
                            <th>Supervisor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Letter</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                            if($type == 'View'){
                                $sr = 1;

                                $blocksRes = mysqli_query($conn, "
                                    SELECT 
                                        bs.block_no,
                                        bs.schedule_date,
                                        bs.schedule_time,
                                        f.faculty_name,
                                        f.dept_code,
                                        bsv.is_extra
                                    FROM block_supervisor bsv
                                    JOIN block_schedule bs ON bs.id = bsv.block_schedule_id
                                    JOIN faculty f ON f.id = bsv.faculty_id
                                    WHERE bsv.s_id = '$s'
                                    ORDER BY bs.id
                                ");

                            while ($row = mysqli_fetch_assoc($blocksRes)) {
                        ?>
                        <tr <?php if($row['is_extra']) {echo "class='extra-row'";} ?>>
                            <td><?php echo $sr; ?></td>
                            <td><?php if(!$row['is_extra']) {
                                echo $row['block_no'];}else{
                                   echo 'Extra';
                                } ?></td>
                            <td><?php echo $row['dept_code']; ?></td>
                            <td><?php echo $row['faculty_name']; ?></td>
                            <td>
                                <?php echo $row['schedule_date']; ?>
                            </td>
                            <td><?php echo $row['schedule_time']; ?></td>
                            <td class="icon-cell"><a href="">
                                Print
                            </a></td>
                        </tr>
                        <?php
                                $sr++;
                            }
                        }else if ($type === "Save") {

                                /* STEP 1: DELETE PREVIOUS ALLOTMENTS */
                                mysqli_query($conn, "
                                    DELETE bsv
                                    FROM block_supervisor bsv
                                    JOIN block_schedule bs ON bs.id = bsv.block_schedule_id
                                    WHERE bs.s_id = '$s'
                                ");

                                /* STEP 2: FETCH BLOCKS */
                                $blocksRes = mysqli_query($conn, "
                                    SELECT id, block_no, course_code, schedule_date, schedule_time
                                    FROM block_schedule
                                    WHERE s_id = '$s'
                                    ORDER BY id
                                ");

                                $blocks = [];
                                while ($row = mysqli_fetch_assoc($blocksRes)) {
                                    $blocks[] = $row;
                                }

                                if (empty($blocks)) return;

                                /* STEP 3: FETCH FACULTY */
                                $facultyList = [];
                                $facultyRes = mysqli_query($conn, "
                                    SELECT id, faculty_name, dept_code, courses, duties
                                    FROM faculty
                                    WHERE status = 'ON' AND duties > 0
                                    ORDER BY duties DESC
                                ");

                                while ($f = mysqli_fetch_assoc($facultyRes)) {
                                    $facultyList[] = [
                                        'id'             => $f['id'],
                                        'faculty_name'   => $f['faculty_name'],
                                        'dept_code'      => $f['dept_code'],
                                        'courses'        => $f['courses'],
                                        'duties'         => (int)$f['duties'],
                                        'assigned_count' => 0
                                    ];
                                }

                                if (empty($facultyList)) return;

                                $sr = 1;
                                $blockCounter = 0;

                                /* =========================================
                                STEP 4: ASSIGN SUPERVISORS
                                ========================================= */
                                foreach ($blocks as $block) {

                                    if (empty($block['course_code'])) continue;

                                    $blockCourses = array_map('trim', explode(',', $block['course_code']));
                                    $blockCounter++;

                                    /* ==============================
                                    MAIN SUPERVISOR
                                    ============================== */
                                    foreach ($facultyList as $idx => $faculty) {

                                        if ($faculty['assigned_count'] >= $faculty['duties']) continue;

                                        /* ❌ SAME DATE + SAME TIME CHECK */
                                        if (facultyBusy(
                                            $conn,
                                            $faculty['id'],
                                            $block['schedule_date'],
                                            $block['schedule_time'],
                                            $s
                                        )) {
                                            continue;
                                        }

                                        $safe = true;

                                        /* COURSE / DEPT CHECK */
                                        if (!empty($faculty['courses']) && !empty($faculty['dept_code'])) {
                                            $facultyCourses = array_map('trim', explode(',', $faculty['courses']));
                                            foreach ($blockCourses as $c) {
                                                if (
                                                    in_array($c, $facultyCourses) ||
                                                    substr($c, 0, 2) === $faculty['dept_code']
                                                ) {
                                                    // echo "Not Assigned";
                                                    $safe = false;
                                                    break;
                                                }
                                            }
                                        }

                                        // echo "<pre>";print_r($faculty);echo "</pre>";

                                        if ($safe) {

                                            mysqli_query($conn, "
                                                INSERT INTO block_supervisor
                                                (block_schedule_id, faculty_id, s_id, is_extra)
                                                VALUES ({$block['id']}, {$faculty['id']}, '$s', 0)
                                            ");

                                            $facultyList[$idx]['assigned_count']++;

                                            ?>
                                            <tr>
                                                <td><?= $sr++ ?></td>
                                                <td><?= $block['block_no'] ?></td>
                                                <td><?= $faculty['dept_code'] ?></td>
                                                <td><?= $faculty['faculty_name'] ?></td>
                                                <td><?= $block['schedule_date'] ?></td>
                                                <td><?= $block['schedule_time'] ?></td>
                                                <td><a href="">Print</a></td>
                                            </tr>
                                            <?php

                                            /* 🔁 ROTATE FACULTY QUEUE */
                                            $temp = $facultyList[$idx];
                                            unset($facultyList[$idx]);
                                            $facultyList = array_values($facultyList);
                                            $facultyList[] = $temp;

                                            break;
                                        }
                                    }

                                    /* ==============================
                                    EXTRA SUPERVISOR
                                    AFTER EVERY 5 BLOCKS
                                    ============================== */
                                    if ($blockCounter % 5 === 0 || $blockCounter === count($blocks)) {

                                        foreach ($facultyList as $idx => $faculty) {

                                            if ($faculty['assigned_count'] >= $faculty['duties']) continue;

                                            /* ❌ SAME DATE + SAME TIME CHECK */
                                            if (facultyBusy(
                                                $conn,
                                                $faculty['id'],
                                                $block['schedule_date'],
                                                $block['schedule_time'],
                                                $s
                                            )) {
                                                continue;
                                            }

                                            mysqli_query($conn, "
                                                INSERT INTO block_supervisor
                                                (block_schedule_id, faculty_id, s_id, is_extra)
                                                VALUES ({$block['id']}, {$faculty['id']}, '$s', 1)
                                            ");

                                            $facultyList[$idx]['assigned_count']++;

                                            ?>
                                            <tr class="extra-row">
                                                <td><?= $sr++ ?></td>
                                                <td>Extra</td>
                                                <td><?= $faculty['dept_code'] ?></td>
                                                <td><?= $faculty['faculty_name'] ?></td>
                                                <td><?= $block['schedule_date'] ?></td>
                                                <td><?= $block['schedule_time'] ?></td>
                                                <td><a href="">Print</a></td>
                                            </tr>
                                            <?php

                                            /* 🔁 ROTATE FACULTY QUEUE */
                                            $temp = $facultyList[$idx];
                                            unset($facultyList[$idx]);
                                            $facultyList = array_values($facultyList);
                                            $facultyList[] = $temp;

                                            break;
                                        }
                                    }
                                }
                            }
                        ?>
                    </tbody>
                </table>
            </div>
            <?php if(!empty($blocksRes)){ ?>
            <div class="print-btn-container">
                <div></div>
                <button class="print-btn"><a href="./Backend/supervisors_allotment_pdf.php?s=<?php echo $s; ?>">Print Allotment</a></button>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
<script>

</script>
</html>
