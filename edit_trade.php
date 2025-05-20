<?php
include 'db.php';

if (!isset($_GET['id'])) {
    die("Trade ID missing");
}

$trade_id = intval($_GET['id']);

// Fetch trade details
$stmt = $conn->prepare("SELECT * FROM trades WHERE id = ?");
$stmt->bind_param("i", $trade_id);
$stmt->execute();
$trade = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$trade) {
    die("Trade not found");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update trade details
    $entry = floatval($_POST['entry_price']);
    $sl = floatval($_POST['stoploss']);
    $t1 = floatval($_POST['target1']);
    $t2 = !empty($_POST['target2']) ? floatval($_POST['target2']) : null;
    $qty = intval($_POST['quantity']);
    $broker = trim($_POST['broker_name'] ?? '');
    $added_by = trim($_POST['added_by']);
    $remark = trim($_POST['remark'] ?? '');
    $lt = ($_POST['is_long_term'] == '1') ? 1 : 0;

    $stmt = $conn->prepare("UPDATE trades SET entry_price=?, stoploss=?, target1=?, target2=?, quantity=?, broker_name=?, added_by=?, remark=?, is_long_term=? WHERE id=?");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ddddisssii", $entry, $sl, $t1, $t2, $qty, $broker, $added_by, $remark, $lt, $trade_id);

    if ($stmt->execute()) {
        header("Location: index.php?msg=Trade+updated+successfully");
        exit();
    } else {
        die("Error updating trade: " . $stmt->error);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Edit Trade #<?= $trade_id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<div class="container my-4">
    <h1>Edit Trade: <?= htmlspecialchars($trade['stock_symbol']) ?></h1>

    <form method="post" class="row g-3">
        <div class="col-md-3">
            <label>Entry Price</label>
            <input type="number" step="0.01" name="entry_price" class="form-control" value="<?= $trade['entry_price'] ?>" required />
        </div>
        <div class="col-md-3">
            <label>Stoploss</label>
            <input type="number" step="0.01" name="stoploss" class="form-control" value="<?= $trade['stoploss'] ?>" required />
        </div>
        <div class="col-md-3">
            <label>Target 1</label>
            <input type="number" step="0.01" name="target1" class="form-control" value="<?= $trade['target1'] ?>" required />
        </div>
        <div class="col-md-3">
            <label>Target 2</label>
            <input type="number" step="0.01" name="target2" class="form-control" value="<?= $trade['target2'] ?>" />
        </div>
        <div class="col-md-3">
            <label>Quantity</label>
            <input type="number" name="quantity" class="form-control" min="1" value="<?= $trade['quantity'] ?>" required />
        </div>
        <div class="col-md-3">
            <label>Broker Name</label>
            <input type="text" name="broker_name" class="form-control" value="<?= htmlspecialchars($trade['broker_name'] ?? '') ?>" />
        </div>
        <div class="col-md-3">
            <label>Added By</label>
            <input type="text" name="added_by" class="form-control" value="<?= htmlspecialchars($trade['added_by']) ?>" required />
        </div>
        <div class="col-md-6">
            <label>Remark</label>
            <textarea name="remark" class="form-control" rows="3"><?= htmlspecialchars($trade['remark'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
            <label>Long Term</label>
            <select name="is_long_term" class="form-select">
                <option value="0" <?= $trade['is_long_term'] == 0 ? 'selected' : '' ?>>Swing Trade</option>
                <option value="1" <?= $trade['is_long_term'] == 1 ? 'selected' : '' ?>>Long Term</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary mt-3">Save Changes</button>
            <a href="index.php" class="btn btn-secondary mt-3">Cancel</a>
        </div>
    </form>
</div>

</body>
</html>
