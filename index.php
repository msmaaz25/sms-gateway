<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Main landing page - shows company info and login form
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db_setup.php';

// If already logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customers/dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Service - Secure Authentication</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .info-section {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border-radius: 15px 0 0 15px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-section {
            padding: 40px;
        }
        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #fff;
        }
        .feature-list {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        .feature-list li:before {
            content: "✓";
            margin-right: 10px;
            color: #4CAF50;
        }
        .login-title {
            color: #2c3e50;
            font-weight: 600;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row min-vh-100">
            <!-- Information Section (Left) -->
            <div class="col-lg-7 d-none d-lg-block">
                <div class="info-section h-100">
                    <div>
                        <div class="logo">SMS Gateway</div>
                        <h1>Secure OTP Service</h1>
                        <p class="lead">Enterprise-grade one-time password authentication system for secure user verification.</p>

                        <h5 class="mt-5">Key Features</h5>
                        <ul class="feature-list">
                            <li>Real-time OTP generation and delivery</li>
                            <li>Customizable message templates</li>
                            <li>Secure API integration</li>
                            <li>Comprehensive admin controls</li>
                            <li>Detailed usage analytics</li>
                            <li>Multi-channel delivery options</li>
                        </ul>

                        <div class="mt-5">
                            <h6>Need an account?</h6>
                            <p>Contact our sales team to get started with our OTP service.</p>
                            <p><i class="fas fa-envelope"></i> sales@otpservice.com</p>
                            <p><i class="fas fa-phone"></i> +1 (555) 123-4567</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Section (Right) -->
            <div class="col-lg-5">
                <div class="login-section d-flex align-items-center">
                    <div class="w-100">
                        <h2 class="login-title text-center mb-4">OTP Service Login</h2>

                        <?php
                        $error = '';

                        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                            $username = sanitizeInput($_POST['username']);
                            $password = $_POST['password'];

                            if (empty($username) || empty($password)) {
                                $error = 'Please fill in all fields';
                            } else {
                                require_once 'includes/auth.php';
                                $login_result = Auth::login($username, $password);
                                if ($login_result['success']) {
                                    if ($login_result['user_type'] === 'admin') {
                                        header("Location: admin/dashboard.php");
                                    } else {
                                        header("Location: customers/dashboard.php");
                                    }
                                    exit();
                                } else {
                                    $error = $login_result['message'];
                                }
                            }
                        }
                        ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>

                        <!-- Mobile login link -->
                        <div class="d-lg-none mt-4 text-center">
                            <a href="login.php" class="btn btn-outline-primary">Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>