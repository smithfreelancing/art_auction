<?php
/*
Name of file: /artwork.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Display artwork details and handle bidding
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artwork.php';
require_once 'models/Auction.php';
require_once 'models/Bid.php';

// Check if artwork ID is provided
if(!isset($_GET['id'])) {
    header('Location: artworks.php');
    exit();
}

$artwork_id = intval($_GET['id']);

// Database connection
$database = new Database();
$db = $database->connect();

// Create artwork object
$artwork = new Artwork($db);
$artwork->id = $artwork_id;

// Get artwork data
if(!$artwork->read_single()) {
    $_SESSION['message'] = 'Artwork not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: artworks.php');
    exit();
}

// Increment view count
$artwork->increment_views();

// Get artwork categories
$categories = $artwork->get_categories();

// Get artwork tags
$tags = $artwork->get_tags();

// Check if artwork is favorited by current user
$is_favorited = false;
if(isset($_SESSION['user_id'])) {
    $is_favorited = $artwork->is_favorited($_SESSION['user_id']);
}

// Get auction data if it's an auction
$auction = null;
$time_remaining = null;
$user_highest_bid = null;
$is_user_winning = false;
$min_bid = 0;

if($artwork->is_auction) {
    $auction = new Auction($db);
    $auction->artwork_id = $artwork->id;
    
    if($auction->read_by_artwork()) {
        // Check auction status
        $auction->check_status();
        
        // Get time remaining
        $time_remaining = $auction->get_time_remaining();
        
        // Get user's highest bid
        if(isset($_SESSION['user_id'])) {
            $bid = new Bid($db);
            $bid->auction_id = $auction->id;
            $bid->user_id = $_SESSION['user_id'];
            $user_highest_bid = $bid->get_user_highest_bid();
            $is_user_winning = $bid->is_user_winning();
        }
        
        // Calculate minimum bid
        $min_bid = $auction->current_price ? $auction->current_price + $auction->min_bid_increment : $auction->starting_price;
    }
}

// Process bid submission
$bid_errors = [];
$bid_success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    // Check if user is logged in
    if(!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'artwork.php?id=' . $artwork_id;
        header('Location: login.php');
        exit();
    }
    
    // Get bid amount
    $bid_amount = isset($_POST['bid_amount']) ? floatval($_POST['bid_amount']) : 0;
    
    // Validate bid amount
    if($bid_amount <= 0) {
        $bid_errors[] = 'Please enter a valid bid amount.';
    } elseif($bid_amount < $min_bid) {
        $bid_errors[] = 'Bid amount must be at least $' . number_format($min_bid, 2) . '.';
    }
    
    // If no errors, place bid
    if(empty($bid_errors)) {
        try {
            $bid = new Bid($db);
            $bid->auction_id = $auction->id;
            $bid->user_id = $_SESSION['user_id'];
            $bid->amount = $bid_amount;
            
            if($bid->create()) {
                $bid_success = true;
                
                // Refresh auction data
                $auction->read_by_artwork();
                $user_highest_bid = $bid->get_user_highest_bid();
                $is_user_winning = $bid->is_user_winning();
                $min_bid = $auction->current_price + $auction->min_bid_increment;
            } else {
                $bid_errors[] = 'Failed to place bid. Please try again.';
            }
        } catch(Exception $e) {
            $bid_errors[] = $e->getMessage();
        }
    }
}

// Process favorite toggle
if(isset($_POST['toggle_favorite']) && isset($_SESSION['user_id'])) {
    $artwork->toggle_favorite($_SESSION['user_id']);
    $is_favorited = $artwork->is_favorited($_SESSION['user_id']);
}

// Get comments
$comments = $artwork->get_comments(5);
$comment_count = $artwork->count_comments();

// Process comment submission
$comment_errors = [];
$comment_success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    // Check if user is logged in
    if(!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'artwork.php?id=' . $artwork_id;
        header('Location: login.php');
        exit();
    }
    
    // Get comment text
    $comment_text = isset($_POST['comment']) ? clean_input($_POST['comment'], true) : '';
    
    // Validate comment
    if(empty($comment_text)) {
        $comment_errors[] = 'Please enter a comment.';
    }
    
    // If no errors, add comment
    if(empty($comment_errors)) {
        if($artwork->add_comment($_SESSION['user_id'], $comment_text)) {
            $comment_success = true;
            $comments = $artwork->get_comments(5);
            $comment_count = $artwork->count_comments();
        } else {
            $comment_errors[] = 'Failed to add comment. Please try again.';
        }
    }
}

// Get similar artworks
$similar_artworks = [];
if(!empty($categories)) {
    $category_ids = array_column($categories, 'id');
    $category_id = $category_ids[0]; // Use first category for simplicity
    
    $query = "SELECT a.*, u.username, u.first_name, u.last_name
              FROM artworks a
              JOIN users u ON a.user_id = u.id
              JOIN artwork_category_relationships acr ON a.id = acr.artwork_id
              WHERE acr.category_id = :category_id
              AND a.id != :artwork_id
              AND a.status = 'active'
              ORDER BY a.created_at DESC
              LIMIT 4";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $category_id);
    $stmt->bindParam(':artwork_id', $artwork->id);
    $stmt->execute();
    $similar_artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Set page title
$pageTitle = htmlspecialchars($artwork->title) . ' by ' . htmlspecialchars($artwork->artist_first_name . ' ' . $artwork->artist_last_name);

// Set meta tags for SEO
$meta_description = substr(strip_tags($artwork->description), 0, 160);
$meta_keywords = $artwork->title . ', ' . $artwork->artist_first_name . ' ' . $artwork->artist_last_name;

foreach($tags as $tag) {
    $meta_keywords .= ', ' . $tag['name'];
}

$additional_head_content = '
<meta name="description" content="' . htmlspecialchars($meta_description) . '">
<meta name="keywords" content="' . htmlspecialchars($meta_keywords) . '">
<meta property="og:title" content="' . htmlspecialchars($pageTitle) . '">
<meta property="og:description" content="' . htmlspecialchars($meta_description) . '">
<meta property="og:image" content="https://' . $_SERVER['HTTP_HOST'] . htmlspecialchars($artwork->image_path) . '">
<meta property="og:url" content="https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">
<meta property="og:type" content="product">
<meta property="product:price:amount" content="' . ($artwork->is_auction ? $artwork->starting_price : $artwork->price) . '">
<meta property="product:price:currency" content="USD">
';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="artworks.php">Artworks</a></li>
                        <?php if(!empty($categories)): ?>
                <li class="breadcrumb-item"><a href="artworks.php?category=<?php echo urlencode($categories[0]['name']); ?>"><?php echo htmlspecialchars($categories[0]['name']); ?></a></li>
            <?php endif; ?>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($artwork->title); ?></li>
        </ol>
    </nav>
    
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
    <?php endif; ?>
    
    <div class="row">
        <!-- Artwork Images -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body p-0">
                    <div id="artworkCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <div class="carousel-item active">
                                <img src="<?php echo htmlspecialchars($artwork->image_path); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($artwork->title); ?>" style="max-height: 500px; object-fit: contain;">
                            </div>
                            <?php 
                            if(!empty($artwork->additional_images)) {
                                $additional_images = json_decode($artwork->additional_images, true);
                                if(is_array($additional_images)) {
                                    foreach($additional_images as $image) {
                                        echo '<div class="carousel-item">';
                                        echo '<img src="' . htmlspecialchars($image) . '" class="d-block w-100" alt="' . htmlspecialchars($artwork->title) . '" style="max-height: 500px; object-fit: contain;">';
                                        echo '</div>';
                                    }
                                }
                            }
                            ?>
                        </div>
                        <?php if(!empty($artwork->additional_images) && count(json_decode($artwork->additional_images, true)) > 0): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#artworkCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#artworkCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(!empty($artwork->additional_images) && count(json_decode($artwork->additional_images, true)) > 0): ?>
                        <div class="d-flex mt-2 overflow-auto">
                            <div class="thumbnail-item me-2" data-bs-target="#artworkCarousel" data-bs-slide-to="0">
                                <img src="<?php echo htmlspecialchars($artwork->image_path); ?>" class="img-thumbnail" alt="Thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                            </div>
                            <?php 
                            $additional_images = json_decode($artwork->additional_images, true);
                            if(is_array($additional_images)) {
                                foreach($additional_images as $index => $image) {
                                    echo '<div class="thumbnail-item me-2" data-bs-target="#artworkCarousel" data-bs-slide-to="' . ($index + 1) . '">';
                                    echo '<img src="' . htmlspecialchars($image) . '" class="img-thumbnail" alt="Thumbnail" style="width: 80px; height: 80px; object-fit: cover;">';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Artwork Details -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h1 class="card-title"><?php echo htmlspecialchars($artwork->title); ?></h1>
                        <form method="post" class="ms-2">
                            <button type="submit" name="toggle_favorite" class="btn btn-outline-danger btn-sm">
                                <i class="<?php echo $is_favorited ? 'fas' : 'far'; ?> fa-heart"></i>
                            </button>
                        </form>
                    </div>
                    
                    <h5 class="mb-3">
                        <a href="artist.php?id=<?php echo $artwork->user_id; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($artwork->artist_first_name . ' ' . $artwork->artist_last_name); ?>
                        </a>
                    </h5>
                    
                    <div class="mb-4">
                        <?php foreach($categories as $category): ?>
                            <a href="artworks.php?category=<?php echo urlencode($category['name']); ?>" class="badge bg-primary text-decoration-none me-1">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </a>
                        <?php endforeach; ?>
                        
                        <?php foreach($tags as $tag): ?>
                            <a href="artworks.php?tag=<?php echo urlencode($tag['name']); ?>" class="badge bg-secondary text-decoration-none me-1">
                                <?php echo htmlspecialchars($tag['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-4">
                        <p><?php echo nl2br(htmlspecialchars($artwork->description)); ?></p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p><strong>Medium:</strong> <?php echo htmlspecialchars($artwork->medium); ?></p>
                            <?php if(!empty($artwork->dimensions)): ?>
                                <p><strong>Dimensions:</strong> <?php echo htmlspecialchars($artwork->dimensions); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Year Created:</strong> <?php echo htmlspecialchars($artwork->year_created); ?></p>
                            <p><strong>Views:</strong> <?php echo number_format($artwork->views); ?></p>
                        </div>
                    </div>
                    
                    <?php if($artwork->is_auction && $auction): ?>
                        <!-- Auction Details -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Auction Details</h4>
                                
                                <?php if($auction->status === 'active'): ?>
                                    <div class="mb-3">
                                        <p class="mb-1"><strong>Current Bid:</strong> $<?php echo number_format($auction->current_price ?? $auction->starting_price, 2); ?></p>
                                        <p class="mb-1"><strong>Bids:</strong> <?php echo $auction->bid_count; ?></p>
                                        <p class="mb-0"><strong>Time Remaining:</strong> <span id="timeRemaining"><?php echo $auction->format_time_remaining(); ?></span></p>
                                        
                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artwork->user_id): ?>
                                            <?php if($user_highest_bid): ?>
                                                <div class="alert <?php echo $is_user_winning ? 'alert-success' : 'alert-warning'; ?> mt-3 mb-0">
                                                    <p class="mb-0">
                                                        <strong>Your highest bid:</strong> $<?php echo number_format($user_highest_bid, 2); ?>
                                                        <?php if($is_user_winning): ?>
                                                            <span class="badge bg-success ms-2">Winning</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark ms-2">Outbid</span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if($bid_success): ?>
                                                <div class="alert alert-success mt-3">
                                                    <p class="mb-0">Your bid has been placed successfully!</p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(!empty($bid_errors)): ?>
                                                <div class="alert alert-danger mt-3">
                                                    <ul class="mb-0">
                                                        <?php foreach($bid_errors as $error): ?>
                                                            <li><?php echo $error; ?></li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <form method="post" class="mt-3">
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" class="form-control" name="bid_amount" min="<?php echo $min_bid; ?>" step="0.01" value="<?php echo $min_bid; ?>" required>
                                                    <button type="submit" name="place_bid" class="btn btn-primary">Place Bid</button>
                                                </div>
                                                <div class="form-text">Minimum bid: $<?php echo number_format($min_bid, 2); ?></div>
                                            </form>
                                        <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $artwork->user_id): ?>
                                            <div class="alert alert-info mt-3 mb-0">
                                                <p class="mb-0">This is your auction. You cannot bid on your own artwork.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mt-3 mb-0">
                                                <p class="mb-0">Please <a href="login.php">login</a> to place a bid.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif($auction->status === 'ended'): ?>
                                    <div class="alert alert-info mb-0">
                                        <p class="mb-1"><strong>Final Price:</strong> $<?php echo number_format($auction->current_price, 2); ?></p>
                                        <p class="mb-1"><strong>Bids:</strong> <?php echo $auction->bid_count; ?></p>
                                        <p class="mb-0"><strong>Status:</strong> Auction ended</p>
                                        <?php if($auction->winner_id): ?>
                                            <p class="mb-0 mt-2"><strong>Winner:</strong> <?php echo htmlspecialchars($auction->winner_username); ?></p>
                                        <?php else: ?>
                                            <p class="mb-0 mt-2">No winner (reserve price not met)</p>
                                        <?php endif; ?>
                                    </div>
                                <?php elseif($auction->status === 'pending'): ?>
                                    <div class="alert alert-warning mb-0">
                                        <p class="mb-1"><strong>Starting Price:</strong> $<?php echo number_format($auction->starting_price, 2); ?></p>
                                        <p class="mb-0"><strong>Status:</strong> Auction starts on <?php echo date('M d, Y \a\t h:i A', strtotime($auction->start_time)); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary mb-0">
                                        <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($auction->status); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Fixed Price Details -->
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h4 class="card-title">Price</h4>
                                <p class="display-6 mb-3">$<?php echo number_format($artwork->price, 2); ?></p>
                                
                                <?php if($artwork->status === 'active'): ?>
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artwork->user_id): ?>
                                        <a href="purchase.php?id=<?php echo $artwork->id; ?>" class="btn btn-primary btn-lg">Buy Now</a>
                                    <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $artwork->user_id): ?>
                                        <div class="alert alert-info mb-0">
                                            <p class="mb-0">This is your artwork.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <p class="mb-0">Please <a href="login.php">login</a> to purchase.</p>
                                        </div>
                                    <?php endif; ?>
                                <?php elseif($artwork->status === 'sold'): ?>
                                    <div class="alert alert-secondary mb-0">
                                        <p class="mb-0"><strong>Status:</strong> Sold</p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-secondary mb-0">
                                        <p class="mb-0"><strong>Status:</strong> <?php echo ucfirst($artwork->status); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Artist Info -->
                    <div class="d-flex align-items-center mt-4">
                        <img src="<?php echo !empty($artwork->artist_profile_image) ? htmlspecialchars($artwork->artist_profile_image) : '/assets/images/default-profile.jpg'; ?>" 
                             class="rounded-circle me-3" style="width: 60px; height: 60px; object-fit: cover;" 
                             alt="<?php echo htmlspecialchars($artwork->artist_username); ?>">
                        <div>
                            <h5 class="mb-1">
                                <a href="artist.php?id=<?php echo $artwork->user_id; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($artwork->artist_first_name . ' ' . $artwork->artist_last_name); ?>
                                </a>
                            </h5>
                            <p class="mb-0 text-muted">@<?php echo htmlspecialchars($artwork->artist_username); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Comments Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Comments (<?php echo $comment_count; ?>)</h4>
                </div>
                <div class="card-body">
                    <?php if($comment_success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            Your comment has been added successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($comment_errors)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <ul class="mb-0">
                                <?php foreach($comment_errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <form method="post" class="mb-4">
                            <div class="mb-3">
                                <label for="comment" class="form-label">Add a Comment</label>
                                <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                            </div>
                            <button type="submit" name="add_comment" class="btn btn-primary">Submit</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-4">
                            <p class="mb-0">Please <a href="login.php">login</a> to leave a comment.</p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($comments)): ?>
                        <?php foreach($comments as $comment): ?>
                            <div class="d-flex mb-4">
                                <img src="<?php echo !empty($comment['profile_image']) ? htmlspecialchars($comment['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                     class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                     alt="<?php echo htmlspecialchars($comment['username']); ?>">
                                <div>
                                    <h6 class="mb-1">
                                        <?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?>
                                        <small class="text-muted">@<?php echo htmlspecialchars($comment['username']); ?></small>
                                    </h6>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                                    <small class="text-muted"><?php echo date('M d, Y \a\t h:i A', strtotime($comment['created_at'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if($comment_count > count($comments)): ?>
                            <div class="text-center">
                                <a href="artwork_comments.php?id=<?php echo $artwork->id; ?>" class="btn btn-outline-primary">
                                    View All Comments (<?php echo $comment_count; ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted text-center">No comments yet. Be the first to comment!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Similar Artworks -->
    <?php if(!empty($similar_artworks)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <h3>Similar Artworks</h3>
                <div class="row">
                    <?php foreach($similar_artworks as $similar): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($similar['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar['title']); ?>" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($similar['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        By <?php echo htmlspecialchars($similar['first_name'] . ' ' . $similar['last_name']); ?>
                                    </p>
                                    <p class="card-text">
                                        <?php if($similar['is_auction']): ?>
                                            <strong>Starting at:</strong> $<?php echo number_format($similar['starting_price'], 2); ?>
                                        <?php else: ?>
                                            <strong>Price:</strong> $<?php echo number_format($similar['price'], 2); ?>
                                        <?php endif; ?>
                                    </p>
                                    <a href="artwork.php?id=<?php echo $similar['id']; ?>" class="btn btn-outline-primary">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if($artwork->is_auction && $auction && $auction->status === 'active'): ?>
<script>
// Countdown timer for auction
document.addEventListener('DOMContentLoaded', function() {
    const endTime = <?php echo strtotime($auction->end_time) * 1000; ?>;
    const timeRemainingElement = document.getElementById('timeRemaining');
    
    function updateTimer() {
        const now = new Date().getTime();
        const distance = endTime - now;
        
        if (distance <= 0) {
            clearInterval(timerInterval);
            timeRemainingElement.innerHTML = 'Auction ended';
            location.reload(); // Reload page when auction ends
            return;
        }
        
        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);
        
        let timeString = '';
        
        if (days > 0) {
            timeString += days + 'd ';
        }
        
        if (hours > 0 || days > 0) {
            timeString += hours + 'h ';
        }
        
        if (minutes > 0 || hours > 0 || days > 0) {
            timeString += minutes + 'm ';
        }
        
        timeString += seconds + 's';
        
        timeRemainingElement.innerHTML = timeString;
    }
    
    updateTimer();
    const timerInterval = setInterval(updateTimer, 1000);
});

// Initialize carousel thumbnails
document.addEventListener('DOMContentLoaded', function() {
    const thumbnails = document.querySelectorAll('.thumbnail-item');
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const slideIndex = this.getAttribute('data-bs-slide-to');
            const carousel = document.getElementById('artworkCarousel');
            const bsCarousel = bootstrap.Carousel.getInstance(carousel);
            bsCarousel.to(parseInt(slideIndex));
        });
    });
});
</script>
<?php endif; ?>

<?php
// Include footer
include_once 'includes/footer.php';
?>

