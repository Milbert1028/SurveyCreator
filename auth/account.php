<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to access your account", "warning");
    redirect('/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$errors = [];
$success = false;

// Get user data
$user = $db->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verify_csrf_token($csrf_token)) {
        $errors[] = "Invalid request";
    } else {
        switch ($action) {
            case 'update_profile':
                $username = sanitize_input($_POST['username'] ?? '');
                $email = sanitize_input($_POST['email'] ?? '');

                // Validate username
                if (empty($username) || strlen($username) < 3) {
                    $errors[] = "Username must be at least 3 characters long";
                }
                // Validate email
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid email format";
                }
                // Check if email is taken by another user
                elseif ($email !== $user['email']) {
                    $email_check = $db->query("SELECT id FROM users WHERE email = '" . $db->escape($email) . "' AND id != $user_id");
                    if ($email_check && $email_check->num_rows > 0) {
                        $errors[] = "Email already taken";
                    }
                }
                // Check if username is taken by another user
                elseif ($username !== $user['username']) {
                    $username_check = $db->query("SELECT id FROM users WHERE username = '" . $db->escape($username) . "' AND id != $user_id");
                    if ($username_check && $username_check->num_rows > 0) {
                        $errors[] = "Username already taken";
                    }
                }

                if (empty($errors)) {
                    $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                    $stmt->bind_param("ssi", $username, $email, $user_id);
                    
                    if ($stmt->execute()) {
                        $user['username'] = $username;
                        $user['email'] = $email;
                        flash_message("Profile updated successfully!", "success");
                        $success = true;
                    } else {
                        $errors[] = "Failed to update profile";
                    }
                }
                break;

            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                // Verify current password
                if (!password_verify($current_password, $user['password'])) {
                    $errors[] = "Current password is incorrect";
                }
                // Validate new password
                elseif (strlen($new_password) < 8) {
                    $errors[] = "New password must be at least 8 characters long";
                }
                elseif ($new_password !== $confirm_password) {
                    $errors[] = "New passwords do not match";
                }

                if (empty($errors)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->bind_param("si", $hashed_password, $user_id);
                    
                    if ($stmt->execute()) {
                        flash_message("Password changed successfully!", "success");
                        $success = true;
                    } else {
                        $errors[] = "Failed to change password";
                    }
                }
                break;

            case 'delete_account':
                $password = $_POST['confirm_delete_password'] ?? '';

                // Verify password
                if (!password_verify($password, $user['password'])) {
                    $errors[] = "Password is incorrect";
                }

                if (empty($errors)) {
                    // Start transaction
                    $db->query('START TRANSACTION');
                    
                    try {
                        // Delete user's surveys
                        $db->query("DELETE FROM surveys WHERE user_id = $user_id");
                        
                        // Delete user's responses
                        $db->query("DELETE FROM responses WHERE user_id = $user_id");
                        
                        // Delete user account
                        $db->query("DELETE FROM users WHERE id = $user_id");
                        
                        $db->query('COMMIT');
                        
                        // Log out user
                        logout_user();
                        flash_message("Your account has been deleted successfully", "success");
                        redirect('/auth/login.php');
                        
                    } catch (Exception $e) {
                        $db->query('ROLLBACK');
                        $errors[] = "Failed to delete account. Please try again.";
                    }
                }
                break;
        }
    }
}

require_once '../templates/header.php';
?>

<div class="account-container">
    <div class="account-header">
        <h2>Account Settings</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Profile Section -->
    <div class="account-section">
        <h3>Profile Information</h3>
        <form method="POST" class="account-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="update_profile">
            
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <button type="submit" class="btn btn-primary">Update Profile</button>
        </form>
    </div>

    <!-- Password Section -->
    <div class="account-section">
        <h3>Change Password</h3>
        <form method="POST" class="account-form">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="change_password">
            
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>

            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" 
                       pattern=".{8,}" title="Password must be at least 8 characters long" required>
            </div>

            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary">Change Password</button>
        </form>
    </div>

    <!-- Delete Account Section -->
    <div class="account-danger-zone">
        <h4>Delete Account</h4>
        <p class="text-danger">Warning: This action cannot be undone. All your surveys and responses will be permanently deleted.</p>
        
        <form method="POST" class="account-form" onsubmit="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <input type="hidden" name="action" value="delete_account">
            
            <div class="mb-3">
                <label for="confirm_delete_password" class="form-label">Enter your password to confirm</label>
                <input type="password" class="form-control" id="confirm_delete_password" name="confirm_delete_password" required>
            </div>

            <button type="submit" class="btn btn-danger">Delete My Account</button>
        </form>
    </div>
</div>

<?php require_once '../templates/footer.php'; ?>
