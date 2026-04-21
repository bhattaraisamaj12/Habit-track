<?php
include("../config/db.php");
session_start();

$user_id = $_SESSION['user_id'];
$username = $_POST['username'];

$sql = "UPDATE users SET username=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $username, $user_id);

if ($stmt->execute()) {

    // UPDATE SESSION
    $_SESSION['username'] = $username;

    echo "success";

} else {
    echo "error";
}
?>