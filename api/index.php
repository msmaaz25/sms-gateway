<?php
require_once '../config/config.php';

// Allow access only to logged-in customers. Redirect others to customer login.
if (!isLoggedIn() || !isCustomer()) {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation - OTP Service</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">OTP Service</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../login.php">Login</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>OTP Service API Documentation</h1>
                <p class="lead">Documentation for the OTP Service API endpoints</p>
                
                <div class="alert alert-info">
                    To use the API, you need to obtain an API key from your account settings after logging in.
                </div>
                
                <h3 class="mt-4">Authentication</h3>
                <p>All API requests require an API key to be included in the Authorization header:</p>
                <pre>Authorization: Bearer YOUR_API_KEY</pre>
                
                <h3 class="mt-4">API Endpoints</h3>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Generate OTP</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>POST</strong> <code>/api/generate_otp</code></p>
                        <p>Generate a new OTP for a phone number. Optionally specify a mask for the SMS sender ID.</p>
                        
                        <h6>Headers:</h6>
                        <ul>
                            <li>Content-Type: application/json</li>
                            <li>Authorization: Bearer YOUR_API_KEY</li>
                        </ul>
                        
                        <h6>Body:</h6>
                        <pre>{
  "phone_number": "+1234567890",
  "purpose": "Login verification",
  "mask": "YourMaskName" (optional)
}</pre>
                        
                        <h6>Response:</h6>
                        <pre>{
  "success": true,
  "message": "OTP sent successfully",
  "otp_code": "123456",
  "message_sent": true (indicates if SMS was sent successfully)
}</pre>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h5>Verify OTP</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>POST</strong> <code>/api/verify_otp</code></p>
                        <p>Verify an OTP code for a specific phone number. Only verifies the latest OTP sent to the phone number from the same user.</p>
                        
                        <h6>Headers:</h6>
                        <ul>
                            <li>Content-Type: application/json</li>
                            <li>Authorization: Bearer YOUR_API_KEY</li>
                        </ul>
                        
                        <h6>Body:</h6>
                        <pre>{
  "number": "+1234567890",
  "otp": "123456"
}</pre>
                        
                        <h6>Response:</h6>
                        <pre>{
  "success": true,
  "message": "OTP verified successfully"
}</pre>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>