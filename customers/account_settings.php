<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireCustomer();

require_once '../models/User.php';

$userModel = new User();
$message = '';

// Get current user
$user = $userModel->getUserById($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Support AJAX JSON requests (from dashboard) — return JSON responses
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        $current_password = $input['current_password'] ?? '';
        $new_password = $input['new_password'] ?? '';

        if (empty($current_password) || empty($new_password)) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
            exit;
        }

        if (strlen($new_password) < 6) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }

        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $query = "UPDATE users SET password = ? WHERE id = ?";
                $conn = getConnection();
                $stmt = $conn->prepare($query);
                $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
                exit;
            } catch (Exception $e) {
                header('Content-Type: application/json');
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Error changing password: ' . $e->getMessage()]);
                exit;
            }
        } else {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
    }
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $username = sanitizeInput($_POST['username']);
        $email = sanitizeInput($_POST['email']);
        $company_name = sanitizeInput($_POST['company_name']);
        
        if (empty($username) || empty($email)) {
            $message = 'Username and email are required';
        } else {
            try {
                $userModel->updateUser($_SESSION['user_id'], $username, $email, $company_name);
                
                // Update session data
                $_SESSION['username'] = $username;
                $_SESSION['company_name'] = $company_name;
                
                $user = $userModel->getUserById($_SESSION['user_id']);
                $message = 'Profile updated successfully';
            } catch (Exception $e) {
                $message = 'Error updating profile: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = 'Please fill in all password fields';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $message = 'New password must be at least 6 characters';
        } else {
            // Verify current password
            if (password_verify($current_password, $user['password'])) {
                try {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $query = "UPDATE users SET password = ? WHERE id = ?";
                    $conn = getConnection();
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $message = 'Password changed successfully';
                } catch (Exception $e) {
                    $message = 'Error changing password: ' . $e->getMessage();
                }
            } else {
                $message = 'Current password is incorrect';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - OTP Service Customer</title>
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
                <h1>Account Settings</h1>
                <a href="dashboard" class="btn btn-secondary mb-3">← Back to Dashboard</a>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="action" value="update_profile">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="company_name" class="form-label">Company Name</label>
                                        <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($user['company_name'] ?? ''); ?>">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- API Key and Security -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>API Key & Security</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="api_key" class="form-label">Your API Key</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="api_key" value="<?php echo $user['api_key']; ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard()">Copy</button>
                                    </div>
                                    <div class="form-text">Use this API key to authenticate API requests</div>
                                </div>
                                
                                <form method="POST" class="mt-4">
                                    <input type="hidden" name="action" value="change_password">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" class="btn btn-warning">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard() {
            const apiKeyInput = document.getElementById('api_key');
            apiKeyInput.select();
            document.execCommand('copy');
            
            // Show feedback
            const originalValue = apiKeyInput.value;
            apiKeyInput.value = 'Copied!';
            setTimeout(() => {
                apiKeyInput.value = originalValue;
            }, 1000);
        }
    </script>
</body>
</html>