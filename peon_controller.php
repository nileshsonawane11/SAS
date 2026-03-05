<?php
include './Backend/auth_guard.php';
include './Backend/config.php';
$owner = $user_data['_id'] ?? 0;

// ADD
if ($_GET['action'] == "add") {

    $name   = $_POST['name'];
    $dept   = $_POST['department'];
    $rate   = $_POST['rate'];
    $duty   = $_POST['duty'];

    mysqli_query($conn,
        "INSERT INTO peons (name, dept, rate, duties, Created_by, status)
         VALUES ('$name','$dept','$rate','$duty', '$owner', 1)");

    echo "Added Successfully";
}

// DELETE
if ($_GET['action'] == "delete") {
    $id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM peons WHERE id=$id AND Created_by = '$owner'");
    echo "Deleted Successfully";
}

// GET SINGLE FOR EDIT
if ($_GET['action'] == "get") {
    $id = $_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM peons WHERE id=$id AND Created_by = '$owner'");
    echo json_encode(mysqli_fetch_assoc($res));
}

// UPDATE
if ($_GET['action'] == "update") {

    $id     = $_POST['id'];
    $name   = $_POST['name'];
    $dept   = $_POST['department'];
    $rate   = $_POST['rate'];
    $status   = $_POST['status'];
    $duty   = $_POST['duty'];

    mysqli_query($conn,
        "UPDATE peons SET 
            name='$name',
            dept='$dept',
            rate='$rate',
            status ='$status',
            duties='$duty'
         WHERE id=$id AND Created_by = '$owner'");

    echo "Updated Successfully";
}

// SEARCH
if ($_GET['action'] == "search") {
    $q = $_GET['query'];

    $sql = mysqli_query($conn,
        "SELECT * FROM peons 
         WHERE name LIKE '%$q%'
         OR dept LIKE '%$q%' 
         OR duties LIKE '%$q%' 
         AND Created_by = '$owner'
         ORDER BY id DESC");

    $sn = 1;
    while($r = mysqli_fetch_assoc($sql)) {
        echo "
        <tr>
            <td>$sn</td>
            <td>{$r['name']}</td>
            <td>{$r['dept']}</td>
            <td>{$r['rate']}</td>
            <td>{$r['duties']}</td>
            <td>
                <button class='edit-btn' onclick='openEdit({$r['id']})'>Edit</button>
                <button class='delete-btn' onclick='deletePeon({$r['id']})'>Delete</button>
            </td>
        </tr>";
        $sn++;
    }
}
?>