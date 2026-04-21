<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_SESSION['user_id'])) {
        echo "unauthorized";
        exit();
    }

    $id = $_POST['id'];
    $user_id = $_SESSION['user_id'];

    // Ensure user deletes only their own habits
    $sql = "DELETE FROM habits WHERE id='$id' AND user_id='$user_id'";

    if (mysqli_query($conn, $sql)) {
        echo "success";
    } else {
        echo "error: " . mysqli_error($conn);
    }
}
?>