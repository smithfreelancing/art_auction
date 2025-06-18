<?php
/*
Name of file: /test_email.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Test email functionality
*/

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set a test email address - CHANGE THIS TO YOUR EMAIL
$test_email = 'jsmith@smithfreelancing.com'; // ← CHANGE THIS

echo "<h1>Email System Test</h1>";

// Step 1: Check if PHPMailer is installed
echo "<h2>Step 1: Checking PHPMailer Installation</h2>";

$phpmailer_files = [
    'vendor/phpmailer/phpmailer/src/Exception.php',
    'vendor/phpmailer/phpmailer/src/PHPMailer.php',
    'vendor/phpmailer/phpmailer/src/SMTP.php'
];

$phpmailer_missing = false;
foreach ($phpmailer_files as $file) {
    if (!file_exists($file)) {
        echo "<p style='color: red;'>❌ Missing file: $file</p>";
        $phpmailer_missing = true;
    } else {
        echo "<p style='color: green;'>✓ Found file: $file</p>";
    }
}

if ($phpmailer_missing) {
    echo "<p style='color: red; font-weight: bold;'>PHPMailer is not properly installed. Please install it first.</p>";
    echo "<p>You can install PHPMailer by running this command in your project root:</p>";
    echo "<pre>composer require phpmailer/phpmailer</pre>";
    echo "<p>Or download it manually from <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a>.</p>";
    exit;
}

// Step 2: Check email configuration
echo "<h2>Step 2: Checking Email Configuration</h2>";

if (!file_exists('config/email_config.php')) {
    echo "<p style='color: red;'>❌ Missing email configuration file: config/email_config.php</p>";
    echo "<p>Creating a basic email configuration file for testing...</p>";
    
    // Create a basic email config file
    $email_config = '<?php
// Email Server Settings
define("EMAIL_HOST", "mail.artmichels.com");
define("EMAIL_USERNAME", "admin@artmichels.com");
define("EMAIL_PASSWORD", "AMichels2025$$");
define("EMAIL_PORT", 465);
define("EMAIL_ENCRYPTION", "ssl");
define("EMAIL_FROM_ADDRESS", "admin@artmichels.com");
define("EMAIL_FROM_NAME", "Art Auction");
?>';
    
    file_put_contents('config/email_config.php', $email_config);
    echo "<p style='color: green;'>✓ Created basic email configuration file</p>";
} else {
    echo "<p style='color: green;'>✓ Email configuration file exists</p>";
}

// Include email configuration
require_once 'config/email_config.php';

// Display email configuration
echo "<h3>Email Configuration:</h3>";
echo "<ul>";
echo "<li>Host: " . EMAIL_HOST . "</li>";
echo "<li>Username: " . EMAIL_USERNAME . "</li>";
echo "<li>Password: " . str_repeat('*', strlen(EMAIL_PASSWORD)) . "</li>";
echo "<li>Port: " . EMAIL_PORT . "</li>";
echo "<li>Encryption: " . EMAIL_ENCRYPTION . "</li>";
echo "<li>From Address: " . EMAIL_FROM_ADDRESS . "</li>";
echo "<li>From Name: " . EMAIL_FROM_NAME . "</li>";
echo "</ul>";

// Step 3: Test sending an email
echo "<h2>Step 3: Testing Email Sending</h2>";

// Include PHPMailer
require_once 'vendor/phpmailer/phpmailer/src/Exception.php';
require_once 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once 'vendor/phpmailer/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Create a new PHPMailer instance
$mail = new PHPMailer(true);

try {
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->isSMTP();                       // Send using SMTP
    $mail->Host       = EMAIL_HOST;        // Set the SMTP server to send through
    $mail->SMTPAuth   = true;              // Enable SMTP authentication
    $mail->Username   = EMAIL_USERNAME;    // SMTP username
    $mail->Password   = EMAIL_PASSWORD;    // SMTP password
    $mail->SMTPSecure = EMAIL_ENCRYPTION === 'ssl' ? 
                        PHPMailer::ENCRYPTION_SMTPS : 
                        PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
    $mail->Port       = EMAIL_PORT;        // TCP port to connect to
    
    // Set sender
    $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
    
    // Add recipient
    $mail->addAddress($test_email);
    
    // Set email format to HTML
    $mail->isHTML(true);
    
    // Set subject
    $mail->Subject = 'Test Email from Art Auction Platform';
    
    // Set body
    $mail->Body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4e73df; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background-color: #f8f9fc; }
            .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Email Test Successful!</h2>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>This is a test email from your Art Auction Platform.</p>
                <p>If you received this email, your email system is working correctly!</p>
                <p>Time sent: ' . date('Y-m-d H:i:s') . '</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>';
    
    // Plain text version
    $mail->AltBody = 'This is a test email from your Art Auction Platform. If you received this email, your email system is working correctly!';
    
    // Send the email
    $mail->send();
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='margin-top: 0;'>Email sent successfully!</h3>";
    echo "<p>A test email has been sent to: $test_email</p>";
    echo "<p>Please check your inbox (and spam folder) to verify that the email was received.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h3 style='margin-top: 0;'>Email could not be sent</h3>";
    echo "<p>Error: " . $mail->ErrorInfo . "</p>";
    echo "<h4>Troubleshooting Tips:</h4>";
    echo "<ol>";
    echo "<li>Check if your hosting provider allows sending emails via SMTP</li>";
    echo "<li>Verify your SMTP credentials (username, password, host, port)</li>";
    echo "<li>Make sure the email account exists and has proper permissions</li>";
    echo "<li>Try a different port (587 for TLS, 465 for SSL)</li>";
    echo "<li>Check if your hosting provider blocks outgoing SMTP connections</li>";
    echo "</ol>";
    echo "</div>";
}

// Step 4: Provide implementation for email verification
echo "<h2>Step 4: Email Verification Implementation</h2>";

echo "<h3>Here's how to implement email verification in your registration process:</h3>";

echo "<ol>";
echo "<li>When a user registers, create a verification token and store it in the database</li>";
echo "<li>Send an email with a verification link containing the token</li>";
echo "<li>When the user clicks the link, verify the token and mark the user as verified</li>";
echo "</ol>";

echo "<h3>Sample Code for Registration:</h3>";
echo "<pre>";
echo htmlspecialchars('
// In register.php after successful user registration:
if($user->register()) {
    // Create verification token
    $token = $user->create_verification_token();
    
    if($token) {
        // Prepare verification email
        $verification_link = "https://" . $_SERVER["HTTP_HOST"] . "/verify_email.php?token=" . $token;
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = EMAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = EMAIL_USERNAME;
            $mail->Password   = EMAIL_PASSWORD;
            $mail->SMTPSecure = EMAIL_ENCRYPTION === "ssl" ? 
                                PHPMailer::ENCRYPTION_SMTPS : 
                                PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = EMAIL_PORT;
            
            // Recipients
            $mail->setFrom(EMAIL_FROM_ADDRESS, EMAIL_FROM_NAME);
            $mail->addAddress($user->email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = "Verify Your Email - Art Auction";
            $mail->Body    = get_verification_email_template($user->username, $verification_link);
            $mail->AltBody = "Please verify your email by clicking this link: " . $verification_link;
            
            $mail->send();
            
            // Set session message
            $_SESSION["message"] = "Registration successful! Please check your email to verify your account.";
            $_SESSION["message_type"] = "success";
            
            // Redirect to login page
            header("Location: login.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Failed to send verification email. Please try again or contact support.";
        }
    }
}');
echo "</pre>";

echo "<p>Make sure you have the following files in place:</p>";
echo "<ul>";
echo "<li><strong>config/email_config.php</strong> - Email configuration settings</li>";
echo "<li><strong>includes/email_templates.php</strong> - Email template functions</li>";
echo "<li><strong>verify_email.php</strong> - Page to handle verification links</li>";
echo "<li><strong>resend_verification.php</strong> - Page to resend verification emails</li>";
echo "</ul>";

echo "<p>If you need any of these files, please let me know!</p>";
?>

<div style="margin-top: 30px; padding: 15px; background-color: #e2f0d9; border-radius: 5px;">
    <h2 style="margin-top: 0;">Next Steps</h2>
    <p>After confirming that the test email was received, you can implement the full email verification system in your registration process.</p>
    <p>If you're having issues with email delivery, consider:</p>
    <ul>
        <li>Using
