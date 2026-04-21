<?php
session_start();
include("../config/db.php");

$user_id = $_SESSION['user_id'];
$date = date("Y-m-d");


$userQuery = $conn->prepare("SELECT username, email, created_at FROM users WHERE id=?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();

$res = $userQuery->get_result();
$userData = $res ? $res->fetch_assoc() : [];


$sql = "
SELECT h.*, IFNULL(l.status, 0) as status
FROM habits h
LEFT JOIN habit_logs l 
ON h.id = l.habit_id AND l.date = ?
WHERE h.user_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $date, $user_id);
$stmt->execute();

$result = $stmt->get_result();

$habits = [];

/* MAIN STREAK */
function getMainStreak($conn, $user_id) {
    $streak = 0;
    $currentDate = date("Y-m-d");

    while (true) {

        $totalQuery = $conn->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id=?");
        $totalQuery->bind_param("i", $user_id);
        $totalQuery->execute();
        $totalHabits = $totalQuery->get_result()->fetch_assoc()['total'];

        if ($totalHabits == 0) break;

        $doneQuery = $conn->prepare("
            SELECT COUNT(*) as done 
            FROM habit_logs l
            JOIN habits h ON l.habit_id = h.id
            WHERE h.user_id=? AND l.date=? AND l.status=1
        ");
        $doneQuery->bind_param("is", $user_id, $currentDate);
        $doneQuery->execute();
        $doneCount = $doneQuery->get_result()->fetch_assoc()['done'];

        if ($doneCount == $totalHabits) {
            $streak++;
            $currentDate = date("Y-m-d", strtotime($currentDate . " -1 day"));
        } else {
            break;
        }
    }

    return $streak;
}

/* CONSISTENCY */
function getConsistency($conn, $user_id) {

    $totalQuery = $conn->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id=?");
    $totalQuery->bind_param("i", $user_id);
    $totalQuery->execute();
    $totalHabits = $totalQuery->get_result()->fetch_assoc()['total'];

    if ($totalHabits == 0) return 0;

    $dateQuery = $conn->prepare("SELECT MIN(created_at) as start FROM habits WHERE user_id=?");
    $dateQuery->bind_param("i", $user_id);
    $dateQuery->execute();
    $startDate = $dateQuery->get_result()->fetch_assoc()['start'];

    if (!$startDate) return 0;

    $start = new DateTime($startDate);
    $today = new DateTime();

    $days = $start->diff($today)->days + 1;
    $expected = $days * $totalHabits;

    $doneQuery = $conn->prepare("
        SELECT COUNT(*) as done
        FROM habit_logs l
        JOIN habits h ON l.habit_id = h.id
        WHERE h.user_id=? AND l.status=1
    ");
    $doneQuery->bind_param("i", $user_id);
    $doneQuery->execute();
    $done = $doneQuery->get_result()->fetch_assoc()['done'];

    if ($expected == 0) return 0;

    return round(($done / $expected) * 100);
}

/* PERFORMANCE */
function getPerformance($conn, $user_id) {

    $today = new DateTime();

    $startRecent = (clone $today)->modify("-6 days")->format("Y-m-d");
    $endRecent = $today->format("Y-m-d");

    $startPrev = (clone $today)->modify("-13 days")->format("Y-m-d");
    $endPrev = (clone $today)->modify("-7 days")->format("Y-m-d");

    $totalQuery = $conn->prepare("SELECT COUNT(*) as total FROM habits WHERE user_id=?");
    $totalQuery->bind_param("i", $user_id);
    $totalQuery->execute();
    $totalHabits = $totalQuery->get_result()->fetch_assoc()['total'];

    if ($totalHabits == 0) return "No Data";

    function calcRange($conn, $user_id, $start, $end, $totalHabits) {

        $days = (new DateTime($start))->diff(new DateTime($end))->days + 1;
        $expected = $days * $totalHabits;

        $query = $conn->prepare("
            SELECT COUNT(*) as done
            FROM habit_logs l
            JOIN habits h ON l.habit_id = h.id
            WHERE h.user_id=? AND l.status=1 AND l.date BETWEEN ? AND ?
        ");
        $query->bind_param("iss", $user_id, $start, $end);
        $query->execute();
        $done = $query->get_result()->fetch_assoc()['done'];

        if ($expected == 0) return 0;

        return ($done / $expected) * 100;
    }

    $recent = calcRange($conn, $user_id, $startRecent, $endRecent, $totalHabits);
    $previous = calcRange($conn, $user_id, $startPrev, $endPrev, $totalHabits);

    if ($recent > $previous + 5) return "Improving 📈";
    if ($recent < $previous - 5) return "Declining 📉";

    return "Stable ➖";
}


$weeklyQuery = $conn->prepare("
    SELECT DATE(l.date) as day, COUNT(*) as completed
    FROM habit_logs l
    JOIN habits h ON l.habit_id = h.id
    WHERE h.user_id=? AND l.status=1
    AND l.date >= CURDATE() - INTERVAL 6 DAY
    GROUP BY day
");
$weeklyQuery->bind_param("i", $user_id);
$weeklyQuery->execute();

$resultWeekly = $weeklyQuery->get_result();

$dataMap = [];

while ($row = $resultWeekly->fetch_assoc()) {
    $dataMap[$row['day']] = $row['completed'];
}

$weekly = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date("Y-m-d", strtotime("-$i days"));

    $weekly[] = [
        "day" => $d,
        "completed" => isset($dataMap[$d]) ? $dataMap[$d] : 0
    ];
}

/* HABIT loop*/
while ($row = $result->fetch_assoc()) {

    $habit_id = $row['id'];
    $streak = 0;
    $currentDate = date("Y-m-d");

    while (true) {
        $check = $conn->prepare("
            SELECT status FROM habit_logs 
            WHERE habit_id=? AND date=?
        ");
        $check->bind_param("is", $habit_id, $currentDate);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $data = $res->fetch_assoc();

            if ($data['status'] == 1) {
                $streak++;
                $currentDate = date("Y-m-d", strtotime($currentDate . " -1 day"));
            } else {
                break;
            }
        } else {
            break;
        }
    }

    $row['streak'] = $streak;
    $habits[] = $row;
}


echo json_encode([
    "username" => $userData['username'] ?? "",
    "email" => $userData['email'] ?? "",
    "created_at" => $userData['created_at'] ?? "",
    "habits" => $habits,
    "main_streak" => getMainStreak($conn, $user_id),
    "consistency" => getConsistency($conn, $user_id),
    "performance" => getPerformance($conn, $user_id),
    "weekly" => $weekly
]);
?>