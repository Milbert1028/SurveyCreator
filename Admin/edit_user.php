<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !is_admin()) {
    flash_message("You must be logged in as an admin to access this page", "danger");
    redirect('/Admin/login.php');
    exit();
}

// Activity logger
require_once '../includes/activity_logger.php';
$logger = new ActivityLogger($_SESSION['user_id']);

// Check if user ID is provided
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_new = $user_id === 0;

// Initialize database connection
$db = Database::getInstance();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        flash_message("Invalid request", "danger");
        redirect('/Admin/users.php');
        exit();
    }
    
    // Collect form data
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $role = sanitize_input($_POST['role'] ?? 'user');
    $active = isset($_POST['active']) ? 1 : 0;
    $verified = isset($_POST['verified']) ? 1 : 0;
    
    // Password is only required for new users
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    // Validate inputs
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if ($is_new || !empty($password)) {
        if (empty($password)) {
            $errors[] = "Password is required for new users";
        } elseif (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        } elseif ($password !== $password_confirm) {
            $errors[] = "Passwords do not match";
        }
    }
    
    // Check if username or email already exists
    if (empty($errors)) {
        // For username check, exclude current user when editing
        $username_check_sql = "SELECT id FROM users WHERE username = '" . $db->escape($username) . "'";
        if (!$is_new) {
            $username_check_sql .= " AND id != $user_id";
        }
        
        $username_check = $db->query($username_check_sql);
        if ($username_check && $username_check->num_rows > 0) {
            $errors[] = "Username is already taken";
        }
        
        // For email check, exclude current user when editing
        $email_check_sql = "SELECT id FROM users WHERE email = '" . $db->escape($email) . "'";
        if (!$is_new) {
            $email_check_sql .= " AND id != $user_id";
        }
        
        $email_check = $db->query($email_check_sql);
        if ($email_check && $email_check->num_rows > 0) {
            $errors[] = "Email is already registered";
        }
    }
    
    // Save user if no errors
    if (empty($errors)) {
        if ($is_new) {
            // Create new user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, email, password_hash, role, email_verified, active) 
                    VALUES ('" . $db->escape($username) . "', '" . $db->escape($email) . "', 
                           '" . $db->escape($password_hash) . "', '" . $db->escape($role) . "', $verified, $active)";
            
            $result = $db->query($sql);
            if ($result) {
                $user_id = $db->getLastId();
                $logger->log('create', 'users', "Created user: $username", ['user_id' => $user_id]);
                flash_message("User created successfully", "success");
                redirect('/Admin/users.php');
                exit();
            } else {
                $errors[] = "Failed to create user: " . $db->getLastError();
            }
        } else {
            // Update existing user
            $sql_parts = [
                "username = '" . $db->escape($username) . "'",
                "email = '" . $db->escape($email) . "'",
                "role = '" . $db->escape($role) . "'",
                "email_verified = $verified",
                "active = $active"
            ];
            
            // Add password update if provided
            if (!empty($password)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql_parts[] = "password_hash = '" . $db->escape($password_hash) . "'";
            }
            
            $sql = "UPDATE users SET " . implode(", ", $sql_parts) . " WHERE id = $user_id";
            
            $result = $db->query($sql);
            if ($result) {
                $logger->log('update', 'users', "Updated user: $username", ['user_id' => $user_id]);
                flash_message("User updated successfully", "success");
                redirect('/Admin/users.php');
                exit();
            } else {
                $errors[] = "Failed to update user: " . $db->getLastError();
            }
        }
    }
}

// Get user data if editing
$user = null;
if (!$is_new) {
    $query = $db->query("SELECT * FROM users WHERE id = $user_id");
    if ($query && $query->num_rows > 0) {
        $user = $query->fetch_assoc();
    } else {
        flash_message("User not found", "danger");
        redirect('/Admin/users.php');
        exit();
    }
}

$page_title = $is_new ? "Create User" : "Edit User";
require_once '../includes/header.php';
?>

<div class="admin-container">
    <!-- Include sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="admin-content">
        <div class="admin-header">
            <h1><?php echo $is_new ? '<i class="fas fa-user-plus me-2"></i> Create User' : '<i class="fas fa-user-edit me-2"></i> Edit User'; ?></h1>
            <a href="users.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h5><i class="fas fa-exclamation-triangle me-2"></i> Please fix the following errors:</h5>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="data-card">
            <div class="card-header">
                <h2><?php echo $is_new ? 'User Information' : 'Edit ' . htmlspecialchars($user['username']); ?></h2>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username'] ?? $_POST['username'] ?? ''); ?>" required>
                                <small class="text-muted">3-20 characters, letters, numbers, and underscores only</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email'] ?? $_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="password" class="form-label"><?php echo $is_new ? 'Password' : 'New Password (leave blank to keep current)'; ?></label>
                                <input type="password" class="form-control" id="password" name="password" 
                                       <?php echo $is_new ? 'required' : ''; ?>>
                                <small class="text-muted">Minimum 8 characters</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="password_confirm" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="password_confirm" name="password_confirm" 
                                       <?php echo $is_new ? 'required' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-control" id="role" name="role">
                                    <option value="user" <?php echo (isset($user['role']) && $user['role'] === 'user') || (!isset($user['role']) && (!isset($_POST['role']) || $_POST['role'] === 'user')) ? 'selected' : ''; ?>>User</option>
                                    <option value="admin" <?php echo (isset($user['role']) && $user['role'] === 'admin') || (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="verified" name="verified" 
                                       <?php echo (isset($user['email_verified']) && $user['email_verified'] == 1) || (isset($_POST['verified'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="verified">
                                    Email Verified
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" id="active" name="active" 
                                       <?php echo (!isset($user['active']) || $user['active'] == 1) || (isset($_POST['active'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="active">
                                    Active
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas <?php echo $is_new ? 'fa-user-plus' : 'fa-save'; ?> me-1"></i>
                            <?php echo $is_new ? 'Create User' : 'Update User'; ?>
                        </button>
                        <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <?php if (!$is_new): ?>
        <div class="data-card mt-4">
            <div class="card-header">
                <h2><i class="fas fa-history me-2"></i> User Activity</h2>
            </div>
            <div class="card-body">
                <?php
                // Get user activity
                $activity_query = $db->query("
                    SELECT * FROM activity_logs 
                    WHERE user_id = $user_id 
                    ORDER BY created_at DESC
                    LIMIT 10
                ");
                
                if ($activity_query && $activity_query->num_rows > 0):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Description</th>
                                <th>Date/Time</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($log = $activity_query->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-<?php echo $log['action'] === 'create' ? 'success' : ($log['action'] === 'update' ? 'primary' : ($log['action'] === 'delete' ? 'danger' : 'info')); ?>">
                                        <?php echo ucfirst($log['action']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($log['module']); ?></td>
                                <td><?php echo htmlspecialchars($log['description']); ?></td>
                                <td><?php echo date('M j, Y, g:i a', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No activity records found for this user.
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once '../includes/footer.php'; ?> 