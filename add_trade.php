<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $symbol = strtoupper(trim($_POST['stock_symbol']));
    $entry = floatval($_POST['entry_price']);
    $sl = floatval($_POST['stoploss']);
    $t1 = floatval($_POST['target1']);
    $t2 = !empty($_POST['target2']) ? floatval($_POST['target2']) : null;
    $quantity = intval($_POST['quantity']);
    $closed_quantity = 0; // New trade, no shares booked yet
    $booked_price = null; // Null initially
    $added_by = trim($_POST['added_by'] ?? '');

    $lt = ($_POST['is_long_term'] == '1') ? 1 : 0;

    $stmt = $conn->prepare("INSERT INTO trades (stock_symbol, entry_price, stoploss, target1, target2, is_long_term, quantity, closed_quantity, booked_price, added_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    if ($stmt === false) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sddddiiiss", $symbol, $entry, $sl, $t1, $t2, $lt, $quantity, $closed_quantity, $booked_price, $added_by);

    if ($stmt->execute()) {
        header("Location: index.php");
        exit();
    } else {
        die("Error inserting trade: " . $stmt->error);
    }
}
?>
