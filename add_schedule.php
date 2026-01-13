<div class="form-container">
    <div class="task-form">
        <div class="inputfield">
            <label for="task">Enter Schedule Name <sup class="required">*</sup></label>
            <input type="text" name="" id="task" required>
        </div>
        <div class="inputfield">
            <label for="task">Schedule Type <sup class="required">*</sup></label>
            <select name="" id="task_type" required>
                <option value="" hidden></option>
                <option value="PT">Progressive Test</option>
                <option value="Final">Final Exam</option>
            </select>
        </div>
        <div class="inputfield">
            <label for="staff-Duties">Choose Timetable (csv) File <sup class="required">*</sup></label>
            <input type="file" name=""  id="time_table_file" required style="border:none;">
        </div>
        <div class="error" id="err_task_error"></div>
        <div class="inputfield">
            <button class="add-schedule" onclick="add_schedule()">Create Allotement</button>
        </div>
    </div>
</div>