<?php
// Database Migration Script to remove sms_logs table
require_once __DIR__ . '/config/config.php';

try {
    $conn = getConnection();
    
    // Check if sms_logs table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'sms_logs'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        // Drop the sms_logs table
        $conn->exec("DROP TABLE sms_logs");
        echo "sms_logs table has been removed successfully!\n";
    } else {
        echo "sms_logs table does not exist, nothing to remove.\n";
    }
    
    echo "Database migration completed!\n";
    
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>