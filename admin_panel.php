<?php
include './Backend/config.php';

$setting_row = mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admin_panel LIMIT 1"));
if(empty($setting_row)){
    echo "No Setting Data Found";
    exit;
}
$letter_data = json_decode($setting_row['letter_json'],true);
?>
<style>
    /* ==============================
   ROOT VARIABLES
================================ */
:root {
    --primary: #2563eb;
    --primary-dark: #1e40af;
    --success-bg: #ecfdf5;
    --success-text: #065f46;
    --border: #e5e7eb;
    --text-main: #111827;
    --text-muted: #6b7280;
    --bg-soft: #f9fafb;
    --white: #ffffff;
}

.py-5 *{
    box-sizing: border-box;
    margin: 0;
    padding: 0;;
}
/* ==============================
   CARD / PANEL
================================ */
.panel-box {
    background: var(--white);
    padding: 40px;
    display: flex;
    flex-direction: column;
    gap: 25px;
    margin-bottom: 90px;
}
.nav{
    background-color: #ffff;
}
/* ==============================
   TYPOGRAPHY
================================ */
.text-center {
    text-align: center;
}
.section{
    padding: 25px;
    display: flex;
    border-radius: 15px;
    background: #f6f6f6;
    gap: 15px;
    flex-direction: column;
}
.fw-bold {
    font-weight: 700;
}

.mb-4 {
    margin: 30px;
}

.mb-3 {
    margin-bottom: 20px;
}

.mt-4 {
    margin-top: 30px;
}

h2 {
    font-size: 26px;
    margin-bottom: 6px;
}

h5 {
    font-size: 17px;
    margin-bottom: 15px;
    color: var(--text-main);
}

/* ==============================
   ALERTS
================================ */
.alert {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    position: fixed;
    top: 10px;
    right: 10px;
}

.alert-success {
    background: var(--success-bg);
    color: var(--success-text);
    border: 1px solid #10b981;
}

.alert-dismissible .btn-close {
    position: absolute;
    top: 12px;
    right: 14px;
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
}

/* ==============================
   LABELS
================================ */
.form-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-main);
    margin-bottom: 6px;
    display: block;
}

.form-check-label {
    font-size: 14px;
    color: var(--text-main);
}

/* ==============================
   INPUTS
================================ */
.form-control {
    width: 100%;
    padding: 12px 14px;
    border-radius: 8px;
    border: 1px solid var(--border);
    font-size: 14px;
    transition: all 0.25s ease;
}

.form-control::placeholder {
    color: var(--text-muted);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
}

/* ==============================
   CHECKBOX & RADIO
================================ */
.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-check-input {
    width: 16px;
    height: 16px;
    cursor: pointer;
    accent-color: var(--primary);
}

.form-check-inline {
    display: inline-flex;
    align-items: center;
    margin-right: 16px;
}

/* ==============================
   ROLE FIELDS BOX
================================ */
#roleFields {
    background: var(--bg-soft);
    padding: 20px;
    border-radius: 10px;
    border: 1px dashed var(--border);
    margin-top: 15px;
}
/* ==============================
   BUTTONS
================================ */
.btn {
    border-radius: 10px;
    font-size: 15px;
    cursor: pointer;
    transition: 0.25s ease;
}

.btn-primary {
    background: var(--primary);
    color: var(--white);
    border: none;
    padding: 14px 42px;
}

.btn-primary:hover {
    background: var(--primary-dark);
}

/* ==============================
   UTILITIES
================================ */
.hidden,
.d-none {
    display: none;
}

.px-4 {
    padding-left: 24px;
    padding-right: 24px;
}
.section-head{
    text-align: center;
    margin-bottom: 15px;
    color: #ff0000;
}
body {
        font-family: "Times New Roman", serif;
        background: #f5f5f5;
    }

    /* A4 PRINT LAYOUT */
    .page1 {
        width: 100%;
        max-width: 210mm;
        position: relative;
        min-height: 297mm;
        margin: 20px auto;
        background: #fff;
        padding: 25mm 20mm;
        box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }

    .page2 {
        width: 100%;
        max-width: 210mm;
        position: relative;
        min-height: 297mm;
        margin: 20px auto;
        background: #fff;
        padding: 25mm 20mm;
        box-shadow: 0 0 10px rgba(0,0,0,0.15);
    }

    .editable {
        outline: none;
        background-color: #f4f4f485;
        border-radius: 8px;
        padding: 8px;
        margin: 10px 0;
    }

    .header {
        text-align: center;
        display: flex;
        padding-bottom: 30px;
        border-bottom: 2px solid #000;
        align-items: center;
        gap: 10px;
        flex-direction: row;
        justify-content: center;
    }

    .header h2 {
        margin: 5px 0;
        font-size: 19px;
        font-weight: bold;
    }

    .header p {
        margin: 0;
        font-size: 10px;
    }

    .date-ref {
        margin-top: 20px;
        font-size: 14px;
        display: flex;
        flex-direction: row-reverse;
        align-items: center;
        justify-content: space-between;
    }
    .date-ref span{
        display: flex;
        align-items: center;
        flex-direction: row;
        gap: 10px;
    }
    .content {
        margin-top: 25px;
        font-size: 15px;
        line-height: 1.6;
    }

    .subject {
        margin: 40px 0;
        font-size: 16px;
        font-weight: bold;
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
        padding: 6px;
        text-align: center;
    }

    .signature {
        margin-top: 50px;
        text-align: right;
    }

    .signature img,
    .header img {
        width: 120px;
        display: block;
        margin-left: auto;
    }
    .inline{
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 10px 0px;
    }
    .sign-area{
        margin-top: 70px;
        display: flex;
        width: 100%;
        flex-direction: column;
    }
    .sign-area .editable{
        margin: 0;
        border-radius: 0;
    }
    .dom-alert{
        position: absolute;
        top: 10px;
        left: 10px;
        font-size: 15px;
        font-family: math;
        color: red;
        text-transform: capitalize;
    }
    @media print {
        body {
            background: none;
        }
        .page1 {
            box-shadow: none;
            margin: 0;
        }
        .page2 {
            box-shadow: none;
            margin: 0;
        }
    }
/* ==============================
   RESPONSIVE
================================ */
@media (max-width: 768px) {

    .panel-box {
        padding: 25px;
    }

    h2 {
        font-size: 22px;
    }

    .btn-primary {
        width: 100%;
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
                    <span><div class="ref-no">Outword No.: </div><div class="editable" contenteditable="true" data-key="ref_no"><?= $letter_data['ref_no'] ?? '' ?></div></span>
                </div>
                <div class="editable" contenteditable="true" data-key="order_by"><?= $letter_data['order_by'] ?? '' ?></div>
                <span><div class="refe">Reference : </div><div class="editable" contenteditable="true" data-key="reference"><?= $letter_data['reference'] ?? 'Time Table' ?></div></span>
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
