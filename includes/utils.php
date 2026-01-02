<?php
// Utility functions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

// Function to send SMS (placeholder - would integrate with actual SMS gateway)
function sendSMS($phone_number, $message) {
    // This is a placeholder - in a real application, you would integrate with an SMS gateway
    // For now, we'll just return success for demo purposes

    // In a real application, you would make an API call to your SMS gateway here
    // Example:
    /*
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.sms-gateway.com/send",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'to' => $phone_number,
            'message' => $message,
            'api_key' => 'your_api_key_here'
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer your_auth_token'
        ]
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code === 200) {
        return true;
    } else {
        error_log("SMS sending failed: " . $response);
        return false;
    }
    */

    // For demo purposes, return success
    return true;
}

// Function to log SMS
function logSMS($phone_number, $message, $sms_type = 'otp', $otp_request_id = null) {
    global $conn;
    $conn = getConnection();

    try {
        $user_id = $_SESSION['user_id'];
        $query = "INSERT INTO sms_logs (user_id, phone_number, message, sms_type, otp_request_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id, $phone_number, $message, $sms_type, $otp_request_id]);
    } catch(PDOException $e) {
        error_log("Error logging SMS: " . $e->getMessage());
    }
}

// Function to validate phone number
function validatePhoneNumber($phone) {
    // Simple validation - in real app, use more comprehensive validation
    return preg_match('/^[\+]?[1-9][\d]{0,15}$/', $phone);
}

// Function to validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Function to generate API key
function generateAPIKey() {
    return bin2hex(random_bytes(32));
}

// Function to check if current user has access to a specific resource
function hasAccess($resource_user_id) {
    if (isAdmin()) {
        return true; // Admin can access all
    } else {
        // Regular user can only access their own resources
        return $_SESSION['user_id'] == $resource_user_id;
    }
}

// Function to send other types of SMS (non-OTP)
function sendOtherSMS($phone_number, $message) {
    // This is a placeholder - in a real application, you would integrate with an SMS gateway
    // For now, we'll just log the SMS attempt and return success for demo purposes

    // Log the SMS as 'other' type
    logSMS($phone_number, $message, 'other');

    // In a real application, you would make an API call to your SMS gateway here
    // Similar implementation as sendSMS but for non-OTP messages

    // For demo purposes, return success
    return true;
}
?>