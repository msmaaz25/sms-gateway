<?php
// Database Schema for OTP Service
// This file contains the SQL to create the necessary tables

$database_schema = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    company_name VARCHAR(100),
    api_key VARCHAR(255) UNIQUE,
    otp_monthly_quota INT DEFAULT 0,
    otp_used_current_month INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS otp_message_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_template TEXT NOT NULL,
    placeholder VARCHAR(20) DEFAULT '{OTP}',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_template (user_id)
);

CREATE TABLE IF NOT EXISTS otp_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    otp_purpose VARCHAR(255),
    status ENUM('pending', 'verified', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS sms_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    sms_type ENUM('otp', 'other') DEFAULT 'otp',
    otp_request_id INT NULL,
    status ENUM('sent', 'failed', 'delivered') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (otp_request_id) REFERENCES otp_requests(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS maskings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    masking_code VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_masking_code (masking_code),
    INDEX idx_user_id (user_id)
);
";

// Optional: Create a default admin user
function createDefaultAdmin() {
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
    $api_key = bin2hex(random_bytes(32));
    return "
INSERT IGNORE INTO users (username, email, password, user_type, company_name, api_key)
VALUES ('admin', 'admin@otpservice.com', '$hashed_password', 'admin', 'Admin User', '$api_key');
";
}
?>