<?php
// Database Initialization Script
require_once 'config/config.php';
require_once 'config/schema.php';

try {
    $conn = getConnection();
    
    // Execute the schema
    $conn->exec($database_schema);
    $conn->exec($default_admin);
    
    echo "Database initialized successfully!<br>";
    echo "Default admin account created:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123<br>";
    
} catch(PDOException $e) {
    die("Error initializing database: " . $e->getMessage());
}
?>