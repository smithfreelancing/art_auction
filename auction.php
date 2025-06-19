<?php
/*
Name of file: /auction.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Display auction details and bid history
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artwork.php';
require_once 'models/Auction.php';
require_once 'models/Bid.php';

// Check if auction ID is provided
if(!isset($_GET['id'])) {
    header('Location: auctions.php');
    exit();
}

$auction_id = intval($_GET['id']);

// Database connection
$database = new Database();
$db = $database->connect();

// Create auction object
$auction = new Auction($db);
$auction->id = $auction_id;

// Get auction data
if(!$auction->read_single()) {
    $_SESSION['message'] = 'Auction not found.';
    $_SESSION['message_type'] = 'danger';
    header('Location: auctions.php');
    exit();
}

// Check auction status
$auction->check_status();

// Get artwork data
$artwork = new Artwork($db);
$artwork->id = $auction->artwork_id;
$artwork->read_single();

// Get bid history
$bid = new Bid($db);
$bid->auction_id = $auction_id;
$bids = $bid->get_auction_bids(50);
$bid_count = $bid->count_auction_bids();

// Get user's highest bid
$user_highest_bid = null;
$is_user_winning = false;

if(isset($_SESSION['user_id'])) {
    $bid->user_id = $_SESSION['user_id'];
    $user_highest_bid = $bid->get_user_highest_bid();
    $is_user_winning = $bid->is_user_winning();
}

// Calculate minimum bid
$min_bid = $auction->current_price ? $auction->current_price + $auction->min_bid_increment : $auction->starting_price;

// Process bid submission
$bid_errors = [];
$bid_success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    // Check if user is logged in
    if(!isset($_SESSION['user_id'])) {
        $_SESSION['redirect_after_login'] = 'auction.php?id=' . $auction_id;
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
            $bid->auction_id = $auction_id;
            $bid->user_id = $_SESSION['user_id'];
            $bid->amount = $bid_amount;
            
            if($bid->create()) {
                $bid_success = true;
                
                // Refresh auction data
                $auction->read_single();
                $user_highest_bid = $bid->get_user_highest_bid();
                $is_user_winning = $bid->is_user_winning();
                $min_bid = $auction->current_price + $auction->min_bid_increment;
                
                // Refresh bid history
                $bids = $bid->get_auction_bids(50);
                $bid_count = $bid->count_auction_bids();
            } else {
                $bid_errors[] = 'Failed to place bid. Please try again.';
            }
        } catch(Exception $e) {
            $bid_errors[] = $e->getMessage();
        }
    }
}

// Get time remaining
$time_remaining = $auction->get_time_remaining();

// Set page title
$pageTitle = 'Auction: ' . htmlspecialchars($artwork->title);

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="auctions.php">Auctions</a></li>
            <li class="breadcrumb-item"><a href="artwork.php?id=<?php echo $artwork->id; ?>"><?php echo htmlspecialchars($artwork->title); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Auction Details</li>
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
        <!-- Auction Details -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Auction Details</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-4">
                            <img src="<?php echo htmlspecialchars($artwork->image_path); ?>" class="img-fluid rounded" alt="<?php echo htmlspecialchars($artwork->title); ?>">
                        </div>
                        <div class="col-md-8">
                            <h3><?php echo htmlspecialchars($artwork->title); ?></h3>
                            <p>
                                By <a href="artist.php?id=<?php echo $artwork->user_id; ?>" class="text-decoration-none">
                                    <?php echo htmlspecialchars($artwork->artist_first_name . ' ' . $artwork->artist_last_name); ?>
                                </a>
                            </p>
                            <p><?php echo nl2br(htmlspecialchars(substr($artwork->description, 0, 200))); ?>...</p>
                            <p><a href="artwork.php?id=<?php echo $artwork->id; ?>" class="btn btn-outline-primary btn-sm">View Artwork Details</a></p>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Auction Information</h5>
                            <ul class="list-unstyled">
                                <li><strong>Status:</strong> <?php echo ucfirst($auction->status); ?></li>
                                <li><strong>Starting Price:</strong> $<?php echo number_format($auction->starting_price, 2); ?></li>
                                <?php if($auction->current_price): ?>
                                    <li><strong>Current Bid:</strong> $<?php echo number_format($auction->current_price, 2); ?></li>
                                <?php endif; ?>
                                <?php if($auction->reserve_price): ?>
                                    <li><strong>Reserve Price:</strong> $<?php echo number_format($auction->reserve_price, 2); ?></li>
                                <?php endif; ?>
                                <li><strong>Minimum Bid Increment:</strong> $<?php echo number_format($auction->min_bid_increment, 2); ?></li>
                                <li><strong>Total Bids:</strong> <?php echo $bid_count; ?></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Auction Timeline</h5>
                            <ul class="list-unstyled">
                                <li><strong>Start Time:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($auction->start_time)); ?></li>
                                <li><strong>End Time:</strong> <?php echo date('M d, Y \a\t h:i A', strtotime($auction->end_time)); ?></li>
                                <?php if($auction->status === 'active'): ?>
                                    <li><strong>Time Remaining:</strong> <span id="timeRemaining"><?php echo $auction->format_time_remaining(); ?></span></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if($auction->status === 'active'): ?>
                        <div class="mt-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Place a Bid</h5>
                                    
                                    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $artwork->user_id): ?>
                                        <?php if($user_highest_bid): ?>
                                            <div class="alert <?php echo $is_user_winning ? 'alert-success' : 'alert-warning'; ?> mb-3">
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
                                            <div class="alert alert-success mb-3">
                                                <p class="mb-0">Your bid has been placed successfully!</p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if(!empty($bid_errors)): ?>
                                            <div class="alert alert-danger mb-3">
                                                <ul class="mb-0">
                                                    <?php foreach($bid_errors as $error): ?>
                                                        <li><?php echo $error; ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form method="post" class="row g-3">
                                            <div class="col-md-8">
                                                <div class="input-group">
                                                    <span class="input-group-text">$</span>
                                                    <input type="number" class="form-control" name="bid_amount" min="<?php echo $min_bid; ?>" step="0.01" value="<?php echo $min_bid; ?>" required>
                                                </div>
                                                <div class="form-text">Minimum bid: $<?php echo number_format($min_bid, 2); ?></div>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="submit" name="place_bid" class="btn btn-primary w-100">Place Bid</button>
                                            </div>
                                        </form>
                                    <?php elseif(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $artwork->user_id): ?>
                                        <div class="alert alert-info mb-0">
                                            <p class="mb-0">This is your auction. You cannot bid on your own artwork.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info mb-0">
                                            <p class="mb-0">Please <a href="login.php">login</a> to place a bid.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php elseif($auction->status === 'ended'): ?>
                        <div class="mt-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Auction Ended</h5>
                                    <p class="mb-0">
                                        This auction ended on <?php echo date('M d, Y \a\t h:i A', strtotime($auction->end_time)); ?>.
                                        <?php if($auction->winner_id): ?>
                                            The winning bid was $<?php echo number_format($auction->current_price, 2); ?> by <?php echo htmlspecialchars($auction->winner_username); ?>.
                                        <?php else: ?>
                                            There was no winner for this auction.
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php elseif($auction->status === 'pending'): ?>
                        <div class="mt-4">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h5 class="card-title">Auction Not Started</h5>
                                    <p class="mb-0">
                                        This auction will start on <?php echo date('M d, Y \a\t h:i A', strtotime($auction->start_time)); ?>.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Bid History -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Bid History (<?php echo $bid_count; ?>)</h4>
                </div>
                <div class="card-body p-0">
                    <?php if(!empty($bids)): ?>
                        <div class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                            <?php foreach($bids as $bid_item): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong>$<?php echo number_format($bid_item['amount'], 2); ?></strong>
                                            <?php if(isset($_SESSION['user_id']) && $bid_item['user_id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-primary ms-1">Your Bid</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($bid_item['created_at'])); ?></small>
                                    </div>
                                    <div class="mt-1">
                                        <small>
                                            by <?php echo htmlspecialchars($bid_item['username']); ?>
                                            <?php if($auction->status === 'ended' && $auction->winner_id == $bid_item['user_id']): ?>
                                                <span class="badge bg-success">Winner</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="p-4 text-center">
                            <p class="text-muted mb-0">No bids yet. Be the first to bid!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($auction->status === 'active'): ?>
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
</script>
<?php endif; ?>

<?php
// Include footer
include_once 'includes/footer.php';
?>
