<?php
// Database setup script
echo "<h2>Database Setup for Cafe Management System</h2>";

// Database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cafe_management';

try {
    // Connect to MySQL (without selecting database)
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Connected to MySQL successfully</p>";
    
    // Create database if it doesn't exist
    $create_db_sql = "CREATE DATABASE IF NOT EXISTS $database";
    if ($conn->query($create_db_sql)) {
        echo "<p style='color: green;'>✓ Database '$database' created or already exists</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating database: " . $conn->error . "</p>";
    }
    
    // Select the database
    $conn->select_db($database);
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/database_setup.sql';
    if (file_exists($sql_file)) {
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL content into individual statements
        $statements = explode(';', $sql_content);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                if ($conn->query($statement)) {
                    echo "<p style='color: green;'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ Skipped/Failed: " . substr($statement, 0, 50) . "...</p>";
                }
            }
        }
        
        echo "<p style='color: green; font-weight: bold;'>✓ Database setup completed!</p>";
        
        // Verify tables were created
        $tables = ['users', 'customers', 'categories', 'menu_items', 'inventory', 'daily_closing_inventory', 'tables', 'orders'];
        echo "<h3>Verifying Tables:</h3>";
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "<p style='color: green;'>✓ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>✗ Table '$table' missing</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ SQL file not found: $sql_file</p>";
    }
    
    $conn->close();
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px;'>";
    echo "<h3>Setup Complete!</h3>";
    echo "<p>Your database has been set up successfully. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Go to Login Page</a></li>";
    echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
    echo "<li><a href='inventory_management.php'>Go to Inventory Management</a></li>";
    echo "<li><a href='daily_closing_inventory.php'>Go to Daily Closing Inventory</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL connection details and make sure MySQL is running.</p>";
}
?>
