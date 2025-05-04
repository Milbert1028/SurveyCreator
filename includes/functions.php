<?php
require_once __DIR__ . '/config.php';
require_once 'db.php';

// Authentication functions
function register_user($username, $email, $password) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $username = $db->escape($username);
        $email = $db->escape($email);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Check if email already exists
        $check_email = $db->query("SELECT id FROM users WHERE email = '$email'");
        if ($check_email && $check_email->num_rows > 0) {
            return "Email address is already registered";
        }
        
        // Check if username already exists
        $check_username = $db->query("SELECT id FROM users WHERE username = '$username'");
        if ($check_username && $check_username->num_rows > 0) {
            return "Username is already taken";
        }
        
        // Set email_verified based on config
        $email_verified = (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION === false) ? 1 : 0;
        
        // Insert the new user
        $sql = "INSERT INTO users (username, email, password_hash, email_verified) VALUES ('$username', '$email', '$password_hash', $email_verified)";
        $result = $db->query($sql);
        
        if (!$result) {
            error_log("Failed to insert user: " . $db->getLastError());
            return "Database error: " . $db->getLastError();
        }
        
        $user_id = $conn->insert_id;
        
        // Skip email verification if disabled in config
        if (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION === false) {
            // Skip verification email sending if not required
            return true;
        }
        
        // Create and send verification email
        $verification_result = create_email_verification($user_id, $email);
        if ($verification_result !== true) {
            // Log the failure but don't prevent registration
            error_log("Failed to create verification during registration: " . $verification_result);
            
            // Try sending through alternate method
            $retry_result = resend_verification_email($email);
            if ($retry_result !== true) {
                error_log("Failed to send verification through alternate method: " . $retry_result);
                // Return success anyway but log the issue
                return true;
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Exception in register_user: " . $e->getMessage());
        return "Exception: " . $e->getMessage();
    }
}

function create_email_verification($user_id, $email) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        
        // Set expiration time to 24 hours from now
        $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Delete any existing verification tokens for this user
        $delete_sql = "DELETE FROM email_verifications WHERE user_id = " . intval($user_id);
        $db->query($delete_sql);
        
        // Insert new verification token
        $sql = "INSERT INTO email_verifications (user_id, token, expires_at) 
                VALUES (" . intval($user_id) . ", '" . $db->escape($token) . "', '" . $db->escape($expires_at) . "')";
        $result = $db->query($sql);
        
        if (!$result) {
            error_log("Failed to insert verification token: " . $db->getLastError());
            return "Failed to create verification token: " . $db->getLastError();
        }
        
        // Send verification email
        $email_result = send_verification_email($email, $token);
        if ($email_result !== true) {
            error_log("Failed to send verification email: " . $email_result);
            return "Failed to send verification email: " . $email_result;
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Exception in create_email_verification: " . $e->getMessage());
        return "Exception: " . $e->getMessage();
    }
}

function send_verification_email($email, $token) {
    // Generate verification URL
    $verification_url = SITE_URL . "/auth/verify-email.php?token=" . urlencode($token);
    
    // Send email using SimpleMailer
    require_once __DIR__ . '/SimpleMailer.php';
    
    $subject = "Verify Your Email Address";
    $body = '
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto;">
        <div style="background-color: #f7f7f7; padding: 20px; border-radius: 5px; border-top: 4px solid #0d6efd;">
            <h2 style="color: #0d6efd; margin-top: 0;">Verify Your Email Address</h2>
            <p>Thank you for registering! To complete your registration and activate your account, please click the button below:</p>
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . $verification_url . '" style="background-color: #0d6efd; color: white; padding: 12px 20px; text-decoration: none; border-radius: 4px; font-weight: bold;">
                    Verify Email Address
                </a>
            </div>
            <p>Or copy and paste this link into your browser:</p>
            <p style="background-color: #e9ecef; padding: 10px; border-radius: 4px;">' . $verification_url . '</p>
            <p>This verification link will expire in 24 hours.</p>
            <p>If you did not sign up for an account, you can safely ignore this email.</p>
        </div>
    </body>
    </html>';
    
    return SimpleMailer::sendEmail($email, $subject, $body);
}

function verify_email($token) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Find the verification record
    $token = $db->escape($token);
    $sql = "SELECT user_id, expires_at FROM email_verifications WHERE token = '$token' LIMIT 1";
    $result = $db->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $verification = $result->fetch_assoc();
        $current_time = date('Y-m-d H:i:s');
        
        // Check if token has expired
        if ($verification['expires_at'] < $current_time) {
            return [
                'success' => false,
                'message' => 'Verification link has expired. Please request a new one.'
            ];
        }
        
        // Update user's email_verified status
        $user_id = intval($verification['user_id']);
        $update_sql = "UPDATE users SET email_verified = 1 WHERE id = $user_id";
        $update_result = $db->query($update_sql);
        
        if ($update_result) {
            // Remove verification token
            $delete_sql = "DELETE FROM email_verifications WHERE token = '$token'";
            $db->query($delete_sql);
            
            return [
                'success' => true,
                'message' => 'Email verified successfully! You can now log in.'
            ];
        }
    }
    
    return [
        'success' => false,
        'message' => 'Invalid verification link.'
    ];
}

function login_user($email, $password) {
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        
        $email = $db->escape($email);
        $sql = "SELECT * FROM users WHERE email = '$email' LIMIT 1";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password_hash'])) {
                // Check if email verification is required and the email is not verified
                if (defined('REQUIRE_EMAIL_VERIFICATION') && REQUIRE_EMAIL_VERIFICATION === true && $user['email_verified'] == 0) {
                    return [
                        'success' => false,
                        'message' => 'Your account requires email verification before login. Please check your inbox or click below to resend the verification email:',
                        'reason' => 'unverified_email',
                        'user_id' => $user['id']
                    ];
                }
                
                // Email is verified or verification is not required, proceed with login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return [
                    'success' => true
                ];
            }
        }
        return [
            'success' => false,
            'message' => 'Invalid email or password',
            'reason' => 'invalid_credentials'
        ];
    } catch (Exception $e) {
        error_log("Exception in login_user: " . $e->getMessage());
        return [
            'success' => false,
            'message' => "An error occurred during login.",
            'reason' => 'system_error'
        ];
    }
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function logout_user() {
    session_destroy();
    session_start();
}

// Survey functions
function create_survey($user_id, $title, $description) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO surveys (user_id, title, description) VALUES (?, ?, ?)");
    
    if ($stmt === false) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("iss", $user_id, $title, $description);
    
    if ($stmt->execute()) {
        return $conn->insert_id;
    }
    
    return false;
}

function add_question($survey_id, $question_text, $question_type, $options, $required, $order) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $options_json = $options ? json_encode($options) : 'NULL';
    $required = $required ? 1 : 0;
    
    $stmt = $conn->prepare("INSERT INTO questions (survey_id, question_text, question_type, options, required, order_position) 
            VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("isssii", $survey_id, $question_text, $question_type, $options_json, $required, $order);
    
    return $stmt->execute();
}

function get_survey($survey_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $sql = "SELECT s.*, u.username as creator 
            FROM surveys s 
            JOIN users u ON s.user_id = u.id 
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        error_log("Failed to prepare statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $survey_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $survey = $result->fetch_assoc();
        
        // Get questions
        $questions_sql = "SELECT * FROM questions WHERE survey_id = ? ORDER BY order_position";
        $questions_stmt = $conn->prepare($questions_sql);
        
        if ($questions_stmt === false) {
            error_log("Failed to prepare questions statement: " . $conn->error);
            return false;
        }
        
        $questions_stmt->bind_param("i", $survey_id);
        $questions_stmt->execute();
        $questions_result = $questions_stmt->get_result();
        
        $survey['questions'] = [];
        while ($question = $questions_result->fetch_assoc()) {
            // Check if options exist and are not null before decoding
            if (!empty($question['options'])) {
                $question['options'] = json_decode($question['options'], true);
                // Handle potential JSON decode errors
                if (json_last_error() !== JSON_ERROR_NONE) {
                    error_log("JSON decode error for options in question ID {$question['id']}: " . json_last_error_msg());
                    $question['options'] = []; // Default to empty array on error
                }
            } else {
                $question['options'] = []; // Ensure options is always an array
            }
            $survey['questions'][] = $question;
        }
        
        return $survey;
    }
    return false;
}

function get_share_settings($survey_id) {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    try {
        // We already know restrict_access exists from the SQL you ran
        $stmt = $conn->prepare("SELECT show_progress_bar, allow_multiple_responses, require_login, restrict_access, response_limit, close_date FROM surveys WHERE id = ?");
        
        if ($stmt === false) {
            error_log("Failed to prepare the statement: " . $conn->error);
            return [
                'show_progress_bar' => 1,
                'allow_multiple_responses' => 0,
                'require_login' => 0,
                'restrict_access' => 0,
                'response_limit' => null,
                'close_date' => null
            ];
        }
        
        $stmt->bind_param("i", $survey_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result === false) {
            error_log("Failed to execute query: " . $conn->error);
            return [
                'show_progress_bar' => 1,
                'allow_multiple_responses' => 0,
                'require_login' => 0,
                'restrict_access' => 0,
                'response_limit' => null,
                'close_date' => null
            ];
        }
        
        $settings = $result->fetch_assoc();
        return $settings;
    } catch (Exception $e) {
        error_log("Exception in get_share_settings: " . $e->getMessage());
        return [
            'show_progress_bar' => 1,
            'allow_multiple_responses' => 0,
            'require_login' => 0,
            'restrict_access' => 0,
            'response_limit' => null,
            'close_date' => null
        ];
    }
}

// Utility functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($path) {
    // Check if path already contains the domain (absolute URL)
    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
        header("Location: " . $path);
    } 
    // Check if path already starts with a slash
    else if (strpos($path, '/') === 0) {
        header("Location: " . SITE_URL . $path);
    } 
    // Otherwise, add a slash
    else {
        header("Location: " . SITE_URL . '/' . $path);
    }
    exit();
}

function flash_message($message, $type = 'info') {
    $_SESSION['flash'] = [
        'message' => $message,
        'type' => $type
    ];
}

function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Resend verification email by email address
 * 
 * @param string $email User's email address
 * @return bool|string True on success, error message on failure
 */
function resend_verification_email($email) {
    try {
        $db = Database::getInstance();
        
        // Find the user
        $email_escaped = $db->escape($email);
        $sql = "SELECT id, email_verified FROM users WHERE email = '$email_escaped' LIMIT 1";
        $result = $db->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if email is already verified
            if ($user['email_verified'] == 1) {
                return "Email already verified";
            }
            
            // Create and send verification email
            return create_email_verification($user['id'], $email);
        }
        
        return "User not found";
    } catch (Exception $e) {
        error_log("Exception in resend_verification_email: " . $e->getMessage());
        return "Exception: " . $e->getMessage();
    }
}
