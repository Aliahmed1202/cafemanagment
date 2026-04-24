<?php
// Test database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'cafe_management';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>✅ Database Connection Successful!</h2>";
echo "<p>Connected to database: <strong>$database</strong></p>";

// Test if tables exist
$tables = ['users', 'orders', 'customers', 'menu_items', 'categories'];
echo "<h3>Checking Tables:</h3>";

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✅ Table '$table' exists<br>";
    } else {
        echo "❌ Table '$table' missing<br>";
    }
}

// Test sample data
echo "<h3>Sample Users:</h3>";
$result = $conn->query("SELECT username, full_name, role FROM users LIMIT 5");
if ($result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['username']}</td><td>{$row['full_name']}</td><td>{$row['role']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "❌ No users found in database";
}

echo "<br><a href='login.php'>Go to Login</a>";
?>
