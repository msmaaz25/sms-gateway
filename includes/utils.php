<?php
// Utility functions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

// Function to send SMS (integrated with Zong SMS API)
function sendSMS($phone_number, $message, $mask) {
    // Get credentials from environment variables
    $loginId = getenv('ZONG_LOGIN_ID');
    $loginPassword = getenv('ZONG_LOGIN_PASSWORD');

    // Check if credentials are set
    if (!$loginId || !$loginPassword) {
        error_log("ZONG_LOGIN_ID or ZONG_LOGIN_PASSWORD not set in environment variables");
        return false;
    }
    
    // Prepare the API request
    $curl = curl_init();
    $postData = json_encode([
        "loginId" => $loginId,
        "loginPassword" => $loginPassword,
        "Destination" => $phone_number,
        "Mask" => $mask,
        "Message" => $message,
        "UniCode" => "0",
        "ShortCodePrefered" => "N"
    ]);

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://cbs.zong.com.pk/reachrestapi/home/SendQuickSMS",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification (not recommended for production)
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);

    // Log the request for debugging
    error_log("Zong SMS API Request - Phone: $phone_number, Mask: $mask, Message: $message");
    error_log("Zong SMS API Response: $response, HTTP Code: $http_code");

    if ($error) {
        error_log("Zong SMS API Error: " . $error);
        return false;
    }

    if ($http_code === 200) {
        // Check if response indicates success (you may need to adjust this based on actual API response)
        $responseData = json_decode($response, true);
        if ($responseData !== null) {
            // Check for success in response (adjust based on actual API response format)
            // This is a general check - you may need to modify based on actual API response
            return true;
        }
        return true; // Assume success if response is valid JSON
    } else {
        error_log("Zong SMS API failed with HTTP code: " . $http_code . ", Response: " . $response);
        return false;
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
    // For now, we'll just return success for demo purposes

    // In a real application, you would make an API call to your SMS gateway here
    // Similar implementation as sendSMS but for non-OTP messages

    // For demo purposes, return success
    return true;
}
?>