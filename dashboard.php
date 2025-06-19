<?php
/*
Name of file: /dashboard.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: User dashboard page with artist welcome message
*/

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
require_once 'models/Artist.php';

// Set page title
$pageTitle = 'Dashboard - Art Auction';

// Get user data
$database = new Database();
$db = $database->connect();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

// Check for welcome message
$welcome = isset($_GET['welcome']) && $_GET['welcome'] == 1;

// Check for login success message
$login_success = isset($_SESSION['login_success']) && $_SESSION['login_success'] === true;
if ($login_success) {
    unset($_SESSION['login_success']); // Clear the message after displaying it
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <?php if($welcome): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Welcome to Art Auction!</h4>
            <p>Your account has been created successfully. You can now start exploring artworks or set up your profile.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if($login_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">Login Successful!</h4>
            <p>Welcome back to Art Auction.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if(!$user->is_verified()): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Email Not Verified</h4>
            <p>Your email address has not been verified. Please check your inbox for the verification email or <a href="resend_verification.php">request a new one</a>.</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php
    // Include artist stats widget for artists
    if($user->user_type === 'artist') {
        include_once 'includes/artist_stats_widget.php';
    }
    ?>
    
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
                    <div class="d-grid gap-2">
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="list-group mt-4">
                <a href="dashboard.php" class="list-group-item list-group-item-action active">
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
                <a href="settings.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Dashboard</h4>
                </div>
                <div class="card-body">
                    <h5>Welcome, <?php echo htmlspecialchars($user->first_name ?: $user->username); ?>!</h5>
                    <p>Here's an overview of your activity on Art Auction.</p>
                    
                    <?php
                    // Personalized welcome message for artists
                    if($user->user_type === 'artist'):
                        // Get artist stats
                        $artist = new Artist($db);
                        $artist->id = $user->id;
                        $artist_stats = $artist->get_statistics();
                        
                        // Check if artist has uploaded any artworks
                        if($artist_stats['total_artworks'] == 0):
                    ?>
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Get Started as an Artist</h5>
                            <p>Welcome to your artist dashboard! To get started, upload your first artwork and set up your artist profile.</p>
                            <div class="mt-3">
                                <a href="add-artwork.php" class="btn btn-primary me-2">
                                    <i class="fas fa-plus"></i> Add Artwork
                                </a>
                                <a href="edit_artist_profile.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-edit"></i> Complete Your Profile
                                </a>
                            </div>
                        </div>
                    <?php 
                        // Check if artist profile is incomplete
                        elseif(empty($user->bio) || empty($artist->specialties)):
                    ?>
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-circle"></i> Complete Your Artist Profile</h5>
                            <p>Your artist profile is incomplete. Add a bio and specialties to help collectors discover your work.</p>
                            <div class="mt-3">
                                <a href="edit_artist_profile.php" class="btn btn-warning">
                                    <i class="fas fa-user-edit"></i> Complete Your Profile
                                </a>
                            </div>
                        </div>
                    <?php
                        endif;
                    endif;
                    ?>
                    
                    <div class="row mt-4">
                        <?php if($user->user_type === 'artist'): ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h1>0</h1>
                                        <p class="mb-0">Active Auctions</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h1>0</h1>
                                        <p class="mb-0">Sold Artworks</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h1>$0</h1>
                                        <p class="mb-0">Total Earnings</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h1>0</h1>
                                        <p class="mb-0">Active Bids</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h1>0</h1>
                                        <p class="mb-0">Won Auctions</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h1>0</h1>
                                        <p class="mb-0">Favorites</p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if($user->user_type === 'artist'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Sales</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center">No sales yet.</p>
                        <div class="text-center mt-3">
                            <a href="add-artwork.php" class="btn btn-primary">Add Your First Artwork</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Bids</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted text-center">No bids yet.</p>
                        <div class="text-center mt-3">
                            <a href="artworks.php" class="btn btn-primary">Explore Artworks</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recommended for You</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <p class="text-muted text-center">We'll show personalized recommendations as you interact with the platform.</p>
                        <div class="text-center mt-3">
                            <a href="artworks.php" class="btn btn-outline-primary">Browse All Artworks</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>


