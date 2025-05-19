<?php
include 'db.php';

// Validate date function
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Get and validate date filters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
if (!validateDate($start_date)) $start_date = '';
if (!validateDate($end_date)) $end_date = '';

// Build WHERE clause for dates
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

// Helper to run prepared queries
function runQuery($conn, $sql, $types = "", $params = []) {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) die("Prepare failed: " . $conn->error);
    if ($types && $params) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch all trades filtered by date
$sql_all_trades = "SELECT * FROM trades WHERE 1=1 $where_date ORDER BY stock_symbol, trade_date DESC";
$all_trades = runQuery($conn, $sql_all_trades, $types, $params);

// Active trades aggregation
$sql_active = "SELECT stock_symbol, SUM(quantity - closed_quantity) AS current_holding, COUNT(*) AS active_count FROM trades WHERE status='active' $where_date GROUP BY stock_symbol";
$active_trades = runQuery($conn, $sql_active, $types, $params);

// Closed trades aggregation
$sql_closed = "SELECT stock_symbol, COUNT(*) AS closed_count FROM trades WHERE status='closed' $where_date GROUP BY stock_symbol";
$closed_trades = runQuery($conn, $sql_closed, $types, $params);

// Build stock summary
$stock_summary = [];
foreach ($active_trades as $active) {
    $stock_summary[$active['stock_symbol']] = [
        'current_holding' => (int)$active['current_holding'],
        'active_count' => (int)$active['active_count'],
        'closed_count' => 0,
        'pnl' => 0.0
    ];
}
foreach ($closed_trades as $closed) {
    if (!isset($stock_summary[$closed['stock_symbol']])) {
        $stock_summary[$closed['stock_symbol']] = [
            'current_holding' => 0,
            'active_count' => 0,
            'closed_count' => (int)$closed['closed_count'],
            'pnl' => 0.0
        ];
    } else {
        $stock_summary[$closed['stock_symbol']]['closed_count'] = (int)$closed['closed_count'];
    }
}

// Mock LTP function (replace with real API if you want)
function getLTP($symbol) {
    return round(rand(9000, 11000)/100, 2);
}

// Calculate PnL per stock
foreach ($all_trades as $trade) {
    $symbol = $trade['stock_symbol'];
    $entry_price = $trade['entry_price'];
    $booked_price = $trade['booked_price'];
    $closed_qty = $trade['closed_quantity'];
    $qty = $trade['quantity'];

    $ltp = getLTP($symbol);

    $current_holding = $qty - $closed_qty;

    $booked_pnl = ($booked_price !== null) ? ($booked_price - $entry_price) * $closed_qty : 0;
    $current_pnl = ($ltp - $entry_price) * $current_holding;

    $total_trade_pnl = $booked_pnl + $current_pnl;

    if (!isset($stock_summary[$symbol]['pnl'])) {
        $stock_summary[$symbol]['pnl'] = 0;
    }
    $stock_summary[$symbol]['pnl'] += $total_trade_pnl;
}

// Prepare data for chart
$symbols = array_keys($stock_summary);
$holdings = [];
foreach ($symbols as $sym) {
    $holdings[] = $stock_summary[$sym]['current_holding'];
}

// Colors cycling
$baseColors = [
    '#4dc9f6', '#f67019', '#f53794', '#537bc4', '#acc236'
];
$backgroundColors = [];
for ($i = 0; $i < count($symbols); $i++) {
    $backgroundColors[] = $baseColors[$i % count($baseColors)];
}

// --- Next-level charts data ---

// Prepare cumulative PnL by date and trade count by date
$pnl_by_date = [];
$trade_count_by_date = [];

foreach ($all_trades as $trade) {
    $date = $trade['trade_date'] ?? '1970-01-01';

    if (!isset($pnl_by_date[$date])) {
        $pnl_by_date[$date] = 0;
    }
    if (!isset($trade_count_by_date[$date])) {
        $trade_count_by_date[$date] = 0;
    }

    $entry_price = $trade['entry_price'];
    $booked_price = $trade['booked_price'];
    $closed_qty = $trade['closed_quantity'];
    $qty = $trade['quantity'];
    $current_holding = $qty - $closed_qty;

    $ltp = getLTP($trade['stock_symbol']);
    $booked_pnl = ($booked_price !== null) ? ($booked_price - $entry_price) * $closed_qty : 0;
    $current_pnl = ($ltp - $entry_price) * $current_holding;

    $total_pnl = $booked_pnl + $current_pnl;
    $pnl_by_date[$date] += $total_pnl;

    $trade_count_by_date[$date]++;
}

// Sort ascending by date
ksort($pnl_by_date);
ksort($trade_count_by_date);

// Prepare arrays for Chart.js
$pnl_dates = array_keys($pnl_by_date);
$pnl_values = [];
$cumulative_pnl = 0;
foreach ($pnl_dates as $d) {
    $cumulative_pnl += $pnl_by_date[$d];
    $pnl_values[] = round($cumulative_pnl, 2);
}

$trade_count_dates = array_keys($trade_count_by_date);
$trade_counts = array_values($trade_count_by_date);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Enhanced Trade & Stock Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f9fafb; }
        h1, h3 { color: #333; }
        h1 { margin-bottom: 1.5rem; text-align: center; }
        .table-responsive { margin-top: 2rem; }
        .pnl-positive { color: green; font-weight: bold; }
        .pnl-negative { color: red; font-weight: bold; }
        canvas { background: #fff; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container my-4">
    <h1>Enhanced Trade & Stock Report</h1>

    <form method="get" class="row g-3 mb-4 justify-content-center align-items-end">
        <div class="col-auto">
            <label for="start_date" class="form-label">Start Date:</label>
            <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="form-control" />
        </div>
        <div class="col-auto">
            <label for="end_date" class="form-label">End Date:</label>
            <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="form-control" />
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="report.php" class="btn btn-secondary">Reset</a>
        </div>
        <div class="col-auto">
            <a href="export_report.php?start_date=<?= urlencode($start_date) ?>&end_date=<?= urlencode($end_date) ?>" class="btn btn-success">Export CSV</a>
        </div>
    </form>

    <div>
        <canvas id="holdingChart" height="100"></canvas>
    </div>

    <div class="table-responsive mt-4">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Stock Symbol</th>
                    <th>Current Holding</th>
                    <th>Active Trades</th>
                    <th>Closed Trades</th>
                    <th>Total PnL (₹)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_summary as $symbol => $data): ?>
                <tr>
                    <td><?= htmlspecialchars($symbol) ?></td>
                    <td><?= $data['current_holding'] ?></td>
                    <td><?= $data['active_count'] ?></td>
                    <td><?= $data['closed_count'] ?></td>
                    <td class="<?= ($data['pnl'] < 0) ? 'pnl-negative' : 'pnl-positive' ?>">
                        <?= number_format($data['pnl'], 2) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h3 class="mt-5">Cumulative PnL Over Time</h3>
    <canvas id="cumulativePnlChart" height="150"></canvas>

    <h3 class="mt-5">Trade Count Over Time</h3>
    <canvas id="tradeCountChart" height="150"></canvas>
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
            backgroundColor: <?= json_encode($backgroundColors) ?>,
            borderColor: <?= json_encode($backgroundColors) ?>,
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

const ctxPnl = document.getElementById('cumulativePnlChart').getContext('2d');
const cumulativePnlChart = new Chart(ctxPnl, {
    type: 'line',
    data: {
        labels: <?= json_encode($pnl_dates) ?>,
        datasets: [{
            label: 'Cumulative PnL (₹)',
            data: <?= json_encode($pnl_values) ?>,
            borderColor: '#4bc0c0',
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            fill: true,
            tension: 0.3,
            pointRadius: 3,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 30,
                    maxTicksLimit: 10
                }
            }
        }
    }
});

const ctxTradeCount = document.getElementById('tradeCountChart').getContext('2d');
const tradeCountChart = new Chart(ctxTradeCount, {
    type: 'bar',
    data: {
        labels: <?= json_encode($trade_count_dates) ?>,
        datasets: [{
            label: 'Trades Opened',
            data: <?= json_encode($trade_counts) ?>,
            backgroundColor: '#f67019',
            borderColor: '#f67019',
            borderWidth: 1,
            borderRadius: 3
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 30,
                    maxTicksLimit: 10
                }
            }
        }
    }
});
</script>

</body>
</html>
