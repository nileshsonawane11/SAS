<?php
    // require './Backend/auth_guard.php';
    include './Backend/config.php';
    $staff_id = $_GET['s'] ?? '';
    $row = '';

    if(!empty($staff_id)){
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM faculty WHERE id = '$staff_id'"));
    }

    $dept_code = $row['dept_code'] ?? '';
    $faculty_name = $row['faculty_name'] ?? '';
    $courses = $row['courses'] ?? '';
    $duties = $row['duties'] ?? '';
    $status = $row['status'] ?? '';
    $role = $row['role'] ?? '';
?>
<div class="form-container">
    <div class="staff-form">
        <h3 class="staff-form-heading"><?php echo (empty($row)) ? 'Add' : 'Update'; ?> Staff</h3>
        <div class="staff-form-body">
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-name"><span></span><br>Staff Name <sup class="required">*</sup></label>
                    <input type="text" name="" id="staff-name" value="<?php echo $faculty_name; ?>" required>
                </div>
                <div class="inputfield">
                    <label for="staff-course">Course Code<br><span>(Ex.AB123,CD456,..)</span></label>
                    <textarea type="text" name="" id="staff-course" required><?php echo $courses; ?></textarea>
                </div>
            </div>
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-Branch">Branch <sup class="required">*</sup></label>
                    <select name="" id="staff-Branch" required>
                        <option value="" hidden>Select Branch</option>
                        <option value="AE" <?php echo ($dept_code == 'AE') ? "Selected" : ''; ?>>AE</option>
                        <option value="CE" <?php echo ($dept_code == 'CE') ? "Selected" : ''; ?>>CE</option>
                        <option value="CM" <?php echo ($dept_code == 'CM') ? "Selected" : ''; ?>>CM</option>
                        <option value="DD" <?php echo ($dept_code == 'DD') ? "Selected" : ''; ?>>DDGM</option>
                        <option value="EE" <?php echo ($dept_code == 'EE') ? "Selected" : ''; ?>>EE</option>
                        <option value="EL" <?php echo ($dept_code == 'EL') ? "Selected" : ''; ?>>EL</option>
                        <option value="IF" <?php echo ($dept_code == 'IF') ? "Selected" : ''; ?>>IF</option>
                        <option value="ID" <?php echo ($dept_code == 'ID') ? "Selected" : ''; ?>>IDD</option>
                        <option value="ME" <?php echo ($dept_code == 'ME') ? "Selected" : ''; ?>>ME</option>
                        <option value="MK" <?php echo ($dept_code == 'MK') ? "Selected" : ''; ?>>MK</option>
                        <option value="SC" <?php echo ($dept_code == 'SC') ? "Selected" : ''; ?>>SC</option>
                        <option value="PO" <?php echo ($dept_code == 'PO') ? "Selected" : ''; ?>>PO</option>
                    </select>
                </div>
                <div class="inputfield">
                    <label for="staff-role">Role</label>
                    <select name="" id="staff-role" required>
                        <option value="" hidden>Select Role</option>
                        <option value="TS" <?php echo ($role == 'TS') ? "Selected" : ''; ?>>Teaching</option>
                        <option value="NTS" <?php echo ($role == 'NTS') ? "Selected" : ''; ?>>Non-Teaching</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-Duties">Duties <sup class="required">*</sup></label>
                    <input type="number" name="" id="staff-duties" value="<?php echo $duties; ?>" required>
                </div>
                <div class="inputfield">
                    <label for="staff-status">Status <sup class="required">*</sup></label>
                    <select name="" id="staff-status" required>
                        <option value="" hidden>Select Status</option>
                        <option value="ON" <?php echo ($status == 'ON') ? "Selected" : ''; ?>>On</option>
                        <option value="OFF" <?php echo ($status == 'OFF') ? "Selected" : ''; ?>>Off</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="error" id="err_staff_form"></div>
        <div class="staff-from-footer">
            <button class="Add_staff Add_staff_btn" onclick="<?php echo (empty($row)) ? 'Add_staff('.$staff_id.')' : 'Update_staff('.$staff_id.')'; ?>"><?php echo (empty($row)) ? 'Add' : 'Update'; ?></button>
        </div>
        <?php if(empty($row)){ ?>
        <div class="staff-from-footer">
            <button class="Add_staff import_btn">Import File</button>
        </div>
        <?php } ?>
    </div>
</div>

<dialog id="staff_dialog">
    <form action="" class="staff_dialog_form">
        <div class="inputfield">
            <label for="staff-Duties">Choose (xls,csv) File <sup class="required">*</sup></label>
            <input type="file" name=""  id="staff_file" required style="border:none;">
        </div>
        <div class="error" id="err_staff_file"></div>
        <div class="task">
            <button type="submit" class="Add_staff Add_staff_file_btn">Add</button>
            <button type="button" class="Add_staff cancel_btn" onclick="document.querySelector('#staff_dialog').close();">Cancel</button>
        </div>
    </form>
</dialog>