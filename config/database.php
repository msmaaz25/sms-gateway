<?php
// Database Configuration (reads from environment variables with sensible fallbacks)
$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbPort = getenv('DB_PORT') ?: '3306';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'otp_service';

if (!defined('DB_HOST')) {
    define('DB_HOST', $dbHost);
}
if (!defined('DB_PORT')) {
    define('DB_PORT', $dbPort);
}
if (!defined('DB_USER')) {
    define('DB_USER', $dbUser);  // Adjust based on your XAMPP setup
}
if (!defined('DB_PASS')) {
    define('DB_PASS', $dbPass);      // Adjust based on your XAMPP setup
}
if (!defined('DB_NAME')) {
    define('DB_NAME', $dbName);
}

// Create database connection
if (!function_exists('getConnection')) {
    function getConnection() {
        try {
            // First try to connect with the database (include port and charset)
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $conn = new PDO($dsn, DB_USER, DB_PASS);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $conn;
        } catch(PDOException $e) {
            // If database doesn't exist, try to connect without specifying database name
            if (strpos($e->getMessage(), 'Unknown database') !== false) {
                try {
                    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                    $conn = new PDO($dsn, DB_USER, DB_PASS);
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