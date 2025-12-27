<?php
// Main index page - redirects to appropriate dashboard based on login status
require_once 'config/config.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customers/dashboard.php");
    }
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>