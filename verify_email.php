<?php
/*
Name of file: /verify_email.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Email verification page
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'Verify Email - Art Auction';

// Check if token is provided
$token = isset($_GET['token']) ? $_GET['token'] : '';
$verified = false;
$error = '';

if(!empty($token)) {
    try {
        // Database connection
        $database = new Database();
        $db = $database->connect();
        
        // Create user object
        $user = new User($db);
        
        // Verify token
        if($user->verify_token($token)) {
            // Set user as verified
            if($user->verify()) {
                $verified = true;
                
                // Set session message
                $_SESSION['message'] = 'Your email has been verified successfully! You can now log in.';
                $_SESSION['message_type'] = 'success';
            } else {
                $error = 'Failed to verify email. Please try again.';
            }
        } else {
            $error = 'Invalid or expired verification token. Please request a new one.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . $e->getMessage();
        error_log("Verification error: " . $e->getMessage());
    }
} else {
    $error = 'No verification token provided.';
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Email Verification</h3>
                </div>
                <div class="card-body text-center">
                    <?php if($verified): ?>
                        <div class="mb-4">
                            <i class="fas fa-check-circle text-success fa-5x"></i>
                        </div>
                        <h4>Email Verified Successfully!</h4>
                        <p>Your email has been verified. You can now log in to your account.</p>
                        <a href="login.php" class="btn btn-primary">Login</a>
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-danger fa-5x"></i>
                        </div>
                        <h4>Verification Failed</h4>
                        <p><?php echo $error; ?></p>
                        <a href="resend_verification.php" class="btn btn-primary">Request New Verification</a>
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
