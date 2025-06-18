<?php
/*
Name of file: /logout.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: User logout
*/

// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Clear remember me cookie if it exists
if(isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit();
?>
