<?php
/*
Name of file: /register.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User registration with mandatory email verification
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
require_once 'includes/admin_notifications.php';

// Set page title
$pageTitle = 'Register - Art Auction';

// Process form submission
$errors = [];
$success = false;
$redirect = false;
$verification_email_sent = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
        $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $first_name = isset($_POST['first_name']) ? clean_input($_POST['first_name']) : '';
        $last_name = isset($_POST['last_name']) ? clean_input($_POST['last_name']) : '';
        $user_type = isset($_POST['user_type']) ? clean_input($_POST['user_type']) : 'user';
        
        // Validate form data
        if(empty($username)) {
            $errors[] = 'Username is required';
        } elseif(strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        }
        
        if(empty($email)) {
            $errors[] = 'Email is required';
        } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }
        
        if(empty($password)) {
            $errors[] = 'Password is required';
        } elseif(strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        if($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }
        
        if(!in_array($user_type, ['user', 'artist'])) {
            $errors[] = 'Invalid user type';
        }
        
        // If no errors, proceed with registration
        if(empty($errors)) {
            // Database connection
            $database = new Database();
            $db = $database->connect();
            
            if (!$db) {
                throw new Exception("Database connection failed");
            }
            
            // Create user object
            $user = new User($db);
            
            // Check if username already exists
            $user->username = $username;
            if($user->username_exists()) {
                $errors[] = 'Username already exists';
            }
            
            // Check if email already exists
            $user->email = $email;
            if($user->email_exists()) {
                $errors[] = 'Email already exists';
            }
            
            // If username and email are unique, register user
            if(empty($errors)) {
                // Set user properties
                $user->password = $password;
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->user_type = $user_type;
                
                // Register user - this will set verified to FALSE
                if($user->register()) {
                    // Send admin notification
                    notify_admin_registration(
                        $username,
                        $email,
                        $first_name,
                        $last_name,
                        $user_type
                    );
                    
                    // Create verification token
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
                                    <p>Hello ' . htmlspecialchars($username) . ',</p>
                                    <p>Thank you for registering with Art Auction. To complete your registration and verify your email address, please click the button below:</p>
                                    <p style="text-align: center;">
                                        <a href="' . $verification_link . '" class="button">Verify Email Address</a>
                                    </p>
                                    <p>Or copy and paste this link into your browser:</p>
                                    <p>' . $verification_link . '</p>
                                    <p>This link will expire in 24 hours.</p>
                                    <p>If you did not create an account, please ignore this email.</p>
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
                        $verification_email_sent = mail($email, $subject, $message, $headers);
                        
                        $success = true;
                        
                        // Set session message
                        $_SESSION['message'] = 'Registration successful! Please check your email to verify your account before logging in.';
                        $_SESSION['message_type'] = 'success';
                        
                        // Set redirect flag
                        $redirect = true;
                    } else {
                        $errors[] = 'Failed to create verification token. Please try again.';
                    }
                } else {
                    $errors[] = 'Registration failed. Please try again.';
                }
            }
        }
    } catch (Exception $e) {
        $errors[] = 'An error occurred: ' . $e->getMessage();
        error_log("Registration error: " . $e->getMessage());
    }
}

// Include header
include_once 'includes/header.php';

// If registration was successful and we need to redirect
if ($redirect) {
    echo '<script>window.location.href = "login.php";</script>';
    exit();
}
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Create an Account</h3>
                </div>
                <div class="card-body">
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            Registration successful! Please check your email to verify your account before logging in.
                            <?php if(!$verification_email_sent): ?>
                                <p>There was an issue sending the verification email. Please contact support.</p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="user_type" class="form-label">I want to:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="user_type_user" value="user" <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] === 'user') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="user_type_user">
                                    <i class="fas fa-user"></i> Join as a Collector (bid on artworks)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="user_type_artist" value="artist" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'artist') ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="user_type_artist">
                                    <i class="fas fa-paint-brush"></i> Join as an Artist (sell artworks)
                                </label>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <div class="form-text">Choose a unique username (3-50 characters)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <div class="form-text">You'll need to verify this email address</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a></label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Already have an account? <a href="login.php">Log In</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>





