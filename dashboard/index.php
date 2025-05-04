<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to access the dashboard", "warning");
    redirect('/auth/login.php');
}

// Get user's surveys
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get total surveys count
$surveys_count = $db->query("SELECT COUNT(*) as count FROM surveys WHERE user_id = $user_id");
$total_surveys = $surveys_count->fetch_assoc()['count'];

// Get total responses count
$responses_count = $db->query("
    SELECT COUNT(*) as count 
    FROM responses r 
    JOIN surveys s ON r.survey_id = s.id 
    WHERE s.user_id = $user_id
");
$total_responses = $responses_count->fetch_assoc()['count'];

// Get recent surveys
$recent_surveys = $db->query("
    SELECT id, title, status, created_at,
           (SELECT COUNT(*) FROM responses WHERE survey_id = surveys.id) as response_count
    FROM surveys 
    WHERE user_id = $user_id 
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get survey response data for chart
$survey_responses = $db->query("
    SELECT s.title, COUNT(r.id) as response_count
    FROM surveys s
    LEFT JOIN responses r ON s.id = r.survey_id
    WHERE s.user_id = $user_id
    GROUP BY s.id, s.title
    ORDER BY response_count DESC
    LIMIT 5
");

// Get survey status counts
$survey_status = $db->query("
    SELECT status, COUNT(*) as count
    FROM surveys
    WHERE user_id = $user_id
    GROUP BY status
");

// Prepare data for charts
$survey_titles = [];
$response_counts = [];

if ($survey_responses && $survey_responses->num_rows > 0) {
    while ($response = $survey_responses->fetch_assoc()) {
        $survey_titles[] = $response['title'];
        $response_counts[] = $response['response_count'];
    }
}

// Prepare survey status data with counts in labels
$status_labels = [];
$status_counts = [0, 0, 0];
$status_colors = [
    'rgba(255, 206, 86, 0.7)', // yellow for draft
    'rgba(75, 192, 192, 0.7)',  // green for published
    'rgba(255, 99, 132, 0.7)'   // red for closed
];
$status_borders = [
    'rgba(255, 206, 86, 1)',
    'rgba(75, 192, 192, 1)',
    'rgba(255, 99, 132, 1)'
];

if ($survey_status && $survey_status->num_rows > 0) {
    while ($status = $survey_status->fetch_assoc()) {
        if ($status['status'] == 'draft') {
            $status_counts[0] = $status['count'];
        } else if ($status['status'] == 'published') {
            $status_counts[1] = $status['count'];
        } else if ($status['status'] == 'closed') {
            $status_counts[2] = $status['count'];
        }
    }
}

// Create labels with counts
$status_labels = [
    'Draft (' . $status_counts[0] . ')',
    'Published (' . $status_counts[1] . ')',
    'Closed (' . $status_counts[2] . ')'
];

$page_title = 'Dashboard';
require_once '../templates/header.php';
?>

<div class="dashboard-hero mb-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-6 animate__animated animate__fadeInLeft">
                <h1 class="display-5 fw-bold text-white mb-2">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h1>
                <p class="lead text-white-50">Manage your surveys and analyze responses from one place.</p>
                <div class="mt-4">
                    <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-light btn-lg me-2 animate__animated animate__pulse animate__infinite animate__slower">
                        <i class="fas fa-plus-circle me-2"></i> Create New Survey
                    </a>
                    <a href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-list me-2"></i> View All Surveys
                    </a>
                </div>
            </div>
            <div class="col-lg-6 d-none d-lg-block animate__animated animate__fadeInRight">
                <img src="<?php echo SITE_URL; ?>/assets/img/logo.svg" alt="SurveyPro Logo" class="img-fluid dashboard-hero-image">
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="container mb-5">
    <div class="row">
        <div class="col-md-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
            <div class="card dashboard-card stats-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted">Total Surveys</h5>
                            <h2 class="card-text counter" data-target="<?php echo $total_surveys; ?>">0</h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-file-alt me-1"></i> Surveys in your account
                            </p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-clipboard-list fa-3x text-primary opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                    <a href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i> View All
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
            <div class="card dashboard-card stats-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted">Total Responses</h5>
                            <h2 class="card-text counter" data-target="<?php echo $total_responses; ?>">0</h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-chart-bar me-1"></i> Responses collected
                            </p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-comments fa-3x text-success opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                    <?php if ($total_surveys > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/dashboard/analytics.php?id=0" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-chart-line me-1"></i> View Analytics
                        </a>
                    <?php else: ?>
                        <span class="text-muted">No survey data available</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4 animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
            <div class="card dashboard-card stats-card h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="card-title text-muted">Response Rate</h5>
                            <h2 class="card-text">
                                <?php 
                                echo $total_surveys > 0 
                                    ? round(($total_responses / $total_surveys), 1) 
                                    : 0; 
                                ?>
                            </h2>
                            <p class="text-muted mb-0">
                                <i class="fas fa-percentage me-1"></i> Avg. responses per survey
                            </p>
                        </div>
                        <div class="stats-icon">
                            <i class="fas fa-chart-pie fa-3x text-info opacity-25"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 p-4 pt-0">
                    <a href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-eye me-1"></i> View Surveys
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mb-4">
    <div class="row">
        <div class="col-lg-8">
            <!-- Recent Surveys -->
            <div class="card dashboard-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
                <div class="card-header bg-white d-flex justify-content-between align-items-center p-4">
                    <h5 class="mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Surveys</h5>
                    <a href="<?php echo SITE_URL; ?>/dashboard/view-surveys.php" class="btn btn-sm btn-primary rounded-pill px-3">
                        <i class="fas fa-list me-1"></i> View All
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_surveys && $recent_surveys->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover survey-table">
                                <thead>
                                    <tr class="table-light">
                                        <th class="ps-4">Title</th>
                                        <th>Status</th>
                                        <th>Responses</th>
                                        <th>Created</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($survey = $recent_surveys->fetch_assoc()): ?>
                                        <tr class="survey-row animate__animated animate__fadeIn">
                                            <td class="ps-4">
                                                <div class="survey-title text-truncate" style="max-width: 250px;">
                                                    <?php echo htmlspecialchars($survey['title']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?php 
                                                    echo $survey['status'] === 'published' ? 'success' : 
                                                        ($survey['status'] === 'draft' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <i class="fas fa-<?php 
                                                        echo $survey['status'] === 'published' ? 'check-circle' : 
                                                            ($survey['status'] === 'draft' ? 'edit' : 'lock'); 
                                                    ?> me-1"></i>
                                                    <?php echo ucfirst($survey['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="response-count-badge" data-count="<?php echo $survey['response_count']; ?>"><?php echo $survey['response_count']; ?></div>
                                                </div>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($survey['created_at'])); ?></td>
                                            <td class="text-end pe-4">
                                                <div class="action-buttons">
                                                    <a href="<?php echo SITE_URL; ?>/dashboard/edit-survey.php?id=<?php echo $survey['id']; ?>" 
                                                       class="btn btn-sm btn-icon btn-outline-primary" 
                                                       title="Edit Survey"
                                                       data-survey-id="<?php echo $survey['id']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/dashboard/view-responses.php?id=<?php echo $survey['id']; ?>" 
                                                       class="btn btn-sm btn-icon btn-outline-info" 
                                                       title="View Responses">
                                                        <i class="fas fa-chart-bar"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/dashboard/share-survey.php?id=<?php echo $survey['id']; ?>" 
                                                       class="btn btn-sm btn-icon btn-outline-success" 
                                                       title="Share Survey">
                                                        <i class="fas fa-share-alt"></i>
                                                    </a>
                                                    <a href="<?php echo SITE_URL; ?>/dashboard/delete-survey.php?id=<?php echo $survey['id']; ?>" 
                                                       class="btn btn-sm btn-icon btn-outline-danger" 
                                                       title="Delete Survey"
                                                       onclick="return confirm('Are you sure you want to delete this survey?');">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="empty-state-icon mb-3">
                                <i class="fas fa-clipboard fa-4x text-muted"></i>
                            </div>
                            <h5 class="text-muted">No surveys created yet</h5>
                            <p class="text-muted mb-4">Get started by creating your first survey</p>
                            <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-primary px-4 py-2">
                                <i class="fas fa-plus me-2"></i> Create Your First Survey
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card dashboard-card animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                <div class="card-header bg-white p-4">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
                </div>
                <div class="card-body p-2">
                    <div class="list-group list-group-flush">
                        <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="list-group-item list-group-item-action p-3 d-flex align-items-center border-0 quick-action-item">
                            <div class="action-icon me-3 bg-primary">
                                <i class="fas fa-plus text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Create New Survey</h6>
                                <small class="text-muted">Build a custom survey from scratch</small>
                            </div>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/dashboard/templates.php" class="list-group-item list-group-item-action p-3 d-flex align-items-center border-0 quick-action-item">
                            <div class="action-icon me-3 bg-info">
                                <i class="fas fa-copy text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Use Template</h6>
                                <small class="text-muted">Start with a pre-built template</small>
                            </div>
                        </a>
                        <a href="<?php echo SITE_URL; ?>/dashboard/analytics.php?id=0" class="list-group-item list-group-item-action p-3 d-flex align-items-center border-0 quick-action-item">
                            <div class="action-icon me-3 bg-success">
                                <i class="fas fa-chart-line text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Analytics Dashboard</h6>
                                <small class="text-muted">View comprehensive statistics</small>
                            </div>
                        </a>
                        <a href="../profile.php" class="list-group-item list-group-item-action p-3 d-flex align-items-center border-0 quick-action-item">
                            <div class="action-icon me-3 bg-warning">
                                <i class="fas fa-user-cog text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0">Manage Profile</h6>
                                <small class="text-muted">Update your account details</small>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-hero {
    background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
    margin-top: -1.5rem;
    padding: 2rem 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.dashboard-hero:before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

.dashboard-hero-image {
    max-height: 180px;
    opacity: 0.9;
}

/* Response count badge */
.response-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 28px;
    height: 28px;
    padding: 0 8px;
    font-size: 14px;
    font-weight: bold;
    color: white;
    background-color: #4e73df;
    border-radius: 14px;
}

.survey-row {
    transition: all 0.2s ease;
}

.survey-row:hover {
    background-color: rgba(78, 115, 223, 0.05);
}

.survey-title {
    font-weight: 500;
}

/* Quick action buttons */
.action-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 8px;
    flex-shrink: 0;
}

.quick-action-item {
    transition: all 0.3s ease;
    border-radius: 10px;
    margin-bottom: 5px;
}

.quick-action-item:hover {
    transform: translateX(5px);
    background-color: rgba(78, 115, 223, 0.05);
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 5px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    transform: translateY(-2px);
}

/* Empty state */
.empty-state-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(78, 115, 223, 0.1);
}

.stats-icon {
    position: absolute;
    right: 15px;
    top: 15px;
    opacity: 0.2;
}
</style>

<?php require_once '../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Counter animation
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
