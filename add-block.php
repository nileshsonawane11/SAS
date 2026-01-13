<?php
    include './Backend/config.php';
    $block_id = $_GET['b'] ?? '';
    $row = '';

    if(!empty($block_id)){
        $row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM blocks WHERE id = '$block_id'"));
    }

    $block_no = $row['block_no'] ?? '';
    $place = $row['place'] ?? '';
    $capacity = $row['capacity'] ?? '';
    $double_sit = $row['double_sit'] ?? '';
?>
<div class="form-container">
    <div class="staff-form">
        <h3 class="staff-form-heading"><?php echo (empty($row)) ? 'Add' : 'Update'; ?> Block</h3>
        <div class="staff-form-body">
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-name">Block NO. <sup class="required">*</sup></label>
                    <input type="text" name="" id="block-no" value="<?php echo $block_no; ?>" required>
                </div>
                <div class="inputfield">
                    <label for="staff-course">Palce</label>
                    <input type="text" name="" id="dept" value="<?php echo $place; ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="inputfield">
                    <label for="staff-Duties">Capacity</label>
                    <input type="number" name="" id="block-capacity" value="<?php echo $capacity; ?>" required>
                </div>
                <div class="inputfield">
                    <label for="staff-status">Double Sitting Enable ? <sup class="required">*</sup></label>
                    <select name="" id="double_sit" required>
                        <option value="" hidden>Select Mode</option>
                        <option value="YES" <?php echo ($double_sit == 'Yes') ? "Selected" : ''; ?>>YES</option>
                        <option value="NO" <?php echo ($double_sit == 'No') ? "Selected" : ''; ?>>NO</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="error" id="err_block_form"></div>
        <div class="staff-from-footer">
            <button class="Add_block" onclick="<?php echo (empty($row)) ? 'Add_block('.$block_id.')' : 'Update_block('.$block_id.')'; ?>"><?php echo (empty($row)) ? 'Add' : 'Update'; ?></button>
        </div>
        <?php if(empty($row)){ ?>
        <div class="staff-from-footer">
            <button class="Add_block import_block_btn">Import File</button>
        </div>
        <?php } ?>
    </div>
</div>

<dialog id="block_dialog">
    <form action="" class="staff_dialog_form">
        <div class="inputfield">
            <label for="staff-Duties">Choose (xls,csv) File <sup class="required">*</sup></label>
            <input type="file" name=""  id="block_file" required style="border:none;">
        </div>
        <div class="error" id="err_block_file"></div>
        <div class="task">
            <button type="submit" class="Add_staff Add_block_file_btn">Add</button>
            <button type="button" class="Add_staff cancel_btn" onclick="document.querySelector('#block_dialog').close();">Cancel</button>
        </div>
    </form>
</dialog>