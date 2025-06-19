<?php
/*
Name of file: /models/Artist.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Artist model for handling artist-specific database operations
*/

class Artist {
    // Database connection and table
    private $conn;
    private $table = 'users';
    
    // Artist properties
    public $id;
    public $username;
    public $email;
    public $first_name;
    public $last_name;
    public $profile_image;
    public $bio;
    public $specialties;
    public $website;
    public $social_media;
    public $featured;
    public $rating;
    public $total_sales;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get artist by ID
    public function read_single() {
        // SQL query
        $query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM artworks WHERE user_id = u.id) as artwork_count,
                 (SELECT COUNT(*) FROM transactions WHERE seller_id = u.id AND payment_status = 'completed') as sales_count,
                 (SELECT COALESCE(AVG(rating), 0) FROM artist_reviews WHERE artist_id = u.id) as avg_rating
                 FROM " . $this->table . " u
                 WHERE u.id = :id AND u.user_type = 'artist'
                 LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->profile_image = $row['profile_image'];
            $this->bio = $row['bio'];
            $this->specialties = $row['specialties'] ?? '';
            $this->website = $row['website'] ?? '';
            $this->social_media = $row['social_media'] ?? '';
            $this->featured = $row['featured'] ?? 0;
            $this->rating = $row['avg_rating'];
            $this->total_sales = $row['sales_count'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Get all artists
    public function read_all($limit = 10, $offset = 0, $featured_only = false) {
        // SQL query
        $query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM artworks WHERE user_id = u.id) as artwork_count,
                 (SELECT COALESCE(AVG(rating), 0) FROM artist_reviews WHERE artist_id = u.id) as avg_rating
                 FROM " . $this->table . " u
                 WHERE u.user_type = 'artist'";
        
        if($featured_only) {
            $query .= " AND u.featured = 1";
        }
        
        $query .= " ORDER BY u.featured DESC, avg_rating DESC, artwork_count DESC
                  LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Search artists
    public function search($keyword, $limit = 10, $offset = 0) {
        // SQL query
        $query = "SELECT u.*, 
                 (SELECT COUNT(*) FROM artworks WHERE user_id = u.id) as artwork_count,
                 (SELECT COALESCE(AVG(rating), 0) FROM artist_reviews WHERE artist_id = u.id) as avg_rating
                 FROM " . $this->table . " u
                 WHERE u.user_type = 'artist' 
                 AND (u.username LIKE :keyword 
                      OR u.first_name LIKE :keyword 
                      OR u.last_name LIKE :keyword 
                      OR u.bio LIKE :keyword
                      OR u.specialties LIKE :keyword)
                 ORDER BY u.featured DESC, avg_rating DESC, artwork_count DESC
                 LIMIT :limit OFFSET :offset";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Clean keyword
        $keyword = htmlspecialchars(strip_tags($keyword));
        $keyword = "%{$keyword}%";
        
        // Bind parameters
        $stmt->bindParam(':keyword', $keyword);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Update artist profile
    public function update_profile() {
        // Clean data
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->specialties = htmlspecialchars(strip_tags($this->specialties));
        $this->website = htmlspecialchars(strip_tags($this->website));
        $this->social_media = htmlspecialchars(strip_tags($this->social_media));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // SQL query
        $query = "UPDATE " . $this->table . "
                  SET bio = :bio, 
                      specialties = :specialties, 
                      website = :website, 
                      social_media = :social_media
                  WHERE id = :id AND user_type = 'artist'";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':specialties', $this->specialties);
        $stmt->bindParam(':website', $this->website);
        $stmt->bindParam(':social_media', $this->social_media);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Get artist statistics
    public function get_statistics() {
        $stats = [
            'total_artworks' => 0,
            'active_auctions' => 0,
            'completed_sales' => 0,
            'total_revenue' => 0,
            'avg_rating' => 0,
            'review_count' => 0
        ];
        
        try {
            // Get total artworks
            $query = "SELECT COUNT(*) as count FROM artworks WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['total_artworks'] = $row['count'];
            
            // Get active auctions
            $query = "SELECT COUNT(*) as count FROM artworks 
                      WHERE user_id = :user_id AND status = 'active'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['active_auctions'] = $row['count'];
            
            // Get completed sales
            $query = "SELECT COUNT(*) as count, COALESCE(SUM(amount), 0) as total 
                      FROM transactions 
                      WHERE seller_id = :user_id AND payment_status = 'completed'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $this->id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['completed_sales'] = $row['count'];
            $stats['total_revenue'] = $row['total'];
            
            // Get average rating
            $query = "SELECT AVG(rating) as avg, COUNT(*) as count 
                      FROM artist_reviews 
                      WHERE artist_id = :artist_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':artist_id', $this->id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats['avg_rating'] = $row['avg'] ? round($row['avg'], 1) : 0;
            $stats['review_count'] = $row['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Error getting artist statistics: " . $e->getMessage());
            return $stats;
        }
    }
    
    // Set artist as featured
    public function set_featured($featured = true) {
        // SQL query
        $query = "UPDATE " . $this->table . "
                  SET featured = :featured
                  WHERE id = :id AND user_type = 'artist'";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $featured_val = $featured ? 1 : 0;
        $stmt->bindParam(':featured', $featured_val, PDO::PARAM_INT);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        return $stmt->execute();
    }
    
    // Get featured artists
    public function get_featured($limit = 6) {
        return $this->read_all($limit, 0, true);
    }
    
    // Count total artists
    public function count_all() {
        // SQL query
        $query = "SELECT COUNT(*) as total FROM " . $this->table . " WHERE user_type = 'artist'";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
}
?>
