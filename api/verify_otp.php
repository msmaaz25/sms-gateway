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
// Try multiple methods to get the authorization header due to Apache configurations
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

// If not found, try getting from REDIRECT_HTTP_AUTHORIZATION (Apache with mod_rewrite)
if (empty($auth_header) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}

// If still not found, try getting all headers
if (empty($auth_header) && function_exists('getallheaders')) {
    $headers = getallheaders();
    if (isset($headers['Authorization']) || isset($headers['authorization'])) {
        $auth_header = $headers['Authorization'] ?? $headers['authorization'];
    }
}

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

$phone_number = $input['number'] ?? '';
$otp_code = $input['otp'] ?? '';

if (empty($phone_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is required']);
    exit;
}

if (empty($otp_code)) {
    http_response_code(400);
    echo json_encode(['error' => 'OTP code is required']);
    exit;
}

// Validate phone number format
$formatted_phone_number = validatePhoneNumberFormat($phone_number);
if ($formatted_phone_number === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is not valid']);
    exit;
}

try {
    $otpModel = new OTP();
    $result = $otpModel->verifyOTP($user['id'], $formatted_phone_number, $otp_code);

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

// Function to validate phone number format according to requirements and return formatted number
function validatePhoneNumberFormat($phone_number) {
    // Remove any spaces or special characters except +
    $clean_number = preg_replace('/[^0-9+]/', '', $phone_number);

    // Check if it starts with +92 (should be 13 digits total) -> return as 92... (12 digits)
    if (substr($clean_number, 0, 3) === '+92' && strlen($clean_number) === 13 && ctype_digit(substr($clean_number, 3))) {
        return substr($clean_number, 1); // Remove the '+' sign
    }

    // Check if it starts with 92 (should be 12 digits total) -> return as is
    if (substr($clean_number, 0, 2) === '92' && strlen($clean_number) === 12 && ctype_digit(substr($clean_number, 2))) {
        return $clean_number;
    }

    // Check if it starts with 0 (should be 11 digits total) -> replace 0 with 92
    if (substr($clean_number, 0, 1) === '0' && strlen($clean_number) === 11 && ctype_digit($clean_number)) {
        return '92' . substr($clean_number, 1);
    }

    // Default case: 10 digits -> add 92 in the beginning
    if (strlen($clean_number) === 10 && ctype_digit($clean_number)) {
        return '92' . $clean_number;
    }

    // If none of the conditions match, return false
    return false;
}
?>