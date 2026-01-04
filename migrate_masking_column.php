<?php
// Database Migration Script to update masking_code column size
require_once __DIR__ . '/config/config.php';

try {
    $conn = getConnection();
    
    // Modify the masking_code column to VARCHAR(20)
    $conn->exec("ALTER TABLE maskings MODIFY masking_code VARCHAR(20) UNIQUE NOT NULL");
    
    echo "masking_code column has been updated to VARCHAR(20) successfully!\n";
    echo "Database migration completed!\n";
    
} catch (PDOException $e) {
    echo "Error during migration: " . $e->getMessage() . "\n";
}
?>