<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireCustomer();

require_once '../models/OTP.php';

$otpModel = new OTP();
$message = '';

// Get current OTP message template for the user
$template = $otpModel->getOTPMessageTemplate($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View OTP Message Template - OTP Service Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard">OTP Service Customer</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a class="nav-link" href="../logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>View OTP Message Template</h1>
                <a href="dashboard" class="btn btn-secondary mb-3">← Back to Dashboard</a>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- OTP Message Template View (read-only) -->
                <div class="card">
                    <div class="card-header">
                        <h5>Your OTP Message Template</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Message templates can only be managed by the admin. Contact your administrator to update your template.</p>

                        <div class="mb-3">
                            <label for="message_template" class="form-label">Message Template</label>
                            <textarea class="form-control" id="message_template" name="message_template" rows="4" placeholder="Your OTP code is: {OTP}" readonly><?php echo htmlspecialchars($template['message_template'] ?? ''); ?></textarea>
                            <div class="form-text">Use {OTP} as a placeholder for the OTP code (default: {OTP})</div>
                        </div>
                        <div class="mb-3">
                            <label for="placeholder" class="form-label">Placeholder</label>
                            <input type="text" class="form-control" id="placeholder" name="placeholder" value="<?php echo htmlspecialchars($template['placeholder'] ?? '{OTP}'); ?>" readonly>
                            <div class="form-text">The placeholder that will be replaced with the OTP code (default: {OTP})</div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>Template Preview</h5>
                    </div>
                    <div class="card-body">
                        <p>When you generate an OTP, it will be sent with a message like this:</p>
                        <div class="border p-3 bg-light">
                            <?php
                            $preview_message = $template ? str_replace($template['placeholder'] ?? '{OTP}', '123456', $template['message_template'] ?? 'Your OTP code is: {OTP}') : 'Your OTP code is: 123456';
                            echo htmlspecialchars($preview_message);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>