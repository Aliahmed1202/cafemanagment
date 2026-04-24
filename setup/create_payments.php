<?php
require_once '../config/database.php';

if (!$conn || $conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$sql = file_get_contents('create_payments_table.sql');
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if (!$conn->query($statement)) {
            echo "Error executing statement: " . $conn->error . PHP_EOL;
            echo "Statement: " . $statement . PHP_EOL;
        } else {
            echo "Statement executed successfully." . PHP_EOL;
        }
    }
}

echo "Payments table created successfully!";
?>
