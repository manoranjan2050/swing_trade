<?php
include 'db.php';

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
    $trade_date = date('Y-m-d');

    // Simple validation
    if (!$symbol || $entry <= 0 || $sl <= 0 || $t1 <= 0 || $qty <= 0 || !$added_by) {
        die("Invalid input data");
    }

    if ($t2 === null) {
        // Without target2
        $stmt = $conn->prepare("INSERT INTO trades (stock_symbol, entry_price, stoploss, target1, quantity, broker_name, added_by, remark, is_long_term, trade_date, status, closed_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sdddisssii", $symbol, $entry, $sl, $t1, $qty, $broker, $added_by, $remark, $lt, $trade_date);
    } else {
        // With target2
        $stmt = $conn->prepare("INSERT INTO trades (stock_symbol, entry_price, stoploss, target1, target2, quantity, broker_name, added_by, remark, is_long_term, trade_date, status, closed_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sddddisssii", $symbol, $entry, $sl, $t1, $t2, $qty, $broker, $added_by, $remark, $lt, $trade_date);
    }

    if ($stmt->execute()) {
        header("Location: index.php?msg=Trade+added+successfully");
        exit();
    } else {
        die("Error inserting trade: " . $stmt->error);
    }
}
?>
