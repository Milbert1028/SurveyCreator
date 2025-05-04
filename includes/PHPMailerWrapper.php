<?php
/**
 * PHPMailer Wrapper for Survey Creator
 * 
 * This class provides a wrapper around PHPMailer for sending emails in the application.
 * It requires the PHPMailer library to be installed in the vendor directory.
 */

// Check if PHPMailer autoload exists
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // If vendor autoload doesn't exist, we'll add fallback paths
    $phpmailer_paths = [
        __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php',
        __DIR__ . '/../lib/PHPMailer/src/SMTP.php',
        __DIR__ . '/../lib/PHPMailer/src/Exception.php'
    ];
    
    foreach ($phpmailer_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
        }
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class PHPMailerWrapper {
    /**
     * Send an email using PHPMailer
     *
     * @param string $to_email Recipient email address
     * @param string $to_name Recipient name (optional)
     * @param string $subject Email subject
     * @param string $html_body HTML email body
     * @param string $text_body Plain text email body (optional)
     * @param array $attachments Array of attachments (optional)
     * @return boolean|string True on success, error message on failure
     */
    public static function sendEmail($to_email, $to_name = '', $subject, $html_body, $text_body = '', $attachments = []) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->SMTPDebug = 0; // No debug output
            $mail->isSMTP();
            $mail->Host       = 'mail.smtp2go.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '_mainaccount@surveycreator.online';
            $mail->Password   = 'gMI5qpQBgIukelHh';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 2525;
            
            // Add SSL options to handle certificate issues
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
            // Recipients
            $mail->setFrom(defined('EMAIL_FROM') ? EMAIL_FROM : 'noreply@surveycreator.online', EMAIL_FROM_NAME);
            $mail->addAddress($to_email, $to_name);
            $mail->addReplyTo(defined('EMAIL_REPLY_TO') ? EMAIL_REPLY_TO : 'support@surveycreator.online', defined('EMAIL_REPLY_TO_NAME') ? EMAIL_REPLY_TO_NAME : EMAIL_FROM_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_body;
            
            if (!empty($text_body)) {
                $mail->AltBody = $text_body;
            } else {
                $mail->AltBody = strip_tags(str_replace(['<br>', '<br />', '<br/>'], "\n", $html_body));
            }
            
            // Add attachments if any
            if (!empty($attachments) && is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $mail->addAttachment(
                            $attachment['path'],
                            $attachment['name'] ?? basename($attachment['path']),
                            $attachment['encoding'] ?? 'base64',
                            $attachment['type'] ?? ''
                        );
                    }
                }
            }
            
            // Send the email
            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Email could not be sent. PHPMailer Error: {$mail->ErrorInfo}";
        }
    }
    
    /**
     * Send a password reset code to a user
     *
     * @param string $to_email Recipient email address
     * @param string $code Verification code
     * @return boolean|string True on success, error message on failure
     */
    public static function sendPasswordResetCode($to_email, $code) {
        $subject = APP_NAME . " - Password Reset Code";
        
        // Create HTML body
        $html_body = '
        <html>
        <head>
            <title>' . $subject . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
                <h2 style="color: #0d6efd; margin-top: 0;">Password Reset Code</h2>
                <p>You are receiving this email because a password reset request was made for your account.</p>
                <div style="background-color: #fff; padding: 15px; border-radius: 4px; margin: 20px 0; text-align: center; border: 1px solid #ddd;">
                    <h3 style="font-size: 24px; letter-spacing: 5px; margin: 10px 0; color: #333;">' . $code . '</h3>
                </div>
                <p>Enter this code on the verification page to continue with the password reset process.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p style="margin-top: 30px; font-size: 12px; color: #777;">
                    This code will expire in 1 hour.
                </p>
            </div>
        </body>
        </html>';
        
        // Create plain text alternative
        $text_body = "Password Reset Code: $code\n\n"
                   . "Enter this code on the verification page to continue with the password reset process.\n\n"
                   . "If you did not request a password reset, please ignore this email.\n\n"
                   . "This code will expire in 1 hour.";
        
        return self::sendEmail($to_email, '', $subject, $html_body, $text_body);
    }
    
    /**
     * Send a password reset confirmation to a user
     *
     * @param string $to_email Recipient email address
     * @return boolean|string True on success, error message on failure
     */
    public static function sendPasswordResetConfirmation($to_email) {
        $subject = APP_NAME . " - Password Reset Successful";
        
        // Create HTML body
        $html_body = '
        <html>
        <head>
            <title>' . $subject . '</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
                <h2 style="color: #0d6efd; margin-top: 0;">Password Reset Successful</h2>
                <p>Your password has been reset successfully.</p>
                <p>If you did not request this password reset, please contact support immediately as your account may have been compromised.</p>
            </div>
        </body>
        </html>';
        
        // Create plain text alternative
        $text_body = "Your password has been reset successfully.\n\n"
                   . "If you did not request this password reset, please contact support immediately as your account may have been compromised.";
        
        return self::sendEmail($to_email, '', $subject, $html_body, $text_body);
    }
}
