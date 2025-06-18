<?php
/*
Name of file: /includes/email_templates.php
Programmed by: Jaime C Smith
Date: 2023-11-14
Purpose of this code: Email templates for the application
*/

/**
 * Get verification email template
 * 
 * @param string $username Username
 * @param string $verification_link Verification link
 * @return string HTML email template
 */
function get_verification_email_template($username, $verification_link) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Your Email</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #4e73df;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f8f9fc;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4e73df;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Welcome to Art Auction!</h2>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($username) . ',</p>
                <p>Thank you for registering with Art Auction. To complete your registration and verify your email address, please click the button below:</p>
                <p style="text-align: center;">
                    <a href="' . $verification_link . '" class="button">Verify Email Address</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p>' . $verification_link . '</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not create an account, please ignore this email.</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * Get resend verification email template
 * 
 * @param string $username Username
 * @param string $verification_link Verification link
 * @return string HTML email template
 */
function get_resend_verification_email_template($username, $verification_link) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Your Email</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
            }
            .header {
                background-color: #4e73df;
                color: white;
                padding: 20px;
                text-align: center;
            }
            .content {
                padding: 20px;
                background-color: #f8f9fc;
            }
            .button {
                display: inline-block;
                padding: 10px 20px;
                background-color: #4e73df;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                margin: 20px 0;
            }
            .footer {
                text-align: center;
                padding: 20px;
                font-size: 12px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Email Verification</h2>
            </div>
            <div class="content">
                <p>Hello ' . htmlspecialchars($username) . ',</p>
                <p>You requested a new verification email. To verify your email address, please click the button below:</p>
                <p style="text-align: center;">
                    <a href="' . $verification_link . '" class="button">Verify Email Address</a>
                </p>
                <p>Or copy and paste this link into your browser:</p>
                <p>' . $verification_link . '</p>
                <p>This link will expire in 24 hours.</p>
                <p>If you did not request this email, please ignore it.</p>
            </div>
            <div class="footer">
                <p>© ' . date('Y') . ' Art Auction. All rights reserved.</p>
                <p>This is an automated email, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>';
}
?>

