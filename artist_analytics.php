<?php
/*
Name of file: /artist_analytics.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Display analytics for artists
*/

// Start session
session_start();

// Include authentication middleware
require_once 'includes/auth_middleware.php';
require_login();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artist.php';
require_once 'models/User.php';

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

// Create artist object
$artist = new Artist($db);
$artist->id = $_SESSION['user_id'];
$artist->read_single();

// Get artist statistics
$stats = $artist->get_statistics();

// Get monthly sales data for chart
$monthly_sales = [];
$monthly_revenue = [];

try {
    $query = "SELECT 
                DATE_FORMAT(t.created_at, '%Y-%m') as month,
                COUNT(*) as sales_count,
                SUM(t.amount) as revenue
              FROM transactions t
              WHERE t.seller_id = :artist_id
              AND t.payment_status = 'completed'
              AND t.created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
              GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
              ORDER BY month ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist->id);
    $stmt->execute();
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize arrays with zeros for all months
    $current_month = date('Y-m');
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M Y', strtotime("-$i months"));
        $monthly_sales[$month_name] = 0;
        $monthly_revenue[$month_name] = 0;
    }
    
    // Fill in actual data
    foreach ($results as $row) {
        $month_name = date('M Y', strtotime($row['month'] . '-01'));
        $monthly_sales[$month_name] = (int)$row['sales_count'];
        $monthly_revenue[$month_name] = (float)$row['revenue'];
    }
    
} catch (Exception $e) {
    error_log("Error getting monthly sales data: " . $e->getMessage());
}

// Get top selling artworks
try {
    $query = "SELECT 
                a.id,
                a.title,
                a.image_path,
                COUNT(t.id) as sales_count,
                SUM(t.amount) as total_revenue
              FROM artworks a
              JOIN transactions t ON a.id = t.artwork_id
              WHERE a.user_id = :artist_id
              AND t.payment_status = 'completed'
              GROUP BY a.id
              ORDER BY sales_count DESC, total_revenue DESC
              LIMIT 5";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist->id);
    $stmt->execute();
    
    $top_artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Error getting top artworks: " . $e->getMessage());
    $top_artworks = [];
}

// Set page title
$pageTitle = 'Artist Analytics';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <?php include_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Artist Analytics</h4>
                    <div class="float-end">
                        <a href="download_portfolio.php" class="btn btn-sm btn-outline-light">
                            <i class="fas fa-download"></i> Download Portfolio Data
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-center">
                                    <h1><?php echo $stats['total_artworks']; ?></h1>
                                    <p class="mb-0">Total Artworks</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <h1><?php echo $stats['active_auctions']; ?></h1>
                                    <p class="mb-0">Active Auctions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <h1><?php echo $stats['completed_sales']; ?></h1>
                                    <p class="mb-0">Completed Sales</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-warning text-dark h-100">
                                <div class="card-body text-center">
                                    <h1>$<?php echo number_format($stats['total_revenue'], 2); ?></h1>
                                    <p class="mb-0">Total Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Monthly Sales</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0">Monthly Revenue</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Top Selling Artworks</h5>
                                </div>
                                <div class="card-body">
                                    <?php if(count($top_artworks) > 0): ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Artwork</th>
                                                        <th>Title</th>
                                                        <th class="text-center">Sales</th>
                                                        <th class="text-end">Revenue</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($top_artworks as $artwork): ?>
                                                        <tr>
                                                            <td>
                                                                <img src="<?php echo htmlspecialchars($artwork['image_path']); ?>" 
                                                                     alt="<?php echo htmlspecialchars($artwork['title']); ?>"
                                                                     class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                                            </td>
                                                            <td>
                                                                <a href="artwork.php?id=<?php echo $artwork['id']; ?>">
                                                                    <?php echo htmlspecialchars($artwork['title']); ?>
                                                                </a>
                                                            </td>
                                                            <td class="text-center"><?php echo $artwork['sales_count']; ?></td>
                                                            <td class="text-end">$<?php echo number_format($artwork['total_revenue'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted text-center">No sales data available yet.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sales Chart
const salesCtx = document.getElementById('salesChart').getContext('2d');
const salesChart = new Chart(salesCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_keys($monthly_sales)); ?>,
        datasets: [{
            label: 'Number of Sales',
            data: <?php echo json_encode(array_values($monthly_sales)); ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    precision: 0
                }
            }
        },
        responsive: true,
        maintainAspectRatio: false
    }
});

// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($monthly_revenue)); ?>,
        datasets: [{
            label: 'Revenue ($)',
            data: <?php echo json_encode(array_values($monthly_revenue)); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 2,
            tension: 0.1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value;
                    }
                }
            }
        },
        responsive: true,
        maintainAspectRatio: false
    }
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
