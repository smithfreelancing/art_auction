<?php
/*
Name of file: /my_artworks.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Allow artists to view and manage their artworks
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
require_once 'models/Artwork.php';
require_once 'models/Auction.php';

// Check if user is an artist
$database = new Database();
$db = $database->connect();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

if($user->user_type !== 'artist') {
    $_SESSION['message'] = 'Only artists can access this page.';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit();
}

// Get filter parameters
$status = isset($_GET['status']) ? clean_input($_GET['status']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get artworks
$artwork = new Artwork($db);
$artworks_stmt = $artwork->read_by_user($_SESSION['user_id'], $status, $limit, $offset);
$total_artworks = $artwork->count_all($status);

// Calculate pagination
$total_pages = ceil($total_artworks / $limit);

// Process auction status updates
$auction = new Auction($db);
$auction_messages = $auction->process_auctions();

// Set page title
$pageTitle = 'My Artworks';

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
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">My Artworks</h4>
                    <a href="add_artwork.php" class="btn btn-light btn-sm">
                        <i class="fas fa-plus"></i> Add New Artwork
                    </a>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <!-- Filter options -->
                    <div class="mb-4">
                        <div class="btn-group" role="group" aria-label="Filter artworks">
                            <a href="my_artworks.php" class="btn btn-outline-primary <?php echo empty($status) ? 'active' : ''; ?>">All</a>
                            <a href="my_artworks.php?status=active" class="btn btn-outline-primary <?php echo $status === 'active' ? 'active' : ''; ?>">Active</a>
                            <a href="my_artworks.php?status=pending" class="btn btn-outline-primary <?php echo $status === 'pending' ? 'active' : ''; ?>">Pending</a>
                            <a href="my_artworks.php?status=sold" class="btn btn-outline-primary <?php echo $status === 'sold' ? 'active' : ''; ?>">Sold</a>
                            <a href="my_artworks.php?status=expired" class="btn btn-outline-primary <?php echo $status === 'expired' ? 'active' : ''; ?>">Expired</a>
                        </div>
                    </div>
                    
                    <?php if($artworks_stmt && $artworks_stmt->rowCount() > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $artworks_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($row['title']); ?>"
                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                            </td>
                                            <td><?php echo htmlspecialchars($row['title']); ?></td>
                                            <td><?php echo $row['is_auction'] ? 'Auction' : 'Fixed Price'; ?></td>
                                            <td>
                                                <?php if($row['is_auction']): ?>
                                                    Starting: $<?php echo number_format($row['starting_price'], 2); ?><br>
                                                    <?php if($row['current_price']): ?>
                                                        Current: $<?php echo number_format($row['current_price'], 2); ?>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    $<?php echo number_format($row['price'], 2); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php 
                                                    switch($row['status']) {
                                                        case 'active': echo 'bg-success'; break;
                                                        case 'pending': echo 'bg-warning text-dark'; break;
                                                        case 'sold': echo 'bg-primary'; break;
                                                        case 'expired': echo 'bg-secondary'; break;
                                                        case 'rejected': echo 'bg-danger'; break;
                                                        default: echo 'bg-info';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group" role="group" aria-label="Artwork actions">
                                                    <a href="artwork.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <?php if($row['status'] !== 'sold'): ?>
                                                        <a href="edit_artwork.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $row['id']; ?>">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Delete Modal -->
                                                <div class="modal fade" id="deleteModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $row['id']; ?>">Confirm Delete</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Are you sure you want to delete "<?php echo htmlspecialchars($row['title']); ?>"?</p>
                                                                <p class="text-danger">This action cannot be undone.</p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <a href="delete_artwork.php?id=<?php echo $row['id']; ?>" class="btn btn-danger">Delete</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                            <nav aria-label="Artwork pagination">
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
                            <i class="fas fa-palette fa-4x text-muted mb-3"></i>
                            <h4>No Artworks Found</h4>
                            <p class="text-muted">
                                <?php if(!empty($status)): ?>
                                    You don't have any <?php echo $status; ?> artworks.
                                <?php else: ?>
                                    You haven't uploaded any artworks yet.
                                <?php endif; ?>
                            </p>
                            <a href="add_artwork.php" class="btn btn-primary mt-3">
                                <i class="fas fa-plus"></i> Add New Artwork
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
