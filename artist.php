<?php
/*
Name of file: /artist.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Display an artist's profile and portfolio
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artist.php';
require_once 'models/User.php';

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

// Get artist statistics
$stats = $artist->get_statistics();

// Generate meta description
$meta_description = "View " . htmlspecialchars($artist->first_name . ' ' . $artist->last_name) . "'s artist profile on Art Auction. ";
if(!empty($artist->bio)) {
    $meta_description .= substr(strip_tags($artist->bio), 0, 150) . "...";
} else {
    $meta_description .= "Browse their portfolio, read reviews, and discover their artwork.";
}

// Generate meta keywords
$meta_keywords = "artist, art, " . htmlspecialchars($artist->first_name . ' ' . $artist->last_name);
if(!empty($artist->specialties)) {
    $meta_keywords .= ", " . htmlspecialchars($artist->specialties);
}

// Add meta tags to header
$additional_head_content = '
<meta name="description" content="' . $meta_description . '">
<meta name="keywords" content="' . $meta_keywords . '">
<meta property="og:title" content="' . htmlspecialchars($artist->first_name . ' ' . $artist->last_name) . ' - Artist Profile">
<meta property="og:description" content="' . $meta_description . '">
<meta property="og:type" content="profile">
<meta property="og:url" content="https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . '">
';

// Add profile image if available
if(!empty($artist->profile_image)) {
    $image_url = 'https://' . $_SERVER['HTTP_HOST'] . htmlspecialchars($artist->profile_image);
    $additional_head_content .= '<meta property="og:image" content="' . $image_url . '">';
}

// Set page title
$pageTitle = htmlspecialchars($artist->first_name . ' ' . $artist->last_name) . ' - Artist Profile';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <!-- Artist Profile Sidebar -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo !empty($artist->profile_image) ? htmlspecialchars($artist->profile_image) : '/assets/images/default-profile.jpg'; ?>" 
                         class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;" 
                         alt="<?php echo htmlspecialchars($artist->username); ?>">
                    
                    <h3>
                        <?php echo htmlspecialchars($artist->first_name . ' ' . $artist->last_name); ?>
                        
                        <?php
                        // Check if artist is verified
                        $query = "SELECT artist_verified FROM users WHERE id = :id AND user_type = 'artist'";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':id', $artist->id);
                        $stmt->execute();
                        $artist_verified = $stmt->fetchColumn();

                        // Display verification badge if artist is verified
                        if($artist_verified) {
                            echo '<span class="badge bg-primary ms-2" title="Verified Artist"><i class="fas fa-check-circle"></i> Verified</span>';
                        }
                        ?>
                    </h3>
                    <p class="text-muted">@<?php echo htmlspecialchars($artist->username); ?></p>
                    
                    <?php if($artist->featured): ?>
                        <span class="badge bg-warning text-dark mb-3">Featured Artist</span>
                    <?php endif; ?>
                    
                    <!-- Artist Rating -->
                    <div class="mb-3">
                        <?php
                        $rating = $stats['avg_rating'];
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
                        <span class="ms-2"><?php echo number_format($rating, 1); ?> (<?php echo $stats['review_count']; ?> reviews)</span>
                    </div>
                    
                    <!-- Artist Stats -->
                    <div class="row text-center mb-3">
                        <div class="col">
                            <h5><?php echo $stats['total_artworks']; ?></h5>
                            <small class="text-muted">Artworks</small>
                        </div>
                        <div class="col">
                            <h5><?php echo $stats['active_auctions']; ?></h5>
                            <small class="text-muted">Active</small>
                        </div>
                        <div class="col">
                            <h5><?php echo $stats['completed_sales']; ?></h5>
                            <small class="text-muted">Sold</small>
                        </div>
                    </div>
                    
                    <!-- Social Links -->
                    <?php if(!empty($artist->website) || !empty($artist->social_media)): ?>
                        <div class="mb-3">
                            <?php if(!empty($artist->website)): ?>
                                <a href="<?php echo htmlspecialchars($artist->website); ?>" target="_blank" class="btn btn-sm btn-outline-primary me-2">
                                    <i class="fas fa-globe"></i> Website
                                </a>
                            <?php endif; ?>
                            
                            <?php if(!empty($artist->social_media)): ?>
                                <?php 
                                $social_links = json_decode($artist->social_media, true);
                                if(is_array($social_links)) {
                                    foreach($social_links as $platform => $url) {
                                        $icon = 'fa-link';
                                        switch(strtolower($platform)) {
                                            case 'facebook': $icon = 'fa-facebook-f'; break;
                                            case 'twitter': $icon = 'fa-twitter'; break;
                                            case 'instagram': $icon = 'fa-instagram'; break;
                                            case 'linkedin': $icon = 'fa-linkedin-in'; break;
                                            case 'youtube': $icon = 'fa-youtube'; break;
                                        }
                                        echo '<a href="' . htmlspecialchars($url) . '" target="_blank" class="btn btn-sm btn-outline-secondary me-1 mb-1">';
                                        echo '<i class="fab ' . $icon . '"></i>';
                                        echo '</a>';
                                    }
                                }
                                ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Contact Button -->
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
                        <a href="messages.php?to=<?php echo $artist->id; ?>" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Contact Artist
                        </a>
                    <?php endif; ?>
                    
                    <!-- Edit Profile Button (for the artist themselves) -->
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $artist->id): ?>
                        <a href="edit_artist_profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Artist Specialties -->
            <?php if(!empty($artist->specialties)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Specialties</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $specialties = explode(',', $artist->specialties);
                        foreach($specialties as $specialty) {
                            $specialty = trim($specialty);
                            if(!empty($specialty)) {
                                echo '<span class="badge bg-secondary me-1 mb-1">' . htmlspecialchars($specialty) . '</span>';
                            }
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php
            // Get artist categories
            $query = "SELECT ac.name 
                      FROM artist_categories ac
                      JOIN artist_category_relationships acr ON ac.id = acr.category_id
                      WHERE acr.artist_id = :artist_id
                      ORDER BY ac.name";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':artist_id', $artist->id);
            $stmt->execute();
            $artist_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Display categories if any
            if(!empty($artist_categories)):
            ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Categories</h5>
                </div>
                <div class="card-body">
                    <?php foreach($artist_categories as $category): ?>
                        <span class="badge bg-primary me-1 mb-1"><?php echo htmlspecialchars($category); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Member Since -->
            <div class="card mt-4">
                <div class="card-body">
                    <p class="mb-0"><i class="fas fa-calendar-alt"></i> Member since <?php echo date('F Y', strtotime($artist->created_at)); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Artist Content -->
        <div class="col-md-8">
            <!-- Artist Bio -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">About the Artist</h4>
                </div>
                <div class="card-body">
                    <?php if(!empty($artist->bio)): ?>
                        <p><?php echo nl2br(htmlspecialchars($artist->bio)); ?></p>
                    <?php else: ?>
                        <p class="text-muted">This artist hasn't added a bio yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Artist Portfolio -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Portfolio</h4>
                    <a href="artist_portfolio.php?id=<?php echo $artist->id; ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php
                    // Get artist's artworks
                    $query = "SELECT * FROM artworks WHERE user_id = :artist_id ORDER BY created_at DESC LIMIT 6";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':artist_id', $artist->id);
                    $stmt->execute();
                    $artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($artworks) > 0):
                    ?>
                        <div class="row">
                            <?php foreach($artworks as $artwork): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($artwork['title']); ?>" style="height: 150px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h6>
                                            <p class="card-text text-muted small"><?php echo substr(htmlspecialchars($artwork['description']), 0, 50) . '...'; ?></p>
                                            <a href="artwork.php?id=<?php echo $artwork['id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">This artist hasn't uploaded any artworks yet.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Artist Reviews -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Reviews</h4>
                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artist->id): ?>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#reviewModal">
                            Write a Review
                        </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php
                    // Get artist reviews
                    $query = "SELECT r.*, u.username, u.profile_image 
                              FROM artist_reviews r 
                              JOIN users u ON r.reviewer_id = u.id 
                              WHERE r.artist_id = :artist_id 
                              ORDER BY r.created_at DESC 
                              LIMIT 5";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':artist_id', $artist->id);
                    $stmt->execute();
                    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($reviews) > 0):
                        foreach($reviews as $review):
                    ?>
                        <div class="d-flex mb-4">
                            <img src="<?php echo !empty($review['profile_image']) ? htmlspecialchars($review['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($review['username']); ?>">
                            <div>
                                <div class="d-flex align-items-center mb-1">
                                    <h6 class="mb-0 me-2"><?php echo htmlspecialchars($review['username']); ?></h6>
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
                                    </div>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($review['review'])); ?></p>
                                <small class="text-muted"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></small>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                        
                        // If there are more than 5 reviews, show a link to view all
                        $query = "SELECT COUNT(*) as count FROM artist_reviews WHERE artist_id = :artist_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':artist_id', $artist->id);
                        $stmt->execute();
                        $total_reviews = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        
                        if($total_reviews > 5):
                    ?>
                        <div class="text-center mt-3">
                            <a href="artist_reviews.php?id=<?php echo $artist->id; ?>" class="btn btn-outline-primary">
                                View All <?php echo $total_reviews; ?> Reviews
                            </a>
                        </div>
                    <?php 
                        endif;
                    else: 
                    ?>
                        <p class="text-muted">This artist hasn't received any reviews yet.</p>
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





