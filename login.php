<?php
/*
Name of file: /login.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User login page with verification check
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
require_once 'includes/admin_notifications.php';

// Set page title
$pageTitle = 'Login - Art Auction';

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = clean_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    // Validate form data
    if(empty($username)) {
        $errors[] = 'Username or email is required';
    }
    
    if(empty($password)) {
        $errors[] = 'Password is required';
    }
    
    // If no errors, proceed with login
    if(empty($errors)) {
        try {
            // Database connection
            $database = new Database();
            $db = $database->connect();
            
            // Create user object
            $user = new User($db);
            
            // Set user properties
            $user->username = $username;
            $user->password = $password;
            
            // Login user
            if($user->login()) {
                // Check if user is verified
                if($user->is_verified()) {
                    $success = true;
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['username'] = $user->username;
                    $_SESSION['user_type'] = $user->user_type;
                    
                    // Send admin notification
                    notify_admin_login(
                        $user->username,
                        $user->email,
                        $user->first_name,
                        $user->last_name,
                        $user->user_type
                    );
                    
                    // Set remember me cookie if checked
                    if($remember) {
                        $token = generate_token();
                        setcookie('remember_token', $token, time() + (86400 * 30), '/'); // 30 days
                    }
                    
                    // Store the success message in session to display after redirect
                    $_SESSION['login_success'] = true;
                    
                    // Redirect to dashboard using JavaScript to avoid header issues
                    echo "<script>window.location.href = 'dashboard.php';</script>";
                    exit();
                } else {
                    $errors[] = 'Your email address has not been verified. Please check your email for the verification link or <a href="resend_verification.php">request a new one</a>.';
                }
            } else {
                $errors[] = 'Invalid username/email or password';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred: ' . $e->getMessage();
            error_log("Login error: " . $e->getMessage());
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
                    <h3 class="mb-0">Login to Your Account</h3>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success">
                            Login successful! Redirecting...
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
                            <label for="username" class="form-label">Username or Email</label>
                            <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="forgot_password.php">Forgot your password?</a>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    Don't have an account? <a href="register.php">Register</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>


