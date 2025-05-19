<?php
$servername = "localhost";
$username = "root";    // your mysql username
$password = "Master@2050";        // your mysql password
$dbname = "swing_trading";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
