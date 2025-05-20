<?php
session_start();

// Clear all session data
session_unset();
session_destroy();

// Optional: Remove token from DB (if you want to fully revoke locally)
// include 'db.php';
// if (isset($_SESSION['kite_user_id'])) {
//     $user_id = $_SESSION['kite_user_id'];
//     $stmt = $conn->prepare("DELETE FROM kite_tokens WHERE user_id = ?");
//     $stmt->bind_param("s", $user_id);
//     $stmt->execute();
//     $stmt->close();
// }

header("Location: kite_login.php");
exit;
