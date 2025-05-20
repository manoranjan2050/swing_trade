<?php
include 'db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid trade ID.");
}

// Fetch trade details
$stmt = $conn->prepare("SELECT t.*, i.company_name FROM trades t LEFT JOIN instruments i ON t.stock_symbol = i.symbol WHERE t.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trade = $result->fetch_assoc();

if (!$trade) {
    die("Trade not found.");
}

// Map trade type
$trade_types = [
    0 => 'Swing Trade',
    1 => 'Long Term',
    2 => 'MSI'
];
$trade_type_str = $trade_types[$trade['is_long_term']] ?? 'Unknown';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Trade Info - <?= htmlspecialchars($trade['stock_symbol']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container my-4">
    <h1>Trade Details for <?= htmlspecialchars($trade['stock_symbol']) ?></h1>
    <a href="index.php" class="btn btn-secondary mb-3">Back to Dashboard</a>

    <table class="table table-bordered">
        <tr><th>Symbol</th><td><?= htmlspecialchars($trade['stock_symbol']) ?></td></tr>
        <tr><th>Company</th><td><?= htmlspecialchars($trade['company_name'] ?? '-') ?></td></tr>
        <tr><th>Added By</th><td><?= htmlspecialchars($trade['added_by']) ?></td></tr>
        <tr><th>Broker</th><td><?= htmlspecialchars($trade['broker_name'] ?? '-') ?></td></tr>
        <tr><th>Remark</th><td><?= htmlspecialchars($trade['remark'] ?? '-') ?></td></tr>
        <tr><th>Trade Type</th><td><?= htmlspecialchars($trade_type_str) ?></td></tr>
        <tr><th>Total Quantity</th><td><?= $trade['quantity'] ?></td></tr>
        <tr><th>Closed Quantity</th><td><?= $trade['closed_quantity'] ?></td></tr>
        <tr><th>Booked Price</th><td><?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?></td></tr>
        <tr><th>Entry Price</th><td><?= $trade['entry_price'] ?></td></tr>
        <tr><th>Stoploss</th><td><?= $trade['stoploss'] ?></td></tr>
        <tr><th>Target 1</th><td><?= $trade['target1'] ?></td></tr>
        <tr><th>Target 2</th><td><?= $trade['target2'] ?? '-' ?></td></tr>
        <tr><th>Why Holding</th><td><?= htmlspecialchars($trade['remark_hold'] ?? '-') ?></td></tr>
    </table>

    <!-- Optional: embed TradingView widget here if you want -->

</div>
</body>
</html>
