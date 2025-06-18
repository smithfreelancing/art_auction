<?php
/*
Name of file: /setup_verification.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Set up database tables for email verification
*/

// Include database connection
require_once 'config/database.php';

// Create database connection
$database = new Database();
$conn = $database->connect();

try {
    // Create email_verifications table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS email_verifications (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($query);
    echo "Created or verified 'email_verifications' table.<br>";
    
    echo "<p>Database setup for email verification completed successfully!</p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


