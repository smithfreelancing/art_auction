<?php
/*
Name of file: /includes/auth_middleware.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Authentication middleware to protect routes
*/

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 * If not, redirect to login page
 */
function require_login() {
    if(!isset($_SESSION['user_id'])) {
        // Store the requested URL for redirection after login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: /login.php');
        exit();
    }
}

/**
 * Check if user is an artist
 * If not, redirect to dashboard
 */
function require_artist() {
    require_login();
    
    if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'artist') {
        // Redirect to dashboard with error message
        $_SESSION['message'] = 'You must be an artist to access this page.';
        $_SESSION['message_type'] = 'error';
        
        header('Location: /dashboard.php');
        exit();
    }
}

/**
 * Check if user is an admin
 * If not, redirect to dashboard
 */
function require_admin() {
    require_login();
    
    if(!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        // Redirect to dashboard with error message
        $_SESSION['message'] = 'You must be an administrator to access this page.';
        $_SESSION['message_type'] = 'error';
        
        header('Location: /dashboard.php');
        exit();
    }
}

/**
 * Check if user is logged out
 * If not, redirect to dashboard
 */
function require_logout() {
    if(isset($_SESSION['user_id'])) {
        header('Location: /dashboard.php');
        exit();
    }
}

/**
 * Check if user owns a resource
 * @param int $resource_user_id The user ID of the resource owner
 * @return bool True if user owns the resource, false otherwise
 */
function is_resource_owner($resource_user_id) {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] == $resource_user_id;
}

/**
 * Redirect if user doesn't own a resource
 * @param int $resource_user_id The user ID of the resource owner
 */
function require_resource_owner($resource_user_id) {
    require_login();
    
    if(!is_resource_owner($resource_user_id) && $_SESSION['user_type'] !== 'admin') {
        // Redirect to dashboard with error message
        $_SESSION['message'] = 'You do not have permission to access this resource.';
        $_SESSION['message_type'] = 'error';
        
        header('Location: /dashboard.php');
        exit();
    }
}
?>
