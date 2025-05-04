<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/Database.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    flash_message("You must be logged in as an admin to access this page", "danger");
    redirect('/admin/login.php');
}

// Initialize database connection
$db = new Database();
$conn = $db->getConnection();

// Handle survey actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle survey deletion
    if (isset($_POST['delete_survey']) && !empty($_POST['survey_id'])) {
        $survey_id = filter_var($_POST['survey_id'], FILTER_VALIDATE_INT);
        if ($survey_id) {
            // First delete survey responses
            $delete_responses = "DELETE FROM survey_responses WHERE survey_id = ?";
            $stmt = $conn->prepare($delete_responses);
            $stmt->bind_param("i", $survey_id);
            $stmt->execute();
            
            // Then delete survey questions
            $delete_questions = "DELETE FROM survey_questions WHERE survey_id = ?";
            $stmt = $conn->prepare($delete_questions);
            $stmt->bind_param("i", $survey_id);
            $stmt->execute();
            
            // Finally delete the survey
            $delete_survey = "DELETE FROM surveys WHERE id = ?";
            $stmt = $conn->prepare($delete_survey);
            $stmt->bind_param("i", $survey_id);
            
            if ($stmt->execute()) {
                flash_message("Survey deleted successfully", "success");
            } else {
                flash_message("Failed to delete survey", "danger");
            }
        }
    }
    
    // Handle survey status toggle
    if (isset($_POST['toggle_status']) && !empty($_POST['survey_id'])) {
        $survey_id = filter_var($_POST['survey_id'], FILTER_VALIDATE_INT);
        $new_status = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        if ($survey_id) {
            $update_query = "UPDATE surveys SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $new_status, $survey_id);
            
            if ($stmt->execute()) {
                flash_message("Survey status updated successfully", "success");
            } else {
                flash_message("Failed to update survey status", "danger");
            }
        }
    }
}

// Search functionality
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE title LIKE ? OR description LIKE ?";
    $search_params = ["%$search%", "%$search%"];
}

// Count total surveys for pagination
$count_query = "SELECT COUNT(*) as total FROM surveys $search_condition";
$stmt = $conn->prepare($count_query);

if (!empty($search_params)) {
    $stmt->bind_param(str_repeat("s", count($search_params)), ...$search_params);
}

$stmt->execute();
$result = $stmt->get_result();
$total_surveys = $result->fetch_assoc()['total'];

// Pagination
$surveys_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $surveys_per_page;
$total_pages = ceil($total_surveys / $surveys_per_page);

// Get surveys with pagination
$query = "SELECT s.*, u.name as creator_name, 
         (SELECT COUNT(*) FROM survey_responses sr WHERE sr.survey_id = s.id) as response_count
         FROM surveys s 
         LEFT JOIN users u ON s.user_id = u.id 
         $search_condition
         ORDER BY s.created_at DESC 
         LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);

if (!empty($search_params)) {
    $param_types = str_repeat("s", count($search_params)) . "ii";
    $params = array_merge($search_params, [$surveys_per_page, $offset]);
    $stmt->bind_param($param_types, ...$params);
} else {
    $stmt->bind_param("ii", $surveys_per_page, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$surveys = $result->fetch_all(MYSQLI_ASSOC);

$page_title = "Survey Management";
?>

<?php include_once '../templates/admin-header.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Survey Management</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item active">Surveys</li>
    </ol>
    
    <?php display_flash_messages(); ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-clipboard-list me-1"></i>
                    All Surveys
                </div>
                <div>
                    <a href="../dashboard/create-survey.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-1"></i> Create New Survey
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Search Form -->
            <form method="GET" class="mb-4">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search surveys..." 
                           name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="surveys.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <?php if (empty($surveys)): ?>
                <div class="alert alert-info">
                    <?php echo empty($search) ? "No surveys found." : "No surveys matching your search criteria."; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Creator</th>
                                <th>Responses</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($surveys as $survey): ?>
                                <tr>
                                    <td><?php echo $survey['id']; ?></td>
                                    <td>
                                        <a href="../survey.php?id=<?php echo $survey['id']; ?>" target="_blank" 
                                           title="View Survey">
                                            <?php echo htmlspecialchars($survey['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($survey['creator_name']); ?></td>
                                    <td>
                                        <?php if ($survey['response_count'] > 0): ?>
                                            <a href="responses.php?survey_id=<?php echo $survey['id']; ?>" 
                                               class="badge bg-info text-decoration-none">
                                                <?php echo $survey['response_count']; ?> responses
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No responses</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $survey['status'] === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst($survey['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($survey['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../dashboard/edit-survey.php?id=<?php echo $survey['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Edit Survey">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-info" 
                                                    onclick="copyShareLink(<?php echo $survey['id']; ?>)" 
                                                    title="Copy Share Link">
                                                <i class="fas fa-link"></i>
                                            </button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change the status of this survey?');">
                                                <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $survey['status']; ?>">
                                                <button type="submit" name="toggle_status" class="btn btn-sm btn-outline-<?php echo $survey['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                        title="<?php echo $survey['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> Survey">
                                                    <i class="fas fa-<?php echo $survey['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this survey? This action cannot be undone!');">
                                                <input type="hidden" name="survey_id" value="<?php echo $survey['id']; ?>">
                                                <button type="submit" name="delete_survey" class="btn btn-sm btn-outline-danger" title="Delete Survey">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Previous
                                </a>
                            </li>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                    Next
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function copyShareLink(surveyId) {
        const shareUrl = `${window.location.origin}/survey.php?id=${surveyId}`;
        navigator.clipboard.writeText(shareUrl).then(() => {
            alert('Survey link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy link:', err);
            alert('Failed to copy link. Please try again.');
        });
    }
</script>

<?php include_once '../templates/admin-footer.php'; ?>
