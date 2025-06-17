<?php
/*
Name of file: /includes/functions.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Contains utility functions used throughout the application
*/

// Clean input data to prevent XSS
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate a random string for tokens
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

// Format price with currency symbol
function format_price($price, $currency = '$') {
    return $currency . number_format($price, 2);
}

// Calculate time remaining for auction
function time_remaining($end_date) {
    $end = new DateTime($end_date);
    $now = new DateTime();
    $interval = $now->diff($end);
    
    if ($now > $end) {
        return 'Expired';
    }
    
    if ($interval->days > 0) {
        return $interval->format('%a days, %h hours');
    } else {
        return $interval->format('%h hours, %i minutes');
    }
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check if user is an artist
function is_artist() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'artist';
}

// Check if user is an admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Redirect with message
function redirect($url, $message = '', $message_type = 'info') {
    if (!empty($message)) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $message_type;
    }
    header('Location: ' . $url);
    exit;
}

// Display flash message
function display_message() {
    if (isset($_SESSION['message'])) {
        $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        $alert_class = 'alert-info';
        
        switch ($message_type) {
            case 'success':
                $alert_class = 'alert-success';
                break;
            case 'error':
                $alert_class = 'alert-danger';
                break;
            case 'warning':
                $alert_class = 'alert-warning';
                break;
        }
        
        echo '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">';
        echo $_SESSION['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?>
