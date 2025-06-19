<?php
/*
Name of file: /includes/header.php
Programmed by: Jaime C Smith
Date: 2023-11-15
Purpose of this code: Header template for the website with additional head content support
*/

// If page title is not set, use default
if(!isset($pageTitle)) {
    $pageTitle = 'Art Auction';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" href="/assets/images/favicon.ico" type="image/x-icon">
    
    <!-- Include additional head content if defined -->
    <?php if(isset($additional_head_content)) {
        echo $additional_head_content;
    } ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/logo.png" alt="Art Auction Logo" height="30" class="d-inline-block align-text-top me-2">
                Art Auction
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/artworks.php">Artworks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/auctions.php">Auctions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/artists.php">Artists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/contact.php">Contact</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                                <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artist'): ?>
                                    <li><a class="dropdown-item" href="/edit_artist_profile.php"><i class="fas fa-palette"></i> Artist Profile</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="/my-bids.php"><i class="fas fa-gavel"></i> My Bids</a></li>
                                <li><a class="dropdown-item" href="/favorites.php"><i class="fas fa-heart"></i> Favorites</a></li>
                                <li><a class="dropdown-item" href="/messages.php"><i class="fas fa-envelope"></i> Messages</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                                <li><a class="dropdown-item" href="/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main>

