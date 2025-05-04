<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to view surveys", "warning");
    redirect('/auth/login.php');
}

// Get user's surveys with pagination
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Pagination settings
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total surveys count
$total_count = $db->query("SELECT COUNT(*) as count FROM surveys WHERE user_id = $user_id");
$total_surveys = $total_count->fetch_assoc()['count'];
$total_pages = ceil($total_surveys / $per_page);

// Get surveys for current page
$surveys = $db->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM responses WHERE survey_id = s.id) as response_count,
           (SELECT MAX(submitted_at) FROM responses WHERE survey_id = s.id) as last_response
    FROM surveys s 
    WHERE s.user_id = $user_id 
    ORDER BY s.created_at DESC
    LIMIT $offset, $per_page
");

require_once '../templates/header.php';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h1>My Surveys</h1>
    </div>
    <div class="col-md-6 text-end">
        <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Survey
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" id="search-surveys" placeholder="Search surveys...">
            </div>
            <div class="col-md-3">
                <select class="form-select" id="status-filter">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="sort-by">
                    <option value="created_desc">Newest First</option>
                    <option value="created_asc">Oldest First</option>
                    <option value="responses_desc">Most Responses</option>
                    <option value="responses_asc">Least Responses</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Surveys List -->
<div class="card">
    <div class="card-body">
        <?php if ($surveys && $surveys->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Responses</th>
                            <th>Created</th>
                            <th>Last Response</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($survey = $surveys->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($survey['title']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $survey['status'] === 'published' ? 'success' : 
                                            ($survey['status'] === 'draft' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($survey['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $survey['response_count']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($survey['created_at'])); ?></td>
                                <td><?php echo $survey['last_response'] ? date('M j, Y', strtotime($survey['last_response'])) : '-'; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo SITE_URL; ?>/dashboard/edit-survey.php?id=<?php echo $survey['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="tooltip" 
                                           title="Edit Survey">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/dashboard/view-responses.php?id=<?php echo $survey['id']; ?>" 
                                           class="btn btn-sm btn-outline-info" 
                                           data-bs-toggle="tooltip" 
                                           title="View Responses">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/dashboard/share-survey.php?id=<?php echo $survey['id']; ?>" 
                                           class="btn btn-sm btn-outline-success" 
                                           data-bs-toggle="tooltip" 
                                           title="Share Survey">
                                            <i class="fas fa-share-alt"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/dashboard/download-survey.php?id=<?php echo $survey['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary" 
                                           data-bs-toggle="tooltip"
                                           title="Download PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-danger delete-survey" 
                                                data-survey-id="<?php echo $survey['id']; ?>"
                                                data-bs-toggle="tooltip"
                                                title="Delete Survey">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                <p class="lead">No surveys found</p>
                <a href="<?php echo SITE_URL; ?>/dashboard/create-survey.php" class="btn btn-primary">Create Your First Survey</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Survey</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this survey? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(tooltip => new bootstrap.Tooltip(tooltip));

    // Search functionality
    const searchInput = document.getElementById('search-surveys');
    searchInput?.addEventListener('input', filterSurveys);

    // Status filter
    const statusFilter = document.getElementById('status-filter');
    statusFilter?.addEventListener('change', filterSurveys);

    // Sort functionality
    const sortSelect = document.getElementById('sort-by');
    sortSelect?.addEventListener('change', sortSurveys);

    // Delete survey functionality
    let surveyToDelete = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.querySelectorAll('.delete-survey').forEach(button => {
        button.addEventListener('click', () => {
            surveyToDelete = button.dataset.surveyId;
            deleteModal.show();
        });
    });

    document.getElementById('confirmDelete')?.addEventListener('click', async () => {
        if (!surveyToDelete) return;

        try {
            const response = await fetch(`${SITE_URL}/api/surveys.php?id=${surveyToDelete}`, {
                method: 'DELETE'
            });

            const result = await response.json();

            if (response.ok) {
                // Remove the survey row and hide modal
                const row = document.querySelector(`[data-survey-id="${surveyToDelete}"]`).closest('tr');
                row.remove();
                deleteModal.hide();

                // Show success message
                const toast = document.createElement('div');
                toast.className = 'toast show position-fixed bottom-0 end-0 m-3 bg-success text-white';
                toast.innerHTML = `
                    <div class="toast-body">
                        Survey deleted successfully
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
                    </div>
                `;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 3000);

                // Reload if no surveys left
                if (document.querySelectorAll('tbody tr').length === 0) {
                    location.reload();
                }
            } else {
                throw new Error(result.error || 'Failed to delete survey');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to delete survey: ' + error.message);
        }
    });
});

function filterSurveys() {
    const searchTerm = document.getElementById('search-surveys').value.toLowerCase();
    const status = document.getElementById('status-filter').value;
    const rows = document.querySelectorAll('tbody tr');

    rows.forEach(row => {
        const title = row.cells[0].textContent.toLowerCase();
        const rowStatus = row.cells[1].textContent.trim().toLowerCase();
        const matchesSearch = title.includes(searchTerm);
        const matchesStatus = !status || rowStatus === status;
        
        row.style.display = matchesSearch && matchesStatus ? '' : 'none';
    });
}

function sortSurveys() {
    const sortBy = document.getElementById('sort-by').value;
    const tbody = document.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));

    rows.sort((a, b) => {
        switch (sortBy) {
            case 'created_desc':
                return new Date(b.cells[3].textContent) - new Date(a.cells[3].textContent);
            case 'created_asc':
                return new Date(a.cells[3].textContent) - new Date(b.cells[3].textContent);
            case 'responses_desc':
                return parseInt(b.cells[2].textContent) - parseInt(a.cells[2].textContent);
            case 'responses_asc':
                return parseInt(a.cells[2].textContent) - parseInt(b.cells[2].textContent);
            default:
                return 0;
        }
    });

    rows.forEach(row => tbody.appendChild(row));
}
</script>

<?php require_once '../templates/footer.php'; ?>
