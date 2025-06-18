<?php
/*
Name of file: /config/smtp_config.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: SMTP configuration settings
*/

// SMTP Server Settings
define('SMTP_HOST', 'mail.artmichels.com');  // SMTP server address
define('SMTP_USERNAME', 'admin@artmichels.com');  // SMTP username
define('SMTP_PASSWORD', 'AMichels2025$$');  // SMTP password
define('SMTP_PORT', 465);  // SMTP port (usually 587 for TLS, 465 for SSL)

// Sender Information
define('SMTP_FROM_EMAIL', 'admin@artmichels.com');  // From email address
define('SMTP_FROM_NAME', 'Art Auction');  // From name

/*
INSTRUCTIONS:
1. Replace 'smtp.example.com' with your actual SMTP server address
   - For Gmail: smtp.gmail.com
   - For Outlook/Hotmail: smtp.office365.com
   - For Yahoo: smtp.mail.yahoo.com
   - Check with your email provider for the correct SMTP server

2. Replace 'your_email@example.com' with your actual email address

3. Replace 'your_email_password' with your actual email password
   - For Gmail, you may need to create an "App Password" if you have 2FA enabled
   - Visit: https://myaccount.google.com/apppasswords

4. Set the correct port number:
   - 587 for TLS (most common)
   - 465 for SSL
   - Check with your email provider for the correct port

5. Update the sender information:
   - SMTP_FROM_EMAIL: The email address that will appear in the "From" field
   - SMTP_FROM_NAME: The name that will appear in the "From" field

6. Make sure this file has restricted access permissions
   - chmod 600 smtp_config.php (on Linux/Unix systems)
*/
?>
