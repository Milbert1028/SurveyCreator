<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo APP_NAME; ?> Admin</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Admin CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/admin.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-brand">
                <a href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                    <i class="fas fa-poll me-2"></i>
                    <span><?php echo APP_NAME; ?></span>
                </a>
                <button class="sidebar-toggle d-md-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            
            <div class="sidebar-divider"></div>
            
            <ul class="sidebar-nav">
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="sidebar-link">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/users.php" class="sidebar-link">
                        <i class="fas fa-users me-2"></i>
                        <span>Users</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/surveys.php" class="sidebar-link">
                        <i class="fas fa-clipboard-list me-2"></i>
                        <span>Surveys</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/templates.php" class="sidebar-link">
                        <i class="fas fa-file-alt me-2"></i>
                        <span>Templates</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/analytics.php" class="sidebar-link">
                        <i class="fas fa-chart-bar me-2"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/settings.php" class="sidebar-link">
                        <i class="fas fa-cog me-2"></i>
                        <span>Settings</span>
                    </a>
                </li>
                
                <li class="sidebar-item">
                    <a href="<?php echo SITE_URL; ?>/admin/email-check.php" class="sidebar-link">
                        <i class="fas fa-envelope me-2"></i>
                        <span>Email Check</span>
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-divider"></div>
            
            <div class="sidebar-footer">
                <a href="<?php echo SITE_URL; ?>/" class="sidebar-link" title="Back to main site">
                    <i class="fas fa-home me-2"></i>
                    <span>Main Site</span>
                </a>
                <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="sidebar-link" title="Logout">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-content">
            <!-- Top Navbar -->
            <nav class="admin-navbar navbar navbar-expand">
                <button class="navbar-toggler d-md-none" type="button" id="mobileToggle">
                    <i class="fas fa-bars"></i>
                </button>
                
                <div class="navbar-collapse">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <span>
                                    <?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Admin'; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/profile.php">
                                        <i class="fas fa-user me-2"></i> Profile
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/settings.php">
                                        <i class="fas fa-cog me-2"></i> Settings
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main Content Area -->
            <main class="admin-main">
                <!-- Content is inserted here -->
            </main>
        </div>
    </div>
</body>
</html> 