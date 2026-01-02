<?php
// Masking Model
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

if (!class_exists('Masking')) {
    class Masking {
        private $conn;

        public function __construct() {
            $this->conn = getConnection();
        }

        // Create a new masking
        public function createMasking($masking_code, $is_default = 0) {
            try {
                // Check if masking code already exists
                if ($this->maskingCodeExists($masking_code)) {
                    throw new Exception("Masking code already exists");
                }

                // If setting as default, remove default from existing masking
                if ($is_default) {
                    $this->removeDefaultMasking();
                }

                $query = "INSERT INTO maskings (masking_code, is_default) VALUES (?, ?)";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$masking_code, $is_default]);
            } catch(PDOException $e) {
                throw new Exception("Error creating masking: " . $e->getMessage());
            }
        }

        // Get all maskings
        public function getAllMaskings() {
            try {
                $query = "SELECT m.*, u.username as assigned_user FROM maskings m
                          LEFT JOIN users u ON m.user_id = u.id
                          ORDER BY m.is_default DESC, m.created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting maskings: " . $e->getMessage());
            }
        }

        // Get masking by ID
        public function getMaskingById($id) {
            try {
                $query = "SELECT m.*, u.username as assigned_user FROM maskings m
                          LEFT JOIN users u ON m.user_id = u.id
                          WHERE m.id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$id]);
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting masking: " . $e->getMessage());
            }
        }

        // Get maskings assigned to a specific user
        public function getMaskingsByUser($user_id) {
            try {
                $query = "SELECT * FROM maskings WHERE user_id = ? ORDER BY created_at DESC";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$user_id]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting maskings for user: " . $e->getMessage());
            }
        }

        // Get default masking
        public function getDefaultMasking() {
            try {
                $query = "SELECT * FROM maskings WHERE is_default = 1 LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                return $stmt->fetch(PDO::FETCH_ASSOC);
            } catch(PDOException $e) {
                throw new Exception("Error getting default masking: " . $e->getMessage());
            }
        }

        // Set masking as default
        public function setAsDefault($masking_id) {
            try {
                // First remove default from existing masking
                $this->removeDefaultMasking();

                // Set the new masking as default
                $query = "UPDATE maskings SET is_default = 1 WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$masking_id]);
            } catch(PDOException $e) {
                throw new Exception("Error setting masking as default: " . $e->getMessage());
            }
        }

        // Remove default masking (set all to 0)
        private function removeDefaultMasking() {
            try {
                $query = "UPDATE maskings SET is_default = 0 WHERE is_default = 1";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute();
            } catch(PDOException $e) {
                throw new Exception("Error removing default masking: " . $e->getMessage());
            }
        }

        // Assign a masking to a user
        public function assignMaskingToUser($masking_id, $user_id) {
            try {
                // Check if masking is already assigned to another user
                $masking = $this->getMaskingById($masking_id);

                // Check if masking is default (can't be assigned to a specific user)
                if ($masking && $masking['is_default'] == 1) {
                    throw new Exception("Default masking cannot be assigned to a specific user");
                }

                if ($masking && $masking['user_id'] != null && $masking['user_id'] != $user_id) {
                    throw new Exception("This masking is already assigned to another user");
                }

                $query = "UPDATE maskings SET user_id = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$user_id, $masking_id]);
            } catch(PDOException $e) {
                throw new Exception("Error assigning masking to user: " . $e->getMessage());
            }
        }

        // Remove masking assignment from user
        public function removeMaskingFromUser($masking_id) {
            try {
                // Check if masking is default (can't be unassigned)
                $masking = $this->getMaskingById($masking_id);
                if ($masking && $masking['is_default'] == 1) {
                    throw new Exception("Default masking cannot be unassigned");
                }

                $query = "UPDATE maskings SET user_id = NULL WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$masking_id]);
            } catch(PDOException $e) {
                throw new Exception("Error removing masking assignment: " . $e->getMessage());
            }
        }

        // Update masking status
        public function updateMaskingStatus($id, $is_active) {
            try {
                $query = "UPDATE maskings SET is_active = ? WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$is_active, $id]);
            } catch(PDOException $e) {
                throw new Exception("Error updating masking status: " . $e->getMessage());
            }
        }

        // Delete a masking
        public function deleteMasking($id) {
            try {
                // Check if masking is default (can't be deleted)
                $masking = $this->getMaskingById($id);
                if ($masking && $masking['is_default'] == 1) {
                    throw new Exception("Default masking cannot be deleted");
                }

                $query = "DELETE FROM maskings WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                return $stmt->execute([$id]);
            } catch(PDOException $e) {
                throw new Exception("Error deleting masking: " . $e->getMessage());
            }
        }

        // Check if masking code already exists
        private function maskingCodeExists($masking_code) {
            try {
                $query = "SELECT COUNT(*) FROM maskings WHERE masking_code = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->execute([$masking_code]);
                return $stmt->fetchColumn() > 0;
            } catch(PDOException $e) {
                throw new Exception("Error checking masking code: " . $e->getMessage());
            }
        }

        // Validate masking code format (alphanumeric)
        public function validateMaskingCode($masking_code) {
            return preg_match('/^[a-zA-Z0-9]+$/', $masking_code) && strlen($masking_code) <= 50;
        }
    }
}
?>