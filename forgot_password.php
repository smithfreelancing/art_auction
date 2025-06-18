<?php
/*
Name of file: /forgot_password.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Password recovery page
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
$pageTitle = 'Forgot Password - Art Auction';

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
    
    // If no errors, proceed with password reset
    if(empty($errors)) {
        // Database connection
        $database = new Database();
        $db = $database->connect();
        
        // Create user object
        $user = new User($db);
        
        // Set user email
        $user->email = $email;
        
        // Check if email exists
        if($user->get_by_email()) {
            // Generate reset token
            $token = generate_token();
            $token_hash = password_hash($token, PASSWORD_DEFAULT);
            
            // Store token in database (you would need to add a password_resets table)
            // This is a simplified version
            
            // Send reset email (in a real application)
            // mail($email, 'Password Reset', 'Click here to reset your password: http://yourdomain.com/reset_password.php?token=' . $token);
            
            $success = true;
        } else {
            // Don't reveal if email exists or not for security
            $success = true;
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
                    <h3 class="mb-0">Forgot Password</h3>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <p>If an account exists with the email address you provided, you will receive password reset instructions.</p>
                            <p>Please check your email and follow the instructions to reset your password.</p>
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
                        
                        <p>Enter your email address below and we'll send you instructions to reset your password.</p>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Send Reset Instructions</button>
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
