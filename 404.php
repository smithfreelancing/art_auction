<?php
/*
Name of file: /404.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Custom 404 error page
*/

// Start session
session_start();

// Set page title
$pageTitle = 'Page Not Found';

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0">
                <div class="card-body p-5">
                    <h1 class="display-1 text-danger">404</h1>
                    <h2 class="mb-4">Page Not Found</h2>
                    <p class="lead mb-4">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
                    <div class="mb-4">
                        <i class="fas fa-search fa-3x text-muted mb-3"></i>
                        <p>Let's get you back on track!</p>
                    </div>
                    <div>
                        <a href="/" class="btn btn-primary me-2">
                            <i class="fas fa-home"></i> Go to Homepage
                        </a>
                        <a href="/artworks.php" class="btn btn-outline-primary">
                            <i class="fas fa-palette"></i> Browse Artworks
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
