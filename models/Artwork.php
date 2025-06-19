<?php
/*
Name of file: /models/Artwork.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Artwork model for handling artwork-related database operations
*/

class Artwork {
    // Database connection and table
    private $conn;
    private $table = 'artworks';
    
    // Artwork properties
    public $id;
    public $user_id;
    public $title;
    public $description;
    public $medium;
    public $dimensions;
    public $year_created;
    public $image_path;
    public $additional_images;
    public $status;
    public $price;
    public $starting_price;
    public $current_price;
    public $reserve_price;
    public $is_auction;
    public $featured;
    public $views;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create new artwork
     * 
     * @return boolean True if created, false otherwise
     */
    public function create() {
        try {
            // Clean data
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->medium = htmlspecialchars(strip_tags($this->medium));
            $this->dimensions = htmlspecialchars(strip_tags($this->dimensions));
            $this->image_path = htmlspecialchars(strip_tags($this->image_path));
            
            // SQL query
            $query = "INSERT INTO " . $this->table . "
                      (user_id, title, description, medium, dimensions, year_created, 
                       image_path, additional_images, status, price, starting_price, 
                       reserve_price, is_auction)
                      VALUES
                      (:user_id, :title, :description, :medium, :dimensions, :year_created, 
                       :image_path, :additional_images, :status, :price, :starting_price, 
                       :reserve_price, :is_auction)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $this->user_id);
            $stmt->bindParam(':title', $this->title);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':medium', $this->medium);
            $stmt->bindParam(':dimensions', $this->dimensions);
            $stmt->bindParam(':year_created', $this->year_created);
            $stmt->bindParam(':image_path', $this->image_path);
            $stmt->bindParam(':additional_images', $this->additional_images);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':price', $this->price);
            $stmt->bindParam(':starting_price', $this->starting_price);
            $stmt->bindParam(':reserve_price', $this->reserve_price);
            $stmt->bindParam(':is_auction', $this->is_auction);
            
            // Execute query
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error creating artwork: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read single artwork
     * 
     * @return boolean True if found, false otherwise
     */
    public function read_single() {
        try {
            // SQL query
            $query = "SELECT a.*, u.username, u.first_name, u.last_name, u.profile_image,
                      (SELECT COUNT(*) FROM favorites WHERE artwork_id = a.id) as favorite_count
                      FROM " . $this->table . " a
                      LEFT JOIN users u ON a.user_id = u.id
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
                $this->user_id = $row['user_id'];
                $this->title = $row['title'];
                $this->description = $row['description'];
                $this->medium = $row['medium'];
                $this->dimensions = $row['dimensions'];
                $this->year_created = $row['year_created'];
                $this->image_path = $row['image_path'];
                $this->additional_images = $row['additional_images'];
                $this->status = $row['status'];
                $this->price = $row['price'];
                $this->starting_price = $row['starting_price'];
                $this->current_price = $row['current_price'];
                $this->reserve_price = $row['reserve_price'];
                $this->is_auction = $row['is_auction'];
                $this->featured = $row['featured'];
                $this->views = $row['views'];
                $this->created_at = $row['created_at'];
                $this->updated_at = $row['updated_at'];
                
                // Artist info
                $this->artist_username = $row['username'];
                $this->artist_first_name = $row['first_name'];
                $this->artist_last_name = $row['last_name'];
                $this->artist_profile_image = $row['profile_image'];
                
                // Stats
                $this->favorite_count = $row['favorite_count'];
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error reading artwork: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update artwork
     * 
     * @return boolean True if updated, false otherwise
     */
    public function update() {
        try {
            // Clean data
            $this->title = htmlspecialchars(strip_tags($this->title));
            $this->description = htmlspecialchars(strip_tags($this->description));
            $this->medium = htmlspecialchars(strip_tags($this->medium));
            $this->dimensions = htmlspecialchars(strip_tags($this->dimensions));
            
            // SQL query
            $query = "UPDATE " . $this->table . "
                      SET title = :title, 
                          description = :description, 
                          medium = :medium, 
                          dimensions = :dimensions, 
                          year_created = :year_created, 
                          price = :price, 
                          status = :status
                      WHERE id = :id AND user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':title', $this->title);
            $stmt->bindParam(':description', $this->description);
            $stmt->bindParam(':medium', $this->medium);
            $stmt->bindParam(':dimensions', $this->dimensions);
            $stmt->bindParam(':year_created', $this->year_created);
            $stmt->bindParam(':price', $this->price);
            $stmt->bindParam(':status', $this->status);
            $stmt->bindParam(':id', $this->id);
            $stmt->bindParam(':user_id', $this->user_id);
            
            // Execute query
            if($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error updating artwork: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete artwork
     * 
     * @return boolean True if deleted, false otherwise
     */
    public function delete() {
        try {
            // SQL query
            $query = "DELETE FROM " . $this->table . " WHERE id = :id AND user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':id', $this->id);
            $stmt->bindParam(':user_id', $this->user_id);
            
            // Execute query
            if($stmt->execute()) {
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error deleting artwork: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all artworks with filtering and pagination
     * 
     * @param string $status Filter by status
     * @param string $category Filter by category
     * @param string $search Search term
     * @param string $sort Sort order
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_all($status = null, $category = null, $search = null, $sort = 'newest', $limit = 10, $offset = 0) {
        try {
            // Base query
            $query = "SELECT a.*, u.username, u.first_name, u.last_name, u.profile_image,
                      (SELECT COUNT(*) FROM favorites WHERE artwork_id = a.id) as favorite_count
                      FROM " . $this->table . " a
                      LEFT JOIN users u ON a.user_id = u.id";
            
            // Add category filter if provided
            if($category) {
                $query .= " LEFT JOIN artwork_category_relationships acr ON a.id = acr.artwork_id
                           LEFT JOIN artwork_categories ac ON acr.category_id = ac.id";
            }
            
            // Start WHERE clause
            $query .= " WHERE 1=1";
            
            // Add status filter if provided
            if($status) {
                $query .= " AND a.status = :status";
            }
            
            // Add category filter if provided
            if($category) {
                $query .= " AND ac.name = :category";
            }
            
            // Add search filter if provided
            if($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search OR a.medium LIKE :search)";
            }
            
            // Add sorting
            switch($sort) {
                case 'price_low':
                    $query .= " ORDER BY a.price ASC";
                    break;
                case 'price_high':
                    $query .= " ORDER BY a.price DESC";
                    break;
                case 'oldest':
                    $query .= " ORDER BY a.created_at ASC";
                    break;
                case 'popular':
                    $query .= " ORDER BY favorite_count DESC, a.views DESC";
                    break;
                case 'newest':
                default:
                    $query .= " ORDER BY a.created_at DESC";
                    break;
            }
            
            // Add limit and offset
            $query .= " LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            
            if($category) {
                $stmt->bindParam(':category', $category);
            }
            
            if($search) {
                $search = '%' . $search . '%';
                $stmt->bindParam(':search', $search);
            }
            
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading artworks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get artworks by user ID
     * 
     * @param int $user_id User ID
     * @param string $status Filter by status
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_by_user($user_id, $status = null, $limit = 10, $offset = 0) {
        try {
            // Base query
            $query = "SELECT a.*, 
                      (SELECT COUNT(*) FROM favorites WHERE artwork_id = a.id) as favorite_count
                      FROM " . $this->table . " a
                      WHERE a.user_id = :user_id";
            
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
            error_log("Error reading user artworks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get featured artworks
     * 
     * @param int $limit Number of records to return
     * @return PDOStatement Result set
     */
    public function read_featured($limit = 6) {
        try {
            // SQL query
            $query = "SELECT a.*, u.username, u.first_name, u.last_name, u.profile_image
                      FROM " . $this->table . " a
                      LEFT JOIN users u ON a.user_id = u.id
                      WHERE a.featured = 1 AND a.status = 'active'
                      ORDER BY a.created_at DESC
                      LIMIT :limit";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading featured artworks: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ending soon auctions
     * 
     * @param int $limit Number of records to return
     * @return PDOStatement Result set
     */
    public function read_ending_soon($limit = 6) {
        try {
            // SQL query
            $query = "SELECT a.*, u.username, u.first_name, u.last_name, u.profile_image, 
                      auc.end_time, auc.current_price
                      FROM " . $this->table . " a
                      LEFT JOIN users u ON a.user_id = u.id
                      LEFT JOIN auctions auc ON a.id = auc.artwork_id
                      WHERE a.is_auction = 1 AND a.status = 'active' AND auc.status = 'active'
                      AND auc.end_time > NOW()
                      ORDER BY auc.end_time ASC
                      LIMIT :limit";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt;
        } catch (Exception $e) {
            error_log("Error reading ending soon auctions: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Increment view count
     * 
     * @return boolean True if updated, false otherwise
     */
    public function increment_views() {
        try {
            // SQL query
            $query = "UPDATE " . $this->table . " SET views = views + 1 WHERE id = :id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':id', $this->id);
            
            // Execute query
            if($stmt->execute()) {
                $this->views += 1;
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error incrementing views: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set artwork categories
     * 
     * @param array $category_ids Array of category IDs
     * @return boolean True if successful, false otherwise
     */
    public function set_categories($category_ids) {
        try {
            // First, delete existing relationships
            $query = "DELETE FROM artwork_category_relationships WHERE artwork_id = :artwork_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':artwork_id', $this->id);
            $stmt->execute();
            
            // Then, insert new relationships
            if(!empty($category_ids)) {
                $query = "INSERT INTO artwork_category_relationships (artwork_id, category_id) VALUES (:artwork_id, :category_id)";
                $stmt = $this->conn->prepare($query);
                
                foreach($category_ids as $category_id) {
                    $stmt->bindParam(':artwork_id', $this->id);
                    $stmt->bindParam(':category_id', $category_id);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error setting artwork categories: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get artwork categories
     * 
     * @return array Array of categories
     */
    public function get_categories() {
        try {
            // SQL query
            $query = "SELECT ac.id, ac.name
                      FROM artwork_categories ac
                      JOIN artwork_category_relationships acr ON ac.id = acr.category_id
                      WHERE acr.artwork_id = :artwork_id
                      ORDER BY ac.name";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->id);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting artwork categories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Set artwork tags
     * 
     * @param array $tags Array of tag names
     * @return boolean True if successful, false otherwise
     */
    public function set_tags($tags) {
        try {
            // First, delete existing relationships
            $query = "DELETE FROM artwork_tag_relationships WHERE artwork_id = :artwork_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':artwork_id', $this->id);
            $stmt->execute();
            
            // Then, insert new tags and relationships
            if(!empty($tags)) {
                foreach($tags as $tag) {
                    $tag = trim(strtolower($tag));
                    if(empty($tag)) continue;
                    
                    // Check if tag exists
                    $query = "SELECT id FROM artwork_tags WHERE name = :name";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':name', $tag);
                    $stmt->execute();
                    
                    if($stmt->rowCount() > 0) {
                        // Tag exists, get ID
                        $tag_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                    } else {
                        // Tag doesn't exist, create it
                        $query = "INSERT INTO artwork_tags (name) VALUES (:name)";
                        $stmt = $this->conn->prepare($query);
                        $stmt->bindParam(':name', $tag);
                        $stmt->execute();
                        $tag_id = $this->conn->lastInsertId();
                    }
                    
                    // Create relationship
                    $query = "INSERT INTO artwork_tag_relationships (artwork_id, tag_id) VALUES (:artwork_id, :tag_id)";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':artwork_id', $this->id);
                    $stmt->bindParam(':tag_id', $tag_id);
                    $stmt->execute();
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Error setting artwork tags: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get artwork tags
     * 
     * @return array Array of tags
     */
    public function get_tags() {
        try {
            // SQL query
            $query = "SELECT at.id, at.name
                      FROM artwork_tags at
                      JOIN artwork_tag_relationships atr ON at.id = atr.tag_id
                      WHERE atr.artwork_id = :artwork_id
                      ORDER BY at.name";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->id);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting artwork tags: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count total artworks with filters
     * 
     * @param string $status Filter by status
     * @param string $category Filter by category
     * @param string $search Search term
     * @return int Total count
     */
    public function count_all($status = null, $category = null, $search = null) {
        try {
            // Base query
            $query = "SELECT COUNT(*) as total FROM " . $this->table . " a";
            
            // Add category filter if provided
                        // Add category filter if provided
            if($category) {
                $query .= " LEFT JOIN artwork_category_relationships acr ON a.id = acr.artwork_id
                           LEFT JOIN artwork_categories ac ON acr.category_id = ac.id";
            }
            
            // Start WHERE clause
            $query .= " WHERE 1=1";
            
            // Add status filter if provided
            if($status) {
                $query .= " AND a.status = :status";
            }
            
            // Add category filter if provided
            if($category) {
                $query .= " AND ac.name = :category";
            }
            
            // Add search filter if provided
            if($search) {
                $query .= " AND (a.title LIKE :search OR a.description LIKE :search OR a.medium LIKE :search)";
            }
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            if($status) {
                $stmt->bindParam(':status', $status);
            }
            
            if($category) {
                $stmt->bindParam(':category', $category);
            }
            
            if($search) {
                $search = '%' . $search . '%';
                $stmt->bindParam(':search', $search);
            }
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting artworks: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Toggle favorite status for a user
     * 
     * @param int $user_id User ID
     * @return boolean True if successful, false otherwise
     */
    public function toggle_favorite($user_id) {
        try {
            // Check if already favorited
            $query = "SELECT id FROM favorites WHERE user_id = :user_id AND artwork_id = :artwork_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':artwork_id', $this->id);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                // Already favorited, remove it
                $query = "DELETE FROM favorites WHERE user_id = :user_id AND artwork_id = :artwork_id";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':artwork_id', $this->id);
                return $stmt->execute();
            } else {
                // Not favorited, add it
                $query = "INSERT INTO favorites (user_id, artwork_id) VALUES (:user_id, :artwork_id)";
                $stmt = $this->conn->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':artwork_id', $this->id);
                return $stmt->execute();
            }
        } catch (Exception $e) {
            error_log("Error toggling favorite: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if artwork is favorited by user
     * 
     * @param int $user_id User ID
     * @return boolean True if favorited, false otherwise
     */
    public function is_favorited($user_id) {
        try {
            // SQL query
            $query = "SELECT id FROM favorites WHERE user_id = :user_id AND artwork_id = :artwork_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':artwork_id', $this->id);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log("Error checking if favorited: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's favorite artworks
     * 
     * @param int $user_id User ID
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return PDOStatement Result set
     */
    public function read_favorites($user_id, $limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT a.*, u.username, u.first_name, u.last_name, u.profile_image,
                      f.created_at as favorited_at
                      FROM favorites f
                      JOIN " . $this->table . " a ON f.artwork_id = a.id
                      LEFT JOIN users u ON a.user_id = u.id
                      WHERE f.user_id = :user_id
                      ORDER BY f.created_at DESC
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
            error_log("Error reading favorites: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Count user's favorite artworks
     * 
     * @param int $user_id User ID
     * @return int Total count
     */
    public function count_favorites($user_id) {
        try {
            // SQL query
            $query = "SELECT COUNT(*) as total FROM favorites WHERE user_id = :user_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $user_id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting favorites: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Add comment to artwork
     * 
     * @param int $user_id User ID
     * @param string $comment Comment text
     * @return boolean True if successful, false otherwise
     */
    public function add_comment($user_id, $comment) {
        try {
            // Clean data
            $comment = htmlspecialchars(strip_tags($comment));
            
            // SQL query
            $query = "INSERT INTO artwork_comments (artwork_id, user_id, comment) 
                      VALUES (:artwork_id, :user_id, :comment)";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':comment', $comment);
            
            // Execute query
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get artwork comments
     * 
     * @param int $limit Number of records to return
     * @param int $offset Offset for pagination
     * @return array Array of comments
     */
    public function get_comments($limit = 10, $offset = 0) {
        try {
            // SQL query
            $query = "SELECT ac.*, u.username, u.first_name, u.last_name, u.profile_image
                      FROM artwork_comments ac
                      JOIN users u ON ac.user_id = u.id
                      WHERE ac.artwork_id = :artwork_id
                      ORDER BY ac.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->id);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            // Execute query
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting comments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count artwork comments
     * 
     * @return int Total count
     */
    public function count_comments() {
        try {
            // SQL query
            $query = "SELECT COUNT(*) as total FROM artwork_comments WHERE artwork_id = :artwork_id";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':artwork_id', $this->id);
            
            // Execute query
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (Exception $e) {
            error_log("Error counting comments: " . $e->getMessage());
            return 0;
        }
    }
}
?>

