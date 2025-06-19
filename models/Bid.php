<?php
/*
Name of file: /models/Bid.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Bid model for handling bid-related database operations
*/

class Bid {
    // Database connection and table
    private $conn;
    private $table = 'bids';
    
    // Bid properties
    public $id;
    public $auction_id;
    public $user_id;
    public $amount;
    public $created_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new bid
     * 
     * @return boolean True if created, false otherwise
     */
    public function create() {
        try {
            // Start transaction
            $this->conn->beginTransaction();
            
            // Check if auction exists and is active
            $query = "SELECT a.*, art.user_id as seller_id, art.title
                      FROM auctions a
                      JOIN artworks art ON a.artwork_id = art.id
                      WHERE a.id = :auction_id
                      LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':auction_id', $this->auction_id);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                throw new Exception("Auction not found");
            }
            
            $auction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($auction['status'] !== 'active') {
                throw new Exception("Auction is not active");
            }
            
            if(strtotime($auction['start_time']) > time()) {
                throw new Exception("Auction has not started yet");
            }
            
            if(strtotime($auction['end_time']) < time()) {
                throw new Exception("Auction has ended");
            }
            
            // Check if user is not the seller
            if($auction['seller_id'] == $this->user_id) {
                throw new Exception("You cannot bid on your own auction");
            }
            
            // Get highest bid
            $query = "SELECT MAX(amount) as highest_bid FROM " . $this->table . " 
                      WHERE auction_id = :auction_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':auction_id', $this->auction_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $highest_bid = $row['highest_bid'] ?? 0;
            $min_bid = $highest_bid > 0 ? $highest_bid + $auction['min_bid_increment'] : $auction['starting_price'];
            
            // Check if bid amount is high enough
            if($this->amount < $min_bid) {
                throw new Exception("Bid amount must be at least $" . number_format($min_bid, 2));
            }
            
            // Insert bid
            $query = "INSERT INTO " . $this->table . " (auction_id, user_id, amount) 
                      VALUES (:auction_id, :user_id, :amount)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':auction_id', $this->auction_id);
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':amount', $this->amount);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to place bid");
            }
            
            $this->id = $this->conn->lastInsertId();
            
            // Update auction current price
            $query = "UPDATE auctions SET current_price = :amount WHERE id = :auction_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':auction_id', $this->auction_id);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to update auction price");
            }
            
            // Update artwork current price
            $query = "UPDATE artworks SET current_price = :amount WHERE id = :artwork_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':amount', $this->amount);
            $stmt->bindParam(':artwork_id', $auction['artwork_id']);
            
            if(!$stmt->execute()) {
                throw new Exception("Failed to update artwork price");
            }
            
            // Commit transaction
            $this->conn->commit();
            
            // Send outbid notification to previous highest bidder
            if($highest_bid > 0) {
                $query = "SELECT user_id FROM " . $this->table . " 
                          WHERE auction_id = :auction_id AND amount = :amount
                          AND user_id != :user_id
                          LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':auction_id', $this->auction_id);
                $stmt->bindParam(':amount', $highest_bid);
                $stmt->bindParam(':user_id', $this->user_id);
                $stmt->execute();
                
                if($stmt->rowCount() > 0) {
                    $outbid_user = $stmt->fetch(PDO::FETCH_ASSOC)['user_id'];
                    $this->send_outbid_notification($outbid_user, $auction['title'], $this->auction_id);
                }
            }
            
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            if($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            error_log("Error creating bid: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get bids for an auction
     * 
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of bids
     */
    public function get_auction_bids($limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT b.*, u.username, u.first_name, u.last_name, u.profile_image
                      FROM " . $this->table . " b
                      JOIN users u ON b.user_id = u.id
                      WHERE b.auction_id = :auction_id
                      ORDER BY b.amount DESC, b.created_at ASC
                      LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':auction_id', $this->auction_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting auction bids: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count bids for an auction
     * 
     * @return int Total count
     */
    public function count_auction_bids() {
        try {
            // SQL query
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                      WHERE auction_id = :auction_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':auction_id', $this->auction_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting auction bids: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get user's highest bid for an auction
     * 
     * @return float|null Highest bid amount or null if no bids
     */
    public function get_user_highest_bid() {
        try {
            // SQL query
            $query = "SELECT MAX(amount) as highest_bid FROM " . $this->table . " 
                      WHERE auction_id = :auction_id AND user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':auction_id', $this->auction_id);
            $stmt->bindParam(':user_id', $this->user_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['highest_bid'];
        } catch (Exception $e) {
            error_log("Error getting user highest bid: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Check if user is winning an auction
     * 
     * @return boolean True if winning, false otherwise
     */
    public function is_user_winning() {
        try {
            // Get user's highest bid
            $user_highest_bid = $this->get_user_highest_bid();
            
            if(!$user_highest_bid) {
                return false;
            }
            
            // Get highest bid from any user
            $query = "SELECT MAX(amount) as highest_bid FROM " . $this->table . " 
                      WHERE auction_id = :auction_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':auction_id', $this->auction_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $highest_bid = $row['highest_bid'];
            
            // Check if user's bid is the highest
            return $user_highest_bid == $highest_bid;
        } catch (Exception $e) {
            error_log("Error checking if user is winning: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's bids
     * 
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of bids
     */
    public function get_user_bids($limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT b.*, a.end_time, a.status as auction_status, 
                      art.title, art.image_path, art.user_id as seller_id,
                      u.username as seller_username
                      FROM " . $this->table . " b
                      JOIN auctions a ON b.auction_id = a.id
                      JOIN artworks art ON a.artwork_id = art.id
                      JOIN users u ON art.user_id = u.id
                      WHERE b.user_id = :user_id
                      ORDER BY b.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting user bids: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count user's bids
     * 
     * @return int Total count
     */
    public function count_user_bids() {
        try {
            // SQL query
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " 
                      WHERE user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $this->user_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting user bids: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Send outbid notification
     * 
     * @param int $user_id User ID
     * @param string $artwork_title Artwork title
     * @param int $auction_id Auction ID
     * @return boolean True if sent, false otherwise
     */
    private function send_outbid_notification($user_id, $artwork_title, $auction_id) {
        try {
            // Check if notifications table exists
            $query = "SHOW TABLES LIKE 'notifications'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            if($stmt->rowCount() == 0) {
                // Create notifications table
                $query = "CREATE TABLE notifications (
                    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) UNSIGNED NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    message TEXT NOT NULL,
                    link VARCHAR(255),
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                $this->conn->exec($query);
            }
            
            // Insert notification
            $message = "You have been outbid on \"" . $artwork_title . "\". Place a new bid to stay in the auction!";
            $link = "/auction.php?id=" . $auction_id;
            
            $query = "INSERT INTO notifications (user_id, type, message, link) 
                      VALUES (:user_id, 'outbid', :message, :link)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':link', $link);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error sending outbid notification: " . $e->getMessage());
            return false;
        }
    }
}
?>
