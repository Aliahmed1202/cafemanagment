<?php
session_start();

// Include database configuration
require_once '../config/database.php';

// Check if database connection is working
if (!$conn || $conn->connect_error) {
    die("Database connection failed. Please check your database setup.");
}

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // You could add a logout_logs table to track this
    // For now, we'll just destroy the session
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any output buffers before redirect
if (ob_get_level()) {
    ob_end_clean();
}

// Redirect to login page with logout message
header('Location: login.php?logout=success');
exit();
?>
