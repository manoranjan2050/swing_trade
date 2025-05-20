<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = strtoupper(trim($_POST['stock_symbol']));
    $entry = floatval($_POST['entry_price']);
    $sl = floatval($_POST['stoploss']);
    $t1 = floatval($_POST['target1']);
    $t2 = isset($_POST['target2']) && $_POST['target2'] !== '' ? floatval($_POST['target2']) : null;
    $qty = intval($_POST['quantity']);
    $broker = trim($_POST['broker_name'] ?? '');
    $added_by = trim($_POST['added_by']);
    $remark = trim($_POST['remark'] ?? '');
    $lt = ($_POST['is_long_term'] == '1') ? 1 : 0;
    $trade_date = date('Y-m-d');

    // Validate symbol exists in instruments table
    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM instruments WHERE symbol = ?");
    $stmt_check->bind_param("s", $symbol);
    $stmt_check->execute();
    $stmt_check->bind_result($count);
    $stmt_check->fetch();
    $stmt_check->close();

    if ($count == 0) {
        die("Invalid stock symbol: " . htmlspecialchars($symbol));
    }

    if ($t2 === null) {
        $stmt = $conn->prepare("INSERT INTO trades (stock_symbol, entry_price, stoploss, target1, quantity, broker_name, added_by, remark, is_long_term, trade_date, status, closed_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sdddisssii", $symbol, $entry, $sl, $t1, $qty, $broker, $added_by, $remark, $lt, $trade_date);
    } else {
        $stmt = $conn->prepare("INSERT INTO trades (stock_symbol, entry_price, stoploss, target1, target2, quantity, broker_name, added_by, remark, is_long_term, trade_date, status, closed_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 0)");
        if ($stmt === false) {
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
