<?php
// Customer portal main page - redirects to appropriate page based on login status
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_setup.php';

// If not logged in, redirect to main login
if (!isLoggedIn()) {
    header("Location: ../index");
    exit();
}

// If logged in but not customer, redirect appropriately
if (!isCustomer()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard");
    } else {
        header("Location: ../index");
    }
    exit();
}

// If customer is logged in, redirect to dashboard
header("Location: dashboard");
exit();
?>