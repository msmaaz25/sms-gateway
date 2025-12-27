<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');  // Adjust based on your XAMPP setup
define('DB_PASS', '');      // Adjust based on your XAMPP setup
define('DB_NAME', 'otp_service');

// Create database connection
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}
?>