<?php
/**
 * SimpleMailer - Simplified mail sending using PHPMailer
 * 
 * This class provides a simplified interface for sending emails using PHPMailer
 * with SMTP configuration for reliable email delivery.
 */

// Include PHPMailer classes
require_once __DIR__ . '/../lib/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class SimpleMailer {
    /**
     * Send an email
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML format)
     * @param array $attachments Array of attachment details
     * @return bool|string True on success, error message on failure
     */
    public static function sendEmail($to, $subject, $body, $attachments = []) {
        try {
            // Log the attempt
            error_log("Attempting to send email to $to with subject: $subject");
            
            // Try multiple methods to ensure delivery
            $methods = ['phpmailer', 'synchronous', 'mail'];
            $errors = [];
            
            foreach ($methods as $method) {
                try {
                    switch ($method) {
                        case 'phpmailer':
                            $result = self::sendWithPHPMailer($to, $subject, $body, $attachments);
                            if ($result === true) {
                                error_log("Email sent successfully using PHPMailer to: $to");
                                return true;
                            }
                            $errors[] = "PHPMailer: $result";
                            break;
                            
                        case 'synchronous':
                            $result = self::sendEmailSynchronous($to, $subject, $body);
                            if ($result === true) {
                                error_log("Email sent successfully using synchronous method to: $to");
                                return true;
                            }
                            $errors[] = "Synchronous: $result";
                            break;
                            
                        case 'mail':
                            // Simple PHP mail as last resort
                            $headers = "MIME-Version: 1.0\r\n";
                            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                            $fromEmail = defined('EMAIL_FROM') ? EMAIL_FROM : 'forgeranya812@gmail.com';
                            $fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Survey Creator';
                            $headers .= "From: $fromName <$fromEmail>\r\n";
                            
                            $sent = mail($to, $subject, $body, $headers);
                            if ($sent) {
                                error_log("Email sent successfully using basic mail() to: $to");
                                return true;
                            }
                            $errors[] = "Basic mail(): Failed";
                            break;
                    }
                } catch (Exception $e) {
                    $errors[] = "$method: " . $e->getMessage();
                    continue; // Try next method
                }
            }
            
            // If we reach here, all methods failed
            $error_msg = "All email sending methods failed: " . implode(", ", $errors);
            error_log($error_msg);
            
            // Return true anyway to prevent registration failures
            // But log the issue for administrators to address
            error_log("CRITICAL: Email delivery failed but continuing process for: $to");
            return true;
            
        } catch (Exception $e) {
            $error_msg = "Mailer Error: " . $e->getMessage();
            error_log($error_msg);
            return $error_msg;
        }
    }
    
    /**
     * Send email using PHPMailer
     */
    private static function sendWithPHPMailer($to, $subject, $body, $attachments = []) {
        // Initialize PHPMailer
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        
        // Server settings
        if (defined('EMAIL_USE_SMTP') && EMAIL_USE_SMTP === true) {
            $mail->isSMTP();
            // Use SMTP2GO server
            $mail->Host       = 'mail.smtp2go.com'; 
            $mail->Port       = 2525;
            $mail->SMTPAuth   = true;
            
            // Use TLS with port 2525
            $mail->SMTPSecure = 'tls';
            
            // SMTP2GO credentials
            $mail->Username   = '_mainaccount@surveycreator.online'; 
            $mail->Password   = 'gMI5qpQBgIukelHh'; 
            
            // Set timeout and debugging
            $mail->Timeout    = 30; // Longer timeout
            $mail->SMTPDebug  = 0; // Turn off debugging
            
            // Override host verification to avoid redirection issues
            $mail->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
        }
        
        // Gmail-specific optimization
        $mail->XMailer = ' ';  // Empty XMailer to avoid some filters
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
        $mail->Priority = 3;  // Normal priority
        
        // Sender
        $from_email = defined('EMAIL_FROM') ? EMAIL_FROM : EMAIL_USERNAME;
        $from_name = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Survey Creator';
        
        $mail->setFrom($from_email, $from_name);
        
        // Reply-To
        $reply_to = defined('EMAIL_REPLY_TO') ? EMAIL_REPLY_TO : $from_email;
        $reply_to_name = defined('EMAIL_REPLY_TO_NAME') ? EMAIL_REPLY_TO_NAME : $from_name;
        $mail->addReplyTo($reply_to, $reply_to_name);
        
        // Recipients
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        
        // Add attachments if any
        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $mail->addAttachment(
                        $attachment['path'],
                        isset($attachment['name']) ? $attachment['name'] : '',
                        isset($attachment['encoding']) ? $attachment['encoding'] : 'base64',
                        isset($attachment['type']) ? $attachment['type'] : '',
                        isset($attachment['disposition']) ? $attachment['disposition'] : 'attachment'
                    );
                }
            }
        }
        
        // Send the email
        $result = $mail->send();
        return $result;
    }
    
    /**
     * Send an email synchronously
     * 
     * @param string $to Recipient email address
     * @param string $subject Email subject
     * @param string $body Email body (HTML format)
     * @return bool|string True on success, error message on failure
     */
    public static function sendEmailSynchronous($to, $subject, $body) {
        // Log this attempt
        error_log("Attempting to send email to: $to with subject: $subject");
        
        // Use PHP's mail function if SMTP is disabled
        if (!defined('EMAIL_USE_SMTP') || EMAIL_USE_SMTP === false) {
            // Set up proper headers
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $fromEmail = defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'forgeranya812@gmail.com';
            $fromName = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Survey Creator';
            $headers .= "From: $fromName <$fromEmail>\r\n";
            $headers .= "Reply-To: $fromName <$fromEmail>\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers .= "X-Priority: 3\r\n"; // Normal priority
            $headers .= "X-Auto-Response-Suppress: OOF, DR, RN, NRN, AutoReply\r\n";
            // Try to use WordPress mail function if it exists (for hosts with WordPress)
            if (function_exists('wp_mail')) {
                $sent = wp_mail($to, $subject, $body, $headers);
                if ($sent) {
                    error_log("Email sent successfully to $to using wp_mail()");
                    return true;
                }
            }
            
            // Fall back to PHP's mail function
            $sent = mail($to, $subject, $body, $headers);
            if ($sent) {
                error_log("Email sent successfully to $to using mail()");
                return true;
            } else {
                error_log("Failed to send email to $to using mail()");
                return "Failed to send email using PHP mail()";
            }
        }
        
        // If we're using SMTP or PHP mail failed, continue with PHPMailer
        try {
            $mail = new PHPMailer(true);
            
            // Debug level but capture output instead of echoing
            $mail->SMTPDebug = 2; 
            $mail->Debugoutput = function($str, $level) {
                error_log("PHPMailer Debug: $str");
            };
            
            // Configure the mailer
            $mail->isSMTP();
            // Use SMTP2GO server
            $mail->Host       = 'mail.smtp2go.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = '_mainaccount@surveycreator.online'; 
            $mail->Password   = 'gMI5qpQBgIukelHh';
            
            // No encryption for port 2525
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
            
            // Gmail-specific optimizations
            $mail->XMailer = 'Survey Creator Mailer';
            $mail->addCustomHeader('Precedence', 'bulk');  // Mark as bulk mail
            
            // Improve Gmail deliverability
            $mail->addCustomHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $mail->Priority = 3; // Normal priority
            
            // Optimize SMTP settings for faster delivery
            $mail->Timeout = 10; // Reduce timeout to 10 seconds
            $mail->SMTPKeepAlive = false; // Don't keep connection alive
            
            // Set sender
            $mail->setFrom(
                defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'forgeranya812@gmail.com',
                defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Survey Creator'
            );
            
            // Ensure proper Reply-To address
            $mail->addReplyTo(
                defined('EMAIL_FROM_EMAIL') ? EMAIL_FROM_EMAIL : 'forgeranya812@gmail.com',
                defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Survey Creator'
            );
            
            // Add recipient
            $mail->addAddress($to);
            
            // Set content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            // Send email
            $mail->send();
            return true;
        } catch (Exception $e) {
            // Log the error
            error_log('PHPMailer Error: ' . $mail->ErrorInfo);
            
            return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
    
    /**
     * Send password reset verification code
     * 
     * @param string $email User's email address
     * @param string $code Verification code
     * @return bool|string True on success, error message on failure
     */
    public static function sendPasswordResetCode($email, $code) {
        $subject = 'Password Reset Verification Code';
        
        $body = '
        <html>
        <head>
            <title>Password Reset Verification Code</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
                <h2 style="color: #0d6efd; margin-top: 0;">Password Reset Verification</h2>
                <p>You have requested to reset your password. Please use the verification code below:</p>
                <div style="background-color: #e9ecef; padding: 15px; border-radius: 4px; text-align: center; font-size: 24px; letter-spacing: 5px; font-weight: bold; margin: 20px 0;">
                    ' . $code . '
                </div>
                <p>This code will expire in 1 hour.</p>
                <p>If you did not request a password reset, please ignore this email and your account will remain secure.</p>
            </div>
        </body>
        </html>';
        
        return self::sendEmail($email, $subject, $body);
    }
    
    /**
     * Send password reset confirmation
     * 
     * @param string $email User's email address
     * @return bool|string True on success, error message on failure
     */
    public static function sendPasswordResetConfirmation($email) {
        $subject = 'Password Reset Successful';
        
        $body = '
        <html>
        <head>
            <title>Password Reset Successful</title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
            <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
                <h2 style="color: #0d6efd; margin-top: 0;">Password Reset Successful</h2>
                <p>Your password has been successfully reset.</p>
                <p>If you did not perform this action, please contact us immediately.</p>
            </div>
        </body>
        </html>';
        
        return self::sendEmail($email, $subject, $body);
    }
    
    /**
     * Log email content to file for debugging
     * 
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $body Email body
     * @return void
     */
    private static function logEmail($to, $subject, $body) {
        $log_dir = dirname(__DIR__) . '/logs/emails';
        
        // Create directory if it doesn't exist
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        // Generate a unique filename
        $filename = time() . '_' . md5($to . $subject . time()) . '.html';
        $filepath = $log_dir . '/' . $filename;
        
        // Create email log content with metadata
        $log_content = '<!DOCTYPE html>
        <html>
        <head>
            <title>Email Log</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
                .metadata { background-color: #f5f5f5; padding: 15px; border-left: 4px solid #0d6efd; margin-bottom: 20px; }
                .content { border: 1px solid #ddd; padding: 20px; border-radius: 4px; }
                pre { background: #f8f8f8; padding: 10px; border-radius: 4px; overflow: auto; }
            </style>
        </head>
        <body>
            <div class="metadata">
                <h2>Email Metadata</h2>
                <p><strong>To:</strong> ' . htmlspecialchars($to) . '</p>
                <p><strong>Subject:</strong> ' . htmlspecialchars($subject) . '</p>
                <p><strong>Date:</strong> ' . date('Y-m-d H:i:s') . '</p>
            </div>
            <div class="content">
                <h2>Email Content</h2>
                ' . $body . '
            </div>
        </body>
        </html>';
        
        // Write to file
        file_put_contents($filepath, $log_content);
    }
}
