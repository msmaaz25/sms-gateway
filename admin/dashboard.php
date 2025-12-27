<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
?>

<?php
require_once '../models/User.php';
require_once '../models/OTP.php';

$userModel = new User();
$otpModel = new OTP();

// Get statistics with error handling
try {
    $customers = $userModel->getAllCustomers();
    $customers_count = count($customers);

    $all_otp_requests = $otpModel->getAllOTPRequests();
    $all_otp_requests_count = count($all_otp_requests);

    $today_otp_requests = array_filter($all_otp_requests, function($otp) {
        return date('Y-m-d', strtotime($otp['created_at'])) === date('Y-m-d');
    });
    $today_otp_requests_count = count($today_otp_requests);
} catch (Exception $e) {
    // Handle potential database errors
    $customers_count = 0;
    $all_otp_requests_count = 0;
    $today_otp_requests_count = 0;
    $error_message = "Error loading dashboard data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OTP Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">OTP Service Admin</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Admin Dashboard</h1>
                <hr>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Customers</h5>
                        <h2><?php echo $customers_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Today's OTPs</h5>
                        <h2><?php echo $today_otp_requests_count; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total OTPs</h5>
                        <h2><?php echo $all_otp_requests_count; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h5>Admin Functions</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <a href="manage_customers.php" class="btn btn-primary btn-block">Manage Customers</a>
                            </div>
                            <div class="col-md-3">
                                <a href="view_otp_requests.php" class="btn btn-success btn-block">View OTP Requests</a>
                            </div>
                            <div class="col-md-3">
                                <a href="view_message_templates.php" class="btn btn-warning btn-block">View Message Templates</a>
                            </div>
                            <div class="col-md-3">
                                <a href="view_all_customers.php" class="btn btn-info btn-block">View All Customers</a>
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