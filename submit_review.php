<?php
/*
Name of file: /submit_review.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Process artist review submissions
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
$artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$review = isset($_POST['review']) ? clean_input($_POST['review']) : '';

// Validate data
if($artist_id <= 0 || $rating < 1 || $rating > 5 || empty($review)) {
    $_SESSION['message'] = 'Invalid review data. Please try again.';
    $_SESSION['message_type'] = 'danger';
    header('Location: artist.php?id=' . $artist_id);
    exit();
}

// Check if user is trying to review themselves
if($_SESSION['user_id'] == $artist_id) {
    $_SESSION['message'] = 'You cannot review yourself.';
    $_SESSION['message_type'] = 'danger';
    header('Location: artist.php?id=' . $artist_id);
    exit();
}

try {
    // Database connection
    $database = new Database();
    $db = $database->connect();
    
    // Check if artist exists and is actually an artist
    $query = "SELECT id FROM users WHERE id = :id AND user_type = 'artist'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $artist_id);
    $stmt->execute();
    
    if($stmt->rowCount() == 0) {
        $_SESSION['message'] = 'Artist not found.';
        $_SESSION['message_type'] = 'danger';
        header('Location: artists.php');
        exit();
    }
    
    // Check if user has already reviewed this artist
    $query = "SELECT id FROM artist_reviews WHERE artist_id = :artist_id AND reviewer_id = :reviewer_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->bindParam(':reviewer_id', $_SESSION['user_id']);
    $stmt->execute();
    
    if($stmt->rowCount() > 0) {
        // Update existing review
        $query = "UPDATE artist_reviews 
                  SET rating = :rating, review = :review, created_at = NOW() 
                  WHERE artist_id = :artist_id AND reviewer_id = :reviewer_id";
        $message = 'Your review has been updated.';
    } else {
        // Insert new review
        $query = "INSERT INTO artist_reviews (artist_id, reviewer_id, rating, review) 
                  VALUES (:artist_id, :reviewer_id, :rating, :review)";
        $message = 'Your review has been submitted.';
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':artist_id', $artist_id);
    $stmt->bindParam(':reviewer_id', $_SESSION['user_id']);
    $stmt->bindParam(':rating', $rating);
    $stmt->bindParam(':review', $review);
    
    if($stmt->execute()) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Failed to submit review. Please try again.';
        $_SESSION['message_type'] = 'danger';
    }
    
} catch(Exception $e) {
    $_SESSION['message'] = 'An error occurred: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
    error_log("Review submission error: " . $e->getMessage());
}

// Redirect back to artist page
header('Location: artist.php?id=' . $artist_id);
exit();
?>
