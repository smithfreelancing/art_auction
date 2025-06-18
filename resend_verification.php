<?php
/*
Name of file: /resend_verification.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Resend verification email
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'Resend Verification - Art Auction';

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = clean_input($_POST['email'] ?? '');
    
    // Validate form data
    if(empty($email)) {
        $errors[] = 'Email is required';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    // If no errors, proceed
    if(empty($errors)) {
        try {
            // Database connection
            $database = new Database();
            $db = $database->connect();
            
            // Create user object
            $user = new User($db);
            $user->email = $email;
            
            // Check if email exists
            if($user->get_by_email()) {
                // Check if user is already verified
                if($user->is_verified()) {
                    $errors[] = 'This email is already verified. You can log in to your account.';
                } else {
                    // Delete existing tokens
                    $user->delete_all_verification_tokens();
                    
                    // Create new verification token
                    $token = $user->create_verification_token();
                    
                    if($token) {
                        // Prepare verification email
                        $verification_link = 'https://' . $_SERVER['HTTP_HOST'] . '/verify_email.php?token=' . $token;
                        
                        // Email content
                        $subject = 'Verify Your Email - Art Auction';
                        $message = '
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1.0">
                            <title>Verify Your Email</title>
                            <style>
                                body {
                                    font-family: Arial, sans-serif;
                                    line-height: 1.6;
                                    color: #333;
                                    margin: 0;
                                    padding: 0;
                                }
                                .container {
                                    max-width: 600px;
                                    margin: 0 auto;
                                    padding: 20px;
                                }
                                .header {
                                    background-color: #4e73df;
                                    color: white;
                                    padding: 20px;
                                    text-align: center;
                                }
                                .content {
                                    padding: 20px;
                                    background-color: #f8f9fc;
                                }
                                .button {
                                    display: inline-block;
                                    padding: 10px 20px;
                                    background-color: #4e73df;
                                    color: white;
                                    text-decoration: none;
                                    border-radius: 5px;
                                    margin: 20px 0;
                                }
                                .footer {
                                    text-align: center;
                                    padding: 20px;
                                    font-size: 12px;
                                    color: #666;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <div class="header">
                                    <h2>Verify Your Email Address</h2>
                                </div>
                                <div class="content">
                                    <p>Hello ' . htmlspecialchars($user->username) . ',</p>
                                    <p>You requested a new verification email. To verify your email address, please click the button below:</p>
                                    <p style="text-align: center;">
                                        <a href="' . $verification_link . '" class="button">Verify Email Address</a>
                                    </p>
                                    <p>Or copy and paste this link into your browser:</p>
                                    <p>' . $verification_link . '</p>
                                    <p>This link will expire in 24 hours.</p>
                                    <p>If you did not request this email, please ignore it.</p>
                                </div>
                                <div class="footer">
                                    <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
                                    <p>This is an automated email, please do not reply.</p>
                                </div>
                            </div>
                        </body>
                        </html>';
                        
                        // Set headers
                        $headers = "MIME-Version: 1.0\r\n";
                        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                        $headers .= "From: Art Auction <admin@artmichels.com>\r\n";
                        
                        // Send verification email
                        mail($user->email, $subject, $message, $headers);
                        
                        $success = true;
                    } else {
                        $errors[] = 'Failed to create verification token. Please try again.';
                    }
                }
            } else {
                // Don't reveal if email exists or not for security
                $success = true;
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
            error_log("Resend verification error: " . $e->getMessage());
        }
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
                    <h3 class="mb-0">Resend Verification Email</h3>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <p>If an account exists with the email address you provided, a verification email has been sent.</p>
                            <p>Please check your email and follow the instructions to verify your account.</p>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Return to Login</a>
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
                        
                        <p>Enter your email address below and we'll send you a new verification email.</p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Send Verification Email</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="login.php">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>

