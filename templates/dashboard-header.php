<?php
/**
 * Dashboard Header Component
 * 
 * Provides a consistent header for all dashboard pages
 * 
 * Usage:
 * $dashboard_title = "Page Title";
 * $dashboard_subtitle = "Optional subtitle"; // Optional
 * $dashboard_icon = "fa-chart-line"; // Optional, defaults to fa-tachometer-alt
 * require_once '../templates/dashboard-header.php';
 */

// Default values
$dashboard_title = $dashboard_title ?? 'Dashboard';
$dashboard_subtitle = $dashboard_subtitle ?? '';
$dashboard_icon = $dashboard_icon ?? 'chart-line';

// Get stats if available
$dashboard_stats = $dashboard_stats ?? null;
?>

<div class="dashboard-header animate__animated animate__fadeIn">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center">
                <div class="dashboard-icon-container me-3">
                    <i class="fas fa-<?php echo $dashboard_icon; ?> fa-2x text-primary dashboard-icon"></i>
                </div>
                <div>
                    <h1 class="mb-1 fw-bold dashboard-title"><?php echo $dashboard_title; ?></h1>
                    <?php if (!empty($dashboard_subtitle)): ?>
                        <p class="text-muted dashboard-subtitle mb-0"><?php echo $dashboard_subtitle; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6 text-md-end mt-3 mt-md-0">
            <div class="button-group">
                <?php if (isset($actions) && is_array($actions)): ?>
                    <?php foreach ($actions as $action): ?>
                        <a href="<?php echo $action['url']; ?>" class="btn <?php echo $action['class'] ?? 'btn-primary'; ?> action-btn me-2">
                            <?php if (isset($action['icon'])): ?>
                                <i class="fas fa-<?php echo $action['icon']; ?> me-2"></i>
                            <?php endif; ?>
                            <?php echo $action['text']; ?>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($dashboard_stats): ?>
    <div class="row stats-row mb-4">
        <?php foreach ($dashboard_stats as $stat): ?>
        <div class="col-md-<?php echo 12 / count($dashboard_stats); ?> mb-3 mb-md-0 animate__animated animate__fadeInUp" style="animation-delay: <?php echo 0.1 * array_search($stat, $dashboard_stats); ?>s">
            <div class="card dashboard-card stats-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted"><?php echo $stat['title']; ?></h5>
                            <h2 class="card-text counter" data-target="<?php echo $stat['value']; ?>">0</h2>
                            <p class="text-muted mb-0">
                                <?php if (isset($stat['icon'])): ?>
                                    <i class="fas fa-<?php echo $stat['icon']; ?> me-1"></i>
                                <?php endif; ?>
                                <?php echo $stat['description']; ?>
                            </p>
                        </div>
                        <?php if (isset($stat['icon'])): ?>
                        <div class="stats-icon">
                            <i class="fas fa-<?php echo $stat['icon']; ?> fa-3x text-<?php echo $stat['color'] ?? 'primary'; ?> opacity-25"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (isset($stat['link'])): ?>
                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                    <a href="<?php echo $stat['link']['url']; ?>" class="btn btn-sm btn-outline-<?php echo $stat['color'] ?? 'primary'; ?>">
                        <?php if (isset($stat['link']['icon'])): ?>
                            <i class="fas fa-<?php echo $stat['link']['icon']; ?> me-1"></i>
                        <?php endif; ?>
                        <?php echo $stat['link']['text']; ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-header {
        margin-bottom: 2rem;
    }
    
    .dashboard-icon-container {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: linear-gradient(135deg, rgba(78, 115, 223, 0.1) 0%, rgba(34, 74, 190, 0.1) 100%);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .dashboard-icon {
        animation: pulse 2s infinite;
    }
    
    .dashboard-title {
        font-size: 1.8rem;
        color: #333;
    }
    
    .dashboard-subtitle {
        font-size: 1rem;
    }
    
    .action-btn {
        border-radius: 8px;
        padding: 0.5rem 1.25rem;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Counter animation for stats
        const counters = document.querySelectorAll('.counter');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-target'));
            const duration = 2000; // ms
            const increment = Math.ceil(target / (duration / 30)); // update every ~30ms
            let current = 0;
            
            const updateCounter = () => {
                if (current < target) {
                    current += increment;
                    if (current > target) current = target;
                    counter.textContent = current;
                    requestAnimationFrame(updateCounter);
                }
            };
            
            updateCounter();
        });
    });
</script> 