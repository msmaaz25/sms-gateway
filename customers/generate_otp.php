<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireCustomer();

require_once '../models/OTP.php';
require_once '../includes/utils.php';

$otpModel = new OTP();
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone_number = sanitizeInput($_POST['phone_number']);
    $purpose = sanitizeInput($_POST['purpose']);

    if (empty($phone_number)) {
        $message = 'Phone number is required';
    } elseif (!validatePhoneNumber($phone_number)) {
        $message = 'Invalid phone number format';
    } else {
        try {
            $result = $otpModel->generateOTP($_SESSION['user_id'], $phone_number, $purpose, 10, null, null);
            if ($result['success']) {
                $message = 'OTP generated and sent successfully: ' . $result['otp_code'] . '. Expires at: ' . date('Y-m-d H:i:s', strtotime($result['expires_at']));
            } else {
                $message = 'Failed to generate OTP';
            }
        } catch (Exception $e) {
            $message = 'Error generating OTP: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate OTP - OTP Service Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">OTP Service Customer</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Generate New OTP</h1>
                <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

                <?php if (!empty($message)): ?>
                    <div class="alert
                        <?php
                            if (strpos($message, 'successfully') !== false) echo 'alert-success';
                            else echo 'alert-danger';
                        ?>
                    ">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>

                <!-- Generate OTP Form -->
                <div class="card">
                    <div class="card-header">
                        <h5>Generate OTP</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number" placeholder="+1234567890" required>
                                <div class="form-text">Enter phone number in international format</div>
                            </div>
                            <div class="mb-3">
                                <label for="purpose" class="form-label">Purpose (Optional)</label>
                                <input type="text" class="form-control" id="purpose" name="purpose" placeholder="Purpose for OTP">
                                <div class="form-text">Describe why you need this OTP</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Generate OTP</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header">
                        <h5>API Usage</h5>
                    </div>
                    <div class="card-body">
                        <p>You can also generate OTPs using our API:</p>
                        <code>POST /api/generate_otp</code>
                        <p class="mt-2">Headers:</p>
                        <ul>
                            <li>Content-Type: application/json</li>
                            <li>Authorization: Bearer YOUR_API_KEY</li>
                        </ul>
                        <p>Body:</p>
                        <pre>{
  "phone_number": "+1234567890",
  "purpose": "Login verification"
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>