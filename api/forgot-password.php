<?php
// Set JSON content type first
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/SimpleMailer.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output
ini_set('log_errors', 1); // Enable error logging
ini_set('error_log', '../logs/php_errors.log'); // Set error log file

// Log incoming request
$raw_data = file_get_contents('php://input');
error_log('Received request data: ' . $raw_data);

// Function to send JSON response
function send_json_response($success, $message = '', $data = null) {
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
    ];
    error_log('Sending response: ' . json_encode($response));
    
    if (!$success) {
        http_response_code(400);
    }
    echo json_encode($response);
    exit;
}

// Get JSON request data
$data = json_decode($raw_data, true);
if (!$data) {
    send_json_response(false, 'Invalid request data: ' . json_last_error_msg());
}

$action = $data['action'] ?? '';
$email = $data['email'] ?? '';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_json_response(false, 'Invalid email address');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Log connection status
    error_log('Database connection status: ' . ($conn->connect_error ? $conn->connect_error : 'Connected'));

    // Verify tables exist
    $tables_check = $conn->query("
        SELECT 1 
        FROM information_schema.tables 
        WHERE table_schema = '" . DB_NAME . "' 
        AND table_name IN ('users', 'password_resets')
    ");
    
    if ($tables_check->num_rows < 2) {
        throw new Exception('Required database tables are missing');
    }

    switch ($action) {
        case 'send_code':
            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception('Failed to execute statement: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            if ($result->num_rows === 0) {
                send_json_response(false, 'Email address not found');
            }
            
            // Generate verification code
            $code = sprintf('%06d', mt_rand(0, 999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing codes for this email
            $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $delete_stmt->bind_param('s', $email);
            $delete_stmt->execute();
            
            // Save code to database
            $stmt = $conn->prepare("INSERT INTO password_resets (email, code, expires_at) VALUES (?, ?, ?)");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param('sss', $email, $code, $expires_at);
            if (!$stmt->execute()) {
                throw new Exception('Failed to save reset code: ' . $stmt->error);
            }
            
            // Debug: Check if code was saved
            $check_stmt = $conn->prepare("SELECT code, expires_at FROM password_resets WHERE email = ?");
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            $saved_code = $check_result->fetch_assoc();
            
            error_log("Debug - Saved code: " . json_encode($saved_code));
            
            if (!$saved_code || $saved_code['code'] !== $code) {
                throw new Exception('Failed to verify saved code');
            }
            
            // Send email if enabled
            $email_sent = false;
            $email_error = '';
            
            if (defined('EMAIL_ENABLED') && EMAIL_ENABLED) {
                try {
                    $email_result = SimpleMailer::sendPasswordResetCode($email, $code);
                    
                    if ($email_result === true) {
                        $email_sent = true;
                    } else {
                        $email_error = 'Failed to send email';
                        error_log("Failed to send email: " . $email_error);
                    }
                } catch (Exception $e) {
                    $email_error = $e->getMessage();
                    error_log("Email exception: " . $email_error);
                }
            }
            
            // Return appropriate response based on email sending status
            if ($email_sent) {
                send_json_response(true, 'Reset code sent to your email');
            } else {
                // If email is not sent, throw an error
                throw new Exception('Failed to send verification email. Please try again later or contact support.');
            }
            break;
            
        case 'verify_code':
            $code = $data['code'] ?? '';
            
            error_log("Debug - Verifying code: " . json_encode([
                'email' => $email,
                'code' => $code,
                'raw_data' => $raw_data
            ]));
            
            if (!$code || strlen($code) !== 6 || !ctype_digit($code)) {
                send_json_response(false, 'Invalid verification code format');
            }
            
            // Get the latest code for this email
            $stmt = $conn->prepare("
                SELECT code, expires_at, used 
                FROM password_resets 
                WHERE email = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception('Failed to verify code: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $reset_data = $result->fetch_assoc();
            
            error_log("Debug - Reset data found: " . json_encode($reset_data));
            
            if (!$reset_data) {
                send_json_response(false, 'No reset code found');
            }
            
            if ($reset_data['used']) {
                send_json_response(false, 'Code has already been used');
            }
            
            if (strtotime($reset_data['expires_at']) < time()) {
                send_json_response(false, 'Code has expired');
            }
            
            if ($reset_data['code'] !== $code) {
                send_json_response(false, 'Invalid verification code');
            }
            
            send_json_response(true, 'Code verified successfully');
            break;
            
        case 'reset_password':
            $password = $data['password'] ?? '';
            
            error_log("Debug - Reset password request: " . json_encode([
                'email' => $email,
                'has_password' => !empty($password)
            ]));
            
            if (strlen($password) < 8) {
                send_json_response(false, 'Password must be at least 8 characters long');
            }
            
            // Get the latest verified and unused code
            $stmt = $conn->prepare("
                SELECT id, used, expires_at 
                FROM password_resets 
                WHERE email = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            
            if (!$stmt) {
                throw new Exception('Failed to prepare statement: ' . $conn->error);
            }
            
            $stmt->bind_param('s', $email);
            if (!$stmt->execute()) {
                throw new Exception('Failed to verify code: ' . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $reset_data = $result->fetch_assoc();
            
            if (!$reset_data) {
                send_json_response(false, 'No verification found');
            }
            
            if ($reset_data['used']) {
                send_json_response(false, 'Reset code has already been used');
            }
            
            if (strtotime($reset_data['expires_at']) < time()) {
                send_json_response(false, 'Reset code has expired');
            }
            
            // Mark the reset code as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->bind_param('i', $reset_data['id']);
            $stmt->execute();
            
            // Update the user's password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Log what we're trying to do
            error_log("Updating password for user: " . $email);
            
            // Change 'password' field to 'password_hash' to match the database schema
            $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            
            if (!$stmt) {
                error_log("Prepare error: " . $conn->error);
                throw new Exception('Failed to prepare update statement: ' . $conn->error);
            }
            
            $stmt->bind_param('ss', $hashed_password, $email);
            
            if (!$stmt->execute()) {
                error_log("Execute error: " . $stmt->error);
                throw new Exception('Failed to update password: ' . $stmt->error);
            }
            
            if ($stmt->affected_rows === 0) {
                error_log("No rows affected when updating password for: " . $email);
                throw new Exception('Failed to update password - no rows affected. Email may not exist.');
            }
            
            error_log("Password updated successfully for: " . $email);
            
            // Send password reset confirmation email
            if (defined('EMAIL_ENABLED') && EMAIL_ENABLED) {
                try {
                    error_log("Attempting to send password reset confirmation email to: " . $email);
                    $email_result = SimpleMailer::sendPasswordResetConfirmation($email);
                    
                    if ($email_result === true) {
                        error_log("Password reset confirmation email sent successfully");
                    } else {
                        error_log("Failed to send password reset confirmation: " . $email_result);
                    }
                } catch (Exception $e) {
                    error_log("Exception when sending password reset confirmation: " . $e->getMessage());
                    // Continue even if email fails - don't break the password reset process
                }
            } else {
                error_log("Email is disabled - skipping password reset confirmation email");
            }
            
            send_json_response(true, 'Password has been reset successfully');
            break;
            
        default:
            send_json_response(false, 'Invalid action');
    }
} catch (Exception $e) {
    error_log('Error in forgot-password.php: ' . $e->getMessage());
    send_json_response(false, $e->getMessage());
}
