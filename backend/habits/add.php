<?php
session_start();
include("../config/db.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $habit = $_POST['habit_name'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $user_id = $_SESSION['user_id'];

    // insert habit
    $sql = "INSERT INTO habits (user_id, name, start_time, end_time) 
            VALUES ('$user_id', '$habit', '$start', '$end')";

    if (mysqli_query($conn, $sql)) {
        $habit_id = $conn->insert_id;

        $reminder_time = date("H:i:s", strtotime($start . " -5 minutes"));
        $reminderSql = $conn->prepare("
            INSERT INTO reminders (habit_id, reminder_time, is_active, user_id)
            VALUES (?, ?, 1, ?)
        ");

        $reminderSql->bind_param("isi", $habit_id, $reminder_time, $user_id);
        $reminderSql->execute();

        echo "success";

    } else {
        echo "error: " . mysqli_error($conn);
    }
}
?>