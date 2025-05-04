<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to view your profile", "warning");
    redirect('/auth/login.php');
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();
$errors = [];
$success = false;

// Get user data
$user = $db->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

// Get user statistics
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id) as total_surveys,
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id AND status = 'published') as published_surveys,
        (SELECT COUNT(DISTINCT r.id) FROM responses r 
         JOIN surveys s ON r.survey_id = s.id 
         WHERE s.user_id = $user_id) as total_responses
")->fetch_assoc();

// Get recent activity
$recent_activity = $db->query("
    (SELECT 
        'survey_created' as type,
        title as content,
        created_at as date,
        id,
        status
    FROM surveys 
    WHERE user_id = $user_id)
    UNION ALL
    (SELECT 
        'response_received' as type,
        s.title as content,
        r.submitted_at as date,
        s.id,
        s.status
    FROM responses r
    JOIN surveys s ON r.survey_id = s.id
    WHERE s.user_id = $user_id)
    ORDER BY date DESC
    LIMIT 10
");

$page_title = "Profile";
require_once '../templates/header.php';
?>

<div class="container py-4">
    <div class="row">
        <!-- Profile Overview -->
        <div class="col-md-4 mb-4">
            <div class="card profile-card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                        </div>
                    </div>
                    <h5 class="card-title mb-1"><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                    <div class="d-grid">
                        <a href="account.php" class="btn btn-outline-primary">Edit Profile</a>
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="row g-0">
                        <div class="col">
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo $stats['total_surveys']; ?></div>
                                <div class="profile-stat-label">Surveys</div>
                            </div>
                        </div>
                        <div class="col border-start">
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo $stats['published_surveys']; ?></div>
                                <div class="profile-stat-label">Published</div>
                            </div>
                        </div>
                        <div class="col border-start">
                            <div class="profile-stat-item">
                                <div class="profile-stat-value"><?php echo $stats['total_responses']; ?></div>
                                <div class="profile-stat-label">Responses</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Feed -->
        <div class="col-md-8">
            <div class="card profile-card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <?php if ($recent_activity && $recent_activity->num_rows > 0): ?>
                        <div class="activity-feed">
                            <?php while ($activity = $recent_activity->fetch_assoc()): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <?php if ($activity['type'] === 'survey_created'): ?>
                                            <i class="fas fa-file-alt text-primary"></i>
                                        <?php else: ?>
                                            <i class="fas fa-reply text-success"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <div class="activity-text">
                                            <?php if ($activity['type'] === 'survey_created'): ?>
                                                Created survey "<?php echo htmlspecialchars($activity['content']); ?>"
                                            <?php else: ?>
                                                Received a response on "<?php echo htmlspecialchars($activity['content']); ?>"
                                            <?php endif; ?>
                                        </div>
                                        <div class="activity-date">
                                            <?php 
                                            $date = new DateTime($activity['date']);
                                            echo $date->format('M j, Y g:i A'); 
                                            ?>
                                        </div>
                                    </div>
                                    <div class="activity-action">
                                        <?php if ($activity['type'] === 'survey_created'): ?>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/view-responses.php?id=<?php echo $activity['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                View Responses
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3">
                                <i class="fas fa-clock fa-3x text-muted"></i>
                            </div>
                            <p class="text-muted mb-0">No recent activity</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-avatar {
    width: 80px;
    height: 80px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 600;
    color: #495057;
    margin: 0 auto;
    transition: all 0.2s ease;
}

.profile-avatar:hover {
    transform: scale(1.05);
    background: #dee2e6;
}

.profile-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.profile-stats {
    background-color: #f8f9fa;
    padding: 1rem;
    border-top: 1px solid #e9ecef;
}

.profile-stat-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 0.5rem;
}

.profile-stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #0d6efd;
    margin-bottom: 0.25rem;
}

.profile-stat-label {
    font-size: 14px;
    color: #6c757d;
}

.activity-feed {
    padding: 0;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    transition: background-color 0.2s ease;
}

.activity-item:hover {
    background-color: #f8f9fa;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 32px;
    height: 32px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.activity-content {
    flex: 1;
    min-width: 0;
}

.activity-text {
    margin-bottom: 0.25rem;
    color: #212529;
}

.activity-date {
    font-size: 0.875rem;
    color: #6c757d;
}

.activity-action {
    margin-left: 1rem;
}

@media (max-width: 768px) {
    .activity-action {
        display: none;
    }
    
    .activity-item {
        padding: 0.75rem;
    }
}
</style>

<?php require_once '../templates/footer.php'; ?>
