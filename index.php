<?php
session_start();

// Include database configuration
require_once 'config/database.php';

// Check if database connection is working
if (!$conn || $conn->connect_error) {
    die("Database connection failed. Please check your database setup.");
}

// Set default language or get from session
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'ar'])) {
    $_SESSION['lang'] = $_GET['lang'];
} elseif (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default language
}

// Load language file
$lang_file = __DIR__ . '/languages/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    $lang = require $lang_file;
} else {
    // Fallback to English if the selected language file is missing
    $lang = require __DIR__ . '/languages/en.php';
}

// Clear any output buffers before redirect
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login if not logged in, or to dashboard if logged in
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    header('Location: admin/dashboard.php');
    exit();
} else {
    header('Location: auth/login.php');
    exit();
}
?>