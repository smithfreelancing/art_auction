<?php
/*
Name of file: /artist_reviews.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Display all reviews for an artist
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
$limit = 10;
$offset = ($page - 1) * $limit;

// Get sort parameter
$sort = isset($_GET['sort']) ? clean_input($_GET['sort']) : 'newest';

// Get artist reviews
$query = "SELECT r.*, u.username, u.profile_image, u.first_name, u.last_name 
          FROM artist_reviews r 
          JOIN users u ON r.reviewer_id = u.id 
          WHERE r.artist_id = :artist_id";

// Add sorting
switch ($sort) {
    case 'highest':
        $query .= " ORDER BY r.rating DESC, r.created_at DESC";
        break;
    case 'lowest':
        $query .= " ORDER BY r.rating ASC, r.created_at DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY r.created_at ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY r.created_at DESC";
        break;
}

$query .= " LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist_id);
$stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
$stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total reviews for pagination
$count_query = "SELECT COUNT(*) as total FROM artist_reviews WHERE artist_id = :artist_id";
$count_stmt = $db->prepare($count_query);
$count_stmt->bindParam(':artist_id', $artist_id);
$count_stmt->execute();
$total_reviews = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_reviews / $limit);

// Get average rating
$avg_query = "SELECT AVG(rating) as avg_rating FROM artist_reviews WHERE artist_id = :artist_id";
$avg_stmt = $db->prepare($avg_query);
$avg_stmt->bindParam(':artist_id', $artist_id);
$avg_stmt->execute();
$avg_rating = $avg_stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];

// Get rating distribution
$dist_query = "SELECT rating, COUNT(*) as count FROM artist_reviews WHERE artist_id = :artist_id GROUP BY rating ORDER BY rating DESC";
$dist_stmt = $db->prepare($dist_query);
$dist_stmt->bindParam(':artist_id', $artist_id);
$dist_stmt->execute();
$rating_dist = $dist_stmt->fetchAll(PDO::FETCH_ASSOC);

// Create rating distribution array
$rating_distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($rating_dist as $rating) {
    $rating_distribution[$rating['rating']] = $rating['count'];
}

// Set page title
$pageTitle = htmlspecialchars($artist->first_name . ' ' . $artist->last_name) . ' - Reviews';

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
                        <a href="artist_portfolio.php?id=<?php echo $artist->id; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-palette"></i> View Portfolio
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Rating Summary -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Rating Summary</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h1><?php echo number_format($avg_rating, 1); ?></h1>
                        <div>
                            <?php
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $avg_rating) {
                                    echo '<i class="fas fa-star text-warning"></i>';
                                } elseif ($i - 0.5 <= $avg_rating) {
                                    echo '<i class="fas fa-star-half-alt text-warning"></i>';
                                } else {
                                    echo '<i class="far fa-star text-warning"></i>';
                                }
                            }
                            ?>
                        </div>
                        <p class="text-muted mt-2"><?php echo $total_reviews; ?> reviews</p>
                    </div>
                    
                    <!-- Rating Distribution -->
                    <?php foreach(array_reverse([5, 4, 3, 2, 1]) as $rating): ?>
                        <?php 
                        $percentage = $total_reviews > 0 ? ($rating_distribution[$rating] / $total_reviews) * 100 : 0;
                        ?>
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?php echo $rating; ?> stars</span>
                                <span><?php echo $rating_distribution[$rating]; ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $percentage; ?>%" aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Sort Options -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Sort Reviews</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get">
                        <input type="hidden" name="id" value="<?php echo $artist_id; ?>">
                        
                        <div class="mb-3">
                            <select class="form-select" id="sort" name="sort" onchange="this.form.submit()">
                                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                <option value="highest" <?php echo $sort == 'highest' ? 'selected' : ''; ?>>Highest Rating</option>
                                <option value="lowest" <?php echo $sort == 'lowest' ? 'selected' : ''; ?>>Lowest Rating</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Reviews for <?php echo htmlspecialchars($artist->first_name . ' ' . $artist->last_name); ?></h4>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
                        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            Write a Review
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if(count($reviews) > 0): ?>
                        <?php foreach($reviews as $review): ?>
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex">
                                        <img src="<?php echo !empty($review['profile_image']) ? htmlspecialchars($review['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                             class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;" 
                                             alt="<?php echo htmlspecialchars($review['username']); ?>">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <div>
                                                    <h5 class="mb-0">
                                                        <?php echo !empty($review['first_name']) ? htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) : htmlspecialchars($review['username']); ?>
                                                    </h5>
                                                    <div>
                                                        <?php
                                                        for ($i = 1; $i <= 5; $i++) {
                                                            if ($i <= $review['rating']) {
                                                                echo '<i class="fas fa-star text-warning"></i>';
                                                            } else {
                                                                echo '<i class="far fa-star text-warning"></i>';
                                                            }
                                                        }
                                                        ?>
                                                        <span class="text-muted ms-2"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['reviewer_id']): ?>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="reviewOptions<?php echo $review['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="fas fa-ellipsis-v"></i>
                                                        </button>
                                                        <ul class="dropdown-menu" aria-labelledby="reviewOptions<?php echo $review['id']; ?>">
                                                            <li>
                                                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editReviewModal<?php echo $review['id']; ?>">
                                                                    <i class="fas fa-edit"></i> Edit Review
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteReviewModal<?php echo $review['id']; ?>">
                                                                    <i class="fas fa-trash-alt"></i> Delete Review
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Edit Review Modal -->
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $review['reviewer_id']): ?>
                                <div class="modal fade" id="editReviewModal<?php echo $review['id']; ?>" tabindex="-1" aria-labelledby="editReviewModalLabel<?php echo $review['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editReviewModalLabel<?php echo $review['id']; ?>">Edit Your Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="submit_review.php" method="post">
                                                <div class="modal-body">
                                                    <input type="hidden" name="artist_id" value="<?php echo $artist->id; ?>">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="rating<?php echo $review['id']; ?>" class="form-label">Rating</label>
                                                        <div class="rating">
                                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                                <div class="form-check form-check-inline">
                                                                    <input class="form-check-input" type="radio" name="rating" id="rating<?php echo $review['id'] . '_' . $i; ?>" value="<?php echo $i; ?>" <?php echo $review['rating'] == $i ? 'checked' : ''; ?> required>
                                                                    <label class="form-check-label" for="rating<?php echo $review['id'] . '_' . $i; ?>"><?php echo $i; ?> <i class="far fa-star"></i></label>
                                                                </div>
                                                            <?php endfor; ?>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="review<?php echo $review['id']; ?>" class="form-label">Review</label>
                                                        <textarea class="form-control" id="review<?php echo $review['id']; ?>" name="review" rows="4" required><?php echo htmlspecialchars($review['review']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Review</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Delete Review Modal -->
                                <div class="modal fade" id="deleteReviewModal<?php echo $review['id']; ?>" tabindex="-1" aria-labelledby="deleteReviewModalLabel<?php echo $review['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteReviewModalLabel<?php echo $review['id']; ?>">Delete Review</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to delete your review? This action cannot be undone.</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <form action="delete_review.php" method="post">
                                                    <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                                    <input type="hidden" name="artist_id" value="<?php echo $artist->id; ?>">
                                                    <button type="submit" class="btn btn-danger">Delete Review</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Reviews pagination">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $page-1; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Previous</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $i; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?id=<?php echo $artist_id; ?>&page=<?php echo $page+1; ?><?php echo !empty($sort) ? '&sort='.$sort : ''; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fa-4x text-muted mb-3"></i>
                            <h4>No Reviews Yet</h4>
                            <p class="text-muted">This artist hasn't received any reviews yet.</p>
                            
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
                                <button type="button" class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                    Be the First to Write a Review
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Write a Review for <?php echo htmlspecialchars($artist->first_name . ' ' . $artist->last_name); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="submit_review.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="artist_id" value="<?php echo $artist->id; ?>">
                    
                    <div class="mb-3">
                        <label for="rating" class="form-label">Rating</label>
                        <div class="rating">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating1" value="1" required>
                                <label class="form-check-label" for="rating1">1 <i class="far fa-star"></i></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating2" value="2">
                                <label class="form-check-label" for="rating2">2 <i class="far fa-star"></i></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating3" value="3">
                                <label class="form-check-label" for="rating3">3 <i class="far fa-star"></i></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating4" value="4">
                                <label class="form-check-label" for="rating4">4 <i class="far fa-star"></i></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="rating" id="rating5" value="5">
                                <label class="form-check-label" for="rating5">5 <i class="far fa-star"></i></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="review" class="form-label">Review</label>
                        <textarea class="form-control" id="review" name="review" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
// Include footer
include_once 'includes/footer.php';
?>

