<?php
/*
Name of file: /config/database.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Establishes a database connection for the application
*/

class Database {
    // Update these with your actual hosting credentials from cPanel
    private $host = 'localhost';
    private $username = 'smithfre_art_user'; // Your actual database username
    private $password = 'AMichels2025$$'; // Your actual database password
    private $database = 'smithfre_art_auction'; // Your actual database name
    private $conn;
    
    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                'mysql:host=' . $this->host . ';dbname=' . $this->database,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        
        return $this->conn;
    }
}
?>
