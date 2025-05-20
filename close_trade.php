<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("Trade ID missing.");
}

$trade_id = intval($_GET['id']);
if ($trade_id <= 0) {
    die("Invalid trade ID.");
}

// Set close date to today
$close_date = date('Y-m-d');

// Update trade to closed status
$stmt = $conn->prepare("UPDATE trades SET status = 'closed', close_date = ? WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("si", $close_date, $trade_id);

if ($stmt->execute()) {
    $stmt->close();
    // Redirect back with success message
    header("Location: index.php?msg=Trade+closed+successfully");
    exit();
} else {
    $stmt->close();
    die("Error closing trade: " . $stmt->error);
}
