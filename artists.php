<?php
/*
Name of file: /artists.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Display a list of artists with search and filtering options
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artist.php';

// Set page title
$pageTitle = 'Artists - Art Auction';

// Get search and filter parameters
$search = isset($_GET['search']) ? clean_input($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Database connection
$database = new Database();
$db = $database->connect();

// Create artist object
$artist = new Artist($db);

// Get all categories for the filter dropdown
$query = "SELECT * FROM artist_categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get artists based on search/filter
if($category > 0) {
    $query = "SELECT u.*, 
             (SELECT COUNT(*) FROM artworks WHERE user_id = u.id) as artwork_count,
             (SELECT COALESCE(AVG(rating), 0) FROM artist_reviews WHERE artist_id = u.id) as avg_rating
             FROM users u
             JOIN artist_category_relationships acr ON u.id = acr.artist_id
             WHERE u.user_type = 'artist' AND acr.category_id = :category_id";
    
    if(!empty($search)) {
        $query .= " AND (u.username LIKE :keyword 
                  OR u.first_name LIKE :keyword 
                  OR u.last_name LIKE :keyword 
                  OR u.bio LIKE :keyword
                  OR u.specialties LIKE :keyword)";
    }
    
    $query .= " ORDER BY u.featured DESC, avg_rating DESC, artwork_count DESC
               LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $category);
    
    if(!empty($search)) {
        $keyword = "%{$search}%";
        $stmt->bindParam(':keyword', $keyword);
    }
    
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $artists_stmt = $stmt;
    
    // Count total artists for pagination
    $count_query = "SELECT COUNT(DISTINCT u.id) as total 
                   FROM users u
                   JOIN artist_category_relationships acr ON u.id = acr.artist_id
                   WHERE u.user_type = 'artist' AND acr.category_id = :category_id";
    
    if(!empty($search)) {
        $count_query .= " AND (u.username LIKE :keyword 
                        OR u.first_name LIKE :keyword 
                        OR u.last_name LIKE :keyword 
                        OR u.bio LIKE :keyword
                        OR u.specialties LIKE :keyword)";
    }
    
    $count_stmt = $db->prepare($count_query);
    $count_stmt->bindParam(':category_id', $category);
    
    if(!empty($search)) {
        $count_stmt->bindParam(':keyword', $keyword);
    }
    
    $count_stmt->execute();
    $total_artists = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
} else if(!empty($search)) {
    $artists_stmt = $artist->search($search, $limit, $offset);
    $total_artists = $artist->count_all(); // This is simplified; ideally, we'd count search results
} else {
    $artists_stmt = $artist->read_all($limit, $offset);
    $total_artists = $artist->count_all();
}

// Calculate pagination
$total_pages = ceil($total_artists / $limit);

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1>Artists</h1>
            <p class="lead">Discover talented artists from around the world.</p>
        </div>
        <div class="col-md-4">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="d-flex">
                <input type="text" class="form-control me-2" name="search" placeholder="Search artists..." value="<?php echo htmlspecialchars($search); ?>">
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
                                <option value="0">All Categories</option>
                                <?php foreach($all_categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="rating">Rating</option>
                                <option value="newest">Newest</option>
                                <option value="artworks">Most Artworks</option>
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
    
    <!-- Featured Artists -->
    <?php if(empty($search) && $page == 1 && $category == 0): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h2>Featured Artists</h2>
                <div class="row">
                    <?php
                    $featured_artists = $artist->get_featured(3);
                    while($row = $featured_artists->fetch(PDO::FETCH_ASSOC)):
                    ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                         class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($row['username']); ?>">
                                    
                                    <h4><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h4>
                                    <p class="text-muted">@<?php echo htmlspecialchars($row['username']); ?></p>
                                    
                                    <div class="mb-3">
                                        <?php
                                        $rating = $row['avg_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <p class="text-muted"><?php echo $row['artwork_count']; ?> artworks</p>
                                    
                                    <!-- Fixed link: Added proper URL path -->
                                    <a href="artist.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-primary">View Profile</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- All Artists -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><?php echo !empty($search) ? 'Search Results' : 'All Artists'; ?></h2>
            
            <?php if($artists_stmt->rowCount() > 0): ?>
                <div class="row">
                    <?php while($row = $artists_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <img src="<?php echo !empty($row['profile_image']) ? htmlspecialchars($row['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                         class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;" 
                                         alt="<?php echo htmlspecialchars($row['username']); ?>">
                                    
                                    <h5><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></h5>
                                    <p class="text-muted small">@<?php echo htmlspecialchars($row['username']); ?></p>
                                    
                                    <div class="mb-2">
                                        <?php
                                        $rating = $row['avg_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star text-warning"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                            } else {
                                                echo '<i class="far fa-star text-warning"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    
                                    <p class="text-muted small"><?php echo $row['artwork_count']; ?> artworks</p>
                                    
                                    <!-- Fixed link: Added proper URL path -->
                                    <a href="artist.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary">View Profile</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo $category > 0 ? '&category='.$category : ''; ?>">Previous</a>
                            </li>
                            
                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo $category > 0 ? '&category='.$category : ''; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo $category > 0 ? '&category='.$category : ''; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-info">
                    <?php if(!empty($search)): ?>
                        No artists found matching "<?php echo htmlspecialchars($search); ?>". Try a different search term.
                    <?php else: ?>
                        No artists found.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>

