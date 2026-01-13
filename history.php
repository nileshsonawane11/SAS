<div class="history-list">
                <div class="block-data">
                    <div class="">Sr. No.</div>
                    <div class="courses">
                        Schedule Name
                    </div>
                    <div class="date">Type</div>
                    <div class="time">Timestamp</div>
                </div>
                <?php
                include './Backend/config.php';
                    $schedule_result = mysqli_query($conn,"SELECT * FROM schedule ORDER BY created_at DESC");
                    $count=1;
                    while($schedule_row = mysqli_fetch_assoc($schedule_result)){
                        echo '<div class="block-data block-serial" onclick="go_to_schedule(`'.$schedule_row['id'].'`)">
                            <div class="blk-no">'.$count.'</div>
                            <div class="courses">
                                <div class="c-list">'.$schedule_row['task_name'].'</div>
                            </div>
                            <div class="date">'.$schedule_row['task_type'].'</div>
                            <div class="time">'.$schedule_row['created_at'].'</div>
                        </div>';
                        $count++;
                    };
                ?>
            </div>