<?php
include './Backend/auth_guard.php';
include './Backend/config.php';
$owner = $user_data['_id'] ?? 0;

// ADD
if ($_GET['action'] == "add") {

    $name   = $_POST['name'];
    $deg    = $_POST['designation'];
    $dept   = $_POST['department'];
    $rate   = $_POST['rate'];
    $duty   = $_POST['duty'];

    mysqli_query($conn,
        "INSERT INTO committee (member_name, designation, department, rate, duty, Created_by)
         VALUES ('$name','$deg','$dept','$rate','$duty', '$owner')");

    echo "Added Successfully";
}

// DELETE
if ($_GET['action'] == "delete") {
    $id = $_GET['id'];
    mysqli_query($conn, "DELETE FROM committee WHERE id=$id AND Created_by = '$owner'");
    echo "Deleted Successfully";
}

// GET SINGLE FOR EDIT
if ($_GET['action'] == "get") {
    $id = $_GET['id'];
    $res = mysqli_query($conn, "SELECT * FROM committee WHERE id=$id AND Created_by = '$owner'");
    echo json_encode(mysqli_fetch_assoc($res));
}

// UPDATE
if ($_GET['action'] == "update") {

    $id     = $_POST['id'];
    $name   = $_POST['name'];
    $deg    = $_POST['designation'];
    $dept   = $_POST['department'];
    $status   = $_POST['status'];
    $rate   = $_POST['rate'];
    $duty   = $_POST['duty'];

    mysqli_query($conn,
        "UPDATE committee SET 
            member_name='$name',
            designation='$deg',
            department='$dept',
            status ='$status',
            rate='$rate',
            duty='$duty'
         WHERE id=$id AND Created_by = '$owner'");

    echo "Updated Successfully";
}

// SEARCH
if ($_GET['action'] == "search") {
    $q = $_GET['query'];

    $sql = mysqli_query($conn,
        "SELECT * FROM committee 
         WHERE member_name LIKE '%$q%' 
         OR designation LIKE '%$q%' 
         OR department LIKE '%$q%' 
         OR duty LIKE '%$q%' 
         AND Created_by = '$owner'
         ORDER BY id DESC");

    $sn = 1;
    while($r = mysqli_fetch_assoc($sql)) {
        echo "
        <tr>
            <td>$sn</td>
            <td>{$r['member_name']}</td>
            <td>{$r['designation']}</td>
            <td>{$r['department']}</td>
            <td>{$r['rate']}</td>
            <td>{$r['duty']}</td>
            <td>
                <button class='edit-btn' onclick='openEdit({$r['id']})'>Edit</button>
                <button class='delete-btn' onclick='deleteCommittee({$r['id']})'>Delete</button>
            </td>
        </tr>";
        $sn++;
    }
}
?>