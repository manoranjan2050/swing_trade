<?php
include 'db.php';

$result = $conn->query("SELECT id, stock_symbol, booked_price, closed_quantity FROM trades");

echo "<h2>Trades Booking Info</h2>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr><th>ID</th><th>Symbol</th><th>Booked Price</th><th>Closed Quantity</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . htmlspecialchars($row['stock_symbol']) . "</td>";
    echo "<td>" . ($row['booked_price'] !== null ? $row['booked_price'] : 'NULL') . "</td>";
    echo "<td>" . ($row['closed_quantity'] !== null ? $row['closed_quantity'] : 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";
