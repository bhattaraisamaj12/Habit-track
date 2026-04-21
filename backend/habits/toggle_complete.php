<?php
include("../config/db.php");

$habit_id = $_POST['habit_id'] ?? null;
$date = date("Y-m-d");

if (!$habit_id) {
    die("No habit ID");
}

// check if exists
$sql = "SELECT status FROM habit_logs WHERE habit_id=? AND date=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $habit_id, $date);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $newStatus = $row['status'] == 1 ? 0 : 1;

    $update = "UPDATE habit_logs SET status=? WHERE habit_id=? AND date=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("iis", $newStatus, $habit_id, $date);
    $stmt->execute();
} else {
    $insert = "INSERT INTO habit_logs (habit_id, date, status) VALUES (?, ?, 1)";
    $stmt = $conn->prepare($insert);
    $stmt->bind_param("is", $habit_id, $date);
    $stmt->execute();
}

echo "success";
?>