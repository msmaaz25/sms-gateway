<?php
// API endpoint for generating OTP
header('Content-Type: application/json');

require_once '../config/config.php';
require_once '../models/User.php';
require_once '../models/OTP.php';
require_once '../models/Masking.php';
require_once '../includes/utils.php';

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

// Check quota immediately after API key validation
if ($userModel->hasExceededQuota($user['id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'OTP quota exceeded for this month'
    ]);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Extract parameters
$otp_code = $input['otp'] ?? null;
$message = $input['message'] ?? null;
$mask = $input['mask'] ?? null;
$phone_number = $input['number'] ?? '';
$purpose = $input['purpose'] ?? '';

// Validate mandatory fields
if (empty($phone_number)) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is required']);
    exit;
}

if (empty($purpose)) {
    http_response_code(400);
    echo json_encode(['error' => 'Purpose is required']);
    exit;
}

// Validate phone number format and get formatted number
$formatted_phone_number = validatePhoneNumberFormat($phone_number);
if ($formatted_phone_number === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Phone number is not valid']);
    exit;
}
// Use the formatted phone number from now on
$phone_number = $formatted_phone_number;

// Validate OTP if provided
if ($otp_code !== null) {
    // Check if OTP is 4-5 digits
    if (!preg_match('/^\d{4,5}$/', $otp_code)) {
        http_response_code(400);
        echo json_encode(['error' => 'OTP must be 4-5 digits']);
        exit;
    }
} else {
    // Generate OTP if not provided
    $otp_code = str_pad(rand(0, 99999), 5, "0", STR_PAD_LEFT);
}

// Validate mask if provided
$maskingModel = new Masking();
if ($mask !== null) {
    // Check if mask exists and is assigned to the user or is default
    $mask_valid = false;

    // Check if mask is assigned to this specific user
    $user_maskings = $maskingModel->getMaskingsByUser($user['id']);
    foreach ($user_maskings as $user_masking) {
        if ($user_masking['masking_code'] === $mask) {
            $mask_valid = true;
            break;
        }
    }

    // If not found in user's maskings, check if it's the default masking
    if (!$mask_valid) {
        $default_masking = $maskingModel->getDefaultMasking();
        if ($default_masking && $default_masking['masking_code'] === $mask) {
            $mask_valid = true;
        }
    }

    if (!$mask_valid) {
        http_response_code(400);
        echo json_encode(['error' => 'Mask is not valid']);
        exit;
    }
} else {
    // Use default mask if none provided
    $default_masking = $maskingModel->getDefaultMasking();
    if ($default_masking) {
        $mask = $default_masking['masking_code'];
    }
}


// Validate message if provided
if ($message !== null) {
    // Check if message contains an OTP code (4-6 digits), extract and use it
    if (!preg_match('/\b(\d{4,6})\b/', $message, $matches) || strpos($message, $mask) === false) {
        http_response_code(400);
        echo json_encode(['error' => 'If you want to send a customized OTP message, make sure you have defined the mask and OTP in that along with the mask against the mask key in the API']);
        exit;
    }
    $otp_code = $matches[1];
}

try {
    $otpModel = new OTP();

    // If no message provided, use template
    if ($message === null) {
        $template = $otpModel->getOTPMessageTemplate($user['id']);

        if ($template) {
            // Replace placeholder with OTP code
            $message = str_replace($template['placeholder'], $otp_code, $template['message_template']);
        } else {
            // Default message if no template found
            $message = "Your OTP code is: $otp_code";
        }
    } else {
        // Replace placeholder in provided message if it exists
        $template = $otpModel->getOTPMessageTemplate($user['id']);

        if ($template) {
            $message = str_replace($template['placeholder'], $otp_code, $message);
        }
    }

    // Use the OTP model to generate the OTP which will store the message and mask
    $result = $otpModel->storeOTP($user['id'], $otp_code, $phone_number, $purpose, 10, $message, $mask);

    if($result) {
        // Send SMS using the message and mask
        $sms_sent = sendSMS($phone_number, $message, $mask);

        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully',
            'otp_code' => $otp_code,
            'message_sent' => $sms_sent
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate OTP'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error sending OTP: ' . $e->getMessage()
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