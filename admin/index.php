<?php
// Admin portal main page - redirects to appropriate page based on login status
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_setup.php';

// If not logged in, redirect to admin login
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// If logged in but not admin, redirect appropriately
if (!isAdmin()) {
    if (isCustomer()) {
        header("Location: ../customers/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

// If admin is logged in, redirect to dashboard
header("Location: dashboard.php");
exit();
?>