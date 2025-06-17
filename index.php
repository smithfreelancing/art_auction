<?php
/*
Name of file: /index.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Main entry point for the application, displays featured artworks
*/

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = 'Art Auction - Discover and Bid on Digital Art';

// Include header
include_once 'includes/header.php';

// Display any messages
if (function_exists('display_message')) {
    display_message();
}

// Database connection
try {
    $database = new Database();
    $conn = $database->connect();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get featured artworks
    $query = "SELECT a.*, u.username as artist_name 
              FROM artworks a 
              JOIN users u ON a.user_id = u.id 
              WHERE a.status = 'active' 
              ORDER BY a.created_at DESC 
              LIMIT 6";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $artworks = $stmt->fetchAll();
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    $artworks = [];
}
?>

<!-- Hero Section -->
<div class="bg-dark text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="display-4">Discover & Collect Digital Art</h1>
                <p class="lead">Bid on exclusive digital artworks or buy them instantly. Connect with talented artists from around the world.</p>
                <div class="mt-4">
                    <a href="/artworks.php" class="btn btn-primary btn-lg me-2">
                        <i class="fas fa-palette"></i> Explore Artworks
                    </a>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="/register.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-user-plus"></i> Join Now
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-md-6">
                <img src="/assets/images/hero-image.jpg" alt="Digital Art Collage" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</div>

<!-- Featured Artworks -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Artworks</h2>
        <div class="row">
            <?php if(empty($artworks)): ?>
                <div class="col-12 text-center">
                    <p>No artworks available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach($artworks as $artwork): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($artwork['title']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($artwork['title']); ?></h5>
                                <p class="card-text text-muted">By <?php echo htmlspecialchars($artwork['artist_name']); ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="mb-0">Current Bid:</p>
                                        <h5 class="text-primary"><?php echo function_exists('format_price') ? format_price($artwork['current_price'] ?? $artwork['starting_price']) : '$' . number_format($artwork['current_price'] ?? $artwork['starting_price'], 2); ?></h5>
                                    </div>
                                    <div>
                                        <p class="mb-0">Ends in:</p>
                                        <span class="badge bg-warning text-dark"><?php echo function_exists('time_remaining') ? time_remaining($artwork['auction_end']) : 'Check details'; ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="/artwork.php?id=<?php echo $artwork['id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="/artworks.php" class="btn btn-primary">View All Artworks</a>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="bg-light py-5 mb-5">
    <div class="container">
        <h2 class="text-center mb-5">How It Works</h2>
        <div class="row text-center">
            <div class="col-md-4 mb-4">
                <div class="p-3">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>Create an Account</h4>
                    <p>Sign up as a collector to bid on artworks or as an artist to sell your digital creations.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-3">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-gavel"></i>
                    </div>
                    <h4>Bid or Buy Now</h4>
                    <p>Place bids on your favorite artworks or use the Buy Now option to purchase instantly.</p>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="p-3">
                    <div class="display-4 text-primary mb-3">
                        <i class="fas fa-paint-brush"></i>
                    </div>
                    <h4>Collect Digital Art</h4>
                    <p>Build your collection of unique digital artworks from talented artists worldwide.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body p-4">
                        <h3>Are You an Artist?</h3>
                        <p>Showcase your digital art to collectors around the world. Set your prices and start earning.</p>
                        <a href="/register.php?type=artist" class="btn btn-light">Join as Artist</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card bg-dark text-white h-100">
                    <div class="card-body p-4">
                        <h3>Art Collector?</h3>
                        <p>Discover unique digital artworks from emerging and established artists. Bid or buy now.</p>
                        <a href="/register.php" class="btn btn-light">Start Collecting</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Artists -->
<section class="mb-5">
    <div class="container">
        <h2 class="text-center mb-4">Featured Artists</h2>
        <div class="row">
            <?php
            try {
                // Get featured artists
                $query = "SELECT u.id, u.username, u.profile_image, u.bio, COUNT(a.id) as artwork_count 
                          FROM users u 
                          LEFT JOIN artworks a ON u.id = a.user_id 
                          WHERE u.user_type = 'artist' 
                          GROUP BY u.id 
                          ORDER BY artwork_count DESC 
                          LIMIT 3";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $artists = $stmt->fetchAll();
                
                if (!empty($artists)):
                    foreach($artists as $artist):
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body text-center">
                            <img src="<?php echo !empty($artist['profile_image']) ? htmlspecialchars($artist['profile_image']) : '/assets/images/default-profile.jpg'; ?>" 
                                 class="rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover;" 
                                 alt="<?php echo htmlspecialchars($artist['username']); ?>">
                            <h5 class="card-title"><?php echo htmlspecialchars($artist['username']); ?></h5>
                            <p class="text-muted"><?php echo $artist['artwork_count']; ?> Artwork<?php echo $artist['artwork_count'] != 1 ? 's' : ''; ?></p>
                            <p class="card-text"><?php echo !empty($artist['bio']) ? htmlspecialchars(substr($artist['bio'], 0, 100)) . '...' : 'No bio available'; ?></p>
                            <a href="/artist.php?id=<?php echo $artist['id']; ?>" class="btn btn-outline-primary">View Profile</a>
                        </div>
                    </div>
                </div>
            <?php
                    endforeach;
                else:
            ?>
                <div class="col-12 text-center">
                    <p>No featured artists available at the moment.</p>
                </div>
            <?php
                endif;
            } catch (Exception $e) {
                echo '<div class="col-12 text-center"><p>Unable to load artists at this time.</p></div>';
            }
            ?>
        </div>
        <div class="text-center mt-4">
            <a href="/artists.php" class="btn btn-primary">View All Artists</a>
        </div>
    </div>
</section>

<!-- Testimonials -->
<section class="bg-light py-5 mb-5">
    <div class="container">
        <h2 class="text-center mb-5">What Our Users Say</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"As an artist, this platform has given me exposure to collectors worldwide. The bidding system is transparent and the payments are prompt."</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <img src="/assets/images/testimonial-1.jpg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0">Sarah Johnson</h6>
                                <small class="text-muted">Digital Artist</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <p class="card-text">"I've built an impressive collection of digital art through this platform. The buy-now option is perfect when I find something I absolutely must have!"</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <img src="/assets/images/testimonial-2.jpg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0">Michael Chen</h6>
                                <small class="text-muted">Art Collector</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="mb-3 text-warning">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="card-text">"The auction platform is intuitive and exciting. I love the thrill of bidding on unique pieces and the community of art enthusiasts is fantastic!"</p>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex align-items-center">
                            <img src="/assets/images/testimonial-3.jpg" alt="User" class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div>
                                <h6 class="mb-0">Emily Rodriguez</h6>
                                <small class="text-muted">Art Enthusiast</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Signup -->
<section class="mb-5">
    <div class="container">
        <div class="card bg-primary text-white">
            <div class="card-body p-5 text-center">
                <h3>Stay Updated with New Artworks</h3>
                <p class="lead">Subscribe to our newsletter to receive updates on new artworks, upcoming auctions, and featured artists.</p>
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <form id="newsletterForm" action="/subscribe.php" method="post">
                            <div class="input-group mb-3">
                                <input type="email" class="form-control" placeholder="Your Email Address" aria-label="Your Email Address" required>
                                <button class="btn btn-light" type="submit">Subscribe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>

