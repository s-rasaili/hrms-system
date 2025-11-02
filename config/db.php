<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Leave empty if no password
define('DB_NAME', 'hrms_system');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Set charset
$conn->set_charset("utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
