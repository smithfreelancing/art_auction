<?php
/*
Name of file: /config/artist_setup.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Sets up database tables for artist profiles
*/

// Include database connection
require_once 'database.php';

// Create database connection
$database = new Database();
$conn = $database->connect();

try {
    // Add artist-specific columns to users table if they don't exist
    $columns_to_add = [
        'specialties' => 'TEXT',
        'website' => 'VARCHAR(255)',
        'social_media' => 'TEXT',
        'featured' => 'BOOLEAN DEFAULT FALSE'
    ];
    
    foreach ($columns_to_add as $column => $definition) {
        $query = "SHOW COLUMNS FROM users LIKE '$column'";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $query = "ALTER TABLE users ADD COLUMN $column $definition";
            $conn->exec($query);
            echo "Added '$column' column to users table.<br>";
        } else {
            echo "'$column' column already exists in users table.<br>";
        }
    }
    
    // Create artist_reviews table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS artist_reviews (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        artist_id INT(11) UNSIGNED NOT NULL,
        reviewer_id INT(11) UNSIGNED NOT NULL,
        rating INT(1) NOT NULL CHECK (rating BETWEEN 1 AND 5),
        review TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY (artist_id, reviewer_id)
    )";
    $conn->exec($query);
    echo "Created or verified 'artist_reviews' table.<br>";
    
    // Create artist_categories table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS artist_categories (
        id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($query);
    echo "Created or verified 'artist_categories' table.<br>";
    
    // Create artist_category_relationships table if it doesn't exist
    $query = "CREATE TABLE IF NOT EXISTS artist_category_relationships (
        artist_id INT(11) UNSIGNED NOT NULL,
        category_id INT(11) UNSIGNED NOT NULL,
        PRIMARY KEY (artist_id, category_id),
        FOREIGN KEY (artist_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES artist_categories(id) ON DELETE CASCADE
    )";
    $conn->exec($query);
    echo "Created or verified 'artist_category_relationships' table.<br>";
    
    // Insert some default artist categories
    $default_categories = [
        ['name' => 'Digital Art', 'description' => 'Art created or presented using digital technology'],
        ['name' => 'Photography', 'description' => 'The art of capturing light with a camera'],
        ['name' => 'Painting', 'description' => 'The practice of applying paint to a surface'],
        ['name' => 'Illustration', 'description' => 'Commercial art intended for publications'],
        ['name' => 'Sculpture', 'description' => '3D art created by shaping materials'],
        ['name' => 'Mixed Media', 'description' => 'Art that combines different materials or mediums'],
        ['name' => 'Animation', 'description' => 'The technique of photographing successive drawings to create an illusion of movement'],
        ['name' => 'Graphic Design', 'description' => 'Visual content to communicate messages']
    ];
    
    foreach ($default_categories as $category) {
        $query = "SELECT id FROM artist_categories WHERE name = :name";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $category['name']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $query = "INSERT INTO artist_categories (name, description) VALUES (:name, :description)";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $category['name']);
            $stmt->bindParam(':description', $category['description']);
            $stmt->execute();
            echo "Added category: {$category['name']}<br>";
        }
    }
    
    echo "<p>Database setup for artist profiles completed successfully!</p>";
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
