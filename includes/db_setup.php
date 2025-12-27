<?php
// Database setup checker and initializer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

function checkDatabaseSetup() {
    try {
        $conn = getConnection();

        // Check if required tables exist
        $tables = ['users', 'otp_message_templates', 'otp_requests', 'sms_logs'];

        foreach ($tables as $table) {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                return false; // Table doesn't exist
            }
        }

        // Check if admin user exists
        $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin'");
        $adminCount = $stmt->fetchColumn();

        return $adminCount > 0; // Return true only if admin user exists
    } catch (Exception $e) {
        error_log("Database check error: " . $e->getMessage());
        return false; // Database connection or query failed
    }
}

function setupDatabase() {
    require_once __DIR__ . '/../config/schema.php';

    try {
        $conn = getConnection();

        // Execute the schema
        $conn->exec($database_schema);
        $conn->exec(createDefaultAdmin());

        return true;
    } catch (PDOException $e) {
        error_log("Database setup error: " . $e->getMessage());
        return false;
    }
}

// Check if database is set up (only run once per request to avoid loops)
if (!isset($GLOBALS['db_setup_checked'])) {
    if (!checkDatabaseSetup()) {
        if (setupDatabase()) {
            // Database setup successful
            // Continue execution - no redirect to avoid loops
        } else {
            // Database setup failed
            die("Database setup failed. Please run init_db.php manually.");
        }
    }
    $GLOBALS['db_setup_checked'] = true;
}
?>