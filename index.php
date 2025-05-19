<?php
include 'db.php';

// Fetch settings or default values
$settings_res = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_res->fetch_assoc() ?? [];

$total_capital = $settings['total_capital'] ?? 100000;
$my_name = $settings['my_name'] ?? "Your Name";
$theme_color = $settings['theme_color'] ?? "#4bc0c0";
$copyright_text = $settings['copyright_text'] ?? "© 2025 Your Company";

// Fetch active trades
$active_trades = $conn->query("SELECT * FROM trades WHERE status='active' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch closed trades
$closed_trades = $conn->query("SELECT * FROM trades WHERE status='closed' ORDER BY close_date DESC")->fetch_all(MYSQLI_ASSOC);

function fetchLTP($symbol) {
    // Demo: random LTP near 100
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
            <a href="settings.php" class="btn btn-outline-primary me-2">Settings ⚙️</a>
            <a href="report.php" class="btn btn-outline-success">Report 📊</a>
        </div>
    </div>

    <canvas id="pnlChart" height="100"></canvas>

    <!-- Add Trade Form -->
    <div class="card p-3 mb-4">
        <h4>Add New Trade</h4>
        <form method="post" action="add_trade.php" class="row g-3">
            <div class="col-md-2">
                <input type="text" name="stock_symbol" class="form-control" placeholder="Symbol" required />
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="entry_price" class="form-control" placeholder="Entry Price" required />
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="stoploss" class="form-control" placeholder="Stoploss" required />
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="target1" class="form-control" placeholder="Target 1" required />
            </div>
            <div class="col-md-2">
                <input type="number" step="0.01" name="target2" class="form-control" placeholder="Target 2" />
            </div>
            <div class="col-md-2">
                <input type="number" name="quantity" class="form-control" placeholder="Quantity (Shares)" min="1" required />
            </div>
            <div class="col-md-2">
                <input type="text" name="added_by" class="form-control" placeholder="Added By" required />
            </div>
            <div class="col-md-2">
                <select name="is_long_term" class="form-select">
                    <option value="0">Swing Trade</option>
                    <option value="1">Long Term</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">Add Trade</button>
            </div>
        </form>
    </div>

    <!-- Active Trades Table -->
    <div class="mb-4">
        <h4>Active Trades</h4>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Entry</th>
                    <th>LTP</th>
                    <th>Stoploss</th>
                    <th>Target 1</th>
                    <th>Target 2</th>
                    <th>Total Shares</th>
                    <th>Closed Shares</th>
                    <th>Current Holding</th>
                    <th>Booked Price</th>
                    <th>Booked PnL</th>
                    <th>Added By</th>
                    <th>Long Term</th>
                    <th>PNL</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($active_trades as $trade):
                    $ltp = fetchLTP($trade['stock_symbol']);
                    $current_holding = $trade['quantity'] - $trade['closed_quantity'];
                    $booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
                    $current_pnl = ($ltp - $trade['entry_price']) * $current_holding;
                    $trade_total_pnl = $booked_pnl + $current_pnl;

                    $near_sl = ($ltp <= $trade['stoploss'] * 1.02) ? 'near-sl' : '';
                    $target_hit = ($ltp >= $trade['target1']) ? 'target-hit' : '';
                    $row_class = '';
                    if ($near_sl) $row_class = 'near-sl';
                    if ($target_hit) $row_class = 'target-hit';
                ?>
                <tr class="<?= $row_class ?>">
                    <td><?= htmlspecialchars($trade['stock_symbol']) ?></td>
                    <td><?= $trade['entry_price'] ?></td>
                    <td><?= $ltp ?></td>
                    <td><?= $trade['stoploss'] ?></td>
                    <td><?= $trade['target1'] ?></td>
                    <td><?= $trade['target2'] ?? '-' ?></td>
                    <td><?= $trade['quantity'] ?></td>
                    <td><?= $trade['closed_quantity'] ?></td>
                    <td><?= $current_holding ?></td>
                    <td><?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?></td>
                    <td><?= number_format($booked_pnl, 2) ?></td>
                    <td><?= htmlspecialchars($trade['added_by']) ?></td>
                    <td><?= $trade['is_long_term'] ? 'Yes' : 'No' ?></td>
                    <td><?= number_format($trade_total_pnl, 2) ?></td>
                    <td><?= $trade['status'] ?></td>
                    <td>
                        <a href="edit_trade.php?id=<?= $trade['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        <a href="close_trade.php?id=<?= $trade['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Close this trade?');">Close</a>
                        <a href="delete_trade.php?id=<?= $trade['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this trade?');">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Closed Trades Table -->
    <div>
        <h4>Closed Trades</h4>
        <table class="table table-bordered table-sm">
            <thead>
                <tr>
                    <th>Symbol</th>
                    <th>Entry</th>
                    <th>Close Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($closed_trades as $trade): ?>
                <tr>
                    <td><?= htmlspecialchars($trade['stock_symbol']) ?></td>
                    <td><?= $trade['entry_price'] ?></td>
                    <td><?= $trade['close_date'] ?? '-' ?></td>
                    <td><?= $trade['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

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
            y: { beginAtZero: true }
        }
    }
});
</script>

</body>
</html>
