<?php
/*
Name of file: /download_portfolio.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Allow artists to download their portfolio information
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
    $_SESSION['message'] = 'Only artists can access this feature.';
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

// Get artist's artworks
$query = "SELECT * FROM artworks WHERE user_id = :artist_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist->id);
$stmt->execute();
$artworks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get artist's categories
$query = "SELECT ac.name 
          FROM artist_categories ac
          JOIN artist_category_relationships acr ON ac.id = acr.category_id
          WHERE acr.artist_id = :artist_id
          ORDER BY ac.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist->id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get artist's reviews
$query = "SELECT r.*, u.username 
          FROM artist_reviews r 
          JOIN users u ON r.reviewer_id = u.id 
          WHERE r.artist_id = :artist_id 
          ORDER BY r.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist->id);
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create CSV content
$csv_content = "ARTIST PORTFOLIO INFORMATION\n";
$csv_content .= "Generated on: " . date('Y-m-d H:i:s') . "\n\n";

$csv_content .= "ARTIST INFORMATION\n";
$csv_content .= "Name: " . $artist->first_name . " " . $artist->last_name . "\n";
$csv_content .= "Username: " . $artist->username . "\n";
$csv_content .= "Email: " . $artist->email . "\n";
$csv_content .= "Bio: " . str_replace("\n", " ", $artist->bio) . "\n";
$csv_content .= "Specialties: " . $artist->specialties . "\n";
$csv_content .= "Website: " . $artist->website . "\n";
$csv_content .= "Member since: " . date('Y-m-d', strtotime($artist->created_at)) . "\n\n";

$csv_content .= "STATISTICS\n";
$csv_content .= "Total Artworks: " . $stats['total_artworks'] . "\n";
$csv_content .= "Active Auctions: " . $stats['active_auctions'] . "\n";
$csv_content .= "Completed Sales: " . $stats['completed_sales'] . "\n";
$csv_content .= "Total Revenue: $" . number_format($stats['total_revenue'], 2) . "\n";
$csv_content .= "Average Rating: " . number_format($stats['avg_rating'], 1) . " (" . $stats['review_count'] . " reviews)\n\n";

$csv_content .= "CATEGORIES\n";
foreach($categories as $category) {
    $csv_content .= "- " . $category . "\n";
}
$csv_content .= "\n";

$csv_content .= "ARTWORKS\n";
$csv_content .= "ID,Title,Description,Status,Starting Price,Current Price,Created Date\n";
foreach($artworks as $artwork) {
    $csv_content .= $artwork['id'] . ",";
    $csv_content .= '"' . str_replace('"', '""', $artwork['title']) . '",';
    $csv_content .= '"' . str_replace('"', '""', str_replace("\n", " ", $artwork['description'])) . '",';
    $csv_content .= $artwork['status'] . ",";
    $csv_content .= $artwork['starting_price'] . ",";
    $csv_content .= ($artwork['current_price'] ?? $artwork['starting_price']) . ",";
    $csv_content .= date('Y-m-d', strtotime($artwork['created_at'])) . "\n";
}
$csv_content .= "\n";

$csv_content .= "REVIEWS\n";
$csv_content .= "Reviewer,Rating,Review,Date\n";
foreach($reviews as $review) {
    $csv_content .= '"' . $review['username'] . '",';
    $csv_content .= $review['rating'] . ",";
    $csv_content .= '"' . str_replace('"', '""', str_replace("\n", " ", $review['review'])) . '",';
    $csv_content .= date('Y-m-d', strtotime($review['created_at'])) . "\n";
}

// Set headers for download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="artist_portfolio_' . date('Y-m-d') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Output CSV content
echo $csv_content;
exit();
?>
