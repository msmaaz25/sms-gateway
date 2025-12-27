<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OTP Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <h2 class="text-center mb-4">OTP Service Login</h2>
            
            <?php
            require_once 'config/config.php';
            require_once 'includes/auth.php';
            
            $error = '';
            
            if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $username = sanitizeInput($_POST['username']);
                $password = $_POST['password'];
                
                if (empty($username) || empty($password)) {
                    $error = 'Please fill in all fields';
                } else {
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
            
            <div class="text-center mt-3">
                <small>Default admin: admin / admin123</small>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>