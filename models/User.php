<?php
// User Model
require_once '../config/config.php';

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
}
?>