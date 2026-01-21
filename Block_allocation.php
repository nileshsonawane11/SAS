<?php 
// include './Backend/auth_guard.php';
include './Backend/config.php';
$s = $_GET['s'] ?? '';

$schedule_result = mysqli_query($conn,"SELECT * FROM schedule WHERE id = '$s'");
if(mysqli_num_rows($schedule_result) > 0){
    $schedule_row = mysqli_fetch_assoc($schedule_result);
    $task_name = $schedule_row['task_name'];
    $task_type = $schedule_row['task_type'];

    $block_rows = mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM blocks"));

    $blocks = [];
    $created_blocks = [];

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

    // echo "<pre>";print_r($blocks);echo "</pre>";

    // echo "<pre>";print_r($block_rows); echo "</pre>";

    $created_blocks_rows = mysqli_fetch_all(mysqli_query($conn,"SELECT * FROM block_schedule WHERE s_id = '$s'"));
    
    foreach($created_blocks_rows as $block){
        $created_blocks[] = $block[1];
    }

    $slot_result =  mysqli_query($conn,"SELECT * FROM exam_slots WHERE exam_name = '$task_type'");

    // echo "<pre>";print_r($created_blocks_rows); echo "</pre>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $task_name; ?></title>
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
            gap: 10px;
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
            background-color: rgba(0, 255, 140, 0.12);
        }
        .input-form{
            position: absolute;
            bottom: 0;
            width: 100%;
            display: flex;
            flex-direction: column;
            transform: translate(0px, 0px);
            align-items: center;
            transition: 0.3s ease-in-out all;
        }
        .input-form.active{
            transform: translate(0px, 435px);
        }
        .data-form{
            width: 100%;
            background: #fff;
        }
        .error{
            color: red;
            font-family: fangsong;
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
            transform: rotate(0deg);
            transition: 0.3s ease-in-out all;
        }
        .indicator svg.active{
            transform: rotate(180deg);
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
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <div class="nav-part"><h3 class="heading"><?php echo $task_name; ?></h3></div>
            <div class="nav-part"><svg width="25" height="25" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M2.5 23.75C2.5 25.875 4.125 27.5 6.25 27.5H23.75C25.875 27.5 27.5 25.875 27.5 23.75V13.75H2.5V23.75ZM23.75 5H21.25V3.75C21.25 3 20.75 2.5 20 2.5C19.25 2.5 18.75 3 18.75 3.75V5H11.25V3.75C11.25 3 10.75 2.5 10 2.5C9.25 2.5 8.75 3 8.75 3.75V5H6.25C4.125 5 2.5 6.625 2.5 8.75V11.25H27.5V8.75C27.5 6.625 25.875 5 23.75 5Z" fill="black"/></svg>
                <span>
                    <?php $date = DateTime::createFromFormat('d-M-y', date('d-M-y'));
                        echo strtolower($date->format('l, F j, Y'));
                    ?>
                </span>
            </div>
            <div class="nav-part"><button class="see-allot">View</button><button class="allot-btn">Save</button></div>
        </div>
        <div class="card">
            <div class="block-data">
                <div class="">Block No</div>
                <div class="courses">
                    Course
                </div>
                <div class="date">Date</div>
                <div class="time">Time</div>
            </div>
            <?php
                foreach ($created_blocks_rows as $block) {
                    $courses = explode(',', $block[2]);

                    echo '<div class="block-data">
                            <div class="blk-no">' . $block[1] . '</div>
                            <div class="courses">';

                                foreach ($courses as $course) {
                                    echo '<div class="c-list">' . trim($course) . '</div>';
                                }

                    echo    '</div>
                            <div class="date">' . $block[3] . '</div>
                            <div class="time">' . $block[4] . '</div>
                        </div>';
                }
            ?>

        </div>


        <div class="input-form">
            <div class="indicator">
                <svg width="20" height="14" viewBox="0 0 25 19" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11.4698 18.2113C11.5845 18.3778 11.7379 18.514 11.9169 18.6081C12.0959 18.7021 12.2951 18.7513 12.4973 18.7513C12.6995 18.7513 12.8987 18.7021 13.0777 18.6081C13.2567 18.514 13.4102 18.3778 13.5248 18.2113L24.7748 1.96125C24.905 1.77382 24.9814 1.55429 24.9956 1.32651C25.0098 1.09873 24.9613 0.871404 24.8554 0.669243C24.7495 0.467081 24.5902 0.297811 24.3949 0.179823C24.1995 0.0618351 23.9756 -0.00035793 23.7473 1.54957e-06H1.24733C1.01963 0.000942061 0.7965 0.0639348 0.601928 0.182205C0.407356 0.300476 0.248705 0.469549 0.143038 0.671243C0.0373702 0.872937 -0.0113165 1.09962 0.0022135 1.32692C0.0157434 1.55421 0.0909781 1.77352 0.219827 1.96125L11.4698 18.2113Z" fill="black"/>
                </svg>
            </div>
            <div class="data-form">
                <div class="staff-form-body">
                    <div class="form-row">
                        <div class="inputfield">
                            <label for="staff-name"><span></span><br>Block NO. <sup class="required">*</sup></label>
                            <select name="" id="block_no" required>
                                <option value="" hidden>Select Block</option>
                                <?php  
                                    foreach($blocks as $block){
                                        // if(!(in_array($block,$created_blocks))){
                                            echo "<option value='".$block."'>".$block."</option>";
                                        // }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="inputfield">
                            <label for="staff-course">Course Code <sup class="required">*</sup><br><span>(Ex.AB123,CD456,..)</span></label>
                            <textarea type="text" name="" id="exam-course" required></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="inputfield">
                            <label for="staff-Duties">Date <sup class="required">*</sup></label>
                            <input type="date" name="" id="exam-date" required>
                        </div>
                        <div class="inputfield">
                            <label for="staff-Duties">Time <sup class="required">*</sup></label>
                            <select name="" id="exam-time" required>
                                <option value="" hidden>Select Time</option>
                                <?php  
                                    while($slots = mysqli_fetch_assoc($slot_result)){
                                        // if(!(in_array($block,$created_blocks))){
                                            echo "<option value='".$slots['start_time']." - ".$slots['end_time']."'>".$slots['start_time']." - ".$slots['end_time']."</option>";
                                        // }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="error" id="err_block_error"></div>
                    <div class="staff-from-footer">
                        <button class="Add_block" onclick="addBlockSchedule()">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
<script>
    let dropdown_btn = document.querySelector('.indicator');
    let Schedule_id = '<?php echo $s; ?>';
    const gradients = [
        '#ff000030',
        '#ff00cb30',
        '#007eff30',
        '#00ff8c30',
        '#ffeb0030',
        '#000dff30'
    ];

    dropdown_btn.addEventListener('click',()=>{
        document.querySelector('.input-form').classList.toggle('active');
        document.querySelector('.indicator svg').classList.toggle('active');
    });

    // document.querySelectorAll('.c-list').forEach(item => {
    //     item.style.background = gradients[Math.floor(Math.random() * gradients.length)];
    // });

    function addBlockSchedule() {
        const data = {
            s_id : Schedule_id,
            block_no: document.getElementById('block_no').value,
            course_code: document.getElementById('exam-course').value.trim(),
            date: document.getElementById('exam-date').value,
            time: document.getElementById('exam-time').value
        };

        fetch('./Backend/add_block_schedule.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(res => {
            console.log(res)
            if (res.status !== 200) {
                document.getElementById(`err_${res.field}`).innerText = res.message;
                return;
            }
            location.reload();
        })
        .catch(err => console.error(err));
    }

    let see_btn = document.querySelector('.see-allot');
    let allote_btn = document.querySelector('.allot-btn');

    see_btn.addEventListener('click',()=>{
        window.location.href = "./allotment_table.php?type="+see_btn.innerText+"&s="+Schedule_id;
    });

    allote_btn.addEventListener('click',()=>{
        window.location.href = "./allotment_table.php?type="+allote_btn.innerText+"&s="+Schedule_id;
    });

</script>
</html>
<?php }else{
    echo "No Schedule Found..!";
} ?>