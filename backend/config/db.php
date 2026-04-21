<?php
$host = "hostname";
$user = "username";
$password = "password";
$database = "databasename";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>