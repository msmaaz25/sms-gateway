<?php
// Main Configuration File
session_start();

// Application settings
define('APP_NAME', 'OTP Service');
define('BASE_URL', 'http://localhost/SMS%20Gateway/sms-gateway'); // Adjust based on your setup

// Include database configuration
require_once 'database.php';

// Utility functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isCustomer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
}

function redirect($path) {
    header("Location: $path");
    exit();
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>