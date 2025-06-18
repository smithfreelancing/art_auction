<?php
/*
Name of file: /admin/verify_all_users.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Admin script to verify all existing users
*/

// Start session
session_start();

// Check if user is admin
if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Unauthorized access');
}

// Include necessary files
require_once '../config/database.php';

// Database connection
$database = new Database();
$db = $database->connect();

try {
    // Update all users to verified status
    $query = "UPDATE users SET verified = TRUE WHERE verified = FALSE";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $count = $stmt->rowCount();
    
    echo "<h1>User Verification</h1>";
    echo "<p>Successfully verified $count users.</p>";
    echo "<p><a href='../dashboard.php'>Return to Dashboard</a></p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
