<?php
/*
Name of file: /reset_password.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Password reset page
*/

// Start session
session_start();

// If user is already logged in, redirect to dashboard
if(isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'Reset Password - Art Auction';

// Check if token is provided
if(!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: forgot_password.php');
    exit();
}

$token = $_GET['token'];
$valid_token = true; // In a real app, validate token from database

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate form data
    if(empty($password)) {
        $errors[] = 'Password is required';
    } elseif(strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    
    if($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    // If no errors, proceed with password reset
    if(empty($errors)) {
        // In a real application, you would:
        // 1. Verify the token is valid and not expired
        // 2. Get the user associated with the token
        // 3. Update the user's password
        // 4. Delete the used token
        
        // For this example, we'll just show success
        $success = true;
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Reset Password</h3>
                </div>
                <div class="card-body">
                    <?php if(!$valid_token): ?>
                        <div class="alert alert-danger">
                            Invalid or expired password reset token. Please request a new password reset.
                        </div>
                        <div class="text-center mt-3">
                            <a href="forgot_password.php" class="btn btn-primary">Request New Reset</a>
                        </div>
                    <?php elseif($success): ?>
                        <div class="alert alert-success">
                            <p>Your password has been successfully reset!</p>
                            <p>You can now log in with your new password.</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Login</a>
                        </div>
                    <?php else: ?>
                        <?php if(!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <p>Please enter your new password below.</p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . $token); ?>" method="post">
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Reset Password</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
