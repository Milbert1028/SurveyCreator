<?php
/**
 * Admin Account Creation Script
 * 
 * This script creates an admin account in the database.
 * IMPORTANT: Delete this file after use for security reasons.
 */

// Set to true to run the script
$allow_execution = true;

// Admin account details
$admin_username = "admin";
$admin_email = "admin@example.com";
$admin_password = "Admin@123456"; // Change this to a strong password

// Include database connection
require_once 'includes/config.php';
require_once 'includes/db.php';

if (!$allow_execution) {
    die("Script execution is disabled. Set \$allow_execution to true to run this script.");
}

// Validate inputs
if (empty($admin_username) || empty($admin_email) || empty($admin_password)) {
    die("Error: All fields are required.");
}

if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.");
}

// Password strength check
if (strlen($admin_password) < 10 || 
    !preg_match('/[A-Z]/', $admin_password) || 
    !preg_match('/[a-z]/', $admin_password) || 
    !preg_match('/[0-9]/', $admin_password) || 
    !preg_match('/[^A-Za-z0-9]/', $admin_password)) {
    die("Error: Password must be at least 10 characters and include uppercase, lowercase, numbers, and special characters.");
}

try {
    // Connect to database
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Check if email already exists
    $email = $db->escape($admin_email);
    $check_email = $db->query("SELECT id FROM users WHERE email = '$email'");
    if ($check_email && $check_email->num_rows > 0) {
        die("Error: Email address is already registered.");
    }
    
    // Check if username already exists
    $username = $db->escape($admin_username);
    $check_username = $db->query("SELECT id FROM users WHERE username = '$username'");
    if ($check_username && $check_username->num_rows > 0) {
        die("Error: Username is already taken.");
    }
    
    // Generate password hash
    $password_hash = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Set role to admin and verify email by default
    $role = 'admin';
    $email_verified = 1;
    
    // Insert admin user
    $sql = "INSERT INTO users (username, email, password_hash, role, email_verified) 
            VALUES ('$username', '$email', '$password_hash', '$role', $email_verified)";
    
    $result = $db->query($sql);
    
    if (!$result) {
        die("Error creating admin account: " . $db->getLastError());
    }
    
    echo "<div style='background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h3>✅ Admin Account Created Successfully</h3>";
    echo "<p><strong>Username:</strong> " . htmlspecialchars($admin_username) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($admin_email) . "</p>";
    echo "<p><strong>Password:</strong> [Not displayed for security]</p>";
    echo "<p><strong>IMPORTANT:</strong> Delete this file immediately for security reasons.</p>";
    echo "<p>You can now log in at <a href='Admin/login.php'>Admin/login.php</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 