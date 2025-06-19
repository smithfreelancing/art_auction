<?php
/*
Name of file: /models/Auction.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Auction model for handling auction-related database operations
*/

class Auction {
    // Database connection and table
    private $conn;
    private $table = 'auctions';
    
    // Auction properties
    public $id;
    public $artwork_id;
    public $start_time;
    public $end_time;
    public $starting_price;
    public $reserve_price;
    public $current_price;
    public $min_bid_increment;
    public $status;
    public $winner_id;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new auction
     * 
     * @return boolean True if created, false otherwise
     */
    public function create() {
        try {
            // SQL query
            $query = "INSERT INTO " . $this->table . "
                      (artwork_id, start_time, end_time, starting_price, reserve_price, min_bid_increment, status)
                      VALUES
                      (:artwork_id, :start_time, :end_time, :starting_price, :reserve_price, :min_bid_increment, :status)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->artwork_id);
            $stmt->bindParam(':start_time', $this->start_time);
            $stmt->bindParam(':end_time', $this->end_time);
            $stmt->bindParam(':starting_price', $this->starting_price);
            $stmt->bindParam(':reserve_price', $this->reserve_price);
            $stmt->bindParam(':min_bid_increment', $this->min_bid_increment);
            $stmt->bindParam(':status', $this->status);
            
            // Execute query
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                
                // Update artwork status and is_auction flag
                $query = "UPDATE artworks SET status = :status, is_auction = 1, 
                          starting_price = :starting_price, current_price = :starting_price
                          WHERE id = :artwork_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':starting_price', $this->starting_price);
                $stmt->bindParam(':artwork_id', $this->artwork_id);
                $stmt->execute();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating auction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read single auction
     * 
     * @return boolean True if found, false otherwise
     */
    public function read_single() {
        try {
            // SQL query
            $query = "SELECT a.*, 
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                      (SELECT username FROM users WHERE id = a.winner_id) as winner_username
                      FROM " . $this->table . " a
                      WHERE a.id = :id
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
                $this->artwork_id = $row['artwork_id'];
                $this->start_time = $row['start_time'];
                $this->end_time = $row['end_time'];
                $this->starting_price = $row['starting_price'];
                $this->reserve_price = $row['reserve_price'];
                $this->current_price = $row['current_price'];
                $this->min_bid_increment = $row['min_bid_increment'];
                $this->status = $row['status'];
                $this->winner_id = $row['winner_id'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                // Stats
                $this->bid_count = $row['bid_count'];
                $this->winner_username = $row['winner_username'];
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error reading auction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read auction by artwork ID
     * 
     * @return boolean True if found, false otherwise
     */
    public function read_by_artwork() {
        try {
            // SQL query
            $query = "SELECT a.*, 
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                      (SELECT username FROM users WHERE id = a.winner_id) as winner_username
                      FROM " . $this->table . " a
                      WHERE a.artwork_id = :artwork_id
                      LIMIT 1";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind artwork ID
            $stmt->bindParam(':artwork_id', $this->artwork_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                // Set properties
                $this->id = $row['id'];
                $this->artwork_id = $row['artwork_id'];
                $this->start_time = $row['start_time'];
                $this->end_time = $row['end_time'];
                $this->starting_price = $row['starting_price'];
                $this->reserve_price = $row['reserve_price'];
                $this->current_price = $row['current_price'];
                $this->min_bid_increment = $row['min_bid_increment'];
                $this->status = $row['status'];
                $this->winner_id = $row['winner_id'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                // Stats
                $this->bid_count = $row['bid_count'];
                $this->winner_username = $row['winner_username'];
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error reading auction by artwork: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update auction
     * 
     * @return boolean True if updated, false otherwise
     */
    public function update() {
        try {
            // SQL query
            $query = "UPDATE " . $this->table . "
                      SET start_time = :start_time, 
                          end_time = :end_time, 
                          reserve_price = :reserve_price, 
                          min_bid_increment = :min_bid_increment, 
                          status = :status
                      WHERE id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':start_time', $this->start_time);
            $stmt->bindParam(':end_time', $this->end_time);
            $stmt->bindParam(':reserve_price', $this->reserve_price);
            $stmt->bindParam(':min_bid_increment', $this->min_bid_increment);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);
            
            // Execute query
            if($stmt->execute()) {
                // Update artwork status
                $query = "UPDATE artworks SET status = :status WHERE id = :artwork_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $this->status);
                $stmt->bindParam(':artwork_id', $this->artwork_id);
                $stmt->execute();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error updating auction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel auction
     * 
     * @return boolean True if cancelled, false otherwise
     */
    public function cancel() {
        try {
            // SQL query
            $query = "UPDATE " . $this->table . " SET status = 'cancelled' WHERE id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':id', $this->id);
            
            // Execute query
            if($stmt->execute()) {
                // Update artwork status
                $query = "UPDATE artworks SET status = 'active', is_auction = 0 WHERE id = :artwork_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':artwork_id', $this->artwork_id);
                $stmt->execute();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error cancelling auction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * End auction
     * 
     * @param int $winner_id Winner user ID
     * @return boolean True if ended, false otherwise
     */
    public function end($winner_id = null) {
        try {
            // SQL query
            $query = "UPDATE " . $this->table . " 
                      SET status = 'ended', winner_id = :winner_id 
                      WHERE id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':winner_id', $winner_id);
            $stmt->bindParam(':id', $this->id);
            
            // Execute query
            if($stmt->execute()) {
                // Update artwork status
                $status = $winner_id ? 'sold' : 'expired';
                $query = "UPDATE artworks SET status = :status WHERE id = :artwork_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':artwork_id', $this->artwork_id);
                $stmt->execute();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error ending auction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active auctions
     * 
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_active($limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT a.*, art.title, art.image_path, art.user_id as artist_id,
                      u.username as artist_username, u.first_name as artist_first_name, 
                      u.last_name as artist_last_name,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                      FROM " . $this->table . " a
                      JOIN artworks art ON a.artwork_id = art.id
                      JOIN users u ON art.user_id = u.id
                      WHERE a.status = 'active' AND a.start_time <= NOW() AND a.end_time > NOW()
                      ORDER BY a.end_time ASC
                      LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading active auctions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's auctions (as seller)
     * 
     * @param int $user_id User ID
     * @param string $status Filter by status
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_by_seller($user_id, $status = null, $limit = 10, $offset = 0) {
        try {
            // Base query
            $query = "SELECT a.*, art.title, art.image_path,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                      FROM " . $this->table . " a
                      JOIN artworks art ON a.artwork_id = art.id
                      WHERE art.user_id = :user_id";
            
            // Add status filter if provided
            if($status) {
                $query .= " AND a.status = :status";
            }
            
            // Add sorting
            $query .= " ORDER BY a.created_at DESC";
            
            // Add limit and offset
            $query .= " LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id);
            
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading seller auctions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's auctions (as bidder)
     * 
     * @param int $user_id User ID
     * @param string $status Filter by status
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_by_bidder($user_id, $status = null, $limit = 10, $offset = 0) {
        try {
            // Base query
            $query = "SELECT DISTINCT a.*, art.title, art.image_path, art.user_id as artist_id,
                      u.username as artist_username, u.first_name as artist_first_name, 
                      u.last_name as artist_last_name,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count,
                      (SELECT MAX(amount) FROM bids WHERE auction_id = a.id AND user_id = :user_id) as user_max_bid,
                      (SELECT amount > (SELECT MAX(amount) FROM bids WHERE auction_id = a.id AND user_id != :user_id) 
                       FROM bids WHERE auction_id = a.id AND user_id = :user_id ORDER BY amount DESC LIMIT 1) as is_winning
                      FROM " . $this->table . " a
                      JOIN artworks art ON a.artwork_id = art.id
                      JOIN users u ON art.user_id = u.id
                      JOIN bids b ON a.id = b.auction_id
                      WHERE b.user_id = :user_id";
            
            // Add status filter if provided
            if($status) {
                $query .= " AND a.status = :status";
            }
            
            // Add sorting
            $query .= " ORDER BY a.end_time ASC";
            
            // Add limit and offset
            $query .= " LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id);
            
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading bidder auctions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's won auctions
     * 
     * @param int $user_id User ID
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_won_auctions($user_id, $limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT a.*, art.title, art.image_path, art.user_id as artist_id,
                      u.username as artist_username, u.first_name as artist_first_name, 
                      u.last_name as artist_last_name,
                      (SELECT COUNT(*) FROM bids WHERE auction_id = a.id) as bid_count
                      FROM " . $this->table . " a
                      JOIN artworks art ON a.artwork_id = art.id
                      JOIN users u ON art.user_id = u.id
                      WHERE a.winner_id = :user_id AND a.status = 'ended'
                      ORDER BY a.end_time DESC
                      LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading won auctions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count total auctions with filters
     * 
     * @param string $status Filter by status
     * @return int Total count
     */
    public function count_all($status = null) {
        try {
            // Base query
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " a";
            
            // Add status filter if provided
            if($status) {
                $query .= " WHERE a.status = :status";
            }
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting auctions: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if auction needs to be started or ended
     * 
     * @return string Status message
     */
    public function check_status() {
        try {
            // Check if auction should be started
            if($this->status === 'pending' && strtotime($this->start_time) <= time()) {
                $this->status = 'active';
                $this->update();
                return 'started';
            }
            
            // Check if auction should be ended
            if($this->status === 'active' && strtotime($this->end_time) <= time()) {
                // Get highest bid
                $query = "SELECT user_id, amount FROM bids 
                          WHERE auction_id = :auction_id 
                          ORDER BY amount DESC LIMIT 1";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':auction_id', $this->id);
                $stmt->execute();
                
                $winner_id = null;
                $highest_bid = 0;
                
                if($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    $winner_id = $row['user_id'];
                    $highest_bid = $row['amount'];
                    
                    // Check if reserve price was met
                    if($this->reserve_price && $highest_bid < $this->reserve_price) {
                        $winner_id = null;
                    }
                }
                
                $this->end($winner_id);
                return 'ended';
            }
            
            return 'unchanged';
        } catch (Exception $e) {
            error_log("Error checking auction status: " . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Process auctions that need to be started or ended
     * 
     * @return array Status messages
     */
    public function process_auctions() {
        try {
            $messages = [];
            
            // Start pending auctions
            $query = "SELECT id FROM " . $this->table . " 
                      WHERE status = 'pending' AND start_time <= NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $this->id = $row['id'];
                $this->read_single();
                $status = $this->check_status();
                $messages[] = "Auction #{$this->id} for artwork #{$this->artwork_id}: $status";
            }
            
            // End active auctions
            $query = "SELECT id FROM " . $this->table . " 
                      WHERE status = 'active' AND end_time <= NOW()";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->id = $row['id'];
                $this->read_single();
                $status = $this->check_status();
                $messages[] = "Auction #{$this->id} for artwork #{$this->artwork_id}: $status";
            }
            
            return $messages;
        } catch (Exception $e) {
            error_log("Error processing auctions: " . $e->getMessage());
            return ["Error: " . $e->getMessage()];
        }
    }
    
    /**
     * Get time remaining for auction
     * 
     * @return array Time remaining in days, hours, minutes, seconds
     */
    public function get_time_remaining() {
        $now = time();
        $end_time = strtotime($this->end_time);
        
        if($now >= $end_time) {
            return [
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'seconds' => 0,
                'total_seconds' => 0,
                'ended' => true
            ];
        }
        
        $remaining = $end_time - $now;
        
        $days = floor($remaining / 86400);
        $hours = floor(($remaining % 86400) / 3600);
        $minutes = floor(($remaining % 3600) / 60);
        $seconds = $remaining % 60;
        
        return [
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'total_seconds' => $remaining,
            'ended' => false
        ];
    }
    
    /**
     * Format time remaining as string
     * 
     * @return string Formatted time remaining
     */
    public function format_time_remaining() {
        $time = $this->get_time_remaining();
        
        if($time['ended']) {
            return 'Auction ended';
        }
        
        $parts = [];
        
        if($time['days'] > 0) {
            $parts[] = $time['days'] . 'd';
        }
        
        if($time['hours'] > 0 || !empty($parts)) {
            $parts[] = $time['hours'] . 'h';
        }
        
        if($time['minutes'] > 0 || !empty($parts)) {
            $parts[] = $time['minutes'] . 'm';
        }
        
        $parts[] = $time['seconds'] . 's';
        
        return implode(' ', $parts);
    }
}
?>

