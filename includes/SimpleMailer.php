<?php
/*
Name of file: /includes/SimpleMailer.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Simple email sending functionality optimized for admin notifications
*/

class SimpleMailer {
    private $host;
    private $username;
    private $password;
    private $port;
    private $encryption;
    private $from_email;
    private $from_name;
    
    /**
     * Constructor - initializes with email settings
     */
    public function __construct() {
        // Email settings - update these with your actual values
        $this->host = 'mail.artmichels.com';
        $this->username = 'admin@artmichels.com';
        $this->password = 'AMichels2025$$';
        $this->port = 465;
        $this->encryption = 'ssl';
        $this->from_email = 'admin@artmichels.com';
        $this->from_name = 'Art Auction';
    }
    
    /**
     * Send email
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @return bool True if email sent, false otherwise
     */
    public function send($to, $subject, $body) {
        // Log attempt
        error_log("SimpleMailer: Attempting to send email to $to with subject: $subject");
        
        // Set headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
        $headers .= "Reply-To: {$this->from_email}\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        // Try to send email using PHP's mail function
        $mail_result = mail($to, $subject, $body, $headers);
        
        if ($mail_result) {
            error_log("SimpleMailer: Email sent successfully to $to");
            return true;
        } else {
            error_log("SimpleMailer: Failed to send email to $to");
            
            // For admin notifications, try an alternative approach
            if (strpos($to, 'smithfreelancing.com') !== false) {
                error_log("SimpleMailer: Attempting alternative method for admin email");
                
                // Try with different From address
                $alt_headers = "MIME-Version: 1.0\r\n";
                $alt_headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $alt_headers .= "From: Art Auction <noreply@" . $_SERVER['HTTP_HOST'] . ">\r\n";
                
                $alt_result = mail($to, $subject, $body, $alt_headers);
                
                if ($alt_result) {
                    error_log("SimpleMailer: Alternative method successful");
                    return true;
                } else {
                    error_log("SimpleMailer: Alternative method failed");
                }
            }
            
            return false;
        }
    }
}
?>
