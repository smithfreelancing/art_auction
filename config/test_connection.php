<?php
/*
Name of file: /config/test_connection.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Tests the database connection
*/

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Testing Database Connection</h2>";

// Database connection parameters - update these with your actual credentials
$host = 'localhost';
$username = 'smithfre_art_user'; // Your actual database username
$password = 'your_actual_password'; // Your actual database password
$database = 'smithfre_art_auction'; // Your actual database name

try {
    echo "<p>Attempting to connect to database...</p>";
    
    // Try connecting to the database
    $conn = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green; font-weight: bold;'>Connection successful! Your database credentials are correct.</p>";
    
    // Get server info
    echo "<h3>Database Server Information:</h3>";
    echo "<p>Server Version: " . $conn->getAttribute(PDO::ATTR_SERVER_VERSION) . "</p>";
    echo "<p>Connection Status: " . $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS) . "</p>";
    
    echo "<p><a href='/config/create_tables.php'>Proceed to Create Tables</a></p>";
    
} catch(PDOException $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "Connection failed: " . $e->getMessage();
    echo "</div>";
    
    echo "<h3>Troubleshooting Tips:</h3>";
    echo "<ol>";
    echo "<li>Verify your database name: <strong>$database</strong></li>";
    echo "<li>Verify your username: <strong>$username</strong></li>";
    echo "<li>Double-check your password (not displayed for security)</li>";
    echo "<li>Make sure the user has been granted access to this database in your hosting control panel</li>";
    echo "</ol>";
    
    echo "<h3>Common Issues with Shared Hosting:</h3>";
    echo "<ol>";
    echo "<li>Some hosts prefix the database name and username automatically. Check if your actual database name in cPanel includes a prefix.</li>";
    echo "<li>Some hosts require you to specify the host as something other than 'localhost' (e.g., a specific server address)</li>";
    echo "<li>Your hosting provider may have restrictions on database connections</li>";
    echo "</ol>";
    
    echo "<p>If you continue to have issues, please contact your hosting provider for the correct database connection details.</p>";
}

$conn = null;
?>
