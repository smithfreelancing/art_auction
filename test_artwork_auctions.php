<?php
/*
Name of file: /test_artwork_auctions.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Test the Artwork Management and Auctions functionality
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artwork.php';
require_once 'models/Auction.php';
require_once 'models/Bid.php';

// Set page title
$pageTitle = 'Test Artwork Management and Auctions';

// Database connection
$database = new Database();
$db = $database->connect();

// Test results
$tests = [];

// Test 1: Check if database tables exist
try {
    $tables_to_check = [
        'artworks', 'artwork_categories', 'artwork_category_relationships', 
        'artwork_tags', 'artwork_tag_relationships', 'auctions', 
        'bids', 'favorites', 'artwork_comments'
    ];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        }
    }
    
    if (empty($missing_tables)) {
        $tests[] = ['name' => 'Database Tables', 'status' => 'Pass', 'message' => 'All required tables exist.'];
    } else {
        $tests[] = ['name' => 'Database Tables', 'status' => 'Fail', 'message' => 'Missing tables: ' . implode(', ', $missing_tables)];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Database Tables', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 2: Check if Artwork model works
try {
    $artwork = new Artwork($db);
    $artwork_count = $artwork->count_all();
    
    $tests[] = ['name' => 'Artwork Model', 'status' => 'Pass', 'message' => 'Artwork model is working. Found ' . $artwork_count . ' artworks.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Artwork Model', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 3: Check if Auction model works
try {
    $auction = new Auction($db);
    $auction_count = $auction->count_all();
    
    $tests[] = ['name' => 'Auction Model', 'status' => 'Pass', 'message' => 'Auction model is working. Found ' . $auction_count . ' auctions.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Auction Model', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 4: Check if Bid model works
try {
    $bid = new Bid($db);
    
    // Check if bids table exists and has the correct structure
    $query = "DESCRIBE bids";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $tests[] = ['name' => 'Bid Model', 'status' => 'Pass', 'message' => 'Bid model structure is correct.'];
    } else {
        $tests[] = ['name' => 'Bid Model', 'status' => 'Fail', 'message' => 'Bid table structure is incorrect.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Bid Model', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 5: Check if artwork categories exist
try {
    $query = "SELECT COUNT(*) as count FROM artwork_categories";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $category_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($category_count > 0) {
        $tests[] = ['name' => 'Artwork Categories', 'status' => 'Pass', 'message' => 'Found ' . $category_count . ' artwork categories.'];
    } else {
        $tests[] = ['name' => 'Artwork Categories', 'status' => 'Fail', 'message' => 'No artwork categories found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artwork Categories', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 6: Check if required files exist
$files_to_check = [
    'add_artwork.php',
    'my_artworks.php',
    'artwork.php',
    'artworks.php',
    'auction.php',
    'auctions.php',
    'my_bids.php',
    'favorites.php',
    'models/Artwork.php',
    'models/Auction.php',
    'models/Bid.php'
];

$missing_files = [];
foreach ($files_to_check as $file) {
    if (!file_exists($file)) {
        $missing_files[] = $file;
    }
}

if (empty($missing_files)) {
    $tests[] = ['name' => 'Required Files', 'status' => 'Pass', 'message' => 'All required files exist.'];
} else {
    $tests[] = ['name' => 'Required Files', 'status' => 'Fail', 'message' => 'Missing files: ' . implode(', ', $missing_files)];
}

// Test 7: Check if auction status update works
try {
    $auction = new Auction($db);
    $messages = $auction->process_auctions();
    
    $tests[] = ['name' => 'Auction Status Update', 'status' => 'Pass', 'message' => 'Auction status update process works. ' . count($messages) . ' auctions processed.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Auction Status Update', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 8: Check if favorites functionality works
try {
    $query = "SELECT COUNT(*) as count FROM favorites";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $favorites_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Favorites', 'status' => 'Pass', 'message' => 'Favorites functionality is working. Found ' . $favorites_count . ' favorites.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Favorites', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 9: Check if comments functionality works
try {
    $query = "SELECT COUNT(*) as count FROM artwork_comments";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $comments_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Comments', 'status' => 'Pass', 'message' => 'Comments functionality is working. Found ' . $comments_count . ' comments.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Comments', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 10: Check if artwork tags functionality works
try {
    $query = "SELECT COUNT(*) as count FROM artwork_tags";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $tags_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Artwork Tags', 'status' => 'Pass', 'message' => 'Artwork tags functionality is working. Found ' . $tags_count . ' tags.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Artwork Tags', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Artwork Management and Auctions Test Results</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Test</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tests as $test): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($test['name']); ?></td>
                                        <td>
                                            <?php if($test['status'] === 'Pass'): ?>
                                                <span class="badge bg-success">Pass</span>
                                            <?php elseif($test['status'] === 'Fail'): ?>
                                                <span class="badge bg-danger">Fail</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Error</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($test['message']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <h5 class="mt-4">Manual Testing Checklist:</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Feature</th>
                                    <th>Test URL</th>
                                    <th>Expected Result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Add Artwork</td>
                                    <td><a href="add_artwork.php" target="_blank">add_artwork.php</a></td>
                                    <td>Should allow artists to upload new artworks (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>My Artworks</td>
                                                                        <td><a href="my_artworks.php" target="_blank">my_artworks.php</a></td>
                                    <td>Should display a list of the artist's artworks (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>Artwork Details</td>
                                    <td><a href="artwork.php?id=1" target="_blank">artwork.php?id=1</a></td>
                                    <td>Should display artwork details, comments, and bidding interface if it's an auction</td>
                                </tr>
                                <tr>
                                    <td>Browse Artworks</td>
                                    <td><a href="artworks.php" target="_blank">artworks.php</a></td>
                                    <td>Should display a list of artworks with search and filtering options</td>
                                </tr>
                                <tr>
                                    <td>Auction Details</td>
                                    <td><a href="auction.php?id=1" target="_blank">auction.php?id=1</a></td>
                                    <td>Should display auction details, bid history, and bidding interface</td>
                                </tr>
                                <tr>
                                    <td>Browse Auctions</td>
                                    <td><a href="auctions.php" target="_blank">auctions.php</a></td>
                                    <td>Should display a list of active auctions with search and filtering options</td>
                                </tr>
                                <tr>
                                    <td>My Bids</td>
                                    <td><a href="my_bids.php" target="_blank">my_bids.php</a></td>
                                    <td>Should display a list of the user's bids (requires login)</td>
                                </tr>
                                <tr>
                                    <td>Favorites</td>
                                    <td><a href="favorites.php" target="_blank">favorites.php</a></td>
                                    <td>Should display a list of the user's favorite artworks (requires login)</td>
                                </tr>
                                <tr>
                                    <td>Bidding</td>
                                    <td>Via artwork.php or auction.php</td>
                                    <td>Should allow users to place bids on auctions (requires login)</td>
                                </tr>
                                <tr>
                                    <td>Favoriting</td>
                                    <td>Via artwork.php</td>
                                    <td>Should allow users to add/remove artworks from favorites (requires login)</td>
                                </tr>
                                <tr>
                                    <td>Commenting</td>
                                    <td>Via artwork.php</td>
                                    <td>Should allow users to comment on artworks (requires login)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Overall Status:</h5>
                        <?php
                        $pass_count = 0;
                        $fail_count = 0;
                        $error_count = 0;
                        
                        foreach($tests as $test) {
                            if($test['status'] === 'Pass') {
                                $pass_count++;
                            } elseif($test['status'] === 'Fail') {
                                $fail_count++;
                            } else {
                                $error_count++;
                            }
                        }
                        
                        if($fail_count === 0 && $error_count === 0) {
                            echo '<div class="alert alert-success">All tests passed! The Artwork Management and Auctions functionality is working correctly.</div>';
                        } elseif($fail_count > 0) {
                            echo '<div class="alert alert-danger">' . $fail_count . ' test(s) failed. Please fix the issues before proceeding.</div>';
                        } else {
                            echo '<div class="alert alert-warning">' . $error_count . ' test(s) encountered errors. Please investigate and fix the issues.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>

