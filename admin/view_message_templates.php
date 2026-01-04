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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = (int)$_POST['user_id'];
    $message_template = sanitizeInput($_POST['message_template']);
    $placeholder = sanitizeInput($_POST['placeholder'] ?? '{OTP}');

    if (empty($message_template)) {
        $message = 'Message template is required';
    } else {
        try {
            // Check if template already exists for this user
            $existing = $otpModel->getOTPMessageTemplate($user_id);
            if ($existing) {
                // Update existing template
                $otpModel->updateOTPMessageTemplateByUser($user_id, $message_template, $placeholder);
                $message = 'OTP message template updated successfully';
            } else {
                // Create new template
                $otpModel->createOTPMessageTemplate($user_id, $message_template, $placeholder);
                $message = 'OTP message template created successfully';
            }
        } catch (Exception $e) {
            $message = 'Error saving OTP message template: ' . $e->getMessage();
        }
    }
}

// Get all users to show their templates
$users = $userModel->getAllCustomers();

// Get message template if a user is selected
$selected_user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
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
            <a class="navbar-brand" href="dashboard">OTP Service Admin</a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Welcome, <?php echo $_SESSION['username']; ?>!</span>
                <a class="nav-link" href="../logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Manage All OTP Message Templates</h1>
                <a href="dashboard" class="btn btn-secondary mb-3">← Back to Dashboard</a>

                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>

                <!-- User Selection and Template Management -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Manage Customer Template</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">Customer</label>
                                        <select class="form-control" id="user_id" name="user_id" required>
                                            <option value="">Select a customer</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                    <?php echo ($selected_user_id == $user['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['username']); ?> (<?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message_template" class="form-label">Message Template</label>
                                <textarea class="form-control" id="message_template" name="message_template" rows="4" placeholder="Your OTP code is: {OTP}"><?php echo htmlspecialchars($template['message_template'] ?? ''); ?></textarea>
                                <div class="form-text">Use {OTP} as a placeholder for the OTP code (default: {OTP})</div>
                            </div>
                            <div class="mb-3">
                                <label for="placeholder" class="form-label">Placeholder</label>
                                <input type="text" class="form-control" id="placeholder" name="placeholder" value="<?php echo htmlspecialchars($template['placeholder'] ?? '{OTP}'); ?>">
                                <div class="form-text">The placeholder that will be replaced with the OTP code (default: {OTP})</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Template</button>
                        </form>
                    </div>
                </div>

                <?php if ($selected_user_id && $template): ?>
                    <!-- Template Preview -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Template Preview</h5>
                        </div>
                        <div class="card-body">
                            <p>When this customer generates an OTP, it will be sent with a message like this:</p>
                            <div class="border p-3 bg-light">
                                <?php
                                $preview_message = str_replace($template['placeholder'] ?? '{OTP}', '123456', $template['message_template'] ?? 'Your OTP code is: {OTP}');
                                echo htmlspecialchars($preview_message);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('user_id').addEventListener('change', function() {
            // When user changes, reload the page to show the template for that user
            if (this.value) {
                window.location.href = '?user_id=' + this.value;
            }
        });
    </script>
</body>
</html>