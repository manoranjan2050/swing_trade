<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $capital = floatval($_POST['total_capital']);
    $conn->query("UPDATE settings SET total_capital = $capital WHERE id=1");
    header("Location: settings.php");
    exit();
}

$capital_result = $conn->query("SELECT total_capital FROM settings LIMIT 1");
$capital = $capital_result->fetch_assoc()['total_capital'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Settings - Capital</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>
<div class="container mt-4">
    <h2>Update Total Capital</h2>
    <form method="post" class="mt-3">
        <div class="mb-3">
            <label for="capital" class="form-label">Total Capital (₹)</label>
            <input type="number" step="0.01" class="form-control" id="capital" name="total_capital" value="<?= $capital ?>" required>
        </div>
        <button type="submit" class="btn btn-success">Save</button>
    </form>
</div>
</body>
</html>
