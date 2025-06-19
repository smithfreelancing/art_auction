<?php
/*
Name of file: /artist_portfolio.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Display an artist's complete portfolio
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artist.php';

// Check if artist ID is provided
if (!isset($_GET['id'])) {
    header('Location: artists.php');
    exit();
}

$artist_id = intval($_GET['id']);

// Database connection
$database = new Database();
$db = $database->connect();

// Create artist object
$artist = new Artist($db);
$artist->id = $artist_id;

// Get artist data
if (!$artist->read_single()) {
    // Artist not found or not an artist
    header('Location: artists.php');
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get filter parameters
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'newest';

// Get artist's artworks
$query = "SELECT * FROM artworks WHERE user_id = :artist_id";

// Add status filter if provided
if (!empty($status) && in_array($status, ['active', 'sold', 'pending', 'expired'])) {
    $query .= " AND status = :status";
}

// Add sorting
switch ($sort) {
    case 'price_high':
        $query .= " ORDER BY current_price DESC, created_at DESC";
        break;
    case 'price_low':
        $query .= " ORDER BY current_price ASC, created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY created_at DESC";
        break;
}

$query .= " LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);

if (!empty($status) && in_array($status, ['active', 'sold', 'pending', 'expired'])) {
    $stmt->bindParam(':status', $status);
}

$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total artworks for pagination
$count_query = "SELECT COUNT(*) as total FROM artworks WHERE user_id = :artist_id";

if (!empty($status) && in_array($status, ['active', 'sold', 'pending', 'expired'])) {
    $count_query .= " AND status = :status";
}

$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(':artist_id', $artist_id);

if (!empty($status) && in_array($status, ['active', 'sold', 'pending', 'expired'])) {
    $count_stmt->bindParam(':status', $status);
}

$count_stmt->execute();
$total_artworks = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_artworks / $limit);

// Set page title
$pageTitle = htmlspecialchars($artist->first_name . ' ' . $artist->last_name) . ' - Portfolio';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo !empty($artist->profile_image) ? htmlspecialchars($artist->profile_image) : '/assets/images/default-profile.jpg'; ?>" 
                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" 
                         alt="<?php echo htmlspecialchars($artist->username); ?>">
                    
                    <h3><?php echo htmlspecialchars($artist->first_name . ' ' . $artist->last_name); ?></h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($artist->username); ?></p>
                    
                    <?php if($artist->featured): ?>
                        <span class="badge bg-warning text-dark mb-3">Featured Artist</span>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2">
                        <a href="artist.php?id=<?php echo $artist->id; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-user"></i> View Profile
                        </a>
                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
                            <a href="messages.php?to=<?php echo $artist->id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-envelope"></i> Contact Artist
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Filter Options -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Artworks</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get">
                        <input type="hidden" name="id" value="<?php echo $artist_id; ?>">
                        
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active Auctions</option>
                                <option value="sold" <?php echo $status == 'sold' ? 'selected' : ''; ?>>Sold</option>
                                <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Expired</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><?php echo htmlspecialchars($artist->first_name . ' ' . $artist->last_name); ?>'s Portfolio</h4>
                </div>
                <div class="card-body">
                    <?php if(count($artworks) > 0): ?>
                        <div class="row">
                            <?php foreach($artworks as $artwork): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($artwork['title']); ?>" style="height: 200px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                                            <p class="card-text text-muted small"><?php echo substr(htmlspecialchars($artwork['description']), 0, 100) . '...'; ?></p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">$<?php echo number_format($artwork['current_price'] ?? $artwork['starting_price'], 2); ?></span>
                                                <span class="badge <?php 
                                                    switch($artwork['status']) {
                                                        case 'active': echo 'bg-success'; break;
                                                        case 'sold': echo 'bg-primary'; break;
                                                        case 'pending': echo 'bg-warning text-dark'; break;
                                                        case 'expired': echo 'bg-secondary'; break;
                                                        default: echo 'bg-info';
                                                    }
                                                ?>"><?php echo ucfirst($artwork['status']); ?></span>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <a href="artwork.php?id=<?php echo $artwork['id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Portfolio pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $page-1; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Previous</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $i; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $page+1; ?><?php echo !empty($status) ? '&status='.$status : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                            <h4>No Artworks Found</h4>
                            <p class="text-muted">
                                <?php if(!empty($status)): ?>
                                    No <?php echo $status; ?> artworks found in this artist's portfolio.
                                <?php else: ?>
                                    This artist hasn't uploaded any artworks yet.
                                <?php endif; ?>
                            </p>
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
