<?php
include 'db.php';

// Fetch settings or defaults
$settings_res = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_res->fetch_assoc() ?? [];

$total_capital = $settings['total_capital'] ?? 100000;
$my_name = $settings['my_name'] ?? "Your Name";
$theme_color = $settings['theme_color'] ?? "#4bc0c0";
$copyright_text = $settings['copyright_text'] ?? "© 2025 Your Company";
$api_key = $settings['api_key'] ?? '';
$access_token = $settings['access_token'] ?? '';

// Fetch active trades
$active_trades = $conn->query("SELECT * FROM trades WHERE status='active' ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch closed trades
$closed_trades = $conn->query("SELECT * FROM trades WHERE status='closed' ORDER BY close_date DESC")->fetch_all(MYSQLI_ASSOC);

// Fetch instruments
$instruments_res = $conn->query("SELECT symbol, company_name, instrument_token FROM instruments");
$instruments = $instruments_res->fetch_all(MYSQLI_ASSOC);

function fetchLTP($conn, $instrument_token, $api_key, $access_token) {
    if (!$instrument_token || !$api_key || !$access_token) return 0;

    $quote_url = "https://api.kite.trade/quote?i=" . urlencode($instrument_token);
    $headers = [
        "Authorization: token $api_key:$access_token"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $quote_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return 0;

    $data = json_decode($response, true);
    if (!isset($data['data'][$instrument_token]['last_price'])) return 0;

    return $data['data'][$instrument_token]['last_price'];
}

// Calculate Used Capital and Total PnL
$used_capital = 0;
$total_pnl = 0;

foreach ($active_trades as $trade) {
    $inst_token = 0;
    foreach ($instruments as $inst) {
        if ($inst['symbol'] === $trade['stock_symbol']) {
            $inst_token = $inst['instrument_token'];
            break;
        }
    }
    $ltp = fetchLTP($conn, $inst_token, $api_key, $access_token);
    if ($ltp == 0) $ltp = $trade['entry_price'];

    $current_holding = $trade['quantity'] - $trade['closed_quantity'];
    $used_capital += $trade['entry_price'] * $current_holding;

    $booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
    $current_pnl = ($ltp - $trade['entry_price']) * $current_holding;
    $total_pnl += $booked_pnl + $current_pnl;
}

$roi = ($total_capital > 0) ? ($total_pnl / $total_capital) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Swing Trade Dashboard</title>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://s3.tradingview.com/tv.js"></script>

<style>
.near-sl { animation: pulseRed 1.5s infinite; background-color: #ffcccc; }
.target-hit { animation: pulseGreen 1.5s infinite; background-color: #ccffcc; }
@keyframes pulseRed { 0%, 100% { background-color: #ffcccc; } 50% { background-color: #ff6666; } }
@keyframes pulseGreen { 0%, 100% { background-color: #ccffcc; } 50% { background-color: #66ff66; } }
.my-name {
    font-weight: bold; font-size: 1.2rem; color: <?= htmlspecialchars($theme_color) ?>;
    animation: colorPulse 3s infinite; text-align: right; margin-bottom: 0.5rem;
}
@keyframes colorPulse {
    0%, 100% { color: <?= htmlspecialchars($theme_color) ?>; }
    50% { color: #555; }
}
.pnl-positive { color: green; font-weight: bold; }
.pnl-negative { color: red; font-weight: bold; }
footer { text-align: center; margin-top: 40px; font-size: 0.9rem; color: #666; }
.select2-container .select2-selection--single { height: 38px; }

/* Action buttons small and inline */
.action-buttons a, .action-buttons button {
    margin-right: 4px;
    padding: 2px 6px;
    font-size: 0.75rem;
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
        <div class="col-md-6">
            <label for="stock_symbol" class="form-label">Symbol</label>
            <select name="stock_symbol" id="stock_symbol" class="form-select" style="width: 100%;" required></select>
        </div>
        <div class="col-md-2">
            <label for="entry_price" class="form-label">Entry Price</label>
            <input type="number" step="0.01" name="entry_price" id="entry_price" class="form-control" placeholder="Entry Price" required />
        </div>
        <div class="col-md-2">
            <label for="stoploss" class="form-label">Stoploss</label>
            <input type="number" step="0.01" name="stoploss" id="stoploss" class="form-control" placeholder="Stoploss" required />
        </div>
        <div class="col-md-2">
            <label for="target1" class="form-label">Target 1</label>
            <input type="number" step="0.01" name="target1" id="target1" class="form-control" placeholder="Target 1" required />
        </div>
        <div class="col-md-2">
            <label for="target2" class="form-label">Target 2</label>
            <input type="number" step="0.01" name="target2" id="target2" class="form-control" placeholder="Target 2" />
        </div>
        <div class="col-md-2">
            <label for="quantity" class="form-label">Quantity (Shares)</label>
            <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Quantity (Shares)" min="1" required />
        </div>
        <div class="col-md-2">
            <label for="added_by" class="form-label">Added By</label>
            <input type="text" name="added_by" id="added_by" class="form-control" placeholder="Added By" required />
        </div>
        <div class="col-md-2">
            <label for="broker_name" class="form-label">Broker Name</label>
            <input type="text" name="broker_name" id="broker_name" class="form-control" placeholder="Broker Name" />
        </div>
        <div class="col-md-2">
            <label for="remark" class="form-label">Remark</label>
            <input type="text" name="remark" id="remark" class="form-control" placeholder="Remark" />
        </div>
        <div class="col-md-2">
            <label for="is_long_term" class="form-label">Trade Type</label>
            <select name="is_long_term" id="is_long_term" class="form-select" required>
                <option value="0">Swing Trade</option>
                <option value="1">Long Term</option>
                <option value="2">MSI</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Add Trade</button>
        </div>
    </form>
</div>

<!-- Trade Type Filter -->
<div class="mb-3">
    <label for="filter_trade_type" class="form-label"><strong>Filter Active Trades by Type:</strong></label>
    <select id="filter_trade_type" class="form-select" style="max-width: 200px;">
        <option value="all" selected>All</option>
        <option value="0">Swing Trade</option>
        <option value="1">Long Term</option>
        <option value="2">MSI</option>
    </select>
</div>

<!-- Active Trades Table -->
<div class="mb-4">
    <h4>Active Trades</h4>
    <table class="table table-striped table-responsive" id="activeTradesTable">
        <thead>
            <tr>
                <th>Symbol</th>
                <th>Company</th>
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
                <th>Broker</th>
                <th>Remark</th>
                <th>Trade Type</th>
                <th>PNL</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($active_trades as $trade):
                $inst_token = 0;
                $company_name = '-';
                foreach ($instruments as $inst) {
                    if ($inst['symbol'] === $trade['stock_symbol']) {
                        $inst_token = $inst['instrument_token'];
                        $company_name = $inst['company_name'];
                        break;
                    }
                }
                $ltp = fetchLTP($conn, $inst_token, $api_key, $access_token);
                if ($ltp == 0) $ltp = $trade['entry_price'];

                $current_holding = $trade['quantity'] - $trade['closed_quantity'];
                $booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
                $current_pnl = ($ltp - $trade['entry_price']) * $current_holding;
                $trade_total_pnl = $booked_pnl + $current_pnl;

                $near_sl = ($ltp <= $trade['stoploss'] * 1.02) ? 'near-sl' : '';
                $target_hit = ($ltp >= $trade['target1']) ? 'target-hit' : '';
                $row_class = '';
                if ($near_sl) $row_class = 'near-sl';
                if ($target_hit) $row_class = 'target-hit';

                // Trade type label
                switch ($trade['is_long_term']) {
                    case 0: $trade_type_str = 'Swing Trade'; break;
                    case 1: $trade_type_str = 'Long Term'; break;
                    case 2: $trade_type_str = 'MSI'; break;
                    default: $trade_type_str = 'Unknown';
                }
            ?>
            <tr class="<?= $row_class ?>" data-trade-type="<?= $trade['is_long_term'] ?>">
                <td><?= htmlspecialchars($trade['stock_symbol']) ?></td>
                <td><?= htmlspecialchars($company_name) ?></td>
                <td><?= $trade['entry_price'] ?></td>
                <td><?= number_format($ltp, 2) ?></td>
                <td><?= $trade['stoploss'] ?></td>
                <td><?= $trade['target1'] ?></td>
                <td><?= $trade['target2'] ?? '-' ?></td>
                <td><?= $trade['quantity'] ?></td>
                <td><?= $trade['closed_quantity'] ?></td>
                <td><?= $current_holding ?></td>
                <td><?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?></td>
                <td><?= number_format($booked_pnl, 2) ?></td>
                <td><?= htmlspecialchars($trade['added_by']) ?></td>
                <td><?= htmlspecialchars($trade['broker_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($trade['remark'] ?? '-') ?></td>
                <td><?= $trade_type_str ?></td>
                <td><?= number_format($trade_total_pnl, 2) ?></td>
                <td><?= $trade['status'] ?></td>
                <td class="action-buttons">
                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#infoModal"
                        data-symbol="<?= htmlspecialchars($trade['stock_symbol']) ?>"
                        data-company="<?= htmlspecialchars($company_name) ?>"
                        data-addedby="<?= htmlspecialchars($trade['added_by']) ?>"
                        data-broker="<?= htmlspecialchars($trade['broker_name'] ?? '-') ?>"
                        data-remark="<?= htmlspecialchars($trade['remark'] ?? '-') ?>"
                        data-trade-type="<?= $trade_type_str ?>"
                        data-quantity="<?= $trade['quantity'] ?>"
                        data-closed="<?= $trade['closed_quantity'] ?>"
                        data-booked-price="<?= $trade['booked_price'] !== null ? number_format($trade['booked_price'], 2) : '-' ?>"
                        data-entry-price="<?= $trade['entry_price'] ?>"
                        data-remark-hold="<?= htmlspecialchars($trade['remark_hold'] ?? '-') ?>"
                        data-stoploss="<?= $trade['stoploss'] ?>"
                        data-target1="<?= $trade['target1'] ?>"
                        data-target2="<?= $trade['target2'] ?>"
                    >Info</button>
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
    <table class="table table-bordered table-sm table-responsive">
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

<!-- Info Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoModalLabel">Trade Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Symbol:</strong> <span id="modalSymbol"></span></p>
        <p><strong>Company:</strong> <span id="modalCompany"></span></p>
        <p><strong>Added By:</strong> <span id="modalAddedBy"></span></p>
        <p><strong>Broker:</strong> <span id="modalBroker"></span></p>
        <p><strong>Remark:</strong> <span id="modalRemark"></span></p>
        <p><strong>Trade Type:</strong> <span id="modalTradeType"></span></p>
        <p><strong>Total Quantity:</strong> <span id="modalQuantity"></span></p>
        <p><strong>Closed Quantity:</strong> <span id="modalClosed"></span></p>
        <p><strong>Booked Price:</strong> <span id="modalBookedPrice"></span></p>
        <p><strong>Entry Price:</strong> <span id="modalEntryPrice"></span></p>
        <p><strong>Why Holding:</strong> <span id="modalRemarkHold"></span></p>

        <div id="tradingview_chart" style="height: 450px; width: 100%; margin-top: 20px;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<footer><?= htmlspecialchars($copyright_text) ?></footer>

<!-- JS libs -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Initialize Select2 for symbol search
    $('#stock_symbol').select2({
        placeholder: "Type symbol or company name",
        allowClear: true,
        ajax: {
            url: 'search_symbols.php',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return { q: params.term };
            },
            processResults: function(data) {
                return {
                    results: data.map(function(item) {
                        return {
                            id: item.symbol,
                            text: item.symbol + " - " + item.company_name
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2
    });

    // Filter active trades by trade type
    $('#filter_trade_type').change(function() {
        var selected = $(this).val();
        if (selected === 'all') {
            $('#activeTradesTable tbody tr').show();
        } else {
            $('#activeTradesTable tbody tr').hide();
            $('#activeTradesTable tbody tr[data-trade-type="'+selected+'"]').show();
        }
    });
});

// Modal data fill + TradingView chart
$('#infoModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget);
    var modal = $(this);

    modal.find('#modalSymbol').text(button.data('symbol'));
    modal.find('#modalCompany').text(button.data('company'));
    modal.find('#modalAddedBy').text(button.data('addedby'));
    modal.find('#modalBroker').text(button.data('broker'));
    modal.find('#modalRemark').text(button.data('remark'));
    modal.find('#modalTradeType').text(button.data('trade-type'));
    modal.find('#modalQuantity').text(button.data('quantity'));
    modal.find('#modalClosed').text(button.data('closed'));
    modal.find('#modalBookedPrice').text(button.data('booked-price'));
    modal.find('#modalEntryPrice').text(button.data('entry-price'));
    modal.find('#modalRemarkHold').text(button.data('remark-hold'));

    // TradingView chart load
    var symbol = button.data('symbol');
    var stoploss = button.data('stoploss');
    var entry = button.data('entry-price');
    var target1 = button.data('target1');
    var target2 = button.data('target2');

    // Clear previous widget
    $('#tradingview_chart').empty();

    new TradingView.widget({
        width: '100%',
        height: 450,
        symbol: 'NSE:' + symbol,
        interval: 'D',
        timezone: "Asia/Kolkata",
        theme: "light",
        style: "1",
        toolbar_bg: '#f1f3f6',
        withdateranges: true,
        hide_side_toolbar: false,
        allow_symbol_change: true,
        details: true,
        hotlist: true,
        calendar: true,
        studies: [],
        overrides: {},
        studies_overrides: {}
        // Note: Markers for SL/Entry/Target require advanced TradingView integration
    });
});
</script>

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
