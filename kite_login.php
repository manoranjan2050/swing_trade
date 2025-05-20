<?php
session_start();

$api_key = '3aezx4s0krknq3ot';  // Replace with your Kite Connect API key
$redirect_uri = 'https://tradingblog.in/mytrade/kite_callback.php';  // Your redirect URI registered in Zerodha app

// Construct login URL
$login_url = "https://kite.zerodha.com/connect/login?v=3&api_key={$api_key}&redirect_uri=" . urlencode($redirect_uri);

header("Location: $login_url");
exit;
