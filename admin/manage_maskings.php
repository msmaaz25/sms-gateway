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
    header("Location: ../login.php");
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

require_once __DIR__ . '/../models/Masking.php';
require_once __DIR__ . '/../models/User.php';

$maskingModel = new Masking();
$userModel = new User();

// Handle form submissions
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $masking_code = sanitizeInput($_POST['masking_code']);

                if (empty($masking_code)) {
                    $message = 'Masking code is required';
                    $success = false;
                } elseif (!$maskingModel->validateMaskingCode($masking_code)) {
                    $message = 'Masking code must be alphanumeric and not exceed 50 characters';
                    $success = false;
                } else {
                    try {
                        if ($maskingModel->createMasking($masking_code)) {
                            $message = 'Masking created successfully';
                            $success = true;
                        } else {
                            $message = 'Failed to create masking';
                            $success = false;
                        }
                    } catch (Exception $e) {
                        $message = $e->getMessage();
                        $success = false;
                    }
                }
                break;

            case 'assign':
                $masking_id = (int)$_POST['masking_id'];
                $user_id = (int)$_POST['user_id'];

                try {
                    if ($maskingModel->assignMaskingToUser($masking_id, $user_id)) {
                        $message = 'Masking assigned to user successfully';
                        $success = true;
                    } else {
                        $message = 'Failed to assign masking to user';
                        $success = false;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $success = false;
                }
                break;

            case 'remove_assignment':
                $masking_id = (int)$_POST['masking_id'];

                try {
                    if ($maskingModel->removeMaskingFromUser($masking_id)) {
                        $message = 'Masking assignment removed successfully';
                        $success = true;
                    } else {
                        $message = 'Failed to remove masking assignment';
                        $success = false;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $success = false;
                }
                break;

            case 'update_status':
                $masking_id = (int)$_POST['masking_id'];
                $is_active = (int)$_POST['is_active'];

                try {
                    if ($maskingModel->updateMaskingStatus($masking_id, $is_active)) {
                        $message = 'Masking status updated successfully';
                        $success = true;
                    } else {
                        $message = 'Failed to update masking status';
                        $success = false;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $success = false;
                }
                break;

            case 'delete':
                $masking_id = (int)$_POST['masking_id'];

                try {
                    if ($maskingModel->deleteMasking($masking_id)) {
                        $message = 'Masking deleted successfully';
                        $success = true;
                    } else {
                        $message = 'Failed to delete masking';
                        $success = false;
                    }
                } catch (Exception $e) {
                    $message = $e->getMessage();
                    $success = false;
                }
                break;
        }
    }
}

// Get all maskings and users
$maskings = $maskingModel->getAllMaskings();
$users = $userModel->getAllCustomers(); // Only customers can have maskings assigned
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Maskings - OTP Service Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
    </style>
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
                <h1>Manage Maskings</h1>
                <a href="dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>

                <?php if (!empty($message)): ?>
                    <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Create New Masking Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Add New Masking</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="create">
                            <div class="mb-3">
                                <label for="masking_code" class="form-label">Masking Code</label>
                                <input type="text" class="form-control" id="masking_code" name="masking_code"
                                       placeholder="Enter alphanumeric masking code (e.g., MyCompany123)" required>
                                <div class="form-text">Alphanumeric characters only, max 50 characters</div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Create Masking
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Maskings List -->
                <div class="card">
                    <div class="card-header">
                        <h5>Maskings List</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($maskings)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No maskings found. Create your first masking above.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Masking Code</th>
                                            <th>Assigned To</th>
                                            <th>Status</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maskings as $masking): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($masking['id']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($masking['masking_code']); ?></strong></td>
                                            <td>
                                                <?php if ($masking['user_id']): ?>
                                                    <span class="badge bg-success">
                                                        <?php echo htmlspecialchars($masking['assigned_user'] ?? 'Unknown'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Unassigned</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $masking['is_active'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $masking['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('Y-m-d H:i:s', strtotime($masking['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <!-- Assign to User Button -->
                                                    <?php if (!$masking['user_id']): ?>
                                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#assignModal<?php echo $masking['id']; ?>">
                                                            <i class="fas fa-user-plus"></i> Assign
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-outline-warning btn-sm"
                                                                data-bs-toggle="modal" data-bs-target="#removeModal<?php echo $masking['id']; ?>">
                                                            <i class="fas fa-user-slash"></i> Unassign
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Toggle Status -->
                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to change the status?');">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="masking_id" value="<?php echo $masking['id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $masking['is_active'] ? 0 : 1; ?>">
                                                        <button type="submit" class="btn btn-outline-<?php echo $masking['is_active'] ? 'warning' : 'success'; ?> btn-sm">
                                                            <i class="fas fa-<?php echo $masking['is_active'] ? 'times' : 'check'; ?>"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Delete -->
                                                    <form method="POST" class="d-inline"
                                                          onsubmit="return confirm('Are you sure you want to delete this masking? This action cannot be undone.');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="masking_id" value="<?php echo $masking['id']; ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Assign Modal -->
                                        <div class="modal fade" id="assignModal<?php echo $masking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Assign Masking to User</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="assign">
                                                            <input type="hidden" name="masking_id" value="<?php echo $masking['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Masking Code</label>
                                                                <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($masking['masking_code']); ?>" readonly>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="user_id_<?php echo $masking['id']; ?>" class="form-label">Select User</label>
                                                                <select class="form-select" id="user_id_<?php echo $masking['id']; ?>" name="user_id" required>
                                                                    <option value="">Select a user...</option>
                                                                    <?php foreach ($users as $user): ?>
                                                                        <option value="<?php echo $user['id']; ?>">
                                                                            <?php echo htmlspecialchars($user['username'] . ' (' . $user['company_name'] . ')'); ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">Assign</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Remove Assignment Modal -->
                                        <div class="modal fade" id="removeModal<?php echo $masking['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Remove Masking Assignment</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="action" value="remove_assignment">
                                                            <input type="hidden" name="masking_id" value="<?php echo $masking['id']; ?>">
                                                            <p>Are you sure you want to remove the assignment of masking <strong><?php echo htmlspecialchars($masking['masking_code']); ?></strong> from user <strong><?php echo htmlspecialchars($masking['assigned_user']); ?></strong>?</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-warning">Remove Assignment</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>