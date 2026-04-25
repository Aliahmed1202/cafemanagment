<?php
// Database Configuration
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

// Set charset to support Arabic
$conn->set_charset("utf8mb4");
?>
