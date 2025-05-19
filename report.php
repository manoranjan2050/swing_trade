<?php
include 'db.php';

// Fetch all trades (active + closed)
$all_trades = $conn->query("SELECT * FROM trades ORDER BY stock_symbol, trade_date DESC")->fetch_all(MYSQLI_ASSOC);

// Aggregate holding shares by stock_symbol (only active trades)
$active_trades = $conn->query("SELECT stock_symbol, SUM(quantity - closed_quantity) AS holding FROM trades WHERE status='active' GROUP BY stock_symbol")->fetch_all(MYSQLI_ASSOC);

$symbols = [];
$holdings = [];
foreach ($active_trades as $trade) {
    $symbols[] = $trade['stock_symbol'];
    $holdings[] = (int)$trade['holding'];
}

// Colors for bars - Chart.js will pick by default but you can specify array for consistency
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Trade & Stock Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: #f9fafb;
        }
        h1 {
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }
        .table-responsive {
            margin-top: 2rem;
        }
        /* Color-coded rows example */
        .holding-positive {
            background-color: #d4edda; /* greenish */
        }
        .holding-zero {
            background-color: #f8f9fa; /* light gray */
        }
        .holding-negative {
            background-color: #f8d7da; /* reddish */
        }
    </style>
</head>
<body>

<div class="container my-4">
    <h1>Trade & Stock Report</h1>

    <div class="mb-4 text-center">
        <a href="index.php" class="btn btn-primary">Back to Dashboard</a>
    </div>

    <div>
        <canvas id="holdingChart" height="100"></canvas>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Symbol</th>
                    <th>Entry Price</th>
                    <th>Stoploss</th>
                    <th>Target 1</th>
                    <th>Target 2</th>
                    <th>Total Quantity</th>
                    <th>Closed Quantity</th>
                    <th>Current Holding</th>
                    <th>Booked Price</th>
                    <th>Status</th>
                    <th>Added By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_trades as $trade): 
                    $current_holding = $trade['quantity'] - $trade['closed_quantity'];
                    $row_class = '';
                    if ($current_holding > 0) $row_class = 'holding-positive';
                    elseif ($current_holding == 0) $row_class = 'holding-zero';
                    else $row_class = 'holding-negative'; // Should not be negative, but just in case
                ?>
                <tr class="<?= $row_class ?>">
                    <td><?= htmlspecialchars($trade['stock_symbol']) ?></td>
                    <td><?= number_format($trade['entry_price'], 2) ?></td>
                    <td><?= number_format($trade['stoploss'], 2) ?></td>
                    <td><?= number_format($trade['target1'], 2) ?></td>
                    <td><?= $trade['target2'] !== null ? number_format($trade['target2'], 2) : '-' ?></td>
                    <td><?= $trade['quantity'] ?></td>
                    <td><?= $trade['closed_quantity'] ?></td>
                    <td><?= $current_holding ?></td>
                    <td><?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?></td>
                    <td><?= htmlspecialchars($trade['status']) ?></td>
                    <td><?= htmlspecialchars($trade['added_by']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
const ctx = document.getElementById('holdingChart').getContext('2d');

const holdingChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($symbols) ?>,
        datasets: [{
            label: 'Current Holding Shares',
            data: <?= json_encode($holdings) ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.7)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1,
            borderRadius: 5,
            hoverBackgroundColor: 'rgba(54, 162, 235, 0.8)',
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true },
            tooltip: { enabled: true }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Shares' },
                ticks: { precision: 0 }
            },
            x: {
                title: { display: true, text: 'Stock Symbol' }
            }
        }
    }
});
</script>

</body>
</html>
