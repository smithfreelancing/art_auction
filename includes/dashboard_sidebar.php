<?php
/*
Name of file: /includes/dashboard_sidebar.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Sidebar navigation for user dashboard
*/

// Make sure we have user data
if(!isset($user) && isset($_SESSION['user_id'])) {
    $user_db = new Database();
    $user_conn = $user_db->connect();
    
    $user = new User($user_conn);
    $user->id = $_SESSION['user_id'];
    $user->read_single();
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="card">
    <div class="card-body text-center">
        <img src="<?php echo !empty($user->profile_image) ? htmlspecialchars($user->profile_image) : '/assets/images/default-profile.jpg'; ?>" 
             class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;" 
             alt="<?php echo htmlspecialchars($user->username); ?>">
        <h4><?php echo htmlspecialchars($user->username); ?></h4>
        <p class="text-muted"><?php echo ucfirst($user->user_type); ?></p>
    </div>
</div>

<div class="list-group mt-4">
    <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    
    <?php if($user->user_type === 'artist'): ?>
        <a href="my_artworks.php" class="list-group-item list-group-item-action <?php echo $current_page == 'my_artworks.php' ? 'active' : ''; ?>">
            <i class="fas fa-palette"></i> My Artworks
        </a>
        <a href="add_artwork.php" class="list-group-item list-group-item-action <?php echo $current_page == 'add_artwork.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus"></i> Add New Artwork
        </a>
        <a href="edit_artist_profile.php" class="list-group-item list-group-item-action <?php echo $current_page == 'edit_artist_profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-edit"></i> Artist Profile
        </a>
        <a href="artist_analytics.php" class="list-group-item list-group-item-action <?php echo $current_page == 'artist_analytics.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
    <?php endif; ?>
    <a href="my_bids.php" class="list-group-item list-group-item-action <?php echo $current_page == 'my_bids.php' ? 'active' : ''; ?>">
        <i class="fas fa-gavel"></i> My Bids
    </a>
       <a href="my_purchases.php" class="list-group-item list-group-item-action <?php echo $current_page == 'my_purchases.php' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i> My Purchases
    </a>
    <a href="favorites.php" class="list-group-item list-group-item-action <?php echo $current_page == 'favorites.php' ? 'active' : ''; ?>">
        <i class="fas fa-heart"></i> Favorites
    </a>
    <a href="messages.php" class="list-group-item list-group-item-action <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> Messages
    </a>
    <a href="profile.php" class="list-group-item list-group-item-action <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
        <i class="fas fa-user"></i> My Profile
    </a>
    <a href="settings.php" class="list-group-item list-group-item-action <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Settings
    </a>
    <a href="logout.php" class="list-group-item list-group-item-action text-danger">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</div>

