<?php
/*
Name of file: /config/create_tables.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Creates tables in the existing database
*/

// Display errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Creating Database Tables</h2>";

// Include database connection
require_once 'database.php';

try {
    // Create database connection
    $database = new Database();
    $conn = $database->connect();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    echo "<p>Database connection successful!</p>";
    
    // Create users table
    echo "<p>Creating users table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        first_name VARCHAR(50),
        last_name VARCHAR(50),
        profile_image VARCHAR(255),
        bio TEXT,
        user_type ENUM('user', 'artist', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $conn->exec($sql);
    echo "<p>Users table created successfully</p>";
    
    // Create artworks table
    echo "<p>Creating artworks table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS artworks (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_path VARCHAR(255) NOT NULL,
        starting_price DECIMAL(10,2) NOT NULL,
        buy_now_price DECIMAL(10,2),
        current_price DECIMAL(10,2),
        status ENUM('pending', 'active', 'sold', 'expired') DEFAULT 'pending',
        auction_start DATETIME,
        auction_end DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "<p>Artworks table created successfully</p>";
    
    // Create bids table
    echo "<p>Creating bids table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS bids (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artwork_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        bid_amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "<p>Bids table created successfully</p>";
    
    // Create transactions table
    echo "<p>Creating transactions table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS transactions (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artwork_id INT(11) UNSIGNED NOT NULL,
        buyer_id INT(11) UNSIGNED NOT NULL,
        seller_id INT(11) UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_type ENUM('bid_win', 'buy_now') NOT NULL,
        payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "<p>Transactions table created successfully</p>";
    
    // Create notifications table
    echo "<p>Creating notifications table...</p>";
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $conn->exec($sql);
    echo "<p>Notifications table created successfully</p>";
    
    // Create admin user
    echo "<p>Creating admin user...</p>";
    $admin_username = 'admin';
    $admin_email = 'admin@example.com';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT); // Change this in production!
    
    // Check if admin already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
    $stmt->bindParam(':username', $admin_username);
    $stmt->bindParam(':email', $admin_email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $sql = "INSERT INTO users (username, email, password, user_type) 
                VALUES (:username, :email, :password, 'admin')";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':username', $admin_username);
        $stmt->bindParam(':email', $admin_email);
        $stmt->bindParam(':password', $admin_password);
        $stmt->execute();
        echo "<p>Admin user created successfully</p>";
    } else {
        echo "<p>Admin user already exists</p>";
    }
    
    echo "<h3>Tables created successfully!</h3>";
    echo "<p><a href='/test_setup.php'>Go to Test Setup Page</a></p>";
    
} catch(Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>";
    echo "Error: " . $e->getMessage();
    echo "</div>";
    
    echo "<h3>Troubleshooting Tips:</h3>";
    echo "<ol>";
    echo "<li>Make sure you've created the database in your hosting control panel</li>";
    echo "<li>Verify that the database user has all necessary permissions for this database</li>";
    echo "<li>Double-check your username and password in the database.php file</li>";
    echo "<li>Check if your hosting provider has any specific database connection requirements</li>";
    echo "</ol>";
}
?>
