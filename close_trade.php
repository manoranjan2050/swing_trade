<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $today = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE trades SET status = 'closed', close_date = ? WHERE id = ?");
    $stmt->bind_param("si", $today, $id);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>
