<?php
include 'db.php';

// Fetch settings or default values
$settings_res = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_res->fetch_assoc() ?? [];

$total_capital = $settings['total_capital'] ?? 100000;  // default ₹100,000
$my_name = $settings['my_name'] ?? "Your Name";
$copyright_text = $settings['copyright_text'] ?? "© 2025 Your Company";
$theme_color = $settings['theme_color'] ?? "#4bc0c0";  // default teal

// Fetch active trades
$active_trades = $conn->query("SELECT * FROM trades WHERE status='active' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch closed trades
$closed_trades = $conn->query("SELECT * FROM trades WHERE status='closed' ORDER BY close_date DESC")->fetch_all(MYSQLI_ASSOC);

function fetchLTP($symbol) {
    return round(rand(9000, 11000)/100, 2);
}

// Calculate Used Capital and Total PnL
$used_capital = 0;
$total_pnl = 0;

foreach ($active_trades as $trade) {
    $ltp = fetchLTP($trade['stock_symbol']);
    $current_holding = $trade['quantity'] - $trade['closed_quantity'];
    $used_capital += $trade['entry_price'] * $current_holding;

    $booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
    $current_pnl = ($ltp - $trade['entry_price']) * $current_holding;
    $total_pnl += $booked_pnl + $current_pnl;
}

// Calculate ROI
$roi = ($total_capital > 0) ? ($total_pnl / $total_capital) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Swing Trade Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Animated CSS for alert colors */
        .near-sl {
            animation: pulseRed 1.5s infinite;
            background-color: #ffcccc;
        }
        .target-hit {
            animation: pulseGreen 1.5s infinite;
            background-color: #ccffcc;
        }
        @keyframes pulseRed {
            0%, 100% { background-color: #ffcccc; }
            50% { background-color: #ff6666; }
        }
        @keyframes pulseGreen {
            0%, 100% { background-color: #ccffcc; }
            50% { background-color: #66ff66; }
        }
        /* Animated user name */
        .my-name {
            font-weight: bold;
            font-size: 1.2rem;
            color: <?= htmlspecialchars($theme_color) ?>;
            animation: colorPulse 3s infinite;
            text-align: right;
            margin-bottom: 0.5rem;
        }
        @keyframes colorPulse {
            0%, 100% { color: <?= htmlspecialchars($theme_color) ?>; }
            50% { color: #555; }
        }
        /* Capital & PnL summary styles */
        .pnl-positive {
            color: green;
            font-weight: bold;
        }
        .pnl-negative {
            color: red;
            font-weight: bold;
        }
        footer {
            text-align: center;
            margin-top: 40px;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body class="bg-light">

<div class="container my-4">

    <div class="my-name"><?= htmlspecialchars($my_name) ?></div>

    <h1 class="mb-4">Swing Trade Dashboard</h1>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4>Capital & PnL Summary</h4>
            <p>Total Capital: <strong>₹<?= number_format($total_capital, 2) ?></strong></p>
            <p>Used Capital: <strong>₹<?= number_format($used_capital, 2) ?></strong></p>
            <p>ROI: <strong><?= number_format($roi, 2) ?>%</strong></p>
            <p>Total PnL: 
                <strong class="<?= ($total_pnl < 0) ? 'pnl-negative' : 'pnl-positive' ?>">
                    ₹<?= number_format($total_pnl, 2) ?>
                </strong>
            </p>
        </div>
        <div>
            <a href="settings.php" class="btn btn-outline-primary">Settings ⚙️</a>
        </div>
    </div>

    <canvas id="pnlChart" height="100"></canvas>

    <!-- Add Trade Form (omitted here, keep your existing or update as needed) -->

    <!-- Active Trades Table -->
    <!-- (keep your existing table code but remove target3 as previously) -->

    <!-- Closed Trades Table -->
    <!-- (your existing closed trades table code) -->

</div>

<footer><?= htmlspecialchars($copyright_text) ?></footer>

<script>
const ctx = document.getElementById('pnlChart').getContext('2d');
const pnlChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Active Trades'],
        datasets: [{
            label: 'Total PnL',
            data: [<?= $total_pnl ?>],
            backgroundColor: '<?= $theme_color ?>',
            borderColor: '<?= $theme_color ?>',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        animation: {
            duration: 1000,
            easing: 'easeInOutBounce'
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>
