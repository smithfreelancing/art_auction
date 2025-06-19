<?php
/*
Name of file: /add_artist_verified_column.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Add artist_verified column to users table
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'config/database.php';

echo "<h1>Adding Artist Verification Column</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->connect();
    
    // Check if column already exists
    $query = "SHOW COLUMNS FROM users LIKE 'artist_verified'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, so add it
        $query = "ALTER TABLE users ADD COLUMN artist_verified BOOLEAN DEFAULT FALSE";
        $db->exec($query);
        echo "<p style='color: green;'>Successfully added 'artist_verified' column to users table.</p>";
    } else {
        echo "<p style='color: blue;'>The 'artist_verified' column already exists in users table.</p>";
    }
    
    // Verify the column was added
    $query = "SHOW COLUMNS FROM users LIKE 'artist_verified'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>Verification successful: 'artist_verified' column exists in users table.</p>";
        
        // Optionally set some artists as verified for testing
        $query = "UPDATE users SET artist_verified = TRUE WHERE user_type = 'artist' LIMIT 3";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "<p style='color: green;'>Set $affected artists as verified for testing purposes.</p>";
        }
    } else {
        echo "<p style='color: red;'>Verification failed: 'artist_verified' column does not exist in users table.</p>";
    }
    
    echo "<p>You can now run the test again to verify that the artist verification functionality works.</p>";
    echo "<p><a href='test_artist_profiles.php'>Go to Test Artist Profiles</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
