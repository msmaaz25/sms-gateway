<?php
// Customer portal main page - redirects to appropriate page based on login status
require_once '../config/config.php';
require_once '../includes/auth.php';

// If not logged in, redirect to main login
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// If logged in but not customer, redirect appropriately
if (!isCustomer()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

// If customer is logged in, redirect to dashboard
header("Location: dashboard.php");
exit();
?>