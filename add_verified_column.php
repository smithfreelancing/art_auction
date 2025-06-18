<?php
/*
Name of file: /add_verified_column.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Adds the verified column to the users table
*/

// Include database connection
require_once 'config/database.php';

// Create database connection
$database = new Database();
$conn = $database->connect();

try {
    // Add verified column to users table if it doesn't exist
    $query = "SHOW COLUMNS FROM users LIKE 'verified'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $query = "ALTER TABLE users ADD COLUMN verified BOOLEAN DEFAULT TRUE";
        $conn->exec($query);
        echo "Added 'verified' column to users table.<br>";
    } else {
        echo "'verified' column already exists in users table.<br>";
    }
    
    echo "<p>Database update completed successfully!</p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
