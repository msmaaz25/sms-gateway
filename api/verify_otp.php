<?php
// API endpoint for verifying OTP
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/OTP.php';

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

$otp_code = $input['otp_code'] ?? '';

if (empty($otp_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'OTP code is required']);
    exit;
}

try {
    $otpModel = new OTP();
    $result = $otpModel->verifyOTP($user['id'], $otp_code);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error verifying OTP: ' . $e->getMessage()
    ]);
}
?>