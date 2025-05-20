<?php
function getValidKiteToken($conn, $user_id) {
    $stmt = $conn->prepare("SELECT access_token, expires_at FROM kite_tokens WHERE user_id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $stmt->bind_result($access_token, $expires_at);
    $stmt->fetch();
    $stmt->close();

    if (!$access_token) return null;

    if (strtotime($expires_at) < time()) {
        // Token expired
        return null;
    }
    return $access_token;
}
