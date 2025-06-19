<?php
/*
Name of file: /delete_review.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Process artist review deletion
*/

// Start session
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if form was submitted
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: artists.php');
    exit();
}

// Get form data
$review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
$artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;

// Validate data
if($review_id <= 0 || $artist_id <= 0) {
    $_SESSION['message'] = 'Invalid review data.';
    $_SESSION['message_type'] = 'danger';
    header('Location: artist_reviews.php?id=' . $artist_id);
    exit();
}

try {
    // Database connection
    $database = new Database();
    $db = $database->connect();
    
    // Check if the review belongs to the current user
    $query = "SELECT id FROM artist_reviews WHERE id = :review_id AND reviewer_id = :reviewer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':review_id', $review_id);
    $stmt->bindParam(':reviewer_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        $_SESSION['message'] = 'You can only delete your own reviews.';
        $_SESSION['message_type'] = 'danger';
        header('Location: artist_reviews.php?id=' . $artist_id);
        exit();
    }
    
    // Delete the review
    $query = "DELETE FROM artist_reviews WHERE id = :review_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':review_id', $review_id);
    
    if($stmt->execute()) {
        $_SESSION['message'] = 'Your review has been deleted.';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to delete review. Please try again.';
        $_SESSION['message_type'] = 'danger';
    }
    
} catch(Exception $e) {
    $_SESSION['message'] = 'An error occurred: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    error_log("Review deletion error: " . $e->getMessage());
}

// Redirect back to artist reviews page
header('Location: artist_reviews.php?id=' . $artist_id);
exit();
?>
