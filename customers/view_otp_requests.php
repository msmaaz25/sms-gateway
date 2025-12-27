<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireCustomer();

require_once '../models/OTP.php';

$otpModel = new OTP();

// Get OTP requests for current user
$otp_requests = $otpModel->getOTPRequests($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View OTP Requests - OTP Service Customer</title>
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
                <h1>Your OTP Requests</h1>
                <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
                
                <!-- OTP Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <h5>OTP Request History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Phone Number</th>
                                        <th>OTP Code</th>
                                        <th>Purpose</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Expires</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($otp_requests as $request): ?>
                                    <tr>
                                        <td><?php echo $request['id']; ?></td>
                                        <td><?php echo htmlspecialchars($request['phone_number']); ?></td>
                                        <td><?php echo htmlspecialchars($request['otp_code']); ?></td>
                                        <td><?php echo htmlspecialchars($request['otp_purpose'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge 
                                                <?php 
                                                    if($request['status'] === 'verified') echo 'bg-success';
                                                    elseif($request['status'] === 'expired') echo 'bg-danger';
                                                    else echo 'bg-warning';
                                                ?>
                                            ">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($request['created_at'])); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($request['expires_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($otp_requests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No OTP requests found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>