<?php
// require './Backend/auth_guard.php';
include './Backend/config.php';

$setting_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admin_panel LIMIT 1"));
if(empty($setting_row)){
    echo "No Setting Data Found";
    exit;
}
$letter_data = json_decode($setting_row['letter_json'],true);
?>
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
        --border: #e2e8f0;
        --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius: 12px;
        --radius-sm: 8px;
        --transition: all 0.3s ease;
        --success-bg: #ecfdf5;
        --success-text: #065f46;
        --danger: #ef4444;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .py-5 {
        padding: 25px;
        background: #f8fafc;
        min-height: 100vh;
    }

    /* Main Panel Container */
    .panel-box {
        background: var(--card-bg);
        border-radius: var(--radius);
        padding: 30px;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        display: flex;
        flex-direction: column;
        gap: 25px;
    }

    /* Typography */
    .text-center {
        text-align: center;
    }

    .fw-bold {
        font-weight: 700;
    }

    .fw-semibold {
        font-weight: 600;
    }

    .mb-4 {
        margin-bottom: 30px;
    }

    .mb-3 {
        margin-bottom: 20px;
    }

    .mt-4 {
        margin-top: 30px;
    }

    h2 {
        font-size: 28px;
        color: var(--text-dark);
        margin-bottom: 8px;
        font-weight: 700;
    }

    h3 {
        font-size: 22px;
        color: var(--text-dark);
        margin-bottom: 20px;
        font-weight: 600;
    }

    h5 {
        font-size: 18px;
        color: var(--text-dark);
        margin-bottom: 15px;
    }

    p {
        color: var(--text-light);
        font-size: 15px;
        line-height: 1.5;
    }

    /* Section Styling */
    .section {
        background: #f8fafc;
        border-radius: var(--radius);
        padding: 25px;
        border: 1px solid var(--border);
        transition: var(--transition);
    }

    .section:hover {
        box-shadow: var(--shadow);
    }

    .section-head {
        font-size: 20px;
        color: var(--primary);
        font-weight: 600;
        margin-bottom: 25px;
        text-align: center;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border);
    }

    /* Alert Styling */
    .alert {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 20px;
        border-radius: var(--radius-sm);
        font-size: 14px;
        font-weight: 500;
        z-index: 1000;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: var(--shadow-lg);
        animation: slideIn 0.3s ease-out;
    }

    .alert-success {
        background: var(--success-bg);
        color: var(--success-text);
        border: 1px solid #10b981;
    }

    .alert-dismissible {
        padding-right: 40px;
    }

    .btn-close {
        background: none;
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: inherit;
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.7;
        transition: var(--transition);
    }

    .btn-close:hover {
        opacity: 1;
    }

    .d-none {
        display: none;
    }

    /* Form Controls */
    .form-label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        font-size: 15px;
        transition: var(--transition);
        background: white;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.1);
    }

    .form-control::placeholder {
        color: #94a3b8;
    }

    /* Checkbox & Radio */
    .form-check {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    .form-check-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--primary);
    }

    .form-check-label {
        font-size: 15px;
        color: var(--text-dark);
        cursor: pointer;
        user-select: none;
    }

    .form-check-inline {
        display: inline-flex;
        align-items: center;
        margin-right: 20px;
    }

    /* Role Fields Section */
    #roleFields {
        background: white;
        border-radius: var(--radius-sm);
        padding: 20px;
        border: 1px dashed var(--border);
        margin-top: 15px;
        transition: var(--transition);
    }

    #roleFields.hidden {
        display: none;
    }

    /* Buttons */
    .btn {
        padding: 14px 28px;
        border-radius: var(--radius-sm);
        font-size: 15px;
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

    .btn-primary:active {
        transform: translateY(0);
    }

    .px-4 {
        padding-left: 24px;
        padding-right: 24px;
    }

    /* Document Editor Styling */
    .page1, .page2 {
        width: 100%;
        max-width: 210mm;
        min-height: 297mm;
        margin: 30px auto;
        background: white;
        padding: 25mm 20mm;
        box-shadow: var(--shadow-lg);
        border-radius: 8px;
        position: relative;
        font-family: "Times New Roman", serif;
    }

    .dom-alert {
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 12px;
        color: var(--danger);
        font-style: italic;
        background: rgba(239, 68, 68, 0.1);
        padding: 5px 10px;
        border-radius: 4px;
    }

    .editable {
        outline: none;
        background: rgba(248, 250, 252, 0.5);
        border-radius: 4px;
        padding: 8px 12px;
        margin: 5px 0;
        min-height: 20px;
        transition: var(--transition);
        border: 1px dashed transparent;
    }

    .editable:hover,
    .editable:focus {
        background: rgba(124, 58, 237, 0.05);
        border-color: var(--primary-light);
    }

    .header {
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #000;
        margin-bottom: 20px;
    }

    .header h2 {
        font-size: 20px;
        margin: 5px 0;
    }

    .header p {
        margin: 0;
        font-size: 12px;
    }

    .header img {
        width: 120px;
        height: 120px;
        object-fit: contain;
        cursor: pointer;
        transition: var(--transition);
        border-radius: 4px;
        border: 1px solid var(--border);
    }

    .header img:hover {
        transform: scale(1.05);
        border-color: var(--primary);
    }

    .date-ref {
        margin-top: 20px;
        font-size: 14px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .date-ref span {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .content {
        margin-top: 25px;
        font-size: 15px;
        line-height: 1.6;
    }

    .subject {
        margin: 30px 0;
        font-size: 16px;
        font-weight: bold;
    }

    .inline {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 15px 0;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        font-size: 14px;
    }

    table, th, td {
        border: 1px solid #000;
    }

    th, td {
        padding: 8px;
        text-align: center;
    }

    .signature {
        margin-top: 50px;
    }

    .sign-area {
        margin-top: 40px;
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 5px;
    }

    .signature img {
        width: 150px;
        height: auto;
        cursor: pointer;
        transition: var(--transition);
        border-radius: 4px;
        border: 1px solid var(--border);
    }

    .signature img:hover {
        transform: scale(1.05);
        border-color: var(--primary);
    }

    /* Print Styles */
    @media print {
        .py-5 {
            padding: 0;
            background: none;
        }
        
        .panel-box {
            box-shadow: none;
            margin: 0;
        }
        
        .page1, .page2 {
            box-shadow: none;
            margin: 0;
            padding: 0;
            border: none;
        }
        
        .btn,
        .section-head,
        .dom-alert,
        .editable:hover,
        .editable:focus {
            display: none !important;
        }
        
        .editable {
            background: none !important;
            border: none !important;
        }
    }

    /* Responsive Design */
    @media (max-width: 1200px) {
        .page1, .page2 {
            padding: 20mm 15mm;
        }
    }

    @media (max-width: 1024px) {
        .py-5 {
            padding: 20px;
        }
        
        .panel-box {
            padding: 25px;
        }
        
        .section {
            padding: 20px;
        }
        
        h2 {
            font-size: 24px;
        }
        
        h3 {
            font-size: 20px;
        }
    }

    @media (max-width: 768px) {
        .py-5 {
            padding: 15px;
        }
        
        .panel-box {
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .section {
            padding: 15px;
        }
        
        h2 {
            font-size: 22px;
        }
        
        h3 {
            font-size: 18px;
        }
        
        .header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
        
        .header img {
            width: 100px;
            height: 100px;
        }
        
        .date-ref {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }
        
        .inline {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
        
        .btn {
            width: 100%;
        }
        
        table {
            font-size: 12px;
        }
        
        th, td {
            padding: 6px;
        }
    }

    @media (max-width: 480px) {
        .py-5 {
            padding: 10px;
        }
        
        .panel-box {
            padding: 15px;
        }
        
        .section {
            padding: 12px;
        }
        
        h2 {
            font-size: 20px;
        }
        
        h3 {
            font-size: 16px;
        }
        
        .form-check-inline {
            display: block;
            margin-right: 0;
            margin-bottom: 10px;
        }
        
        .sign-area {
            align-items: center;
            text-align: center;
        }
        
        .signature img {
            width: 120px;
        }
        
        .alert {
            left: 10px;
            right: 10px;
            top: 10px;
        }
    }

    /* Animation for alert */
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fadeIn {
        animation: fadeIn 0.3s ease-out;
    }

    /* Scrollbar Styling */
    .py-5::-webkit-scrollbar,
    .panel-box::-webkit-scrollbar {
        width: 6px;
    }

    .py-5::-webkit-scrollbar-track,
    .panel-box::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 3px;
    }

    .py-5::-webkit-scrollbar-thumb,
    .panel-box::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .py-5::-webkit-scrollbar-thumb:hover,
    .panel-box::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Utility Classes */
    .sub {
        font-size: 12px;
        color: var(--text-light);
        font-style: italic;
    }

    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    /* Input Number Styling */
    input[type="number"] {
        -moz-appearance: textfield;
    }

    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    /* Focus styles for accessibility */
    *:focus-visible {
        outline: 2px solid var(--primary);
        outline-offset: 2px;
    }

    /* Loading state for buttons */
    .btn.loading {
        position: relative;
        color: transparent;
    }

    .btn.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        border: 2px solid white;
        border-top-color: transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Tooltip for labels */
    .form-label {
        position: relative;
    }

    .form-label:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 0;
        background: var(--text-dark);
        color: white;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 100;
    }

    /* Highlight required fields */
    .required::after {
        content: " *";
        color: var(--danger);
    }

    /* Responsive table for document */
    @media (max-width: 768px) {
        table {
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }
        
        .page1, .page2 {
            padding: 15mm 10mm;
        }
        
        .editable {
            padding: 6px 8px;
        }
    }
</style>
<div class="py-5">
    <div class="mb-4 text-center">
        <h2 class="fw-bold">Administration Settings</h2>
        <p style="color:#000;margin-top:6px">
            Configure system rules, documents, and operational settings
        </p>
    </div>

    <!-- SUCCESS ALERT (Initially hidden) -->
    <div id="successAlert" class="alert alert-success alert-dismissible fade show d-none" role="alert">
        Settings saved successfully!
        <button type="button" class="btn-close" onclick="hideAlert()"></button>
    </div>

    <div class="panel-box">
        <div class="section section1">
            <h3 class="section-head">Supervisor Allocation Settings</h3>
            <!-- 1 Duties Restriction -->
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="maxDutiesCheck" <?php echo ($setting_row['duties_restriction']) ? 'checked' : ''?>>
                <label class="form-check-label fw-semibold" for="maxDutiesCheck">Duties Restriction (Max Duties)</label>
            </div>

            <!-- 2 Block Capacity -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Block Capacity</label>
                <input type="number" value="<?php echo ($setting_row['block_capacity']) ?? 0 ?>" class="form-control" placeholder="Enter block capacity">
            </div>

            <!-- 3 Strength per reliever -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Strength of a Set for 1 Reliever</label>
                <input type="number" value="<?php echo ($setting_row['reliever']) ?? 0 ?>" class="form-control" placeholder="Enter strength value">
            </div>

            <!-- 4 Extra faculty -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Extra Faculty Per Slot (%)</label>
                <input type="number" min="0" max="100" value="<?php echo ($setting_row['extra_faculty']*100) ?? 0 ?>" class="form-control" placeholder="Enter extra faculty percentage">
            </div>

            <!-- 5 Role restriction -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Restriction of Role</label>
                <div class="form-check">
                    <input class="form-check-input" <?php echo ($setting_row['role_restriction']) ? 'checked' : ''?> type="checkbox" id="roleRestrictionCheck" onchange="toggleRoleFields(this, 'roleFields')">
                    <label class="form-check-label" for="roleRestrictionCheck">
                        Enable Role Restriction (Teaching & Non-Teaching Staff)
                    </label>
                </div>
            </div>

            <!-- Teaching / Non-teaching staff -->
            <div id="roleFields" class="<?php echo ($setting_row['role_restriction']) ? '' : 'hidden'?>">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Teaching Staff (%)</label>
                    <input type="number" min="0" id="teaching" max="100" oninput="syncPercentages(this, document.getElementById('nonTeaching'))"  value="<?php echo ($setting_row['teaching_staff']*100) ?? 0 ?>" class="form-control" placeholder="Teaching staff percentage">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Non-Teaching Staff (%)</label>
                    <input type="number" id="nonTeaching" min="0" max="100" oninput="syncPercentages(this, document.getElementById('teaching'))" value="<?php echo ($setting_row['non_teaching_staff']*100) ?? 0 ?>" class="form-control" placeholder="Non-teaching staff percentage">
                </div>
            </div>

            <!-- 8 Subject restriction -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Subject Restriction</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="subjectRestriction" class="form-check-input" id="subjectOn" <?php echo ($setting_row['sub_restriction']) ? 'checked' : ''?>>
                    <label for="subjectOn" class="form-check-label">On</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="subjectRestriction" class="form-check-input" id="subjectOff" <?php echo (!$setting_row['sub_restriction']) ? 'checked' : ''?>>
                    <label for="subjectOff" class="form-check-label">Off</label>
                </div>
            </div>

            <!-- 9 Department restriction -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Department Restriction</label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="deptRestriction" class="form-check-input" id="deptOn" <?php echo ($setting_row['dept_restriction']) ? 'checked' : ''?>>
                    <label for="deptOn" class="form-check-label">On</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="deptRestriction" class="form-check-input" id="deptOff" <?php echo (!$setting_row['dept_restriction']) ? 'checked' : ''?>>
                    <label for="deptOff" class="form-check-label">Off</label>
                </div>
            </div>

            <!-- 9 Strict duties restriction -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Assign Strict Duties <sub> (Role Restriction may Affect it.)</sub></label><br>
                <div class="form-check form-check-inline">
                    <input type="radio" name="duties_common" class="form-check-input" id="commonOn" <?php echo ($setting_row['strict_duties']) ? 'checked' : ''?>>
                    <label for="deptOn" class="form-check-label">On</label>
                </div>
                <div class="form-check form-check-inline">
                    <input type="radio" name="duties_common" class="form-check-input" id="commonOff" <?php echo (!$setting_row['strict_duties']) ? 'checked' : ''?>>
                    <label for="deptOff" class="form-check-label">Off</label>
                </div>
            </div>
            <div class="text-center mt-4">
                <button id="saveBtn" class="btn btn-primary px-4" onclick="saveSettings()">Save Settings</button>
            </div>
        </div>

        <div class="section section2">
            <h3 class="section-head">Documentation Settings</h3>
            <div class="page1">
                <div class="dom-alert"><sup>*</sup>Changes made in the given document will be applicable everywhere.</div>
                <div class="header">
                    <div>
                        <img  src="./upload/<?= $letter_data['logo'] ?? '' ?>" id="LogoImg" data-key="logo" onclick="uploadImage(this)" style="cursor:pointer;" alt="Logo">
                        <input  type="file"  accept="image/*" id="LogoInput" hidden>
                    </div>
                    <div>
                        <div class="editable" contenteditable="true" data-key="college_name"><?= $letter_data['college_name'] ?? '' ?></div>
                        <div class="editable" contenteditable="true" data-key="section_name"><?= $letter_data['section_name'] ?? '' ?></div>
                        <div class="editable" contenteditable="true" data-key="institute_address"><?= $letter_data['institute_address'] ?? '' ?></div>
                    </div>
                </div>

                <div class="date-ref">
                    <span><div class="date">Date : </div>________</span>
                    <span><div class="ref-no">Outword No. : </div><div class="editable" contenteditable="true" data-key="ref_no"><?= $letter_data['ref_no'] ?? '' ?></div></span>
                </div>
                <div class="editable" contenteditable="true" data-key="order_by"><?= $letter_data['order_by'] ?? '' ?></div>
                 <div class="editable" contenteditable="true" data-key="reference"><?= $letter_data['reference'] ?? '' ?></div>
                <div class="content">
                    
                    <div class="online">
                        To,<br>
                        faculty_name Here,<br>
                        <div class="editable" contenteditable="true" data-key="department">
                            <?= $letter_data['department'] ?? '' ?>
                        </div>
                        <div class="editable" contenteditable="true" data-key="college_address">
                            <?= $letter_data['college_address'] ?? '' ?>
                        </div>
                    </div>

                    <div class="inline">
                        Subject :
                        <div class="subject editable" contenteditable="true" data-key="subject_name">
                            <?= $letter_data['subject_name'] ?? '' ?>
                        </div>
                    </div>

                    <div class="editable" contenteditable="true" data-key="body_para_1">
                        <?= $letter_data['body_para_1'] ?? '' ?>
                    </div>

                    <div class="editable" contenteditable="true" data-key="body_para_2">
                        <?= $letter_data['body_para_2'] ?? '' ?>
                    </div>

                    <div class="editable" contenteditable="true" data-key="body_para_3">
                        <?= $letter_data['body_para_3'] ?? '' ?>
                    </div>

                    <span class="inline">
                        <strong>Show Schedule :</strong>
                        <span class="inline" data-key="show_table">
                            <label>
                                <input type="radio" class="tble-view" name="table-view" value="yes" onchange="toggleScheduleTable(this)" <?php echo ($letter_data['show_table'] == 'yes') ? 'checked' : '' ?>> YES
                            </label>
                            <label>
                                <input type="radio" class="tble-view" name="table-view" value="no" onchange="toggleScheduleTable(this)" <?php echo ($letter_data['show_table'] == 'no') ? 'checked' : '' ?>> NO
                            </label>
                        </span>
                    </span>
                    <table contenteditable="false" id="scheduleTable" style="display:<?php echo ($letter_data['show_table'] == 'no') ? 'none' : 'table' ?>">
                        <thead>
                            <tr>
                                <th>Sr.No</th>
                                <th>Date</th>
                                <th>Slot</th>
                            </tr>
                        </thead>
                        <tbody contenteditable="false">
                            <tr>
                                <td><br></td>
                                <td><br></td>
                                <td><br></td>
                            </tr>
                            <tr>
                                <td><br></td>
                                <td><br></td>
                                <td><br></td>
                            </tr>
                            <tr>
                                <td><br></td>
                                <td><br></td>
                                <td><br></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="signature">
                        <div class="editable" contenteditable="true" data-key="closing_text"><?= $letter_data['closing_text'] ?? '' ?></div>
                        <span class="sign-area">
                            <img  src="./upload/<?= $letter_data['signature'] ?? '' ?>" id="signatureImg" data-key="signature" onclick="uploadImage(this)" style="cursor:pointer;" alt="Signature">
                            <input  type="file"  accept="image/*" id="signatureInput" hidden>
                            <div class="editable" contenteditable="true" data-key="official_designation"><?= $letter_data['official_designation'] ?? '' ?></div>
                            <div class="editable" contenteditable="true" data-key="off_name"><?= $letter_data['off_name'] ?? '' ?></div>
                            <div class="editable" contenteditable="true" data-key="off_address"><?= $letter_data['off_address'] ?? '' ?></div>
                        </span>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <button class="btn btn-primary px-4" onclick="saveLetter()">Save Document</button>
            </div>
        </div>
        
    </div>
</div>
