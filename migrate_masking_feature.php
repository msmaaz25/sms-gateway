<?php
// Database Migration Script - Add Masking Feature
require_once 'config/config.php';

echo "Starting database migration to add masking feature...\n";

try {
    $conn = getConnection();

    // Check if the masking table already exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'maskings'");
    $tableExists = $tableCheck->rowCount() > 0;

    if ($tableExists) {
        echo "Masking table already exists in the database. Migration not needed.\n";
        exit(0);
    }

    // Create the maskings table
    echo "Creating maskings table...\n";
    $conn->exec("
        CREATE TABLE maskings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            masking_code VARCHAR(50) UNIQUE NOT NULL,
            user_id INT NULL,  -- NULL means unassigned, assigned to a user when linked
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_masking_code (masking_code),
            INDEX idx_user_id (user_id)
        )
    ");

    echo "Database migration completed successfully!\n";
    echo "New table created:\n";
    echo "- maskings: Contains masking codes with user assignments\n";
    echo "  - masking_code: Unique alphanumeric masking identifier\n";
    echo "  - user_id: Links masking to a user (NULL if unassigned)\n";
    echo "  - is_active: Whether the masking is currently active\n";

} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
    exit(1);
}
?>