<?php
include 'db.php';

$q = $_GET['q'] ?? '';
$q = trim($q);

header('Content-Type: application/json');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

// Prepare search with wildcard
$like_q = "%$q%";

$stmt = $conn->prepare("SELECT symbol, company_name FROM instruments WHERE (symbol LIKE ? OR company_name LIKE ?) AND exchange = 'NSE' ORDER BY symbol LIMIT 30");
if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param("ss", $like_q, $like_q);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'symbol' => $row['symbol'],
        'company_name' => $row['company_name']
    ];
}

echo json_encode($data);
