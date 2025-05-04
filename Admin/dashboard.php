<?php
// Start session and include required files
session_start();

// Check if user is logged in and is an admin
require_once '../includes/functions.php';
require_once '../includes/config.php';
require_once '../includes/db.php';

if (!is_logged_in() || !is_admin()) {
    flash_message("You must be logged in as an admin to access this page", "danger");
    redirect('/Admin/login.php');
    exit();
}

// Get database instance
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get user statistics
$stats_query = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM users) as total_users,
        (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_users,
        (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
        (SELECT COUNT(*) FROM surveys) as total_surveys,
        (SELECT COUNT(*) FROM surveys WHERE status = 'published') as active_surveys,
        (SELECT COUNT(*) FROM responses) as total_responses,
        (SELECT COUNT(*) FROM responses WHERE DATE(submitted_at) = CURDATE()) as responses_today
    FROM dual
");

// Set default values in case of query failure
$stats = [
    'total_users' => 0,
    'admin_users' => 0,
    'new_users_today' => 0,
    'total_surveys' => 0,
    'active_surveys' => 0,
    'total_responses' => 0,
    'responses_today' => 0
];

// Get actual values if query was successful
if ($stats_query && $stats_query->num_rows > 0) {
    $stats = $stats_query->fetch_assoc();
}

// Get latest users
$latest_users_query = $db->query("
    SELECT id, username, email, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");

// Get latest responses
$latest_responses_query = $db->query("
    SELECT r.id, r.submitted_at, s.title as survey_title, u.username
    FROM responses r
    JOIN surveys s ON r.survey_id = s.id
    LEFT JOIN users u ON r.user_id = u.id
    GROUP BY r.id
    ORDER BY r.submitted_at DESC
    LIMIT 5
");

// Get popular surveys
$popular_surveys_query = $db->query("
    SELECT s.id, s.title, COUNT(r.id) as response_count, u.username as creator
    FROM surveys s
    LEFT JOIN responses r ON s.survey_id = r.survey_id
    JOIN users u ON s.user_id = u.id
    GROUP BY s.id
    ORDER BY response_count DESC
    LIMIT 5
");

$page_title = "Admin Dashboard";
include '../includes/header.php';
?>

<div class="admin-container">
    <!-- Admin Navigation Sidebar -->
    <nav class="admin-sidebar">
        <div class="sidebar-header">
            <h3>Admin Panel</h3>
            <div class="admin-user">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
            </div>
        </div>
        
        <ul class="sidebar-menu">
            <li class="active">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li>
                <a href="users.php">
                    <i class="fas fa-users"></i> Users
                </a>
            </li>
            <li>
                <a href="surveys.php">
                    <i class="fas fa-poll"></i> Surveys
                </a>
            </li>
            <li>
                <a href="Adminanalytics.php">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
            </li>
            <li>
                <a href="settings.php">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            </li>
            <li>
                <a href="../index.php">
                    <i class="fas fa-home"></i> Back to Site
                </a>
            </li>
            <li>
                <a href="../auth/logout.php" class="text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <main class="admin-content">
        <div class="admin-header">
            <h1><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h1>
            <div class="admin-actions">
                <span class="date-display"><?php echo date('F j, Y'); ?></span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card bg-primary">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($stats['total_users']); ?></h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-footer">
                    <span>+<?php echo $stats['new_users_today']; ?> today</span>
                </div>
            </div>
            
            <div class="stat-card bg-success">
                <div class="stat-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($stats['total_surveys']); ?></h3>
                    <p>Total Surveys</p>
                </div>
                <div class="stat-footer">
                    <span><?php echo $stats['active_surveys']; ?> active</span>
                </div>
            </div>
            
            <div class="stat-card bg-info">
                <div class="stat-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo number_format($stats['total_responses']); ?></h3>
                    <p>Total Responses</p>
                </div>
                <div class="stat-footer">
                    <span>+<?php echo $stats['responses_today']; ?> today</span>
                </div>
            </div>
            
            <div class="stat-card bg-warning">
                <div class="stat-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-details">
                    <h3><?php echo $stats['admin_users']; ?></h3>
                    <p>Admin Users</p>
                </div>
                <div class="stat-footer">
                    <span>of <?php echo $stats['total_users']; ?> total users</span>
                </div>
            </div>
        </div>

        <!-- Latest Data -->
        <div class="data-grid">
            <!-- Latest Users -->
            <div class="data-card">
                <div class="card-header">
                    <h2><i class="fas fa-users me-2"></i> Latest Users</h2>
                    <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($latest_users_query && $latest_users_query->num_rows > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $latest_users_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No users found.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Latest Responses -->
            <div class="data-card">
                <div class="card-header">
                    <h2><i class="fas fa-comment-dots me-2"></i> Latest Responses</h2>
                    <a href="surveys.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($latest_responses_query && $latest_responses_query->num_rows > 0): ?>
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Survey</th>
                                    <th>Respondent</th>
                                    <th>Submitted</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($response = $latest_responses_query->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($response['survey_title']); ?></td>
                                        <td><?php echo $response['username'] ? htmlspecialchars($response['username']) : 'Anonymous'; ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($response['submitted_at'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info">No responses found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <h2><i class="fas fa-bolt me-2"></i> Quick Actions</h2>
            <div class="actions-grid">
                <a href="users.php?action=create" class="action-card">
                    <i class="fas fa-user-plus"></i>
                    <span>Add User</span>
                </a>
                <a href="surveys.php" class="action-card">
                    <i class="fas fa-search"></i>
                    <span>View Surveys</span>
                </a>
                <a href="Adminanalytics.php" class="action-card">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
                <a href="settings.php" class="action-card">
                    <i class="fas fa-cogs"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>
    </main>
</div>

<style>
    /* Admin Dashboard Styles */
    .admin-container {
        display: flex;
        min-height: 100vh;
        background-color: #f8f9fa;
    }
    
    /* Sidebar */
    .admin-sidebar {
        width: 250px;
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        color: white;
        position: fixed;
        height: 100vh;
        overflow-y: auto;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        z-index: 100;
    }
    
    .sidebar-header {
        padding: 1.5rem 1rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .admin-user {
        display: flex;
        align-items: center;
        margin-top: 0.5rem;
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.8);
    }
    
    .admin-user i {
        margin-right: 0.5rem;
    }
    
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .sidebar-menu li {
        margin: 0;
    }
    
    .sidebar-menu li a {
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .sidebar-menu li a:hover {
        background-color: rgba(255, 255, 255, 0.1);
        color: white;
    }
    
    .sidebar-menu li.active a {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        font-weight: bold;
    }
    
    .sidebar-menu li a i {
        margin-right: 0.75rem;
        width: 20px;
        text-align: center;
    }
    
    /* Main Content */
    .admin-content {
        flex: 1;
        padding: 1.5rem;
        margin-left: 250px;
    }
    
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }
    
    .admin-header h1 {
        font-size: 1.8rem;
        font-weight: 500;
        color: #333;
        margin: 0;
    }
    
    .date-display {
        font-size: 1rem;
        color: #6c757d;
    }
    
    /* Stats Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .stat-card {
        background-color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        position: relative;
        overflow: hidden;
        color: white;
    }
    
    .bg-primary {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    }
    
    .bg-success {
        background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    }
    
    .bg-info {
        background: linear-gradient(135deg, #36b9cc 0%, #258391 100%);
    }
    
    .bg-warning {
        background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);
    }
    
    .stat-icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 2rem;
        opacity: 0.3;
    }
    
    .stat-details h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }
    
    .stat-details p {
        margin: 0;
        font-size: 0.9rem;
        opacity: 0.8;
    }
    
    .stat-footer {
        margin-top: auto;
        padding-top: 1rem;
        font-size: 0.8rem;
        opacity: 0.8;
    }
    
    /* Data Cards */
    .data-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    
    .data-card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        overflow: hidden;
    }
    
    .card-header {
        padding: 1rem 1.5rem;
        background-color: white;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e3e6f0;
    }
    
    .card-header h2 {
        font-size: 1.2rem;
        font-weight: 500;
        margin: 0;
        color: #333;
    }
    
    .card-body {
        padding: 1.5rem;
    }
    
    /* Quick Actions */
    .quick-actions {
        margin-bottom: 2rem;
    }
    
    .quick-actions h2 {
        font-size: 1.2rem;
        font-weight: 500;
        margin-bottom: 1rem;
        color: #333;
    }
    
    .actions-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 1rem;
    }
    
    .action-card {
        background-color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        text-align: center;
        text-decoration: none;
        color: #4e73df;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        transition: all 0.3s;
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    
    .action-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        color: #224abe;
    }
    
    .action-card i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .admin-sidebar {
            width: 0;
            display: none;
        }
        
        .admin-content {
            margin-left: 0;
        }
        
        .stats-grid, .data-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>