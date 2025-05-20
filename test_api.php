<?php
session_start();
include 'db.php'; // your DB connection if needed

$api_key = 'YOUR_API_KEY'; // your Kite Connect API key

// Get user_id and access_token from session or DB
if (!isset($_SESSION['kite_user_id'])) {
    die("User ID not found. Please login first.");
}

$user_id = $_SESSION['kite_user_id'];

// Example: fetch access token from DB (adjust if stored in session)
$stmt = $conn->prepare("SELECT access_token FROM kite_tokens WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($access_token);
$stmt->fetch();
$stmt->close();

if (!$access_token) {
    die("Access token not found for user. Please login.");
}

// Make API request to fetch user profile
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.kite.trade/user/profile",
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
    echo "cURL Error: $err";
    exit;
}

$data = json_decode($response, true);

if (isset($data['data'])) {
    echo "<h3>API Call Successful! User Profile:</h3>";
    echo "<pre>" . print_r($data['data'], true) . "</pre>";
} else {
    echo "<h3>API Call Failed or Token Invalid:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
}
