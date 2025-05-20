<?php
session_start();
include 'db.php';

$api_key = '3aezx4s0krknq3ot';

// For testing, set user_id manually if not in session
if (!isset($_SESSION['kite_user_id'])) {
    $_SESSION['kite_user_id'] = 'ZT2273'; // Replace or set dynamically after login
}

$user_id = $_SESSION['kite_user_id'];

if (!isset($_GET['symbol']) || empty($_GET['symbol'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Stock symbol missing']);
    exit;
}

$symbol = strtoupper(trim($_GET['symbol']));
$kite_symbol = "NSE:" . $symbol;

$stmt = $conn->prepare("SELECT access_token, expires_at FROM kite_tokens WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($access_token, $expires_at);
$stmt->fetch();
$stmt->close();

if (!$access_token || strtotime($expires_at) < time()) {
    http_response_code(401);
    echo json_encode(['error' => 'Access token expired or missing. Please login again.']);
    exit;
}

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.kite.trade/quote?i={$kite_symbol}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "X-Kite-Version: 3",
        "Authorization: token {$api_key}:{$access_token}"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    http_response_code(500);
    echo json_encode(['error' => 'cURL error: ' . $err]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['data'][$kite_symbol]['last_price'])) {
    echo json_encode(['ltp' => $data['data'][$kite_symbol]['last_price']]);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Price not found']);
}
