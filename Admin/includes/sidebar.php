<?php
// Get the current page URL to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <h2>Admin Panel</h2>
        <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>" class="back-to-site" title="Back to Website">
            <i class="fas fa-external-link-alt"></i>
        </a>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
            <span class="user-role"><?php echo isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Administrator'; ?></span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <li class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'surveys.php' ? 'active' : ''; ?>">
                <a href="surveys.php">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Surveys</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'questions.php' ? 'active' : ''; ?>">
                <a href="questions.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Questions</span>
                </a>
            </li>
            
            <li class="<?php echo in_array($current_page, ['users.php', 'edit_user.php']) ? 'active' : ''; ?>">
                <a href="users.php">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'responses.php' ? 'active' : ''; ?>">
                <a href="responses.php">
                    <i class="fas fa-reply-all"></i>
                    <span>Responses</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'analytics.php' ? 'active' : ''; ?>">
                <a href="analytics.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'settings.php' ? 'active' : ''; ?>">
                <a href="settings.php">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            
            <li class="<?php echo $current_page === 'activity_logs.php' ? 'active' : ''; ?>">
                <a href="activity_logs.php">
                    <i class="fas fa-history"></i>
                    <span>Activity Logs</span>
                </a>
            </li>
        </ul>
    </nav>
    
    <div class="sidebar-footer">
        <a href="profile.php" title="Profile">
            <i class="fas fa-user-cog"></i>
        </a>
        <a href="../logout.php" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- Mobile Toggle Button -->
<div class="mobile-toggle">
    <button id="sidebarToggle" class="btn btn-primary">
        <i class="fas fa-bars"></i>
    </button>
</div>

<script>
    // Toggle sidebar on mobile
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.querySelector('.admin-sidebar').classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.admin-sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (sidebar.classList.contains('active') && 
            !sidebar.contains(event.target) && 
            !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('active');
        }
    });
</script> 