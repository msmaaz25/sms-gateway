<?php
// API endpoint for getting OTP requests
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/OTP.php';

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get API key from header
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (strpos($auth_header, 'Bearer ') !== 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Authorization header missing or invalid']);
    exit;
}

$api_key = substr($auth_header, 7); // Remove 'Bearer ' prefix

// Validate API key
$userModel = new User();
$user = $userModel->getUserByApiKey($api_key);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid API key']);
    exit;
}

try {
    $otpModel = new OTP();
    $otp_requests = $otpModel->getOTPRequests($user['id']);
    
    echo json_encode([
        'success' => true,
        'data' => $otp_requests
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching OTP requests: ' . $e->getMessage()
    ]);
}
?>