<?php
/*
Name of file: /config/artwork_auction_setup.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Set up database tables for artwork management and auctions
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once 'database.php';

echo "<h1>Setting up Artwork and Auction Database Tables</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->connect();
    
    // Create artworks table
    $query = "CREATE TABLE IF NOT EXISTS artworks (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        medium VARCHAR(100),
        dimensions VARCHAR(100),
        year_created YEAR,
        image_path VARCHAR(255) NOT NULL,
        additional_images TEXT,
        status ENUM('pending', 'active', 'sold', 'expired', 'rejected') DEFAULT 'pending',
        price DECIMAL(10,2),
        starting_price DECIMAL(10,2),
        current_price DECIMAL(10,2),
        reserve_price DECIMAL(10,2),
        is_auction BOOLEAN DEFAULT FALSE,
        featured BOOLEAN DEFAULT FALSE,
        views INT(11) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Artworks table created or already exists.</p>";
    
    // Create artwork_categories table
    $query = "CREATE TABLE IF NOT EXISTS artwork_categories (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($query);
    echo "<p>Artwork categories table created or already exists.</p>";
    
    // Create artwork_category_relationships table
    $query = "CREATE TABLE IF NOT EXISTS artwork_category_relationships (
        artwork_id INT(11) UNSIGNED NOT NULL,
        category_id INT(11) UNSIGNED NOT NULL,
        PRIMARY KEY (artwork_id, category_id),
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES artwork_categories(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Artwork category relationships table created or already exists.</p>";
    
    // Create artwork_tags table
    $query = "CREATE TABLE IF NOT EXISTS artwork_tags (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($query);
    echo "<p>Artwork tags table created or already exists.</p>";
    
    // Create artwork_tag_relationships table
    $query = "CREATE TABLE IF NOT EXISTS artwork_tag_relationships (
        artwork_id INT(11) UNSIGNED NOT NULL,
        tag_id INT(11) UNSIGNED NOT NULL,
        PRIMARY KEY (artwork_id, tag_id),
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (tag_id) REFERENCES artwork_tags(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Artwork tag relationships table created or already exists.</p>";
    
    // Create auctions table
    $query = "CREATE TABLE IF NOT EXISTS auctions (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artwork_id INT(11) UNSIGNED NOT NULL UNIQUE,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        starting_price DECIMAL(10,2) NOT NULL,
        reserve_price DECIMAL(10,2),
        current_price DECIMAL(10,2),
        min_bid_increment DECIMAL(10,2) DEFAULT 5.00,
        status ENUM('pending', 'active', 'ended', 'cancelled') DEFAULT 'pending',
        winner_id INT(11) UNSIGNED,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (winner_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    $db->exec($query);
    echo "<p>Auctions table created or already exists.</p>";
    
    // Create bids table
    $query = "CREATE TABLE IF NOT EXISTS bids (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        auction_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (auction_id) REFERENCES auctions(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Bids table created or already exists.</p>";
    
    // Create favorites table
    $query = "CREATE TABLE IF NOT EXISTS favorites (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) UNSIGNED NOT NULL,
        artwork_id INT(11) UNSIGNED NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id, artwork_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Favorites table created or already exists.</p>";
    
    // Create artwork_comments table
    $query = "CREATE TABLE IF NOT EXISTS artwork_comments (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artwork_id INT(11) UNSIGNED NOT NULL,
        user_id INT(11) UNSIGNED NOT NULL,
        comment TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artwork_id) REFERENCES artworks(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    $db->exec($query);
    echo "<p>Artwork comments table created or already exists.</p>";
    
    // Insert default artwork categories
    $default_categories = [
        ['name' => 'Painting', 'description' => 'Artwork created with paint on canvas, paper, or other surfaces'],
        ['name' => 'Sculpture', 'description' => 'Three-dimensional art made by shaping or combining materials'],
        ['name' => 'Photography', 'description' => 'Art that captures light with a camera to create an image'],
        ['name' => 'Digital Art', 'description' => 'Art created or presented using digital technology'],
        ['name' => 'Drawing', 'description' => 'Art created using lines made with a tool upon a surface'],
        ['name' => 'Mixed Media', 'description' => 'Art that combines different materials or mediums'],
        ['name' => 'Printmaking', 'description' => 'Art of making prints by pressing a surface with ink onto paper or fabric'],
        ['name' => 'Collage', 'description' => 'Art created by assembling different forms to create a new whole'],
        ['name' => 'Textile Art', 'description' => 'Art using textiles such as fabric, yarn, and natural and synthetic fibers'],
        ['name' => 'Ceramics', 'description' => 'Art made from ceramic materials including clay']
    ];
    
    foreach ($default_categories as $category) {
        $query = "INSERT IGNORE INTO artwork_categories (name, description) VALUES (:name, :description)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $category['name']);
        $stmt->bindParam(':description', $category['description']);
        $stmt->execute();
    }
    echo "<p>Default artwork categories inserted.</p>";
    
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>You can now proceed with implementing the artwork and auction functionality.</p>";
    
} catch(PDOException $e) {
    echo "<h2>Error setting up database tables:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
