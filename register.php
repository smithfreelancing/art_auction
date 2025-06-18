<?php
/*
Name of file: /register.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User registration page
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
$pageTitle = 'Register - Art Auction';

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = clean_input($_POST['username'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = clean_input($_POST['first_name'] ?? '');
    $last_name = clean_input($_POST['last_name'] ?? '');
    $user_type = clean_input($_POST['user_type'] ?? 'user');
    
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
            
            // Register user
            if($user->register()) {
                $success = true;
                
                // Auto-login after registration
                $_SESSION['user_id'] = $user->id;
                $_SESSION['username'] = $user->username;
                $_SESSION['user_type'] = $user->user_type;
                
                // Redirect to dashboard
                header('Location: dashboard.php?welcome=1');
                exit();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}

// Include header
include_once 'includes/header.php';
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
                            Registration successful! You are now logged in.
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

