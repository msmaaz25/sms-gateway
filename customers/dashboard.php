<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireCustomer();

require_once '../models/User.php';
require_once '../models/OTP.php';

$userModel = new User();
$otpModel = new OTP();

// Get current user details
$user = $userModel->getUserById($_SESSION['user_id']);
$otp_requests = $otpModel->getOTPRequests($_SESSION['user_id']);

// Get statistics
$today_otp_requests = array_filter($otp_requests, function($otp) {
    return date('Y-m-d', strtotime($otp['created_at'])) === date('Y-m-d');
});
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - OTP Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">OTP Service Customer</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Customer Dashboard</h1>
                <hr>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Your API Key</h5>
                        <p class="card-text" style="font-size: 0.9em;"><?php echo substr($user['api_key'], 0, 10); ?>...</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Today's OTPs</h5>
                        <h2><?php echo count($today_otp_requests); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total OTPs</h5>
                        <h2><?php echo count($otp_requests); ?></h2>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Your Functions</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <a href="view_otp_requests.php" class="btn btn-primary btn-block">View OTP Requests</a>
                            </div>
                            <div class="col-md-3">
                                <a href="generate_otp.php" class="btn btn-success btn-block">Generate New OTP</a>
                            </div>
                            <div class="col-md-3">
                                <a href="manage_templates.php" class="btn btn-warning btn-block">Manage Templates</a>
                            </div>
                            <div class="col-md-3">
                                <a href="account_settings.php" class="btn btn-info btn-block">Account Settings</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>