<?php
/*
Name of file: /includes/artist_stats_widget.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Display artist statistics on the dashboard
*/

// This file should be included in the dashboard.php file for artists

// Check if user is an artist
if(isset($user) && $user->user_type === 'artist') {
    // Create artist object
    $artist = new Artist($db);
    $artist->id = $user->id;
    
    // Get artist statistics
    $stats = $artist->get_statistics();
?>

<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Artist Statistics</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h2><?php echo $stats['total_artworks']; ?></h2>
                        <p class="mb-0">Total Artworks</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h2><?php echo $stats['active_auctions']; ?></h2>
                        <p class="mb-0">Active Auctions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h2><?php echo $stats['completed_sales']; ?></h2>
                        <p class="mb-0">Completed Sales</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-dark h-100">
                    <div class="card-body text-center">
                        <h2>$<?php echo number_format($stats['total_revenue'], 2); ?></h2>
                        <p class="mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Artist Rating</h6>
                    <span class="text-muted small"><?php echo $stats['review_count']; ?> reviews</span>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-2">
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
                    </div>
                    <span class="fw-bold"><?php echo number_format($rating, 1); ?>/5.0</span>
                </div>
            </div>
            <div class="col-md-6 text-end">
                <a href="artist_analytics.php" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line"></i> View Detailed Analytics
                </a>
            </div>
        </div>
    </div>
</div>

<?php
}
?>
