<?php 
require_once __DIR__ . '/../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo SITE_URL; ?>/">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    <link rel="shortcut icon" href="<?php echo SITE_URL; ?>/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Animate.css -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    
    <!-- Sortable.js -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- Base CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Enhanced UI CSS -->
    <link href="<?php echo SITE_URL; ?>/assets/css/enhanced-ui.css" rel="stylesheet">
    
    <!-- Dashboard Layout Fixes -->
    <link href="<?php echo SITE_URL; ?>/assets/css/dashboard-fix.css" rel="stylesheet"><?php // Fixes spacing issues in dashboard ?>
    
    <!-- Custom CSS -->
    <style>
        .question-card {
            cursor: move;
        }
        .question-card .card-body {
            user-select: none;
        }
        .sortable-ghost {
            opacity: 0.4;
        }
        
        /* Profile Page Styles */
        .profile-header {
            margin-top: -1.5rem;
            margin-bottom: 2rem;
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        
        .card {
            transition: transform 0.2s ease-in-out;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .display-4 {
            font-size: 2.5rem;
            font-weight: 600;
        }
        
        /* Avatar Circle */
        .avatar-circle {
            width: 30px;
            height: 30px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .avatar-initials {
            font-size: 14px;
        }
        
        /* Nav buttons */
        .nav-btn {
            border-radius: 20px;
            transition: all 0.3s ease;
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
        }
        
        /* Logo animation */
        .logo-rotate {
            animation: pulse 3s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        /* Dashboard Cards */
        .dashboard-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }
        
        .stats-card {
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }
        
        .stats-card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/">
                <img src="<?php echo SITE_URL; ?>/assets/img/logo-icon.svg" width="36" height="36" class="me-2 logo-rotate" alt="SurveyCreator Logo">
                <span class="fw-bold text-primary">Survey<span class="text-dark">Creator</span></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/dashboard">
                                <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/dashboard/create-survey.php">
                                <i class="fas fa-plus-circle me-1"></i> Create Survey
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php">
                                <i class="fas fa-list me-1"></i> My Surveys
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="avatar-circle me-2">
                                    <span class="avatar-initials"><?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?></span>
                                </div>
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end animate__animated animate__fadeIn">
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                    <i class="fas fa-user me-2 text-primary"></i> Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/dashboard/analytics.php?id=0">
                                    <i class="fas fa-chart-line me-2"></i> Analytics
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2 text-danger"></i> Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm me-2 px-3 nav-btn" href="<?php echo SITE_URL; ?>/auth/login.php">
                                <i class="fas fa-sign-in-alt me-1"></i> Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-light text-primary btn-sm px-3 nav-btn" href="<?php echo SITE_URL; ?>/auth/register.php">
                                <i class="fas fa-user-plus me-1"></i> Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4 content-wrapper">
        <?php
        $flash = get_flash_message();
        if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

<script>
// Fix relative URLs in production
document.addEventListener('DOMContentLoaded', function() {
    const siteUrl = '<?php echo SITE_URL; ?>';
    
    // Only run this fix in production
    if (!siteUrl.includes('localhost')) {
        console.log('Applying URL fixes for production');
        
        // Fix form actions that use relative paths
        document.querySelectorAll('form').forEach(form => {
            const action = form.getAttribute('action');
            if (action && !action.startsWith('http') && !action.startsWith('<?php echo SITE_URL; ?>')) {
                // Skip empty actions or those with PHP_SELF
                if (action !== '' && !action.includes('PHP_SELF')) {
                    // Remove leading slash if present
                    const cleanPath = action.startsWith('/') ? action.substring(1) : action;
                    form.setAttribute('action', `${siteUrl}/${cleanPath}`);
                    console.log('Fixed form action:', action, '->', `${siteUrl}/${cleanPath}`);
                }
            }
        });
        
        // Fix API fetch calls
        const originalFetch = window.fetch;
        window.fetch = function(url, options) {
            if (typeof url === 'string' && !url.startsWith('http') && !url.startsWith('<?php echo SITE_URL; ?>')) {
                if (url.startsWith('../') || url.startsWith('./')) {
                    // Handle relative paths by converting to absolute
                    const cleanPath = url.replace(/^\.\.\//, '').replace(/^\.\//, '');
                    url = `${siteUrl}/${cleanPath}`;
                    console.log('Fixed fetch URL:', url);
                } else if (!url.startsWith('/')) {
                    // Add leading slash if needed
                    url = `${siteUrl}/${url}`;
                } else {
                    // URL starts with slash
                    url = `${siteUrl}${url}`;
                }
            }
            return originalFetch.call(this, url, options);
        };
    }
});
</script>
