<?php

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch database details from the form
    $db_host = $_POST['db_host'] ?? 'localhost';
    $db_name = $_POST['db_name'] ?? '';
    $db_user = $_POST['db_user'] ?? '';
    $db_pass = $_POST['db_pass'] ?? '';
    
    // Try to establish a database connection
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // Check for connection error
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // SQL to create the necessary tables if they do not exist
        $create_tables_sql = [
            "CREATE TABLE IF NOT EXISTS `settings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `api_key` VARCHAR(100) NULL,
                `access_token` VARCHAR(255) NULL,
                `total_capital` DECIMAL(15,2) DEFAULT 100000,
                `my_name` VARCHAR(255) DEFAULT 'Your Name',
                `theme_color` VARCHAR(7) DEFAULT '#4bc0c0',
                `copyright_text` VARCHAR(255) DEFAULT '© 2025 Your Company'
            )",
            "CREATE TABLE IF NOT EXISTS `trades` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `stock_symbol` VARCHAR(20) NOT NULL,
                `entry_price` DECIMAL(10,2) NOT NULL,
                `stoploss` DECIMAL(10,2) NOT NULL,
                `target1` DECIMAL(10,2) NOT NULL,
                `target2` DECIMAL(10,2) DEFAULT NULL,
                `quantity` INT NOT NULL,
                `broker_name` VARCHAR(255) DEFAULT NULL,
                `added_by` VARCHAR(255) NOT NULL,
                `remark` TEXT DEFAULT NULL,
                `is_long_term` TINYINT(1) NOT NULL DEFAULT 0,
                `trade_date` DATE NOT NULL,
                `status` ENUM('active', 'closed') DEFAULT 'active',
                `closed_quantity` INT DEFAULT 0,
                `booked_price` DECIMAL(10,2) DEFAULT NULL
            )",
            "CREATE TABLE IF NOT EXISTS `instruments` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `symbol` VARCHAR(20) NOT NULL,
                `company_name` VARCHAR(255) NOT NULL,
                `instrument_token` BIGINT(20) NOT NULL,
                `exchange_token` BIGINT(20) DEFAULT NULL,
                `lot_size` INT DEFAULT 1,
                `strike_price` DECIMAL(10,2) DEFAULT NULL,
                `expiry` DATE DEFAULT NULL,
                `tick_size` DECIMAL(10,4) DEFAULT NULL,
                `instrument_type` VARCHAR(20) DEFAULT NULL,
                `segment` VARCHAR(20) DEFAULT NULL,
                `exchange` VARCHAR(10) DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS `kite_tokens` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `api_key` VARCHAR(255) NOT NULL,
                `access_token` VARCHAR(255) NOT NULL,
                `expiry_date` DATETIME NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS `partial_bookings` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `trade_id` INT NOT NULL,
                `quantity` INT NOT NULL,
                `booked_price` DECIMAL(10, 2) NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (trade_id) REFERENCES trades(id)
            )"
        ];

        // Execute SQL queries to create tables
        foreach ($create_tables_sql as $sql) {
            if (!$conn->query($sql)) {
                throw new Exception("Error creating table: " . $conn->error);
            }
        }

        // Insert default settings into the settings table
        $insert_settings_sql = "INSERT INTO `settings` (`api_key`, `access_token`, `total_capital`, `my_name`, `theme_color`, `copyright_text`)
                                VALUES ('', '', 100000, 'Your Name', '#4bc0c0', '© 2025 Your Company')";

        if (!$conn->query($insert_settings_sql)) {
            throw new Exception("Error inserting default settings: " . $conn->error);
        }

        // Close the connection
        $conn->close();

        echo "<p>Installation successful! The necessary tables have been created and default settings have been inserted.</p>";
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
} else {
    ?>

    <!-- Installation form -->
    <h1>Install Database</h1>
    <form method="POST" action="install.php">
        <div class="mb-3">
            <label for="db_host" class="form-label">Database Host:</label>
            <input type="text" name="db_host" id="db_host" class="form-control" value="localhost" required>
        </div>
        <div class="mb-3">
            <label for="db_name" class="form-label">Database Name:</label>
            <input type="text" name="db_name" id="db_name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="db_user" class="form-label">Database Username:</label>
            <input type="text" name="db_user" id="db_user" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="db_pass" class="form-label">Database Password:</label>
            <input type="password" name="db_pass" id="db_pass" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Install</button>
    </form>

    <?php
}
?>

