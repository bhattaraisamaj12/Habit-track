<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');
header("Content-Type: application/json");

include("../config/db.php");
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION['user_id'];
$currentTime = date("H:i:s");

$reminders = [];

/*START REMINDER*/
$startSql = "
SELECT r.id, h.id as habit_id, h.name
FROM reminders r
JOIN habits h ON r.habit_id = h.id
WHERE r.user_id = ?
AND r.is_active = 1
AND (r.last_triggered IS NULL OR r.last_triggered != CURDATE())
AND TIME(r.reminder_time) BETWEEN 
    SUBTIME(?, '00:01:00') 
AND 
    ADDTIME(?, '00:01:00')
";

$startStmt = $conn->prepare($startSql);

if (!$startStmt) {
    echo json_encode(["error" => "Start SQL failed", "details" => $conn->error]);
    exit;
}

$startStmt->bind_param("iss", $user_id, $currentTime, $currentTime);
$startStmt->execute();
$startStmt->bind_result($rid, $habit_id, $name);

$idsToUpdate = [];

while ($startStmt->fetch()) {

    $reminders[] = [
        "id" => "start_" . $rid,
        "type" => "start",
        "habit_id" => $habit_id,
        "message" => "⏰ Time to start: " . $name
    ];

    $idsToUpdate[] = $rid;
}

$startStmt->close();

if (!empty($idsToUpdate)) {

    $update = $conn->prepare("UPDATE reminders SET last_triggered = CURDATE() WHERE id = ?");

    if ($update) {
        foreach ($idsToUpdate as $id) {
            $update->bind_param("i", $id);
            $update->execute();
        }
        $update->close();
    }
}

/* END REMINDER */
$endSql = "
SELECT id as habit_id, name
FROM habits
WHERE user_id = ?
AND TIME(end_time) BETWEEN 
    SUBTIME(?, '00:01:00') 
AND 
    ADDTIME(?, '00:01:00')
";

$endStmt = $conn->prepare($endSql);

if (!$endStmt) {
    echo json_encode(["error" => "End SQL failed", "details" => $conn->error]);
    exit;
}

$endStmt->bind_param("iss", $user_id, $currentTime, $currentTime);
$endStmt->execute();
$endStmt->bind_result($habit_id, $name);

while ($endStmt->fetch()) {
    $reminders[] = [
        "id" => "end_" . $habit_id,
        "type" => "end",
        "habit_id" => $habit_id,
        "message" => "⏳ Did you complete: " . $name . "?"
    ];
}

$endStmt->close();

echo json_encode($reminders);
exit;
?>