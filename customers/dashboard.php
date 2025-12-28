<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db_setup.php';

// If not logged in, redirect to main login
if (!isLoggedIn()) {
    header("Location: ../index.php");
    exit();
}

// If logged in but not customer, redirect appropriately
if (!isCustomer()) {
    if (isAdmin()) {
        header("Location: ../admin/dashboard.php");
    } else {
        header("Location: ../index.php");
    }
    exit();
}

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/OTP.php';

$userModel = new User();
$otpModel = new OTP();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['phone_number'])) {
        // Generate OTP form submission
        $phone_number = sanitizeInput($_POST['phone_number']);
        $purpose = sanitizeInput($_POST['purpose'] ?? '');

        if (empty($phone_number)) {
            $generate_otp_message = 'Phone number is required';
            $generate_otp_success = false;
        } elseif (!validatePhoneNumber($phone_number)) {
            $generate_otp_message = 'Invalid phone number format';
            $generate_otp_success = false;
        } else {
            try {
                $result = $otpModel->generateOTP($_SESSION['user_id'], $phone_number, $purpose, 10);
                if ($result['success']) {
                    $generate_otp_message = 'OTP generated and sent successfully: ' . $result['otp_code'] . '. Expires at: ' . date('Y-m-d H:i:s', strtotime($result['expires_at']));
                    $generate_otp_success = true;
                    // Refresh OTP requests
                    $otp_requests = $otpModel->getOTPRequests($_SESSION['user_id']);
                } else {
                    $generate_otp_message = $result['message'];
                    $generate_otp_success = false;
                }
            } catch (Exception $e) {
                $generate_otp_message = 'Error generating OTP: ' . $e->getMessage();
                $generate_otp_success = false;
            }
        }
    } elseif (isset($_POST['message_template'])) {
        // Manage template form submission
        $message_template = sanitizeInput($_POST['message_template']);
        $placeholder = sanitizeInput($_POST['placeholder'] ?? '{OTP}');

        if (empty($message_template)) {
            $template_message = 'Message template is required';
            $template_success = false;
        } else {
            try {
                // Check if template already exists for this user
                $existing = $otpModel->getOTPMessageTemplate($_SESSION['user_id']);
                if ($existing) {
                    // Update existing template
                    $otpModel->updateOTPMessageTemplateByUser($_SESSION['user_id'], $message_template, $placeholder);
                    $template_message = 'OTP message template updated successfully';
                } else {
                    // Create new template
                    $otpModel->createOTPMessageTemplate($_SESSION['user_id'], $message_template, $placeholder);
                    $template_message = 'OTP message template created successfully';
                }
                $template_success = true;
            } catch (Exception $e) {
                $template_message = 'Error saving OTP message template: ' . $e->getMessage();
                $template_success = false;
            }
        }
    }
}

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
                <button class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <i class="fas fa-user"></i> Profile
                </button>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
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
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Today's OTPs</h5>
                        <h2><?php echo count($today_otp_requests); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total OTPs</h5>
                        <h2><?php echo count($otp_requests); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Quota Info</h5>
                        <?php
                        $userModel = new User();
                        $quota_info = $userModel->getUserQuotaInfo($_SESSION['user_id']);
                        $monthly_quota = $quota_info['otp_monthly_quota'] ?? 0;
                        $used_current_month = $quota_info['otp_used_current_month'] ?? 0;
                        $remaining = max(0, $monthly_quota - $used_current_month);
                        ?>
                        <p class="card-text">
                            Used: <?php echo $used_current_month; ?>/<?php echo $monthly_quota; ?><br>
                            Remaining: <?php echo $remaining; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Navigation -->


        <!-- OTP Requests -->
        <div class="card">
            <div class="card-header">
                <h5>Your OTP Requests</h5>
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

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="profileModalLabel">Profile & API Configuration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Profile Tab Navigation -->
                    <ul class="nav nav-tabs" id="profileTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="template-tab" data-bs-toggle="tab" data-bs-target="#template" type="button" role="tab">Message Template</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="api-tab" data-bs-toggle="tab" data-bs-target="#api" type="button" role="tab">API Configuration</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content mt-3" id="profileTabContent">
                        <!-- Profile Tab -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Username</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Company Name</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($user['company_name'] ?? 'N/A'); ?>" readonly>
                            </div>

                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>

                        <!-- Message Template Tab -->
                        <div class="tab-pane fade" id="template" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">Message Template</label>
                                <?php
                                $template = $otpModel->getOTPMessageTemplate($_SESSION['user_id']);
                                ?>
                                <textarea class="form-control bg-light" rows="4" readonly><?php echo htmlspecialchars($template['message_template'] ?? 'Default: Your OTP code is: {OTP}'); ?></textarea>
                                <div class="form-text">Your OTP message template (use Manage Templates from dashboard to edit)</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Placeholder</label>
                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($template['placeholder'] ?? '{OTP}'); ?>" readonly>
                                <div class="form-text">The placeholder that will be replaced with the OTP code</div>
                            </div>
                            <div class="alert alert-info">
                                <small>To modify your message template, please contact company authorities.</small>
                            </div>
                        </div>

                        <!-- API Configuration Tab -->
                        <div class="tab-pane fade" id="api" role="tabpanel">
                            <div class="mb-3">
                                <label class="form-label">API Key</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="apiKey" value="<?php echo $user['api_key']; ?>" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('apiKey')">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                            </div>

                            <h6>API Endpoints</h6>
                            <div class="mb-3">
                                <label class="form-label">Generate OTP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" value="<?php echo BASE_URL; ?>/api/generate_otp" readonly>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#generateOtpTest" aria-expanded="false" aria-controls="generateOtpTest">
                                        Test
                                    </button>
                                </div>
                            </div>

                            <!-- Generate OTP Test Section -->
                            <div class="collapse" id="generateOtpTest">
                                <div class="card card-body mt-2">
                                    <h6>Test Generate OTP API</h6>
                                    <div class="mb-2">
                                        <label class="form-label">Method</label>
                                        <input type="text" class="form-control bg-light" value="POST" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Headers</label>
                                        <input type="text" class="form-control bg-light" value="Content-Type: application/json" readonly>
                                        <input type="text" class="form-control bg-light mt-1" value="Authorization: Bearer <?php echo $user['api_key']; ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Request Body</label>
                                        <textarea class="form-control" id="generateOtpBody" rows="3">{"phone_number": "+1234567890", "purpose": "Test OTP"}</textarea>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-success" onclick="testApi('<?php echo BASE_URL; ?>/api/generate_otp', 'POST', document.getElementById('generateOtpBody').value, 'generateOtpResult')">Run Test</button>
                                        <button class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#generateOtpTest">Close</button>
                                    </div>
                                    <div id="generateOtpResult" class="mt-2"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Verify OTP</label>
                                <div class="input-group">
                                    <input type="text" class="form-control bg-light" value="<?php echo BASE_URL; ?>/api/verify_otp" readonly>
                                    <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#verifyOtpTest" aria-expanded="false" aria-controls="verifyOtpTest">
                                        Test
                                    </button>
                                </div>
                            </div>

                            <!-- Verify OTP Test Section -->
                            <div class="collapse" id="verifyOtpTest">
                                <div class="card card-body mt-2">
                                    <h6>Test Verify OTP API</h6>
                                    <div class="mb-2">
                                        <label class="form-label">Method</label>
                                        <input type="text" class="form-control bg-light" value="POST" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Headers</label>
                                        <input type="text" class="form-control bg-light" value="Content-Type: application/json" readonly>
                                        <input type="text" class="form-control bg-light mt-1" value="Authorization: Bearer <?php echo $user['api_key']; ?>" readonly>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Request Body</label>
                                        <textarea class="form-control" id="verifyOtpBody" rows="3">{"otp_code": "123456"}</textarea>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <button class="btn btn-success" onclick="testApi('<?php echo BASE_URL; ?>/api/verify_otp', 'POST', document.getElementById('verifyOtpBody').value, 'verifyOtpResult')">Run Test</button>
                                        <button class="btn btn-secondary" data-bs-toggle="collapse" data-bs-target="#verifyOtpTest">Close</button>
                                    </div>
                                    <div id="verifyOtpResult" class="mt-2"></div>
                                </div>
                            </div>

                            <h6 class="mt-3">API Test</h6>
                            <div class="alert alert-info">
                                <small>Use the API endpoints above with proper authentication headers to test your API calls.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="changePasswordForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="currentPassword" name="currentPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirmNewPassword" name="confirmNewPassword" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            element.select();
            document.execCommand('copy');

            // Show feedback
            const originalValue = element.value;
            element.value = 'Copied!';
            setTimeout(() => {
                element.value = originalValue;
            }, 1000);
        }

        // Handle password change form
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const currentPassword = formData.get('currentPassword');
            const newPassword = formData.get('newPassword');
            const confirmNewPassword = formData.get('confirmNewPassword');

            if (newPassword !== confirmNewPassword) {
                alert('New passwords do not match!');
                return;
            }

            // Send request to change password API
            fetch('../api/change_password', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Password updated successfully!');
                    // Reset form
                    document.getElementById('changePasswordForm').reset();
                    // Close the modal
                    const passwordModal = bootstrap.Modal.getInstance(document.getElementById('changePasswordModal'));
                    passwordModal.hide();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error.message);
            });
        });

        // Function to test API
        function testApi(url, method, body, resultDivId) {
            const resultDiv = document.getElementById(resultDivId);
            resultDiv.innerHTML = '<div class="alert alert-info">Testing API...</div>';

            fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer <?php echo $user['api_key']; ?>'
                },
                body: body
            })
            .then(response => response.json())
            .then(data => {
                let responseClass = data.success ? 'alert-success' : 'alert-danger';
                resultDiv.innerHTML = `
                    <div class="alert ${responseClass}">
                        <h6>Response:</h6>
                        <pre>${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            })
            .catch(error => {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6>Error:</h6>
                        <p>${error.message}</p>
                    </div>
                `;
            });
        }

        // Handle form submissions to prevent page reload
        document.getElementById('generateOtpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // This form will submit normally since we're handling it server-side
            this.submit();
        });

        document.getElementById('manageTemplateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // This form will submit normally since we're handling it server-side
            this.submit();
        });
    </script>
</body>
</html>