<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Redirect to main index page which has the login form
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_setup.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard");
    } else {
        header("Location: customers/dashboard");
    }
    exit();
} else {
    header("Location: index");
    exit();
}
?>