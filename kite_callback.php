<?php
include 'db.php';

$api_key = '3aezx4s0krknq3ot';
$api_secret = '77kabrbdz1076hgwui7r77w6qcdq5hqs';

if (!isset($_GET['request_token'])) {
    die('Request token missing.');
}

$request_token = $_GET['request_token'];
$checksum = hash('sha256', $api_key . $request_token . $api_secret);

$post_data = [
    'api_key' => $api_key,
    'request_token' => $request_token,
    'checksum' => $checksum,
];

$curl = curl_init('https://api.kite.trade/session/token');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);

if (isset($data['data']['access_token'])) {
    $access_token = $data['data']['access_token'];

    // UPSERT: Insert or update settings row with id=1
    $stmt = $conn->prepare("
        INSERT INTO settings (id, api_key, access_token) VALUES (1, ?, ?)
        ON DUPLICATE KEY UPDATE api_key=VALUES(api_key), access_token=VALUES(access_token)
    ");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ss", $api_key, $access_token);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    echo "Login successful! New access token saved. You can now <a href='index.php'>go to dashboard</a>.";
} else {
    echo "Login failed: " . ($data['message'] ?? 'Unknown error');
}
