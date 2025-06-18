<?php
/*
Name of file: /settings.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User account settings page
*/

// Start session
session_start();

// Include authentication middleware
require_once 'includes/auth_middleware.php';
require_login();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'Account Settings - Art Auction';

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
    if(isset($_POST['update_email_notifications'])) {
        // In a real application, you would update the user's notification preferences
        // For this example, we'll just show success
        $success = true;
        $success_message = 'Email notification settings updated successfully.';
    } elseif(isset($_POST['deactivate_account'])) {
        // In a real application, you would deactivate the user's account
        // For this example, we'll just show a confirmation
        $success = true;
        $success_message = 'Account deactivation request received. Please check your email for confirmation.';
    }
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo !empty($user->profile_image) ? htmlspecialchars($user->profile_image) : '/assets/images/default-profile.jpg'; ?>" 
                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" 
                         alt="<?php echo htmlspecialchars($user->username); ?>">
                    <h4><?php echo htmlspecialchars($user->username); ?></h4>
                    <p class="text-muted">
                        <?php echo ucfirst($user->user_type); ?>
                    </p>
                </div>
            </div>
            
            <div class="list-group mt-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <?php if($user->user_type === 'artist'): ?>
                    <a href="my-artworks.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-palette"></i> My Artworks
                    </a>
                    <a href="add-artwork.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-plus"></i> Add New Artwork
                    </a>
                <?php endif; ?>
                <a href="my-bids.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-gavel"></i> My Bids
                </a>
                <a href="my-purchases.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-shopping-cart"></i> My Purchases
                </a>
                <a href="favorites.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-heart"></i> Favorites
                </a>
                <a href="messages.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="settings.php" class="list-group-item list-group-item-action active">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>
        
        <div class="col-md-9">
            <?php if($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
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
                    <h4 class="mb-0">Account Settings</h4>
                </div>
                <div class="card-body">
                    <h5>Profile Information</h5>
                    <p>Update your profile information and password.</p>
                    <a href="profile.php" class="btn btn-outline-primary">Edit Profile</a>
                    
                    <hr class="my-4">
                    
                    <h5>Email Notifications</h5>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notify_bids" name="notify_bids" checked>
                            <label class="form-check-label" for="notify_bids">Notify me when someone bids on my artwork</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notify_outbid" name="notify_outbid" checked>
                            <label class="form-check-label" for="notify_outbid">Notify me when I'm outbid</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notify_auction_end" name="notify_auction_end" checked>
                            <label class="form-check-label" for="notify_auction_end">Notify me when an auction I'm participating in ends</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notify_messages" name="notify_messages" checked>
                            <label class="form-check-label" for="notify_messages">Notify me when I receive a new message</label>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="notify_newsletter" name="notify_newsletter" checked>
                            <label class="form-check-label" for="notify_newsletter">Subscribe to newsletter</label>
                        </div>
                        <button type="submit" name="update_email_notifications" class="btn btn-primary">Save Notification Settings</button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h5>Account Deactivation</h5>
                    <p class="text-danger">Warning: Deactivating your account will remove your profile from the platform. This action cannot be undone.</p>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deactivateAccountModal">
                        Deactivate Account
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Privacy Settings</h4>
                </div>
                <div class="card-body">
                    <h5>Profile Visibility</h5>
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Who can see my profile?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_visibility" id="visibility_everyone" value="everyone" checked>
                                <label class="form-check-label" for="visibility_everyone">
                                    Everyone
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="profile_visibility" id="visibility_users" value="users">
                                <label class="form-check-label" for="visibility_users">
                                    Registered users only
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Show my email address to:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="email_visibility" id="email_nobody" value="nobody" checked>
                                <label class="form-check-label" for="email_nobody">
                                    Nobody
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="email_visibility" id="email_users" value="users">
                                <label class="form-check-label" for="email_users">
                                    Registered users only
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Privacy Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Account Modal -->
<div class="modal fade" id="deactivateAccountModal" tabindex="-1" aria-labelledby="deactivateAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deactivateAccountModalLabel">Confirm Account Deactivation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to deactivate your account? This action cannot be undone.</p>
                <p>If you proceed:</p>
                <ul>
                    <li>Your profile will be removed from the platform</li>
                    <li>Your artworks will be removed from the marketplace</li>
                    <li>Your bids will be canceled</li>
                    <li>You will no longer receive notifications</li>
                </ul>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="mb-3">
                        <label for="deactivation_reason" class="form-label">Please tell us why you're leaving (optional):</label>
                        <textarea class="form-control" id="deactivation_reason" name="deactivation_reason" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="confirm_deactivation" name="confirm_deactivation" required>
                        <label class="form-check-label" for="confirm_deactivation">I understand this action cannot be undone</label>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="deactivate_account" class="btn btn-danger">Deactivate My Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
