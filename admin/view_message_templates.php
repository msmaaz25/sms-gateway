<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireAdmin();

require_once '../models/OTP.php';
require_once '../models/User.php';

$otpModel = new OTP();
$userModel = new User();
$message = '';

// Get all users to show their templates
$users = $userModel->getAllCustomers();

// Get message template if a user is selected
$selected_user_id = $_GET['user_id'] ?? null;
$template = null;

if ($selected_user_id) {
    $template = $otpModel->getOTPMessageTemplate($selected_user_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All OTP Message Templates - OTP Service Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">OTP Service Admin</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a class="nav-link" href="../logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Manage All OTP Message Templates</h1>
                <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

                <!-- User Selection -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Select Customer</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="mb-3">
                                <label for="user_id" class="form-label">Customer</label>
                                <select class="form-control" id="user_id" name="user_id" onchange="this.form.submit()">
                                    <option value="">Select a customer to view their template</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user['id']; ?>"
                                            <?php echo ($selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($selected_user_id): ?>
                    <!-- Template for Selected User -->
                    <div class="card">
                        <div class="card-header">
                            <h5>OTP Message Template for <?php echo htmlspecialchars($userModel->getUserById($selected_user_id)['username']); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php if ($template): ?>
                                <div class="mb-3">
                                    <label class="form-label">Message Template</label>
                                    <div class="form-control bg-light"><?php echo htmlspecialchars($template['message_template']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Placeholder</label>
                                    <div class="form-control bg-light"><?php echo htmlspecialchars($template['placeholder']); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Created</label>
                                    <div class="form-control bg-light"><?php echo date('Y-m-d H:i:s', strtotime($template['created_at'])); ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Last Updated</label>
                                    <div class="form-control bg-light"><?php echo date('Y-m-d H:i:s', strtotime($template['updated_at'])); ?></div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    This user has not set an OTP message template yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Please select a customer to view their OTP message template.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>