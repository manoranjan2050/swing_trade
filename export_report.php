<?php
include 'db.php';

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}
if (!validateDate($start_date)) $start_date = '';
if (!validateDate($end_date)) $end_date = '';

$where_date = "";
$params = [];
$types = "";

if ($start_date && $end_date) {
    $where_date = " AND trade_date BETWEEN ? AND ? ";
    $params = [$start_date, $end_date];
    $types = "ss";
} elseif ($start_date) {
    $where_date = " AND trade_date >= ? ";
    $params = [$start_date];
    $types = "s";
} elseif ($end_date) {
    $where_date = " AND trade_date <= ? ";
    $params = [$end_date];
    $types = "s";
}

$stmt = $conn->prepare("SELECT stock_symbol, entry_price, stoploss, target1, target2, quantity, closed_quantity, booked_price, added_by, status, trade_date FROM trades WHERE 1=1 $where_date ORDER BY stock_symbol, trade_date DESC");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
if ($types && $params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="trade_report.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Stock Symbol', 'Entry Price', 'Stoploss', 'Target 1', 'Target 2', 'Quantity', 'Closed Quantity', 'Booked Price', 'Added By', 'Status', 'Trade Date']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['stock_symbol'],
        $row['entry_price'],
        $row['stoploss'],
        $row['target1'],
        $row['target2'],
        $row['quantity'],
        $row['closed_quantity'],
        $row['booked_price'],
        $row['added_by'],
        $row['status'],
        $row['trade_date']
    ]);
}
fclose($output);
exit;
