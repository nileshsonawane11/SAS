<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Supervisor Allocation Settings</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .panel-box {
            background: #ffffff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="text-center mb-4 fw-bold">Admin Panel – Supervisor Allocation Settings</h2>

    <!-- SUCCESS ALERT (Initially hidden) -->
    <div id="successAlert" class="alert alert-success alert-dismissible fade show d-none" role="alert">
        Settings saved successfully!
        <button type="button" class="btn-close" onclick="hideAlert()"></button>
    </div>

    <div class="panel-box">

        <!-- 1 Duties Restriction -->
        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="maxDutiesCheck">
            <label class="form-check-label fw-semibold" for="maxDutiesCheck">Duties Restriction (Max Duties)</label>
        </div>

        <!-- 2 Block Capacity -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Block Capacity</label>
            <input type="number" class="form-control" placeholder="Enter block capacity">
        </div>

        <!-- 3 Strength per reliever -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Strength of a Set for 1 Reliever</label>
            <input type="number" class="form-control" placeholder="Enter strength value">
        </div>

        <!-- 4 Extra faculty -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Extra Faculty Per Slot (%)</label>
            <input type="number" class="form-control" placeholder="Enter extra faculty percentage">
        </div>

        <!-- 5 Role restriction -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Restriction of Role</label>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="roleRestrictionCheck">
                <label class="form-check-label" for="roleRestrictionCheck">
                    Enable Role Restriction (TS: 70% & NTS: 30%)
                </label>
            </div>
        </div>

        <!-- Teaching / Non-teaching staff -->
        <div id="roleFields" class="hidden">
            <div class="mb-3">
                <label class="form-label fw-semibold">Teaching Staff (%)</label>
                <input type="number" class="form-control" placeholder="Teaching staff percentage">
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Non-Teaching Staff (%)</label>
                <input type="number" class="form-control" placeholder="Non-teaching staff percentage">
            </div>
        </div>

        <!-- 8 Subject restriction -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Subject Restriction</label><br>
            <div class="form-check form-check-inline">
                <input type="radio" name="subjectRestriction" class="form-check-input" id="subjectOn">
                <label for="subjectOn" class="form-check-label">On</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="subjectRestriction" class="form-check-input" id="subjectOff">
                <label for="subjectOff" class="form-check-label">Off</label>
            </div>
        </div>

        <!-- 9 Department restriction -->
        <div class="mb-3">
            <label class="form-label fw-semibold">Department Restriction</label><br>
            <div class="form-check form-check-inline">
                <input type="radio" name="deptRestriction" class="form-check-input" id="deptOn">
                <label for="deptOn" class="form-check-label">On</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="radio" name="deptRestriction" class="form-check-input" id="deptOff">
                <label for="deptOff" class="form-check-label">Off</label>
            </div>
        </div>

        <!-- Save Button -->
        <div class="text-center mt-4">
            <button id="saveBtn" class="btn btn-primary px-4">Save Settings</button>
        </div>

    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Show/Hide Role Fields
    const roleCheck = document.getElementById("roleRestrictionCheck");
    const roleFields = document.getElementById("roleFields");

    roleCheck.addEventListener("change", function () {
        roleFields.classList.toggle("hidden", !this.checked);
    });

    // Save Button Alert
    document.getElementById("saveBtn").addEventListener("click", function () {
        const alertBox = document.getElementById("successAlert");
        alertBox.classList.remove("d-none");

        // Auto hide after 4 seconds (4000ms)
        setTimeout(() => {
            alertBox.classList.add("d-none");
        }, 4000);
    });

</script>

</body>
</html>
