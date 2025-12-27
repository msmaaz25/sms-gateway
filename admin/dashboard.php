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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .dashboard-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,.1);
        }
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,.1);
        }
        .stat-card .card-body {
            padding: 1.5rem;
        }
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .function-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
            height: 100%;
            text-align: center;
            padding: 2rem 1rem;
        }
        .function-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,.1);
        }
        .function-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        .function-btn {
            display: block;
            width: 100%;
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 8px;
            text-decoration: none;
            color: white;
            font-weight: 500;
            transition: all 0.2s;
        }
        .function-btn:hover {
            text-decoration: none;
            color: white;
            transform: scale(1.02);
        }
        .welcome-text {
            font-size: 1.1rem;
        }
        .stats-container {
            margin: 2rem 0;
        }
        .functions-container {
            margin: 2rem 0;
        }
        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem 0;
            }
            .stat-icon, .function-icon {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid px-4">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>OTP Service Admin
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3 welcome-text">
                    <i class="fas fa-user me-1"></i>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
                </span>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 mt-4">
        <div class="dashboard-header text-center mb-5">
            <h1 class="display-5 fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
            <p class="lead">Manage your OTP service efficiently</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Stats Section -->
        <div class="stats-container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card stat-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Total Customers</h5>
                                    <h2 class="mb-0"><?php echo $customers_count; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Today's OTPs</h5>
                                    <h2 class="mb-0"><?php echo $today_otp_requests_count; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title mb-1">Total OTPs</h5>
                                    <h2 class="mb-0"><?php echo $all_otp_requests_count; ?></h2>
                                </div>
                                <div class="stat-icon">
                                    <i class="fas fa-key"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Functions Section -->
        <div class="functions-container">
            <h3 class="mb-4"><i class="fas fa-cogs me-2"></i>Admin Functions</h3>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card function-card bg-primary text-white">
                        <div class="function-icon text-center">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <h4>Manage Customers</h4>
                        <p>Add, edit, or remove customer accounts</p>
                        <a href="manage_customers.php" class="function-btn btn btn-primary">
                            <i class="fas fa-user-plus me-1"></i>Manage Now
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card function-card bg-success text-white">
                        <div class="function-icon text-center">
                            <i class="fas fa-list-alt"></i>
                        </div>
                        <h4>View OTP Requests</h4>
                        <p>Monitor all OTP generation requests</p>
                        <a href="view_otp_requests.php" class="function-btn btn btn-success">
                            <i class="fas fa-eye me-1"></i>View Requests
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card function-card bg-warning text-dark">
                        <div class="function-icon text-center">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h4>Message Templates</h4>
                        <p>Configure OTP message templates</p>
                        <a href="view_message_templates.php" class="function-btn btn btn-warning text-dark">
                            <i class="fas fa-edit me-1"></i>Configure
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>