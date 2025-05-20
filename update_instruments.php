<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// Fetch api_key and access_token from settings
$settings_res = $conn->query("SELECT api_key, access_token FROM settings LIMIT 1");
if (!$settings_res) {
    die("Failed to fetch settings: " . $conn->error);
}
$settings = $settings_res->fetch_assoc();

if (empty($settings['api_key']) || empty($settings['access_token'])) {
    die("API key or Access token missing in settings. Please login first.");
}

$api_key = $settings['api_key'];
$access_token = $settings['access_token'];

$api_url = 'https://api.kite.trade/instruments';

$headers = [
    "Authorization: token $api_key:$access_token"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);

if ($response === false) {
    die("Curl error: " . curl_error($ch));
}

// Zerodha instruments API returns CSV string — parse it now
$lines = explode("\n", trim($response));

// The first line contains headers
$headers = str_getcsv(array_shift($lines));

// Prepare DB statement
$stmt = $conn->prepare("
    REPLACE INTO instruments 
    (instrument_token, exchange_token, symbol, company_name, last_price, expiry, strike_price, tick_size, lot_size, instrument_type, segment, exchange) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

$conn->begin_transaction();

try {
    foreach ($lines as $line) {
        if (trim($line) === '') continue;
        $data = str_getcsv($line);

        // Map CSV columns by header index
        $instrument_token = isset($data[array_search('instrument_token', $headers)]) ? (int)$data[array_search('instrument_token', $headers)] : null;
        $exchange_token = isset($data[array_search('exchange_token', $headers)]) ? (int)$data[array_search('exchange_token', $headers)] : null;
        $symbol = $data[array_search('tradingsymbol', $headers)] ?? '';
        $company_name = $data[array_search('name', $headers)] ?? $symbol;
        $last_price = isset($data[array_search('last_price', $headers)]) ? floatval($data[array_search('last_price', $headers)]) : 0;
        $expiry_raw = $data[array_search('expiry', $headers)] ?? null;
        $expiry = !empty($expiry_raw) ? date('Y-m-d', strtotime($expiry_raw)) : null;
        $strike_price = isset($data[array_search('strike', $headers)]) && $data[array_search('strike', $headers)] !== '' ? floatval($data[array_search('strike', $headers)]) : null;
        $tick_size = isset($data[array_search('tick_size', $headers)]) && $data[array_search('tick_size', $headers)] !== '' ? floatval($data[array_search('tick_size', $headers)]) : null;
        $lot_size = isset($data[array_search('lot_size', $headers)]) ? (int)$data[array_search('lot_size', $headers)] : 1;
        $instrument_type = $data[array_search('instrument_type', $headers)] ?? '';
        $segment = $data[array_search('segment', $headers)] ?? '';
        $exchange = $data[array_search('exchange', $headers)] ?? '';

        $stmt->bind_param(
            "iissdssdisss",
            $instrument_token,
            $exchange_token,
            $symbol,
            $company_name,
            $last_price,
            $expiry,
            $strike_price,
            $tick_size,
            $lot_size,
            $instrument_type,
            $segment,
            $exchange
        );

        if (!$stmt->execute()) {
            throw new Exception("Error updating symbol $symbol: " . $stmt->error);
        }
    }

    $conn->commit();
    echo "Instruments updated successfully.";
} catch (Exception $e) {
    $conn->rollback();
    die("Transaction failed: " . $e->getMessage());
}
