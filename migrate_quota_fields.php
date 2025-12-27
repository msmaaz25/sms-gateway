<?php
// Database Migration Script - Add OTP Quota Fields
require_once 'config/config.php';

echo "Starting database migration to add OTP quota fields...\n";

try {
    $conn = getConnection();
    
    // Check if the columns already exist
    $columnsCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_monthly_quota'");
    $quotaExists = $columnsCheck->rowCount() > 0;
    
    $columnsCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'otp_used_current_month'");
    $usedExists = $columnsCheck->rowCount() > 0;
    
    if ($quotaExists && $usedExists) {
        echo "OTP quota fields already exist in the database. Migration not needed.\n";
        exit(0);
    }
    
    // Add the new columns if they don't exist
    if (!$quotaExists) {
        echo "Adding otp_monthly_quota column...\n";
        $conn->exec("ALTER TABLE users ADD COLUMN otp_monthly_quota INT DEFAULT 0");
    }
    
    if (!$usedExists) {
        echo "Adding otp_used_current_month column...\n";
        $conn->exec("ALTER TABLE users ADD COLUMN otp_used_current_month INT DEFAULT 0");
    }
    
    echo "Database migration completed successfully!\n";
    echo "New fields added:\n";
    echo "- otp_monthly_quota (default: 0)\n";
    echo "- otp_used_current_month (default: 0)\n";
    
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>