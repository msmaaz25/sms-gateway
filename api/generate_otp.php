<?php
// API endpoint for generating OTP
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/OTP.php';
require_once '../includes/utils.php';

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

$phone_number = $input['phone_number'] ?? '';
$purpose = $input['purpose'] ?? '';

if (empty($phone_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is required']);
    exit;
}

if (!validatePhoneNumber($phone_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid phone number format']);
    exit;
}

try {
    $otpModel = new OTP();
    $result = $otpModel->generateOTP($user['id'], $phone_number, $purpose, 10);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'OTP generated and sent successfully',
            'otp_code' => $result['otp_code'],
            'expires_at' => $result['expires_at'],
            'message_sent' => $result['message_sent']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error generating OTP: ' . $e->getMessage()
    ]);
}
?>