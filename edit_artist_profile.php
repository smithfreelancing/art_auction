<?php
/*
Name of file: /edit_artist_profile.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Allow artists to edit their profile information
*/

// Start session
session_start();

// Include authentication middleware
require_once 'includes/auth_middleware.php';
require_login();

// Include necessary files
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'models/Artist.php';
require_once 'models/User.php';

// Check if user is an artist
$database = new Database();
$db = $database->connect();

$user = new User($db);
$user->id = $_SESSION['user_id'];
$user->read_single();

if($user->user_type !== 'artist') {
    $_SESSION['message'] = 'Only artists can access this page.';
    $_SESSION['message_type'] = 'danger';
    header('Location: dashboard.php');
    exit();
}

// Create artist object
$artist = new Artist($db);
$artist->id = $_SESSION['user_id'];
$artist->read_single();

// Process form submission
$errors = [];
$success = false;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $bio = isset($_POST['bio']) ? clean_input($_POST['bio'], true) : '';
    $specialties = isset($_POST['specialties']) ? clean_input($_POST['specialties']) : '';
    $website = isset($_POST['website']) ? clean_input($_POST['website']) : '';
    
    // Social media links
    $social_media = [];
    if(!empty($_POST['facebook'])) $social_media['facebook'] = clean_input($_POST['facebook']);
    if(!empty($_POST['twitter'])) $social_media['twitter'] = clean_input($_POST['twitter']);
    if(!empty($_POST['instagram'])) $social_media['instagram'] = clean_input($_POST['instagram']);
    if(!empty($_POST['linkedin'])) $social_media['linkedin'] = clean_input($_POST['linkedin']);
    if(!empty($_POST['youtube'])) $social_media['youtube'] = clean_input($_POST['youtube']);
    
    // Validate website URL if provided
    if(!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
        $errors[] = 'Please enter a valid website URL.';
    }
    
    // Validate social media URLs if provided
    foreach($social_media as $platform => $url) {
        if(!filter_var($url, FILTER_VALIDATE_URL)) {
            $errors[] = 'Please enter a valid ' . ucfirst($platform) . ' URL.';
        }
    }
    
    // If no errors, update profile
    if(empty($errors)) {
        $artist->bio = $bio;
        $artist->specialties = $specialties;
        $artist->website = $website;
        $artist->social_media = !empty($social_media) ? json_encode($social_media) : '';
        
        if($artist->update_profile()) {
            // Handle category selection
            if(isset($_POST['categories']) && is_array($_POST['categories'])) {
                // Delete existing relationships
                $query = "DELETE FROM artist_category_relationships WHERE artist_id = :artist_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':artist_id', $artist->id);
                $stmt->execute();
                
                // Insert new relationships
                foreach($_POST['categories'] as $category_id) {
                    $category_id = intval($category_id);
                    if($category_id > 0) {
                        $query = "INSERT INTO artist_category_relationships (artist_id, category_id) VALUES (:artist_id, :category_id)";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':artist_id', $artist->id);
                        $stmt->bindParam(':category_id', $category_id);
                        $stmt->execute();
                    }
                }
            }
            
            $success = true;
            $_SESSION['message'] = 'Your profile has been updated successfully.';
            $_SESSION['message_type'] = 'success';
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}

// Get artist categories
$query = "SELECT ac.* 
          FROM artist_categories ac
          LEFT JOIN artist_category_relationships acr ON ac.id = acr.category_id AND acr.artist_id = :artist_id
          ORDER BY ac.name";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist->id);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get artist's selected categories
$query = "SELECT category_id FROM artist_category_relationships WHERE artist_id = :artist_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':artist_id', $artist->id);
$stmt->execute();
$selected_categories = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $selected_categories[] = $row['category_id'];
}

// Set page title
$pageTitle = 'Edit Artist Profile';

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
                    <h4 class="mb-0">Edit Artist Profile</h4>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate profile completion percentage
                    $completion_items = [
                        'bio' => !empty($artist->bio),
                        'specialties' => !empty($artist->specialties),
                        'website' => !empty($artist->website),
                        'social_media' => !empty($artist->social_media),
                        'profile_image' => !empty($user->profile_image),
                        'categories' => !empty($selected_categories)
                    ];

                    $completed_items = array_filter($completion_items);
                    $completion_percentage = round((count($completed_items) / count($completion_items)) * 100);

                    // Display progress bar
                    ?>
                    <div class="mb-4">
                        <h5>Profile Completion: <?php echo $completion_percentage; ?>%</h5>
                        <div class="progress">
                            <div class="progress-bar <?php echo $completion_percentage == 100 ? 'bg-success' : 'bg-primary'; ?>" 
                                 role="progressbar" 
                                 style="width: <?php echo $completion_percentage; ?>%" 
                                 aria-valuenow="<?php echo $completion_percentage; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                                <?php echo $completion_percentage; ?>%
                            </div>
                        </div>
                        <?php if($completion_percentage < 100): ?>
                            <div class="mt-2 small text-muted">
                                Complete your profile to increase visibility and attract more collectors.
                            </div>
                        <?php else: ?>
                            <div class="mt-2 small text-success">
                                <i class="fas fa-check-circle"></i> Your profile is complete! This helps collectors discover your work.
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(isset($_SESSION['message'])): ?>
                        <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show">
                            <?php echo $_SESSION['message']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            Your profile has been updated successfully.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="bio" class="form-label">Artist Bio</label>
                            <textarea class="form-control" id="bio" name="bio" rows="5"><?php echo htmlspecialchars($artist->bio ?? ''); ?></textarea>
                            <div class="form-text">Tell collectors about yourself, your background, inspiration, and artistic style.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="specialties" class="form-label">Specialties</label>
                            <input type="text" class="form-control" id="specialties" name="specialties" value="<?php echo htmlspecialchars($artist->specialties ?? ''); ?>">
                            <div class="form-text">Enter your specialties separated by commas (e.g., Digital Art, Photography, Illustration)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="website" class="form-label">Website</label>
                            <input type="url" class="form-control" id="website" name="website" value="<?php echo htmlspecialchars($artist->website ?? ''); ?>">
                        </div>
                        
                        <h5 class="mt-4 mb-3">Social Media Links</h5>
                        
                        <?php
                        $social_links = [];
                        if(!empty($artist->social_media)) {
                            $social_links = json_decode($artist->social_media, true) ?? [];
                        }
                        ?>
                        
                        <div class="mb-3">
                            <label for="facebook" class="form-label">Facebook</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-facebook-f"></i></span>
                                <input type="url" class="form-control" id="facebook" name="facebook" value="<?php echo htmlspecialchars($social_links['facebook'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="twitter" class="form-label">Twitter</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-twitter"></i></span>
                                <input type="url" class="form-control" id="twitter" name="twitter" value="<?php echo htmlspecialchars($social_links['twitter'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="instagram" class="form-label">Instagram</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-instagram"></i></span>
                                <input type="url" class="form-control" id="instagram" name="instagram" value="<?php echo htmlspecialchars($social_links['instagram'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="linkedin" class="form-label">LinkedIn</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-linkedin-in"></i></span>
                                <input type="url" class="form-control" id="linkedin" name="linkedin" value="<?php echo htmlspecialchars($social_links['linkedin'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="youtube" class="form-label">YouTube</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fab fa-youtube"></i></span>
                                <input type="url" class="form-control" id="youtube" name="youtube" value="<?php echo htmlspecialchars($social_links['youtube'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <?php
                        // Only show if there are social media links
                        if(!empty($social_links)):
                        ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Social Media Preview</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex flex-wrap">
                                    <?php foreach($social_links as $platform => $url): ?>
                                        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" class="btn btn-outline-secondary me-2 mb-2">
                                            <?php
                                            $icon = 'fa-link';
                                            $label = ucfirst($platform);
                                            switch(strtolower($platform)) {
                                                case 'facebook': $icon = 'fa-facebook-f'; break;
                                                case 'twitter': $icon = 'fa-twitter'; break;
                                                case 'instagram': $icon = 'fa-instagram'; break;
                                                case 'linkedin': $icon = 'fa-linkedin-in'; break;
                                                case 'youtube': $icon = 'fa-youtube'; break;
                                            }
                                            ?>
                                            <i class="fab <?php echo $icon; ?> me-1"></i> <?php echo $label; ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 small text-muted">
                                    <i class="fas fa-info-circle"></i> These links will be displayed on your public profile.
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label class="form-label">Categories</label>
                            <div class="row">
                                <?php foreach($categories as $category): ?>
                                    <div class="col-md-4 mb-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="category<?php echo $category['id']; ?>" <?php echo in_array($category['id'], $selected_categories) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="category<?php echo $category['id']; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-text">Select the categories that best describe your art.</div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="artist.php?id=<?php echo $_SESSION['user_id']; ?>" class="btn btn-outline-secondary">View Public Profile</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>


