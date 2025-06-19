<?php
/*
Name of file: /my_bids.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Allow users to view their bids
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
require_once 'models/Bid.php';
require_once 'models/Auction.php';

// Database connection
$database = new Database();
$db = $database->connect();

// Get user data
$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

// Get filter parameters
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get user's bids
$bid = new Bid($db);
$bid->user_id = $_SESSION['user_id'];
$bids = $bid->get_user_bids($limit, $offset);
$total_bids = $bid->count_user_bids();

// Calculate pagination
$total_pages = ceil($total_bids / $limit);

// Process auction status updates
$auction = new Auction($db);
$auction_messages = $auction->process_auctions();

// Set page title
$pageTitle = 'My Bids';

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
                    <h4 class="mb-0">My Bids</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <?php if(!empty($bids)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Artwork</th>
                                        <th>Your Bid</th>
                                        <th>Current Price</th>
                                        <th>Status</th>
                                        <th>End Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($bids as $bid_item): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo htmlspecialchars($bid_item['image_path']); ?>" 
                                                         alt="<?php echo htmlspecialchars($bid_item['title']); ?>"
                                                         class="img-thumbnail me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                                    <div>
                                                        <a href="artwork.php?id=<?php echo $bid_item['artwork_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($bid_item['title']); ?>
                                                        </a>
                                                        <div class="small text-muted">
                                                            by <?php echo htmlspecialchars($bid_item['seller_username']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($bid_item['amount'], 2); ?></td>
                                            <td>
                                                <?php
                                                // Get current price from auction
                                                $query = "SELECT current_price FROM auctions WHERE id = :auction_id";
                                                $stmt = $db->prepare($query);
                                                $stmt->bindParam(':auction_id', $bid_item['auction_id']);
                                                $stmt->execute();
                                                $current_price = $stmt->fetch(PDO::FETCH_ASSOC)['current_price'];
                                                echo '$' . number_format($current_price, 2);
                                                
                                                // Check if user is winning
                                                $is_winning = $bid_item['amount'] >= $current_price;
                                                if($is_winning && $bid_item['auction_status'] === 'active') {
                                                    echo ' <span class="badge bg-success">Winning</span>';
                                                } elseif($bid_item['auction_status'] === 'active') {
                                                    echo ' <span class="badge bg-warning text-dark">Outbid</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    switch($bid_item['auction_status']) {
                                                        case 'active': echo 'bg-success'; break;
                                                        case 'pending': echo 'bg-warning text-dark'; break;
                                                        case 'ended': echo 'bg-primary'; break;
                                                        case 'cancelled': echo 'bg-danger'; break;
                                                        default: echo 'bg-secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($bid_item['auction_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                echo date('M d, Y', strtotime($bid_item['end_time']));
                                                
                                                // Show time remaining for active auctions
                                                if($bid_item['auction_status'] === 'active') {
                                                    $end_time = strtotime($bid_item['end_time']);
                                                    $now = time();
                                                    $remaining = $end_time - $now;
                                                    
                                                    if($remaining > 0) {
                                                        $days = floor($remaining / 86400);
                                                        $hours = floor(($remaining % 86400) / 3600);
                                                        
                                                        echo '<div class="small text-muted">';
                                                        if($days > 0) {
                                                            echo $days . 'd ';
                                                        }
                                                        echo $hours . 'h remaining</div>';
                                                    }
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Bid actions">
                                                    <a href="auction.php?id=<?php echo $bid_item['auction_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Auction">
                                                        <i class="fas fa-gavel"></i>
                                                    </a>
                                                    <a href="artwork.php?id=<?php echo $bid_item['artwork_id']; ?>" class="btn btn-sm btn-outline-secondary" title="View Artwork">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                                                <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Bids pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page-1; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">Previous</a>
                                    </li>
                                    
                                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $page+1; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-gavel fa-4x text-muted mb-3"></i>
                            <h4>No Bids Found</h4>
                            <p class="text-muted">You haven't placed any bids yet.</p>
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

                
