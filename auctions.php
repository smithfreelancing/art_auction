<?php
/*
Name of file: /auctions.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Browse active auctions
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Auction.php';

// Get filter parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'ending_soon';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Database connection
$database = new Database();
$db = $database->connect();

// Create auction object
$auction = new Auction($db);

// Process auction status updates
$auction_messages = $auction->process_auctions();

// Get active auctions
$auctions_stmt = $auction->read_active($limit, $offset);
$total_auctions = $auction->count_all('active');

// Calculate pagination
$total_pages = ceil($total_auctions / $limit);

// Get artwork categories for filter
$query = "SELECT * FROM artwork_categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$pageTitle = 'Active Auctions';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Active Auctions</h1>
            <p class="lead">Bid on unique artworks from talented artists.</p>
        </div>
        <div class="col-md-4">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="d-flex">
                <input type="text" class="form-control me-2" name="search" placeholder="Search auctions..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="row g-3">
                        <?php if(!empty($search)): ?>
                            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <?php endif; ?>
                        
                        <div class="col-md-4">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">All Categories</option>
                                <?php foreach($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['name']); ?>" <?php echo $category === $cat['name'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="ending_soon" <?php echo $sort === 'ending_soon' ? 'selected' : ''; ?>>Ending Soon</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="most_bids" <?php echo $sort === 'most_bids' ? 'selected' : ''; ?>>Most Bids</option>
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auctions Grid -->
    <?php if($auctions_stmt && $auctions_stmt->rowCount() > 0): ?>
        <div class="row">
            <?php while($row = $auctions_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['title']); ?>" style="height: 200px; object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0 p-2">
                                <span class="badge bg-primary">
                                    <?php
                                    $end_time = strtotime($row['end_time']);
                                    $now = time();
                                    $remaining = $end_time - $now;
                                    
                                    if($remaining <= 3600) { // Less than 1 hour
                                        echo '<i class="fas fa-clock text-warning"></i> ';
                                        echo floor($remaining / 60) . 'm left';
                                    } elseif($remaining <= 86400) { // Less than 1 day
                                        echo '<i class="fas fa-clock"></i> ';
                                        echo floor($remaining / 3600) . 'h left';
                                    } else {
                                        echo '<i class="fas fa-calendar-alt"></i> ';
                                        echo floor($remaining / 86400) . 'd left';
                                    }
                                    ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                            <p class="card-text text-muted">
                                By <a href="artist.php?id=<?php echo $row['artist_id']; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($row['artist_first_name'] . ' ' . $row['artist_last_name']); ?>
                                </a>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>Current Bid:</strong><br>
                                    $<?php echo number_format($row['current_price'] ?? $row['starting_price'], 2); ?>
                                </div>
                                <div class="text-end">
                                    <strong>Bids:</strong><br>
                                    <?php echo $row['bid_count']; ?>
                                </div>
                            </div>
                            <div class="d-grid">
                                <a href="auction.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary">View Auction</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav aria-label="Auction pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Previous</a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <?php if(!empty($search)): ?>
                No auctions found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.
            <?php elseif(!empty($category)): ?>
                No auctions found in the "<?php echo htmlspecialchars($category); ?>" category.
            <?php else: ?>
                No active auctions found.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
