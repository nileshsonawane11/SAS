<?php
// require './Backend/auth_guard.php';
include "./Backend/config.php";

$id = $_GET['s'] ?? '';
if(empty($id)){
    echo "No Faculty Found";
    exit;
}

$staff_result = mysqli_query($conn,"SELECT * FROM faculty WHERE id = $id");
$courses = [];
if(mysqli_num_rows($staff_result) == 0){
    echo "No Faculty Found";
    exit;
}else{
    $staff_data = mysqli_fetch_assoc($staff_result);
    if(!empty($staff_data['courses'])){
        $courses = explode(",", $staff_data['courses']) ?? [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Profile </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --gradient-bg: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
            --menu-bg: #34495E;
            --btn-bg: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            --card-bg: #ffffff;
            --text-primary: #2C3E50;
            --text-secondary: #5D6D7E;
            --border-color: #E8E8E8;
            --status-on: #27AE60;
            --status-off: #E74C3C;
            --edit-bg: #F8F9FA;
            --shadow-light: rgba(0, 0, 0, 0.05);
            --shadow-medium: rgba(0, 0, 0, 0.08);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            color: var(--text-primary);
            line-height: 1.5;
            min-height: 100vh;
        }

        .header {
            background: white;
            color: white;
            padding: 25px 30px;
            box-shadow: 0 4px 20px rgba(52, 73, 94, 0.2);
            position: relative;
            overflow: hidden;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .logo-icon {
            background: var(--gradient-bg);
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .profile-header {
            background: white;
            padding: 30px 0;
            border-radius: 0 0 12px 12px;
            box-shadow: 0 6px 25px var(--shadow-light);
            text-align: center;
            border-bottom: 4px solid rgba(174, 0, 255, 0.1);
        }

        .profile-icon {
            background: var(--gradient-bg);
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 8px 20px rgba(174, 0, 255, 0.25);
            border: 4px solid white;
        }

        .profile-header h1 {
            font-size: 2rem;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 700;
        }

        .profile-header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
        }

        .staff-id {
            background: rgba(174, 0, 255, 0.08);
            color: rgba(174, 0, 255, 0.9);
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.95rem;
            display: inline-block;
            margin-top: 15px;
            border: 1px solid rgba(174, 0, 255, 0.15);
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
            padding: 20px;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 20px var(--shadow-light);
            border-top: 3px solid rgba(174, 0, 255, 0.7);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow-medium);
        }

        .card h3 {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 25px;
            color: var(--menu-bg);
            font-size: 1.25rem;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .card h3 i {
            color: rgba(174, 0, 255, 0.9);
            font-size: 1.1rem;
        }

        .field-group {
            margin-bottom: 20px;
            display: flex;
            flex-direction: column;
        }

        .field-label {
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field-value {
            font-size: 1.05rem;
            color: var(--text-primary);
            padding: 12px 16px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid rgba(174, 0, 255, 0.5);
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .field-value.editable:hover {
            background: var(--edit-bg);
            cursor: pointer;
            border-left-color: rgba(174, 0, 255, 0.8);
        }

        .status-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-toggle {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }

        .status-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .status-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
            pointer-events: none; /* Disable clicks by default */
        }

        .status-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        .edit-mode .status-slider {
            pointer-events: auto; /* Enable clicks in edit mode */
            cursor: pointer;
        }

        input:checked + .status-slider {
            background-color: var(--status-on);
        }

        input:checked + .status-slider:before {
            transform: translateX(30px);
        }

        .status-label {
            font-weight: 600;
            font-size: 1rem;
        }

        .status-on-label {
            color: var(--status-on);
        }

        .status-off-label {
            color: var(--status-off);
        }

        .courses-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }

        .course-code {
            background: rgba(174, 0, 255, 0.08);
            color: rgba(174, 0, 255, 0.9);
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            border: 1px solid rgba(174, 0, 255, 0.2);
            font-family: 'Courier New', monospace;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .course-remove {
            color: rgba(231, 76, 60, 0.8);
            cursor: pointer;
            font-size: 0.8rem;
            padding: 2px 6px;
            border-radius: 50%;
            background: rgba(231, 76, 60, 0.1);
            display: none; /* Hidden by default */
        }

        .edit-mode .course-remove {
            display: inline-flex; /* Show only in edit mode */
            align-items: center;
            justify-content: center;
        }

        .course-remove:hover {
            background: rgba(231, 76, 60, 0.2);
        }

        .add-course-container {
            margin-top: 15px;
            display: none;
        }

        .edit-mode .add-course-container {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .add-course-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid rgba(174, 0, 255, 0.3);
            border-radius: 8px;
            font-size: 0.95rem;
            font-family: 'Courier New', monospace;
        }

        .add-course-input:focus {
            outline: none;
            border-color: rgba(174, 0, 255, 0.7);
            box-shadow: 0 0 0 2px rgba(174, 0, 255, 0.1);
        }

        .add-course-btn {
            background: var(--status-on);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .add-course-btn:hover {
            background: #219653;
        }

        .edit-mode .field-value {
            background: white;
            border: 1px solid rgba(174, 0, 255, 0.3);
            border-left: 3px solid rgba(174, 0, 255, 0.8);
        }

        .edit-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(174, 0, 255, 0.3);
            border-radius: 8px;
            font-size: 1.05rem;
            color: var(--text-primary);
            background: white;
            font-weight: 500;
            font-family: inherit;
        }

        .edit-input:focus {
            outline: none;
            border-color: rgba(174, 0, 255, 0.7);
            box-shadow: 0 0 0 3px rgba(174, 0, 255, 0.1);
        }

        .actions {
            display: flex;
            justify-content: center;
            margin-top: 40px;
            padding-bottom: 30px;
        }

        .edit-btn {
            background: var(--btn-bg);
            color: white;
            padding: 14px 36px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.05rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 5px 15px rgba(174, 0, 255, 0.25);
        }

        .edit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(174, 0, 255, 0.35);
        }

        .edit-btn:active {
            transform: translateY(-1px);
        }

        .edit-btn.cancel {
            background: #95A5A6;
            box-shadow: 0 5px 15px rgba(149, 165, 166, 0.25);
        }

        .edit-btn.cancel:hover {
            background: #7F8C8D;
            box-shadow: 0 8px 20px rgba(149, 165, 166, 0.35);
        }

        .edit-btn.save {
            background: var(--status-on);
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.25);
        }

        .edit-btn.save:hover {
            background: #219653;
            box-shadow: 0 8px 20px rgba(39, 174, 96, 0.35);
        }

        .edit-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        footer {
            text-align: center;
            padding: 25px;
            margin-top: 30px;
            color: var(--text-secondary);
            font-size: 0.9rem;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
        }
        .danger{
            color: red;
            font-size: 15px;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            
            .header-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .profile-header h1 {
                font-size: 1.7rem;
            }
            
            .content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .card {
                padding: 25px;
            }
            
            .edit-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .edit-btn {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }
            
            .add-course-container {
                flex-direction: column;
                align-items: stretch;
            }
        }

        @media (max-width: 480px) {
            .profile-header {
                padding: 30px 20px;
            }
            
            .card {
                padding: 20px;
            }
            
            .field-value {
                font-size: 1rem;
                padding: 10px 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="logo">
                    <span style="color: black;">Staff Profile </span>
                </div>
            </div>
        </div>

        <div class="profile-header">
            <div class="profile-icon">
                <i class="fas fa-user"></i>
            </div>
            <h1><?= $staff_data['faculty_name'] ?? '' ?></h1>
            <p><?= $staff_data['dept_name'] ?? '' ?></p>
        </div>

        <div class="content-grid">
            <!-- Personal Information Card -->
            <div class="card" id="personal-card">
                <h3><i class="fas fa-id-card"></i> Personal Information</h3>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-user"></i> Staff Name <sup class="danger">*</sup>
                    </div>
                    <div id="f_name" class="field-value editable" data-field="staffName"><?= $staff_data['faculty_name'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </div>
                    <div id="f_email" class="field-value editable" data-field="email"><?= $staff_data['email'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-phone"></i> Mobile Number <sup class="danger">*</sup>
                    </div>
                    <div id="f_contact" class="field-value editable" data-field="mobile"><?= $staff_data['mobile'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-id-card"></i> Aadhar Number
                    </div>
                    <div id="f_adhar" class="field-value editable" data-field="aadhar"><?= $staff_data['adhar'] ?? '' ?></div>
                </div>
            </div>

            <!-- Department Information Card -->
            <div class="card" id="department-card">
                <h3><i class="fas fa-building"></i> Department Information</h3>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-university"></i> Department Name
                    </div>
                    <div id="f_dept_name" class="field-value editable" data-field="deptName"><?= $staff_data['dept_name'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-hashtag"></i> Department Code
                    </div>
                    <div id="f_dept_code" class="field-value editable" data-field="deptCode"><?= $staff_data['dept_code'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-user-tag"></i> Role
                    </div>
                    <div class="field-value editable" data-field="role" data-type="select">
                        <select class="edit-input" id="f_role" style="display: none;">
                            <option value="Teaching" <?= $staff_data['role'] === 'TS' ? 'selected' : ''?>>Teaching</option>
                            <option value="Non-Teaching" <?= $staff_data['role'] === 'NTS' ? 'selected' : ''?>>Non-Teaching</option>
                        </select>
                        <span class="display-value">
                            <?php 
                                $role_arr = [
                                    'Teaching' => 'TS',
                                    'Non-Teaching' => 'NTS'
                                ];
                                $role_arr = array_flip($role_arr);
                                echo $role_arr[$staff_data['role']];
                            ?>
                        </span>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-tasks"></i> Duties (Number)
                    </div>
                    <div id="f_duties" class="field-value editable" data-field="duties"><?= $staff_data['duties'] ?? 0 ?></div>
                </div>
            </div>

            <!-- Academic & Financial Information Card -->
            <div class="card" id="academic-card">
                <h3><i class="fas fa-graduation-cap"></i> Academic & Financial</h3>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-book"></i> Course Codes
                    </div>
                    <div class="courses-container" id="coursesContainer">
                        <!-- Course codes will be dynamically added here -->
                    </div>
                    <div class="add-course-container">
                        <input type="text" class="add-course-input" id="newCourseInput" placeholder="Enter course code (e.g., EL235141)">
                        <button class="add-course-btn" id="addCourseBtn">
                            <i class="fas fa-plus"></i> Add
                        </button>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-circle"></i> Status
                    </div>
                    <div class="status-container">
                        <label class="status-toggle">
                            <input type="checkbox" id="statusToggle" <?= $staff_data['status'] == 'ON' ? 'checked' : ''?>>
                            <span class="status-slider"></span>
                        </label>
                        <span class="status-label status-<?= strtolower($staff_data['status']) ?>-label" id="statusLabel"><?= $staff_data['status'] ?></span>
                    </div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-credit-card"></i> Bank Account No.
                    </div>
                    <div id="f_ac_no" class="field-value editable" data-field="account"><?= $staff_data['AC-NO'] ?? '' ?></div>
                </div>
                <div class="field-group">
                    <div class="field-label">
                        <i class="fas fa-sharp fa-light fa-building-columns"></i> IFSC Code
                    </div>
                    <div id="f_ifsc_code" class="field-value editable" data-field="ifsc"><?= $staff_data['IFSC_code'] ?? '' ?></div>
                </div>
            </div>
        </div>

        <div class="actions">
            <button class="edit-btn" id="editProfileBtn">
                <i class="fas fa-edit"></i> Edit Profile
            </button>
            
            <div class="edit-actions" id="editActions" style="display: none;">
                <button class="edit-btn save" id="saveBtn" onclick="save_profile()">
                    <i class="fas fa-check"></i> Save Changes
                </button>
                <button class="edit-btn cancel" id="cancelBtn">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        const STAFF_ID = <?= $id; ?>;
            const editProfileBtn = document.getElementById('editProfileBtn');
            const saveBtn = document.getElementById('saveBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const editActions = document.getElementById('editActions');
            const statusToggle = document.getElementById('statusToggle');
            const statusLabel = document.getElementById('statusLabel');
            const coursesContainer = document.getElementById('coursesContainer');
            const newCourseInput = document.getElementById('newCourseInput');
            const addCourseBtn = document.getElementById('addCourseBtn');
            
            let isEditMode = false;
            let originalValues = {};
            let originalCourses = [];
            let currentCourses = [];
            
            // Initialize with default courses
            const defaultCourses = <?= json_encode($courses) ?? []; ?>;
            currentCourses = [...defaultCourses];
            originalCourses = [...defaultCourses];
            renderCourses();
            
            // Status toggle functionality - only works in edit mode
            statusToggle.addEventListener('change', function() {
                if (isEditMode) {
                    if (this.checked) {
                        statusLabel.textContent = 'ON';
                        statusLabel.className = 'status-label status-on-label';
                    } else {
                        statusLabel.textContent = 'OFF';
                        statusLabel.className = 'status-label status-off-label';
                    }
                } else {
                    // Prevent status change when not in edit mode
                    this.checked = !this.checked;
                }
            });
            
            // Edit profile button click
            editProfileBtn.addEventListener('click', function() {
                if (!isEditMode) {
                    enterEditMode();
                }
            });
            
            // Save button click
            // saveBtn.addEventListener('click', function() {
                
            // });
            
            // Cancel button click
            cancelBtn.addEventListener('click', function() {
                exitEditMode(false);
            });
            
            // Add course button click
            addCourseBtn.addEventListener('click', function() {
                addNewCourse();
            });
            
            // Enter key to add course
            newCourseInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    addNewCourse();
                }
            });
            
            // Enter edit mode
            function enterEditMode() {
                isEditMode = true;
                
                // Store original values
                originalValues = {};
                const editableFields = document.querySelectorAll('.field-value.editable');
                
                editableFields.forEach(field => {
                    const fieldName = field.getAttribute('data-field');
                    originalValues[fieldName] = field.textContent.trim();
                    
                    // Make field editable
                    if (field.getAttribute('data-type') === 'select') {
                        const select = field.querySelector('select');
                        const displayValue = field.querySelector('.display-value');
                        select.style.display = 'block';
                        displayValue.style.display = 'none';
                        // select.value = displayValue.textContent;
                    } else {
                        const currentValue = field.textContent.trim();
                        field.innerHTML = `<input type="text" class="edit-input" value="${currentValue}" data-field="${fieldName}">`;
                    }
                });
                
                // Store original courses
                originalCourses = [...currentCourses];
                
                // Enable status toggle for editing
                document.querySelector('.status-slider').style.pointerEvents = 'auto';
                document.querySelector('.status-slider').style.cursor = 'pointer';
                
                // Add edit mode class to cards
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.add('edit-mode');
                });
                
                // Re-render courses to show remove buttons
                renderCourses();
                
                // Show edit actions, hide edit button
                editProfileBtn.style.display = 'none';
                editActions.style.display = 'flex';
            }
            
            // Exit edit mode
            function exitEditMode(saveChanges) {
                isEditMode = false;
                
                const editableFields = document.querySelectorAll('.field-value.editable');
                
                editableFields.forEach(field => {
                    const fieldName = field.getAttribute('data-field');
                    
                    if (field.getAttribute('data-type') === 'select') {
                        const select = field.querySelector('select');
                        const displayValue = field.querySelector('.display-value');
                        
                        if (saveChanges) {
                            displayValue.textContent = select.value;
                        }
                        
                        select.style.display = 'none';
                        displayValue.style.display = 'inline';
                    } else {
                        const input = field.querySelector('.edit-input');
                        
                        if (saveChanges && input) {
                            field.textContent = input.value;
                        } else {
                            field.textContent = originalValues[fieldName];
                        }
                    }
                });
                
                // Handle courses
                if (!saveChanges) {
                    currentCourses = [...originalCourses];
                }
                
                // Disable status toggle
                document.querySelector('.status-slider').style.pointerEvents = 'none';
                document.querySelector('.status-slider').style.cursor = 'default';
                
                // Remove edit mode class
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('edit-mode');
                });
                
                // Clear course input
                newCourseInput.value = '';
                
                // Re-render courses to hide remove buttons
                renderCourses();
                
                // Show edit button, hide edit actions
                editProfileBtn.style.display = 'flex';
                editActions.style.display = 'none';
            }
            
            // Add new course
            function addNewCourse() {
                const courseCode = newCourseInput.value.trim().toUpperCase();
                
                if (courseCode) {
                    // Basic validation for course code format (starts with letters, followed by numbers)
                    const courseRegex = /^[A-Z]{1,5}\d{1,10}$/;
                    
                    if (!courseRegex.test(courseCode)) {
                        showNotification('Please enter a valid course code (e.g., EL235141)', 'error');
                        return;
                    }
                    
                    // Check if course already exists
                    if (currentCourses.includes(courseCode)) {
                        showNotification('This course code already exists!', 'error');
                        return;
                    }
                    
                    currentCourses.push(courseCode);
                    
                    // 🔹 Update staff profile after rendering
                    updateStaffCourses(currentCourses);

                    renderCourses();
                    newCourseInput.value = '';
                    showNotification(`Course ${courseCode} added!`);
                }
            }
            
            // Remove course
            function removeCourse(courseCode) {
                if (isEditMode) {
                    currentCourses = currentCourses.filter(course => course !== courseCode);

                    // 🔹 Update staff profile after rendering
                    updateStaffCourses(currentCourses);

                    renderCourses();
                    showNotification(`Course ${courseCode} removed!`);
                }
            }
            
            // Render courses
            function renderCourses() {
                coursesContainer.innerHTML = '';
                
                currentCourses.forEach(courseCode => {
                    const courseElement = document.createElement('span');
                    courseElement.className = 'course-code';
                    courseElement.innerHTML = `
                        ${courseCode}
                        <span class="course-remove" onclick="removeCourse('${courseCode}')">
                            <i class="fas fa-times"></i>
                        </span>
                    `;
                    coursesContainer.appendChild(courseElement);
                });
            }
            
            // Make removeCourse function available globally
            window.removeCourse = removeCourse;
            
            // Show notification
            function showNotification(message, type = 'success') {
                // Remove existing notification
                const existingNotification = document.querySelector('.notification');
                if (existingNotification) {
                    existingNotification.remove();
                }
                
                const bgColor = type === 'error' ? 'var(--status-off)' : 'var(--status-on)';
                const icon = type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle';
                
                // Create notification element
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.innerHTML = `
                    <div style="position: fixed; top: 20px; right: 20px; background: ${bgColor}; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000; display: flex; align-items: center; gap: 10px; font-weight: 500;">
                        <i class="fas ${icon}"></i> ${message}
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                // Remove notification after 3 seconds
                setTimeout(() => {
                    notification.remove();
                }, 4000);
            }
            
            // Add click to edit functionality for fields
            document.addEventListener('click', function(e) {
                if (isEditMode && e.target.classList.contains('field-value')) {
                    const input = e.target.querySelector('.edit-input');
                    if (input) input.focus();
                }
            });

            //update dynamic courses on server-side
            function updateStaffCourses(courses) {
                fetch('./Backend/update_staff_courses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        staff_id: STAFF_ID,   // pass from PHP or dataset
                        courses: courses      // array of course codes
                    })
                })
                .then(res => res.json())
                .then(data => {console.log(data)})
            }

            function collectProfileData() {
                const data = {
                    staff_id: STAFF_ID,
                    status: document.querySelector('#statusLabel').textContent.trim(),
                    
                };

                document.querySelectorAll('[data-field]').forEach(el => {
                    const key = el.dataset.field;
                    

                    if (el.tagName === 'INPUT') {
                        data[key] = el.value.trim();
                    } else {
                        if(key == 'role'){
                            data[key] = document.querySelector('#f_role').value.trim();
                        }else{
                            data[key] = el.textContent.trim();
                        }
                    }
                });

                return data;
            }

            //update profile on server-side
            function save_profile(){
                fetch('./Backend/save_profile.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(collectProfileData())
                })
                .then(res => res.json())
                .then(data => {
                    console.log(data)
                    if(data.status == 200){
                        showNotification('Profile updated successfully!');
                        exitEditMode(true);
                    }else{
                        showNotification(data.message, 'error');
                    }
                })
            }
    </script>
</body>
</html>