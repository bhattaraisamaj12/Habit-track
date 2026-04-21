<?php
session_start();
session_destroy();

header("Location: /habit-tracker/Frontend/pages/login.html");
exit();
?>