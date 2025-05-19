<?php
include 'db.php';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = strtoupper(trim($_POST['stock_symbol']));
    $entry = floatval($_POST['entry_price']);
    $sl = floatval($_POST['stoploss']);
    $t1 = floatval($_POST['target1']);
    $t2 = !empty($_POST['target2']) ? floatval($_POST['target2']) : null;
    $quantity = intval($_POST['quantity']);
    $closed_quantity = intval($_POST['closed_quantity']);
    if ($closed_quantity > $quantity) {
        $closed_quantity = $quantity;
    }
    $booked_price = !empty($_POST['booked_price']) ? floatval($_POST['booked_price']) : null;
    $added_by = trim($_POST['added_by'] ?? '');
    $lt = ($_POST['is_long_term'] == '1') ? 1 : 0;

    $stmt = $conn->prepare("UPDATE trades SET stock_symbol=?, entry_price=?, stoploss=?, target1=?, target2=?, quantity=?, closed_quantity=?, booked_price=?, added_by=?, is_long_term=? WHERE id=?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sddddii ssii", $symbol, $entry, $sl, $t1, $t2, $quantity, $closed_quantity, $booked_price, $added_by, $lt, $id);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        die("Error updating trade: " . $stmt->error);
    }
} else {
    $stmt = $conn->prepare("SELECT * FROM trades WHERE id = ?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $trade = $result->fetch_assoc();

    if (!$trade) {
        header("Location: index.php");
        exit();
    }
}

function fetchLTP($symbol) {
    return round(rand(9000, 11000)/100, 2);
}

$ltp = fetchLTP($trade['stock_symbol']);
$current_holding = $trade['quantity'] - $trade['closed_quantity'];
$booked_pnl = ($trade['booked_price'] !== null) ? ($trade['booked_price'] - $trade['entry_price']) * $trade['closed_quantity'] : 0;
$current_pnl = ($ltp - $trade['entry_price']) * $current_holding;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit Trade #<?= $id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container my-4">
    <h2>Edit Trade #<?= $id ?></h2>

    <div class="mb-3">
        <strong>Current LTP:</strong> <?= $ltp ?><br />
        <strong>Booked Shares:</strong> <?= $trade['closed_quantity'] ?> | <strong>Booked PnL:</strong> ₹<?= number_format($booked_pnl, 2) ?><br />
        <strong>Current Holding:</strong> <?= $current_holding ?> | <strong>Current Holding PnL:</strong> ₹<?= number_format($current_pnl, 2) ?>
    </div>

    <form method="post" class="row g-3">
        <div class="col-md-2">
            <input type="text" name="stock_symbol" class="form-control" required value="<?= htmlspecialchars($trade['stock_symbol']) ?>" />
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="entry_price" class="form-control" required value="<?= $trade['entry_price'] ?>" />
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="stoploss" class="form-control" required value="<?= $trade['stoploss'] ?>" />
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="target1" class="form-control" required value="<?= $trade['target1'] ?>" />
        </div>
        <div class="col-md-2">
            <input type="number" step="0.01" name="target2" class="form-control" value="<?= $trade['target2'] ?>" />
        </div>

        <div class="col-md-2">
            <input type="number" name="quantity" class="form-control" min="1" required value="<?= $trade['quantity'] ?>" />
        </div>

        <div class="col-md-2">
            <input type="number" name="closed_quantity" class="form-control" min="0" max="<?= $trade['quantity'] ?>" value="<?= $trade['closed_quantity'] ?>" />
            <small class="text-muted">Closed shares (booked profit)</small>
        </div>

        <div class="col-md-2">
            <input type="number" step="0.01" name="booked_price" class="form-control" placeholder="Booked Share Price" value="<?= htmlspecialchars($trade['booked_price']) ?>" />
        </div>

        <div class="col-md-2">
            <input type="text" name="added_by" class="form-control" placeholder="Added By" required value="<?= htmlspecialchars($trade['added_by']) ?>" />
        </div>

        <div class="col-md-2">
            <select name="is_long_term" class="form-select">
                <option value="0" <?= $trade['is_long_term'] == 0 ? 'selected' : '' ?>>Swing Trade</option>
                <option value="1" <?= $trade['is_long_term'] == 1 ? 'selected' : '' ?>>Long Term</option>
            </select>
        </div>

        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Update Trade</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
