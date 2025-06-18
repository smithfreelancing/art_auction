<?php
/*
Name of file: /includes/Mailer.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Email sending functionality using PHPMailer with artmichels.com SMTP settings
*/

// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader if you're using Composer
// require 'vendor/autoload.php';

// If not using Composer, include the PHPMailer files directly
require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

class Mailer {
    private $mail;
    
    /**
     * Constructor - initializes PHPMailer with artmichels.com SMTP settings
     */
    public function __construct() {
        $this->mail = new PHPMailer(true);
        
        // Server settings for artmichels.com
        $this->mail->isSMTP();
        $this->mail->Host       = 'mail.artmichels.com';  // SMTP server
        $this->mail->SMTPAuth   = true;                   // Enable SMTP authentication
        $this->mail->Username   = 'admin@artmichels.com'; // SMTP username
        $this->mail->Password   = 'AMichels2025$$';       // SMTP password
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL encryption
        $this->mail->Port       = 465;                    // SMTP port for SSL
        
        // Set default sender
        $this->mail->setFrom('admin@artmichels.com', 'Art Auction');
        
        // Enable debug output for troubleshooting (remove in production)
        // $this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param string $altBody Plain text alternative (optional)
     * @return bool True if email sent, false otherwise
     */
    public function send($to, $subject, $body, $altBody = '') {
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            
            // Recipients
            $this->mail->addAddress($to);
            
            // Content
            $this->mail->isHTML(true);
            $this->mail->Subject = $subject;
            $this->mail->Body    = $body;
            
            if (!empty($altBody)) {
                $this->mail->AltBody = $altBody;
            } else {
                $this->mail->AltBody = strip_tags($body);
            }
            
            // Send email
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }
    
    /**
     * Add attachment to email
     * 
     * @param string $path Path to file
     * @param string $name Attachment name (optional)
     * @return bool True if attachment added, false otherwise
     */
    public function addAttachment($path, $name = '') {
        try {
            $this->mail->addAttachment($path, $name);
            return true;
        } catch (Exception $e) {
            error_log("Failed to add attachment. Error: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Add CC recipient
     * 
     * @param string $cc CC recipient email
     * @param string $name CC recipient name (optional)
     * @return bool True if CC added, false otherwise
     */
    public function addCC($cc, $name = '') {
        try {
            $this->mail->addCC($cc, $name);
            return true;
        } catch (Exception $e) {
            error_log("Failed to add CC. Error: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Add BCC recipient
     * 
     * @param string $bcc BCC recipient email
     * @param string $name BCC recipient name (optional)
     * @return bool True if BCC added, false otherwise
     */
    public function addBCC($bcc, $name = '') {
        try {
            $this->mail->addBCC($bcc, $name);
            return true;
        } catch (Exception $e) {
            error_log("Failed to add BCC. Error: {$e->getMessage()}");
            return false;
        }
    }
    
    /**
     * Set reply-to address
     * 
     * @param string $replyTo Reply-to email
     * @param string $name Reply-to name (optional)
     * @return bool True if reply-to set, false otherwise
     */
    public function setReplyTo($replyTo, $name = '') {
        try {
            $this->mail->addReplyTo($replyTo, $name);
            return true;
        } catch (Exception $e) {
            error_log("Failed to set reply-to. Error: {$e->getMessage()}");
            return false;
        }
    }
}
?>

