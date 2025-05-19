<?php
include 'db.php';

// Fetch total capital
$capital_result = $conn->query("SELECT total_capital FROM settings LIMIT 1");
$capital = $capital_result->fetch_assoc()['total_capital'] ?? 0;

// Fetch active trades
$active_trades = $conn->query("SELECT * FROM trades WHERE status='active' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch closed trades
$closed_trades = $conn->query("SELECT * FROM trades WHERE status='closed' ORDER BY close_date DESC")->fetch_all(MYSQLI_ASSOC);

function fetchLTP($symbol) {
    // For demo, mock LTP near 100 range, replace with real API call later
    return round(rand(9000, 11000)/100, 2);
}
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
    </style>
</head>
<body class="bg-light">

<div class="container my-4">
    <h1 class="mb-4">Swing Trade Dashboard</h1>

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
                <input type="number" step="0.01" name="target3" class="form-control" placeholder="Target 3" />
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

    <div class="mb-4">
        <h4>Capital & PnL Summary</h4>
        <p>Total Capital: <strong>₹<?= number_format($capital, 2) ?></strong></p>
        <canvas id="pnlChart" height="100"></canvas>
    </div>

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
                    <th>Target 3</th>
                    <th>Total Shares</th>
                    <th>Closed Shares</th>
                    <th>Current Holding</th>
                    <th>Booked Price</th>
                    <th>Added By</th>
                    <th>Long Term</th>
                    <th>PNL</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total_pnl = 0;
                foreach ($active_trades as $trade) {
                    $ltp = fetchLTP($trade['stock_symbol']);
                    $current_holding = $trade['quantity'] - $trade['closed_quantity'];

                    // Booked PnL calculation
                    $booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
                    // Current holding PnL
                    $current_pnl = ($ltp - $trade['entry_price']) * $current_holding;

                    // Total PnL is sum of booked + current
                    $trade_total_pnl = $booked_pnl + $current_pnl;
                    $total_pnl += $trade_total_pnl;

                    // Color logic
                    $near_sl = ($ltp <= $trade['stoploss'] * 1.02) ? 'near-sl' : '';
                    $target_hit = ($ltp >= $trade['target1']) ? 'target-hit' : '';

                    $row_class = '';
                    if ($near_sl) $row_class = 'near-sl';
                    if ($target_hit) $row_class = 'target-hit';

                    echo "<tr class='$row_class'>";
                    echo "<td>{$trade['stock_symbol']}</td>";
                    echo "<td>{$trade['entry_price']}</td>";
                    echo "<td>$ltp</td>";
                    echo "<td>{$trade['stoploss']}</td>";
                    echo "<td>{$trade['target1']}</td>";
                    echo "<td>" . ($trade['target2'] ?? '-') . "</td>";
                    echo "<td>" . ($trade['target3'] ?? '-') . "</td>";
                    echo "<td>{$trade['quantity']}</td>";
                    echo "<td>{$trade['closed_quantity']}</td>";
                    echo "<td>{$current_holding}</td>";
                    echo "<td>" . ($trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-') . "</td>";
                    echo "<td>" . htmlspecialchars($trade['added_by']) . "</td>";
                    echo "<td>" . ($trade['is_long_term'] ? 'Yes' : 'No') . "</td>";
                    echo "<td>" . number_format($trade_total_pnl, 2) . "</td>";
                    echo "<td>{$trade['status']}</td>";
                    echo "<td>
                            <a href='edit_trade.php?id={$trade['id']}' class='btn btn-sm btn-warning'>Edit</a>
                            <a href='close_trade.php?id={$trade['id']}' class='btn btn-sm btn-success' onclick=\"return confirm('Close this trade?');\">Close</a>
                            <a href='delete_trade.php?id={$trade['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Delete this trade?');\">Delete</a>
                          </td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>

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
                <?php
                foreach ($closed_trades as $trade) {
                    echo "<tr>";
                    echo "<td>{$trade['stock_symbol']}</td>";
                    echo "<td>{$trade['entry_price']}</td>";
                    echo "<td>" . ($trade['close_date'] ?? '-') . "</td>";
                    echo "<td>{$trade['status']}</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('pnlChart').getContext('2d');
const pnlChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Active Trades'],
        datasets: [{
            label: 'Total PnL',
            data: [<?= $total_pnl ?>],
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
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
