<?php
/*
Name of file: /favorites.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Allow users to view their favorite artworks
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
require_once 'models/Artwork.php';

// Database connection
$database = new Database();
$db = $database->connect();

// Get user data
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get user's favorite artworks
$artwork = new Artwork($db);
$favorites_stmt = $artwork->read_favorites($_SESSION['user_id'], $limit, $offset);
$total_favorites = $artwork->count_favorites($_SESSION['user_id']);

// Calculate pagination
$total_pages = ceil($total_favorites / $limit);

// Set page title
$pageTitle = 'My Favorites';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <?php include_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">My Favorites</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <?php if($favorites_stmt && $favorites_stmt->rowCount() > 0): ?>
                        <div class="row">
                            <?php while($row = $favorites_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>" style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                                            <p class="card-text text-muted">
                                                By <a href="artist.php?id=<?php echo $row['user_id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                                </a>
                                            </p>
                                            <p class="card-text">
                                                <?php if($row['is_auction']): ?>
                                                    <strong>Starting at:</strong> $<?php echo number_format($row['starting_price'], 2); ?>
                                                    <?php if($row['current_price'] && $row['current_price'] > $row['starting_price']): ?>
                                                        <br><strong>Current bid:</strong> $<?php echo number_format($row['current_price'], 2); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <strong>Price:</strong> $<?php echo number_format($row['price'], 2); ?>
                                                <?php endif; ?>
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <a href="artwork.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                                <form method="post" action="artwork.php?id=<?php echo $row['id']; ?>">
                                                    <button type="submit" name="toggle_favorite" class="btn btn-outline-danger btn-sm">
                                                        <i class="fas fa-heart"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="card-footer text-muted small">
                                            Added to favorites on <?php echo date('M d, Y', strtotime($row['favorited_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Favorites pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?>">Previous</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="far fa-heart fa-4x text-muted mb-3"></i>
                            <h4>No Favorites Found</h4>
                            <p class="text-muted">You haven't added any artworks to your favorites yet.</p>
                            <a href="artworks.php" class="btn btn-primary mt-3">
                                <i class="fas fa-search"></i> Browse Artworks
                            </a>
                        </div>
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
