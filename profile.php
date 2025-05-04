<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to access your profile", "warning");
    redirect('/auth/login.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get user data
$user = $db->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        flash_message("Invalid request", "danger");
        redirect('/profile.php');
    }

    $errors = [];
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate username
    if (empty($username) || strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    } elseif ($username !== $user['username']) {
        // Check if username is taken
        $check = $db->query("SELECT id FROM users WHERE username = '" . $db->escape($username) . "' AND id != $user_id");
        if ($check && $check->num_rows > 0) {
            $errors[] = "Username is already taken";
        }
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif ($email !== $user['email']) {
        // Check if email is taken
        $check = $db->query("SELECT id FROM users WHERE email = '" . $db->escape($email) . "' AND id != $user_id");
        if ($check && $check->num_rows > 0) {
            $errors[] = "Email is already registered";
        }
    }

    // Handle password change if requested
    if (!empty($current_password)) {
        if (!password_verify($current_password, $user['password_hash'])) {
            $errors[] = "Current password is incorrect";
        } elseif (empty($new_password)) {
            $errors[] = "New password is required";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New passwords do not match";
        }
    }

    // Update profile if no errors
    if (empty($errors)) {
        $updates = [];
        
        // Update username and email
        $updates[] = "username = '" . $db->escape($username) . "'";
        $updates[] = "email = '" . $db->escape($email) . "'";
        
        // Update password if changed
        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $updates[] = "password_hash = '" . $db->escape($password_hash) . "'";
        }
        
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = $user_id";
        
        if ($db->query($sql)) {
            // Update session data
            $_SESSION['username'] = $username;
            
            flash_message("Profile updated successfully", "success");
            redirect('/profile.php');
        } else {
            $errors[] = "Failed to update profile";
        }
    }
}

// Get user statistics
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id) as total_surveys,
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id AND status = 'published') as active_surveys,
        (SELECT COUNT(*) FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.user_id = $user_id) as total_responses,
        (SELECT DATE_FORMAT(created_at, '%M %Y') FROM users WHERE id = $user_id) as member_since
")->fetch_assoc();

require_once 'templates/header.php';
?>

<div class="profile-header bg-primary py-5 mb-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <div class="profile-avatar bg-white text-primary rounded-circle me-3 d-flex align-items-center justify-content-center">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                    <div>
                        <h1 class="text-white mb-1"><?php echo htmlspecialchars($user['username']); ?></h1>
                        <p class="text-white-50 mb-0"><i class="fas fa-calendar-alt me-2"></i>Member since <?php echo $stats['member_since']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-white text-primary px-3 py-2 rounded-pill">
                    <i class="fas fa-star me-1"></i><?php echo ucfirst($user['role']); ?> Account
                </span>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 text-primary mb-2"><?php echo $stats['total_surveys']; ?></div>
                        <p class="text-muted mb-0"><i class="fas fa-chart-bar me-2"></i>Total Surveys</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 text-success mb-2"><?php echo $stats['active_surveys']; ?></div>
                        <p class="text-muted mb-0"><i class="fas fa-check-circle me-2"></i>Active Surveys</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 text-info mb-2"><?php echo $stats['total_responses']; ?></div>
                        <p class="text-muted mb-0"><i class="fas fa-comments me-2"></i>Total Responses</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Profile Settings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profile Settings</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger shadow-sm">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h6 class="mb-3"><i class="fas fa-lock me-2"></i>Change Password</h6>
                        <p class="text-muted small mb-4">Leave blank to keep current password</p>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control" name="current_password">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="new_password" 
                                       pattern=".{8,}" title="Password must be at least 8 characters long">
                            </div>
                            <small class="text-muted">Must be at least 8 characters long</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Account Settings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Settings</h5>
                </div>
                <div class="card-body p-4">
                    <div class="d-grid gap-2">
                        <a href="dashboard/" class="btn btn-outline-primary">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                        <a href="auth/logout.php" class="btn btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="small text-muted">
                        <p class="mb-2"><i class="fas fa-shield-alt me-2"></i>Account Security</p>
                        <ul class="list-unstyled ps-4 mb-0">
                            <li class="mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>Email verified
                            </li>
                            <li>
                                <i class="fas fa-clock text-warning me-2"></i>Last login: Today
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>
