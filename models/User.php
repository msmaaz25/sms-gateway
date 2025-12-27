<?php
// User Model
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

if (!class_exists('User')) {
    class User {
        private $conn;

        public function __construct() {
            $this->conn = getConnection();
        }

        // Create a new user
        public function createUser($username, $email, $password, $user_type = 'customer', $company_name = null) {
            try {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $api_key = bin2hex(random_bytes(32)); // Generate unique API key

                $query = "INSERT INTO users (username, email, password, user_type, company_name, api_key) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($query);

                return $stmt->execute([$username, $email, $hashed_password, $user_type, $company_name, $api_key]);
            } catch(PDOException $e) {
                throw new Exception("Error creating user: " . $e->getMessage());
            }
        }

        // Get user by username
        public function getUserByUsername($username) {
            try {
                $query = "SELECT * FROM users WHERE username = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$username]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting user: " . $e->getMessage());
            }
        }

        // Get user by ID
        public function getUserById($id) {
            try {
                $query = "SELECT * FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting user: " . $e->getMessage());
            }
        }

        // Get all customers (for admin use)
        public function getAllCustomers() {
            try {
                $query = "SELECT id, username, email, company_name, created_at FROM users WHERE user_type = 'customer'";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting customers: " . $e->getMessage());
            }
        }

        // Update user
        public function updateUser($id, $username, $email, $company_name = null) {
            try {
                $query = "UPDATE users SET username = ?, email = ?, company_name = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$username, $email, $company_name, $id]);
            } catch(PDOException $e) {
                throw new Exception("Error updating user: " . $e->getMessage());
            }
        }

        // Delete user
        public function deleteUser($id) {
            try {
                $query = "DELETE FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$id]);
            } catch(PDOException $e) {
                throw new Exception("Error deleting user: " . $e->getMessage());
            }
        }

        // Get user by API key
        public function getUserByApiKey($api_key) {
            try {
                $query = "SELECT * FROM users WHERE api_key = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$api_key]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting user by API key: " . $e->getMessage());
            }
        }

        // Get user's OTP quota information
        public function getUserQuotaInfo($user_id) {
            try {
                $query = "SELECT otp_monthly_quota, otp_used_current_month FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting user quota info: " . $e->getMessage());
            }
        }

        // Update user's OTP quota
        public function updateUserQuota($user_id, $new_quota) {
            try {
                $query = "UPDATE users SET otp_monthly_quota = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$new_quota, $user_id]);
            } catch(PDOException $e) {
                throw new Exception("Error updating user quota: " . $e->getMessage());
            }
        }

        // Add to user's OTP quota
        public function addToUserQuota($user_id, $additional_quota) {
            try {
                $query = "UPDATE users SET otp_monthly_quota = otp_monthly_quota + ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$additional_quota, $user_id]);
            } catch(PDOException $e) {
                throw new Exception("Error adding to user quota: " . $e->getMessage());
            }
        }

        // Increment user's used OTPs for current month
        public function incrementUserUsedQuota($user_id) {
            try {
                // Check if we need to reset the counter for a new month
                $this->resetMonthlyCounterIfNewMonth($user_id);

                $query = "UPDATE users SET otp_used_current_month = otp_used_current_month + 1 WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$user_id]);
            } catch(PDOException $e) {
                throw new Exception("Error incrementing user quota usage: " . $e->getMessage());
            }
        }

        // Reset monthly counter if it's a new month
        private function resetMonthlyCounterIfNewMonth($user_id) {
            try {
                $query = "SELECT created_at, updated_at FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Get the first day of current month
                $current_month = date('Y-m');
                $last_reset_month = date('Y-m', strtotime($user['updated_at']));

                if ($current_month !== $last_reset_month) {
                    // Reset the counter for new month
                    $reset_query = "UPDATE users SET otp_used_current_month = 0 WHERE id = ?";
                    $reset_stmt = $this->conn->prepare($reset_query);
                    $reset_stmt->execute([$user_id]);
                }
            } catch(PDOException $e) {
                throw new Exception("Error resetting monthly counter: " . $e->getMessage());
            }
        }

        // Check if user has exceeded their quota
        public function hasExceededQuota($user_id) {
            try {
                $this->resetMonthlyCounterIfNewMonth($user_id);

                $query = "SELECT otp_monthly_quota, otp_used_current_month FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                return $user['otp_used_current_month'] >= $user['otp_monthly_quota'];
            } catch(PDOException $e) {
                throw new Exception("Error checking user quota: " . $e->getMessage());
            }
        }
    }
}
?>