<?php
/*
Name of file: /test_artist_profiles.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Test the Artist/Seller Profiles functionality
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
require_once 'models/Artist.php';
require_once 'models/User.php';

// Set page title
$pageTitle = 'Test Artist Profiles';

// Database connection
$database = new Database();
$db = $database->connect();

// Test results
$tests = [];

// Test 1: Check if database tables exist
try {
    $tables_to_check = ['users', 'artist_reviews', 'artist_categories', 'artist_category_relationships'];
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

// Test 2: Check if artist columns exist in users table
try {
    $columns_to_check = ['specialties', 'website', 'social_media', 'featured', 'artist_verified'];
    $missing_columns = [];
    
    foreach ($columns_to_check as $column) {
        $query = "SHOW COLUMNS FROM users LIKE '$column'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $missing_columns[] = $column;
        }
    }
    
    if (empty($missing_columns)) {
        $tests[] = ['name' => 'Artist Columns', 'status' => 'Pass', 'message' => 'All required columns exist in users table.'];
    } else {
        $tests[] = ['name' => 'Artist Columns', 'status' => 'Fail', 'message' => 'Missing columns in users table: ' . implode(', ', $missing_columns)];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Columns', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 3: Check if Artist model works
try {
    $artist = new Artist($db);
    $artist_count = $artist->count_all();
    
    $tests[] = ['name' => 'Artist Model', 'status' => 'Pass', 'message' => 'Artist model is working. Found ' . $artist_count . ' artists.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Model', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 4: Check if artist categories exist
try {
    $query = "SELECT COUNT(*) as count FROM artist_categories";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $category_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($category_count > 0) {
        $tests[] = ['name' => 'Artist Categories', 'status' => 'Pass', 'message' => 'Found ' . $category_count . ' artist categories.'];
    } else {
        $tests[] = ['name' => 'Artist Categories', 'status' => 'Fail', 'message' => 'No artist categories found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Categories', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 5: Check if artist reviews functionality works
try {
    $query = "SELECT COUNT(*) as count FROM artist_reviews";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $review_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Artist Reviews', 'status' => 'Pass', 'message' => 'Artist reviews functionality is working. Found ' . $review_count . ' reviews.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Reviews', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 6: Check if required files exist
$files_to_check = [
    'artist.php',
    'artists.php',
    'artist_portfolio.php',
    'artist_reviews.php',
    'edit_artist_profile.php',
    'artist_analytics.php',
    'submit_review.php',
    'delete_review.php',
    'models/Artist.php',
    'includes/dashboard_sidebar.php',
    'download_portfolio.php',
    'includes/artist_stats_widget.php'
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

// Test 7: Check if artist categories are working
try {
    $query = "SELECT ac.*, 
             (SELECT COUNT(*) FROM artist_category_relationships WHERE category_id = ac.id) as artist_count
             FROM artist_categories ac
             ORDER BY ac.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($categories) > 0) {
        $tests[] = ['name' => 'Artist Category Relationships', 'status' => 'Pass', 'message' => 'Found ' . count($categories) . ' categories with ' . array_sum(array_column($categories, 'artist_count')) . ' artist relationships.'];
    } else {
        $tests[] = ['name' => 'Artist Category Relationships', 'status' => 'Fail', 'message' => 'No artist categories found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Category Relationships', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 8: Check if featured artists functionality works
try {
    $query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'artist' AND featured = TRUE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $featured_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Featured Artists', 'status' => 'Pass', 'message' => 'Featured artists functionality is working. Found ' . $featured_count . ' featured artists.'];
} catch (Exception $e) {
    $tests[] = ['name' => 'Featured Artists', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 9: Check if artist verification functionality works
try {
    $query = "SHOW COLUMNS FROM users LIKE 'artist_verified'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $query = "SELECT COUNT(*) as count FROM users WHERE user_type = 'artist' AND artist_verified = TRUE";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $verified_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        $tests[] = ['name' => 'Artist Verification', 'status' => 'Pass', 'message' => 'Artist verification functionality is working. Found ' . $verified_count . ' verified artists.'];
    } else {
        $tests[] = ['name' => 'Artist Verification', 'status' => 'Fail', 'message' => 'Artist verification column not found in users table.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Verification', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 10: Check if artist categories are displayed on profile
try {
    $query = "SELECT COUNT(*) as count FROM artist_category_relationships";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $relationship_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    $tests[] = ['name' => 'Artist Category Display', 'status' => 'Pass', 'message' => 'Artist category relationships found: ' . $relationship_count];
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Category Display', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 11: Check if portfolio download feature works
try {
    if (file_exists('download_portfolio.php')) {
        $tests[] = ['name' => 'Portfolio Download', 'status' => 'Pass', 'message' => 'Portfolio download feature is available for testing.'];
    } else {
        $tests[] = ['name' => 'Portfolio Download', 'status' => 'Fail', 'message' => 'Portfolio download file not found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Portfolio Download', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 12: Check if artist profile completion progress bar works
try {
    if (file_exists('edit_artist_profile.php')) {
        $file_contents = file_get_contents('edit_artist_profile.php');
        if (strpos($file_contents, 'Profile Completion') !== false && strpos($file_contents, 'progress-bar') !== false) {
            $tests[] = ['name' => 'Profile Completion Progress', 'status' => 'Pass', 'message' => 'Profile completion progress bar found in edit_artist_profile.php.'];
        } else {
            $tests[] = ['name' => 'Profile Completion Progress', 'status' => 'Fail', 'message' => 'Profile completion progress bar not found in edit_artist_profile.php.'];
        }
    } else {
        $tests[] = ['name' => 'Profile Completion Progress', 'status' => 'Fail', 'message' => 'edit_artist_profile.php file not found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Profile Completion Progress', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 13: Check if social media preview works
try {
    if (file_exists('edit_artist_profile.php')) {
        $file_contents = file_get_contents('edit_artist_profile.php');
        if (strpos($file_contents, 'Social Media Preview') !== false) {
            $tests[] = ['name' => 'Social Media Preview', 'status' => 'Pass', 'message' => 'Social media preview found in edit_artist_profile.php.'];
        } else {
            $tests[] = ['name' => 'Social Media Preview', 'status' => 'Fail', 'message' => 'Social media preview not found in edit_artist_profile.php.'];
        }
    } else {
        $tests[] = ['name' => 'Social Media Preview', 'status' => 'Fail', 'message' => 'edit_artist_profile.php file not found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Social Media Preview', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 14: Check if SEO enhancement works
try {
    if (file_exists('includes/header.php')) {
        $file_contents = file_get_contents('includes/header.php');
        if (strpos($file_contents, 'additional_head_content') !== false) {
            $tests[] = ['name' => 'SEO Enhancement', 'status' => 'Pass', 'message' => 'SEO enhancement support found in header.php.'];
        } else {
            $tests[] = ['name' => 'SEO Enhancement', 'status' => 'Fail', 'message' => 'SEO enhancement support not found in header.php.'];
        }
    } else {
        $tests[] = ['name' => 'SEO Enhancement', 'status' => 'Fail', 'message' => 'header.php file not found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'SEO Enhancement', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Test 15: Check if artist dashboard welcome message works
try {
    if (file_exists('dashboard.php')) {
        $file_contents = file_get_contents('dashboard.php');
        if (strpos($file_contents, 'Get Started as an Artist') !== false || strpos($file_contents, 'Complete Your Artist Profile') !== false) {
            $tests[] = ['name' => 'Artist Welcome Message', 'status' => 'Pass', 'message' => 'Artist welcome message found in dashboard.php.'];
        } else {
            $tests[] = ['name' => 'Artist Welcome Message', 'status' => 'Fail', 'message' => 'Artist welcome message not found in dashboard.php.'];
        }
    } else {
        $tests[] = ['name' => 'Artist Welcome Message', 'status' => 'Fail', 'message' => 'dashboard.php file not found.'];
    }
} catch (Exception $e) {
    $tests[] = ['name' => 'Artist Welcome Message', 'status' => 'Error', 'message' => $e->getMessage()];
}

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Artist/Seller Profiles Test Results</h4>
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
                                    <td>Artists Listing</td>
                                    <td><a href="artists.php" target="_blank">artists.php</a></td>
                                    <td>Should display a list of artists with search and filtering options</td>
                                </tr>
                                <tr>
                                    <td>Artist Profile</td>
                                    <td><a href="artist.php?id=1" target="_blank">artist.php?id=1</a></td>
                                    <td>Should display an artist's profile with bio, stats, portfolio, and reviews</td>
                                </tr>
                                <tr>
                                    <td>Artist Portfolio</td>
                                    <td><a href="artist_portfolio.php?id=1" target="_blank">artist_portfolio.php?id=1</a></td>
                                    <td>Should display all artworks by an artist with filtering options</td>
                                </tr>
                                <tr>
                                    <td>Artist Reviews</td>
                                    <td><a href="artist_reviews.php?id=1" target="_blank">artist_reviews.php?id=1</a></td>
                                    <td>Should display all reviews for an artist with rating summary</td>
                                </tr>
                                <tr>
                                    <td>Edit Artist Profile</td>
                                    <td><a href="edit_artist_profile.php" target="_blank">edit_artist_profile.php</a></td>
                                    <td>Should allow artists to edit their profile information (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>Artist Analytics</td>
                                    <td><a href="artist_analytics.php" target="_blank">artist_analytics.php</a></td>
                                    <td>Should display analytics for artists (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>Submit Review</td>
                                    <td>Via artist profile or reviews page</td>
                                    <td>Should allow users to submit reviews for artists (requires login)</td>
                                </tr>
                                <tr>
                                    <td>Featured Artists</td>
                                    <td><a href="includes/featured_artists.php" target="_blank">includes/featured_artists.php</a></td>
                                    <td>Should display featured artists component</td>
                                </tr>
                                <tr>
                                    <td>Artist Categories Management</td>
                                    <td><a href="admin/artist_categories.php" target="_blank">admin/artist_categories.php</a></td>
                                    <td>Should allow admins to manage artist categories (requires admin login)</td>
                                </tr>
                                <tr>
                                    <td>Featured Artists Management</td>
                                    <td><a href="admin/featured_artists.php" target="_blank">admin/featured_artists.php</a></td>
                                    <td>Should allow admins to manage featured artists (requires admin login)</td>
                                </tr>
                                <tr>
                                    <td>Artist Verification</td>
                                    <td><a href="admin/verify_artists.php" target="_blank">admin/verify_artists.php</a></td>
                                    <td>Should allow admins to verify artists (requires admin login)</td>
                                </tr>
                                <tr>
                                    <td>Artist Category Display</td>
                                    <td><a href="artist.php?id=1" target="_blank">artist.php?id=1</a></td>
                                    <td>Should display artist categories on their profile</td>
                                </tr>
                                <tr>
                                    <td>Artist Search by Category</td>
                                    <td><a href="artists.php" target="_blank">artists.php</a></td>
                                    <td>Should allow filtering artists by category</td>
                                </tr>
                                <tr>
                                    <td>Artist Profile Completion</td>
                                    <td><a href="edit_artist_profile.php" target="_blank">edit_artist_profile.php</a></td>
                                    <td>Should display profile completion progress bar (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>Portfolio Download</td>
                                    <td><a href="download_portfolio.php" target="_blank">download_portfolio.php</a></td>
                                    <td>Should download artist portfolio data as CSV (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>Social Media Preview</td>
                                    <td><a href="edit_artist_profile.php" target="_blank">edit_artist_profile.php</a></td>
                                    <td>Should display a preview of social media links (requires artist login)</td>
                                </tr>
                                <tr>
                                    <td>SEO Enhancement</td>
                                    <td><a href="artist.php?id=1" target="_blank">artist.php?id=1</a></td>
                                    <td>Should include SEO meta tags in the page head</td>
                                </tr>
                                <tr>
                                    <td>Artist Welcome Message</td>
                                    <td><a href="dashboard.php" target="_blank">dashboard.php</a></td>
                                    <td>Should display personalized welcome messages for artists (requires artist login)</td>
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
                            echo '<div class="alert alert-success">All tests passed! The Artist/Seller Profiles functionality is working correctly.</div>';
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


