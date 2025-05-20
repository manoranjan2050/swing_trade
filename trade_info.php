<?php
include 'db.php';

// Check if trade ID is provided in the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Trade ID is missing.");
}

$trade_id = $_GET['id'];

// Fetch the trade details
$stmt = $conn->prepare("SELECT * FROM trades WHERE id = ?");
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$trade = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trade) {
    die("Trade not found.");
}

// Fetch the instrument details (symbol and company name) for this trade
$symbol = $trade['stock_symbol'];
$stmt = $conn->prepare("SELECT symbol, company_name FROM instruments WHERE symbol = ?");
$stmt->bind_param("s", $symbol);
$stmt->execute();
$instrument = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$instrument) {
    die("Instrument not found.");
}

// Trade details
$company_name = $instrument['company_name'];
$entry_price = $trade['entry_price'];
$stoploss = $trade['stoploss'];
$target1 = $trade['target1'];
$target2 = $trade['target2'];
$quantity = $trade['quantity'];
$closed_quantity = $trade['closed_quantity'];
$broker_name = $trade['broker_name'] ?? 'N/A';
$added_by = $trade['added_by'] ?? 'N/A';
$remark = $trade['remark'] ?? 'N/A';
$remark_hold = $trade['remark_hold'] ?? 'N/A';
$trade_type = $trade['is_long_term'] == 0 ? 'Swing Trade' : ($trade['is_long_term'] == 1 ? 'Long Term' : 'MSI');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Trade Info - <?= htmlspecialchars($company_name) ?> (<?= htmlspecialchars($symbol) ?>)</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://s3.tradingview.com/tv.js"></script>
    
    <style>
        .trade-info-section {
            margin-top: 20px;
        }

        .trade-info-section p {
            font-size: 1.1rem;
        }

        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9rem;
            color: #666;
        }

        .tv-widget {
            height: 450px;
            width: 100%;
        }
    </style>
</head>
<body class="bg-light">

<div class="container my-4">
    <h1 class="mb-4"><?= htmlspecialchars($company_name) ?> (<?= htmlspecialchars($symbol) ?>) Trade Info</h1>

    <div class="trade-info-section">
        <h3>Trade Details</h3>
        <p><strong>Symbol:</strong> <?= htmlspecialchars($symbol) ?></p>
        <p><strong>Company:</strong> <?= htmlspecialchars($company_name) ?></p>
        <p><strong>Added By:</strong> <?= htmlspecialchars($added_by) ?></p>
        <p><strong>Broker:</strong> <?= htmlspecialchars($broker_name) ?></p>
        <p><strong>Remark:</strong> <?= htmlspecialchars($remark) ?></p>
        <p><strong>Trade Type:</strong> <?= $trade_type ?></p>
        <p><strong>Total Quantity:</strong> <?= $quantity ?></p>
        <p><strong>Closed Quantity:</strong> <?= $closed_quantity ?></p>
        <p><strong>Booked Price:</strong> <?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?></p>
        <p><strong>Entry Price:</strong> <?= number_format($entry_price, 2) ?></p>
        <p><strong>Why Holding:</strong> <?= htmlspecialchars($remark_hold) ?></p>
    </div>

    <!-- TradingView Chart -->
    <div class="tv-widget" id="tradingview_chart"></div>

    <footer>&copy; 2025 Trading Blog. All rights reserved.</footer>
</div>

<script>
    var symbol = "<?= htmlspecialchars($symbol) ?>"; // Get the trade symbol dynamically
    var stoploss = <?= $stoploss ?>;
    var entry = <?= $entry_price ?>;
    var target1 = <?= $target1 ?>;
    var target2 = <?= $target2 ?>;

    new TradingView.widget({
        "width": "100%",
        "height": 450,
        "symbol": "NSE:" + symbol,
        "interval": "D",
        "timezone": "Asia/Kolkata",
        "theme": "light",
        "style": "1",
        "toolbar_bg": "#f1f3f6",
        "withdateranges": true,
        "hide_side_toolbar": false,
        "allow_symbol_change": true,
        "details": true,
        "hotlist": true,
        "calendar": true,
        "studies": [],
        "overrides": {},
        "studies_overrides": {},
        "show_popup_button": false,
        "save_image": false
    });
</script>

</body>
</html>
