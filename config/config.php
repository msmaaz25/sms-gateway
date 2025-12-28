<?php
// Main Configuration File
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Application settings
if (!defined('APP_NAME')) {
    define('APP_NAME', 'OTP Service');
}
if (!defined('BASE_URL')) {
    // define('BASE_URL', 'http://localhost/SMS%20Gateway/sms-gateway');
    // Build dynamic base URL
   $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
   $host = $_SERVER['HTTP_HOST'];
   $path = dirname(dirname($_SERVER['SCRIPT_NAME'
]));
   $path = rtrim($path, '/');
   define('BASE_URL', $protocol . '://' . $host . 
    $path);
    
    
    //  // Adjust based on your setup
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Utility functions - only define if not already defined
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('isAdmin')) {
    function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('isCustomer')) {
    function isCustomer() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'customer';
    }
}

if (!function_exists('redirect')) {
    function redirect($path) {
        header("Location: $path");
        exit();
    }
}

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
}
?>