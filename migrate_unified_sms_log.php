<?php
// Database Migration Script - Unified SMS Log System
require_once 'config/config.php';

echo "Starting database migration to implement unified SMS log system...\n";

try {
    $conn = getConnection();

    // Check if the new columns already exist
    $columnsCheck = $conn->query("SHOW COLUMNS FROM sms_logs LIKE 'sms_type'");
    $typeExists = $columnsCheck->rowCount() > 0;

    $columnsCheck = $conn->query("SHOW COLUMNS FROM sms_logs LIKE 'otp_request_id'");
    $otpIdExists = $columnsCheck->rowCount() > 0;

    if ($typeExists && $otpIdExists) {
        echo "Unified SMS log fields already exist in the database. Migration not needed.\n";
        exit(0);
    }

    // Add the new columns if they don't exist
    if (!$typeExists) {
        echo "Adding sms_type column...\n";
        $conn->exec("ALTER TABLE sms_logs ADD COLUMN sms_type ENUM('otp', 'other') DEFAULT 'otp' AFTER message");
    }

    if (!$otpIdExists) {
        echo "Adding otp_request_id column...\n";
        $conn->exec("ALTER TABLE sms_logs ADD COLUMN otp_request_id INT NULL AFTER sms_type");
        
        // Add foreign key constraint
        $conn->exec("ALTER TABLE sms_logs ADD FOREIGN KEY (otp_request_id) REFERENCES otp_requests(id) ON DELETE SET NULL");
    }

    // Update existing records to set sms_type to 'otp' (since they were all OTP-related)
    echo "Updating existing records...\n";
    $conn->exec("UPDATE sms_logs SET sms_type = 'otp' WHERE sms_type IS NULL");

    // Update records that correspond to OTP requests
    // We'll try to match existing sms_logs with otp_requests based on phone number and timestamp
    echo "Linking existing OTP requests to SMS logs...\n";
    $stmt = $conn->query("
        SELECT ol.id as log_id, orq.id as request_id
        FROM sms_logs ol
        JOIN otp_requests orq ON ol.phone_number = orq.phone_number
        AND ol.user_id = orq.user_id
        AND ol.created_at >= orq.created_at
        AND ol.created_at <= DATE_ADD(orq.created_at, INTERVAL 1 MINUTE)
        WHERE ol.otp_request_id IS NULL
    ");

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $updateStmt = $conn->prepare("UPDATE sms_logs SET otp_request_id = ? WHERE id = ?");
        $updateStmt->execute([$row['request_id'], $row['log_id']]);
    }

    // For future SMS logs that are not OTP-related, we'll use 'other' type
    echo "Setting default sms_type for any remaining records...\n";
    $conn->exec("UPDATE sms_logs SET sms_type = 'otp' WHERE sms_type IS NULL");

    echo "Database migration completed successfully!\n";
    echo "New fields added:\n";
    echo "- sms_type (enum: 'otp', 'other')\n";
    echo "- otp_request_id (foreign key to otp_requests)\n";
    echo "\nExisting data has been updated to maintain relationships.\n";

} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>