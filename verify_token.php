<?php
include 'db.php';  // Your database connection

// Replace with the user_id you want to check
$user_id = 'ZT2273';

$stmt = $conn->prepare("SELECT access_token, expires_at FROM kite_tokens WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($access_token, $expires_at);

if ($stmt->fetch()) {
    echo "Access Token for user <strong>{$user_id}</strong>:<br>";
    echo "Token: {$access_token}<br>";
    echo "Expires At: {$expires_at}<br>";
} else {
    echo "No token found for user <strong>{$user_id}</strong>.";
}

$stmt->close();
$conn->close();
