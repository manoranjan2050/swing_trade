<?php
include 'db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    die("Invalid trade ID.");
}

// Fetch instruments for symbol select
$instruments_res = $conn->query("SELECT symbol, company_name FROM instruments WHERE exchange = 'NSE' ORDER BY symbol");
$instruments = $instruments_res->fetch_all(MYSQLI_ASSOC);

// Fetch existing trade
$stmt = $conn->prepare("SELECT * FROM trades WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$trade = $result->fetch_assoc();

if (!$trade) {
    die("Trade not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = strtoupper(trim($_POST['stock_symbol'] ?? ''));
    $entry = floatval($_POST['entry_price'] ?? 0);
    $sl = floatval($_POST['stoploss'] ?? 0);
    $t1 = floatval($_POST['target1'] ?? 0);
    $t2 = isset($_POST['target2']) && $_POST['target2'] !== '' ? floatval($_POST['target2']) : null;
    $qty = intval($_POST['quantity'] ?? 0);
    $broker = trim($_POST['broker_name'] ?? '');
    $added_by = trim($_POST['added_by'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $lt = intval($_POST['is_long_term'] ?? 0);

    if (!$symbol || $entry <= 0 || $sl <= 0 || $t1 <= 0 || $qty <= 0 || !$added_by) {
        die("Invalid input data.");
    }

    if ($t2 === null) {
        $update_stmt = $conn->prepare("UPDATE trades SET stock_symbol=?, entry_price=?, stoploss=?, target1=?, quantity=?, broker_name=?, added_by=?, remark=?, is_long_term=? WHERE id=?");
        $update_stmt->bind_param("sdddisssii", $symbol, $entry, $sl, $t1, $qty, $broker, $added_by, $remark, $lt, $id);
    } else {
        $update_stmt = $conn->prepare("UPDATE trades SET stock_symbol=?, entry_price=?, stoploss=?, target1=?, target2=?, quantity=?, broker_name=?, added_by=?, remark=?, is_long_term=? WHERE id=?");
        $update_stmt->bind_param("sddddisssii", $symbol, $entry, $sl, $t1, $t2, $qty, $broker, $added_by, $remark, $lt, $id);
    }

    if ($update_stmt->execute()) {
        header("Location: index.php?msg=Trade+updated+successfully");
        exit();
    } else {
        die("Error updating trade: " . $update_stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Trade #<?= $trade['id'] ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container .select2-selection--single { height: 38px; }
</style>
</head>
<body class="bg-light">

<div class="container my-4">
    <h1>Edit Trade #<?= $trade['id'] ?></h1>

    <form method="post" action="" class="row g-3">
        <div class="col-md-6">
            <label for="stock_symbol" class="form-label">Symbol</label>
            <select name="stock_symbol" id="stock_symbol" class="form-select" style="width: 100%;" required>
                <?php foreach ($instruments as $inst): ?>
                    <option value="<?= htmlspecialchars($inst['symbol']) ?>" <?= ($inst['symbol'] === $trade['stock_symbol']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($inst['symbol'] . ' - ' . $inst['company_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="entry_price" class="form-label">Entry Price</label>
            <input type="number" step="0.01" name="entry_price" id="entry_price" class="form-control" value="<?= $trade['entry_price'] ?>" required />
        </div>
        <div class="col-md-2">
            <label for="stoploss" class="form-label">Stoploss</label>
            <input type="number" step="0.01" name="stoploss" id="stoploss" class="form-control" value="<?= $trade['stoploss'] ?>" required />
        </div>
        <div class="col-md-2">
            <label for="target1" class="form-label">Target 1</label>
            <input type="number" step="0.01" name="target1" id="target1" class="form-control" value="<?= $trade['target1'] ?>" required />
        </div>
        <div class="col-md-2">
            <label for="target2" class="form-label">Target 2</label>
            <input type="number" step="0.01" name="target2" id="target2" class="form-control" value="<?= $trade['target2'] ?? '' ?>" />
        </div>
        <div class="col-md-2">
            <label for="quantity" class="form-label">Quantity (Shares)</label>
            <input type="number" name="quantity" id="quantity" class="form-control" min="1" value="<?= $trade['quantity'] ?>" required />
        </div>
        <div class="col-md-2">
            <label for="added_by" class="form-label">Added By</label>
            <input type="text" name="added_by" id="added_by" class="form-control" value="<?= htmlspecialchars($trade['added_by']) ?>" required />
        </div>
        <div class="col-md-2">
            <label for="broker_name" class="form-label">Broker Name</label>
            <input type="text" name="broker_name" id="broker_name" class="form-control" value="<?= htmlspecialchars($trade['broker_name'] ?? '') ?>" />
        </div>
        <div class="col-md-4">
            <label for="remark" class="form-label">Remark</label>
            <input type="text" name="remark" id="remark" class="form-control" value="<?= htmlspecialchars($trade['remark'] ?? '') ?>" />
        </div>
        <div class="col-md-2">
            <label for="is_long_term" class="form-label">Trade Type</label>
            <select name="is_long_term" id="is_long_term" class="form-select" required>
                <option value="0" <?= $trade['is_long_term'] == 0 ? 'selected' : '' ?>>Swing Trade</option>
                <option value="1" <?= $trade['is_long_term'] == 1 ? 'selected' : '' ?>>Long Term</option>
                <option value="2" <?= $trade['is_long_term'] == 2 ? 'selected' : '' ?>>MSI</option>
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100">Update Trade</button>
        </div>
        <div class="col-md-2 d-flex align-items-end">
            <a href="index.php" class="btn btn-secondary w-100">Cancel</a>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    $('#stock_symbol').select2({
        placeholder: "Select or type symbol",
        allowClear: true,
        minimumInputLength: 2,
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
                        return { id: item.symbol, text: item.symbol + " - " + item.company_name };
                    })
                };
            },
            cache: true
        }
    });
});
</script>

</body>
</html>
