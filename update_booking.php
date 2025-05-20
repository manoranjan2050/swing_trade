<?php
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trade_id = intval($_POST['trade_id'] ?? 0);
    $booked_price = floatval($_POST['booked_price'] ?? 0);
    $booked_qty = intval($_POST['booked_qty'] ?? 0);

    if ($trade_id <= 0 || $booked_price <= 0 || $booked_qty <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid input']);
        exit;
    }

    // Fetch current closed quantity and booked price
    $stmt = $conn->prepare("SELECT closed_quantity, booked_price, quantity FROM trades WHERE id = ?");
    $stmt->bind_param("i", $trade_id);
    $stmt->execute();
    $stmt->bind_result($old_closed_qty, $old_booked_price, $total_qty);
    $stmt->fetch();
    $stmt->close();

    $old_booked_price = $old_booked_price ?? 0;
    $old_closed_qty = $old_closed_qty ?? 0;

    $new_closed_qty = $old_closed_qty + $booked_qty;

    if ($new_closed_qty > $total_qty) {
        http_response_code(400);
        echo json_encode(['error' => 'Booked quantity exceeds total quantity']);
        exit;
    }

    // Calculate new average booked price
    if ($old_booked_price == 0) {
        $new_booked_price = $booked_price;
    } else {
        $total_booked_value = ($old_booked_price * $old_closed_qty) + ($booked_price * $booked_qty);
        $new_booked_price = $total_booked_value / $new_closed_qty;
    }

    // Update trades table
    $stmt = $conn->prepare("UPDATE trades SET closed_quantity = ?, booked_price = ? WHERE id = ?");
    $stmt->bind_param("idi", $new_closed_qty, $new_booked_price, $trade_id);
    $success = $stmt->execute();
    $stmt->close();

    if (!$success) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update trade']);
        exit;
    }

    // Insert partial booking record
    $stmt = $conn->prepare("INSERT INTO partial_bookings (trade_id, booked_quantity, booked_date) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $trade_id, $booked_qty);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'new_closed_quantity' => $new_closed_qty, 'new_booked_price' => $new_booked_price]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
