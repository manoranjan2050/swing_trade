<?php
include 'db.php';

// Fetch current settings or defaults
$settings_res = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $settings_res->fetch_assoc() ?? [];

$total_capital = $settings['total_capital'] ?? 100000;
$my_name = $settings['my_name'] ?? "Your Name";
$theme_color = $settings['theme_color'] ?? "#4bc0c0";
$copyright_text = $settings['copyright_text'] ?? "© 2025 Your Company";

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize & validate inputs
    $total_capital_input = filter_input(INPUT_POST, 'total_capital', FILTER_VALIDATE_FLOAT);
    $my_name_input = trim($_POST['my_name'] ?? '');
    $theme_color_input = trim($_POST['theme_color'] ?? '');
    $copyright_text_input = trim($_POST['copyright_text'] ?? '');

    if ($total_capital_input === false || $total_capital_input < 0) {
        $errors[] = "Please enter a valid non-negative number for Total Capital.";
    }

    if ($my_name_input === '') {
        $errors[] = "Name cannot be empty.";
    }

    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $theme_color_input)) {
        $errors[] = "Theme Color must be a valid hex color code, e.g. #4bc0c0.";
    }

    if ($copyright_text_input === '') {
        $errors[] = "Copyright text cannot be empty.";
    }

    if (empty($errors)) {
        // Insert or Update settings
        if ($settings) {
            // Update existing
            $stmt = $conn->prepare("UPDATE settings SET total_capital=?, my_name=?, theme_color=?, copyright_text=? WHERE id=?");
            $stmt->bind_param("dsssi", $total_capital_input, $my_name_input, $theme_color_input, $copyright_text_input, $settings['id']);
        } else {
            // Insert new
            $stmt = $conn->prepare("INSERT INTO settings (total_capital, my_name, theme_color, copyright_text) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("dsss", $total_capital_input, $my_name_input, $theme_color_input, $copyright_text_input);
        }

        if ($stmt->execute()) {
            $success = true;
            // Refresh values from DB or POST
            $total_capital = $total_capital_input;
            $my_name = $my_name_input;
            $theme_color = $theme_color_input;
            $copyright_text = $copyright_text_input;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Settings - Swing Trade Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<div class="container my-5" style="max-width: 600px;">
    <h2>Settings</h2>
    <?php if ($success): ?>
        <div class="alert alert-success">Settings saved successfully.</div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <div class="mb-3">
            <label for="total_capital" class="form-label">Total Capital (₹)</label>
            <input type="number" step="0.01" min="0" class="form-control" id="total_capital" name="total_capital" value="<?= htmlspecialchars($total_capital) ?>" required />
        </div>
        <div class="mb-3">
            <label for="my_name" class="form-label">Your Name</label>
            <input type="text" class="form-control" id="my_name" name="my_name" value="<?= htmlspecialchars($my_name) ?>" required />
        </div>
        <div class="mb-3">
            <label for="theme_color" class="form-label">Theme Color (Hex Code)</label>
            <input type="text" pattern="^#[0-9a-fA-F]{6}$" class="form-control" id="theme_color" name="theme_color" value="<?= htmlspecialchars($theme_color) ?>" placeholder="#4bc0c0" required />
            <div class="form-text">Use a hex color code like #4bc0c0</div>
        </div>
        <div class="mb-3">
            <label for="copyright_text" class="form-label">Copyright Text</label>
            <input type="text" class="form-control" id="copyright_text" name="copyright_text" value="<?= htmlspecialchars($copyright_text) ?>" required />
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
        <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
    </form>
</div>
</body>
</html>
