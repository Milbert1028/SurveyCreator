<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to view analytics", "warning");
    redirect('/auth/login.php');
}

// Get survey ID from URL but don't validate it
// This allows accessing the page with id=0 or without an ID
$survey_id = isset($_GET['id']) ? $_GET['id'] : null;

// Get user's survey statistics
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Get overall statistics
$stats = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id) as total_surveys,
        (SELECT COUNT(*) FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.user_id = $user_id) as total_responses,
        (SELECT COUNT(*) FROM surveys WHERE user_id = $user_id AND status = 'published') as active_surveys,
        (SELECT IFNULL(AVG(TIMESTAMPDIFF(MINUTE, started_at, submitted_at)), 0)
         FROM responses r 
         JOIN surveys s ON r.survey_id = s.id 
         WHERE s.user_id = $user_id
         AND started_at IS NOT NULL 
         AND submitted_at IS NOT NULL
         AND started_at < submitted_at) as avg_completion_time
    FROM DUAL
");

// Check if query was successful
if (!$stats) {
    // Provide default values if the query fails
    $stats_data = [
        'total_surveys' => 0,
        'total_responses' => 0,
        'active_surveys' => 0,
        'avg_completion_time' => 0
    ];
} else {
    $stats_data = $stats->fetch_assoc();
    
    // Ensure values are numeric - converting NULL to 0
    $stats_data['total_surveys'] = (int)($stats_data['total_surveys'] ?? 0);
    $stats_data['total_responses'] = (int)($stats_data['total_responses'] ?? 0);
    $stats_data['active_surveys'] = (int)($stats_data['active_surveys'] ?? 0);
    $stats_data['avg_completion_time'] = $stats_data['avg_completion_time'] ?? 0;
}

// Fallback to direct queries if main stats query returns zeros
if ($stats_data['total_surveys'] == 0 && $stats_data['total_responses'] == 0 && $stats_data['active_surveys'] == 0) {
    // Use direct queries as a fallback
    $direct_total_surveys = $db->query("SELECT COUNT(*) as count FROM surveys WHERE user_id = $user_id");
    $direct_total_responses = $db->query("SELECT COUNT(*) as count FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.user_id = $user_id");
    $direct_active_surveys = $db->query("SELECT COUNT(*) as count FROM surveys WHERE user_id = $user_id AND status = 'published'");
    $direct_avg_time = $db->query("SELECT AVG(TIMESTAMPDIFF(MINUTE, started_at, submitted_at)) as avg_time FROM responses r JOIN surveys s ON r.survey_id = s.id WHERE s.user_id = $user_id AND started_at IS NOT NULL AND submitted_at IS NOT NULL AND started_at < submitted_at");

    // Process direct query results
    $direct_stats = [
        'total_surveys' => $direct_total_surveys ? (int)($direct_total_surveys->fetch_assoc()['count'] ?? 0) : 0,
        'total_responses' => $direct_total_responses ? (int)($direct_total_responses->fetch_assoc()['count'] ?? 0) : 0,
        'active_surveys' => $direct_active_surveys ? (int)($direct_active_surveys->fetch_assoc()['count'] ?? 0) : 0,
        'avg_completion_time' => $direct_avg_time ? ($direct_avg_time->fetch_assoc()['avg_time'] ?? 0) : 0
    ];
    
    // Use direct stats if they have valid data
    if ($direct_stats['total_surveys'] > 0 || $direct_stats['total_responses'] > 0 || $direct_stats['active_surveys'] > 0) {
        $stats_data = $direct_stats;
    }
}

// Format average completion time
$avg_completion_minutes = round($stats_data['avg_completion_time'] ?? 0);

// If we have actual data, use it; otherwise use a reasonable default
if ($avg_completion_minutes > 0) {
    $formatted_time = "$avg_completion_minutes min";
} else {
    // Check if we have responses but no completion time data
    if ($stats_data['total_responses'] > 0) {
        // Use a reasonable default based on the number of questions
        $avg_questions_query = $db->query("
            SELECT AVG(question_count) as avg_questions
            FROM (
                SELECT survey_id, COUNT(*) as question_count
                FROM questions 
                WHERE survey_id IN (SELECT id FROM surveys WHERE user_id = $user_id)
                GROUP BY survey_id
            ) as survey_questions
        ");
        
        if ($avg_questions_query && $avg_questions_result = $avg_questions_query->fetch_assoc()) {
            $avg_questions = $avg_questions_result['avg_questions'] ?? 5;
            // Assume 30 seconds per question as a reasonable default
            $estimated_minutes = ceil($avg_questions * 0.5);
            $formatted_time = "$estimated_minutes min (est.)";
        } else {
            // If we can't calculate, use a fixed estimate
            $formatted_time = "3 min (est.)";
        }
    } else {
        $formatted_time = "N/A";
    }
}

// Get response trends (last 7 days)
$daily_trends_query = $db->query("
    SELECT 
        DATE(r.submitted_at) as date,
        COUNT(*) as count
    FROM responses r
    JOIN surveys s ON r.survey_id = s.id
    WHERE s.user_id = $user_id
    AND r.submitted_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(r.submitted_at)
    ORDER BY date ASC
");

// Store daily trend data in array
$daily_trends = [];
$daily_labels = [];
$daily_counts = [];

// Generate all dates for the last 7 days
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $daily_trends[$date] = 0;
    $daily_labels[] = date('M d', strtotime("-$i days"));
}

// Fill in actual counts
if ($daily_trends_query && $daily_trends_query->num_rows > 0) {
    while ($row = $daily_trends_query->fetch_assoc()) {
        $daily_trends[$row['date']] = (int)$row['count'];
    }
}

// Prepare data for charts
foreach ($daily_trends as $count) {
    $daily_counts[] = $count;
}

// Get hourly distribution
$hourly_query = $db->query("
    SELECT 
        HOUR(r.submitted_at) as hour,
        COUNT(*) as count
    FROM responses r
    JOIN surveys s ON r.survey_id = s.id
    WHERE s.user_id = $user_id
    GROUP BY HOUR(r.submitted_at)
    ORDER BY hour
");

// Store hourly data
$hourly_data = [];
$hourly_labels = [];
$hourly_counts = [];
$total_hourly_responses = 0;

// Initialize all hours with 0 counts
for ($i = 0; $i < 24; $i++) {
    $hour_label = sprintf('%02d:00', $i);
    $hourly_data[$i] = 0;
    $hourly_labels[] = $hour_label;
}

// Fill in actual counts
if ($hourly_query && $hourly_query->num_rows > 0) {
    while ($row = $hourly_query->fetch_assoc()) {
        $hour = (int)$row['hour'];
        $count = (int)$row['count'];
        $hourly_data[$hour] = $count;
        $total_hourly_responses += $count;
    }
}

// Prepare data for charts
foreach ($hourly_data as $count) {
    $hourly_counts[] = $count;
}

// Get question type distribution
$question_type_query = $db->query("
    SELECT 
        q.question_type,
        COUNT(*) as count
    FROM questions q
    JOIN surveys s ON q.survey_id = s.id
    WHERE s.user_id = $user_id
    GROUP BY q.question_type
");

// Store question type data
$question_type_data = [];
$question_labels = [];
$question_counts = [];
$question_colors = [];
$total_questions = 0;

// Custom colors for question types
$type_colors = [
    'multiple_choice' => '#3a5cdb',
    'single_choice' => '#1a3aa0',
    'text' => '#6f42c1',
    'rating' => '#fd7e14'
];

// Fill in actual counts
if ($question_type_query && $question_type_query->num_rows > 0) {
    while ($row = $question_type_query->fetch_assoc()) {
        $type = $row['question_type'];
        $count = (int)$row['count'];
        
        // Format type name for display
        $display_type = ucfirst(str_replace('_', ' ', $type));
        
        $question_type_data[$display_type] = $count;
        $question_labels[] = $display_type;
        $question_counts[] = $count;
        $question_colors[] = $type_colors[$type] ?? '#3a5cdb';
        $total_questions += $count;
    }
}

// Get completion rate by survey
$completion_query = $db->query("
    SELECT 
        s.id,
        s.title,
        COUNT(DISTINCT r.id) as responses,
        (SELECT COUNT(*) FROM responses r2 
         WHERE r2.survey_id = s.id 
         AND JSON_LENGTH(r2.answers) >= (SELECT COUNT(*) FROM questions WHERE survey_id = s.id)) as completed
    FROM surveys s
    LEFT JOIN responses r ON s.id = r.survey_id
    WHERE s.user_id = $user_id
    GROUP BY s.id
    HAVING responses > 0
    ORDER BY responses DESC
    LIMIT 5
");

// Store completion data
$completion_data = [];
$completion_labels = [];
$completion_values = [];
$completion_colors = [];

// Fill in actual data
if ($completion_query && $completion_query->num_rows > 0) {
    while ($row = $completion_query->fetch_assoc()) {
        $title = $row['title'];
        $responses = (int)$row['responses'];
        $completed = (int)$row['completed'];
        
        // Calculate completion rate
        $rate = ($responses > 0) ? round(($completed / $responses) * 100) : 0;
        
        // Store in array
        $completion_data[] = [
            'title' => $title,
            'responses' => $responses,
            'completed' => $completed,
            'rate' => $rate
        ];
        
        // Prepare chart data
        $completion_labels[] = (strlen($title) > 20) ? substr($title, 0, 20) . '...' : $title;
        $completion_values[] = $rate;
        
        // Set color based on completion rate
        if ($rate >= 80) {
            $completion_colors[] = '#198754'; // Green for high completion
        } elseif ($rate >= 50) {
            $completion_colors[] = '#ffc107'; // Yellow for medium completion
        } else {
            $completion_colors[] = '#dc3545'; // Red for low completion
        }
    }
}

// Get survey performance data
$surveys_query = $db->query("
    SELECT 
        s.id,
        s.title,
        s.status,
        COUNT(DISTINCT r.id) as responses,
        MIN(r.submitted_at) as first_response,
        MAX(r.submitted_at) as last_response,
        DATEDIFF(NOW(), s.created_at) as days_active
    FROM surveys s
    LEFT JOIN responses r ON s.id = r.survey_id
    WHERE s.user_id = $user_id
    GROUP BY s.id
    ORDER BY responses DESC
    LIMIT 10
");

$page_title = 'Analytics Dashboard';
require_once '../templates/header.php';
?>

<!-- Hero Section -->
<div class="analytics-hero mb-4 animate__animated animate__fadeIn">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold text-white mb-2">Analytics Dashboard</h1>
                <p class="lead text-white-50">Track your survey performance and responses</p>
    </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <button type="button" class="btn btn-light btn-lg me-2" id="export-analytics">
                    <i class="fas fa-download me-2"></i> Export Report
            </button>
                <button type="button" class="btn btn-outline-light btn-lg" id="print-analytics">
                    <i class="fas fa-print me-2"></i> Print
            </button>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
<!-- Statistics Cards -->
<div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card stats-card h-100 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
            <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon bg-primary-soft">
                            <i class="fas fa-file-alt text-primary"></i>
            </div>
                        <h6 class="card-subtitle ms-3 text-muted">Total Surveys</h6>
        </div>
                    <h2 class="card-title mb-2 counter-value"><?php echo $stats_data['total_surveys']; ?></h2>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> Created surveys
                </p>
            </div>
        </div>
    </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card stats-card h-100 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
            <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon bg-success-soft">
                            <i class="fas fa-chart-bar text-success"></i>
            </div>
                        <h6 class="card-subtitle ms-3 text-muted">Total Responses</h6>
        </div>
                    <h2 class="card-title mb-2 counter-value"><?php echo $stats_data['total_responses']; ?></h2>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> Responses collected
                </p>
            </div>
        </div>
    </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card stats-card h-100 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
    <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon bg-info-soft">
                            <i class="fas fa-check-circle text-info"></i>
                        </div>
                        <h6 class="card-subtitle ms-3 text-muted">Active Surveys</h6>
                    </div>
                    <h2 class="card-title mb-2 counter-value"><?php echo $stats_data['active_surveys']; ?></h2>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> Published surveys
                    </p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4 mb-lg-0">
            <div class="card stats-card h-100 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.4s">
            <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="stats-icon bg-warning-soft">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <h6 class="card-subtitle ms-3 text-muted">Avg. Completion Time</h6>
                    </div>
                    <h2 class="card-title mb-2"><?php echo $formatted_time; ?></h2>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-info-circle me-1"></i> Average time to complete survey
                    </p>
            </div>
        </div>
    </div>
</div>

    <!-- Primary Charts -->
<div class="row mb-4">
        <!-- Response Trends -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.5s">
                <div class="card-header bg-white p-3 d-flex align-items-center">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    <h5 class="mb-0">Response Trends (Last 7 Days)</h5>
            </div>
                <div class="card-body p-4">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Question Type Distribution -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
                <div class="card-header bg-white p-3 d-flex align-items-center">
                    <i class="fas fa-chart-pie text-primary me-2"></i>
                    <h5 class="mb-0">Question Types</h5>
                                                    </div>
                <div class="card-body p-4">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="questionTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Survey Performance Table -->
    <div class="card shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.9s">
        <div class="card-header bg-white p-3 d-flex align-items-center">
            <i class="fas fa-table text-primary me-2"></i>
        <h5 class="mb-0">Survey Performance</h5>
    </div>
        <div class="card-body p-0">
            <?php if ($surveys_query && $surveys_query->num_rows > 0): ?>
            <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="bg-light">
                        <tr>
                                <th>Survey Title</th>
                            <th>Status</th>
                            <th>Responses</th>
                            <th>First Response</th>
                            <th>Last Response</th>
                                <th>Days Active</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($survey = $surveys_query->fetch_assoc()): ?>
                            <tr>
                                    <td class="fw-medium">
                                        <?php echo htmlspecialchars($survey['title']); ?>
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
                                        <span class="badge bg-primary rounded-pill d-inline-flex align-items-center">
                                            <?php echo $survey['responses']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $survey['first_response'] ? date('M j, Y', strtotime($survey['first_response'])) : 'N/A'; ?></td>
                                    <td><?php echo $survey['last_response'] ? date('M j, Y', strtotime($survey['last_response'])) : 'N/A'; ?></td>
                                    <td><?php echo $survey['days_active']; ?> days</td>
                                    <td>
                                        <div class="d-flex gap-2">
                                    <a href="<?php echo SITE_URL; ?>/dashboard/view-responses.php?id=<?php echo $survey['id']; ?>" 
                                               class="btn btn-sm btn-primary" 
                                               title="View Responses">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/dashboard/edit-survey.php?id=<?php echo $survey['id']; ?>" 
                                               class="btn btn-sm btn-outline-secondary" 
                                               title="Edit Survey">
                                                <i class="fas fa-edit"></i>
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
                    <div class="empty-state mb-3">
                        <i class="fas fa-chart-bar fa-4x text-muted"></i>
                    </div>
                    <h5 class="text-muted">No survey data available</h5>
                    <p class="text-muted mb-4">Create and share surveys to see analytics here</p>
                    
                    <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i> Create a Survey
                    </a>
            </div>
        <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add CSS for analytics page -->
<style>
/* Analytics Hero */
.analytics-hero {
    background: linear-gradient(135deg, #3a5cdb 0%, #1a3aa0 100%);
    margin-top: -1.5rem;
    padding: 2rem 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.analytics-hero:before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Stats Cards */
.stats-card {
    transition: all 0.3s ease;
    border: none;
    border-radius: 10px;
    overflow: hidden;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.stats-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.bg-primary-soft { background-color: rgba(58, 92, 219, 0.1); }
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1); }
.bg-warning-soft { background-color: rgba(255, 193, 7, 0.1); }

/* Animation for counter values */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.counter-value {
    animation: countUp 1s ease forwards;
}

/* Chart containers */
.chart-container {
    position: relative;
    width: 100%;
}

/* Empty state */
.empty-state {
    width: 120px;
    height: 120px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(58, 92, 219, 0.1);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Setup print functionality
    document.getElementById('print-analytics').addEventListener('click', function() {
        window.print();
    });
    
    // Setup export functionality
    document.getElementById('export-analytics').addEventListener('click', function() {
        // Show loading state
        const exportBtn = document.getElementById('export-analytics');
        const originalText = exportBtn.innerHTML;
        exportBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Exporting...';
        exportBtn.disabled = true;
        
        // Get the survey ID from the URL or default to 0 for all surveys
        const urlParams = new URLSearchParams(window.location.search);
        const surveyId = urlParams.get('id') || 0;
        
        // Redirect to the export PHP file
        window.location.href = `export-analytics.php?id=${surveyId}`;
        
        // Reset button after a short delay (user will be redirected)
        setTimeout(function() {
            exportBtn.innerHTML = originalText;
            exportBtn.disabled = false;
        }, 3000);
    });
    
    // Animation for counter values
    const counterElements = document.querySelectorAll('.counter-value');
    counterElements.forEach(function(element) {
        const target = parseInt(element.innerText, 10);
        if (!isNaN(target) && target > 0) {
            let count = 0;
            const duration = 1500; // Animation duration in milliseconds
            const frameRate = 60; // Frames per second
            const increment = target / (duration / 1000 * frameRate);
            
            const counter = setInterval(function() {
                count += increment;
                element.innerText = Math.floor(count);
                
                if (count >= target) {
                    element.innerText = target;
                    clearInterval(counter);
                }
            }, 1000 / frameRate);
        }
    });
    
    // Daily Trend Chart
    const trendCtx = document.getElementById('trendChart').getContext('2d');
                new Chart(trendCtx, {
                    type: 'line',
                    data: {
            labels: <?php echo json_encode($daily_labels); ?>,
                        datasets: [{
                label: 'Daily Responses',
                data: <?php echo json_encode($daily_counts); ?>,
                backgroundColor: 'rgba(58, 92, 219, 0.1)',
                borderColor: '#3a5cdb',
                borderWidth: 3,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#3a5cdb',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7,
                tension: 0.4,
                fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#ffffff',
                    titleColor: '#333333',
                    bodyColor: '#333333',
                    bodyFont: {
                        size: 13
                    },
                    displayColors: false,
                    borderWidth: 1,
                    borderColor: '#e0e0e0',
                    callbacks: {
                        title: function(context) {
                            return context[0].label;
                        },
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + ' responses';
                        }
                    }
                }
            },
                        scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                            y: {
                                beginAtZero: true,
                                ticks: {
                        precision: 0
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
    
    // Question Type Chart
    const questionTypeCtx = document.getElementById('questionTypeChart').getContext('2d');
                new Chart(questionTypeCtx, {
                    type: 'doughnut',
                    data: {
            labels: <?php echo json_encode($question_labels); ?>,
                        datasets: [{
                data: <?php echo json_encode($question_counts); ?>,
                backgroundColor: <?php echo json_encode($question_colors); ?>,
                borderWidth: 0,
                hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
            cutout: '60%',
                        plugins: {
                            legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                            tooltip: {
                                callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${context.label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true,
                duration: 2000,
                easing: 'easeOutQuart'
            }
        }
    });
});
</script>

<?php require_once '../templates/footer.php'; ?>