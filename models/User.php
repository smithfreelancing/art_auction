<?php
/*
Name of file: /models/User.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User model for handling user-related database operations
*/

class User {
    // Database connection and table
    private $conn;
    private $table = 'users';
    
    // User properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $first_name;
    public $last_name;
    public $profile_image;
    public $bio;
    public $user_type;
    public $created_at;
    public $updated_at;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Register user
    public function register() {
        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->user_type = htmlspecialchars(strip_tags($this->user_type));
        
        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // SQL query
        $query = "INSERT INTO " . $this->table . "
                  (username, email, password, first_name, last_name, user_type)
                  VALUES
                  (:username, :email, :password, :first_name, :last_name, :user_type)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':user_type', $this->user_type);
        
        // Execute query
        if($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        return false;
    }
    
    // Login user
    public function login() {
        try {
            // Clean data
            $this->username = htmlspecialchars(strip_tags($this->username));
            $this->password = htmlspecialchars(strip_tags($this->password));
            
            // SQL query - check if username exists
            $query = "SELECT id, username, email, password, first_name, last_name, profile_image, user_type
                      FROM " . $this->table . "
                      WHERE username = :username OR email = :email
                      LIMIT 1";
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(':username', $this->username);
            $stmt->bindParam(':email', $this->username); // Allow login with email too
            
            // Execute query
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Extract user data
                $this->id = $row['id'];
                $this->username = $row['username'];
                $this->email = $row['email'];
                $this->first_name = $row['first_name'];
                $this->last_name = $row['last_name'];
                $this->profile_image = $row['profile_image'];
                $this->user_type = $row['user_type'];
                
                // Verify password
                if(password_verify($this->password, $row['password'])) {
                    return true;
                }
            }
            
            return false;
        } catch (Exception $e) {
            // Log error (in a real application)
            error_log("Error in login method: " . $e->getMessage());
            return false;
        }
    }
    
    // Get user by ID
    public function read_single() {
        // SQL query
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";
        
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
            $this->user_type = $row['user_type'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Update user
    public function update() {
        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // SQL query
        $query = "UPDATE " . $this->table . "
                  SET username = :username, 
                      email = :email, 
                      first_name = :first_name, 
                      last_name = :last_name, 
                      bio = :bio
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':username', $this->username);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':first_name', $this->first_name);
        $stmt->bindParam(':last_name', $this->last_name);
        $stmt->bindParam(':bio', $this->bio);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        // Print error if something goes wrong
        printf("Error: %s.\n", $stmt->error);
        return false;
    }
    
    // Update profile image
    public function update_image() {
        // Clean data
        $this->profile_image = htmlspecialchars(strip_tags($this->profile_image));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // SQL query
        $query = "UPDATE " . $this->table . "
                  SET profile_image = :profile_image
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':profile_image', $this->profile_image);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Update password
    public function update_password() {
        // Clean data
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Hash password
        $this->password = password_hash($this->password, PASSWORD_DEFAULT);
        
        // SQL query
        $query = "UPDATE " . $this->table . "
                  SET password = :password
                  WHERE id = :id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':id', $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Check if username exists
    public function username_exists() {
        // Clean data
        $this->username = htmlspecialchars(strip_tags($this->username));
        
        // SQL query
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':username', $this->username);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Check if email exists
    public function email_exists() {
        // Clean data
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // SQL query
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':email', $this->email);
        
        // Execute query
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Get user by email (for password reset)
    public function get_by_email() {
        // Clean data
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // SQL query
        $query = "SELECT id, username, email FROM " . $this->table . " WHERE email = :email LIMIT 1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind parameter
        $stmt->bindParam(':email', $this->email);
        
        // Execute query
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Set properties
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            return true;
        }
        
        return false;
    }
}

