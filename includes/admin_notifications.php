<?php
/*
Name of file: /includes/admin_notifications.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Functions for sending admin notifications
*/

/**
 * Send an admin notification email
 * 
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool True if email sent, false otherwise
 */
function send_admin_notification($subject, $message) {
    // Admin email address
    $admin_email = 'jsmith@smithfreelancing.com';
    
    // Check if SimpleMailer is available
    if (file_exists(__DIR__ . '/SimpleMailer.php')) {
        require_once __DIR__ . '/SimpleMailer.php';
        
        if (class_exists('SimpleMailer')) {
            try {
                $mailer = new SimpleMailer();
                return $mailer->send($admin_email, $subject, $message);
            } catch (Exception $e) {
                error_log("Failed to send admin notification: " . $e->getMessage());
            }
        }
    }
    
    // Fallback to PHP's mail function
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Art Auction <admin@artmichels.com>\r\n";
    
    return mail($admin_email, $subject, $message, $headers);
}

/**
 * Send registration notification to admin
 * 
 * @param string $username Username
 * @param string $email User email
 * @param string $first_name User first name
 * @param string $last_name User last name
 * @param string $user_type User type
 * @return bool True if email sent, false otherwise
 */
function notify_admin_registration($username, $email, $first_name, $last_name, $user_type) {
    $subject = 'New User Registration - Art Auction';
    
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fc; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; }
            table, th, td { border: 1px solid #ddd; }
            th, td { padding: 10px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>New User Registration</h2>
            </div>
            <div class="content">
                <p>A new user has registered on the Art Auction platform.</p>
                
                <table>
                    <tr>
                        <th>Username</th>
                        <td>' . htmlspecialchars($username) . '</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>' . htmlspecialchars($email) . '</td>
                    </tr>
                    <tr>
                        <th>First Name</th>
                        <td>' . htmlspecialchars($first_name) . '</td>
                    </tr>
                    <tr>
                        <th>Last Name</th>
                        <td>' . htmlspecialchars($last_name) . '</td>
                    </tr>
                    <tr>
                        <th>User Type</th>
                        <td>' . htmlspecialchars(ucfirst($user_type)) . '</td>
                    </tr>
                    <tr>
                        <th>Registration Time</th>
                        <td>' . date('Y-m-d H:i:s') . '</td>
                    </tr>
                </table>
                
                <p>You can view this user\'s profile in the admin panel.</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_admin_notification($subject, $message);
}

/**
 * Send login notification to admin
 * 
 * @param string $username Username
 * @param string $email User email
 * @param string $first_name User first name
 * @param string $last_name User last name
 * @param string $user_type User type
 * @return bool True if email sent, false otherwise
 */
function notify_admin_login($username, $email, $first_name, $last_name, $user_type) {
    $subject = 'User Login - Art Auction';
    
    $message = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fc; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            table { width: 100%; border-collapse: collapse; }
            table, th, td { border: 1px solid #ddd; }
            th, td { padding: 10px; text-align: left; }
            th { background-color: #f2f2f2; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>User Login Notification</h2>
            </div>
            <div class="content">
                <p>A user has logged in to the Art Auction platform.</p>
                
                <table>
                    <tr>
                        <th>Username</th>
                        <td>' . htmlspecialchars($username) . '</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>' . htmlspecialchars($email) . '</td>
                    </tr>
                    <tr>
                        <th>First Name</th>
                        <td>' . htmlspecialchars($first_name) . '</td>
                    </tr>
                    <tr>
                        <th>Last Name</th>
                        <td>' . htmlspecialchars($last_name) . '</td>
                    </tr>
                    <tr>
                        <th>User Type</th>
                        <td>' . htmlspecialchars(ucfirst($user_type)) . '</td>
                    </tr>
                    <tr>
                        <th>Login Time</th>
                        <td>' . date('Y-m-d H:i:s') . '</td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td>' . $_SERVER['REMOTE_ADDR'] . '</td>
                    </tr>
                </table>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    return send_admin_notification($subject, $message);
}
?>
