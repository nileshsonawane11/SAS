<?php
    include './Backend/config.php';
    $slot_id = $_GET['s'] ?? '';
    $row = '';

    if(!empty($slot_id)){
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM exam_slots WHERE id = '$slot_id'"));
    }

    $exam_name = $row['exam_name'] ?? '';
    $mode = $row['mode'] ?? '';
    $start_time = $row['start_time'] ?? '';
    $end_time = $row['end_time'] ?? '';
?>
<div class="form-container">
    <div class="staff-form">
        <h3 class="staff-form-heading"><?php echo (empty($row)) ? 'Add' : 'Update'; ?> Staff</h3>
        <div class="staff-form-body">
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-name">Exam type <sup class="required">*</sup></label>
                    <select name="" id="exam-name" required>
                        <option value="" hidden>Select Exam</option>
                        <option value="Final" <?php echo ($mode == 'Final') ? "Selected" : ''; ?>>Final Exam</option>
                        <option value="PT" <?php echo ($mode == 'PT') ? "Selected" : ''; ?>>Progressive Test</option>
                    </select>
                </div>
                <div class="inputfield">
                    <label for="staff-course">Mode</label>
                    <select name="" id="slot-mode" required>
                        <option value="" hidden>Select Mode</option>
                        <option value="Online" <?php echo ($mode == 'Online') ? "Selected" : ''; ?>>Online</option>
                        <option value="Offline" <?php echo ($mode == 'Offline') ? "Selected" : ''; ?>>Offline</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-Branch">Start Time <sup class="required">*</sup></label>
                    <input type="time" name="" id="slot-start-time" value="<?php echo $start_time; ?>">
                </div>
                <div class="inputfield">
                    <label for="staff-Branch">End Time <sup class="required">*</sup></label>
                    <input type="time" name="" id="slot-end-time" value="<?php echo $end_time; ?>">
                </div>
            </div>
        </div>
        <div class="error" id="err_slot_form"></div>
        <div class="staff-from-footer">
            <button class="Add_staff Add_slot_btn" onclick="<?php echo (empty($row)) ? 'Add_slot('.$slot_id.')' : 'Update_slot('.$slot_id.')'; ?>"><?php echo (empty($row)) ? 'Add' : 'Update'; ?></button>
        </div>
        <?php if(empty($row)){ ?>
        <div class="staff-from-footer">
            <button class="Add_staff import_slot_btn">Import File</button>
        </div>
        <?php } ?>
    </div>
</div>

<dialog id="slot_dialog">
    <form action="" class="staff_dialog_form">
        <div class="inputfield">
            <label for="staff-Duties">Choose (xls,csv) File <sup class="required">*</sup></label>
            <input type="file" name=""  id="slot_file" required style="border:none;">
        </div>
        <div class="error" id="err_slot_file"></div>
        <div class="task">
            <button type="submit" class="Add_staff Add_slot_file_btn">Add</button>
            <button type="button" class="Add_staff cancel_btn" onclick="document.querySelector('#slot_dialog').close();">Cancel</button>
        </div>
    </form>
</dialog>