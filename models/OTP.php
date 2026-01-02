<?php
// OTP Model
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/utils.php';

if (!class_exists('OTP')) {
    class OTP {
        private $conn;

        public function __construct() {
            $this->conn = getConnection();
        }

        // Generate a new OTP with message template
        public function generateOTP($user_id, $phone_number, $purpose = null, $expiry_minutes = 10) {
            try {
                // Check if user has exceeded their quota
                require_once __DIR__ . '/User.php';
                $userModel = new User();

                if ($userModel->hasExceededQuota($user_id)) {
                    return [
                        'success' => false,
                        'message' => 'OTP quota exceeded for this month'
                    ];
                }

                // Generate 6-digit OTP
                $otp_code = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);

                // Calculate expiry time
                $expires_at = date("Y-m-d H:i:s", strtotime("+$expiry_minutes minutes"));

                // Get message template for this user
                $template = $this->getOTPMessageTemplate($user_id);
                if ($template) {
                    // Replace placeholder with OTP code
                    $message = str_replace($template['placeholder'], $otp_code, $template['message_template']);
                } else {
                    // Default message if no template found
                    $message = "Your OTP code is: $otp_code";
                }

                // Insert OTP request
                $query = "INSERT INTO otp_requests (user_id, phone_number, otp_code, otp_purpose, expires_at) VALUES (?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                $result = $stmt->execute([$user_id, $phone_number, $otp_code, $purpose, $expires_at]);

                if($result) {
                    // Get the ID of the newly inserted OTP request
                    $otp_request_id = $this->conn->lastInsertId();

                    // Increment user's used quota
                    $userModel->incrementUserUsedQuota($user_id);

                    // Log the SMS with the OTP request ID
                    logSMS($phone_number, $message, 'otp', $otp_request_id);

                    return [
                        'success' => true,
                        'otp_code' => $otp_code,
                        'expires_at' => $expires_at,
                        'message_sent' => true,
                        'otp_request_id' => $otp_request_id
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Failed to generate OTP'
                    ];
                }
            } catch(PDOException $e) {
                throw new Exception("Error generating OTP: " . $e->getMessage());
            }
        }

        // Verify OTP
        public function verifyOTP($user_id, $otp_code) {
            try {
                $query = "SELECT * FROM otp_requests WHERE user_id = ? AND otp_code = ? AND status = 'pending' AND expires_at > NOW()";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id, $otp_code]);
                $otp = $stmt->fetch(PDO::FETCH_ASSOC);

                if($otp) {
                    // Update OTP status to verified
                    $update_query = "UPDATE otp_requests SET status = 'verified' WHERE id = ?";
                    $update_stmt = $this->conn->prepare($update_query);
                    $update_stmt->execute([$otp['id']]);

                    return [
                        'success' => true,
                        'message' => 'OTP verified successfully'
                    ];
                } else {
                    return [
                        'success' => false,
                        'message' => 'Invalid or expired OTP'
                    ];
                }
            } catch(PDOException $e) {
                throw new Exception("Error verifying OTP: " . $e->getMessage());
            }
        }

        // Get OTP requests for a user
        public function getOTPRequests($user_id) {
            try {
                $query = "SELECT * FROM otp_requests WHERE user_id = ? ORDER BY created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting OTP requests: " . $e->getMessage());
            }
        }

        // Get OTP request by ID
        public function getOTPRequestById($id) {
            try {
                $query = "SELECT * FROM otp_requests WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting OTP request: " . $e->getMessage());
            }
        }

        // Get all OTP requests (for admin use)
        public function getAllOTPRequests() {
            try {
                $query = "SELECT o.*, u.username, u.company_name FROM otp_requests o
                          JOIN users u ON o.user_id = u.id
                          ORDER BY o.created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting OTP requests: " . $e->getMessage());
            }
        }

        // Get OTP requests for a specific user (for admin use)
        public function getOTPRequestsByUserId($user_id) {
            try {
                $query = "SELECT * FROM otp_requests WHERE user_id = ? ORDER BY created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting OTP requests: " . $e->getMessage());
            }
        }

        // OTP Message Template Methods
        public function createOTPMessageTemplate($user_id, $message_template, $placeholder = '{OTP}') {
            try {
                // First check if a template already exists for this user
                $existing = $this->getOTPMessageTemplate($user_id);
                if ($existing) {
                    // Update existing template instead of creating a new one
                    return $this->updateOTPMessageTemplateByUser($user_id, $message_template, $placeholder);
                }

                $query = "INSERT INTO otp_message_templates (user_id, message_template, placeholder) VALUES (?, ?, ?)";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$user_id, $message_template, $placeholder]);
            } catch(PDOException $e) {
                throw new Exception("Error creating OTP message template: " . $e->getMessage());
            }
        }

        public function updateOTPMessageTemplate($id, $message_template, $placeholder = '{OTP}') {
            try {
                $query = "UPDATE otp_message_templates SET message_template = ?, placeholder = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$message_template, $placeholder, $id]);
            } catch(PDOException $e) {
                throw new Exception("Error updating OTP message template: " . $e->getMessage());
            }
        }

        public function updateOTPMessageTemplateByUser($user_id, $message_template, $placeholder = '{OTP}') {
            try {
                $query = "UPDATE otp_message_templates SET message_template = ?, placeholder = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$message_template, $placeholder, $user_id]);
            } catch(PDOException $e) {
                throw new Exception("Error updating OTP message template: " . $e->getMessage());
            }
        }

        public function deleteOTPMessageTemplate($id) {
            try {
                $query = "DELETE FROM otp_message_templates WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$id]);
            } catch(PDOException $e) {
                throw new Exception("Error deleting OTP message template: " . $e->getMessage());
            }
        }

        public function getOTPMessageTemplate($user_id) {
            try {
                $query = "SELECT * FROM otp_message_templates WHERE user_id = ? LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting OTP message template: " . $e->getMessage());
            }
        }
    }
}
?>