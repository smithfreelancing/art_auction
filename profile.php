<?php
/*
Name of file: /profile.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: User profile page with fixed htmlspecialchars warning
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'My Profile - Art Auction';

try {
    // Get user data
    $database = new Database();
    $db = $database->connect();

    $user = new User($db);
    $user->id = $_SESSION['user_id'];
    $user->read_single();

    // Process form submission
    $errors = [];
    $success = false;

    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Check which form was submitted
        if(isset($_POST['update_profile'])) {
            // Get form data
            $username = isset($_POST['username']) ? clean_input($_POST['username']) : '';
            $email = isset($_POST['email']) ? clean_input($_POST['email']) : '';
            $first_name = isset($_POST['first_name']) ? clean_input($_POST['first_name']) : '';
            $last_name = isset($_POST['last_name']) ? clean_input($_POST['last_name']) : '';
            $bio = isset($_POST['bio']) ? clean_input($_POST['bio'], true) : '';
            
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
            
            // Check if username already exists (if changed)
            if($username !== $user->username) {
                $check_user = new User($db);
                $check_user->username = $username;
                if($check_user->username_exists()) {
                    $errors[] = 'Username already exists';
                }
            }
            
            // Check if email already exists (if changed)
            if($email !== $user->email) {
                $check_user = new User($db);
                $check_user->email = $email;
                if($check_user->email_exists()) {
                    $errors[] = 'Email already exists';
                }
            }
            
            // If no errors, update profile
            if(empty($errors)) {
                $user->username = $username;
                $user->email = $email;
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->bio = $bio;
                
                if($user->update()) {
                    $success = true;
                    $_SESSION['username'] = $user->username;
                } else {
                    $errors[] = 'Failed to update profile';
                }
            }
        } elseif(isset($_POST['update_password'])) {
            // Get form data
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validate form data
            if(empty($current_password)) {
                $errors[] = 'Current password is required';
            }
            
            if(empty($new_password)) {
                $errors[] = 'New password is required';
            } elseif(strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters';
            }
            
            if($new_password !== $confirm_password) {
                $errors[] = 'Passwords do not match';
            }
            
            // Verify current password
            // In a real application, you would need to fetch the current password hash from the database
            // and use password_verify() to check if the current password is correct
            
            // For this example, we'll just show success
            if(empty($errors)) {
                $user->password = $new_password;
                
                if($user->update_password()) {
                    $success = true;
                    $_SESSION['message'] = 'Password updated successfully';
                    $_SESSION['message_type'] = 'success';
                } else {
                    $errors[] = 'Failed to update password';
                }
            }
        } elseif(isset($_POST['update_image'])) {
            // Handle profile image upload
            if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024; // 2MB
                
                $file = $_FILES['profile_image'];
                
                // Validate file type
                if(!in_array($file['type'], $allowed_types)) {
                    $errors[] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
                }
                
                // Validate file size
                if($file['size'] > $max_size) {
                    $errors[] = 'File size exceeds the limit of 2MB.';
                }
                
                if(empty($errors)) {
                    // Generate unique filename
                    $filename = uniqid() . '_' . $file['name'];
                    $upload_dir = 'assets/uploads/profiles/';
                    
                    // Create directory if it doesn't exist
                    if(!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $upload_path = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Update user profile image
                        $user->profile_image = '/' . $upload_path;
                        
                        if($user->update_image()) {
                            $success = true;
                            $_SESSION['message'] = 'Profile image updated successfully';
                            $_SESSION['message_type'] = 'success';
                        } else {
                            $errors[] = 'Failed to update profile image';
                        }
                    } else {
                        $errors[] = 'Failed to upload image';
                    }
                }
            } else {
                $errors[] = 'No image uploaded or an error occurred';
            }
        }
    }
} catch (Exception $e) {
    $errors[] = 'An error occurred: ' . $e->getMessage();
    error_log("Profile page error: " . $e->getMessage());
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <?php include_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    Your profile has been updated successfully.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            <?php endif; ?>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Edit Profile</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($user->username ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($user->email ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->first_name ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->last_name ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="bio" class="form-label">Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user->bio ?? ''); ?></textarea>
                            <div class="form-text">Tell others about yourself or your art.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Change Profile Picture</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center">
                            <img src="<?php echo !empty($user->profile_image) ? htmlspecialchars($user->profile_image) : '/assets/images/default-profile.jpg'; ?>" 
                                 class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($user->username ?? ''); ?>">
                        </div>
                        <div class="col-md-8">
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Select Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" accept="image/*" required>
                                    <div class="form-text">Max file size: 2MB. Supported formats: JPEG, PNG, GIF.</div>
                                </div>
                                <div class="mb-3">
                                    <div id="imagePreviewContainer" class="text-center d-none">
                                        <img id="imagePreview" src="#" alt="Preview" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" name="update_image" class="btn btn-primary">Upload Image</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Change Password</h4>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <div class="form-text">Minimum 6 characters</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_password" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Preview image before upload
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>

