<?php
session_start();
include("../../backend/config/db.php");

if (!isset($_SESSION['username']) && isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("SELECT username FROM users WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    $_SESSION['username'] = $user['username'] ?? "User";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>

    <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>

<nav class="navbar">
    <div class="nav-left">
        <h2><?php echo htmlspecialchars($_SESSION['username'] ?? "User"); ?>'s Dashboard</h2>
    </div>

    <div class="nav-right">
        <div class="profile" onclick="toggleDropdown()">
            <div class="avatar">
                <?php echo strtoupper($_SESSION['username'][0]); ?>
            </div>

            <div id="dropdown" class="dropdown">
                <a href="/habit-tracker/Frontend/pages/profile.html">👤 Profile</a>
                <a href="/habit-tracker/backend/auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container">

    <!-- CARDS -->
    <div class="card-container">
        <div class="card big" id="consistency">
             <div class="consistency-text">
                <h2> consistency</h2>
                <p>Keep going! Maintain your habit streak daily.</p>
            </div>

            <div class="donut">
                <div class="donut-inner">
                    <span id="consistencyValue">0%</span>
                </div>
            </div>

        </div>

    <div class="card" id="mainStreak">
        <h3>Streak</h3>
        <p>🔥 0 days</p>
    </div>

    <div class="card" id="performance">
        <h3>Performance</h3>
        <p>Stable</p>
    </div>

    </div>

    <!-- HABITS -->
    <div class="habits">
        <h2>Your Habits</h2>

        <div id="habitList"></div>

        <form id="habitForm" class="form">
            <input type="text" name="habit_name" placeholder="Habit..." required>

            <div class="time-range">
                <div>
                    <label>From</label>
                    <input type="time" name="start_time">
                </div>

                <div>
                    <label>To</label>
                    <input type="time" name="end_time">
                </div>
            </div>

            <button id="sub" type="submit">Add</button>

        </form>

        <p id="msg"></p>
    </div>

</div>

<script src="../js/dashboard.js"></script>

</body>
</html>