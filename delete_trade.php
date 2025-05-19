<?php
include 'db.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM trades WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

header("Location: index.php");
exit();
?>
