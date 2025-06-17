<?php
/*
Name of file: /test_setup.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Tests the database connection and basic setup
*/

// Start session
session_start();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set page title
$pageTitle = 'Setup Test';

// Include header
include_once 'includes/header.php';

// Display any messages
display_message();
?>

<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2>Setup Test Results</h2>
        </div>
        <div class="card-body">
            <h4>Testing Database Connection</h4>
            <?php
            try {
                $database = new Database();
                $conn = $database->connect();
                
                if ($conn) {
                    echo '<div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Database connection successful!
                          </div>';
                    
                    // Test if tables exist
                    $tables = ['users', 'artworks', 'bids', 'transactions', 'notifications'];
                    $allTablesExist = true;
                    
                    echo '<h4 class="mt-4">Checking Database Tables</h4>';
                    echo '<ul class="list-group mb-4">';
                    
                    foreach ($tables as $table) {
                        $stmt = $conn->prepare("SHOW TABLES LIKE :table");
                        $stmt->bindParam(':table', $table);
                        $stmt->execute();
                        
                        if ($stmt->rowCount() > 0) {
                            echo '<li class="list-group-item text-success">
                                    <i class="fas fa-check-circle"></i> Table "' . $table . '" exists
                                  </li>';
                        } else {
                            echo '<li class="list-group-item text-danger">
                                    <i class="fas fa-times-circle"></i> Table "' . $table . '" does not exist
                                  </li>';
                            $allTablesExist = false;
                        }
                    }
                    
                    echo '</ul>';
                    
                    if (!$allTablesExist) {
                        echo '<div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> Some tables are missing. Please run the setup_database.php script.
                              </div>';
                    }
                }
            } catch (PDOException $e) {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> Database connection failed: ' . $e->getMessage() . '
                      </div>';
            }
            ?>
            
            <h4 class="mt-4">Checking File Structure</h4>
            <ul class="list-group mb-4">
                <?php
                $directories = [
                    'assets/css',
                    'assets/js',
                    'assets/images',
                    'assets/uploads',
                    'config',
                    'includes',
                    'models',
                    'controllers',
                    'views'
                ];
                
                foreach ($directories as $dir) {
                    if (is_dir($dir)) {
                        echo '<li class="list-group-item text-success">
                                <i class="fas fa-check-circle"></i> Directory "' . $dir . '" exists
                              </li>';
                    } else {
                        echo '<li class="list-group-item text-danger">
                                <i class="fas fa-times-circle"></i> Directory "' . $dir . '" does not exist
                              </li>';
                    }
                }
                
                $files = [
                    'config/database.php',
                    'includes/header.php',
                    'includes/footer.php',
                    'includes/functions.php',
                    'assets/css/style.css',
                    'assets/js/main.js',
                    'index.php'
                ];
                
                foreach ($files as $file) {
                    if (file_exists($file)) {
                        echo '<li class="list-group-item text-success">
                                <i class="fas fa-check-circle"></i> File "' . $file . '" exists
                              </li>';
                    } else {
                        echo '<li class="list-group-item text-danger">
                                <i class="fas fa-times-circle"></i> File "' . $file . '" does not exist
                              </li>';
                    }
                }
                ?>
            </ul>
            
            <h4 class="mt-4">PHP Environment</h4>
            <ul class="list-group mb-4">
                <li class="list-group-item">
                    PHP Version: <?php echo phpversion(); ?>
                </li>
                <li class="list-group-item">
                    PDO Enabled: <?php echo extension_loaded('pdo') ? 'Yes' : 'No'; ?>
                </li>
                <li class="list-group-item">
                    PDO MySQL Enabled: <?php echo extension_loaded('pdo_mysql') ? 'Yes' : 'No'; ?>
                </li>
                <li class="list-group-item">
                    GD Library: <?php echo extension_loaded('gd') ? 'Yes' : 'No'; ?>
                </li>
                <li class="list-group-item">
                    File Uploads Enabled: <?php echo ini_get('file_uploads') ? 'Yes' : 'No'; ?>
                </li>
                <li class="list-group-item">
                    Max Upload Size: <?php echo ini_get('upload_max_filesize'); ?>
                </li>
                <li class="list-group-item">
                    Session Support: <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No'; ?>
                </li>
            </ul>
            
            <div class="text-center mt-4">
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                <a href="config/setup_database.php" class="btn btn-warning">Run Database Setup</a>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
