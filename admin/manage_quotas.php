<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
Auth::requireLogin();
Auth::requireAdmin();

require_once '../models/User.php';

$userModel = new User();
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $user_id = (int)$_POST['user_id'];
        
        if ($_POST['action'] === 'update_quota') {
            $new_quota = (int)$_POST['quota'];
            
            try {
                $userModel->updateUserQuota($user_id, $new_quota);
                $message = 'OTP quota updated successfully';
            } catch (Exception $e) {
                $message = 'Error updating OTP quota: ' . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'add_quota') {
            $additional_quota = (int)$_POST['additional_quota'];
            
            try {
                $userModel->addToUserQuota($user_id, $additional_quota);
                $message = 'Additional OTP quota added successfully';
            } catch (Exception $e) {
                $message = 'Error adding OTP quota: ' . $e->getMessage();
            }
        }
    }
}

// Get all customers
$customers = $userModel->getAllCustomers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer OTP Quotas - OTP Service Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
                <h1><i class="fas fa-ticket-alt me-2"></i>Manage Customer OTP Quotas</h1>
                <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
                
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Customer OTP Quotas</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Company</th>
                                        <th>Monthly Quota</th>
                                        <th>Used This Month</th>
                                        <th>Remaining</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                        <?php 
                                        $quota_info = $userModel->getUserQuotaInfo($customer['id']);
                                        $monthly_quota = $quota_info['otp_monthly_quota'] ?? 0;
                                        $used_current_month = $quota_info['otp_used_current_month'] ?? 0;
                                        $remaining = $monthly_quota - $used_current_month;
                                        ?>
                                        <tr>
                                            <td><?php echo $customer['id']; ?></td>
                                            <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                            <td><?php echo htmlspecialchars($customer['company_name'] ?? 'N/A'); ?></td>
                                            <td><?php echo $monthly_quota; ?></td>
                                            <td><?php echo $used_current_month; ?></td>
                                            <td><?php echo $remaining; ?></td>
                                            <td>
                                                <!-- Update Quota Form -->
                                                <form method="POST" class="d-inline mb-2" style="width: 180px;">
                                                    <input type="hidden" name="action" value="update_quota">
                                                    <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control form-control-sm" name="quota" value="<?php echo $monthly_quota; ?>" min="0" required>
                                                        <button class="btn btn-primary btn-sm" type="submit">Set</button>
                                                    </div>
                                                </form>
                                                <br>
                                                <!-- Add Quota Form -->
                                                <form method="POST" class="d-inline" style="width: 180px;">
                                                    <input type="hidden" name="action" value="add_quota">
                                                    <input type="hidden" name="user_id" value="<?php echo $customer['id']; ?>">
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" class="form-control form-control-sm" name="additional_quota" placeholder="Add quota" min="1" required>
                                                        <button class="btn btn-success btn-sm" type="submit">Add</button>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No customers found</td>
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