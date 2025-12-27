<?php
// Authentication functions
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';

class Auth {
    public static function login($username, $password) {
        $user = new User();
        $userData = $user->getUserByUsername($username);
        
        if ($userData && password_verify($password, $userData['password'])) {
            $_SESSION['user_id'] = $userData['id'];
            $_SESSION['username'] = $userData['username'];
            $_SESSION['user_type'] = $userData['user_type'];
            $_SESSION['company_name'] = $userData['company_name'];
            
            return [
                'success' => true,
                'user_type' => $userData['user_type'],
                'message' => 'Login successful'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid username or password'
            ];
        }
    }
    
    public static function logout() {
        session_destroy();
        return true;
    }
    
    public static function requireLogin() {
        if (!isLoggedIn()) {
            redirect('../login.php');
        }
    }
    
    public static function requireAdmin() {
        if (!isAdmin()) {
            redirect('../index.php');
        }
    }
    
    public static function requireCustomer() {
        if (!isCustomer()) {
            redirect('../index.php');
        }
    }
}
?>