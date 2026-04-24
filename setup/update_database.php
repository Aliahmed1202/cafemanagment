<?php
// Database update script for table workflow enhancement
echo "<h2>Updating Database for Enhanced Table Workflow</h2>";

// Database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cafe_management';

try {
    // Connect to MySQL
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✓ Connected to MySQL successfully</p>";
    
    // Select the database
    $conn->select_db($database);
    
    // Add current_order_id column to tables table if it doesn't exist
    $alter_sql = "ALTER TABLE tables ADD COLUMN current_order_id INT NULL AFTER location";
    
    if ($conn->query($alter_sql)) {
        echo "<p style='color: green;'>✓ Added current_order_id column to tables table</p>";
    } else {
        echo "<p style='color: orange;'>⚠ current_order_id column may already exist or error occurred</p>";
    }
    
    // Add foreign key constraint if it doesn't exist
    $fk_sql = "ALTER TABLE tables ADD CONSTRAINT fk_tables_current_order 
                FOREIGN KEY (current_order_id) REFERENCES orders(id) ON DELETE SET NULL";
    
    if ($conn->query($fk_sql)) {
        echo "<p style='color: green;'>✓ Added foreign key constraint for current_order_id</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Foreign key constraint may already exist or error occurred</p>";
    }
    
    // Update table status enum to include 'served'
    $enum_sql = "ALTER TABLE tables MODIFY COLUMN status ENUM('available', 'occupied', 'served', 'reserved', 'cleaning') DEFAULT 'available'";
    
    if ($conn->query($enum_sql)) {
        echo "<p style='color: green;'>✓ Updated table status enum to include 'served'</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Table status enum may already be updated</p>";
    }
    
    // Verify the update
    echo "<h3>Verification:</h3>";
    $check_sql = "DESCRIBE tables";
    $result = $conn->query($check_sql);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $conn->close();
    
    echo "<div style='margin-top: 20px; padding: 15px; background: #e8f5e8; border-radius: 8px;'>";
    echo "<h3>Database Update Complete!</h3>";
    echo "<p>Your database has been updated for the enhanced table workflow. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='table_order_management.php'>Go to Table & Order Management</a></li>";
    echo "<li><a href='dashboard.php'>Go to Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your MySQL connection and permissions.</p>";
}
?>
