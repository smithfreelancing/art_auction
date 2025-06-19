<?php
/*
Name of file: /artworks.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Browse and search for artworks
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artwork.php';

// Get search and filter parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? clean_input($_GET['category']) : '';
$tag = isset($_GET['tag']) ? clean_input($_GET['tag']) : '';
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Database connection
$database = new Database();
$db = $database->connect();

// Create artwork object
$artwork = new Artwork($db);

// Get artworks based on search/filter
$artworks_stmt = $artwork->read_all('active', $category, $search, $sort, $limit, $offset);
$total_artworks = $artwork->count_all('active', $category, $search);

// Calculate pagination
$total_pages = ceil($total_artworks / $limit);

// Get artwork categories for filter
$query = "SELECT * FROM artwork_categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get popular tags for filter
$query = "SELECT at.name, COUNT(*) as count 
          FROM artwork_tags at
          JOIN artwork_tag_relationships atr ON at.id = atr.tag_id
          JOIN artworks a ON atr.artwork_id = a.id
          WHERE a.status = 'active'
          GROUP BY at.name
          ORDER BY count DESC
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$popular_tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$pageTitle = 'Browse Artworks';
if(!empty($search)) {
    $pageTitle = 'Search Results for "' . $search . '"';
} elseif(!empty($category)) {
    $pageTitle = $category . ' Artworks';
} elseif(!empty($tag)) {
    $pageTitle = 'Artworks tagged with "' . $tag . '"';
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
            <p class="lead">Discover and collect unique artworks from talented artists.</p>
        </div>
        <div class="col-md-4">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="d-flex">
                <input type="text" class="form-control me-2" name="search" placeholder="Search artworks..." value="<?php echo htmlspecialchars($search); ?>">
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
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
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
    
    <!-- Popular Tags -->
    <?php if(!empty($popular_tags)): ?>
        <div class="mb-4">
            <h5>Popular Tags:</h5>
            <div>
                <?php foreach($popular_tags as $pop_tag): ?>
                    <a href="artworks.php?tag=<?php echo urlencode($pop_tag['name']); ?>" class="badge bg-secondary text-decoration-none me-1 mb-1">
                        <?php echo htmlspecialchars($pop_tag['name']); ?> (<?php echo $pop_tag['count']; ?>)
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Artworks Grid -->
    <?php if($artworks_stmt && $artworks_stmt->rowCount() > 0): ?>
        <div class="row">
            <?php while($row = $artworks_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="col-md-3 mb-4">
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
                                <span class="text-muted small"><?php echo $row['favorite_count']; ?> <i class="far fa-heart"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav aria-label="Artwork pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($tag) ? '&tag='.urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Previous</a>
                    </li>
                    
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($tag) ? '&tag='.urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($category) ? '&category='.urlencode($category) : ''; ?><?php echo !empty($tag) ? '&tag='.urlencode($tag) : ''; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    <?php else: ?>
        <div class="alert alert-info">
            <?php if(!empty($search)): ?>
                No artworks found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.
            <?php elseif(!empty($category)): ?>
                No artworks found in the "<?php echo htmlspecialchars($category); ?>" category.
            <?php elseif(!empty($tag)): ?>
                No artworks found with the tag "<?php echo htmlspecialchars($tag); ?>".
            <?php else: ?>
                No artworks found.
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>

