<?php include "./Backend/config.php"; ?>
<?php
require './Backend/auth_guard.php';
$owner = $user_data['_id'] ?? 0 ;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Committee Management</title>

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
    --shadow: 0 4px 12px rgba(0,0,0,0.08);
    --shadow-lg: 0 10px 20px rgba(0,0,0,0.12);
    --radius: 12px;
    --radius-sm: 8px;
    --transition: all 0.3s ease;
}

/* ------------------ TITLE ------------------ */
h2 {
    margin-bottom: 20px;
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 26px;
    font-weight: 700;
    text-align: center;
}

/* ------------------ FORM ------------------ */
#committeeForm {
    padding: 15px;
    border-radius: 15px;
    box-shadow: 0px 0px 19px -15px rgba(0, 0, 0, 1);
    margin: 25px auto;
    max-width: 600px;
    background: #fff;
}
.pls{
    display: none;
}
#committeeForm input,
#committeeForm textarea,
#editForm select,
#editForm input,
#editForm textarea{
    width: 100%;
    padding: 10px 12px;
    margin: 6px 0;
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    outline: none;
    transition: var(--transition);
    background: #fff;
}

input:focus,
textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.2);
}

/* ------------------ BUTTONS ------------------ */
button {
    padding: 10px 18px;
    border: none;
    background: var(--primary-gradient);
    color: white;
    border-radius: var(--radius-sm);
    cursor: pointer;
    transition: var(--transition);
    font-weight: 600;
    margin-top: 5px;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

/* Buttons inside table */
.delete-btn {
    background: #ef4444;
}

.delete-btn:hover {
    background: #dc2626;
}

.edit-btn {
    background: #22c55e;
}

.edit-btn:hover {
    background: #16a34a;
}

/* ------------------ TABLE ------------------ */
.table-container {
    width: 100%;
    overflow-x: auto;
    margin-top: 20px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

table {
    width: 100%;
    border-collapse: collapse;
    background: var(--card-bg);
}

th, td {
    padding: 14px;
    border-bottom: 1px solid var(--border);
    text-align: left;
    white-space: nowrap;
}

th {
    background: var(--secondary-gradient);
    color: white;
    font-weight: 600;
}

td {
    color: var(--text-dark);
}

tr:hover td {
    background: #fafafa;
}

/* ------------------ SEARCH ------------------ */
#search {
    width: 100%;
    margin-bottom: 15px;
    padding: 10px 12px;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    max-width: 600px;
    display: block;
    margin-left: auto;
    margin-right: auto;
}

/* ------------------ MODAL ------------------ */
#editModal {
    display: none;
    position: fixed;
    top: 0; 
    left: 0;
    width: 100%; 
    height: 100%;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    transition: var(--transition);
    padding: 15px;
    z-index: 1000;
}

#editBox {
    width: 100%;
    max-width: 430px;
    background: rgba(255,255,255,0.95);
    padding: 25px;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    animation: pop 0.25s ease-out;
}

@keyframes pop {
    from { transform: scale(0.9); opacity: 0; }
    to   { transform: scale(1); opacity: 1; }
}

/* ------------------ RESPONSIVE ------------------ */

/* Mobile */
@media (max-width: 480px) {
    h2 {
        font-size: 22px;
    }

    th, td {
        white-space: normal;
        font-size: 14px;
        padding: 10px;
    }

    #committeeForm {
        margin: 10px;
        padding: 12px;
    }

    #editBox {
        max-width: 95%;
        padding: 18px;
    }
}

/* Tablet */
@media (max-width: 768px) {
    .table-container {
        box-shadow: none;
        border-radius: 0;
    }

    #editBox {
        max-width: 80%;
    }
}

</style>


</head>
<body>

<h2>Committee Members</h2>

<!-- Search -->
<input type="text" id="search" onkeyup="searchCommittee()" placeholder="Search...">

<!-- Add Form -->
<form id="committeeForm" onsubmit="addCommittee(event)">
    <input type="text" name="name" placeholder="Name" required><br>
    <input type="text" name="designation" placeholder="Designation" required><br>
    <input type="text" name="department" placeholder="Department" required><br>
    <input type="number" name="rate" placeholder="Rate" required><br>
    <input type="number" name="duty" placeholder="Duties Days" required><br>

    <button type="submit">Add</button>
    <button onclick="window.open('generate_bill.php', '_blank')">Generate Bill</button>
</form>

<!-- Committee Table -->
<table>
    <thead>
        <tr>
            <th>Sr</th>
            <th>Name</th>
            <th>Designation</th>
            <th>Department</th>
            <th>Status</th>
            <th>Rate</th>
            <th>Duties</th>
            <th>Action</th>
        </tr>
    </thead>

    <tbody id="committeeBody">
        <?php
            $res = mysqli_query($conn, "SELECT * FROM committee WHERE Created_by = '$owner' ORDER BY id DESC");
            $sn = 1;
            if(mysqli_num_rows($res) > 0){
                 while($row = mysqli_fetch_assoc($res)) {
                ?>
                <tr id="row<?= $row['id']; ?>">
                    <td><?= $sn++; ?></td>
                    <td><?= $row['member_name']; ?></td>
                    <td><?= $row['designation']; ?></td>
                    <td><?= $row['department']; ?></td>
                    <td><?= ($row['status'] == 1) ? 'On' : 'Off' ?></td>
                    <td><?= $row['rate']; ?></td>
                    <td><?= $row['duty']; ?></td>
                    <td>
                        <button class="edit-btn" onclick="openEdit(<?= $row['id']; ?>)">Edit</button>
                        <button class="delete-btn" onclick="deleteCommittee(<?= $row['id']; ?>)">Delete</button>
                    </td>
                </tr>
            <?php }
            }else{
                echo "<tr colspan='7'><td>No Committee Found...</td></tr>";
            } ?>
    </tbody>
</table>


<!-- EDIT MODAL -->
<div id="editModal">
    <div id="editBox">
        <h3>Edit Member</h3>
        <form id="editForm" onsubmit="updateCommittee(event)">
            <input type="hidden" name="id" id="editId">
            <input type="text" name="name" id="editName" required><br>
            <input type="text" name="designation" id="editDesignation" required><br>
            <input type="text" name="department" id="editDepartment" required><br>
            <select id="committee_status" name="status">
                <option value="1">ON</option>
                <option value="0">OFF</option>
            </select>
            <input type="number" name="rate" id="editRate" required><br>
            <input type="number" name="duty" id="editDuty" required><br>
            <button type="submit">Update</button>
            <button type="button" onclick="closeEdit()">Cancel</button>
        </form>
    </div>
</div>
</body>
</html>