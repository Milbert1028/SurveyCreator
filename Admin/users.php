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

// Initialize database connection
$db = Database::getInstance();

// Process action if any
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $user_id = intval($_GET['id']);
    
    // Verify CSRF token
    if (!verify_csrf_token($_GET['token'] ?? '')) {
        flash_message("Invalid request", "danger");
        redirect('/Admin/users.php');
        exit();
    }
    
    // Check if user exists and is not current user
    $user_query = $db->query("SELECT username FROM users WHERE id = $user_id");
    if (!$user_query || $user_query->num_rows == 0) {
        flash_message("User not found", "danger");
        redirect('/Admin/users.php');
        exit();
    }
    
    $user = $user_query->fetch_assoc();
    
    // Prevent users from modifying their own account status
    if ($user_id == $_SESSION['user_id'] && in_array($action, ['toggle_active', 'delete'])) {
        flash_message("You cannot modify your own account status", "danger");
        redirect('/Admin/users.php');
        exit();
    }
    
    switch ($action) {
        case 'toggle_active':
            // Get current status
            $status_query = $db->query("SELECT active FROM users WHERE id = $user_id");
            if ($status_query && $status_query->num_rows > 0) {
                $status = $status_query->fetch_assoc()['active'];
                $new_status = $status ? 0 : 1;
                
                $result = $db->query("UPDATE users SET active = $new_status WHERE id = $user_id");
                if ($result) {
                    $status_text = $new_status ? "activated" : "deactivated";
                    $logger->log('update', 'users', "User {$user['username']} $status_text", ['user_id' => $user_id]);
                    flash_message("User {$user['username']} has been $status_text", "success");
                } else {
                    flash_message("Failed to update user status: " . $db->getLastError(), "danger");
                }
            }
            break;
            
        case 'delete':
            // Delete user
            $result = $db->query("DELETE FROM users WHERE id = $user_id");
            if ($result) {
                $logger->log('delete', 'users', "User {$user['username']} deleted", ['user_id' => $user_id]);
                flash_message("User {$user['username']} has been deleted", "success");
            } else {
                flash_message("Failed to delete user: " . $db->getLastError(), "danger");
            }
            break;
    }
    
    redirect('/Admin/users.php');
    exit();
}

// Set up pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$page = max(1, $page); // Ensure page is at least 1
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$where_clause = '';
if (!empty($search)) {
    $escaped_search = $db->escape($search);
    $where_clause = "WHERE username LIKE '%$escaped_search%' OR email LIKE '%$escaped_search%'";
}

// Get total users count for pagination
$count_query = $db->query("SELECT COUNT(*) as total FROM users $where_clause");
$total_users = $count_query ? $count_query->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_users / $limit);

// Get users for current page
$users_query = $db->query("
    SELECT id, username, email, role, email_verified, active, created_at, last_login 
    FROM users 
    $where_clause
    ORDER BY username ASC
    LIMIT $offset, $limit
");

$page_title = "User Management";
require_once 'includes/header.php';
?>

<main>
    <div class="admin-header">
        <h1><i class="fas fa-users me-2"></i> User Management</h1>
        <a href="edit_user.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-1"></i> Add New User
        </a>
    </div>
    
    <div class="data-card">
        <div class="card-header">
            <h2 class="mb-0">Users</h2>
            
            <form action="" method="GET" class="search-form">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search username or email" 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                    <a href="users.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <div class="card-body">
            <?php if ($users_query && $users_query->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verified</th>
                            <th>Created</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_query->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php echo htmlspecialchars($user['username']); ?>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-info ms-1">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['email_verified'] ? 'success' : 'warning'; ?>">
                                    <?php echo $user['email_verified'] ? 'Verified' : 'Unverified'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <?php echo $user['last_login'] ? date('M j, Y g:i a', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td class="text-nowrap">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary" 
                                   title="Edit User">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <a href="users.php?action=toggle_active&id=<?php echo $user['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                   class="btn btn-sm btn-<?php echo $user['active'] ? 'warning' : 'success'; ?>"
                                   title="<?php echo $user['active'] ? 'Deactivate' : 'Activate'; ?> User"
                                   onclick="return confirm('Are you sure you want to <?php echo $user['active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                    <i class="fas fa-<?php echo $user['active'] ? 'ban' : 'check'; ?>"></i>
                                </a>
                                
                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>&token=<?php echo generate_csrf_token(); ?>" 
                                   class="btn btn-sm btn-danger"
                                   title="Delete User"
                                   onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination with Bootstrap 5 styling -->
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php echo empty($search) ? 'No users found in the system.' : 'No users match your search criteria.'; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>