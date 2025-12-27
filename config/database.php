<?php
// Database Configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');  // Adjust based on your XAMPP setup
}
if (!defined('DB_PASS')) {
    define('DB_PASS', '');      // Adjust based on your XAMPP setup
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'otp_service');
}

// Create database connection
if (!function_exists('getConnection')) {
    function getConnection() {
        try {
            // First try to connect with the database
            $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            // If database doesn't exist, try to connect without specifying database name
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    // Create the database
                    $conn->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
                    $conn->exec("USE " . DB_NAME);

                    return $conn;
                } catch(PDOException $e2) {
                    die("Connection failed: " . $e2->getMessage());
                }
            } else {
                die("Connection failed: " . $e->getMessage());
            }
        }
    }
}
?>