<?php
// Redirect to main index page which has the login form
require_once 'config/config.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customers/dashboard.php");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>