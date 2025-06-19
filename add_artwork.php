<?php
/*
Name of file: /add_artwork.php
Programmed by: Jaime C Smith
Date: 06/18/2025
Purpose of this code: Allow artists to upload new artworks
*/

// Start session
session_start();

// Include authentication middleware
require_once 'includes/auth_middleware.php';
require_login();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/User.php';
require_once 'models/Artwork.php';

// Check if user is an artist
$database = new Database();
$db = $database->connect();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

if($user->user_type !== 'artist') {
    $_SESSION['message'] = 'Only artists can upload artworks.';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit();
}

// Get artwork categories
$query = "SELECT * FROM artwork_categories ORDER BY name";
$stmt = $db->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = isset($_POST['title']) ? clean_input($_POST['title']) : '';
    $description = isset($_POST['description']) ? clean_input($_POST['description'], true) : '';
    $medium = isset($_POST['medium']) ? clean_input($_POST['medium']) : '';
    $dimensions = isset($_POST['dimensions']) ? clean_input($_POST['dimensions']) : '';
    $year_created = isset($_POST['year_created']) ? intval($_POST['year_created']) : null;
    $price = isset($_POST['price']) ? floatval($_POST['price']) : null;
    $is_auction = isset($_POST['is_auction']) && $_POST['is_auction'] === '1';
    $starting_price = isset($_POST['starting_price']) ? floatval($_POST['starting_price']) : null;
    $reserve_price = isset($_POST['reserve_price']) && !empty($_POST['reserve_price']) ? floatval($_POST['reserve_price']) : null;
    $selected_categories = isset($_POST['categories']) ? $_POST['categories'] : [];
    $tags = isset($_POST['tags']) ? clean_input($_POST['tags']) : '';
    
    // Validate form data
    if(empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if(empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if(empty($medium)) {
        $errors[] = 'Medium is required';
    }
    
    if($year_created === null || $year_created < 1800 || $year_created > date('Y')) {
        $errors[] = 'Please enter a valid year';
    }
    
    if(!$is_auction && ($price === null || $price <= 0)) {
        $errors[] = 'Please enter a valid price';
    }
    
    if($is_auction) {
        if($starting_price === null || $starting_price <= 0) {
            $errors[] = 'Please enter a valid starting price';
        }
        
        if($reserve_price !== null && $reserve_price <= $starting_price) {
            $errors[] = 'Reserve price must be greater than starting price';
        }
    }
    
    if(empty($selected_categories)) {
        $errors[] = 'Please select at least one category';
    }
    
    // Handle image upload
    $image_path = '';
    $additional_images = [];
    
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['image'];
        
        // Validate file type
        if(!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Invalid file type. Only JPEG, PNG, and GIF are allowed.';
        }
        
        // Validate file size
        if($file['size'] > $max_size) {
            $errors[] = 'File size exceeds the limit of 5MB.';
        }
        
        if(empty($errors)) {
            // Generate unique filename
            $filename = uniqid() . '_' . $file['name'];
            $upload_dir = 'assets/uploads/artworks/';
            
            // Create directory if it doesn't exist
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $upload_path = $upload_dir . $filename;
            
            // Move uploaded file
            if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                $image_path = '/' . $upload_path;
            } else {
                $errors[] = 'Failed to upload image';
            }
        }
    } else {
        $errors[] = 'Please upload an image';
    }
    
    // Handle additional images
    if(isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $files = $_FILES['additional_images'];
        
        for($i = 0; $i < count($files['name']); $i++) {
            if($files['error'][$i] === UPLOAD_ERR_OK) {
                // Validate file type
                if(!in_array($files['type'][$i], $allowed_types)) {
                    $errors[] = 'Invalid file type for additional image. Only JPEG, PNG, and GIF are allowed.';
                    continue;
                }
                
                // Validate file size
                if($files['size'][$i] > $max_size) {
                    $errors[] = 'Additional image size exceeds the limit of 5MB.';
                    continue;
                }
                
                // Generate unique filename
                $filename = uniqid() . '_' . $files['name'][$i];
                $upload_dir = 'assets/uploads/artworks/';
                
                // Create directory if it doesn't exist
                if(!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $upload_path = $upload_dir . $filename;
                
                // Move uploaded file
                if(move_uploaded_file($files['tmp_name'][$i], $upload_path)) {
                    $additional_images[] = '/' . $upload_path;
                } else {
                    $errors[] = 'Failed to upload additional image';
                }
            }
        }
    }
    
    // If no errors, create artwork
    if(empty($errors)) {
        $artwork = new Artwork($db);
        
        // Set artwork properties
        $artwork->user_id = $_SESSION['user_id'];
        $artwork->title = $title;
        $artwork->description = $description;
        $artwork->medium = $medium;
        $artwork->dimensions = $dimensions;
        $artwork->year_created = $year_created;
        $artwork->image_path = $image_path;
        $artwork->additional_images = !empty($additional_images) ? json_encode($additional_images) : null;
        $artwork->status = 'active'; // Set to 'pending' if you want admin approval
        $artwork->price = $is_auction ? null : $price;
        $artwork->starting_price = $is_auction ? $starting_price : null;
        $artwork->reserve_price = $is_auction ? $reserve_price : null;
        $artwork->is_auction = $is_auction;
        
        // Create artwork
        if($artwork->create()) {
            // Set artwork categories
            $artwork->set_categories($selected_categories);
            
            // Set artwork tags
            if(!empty($tags)) {
                $tag_array = explode(',', $tags);
                $artwork->set_tags($tag_array);
            }
            
            // Create auction if needed
            if($is_auction) {
                require_once 'models/Auction.php';
                
                $auction = new Auction($db);
                $auction->artwork_id = $artwork->id;
                $auction->starting_price = $starting_price;
                $auction->reserve_price = $reserve_price;
                $auction->min_bid_increment = isset($_POST['min_bid_increment']) && !empty($_POST['min_bid_increment']) ? floatval($_POST['min_bid_increment']) : 5.00;
                
                // Set auction times
                $start_time = isset($_POST['start_time']) && !empty($_POST['start_time']) ? $_POST['start_time'] : date('Y-m-d H:i:s');
                $duration = isset($_POST['duration']) ? intval($_POST['duration']) : 7;
                
                $auction->start_time = $start_time;
                $auction->end_time = date('Y-m-d H:i:s', strtotime($start_time . " +$duration days"));
                $auction->status = 'pending';
                
                if(!$auction->create()) {
                    $errors[] = 'Failed to create auction';
                }
            }
            
            $success = true;
            $_SESSION['message'] = 'Artwork uploaded successfully!';
            $_SESSION['message_type'] = 'success';
        } else {
            $errors[] = 'Failed to upload artwork';
        }
    }
}

// Set page title
$pageTitle = 'Add New Artwork';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <?php include_once 'includes/dashboard_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Add New Artwork</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            Artwork uploaded successfully!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="text-center mb-4">
                            <a href="my_artworks.php" class="btn btn-primary">View My Artworks</a>
                            <a href="add_artwork.php" class="btn btn-outline-primary">Add Another Artwork</a>
                        </div>
                    <?php else: ?>
                        <?php if(!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title" required value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="medium" class="form-label">Medium <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="medium" name="medium" required value="<?php echo isset($_POST['medium']) ? htmlspecialchars($_POST['medium']) : ''; ?>">
                                    <div class="form-text">E.g., Oil on canvas, Digital print, etc.</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="dimensions" class="form-label">Dimensions</label>
                                    <input type="text" class="form-control" id="dimensions" name="dimensions" value="<?php echo isset($_POST['dimensions']) ? htmlspecialchars($_POST['dimensions']) : ''; ?>">
                                                                        <div class="form-text">E.g., 24" x 36", 50cm x 70cm, etc.</div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="year_created" class="form-label">Year Created <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="year_created" name="year_created" min="1800" max="<?php echo date('Y'); ?>" required value="<?php echo isset($_POST['year_created']) ? htmlspecialchars($_POST['year_created']) : date('Y'); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">Main Image <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                <div class="form-text">Max file size: 5MB. Supported formats: JPEG, PNG, GIF.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="additional_images" class="form-label">Additional Images</label>
                                <input type="file" class="form-control" id="additional_images" name="additional_images[]" accept="image/*" multiple>
                                <div class="form-text">Max file size: 5MB per image. Supported formats: JPEG, PNG, GIF.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Categories <span class="text-danger">*</span></label>
                                <div class="row">
                                    <?php foreach($categories as $category): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category<?php echo $category['id']; ?>" <?php echo isset($_POST['categories']) && in_array($category['id'], $_POST['categories']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="category<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="tags" class="form-label">Tags</label>
                                <input type="text" class="form-control" id="tags" name="tags" value="<?php echo isset($_POST['tags']) ? htmlspecialchars($_POST['tags']) : ''; ?>">
                                <div class="form-text">Separate tags with commas (e.g., abstract, landscape, modern)</div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Selling Method <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_auction" id="is_auction_0" value="0" <?php echo !isset($_POST['is_auction']) || $_POST['is_auction'] === '0' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_auction_0">
                                        Fixed Price
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_auction" id="is_auction_1" value="1" <?php echo isset($_POST['is_auction']) && $_POST['is_auction'] === '1' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_auction_1">
                                        Auction
                                    </label>
                                </div>
                            </div>
                            
                            <div id="fixed_price_section" class="mb-3 <?php echo isset($_POST['is_auction']) && $_POST['is_auction'] === '1' ? 'd-none' : ''; ?>">
                                <label for="price" class="form-label">Price ($) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="price" name="price" min="0.01" step="0.01" value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                            </div>
                            
                            <div id="auction_section" class="<?php echo !isset($_POST['is_auction']) || $_POST['is_auction'] === '0' ? 'd-none' : ''; ?>">
                                <div class="mb-3">
                                    <label for="starting_price" class="form-label">Starting Price ($) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="starting_price" name="starting_price" min="0.01" step="0.01" value="<?php echo isset($_POST['starting_price']) ? htmlspecialchars($_POST['starting_price']) : ''; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="reserve_price" class="form-label">Reserve Price ($)</label>
                                    <input type="number" class="form-control" id="reserve_price" name="reserve_price" min="0.01" step="0.01" value="<?php echo isset($_POST['reserve_price']) ? htmlspecialchars($_POST['reserve_price']) : ''; ?>">
                                    <div class="form-text">Minimum price at which you are willing to sell. Leave blank for no reserve.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="min_bid_increment" class="form-label">Minimum Bid Increment ($)</label>
                                    <input type="number" class="form-control" id="min_bid_increment" name="min_bid_increment" min="1" step="0.01" value="<?php echo isset($_POST['min_bid_increment']) ? htmlspecialchars($_POST['min_bid_increment']) : '5.00'; ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : date('Y-m-d\TH:i'); ?>">
                                    <div class="form-text">Leave blank to start immediately.</div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="duration" class="form-label">Duration (days)</label>
                                    <select class="form-select" id="duration" name="duration">
                                        <option value="1" <?php echo isset($_POST['duration']) && $_POST['duration'] == '1' ? 'selected' : ''; ?>>1 day</option>
                                        <option value="3" <?php echo isset($_POST['duration']) && $_POST['duration'] == '3' ? 'selected' : ''; ?>>3 days</option>
                                        <option value="5" <?php echo isset($_POST['duration']) && $_POST['duration'] == '5' ? 'selected' : ''; ?>>5 days</option>
                                        <option value="7" <?php echo isset($_POST['duration']) && $_POST['duration'] == '7' ? 'selected' : ''; ?> selected>7 days</option>
                                        <option value="10" <?php echo isset($_POST['duration']) && $_POST['duration'] == '10' ? 'selected' : ''; ?>>10 days</option>
                                        <option value="14" <?php echo isset($_POST['duration']) && $_POST['duration'] == '14' ? 'selected' : ''; ?>>14 days</option>
                                        <option value="30" <?php echo isset($_POST['duration']) && $_POST['duration'] == '30' ? 'selected' : ''; ?>>30 days</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Upload Artwork</button>
                                <a href="my_artworks.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle between fixed price and auction sections
document.addEventListener('DOMContentLoaded', function() {
    const fixedPriceRadio = document.getElementById('is_auction_0');
    const auctionRadio = document.getElementById('is_auction_1');
    const fixedPriceSection = document.getElementById('fixed_price_section');
    const auctionSection = document.getElementById('auction_section');
    
    fixedPriceRadio.addEventListener('change', function() {
        if(this.checked) {
            fixedPriceSection.classList.remove('d-none');
            auctionSection.classList.add('d-none');
        }
    });
    
    auctionRadio.addEventListener('change', function() {
        if(this.checked) {
            fixedPriceSection.classList.add('d-none');
            auctionSection.classList.remove('d-none');
        }
    });
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>

