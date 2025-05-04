<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$page_title = 'Survey Templates';
require_once 'templates/header.php';

// Get database instance
$db = Database::getInstance();

// Fetch all templates
$templates = $db->query("
    SELECT 
        t.id,
        t.title,
        t.description,
        COUNT(q.id) as question_count
    FROM templates t
    LEFT JOIN template_questions q ON t.id = q.template_id
    GROUP BY t.id
    ORDER BY t.title
");
?>

<div class="container mt-4">
    <h1>Survey Templates</h1>
    <p class="lead">Choose from our pre-made templates to quickly create your survey</p>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php while ($template = $templates->fetch_assoc()): ?>
            <div class="col">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($template['title']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($template['description']); ?></p>
                        <p class="text-muted">
                            <i class="fas fa-list-ul"></i> 
                            <?php echo $template['question_count']; ?> questions
                        </p>
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <button class="btn btn-primary use-template" data-template-id="<?php echo $template['id']; ?>">
                            <i class="fas fa-plus-circle"></i> Use Template
                        </button>
                        <button class="btn btn-outline-secondary preview-template" data-template-id="<?php echo $template['id']; ?>">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="preview-content">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="use-preview-template">Use Template</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
    let currentTemplateId = null;

    // Handle "Use Template" button clicks
    document.querySelectorAll('.use-template').forEach(button => {
        button.addEventListener('click', async (e) => {
            const templateId = e.target.closest('.use-template').dataset.templateId;
            window.location.href = `create-survey.php?template=${templateId}`;
        });
    });

    // Handle "Preview" button clicks
    document.querySelectorAll('.preview-template').forEach(button => {
        button.addEventListener('click', async (e) => {
            const templateId = e.target.closest('.preview-template').dataset.templateId;
            currentTemplateId = templateId;
            
            try {
                const response = await fetch(`api/get-template.php?template_id=${templateId}`);
                const data = await response.json();
                
                if (response.ok) {
                    // Display template preview
                    const previewContent = document.getElementById('preview-content');
                    previewContent.innerHTML = `
                        <h4>${data.title}</h4>
                        <p class="text-muted">${data.description}</p>
                        <hr>
                        <div class="questions">
                            ${data.questions.map((q, index) => `
                                <div class="question mb-4">
                                    <h5>${index + 1}. ${q.question_text}</h5>
                                    <p class="text-muted">Type: ${q.question_type}</p>
                                    ${q.options ? `
                                        <ul class="list-unstyled">
                                            ${q.options.map(opt => `
                                                <li>
                                                    <i class="fas fa-circle-dot"></i> ${opt}
                                                </li>
                                            `).join('')}
                                        </ul>
                                    ` : ''}
                                </div>
                            `).join('')}
                        </div>
                    `;
                    previewModal.show();
                } else {
                    throw new Error(data.error || 'Failed to load template preview');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load template preview: ' + error.message);
            }
        });
    });

    // Handle "Use Template" from preview modal
    document.getElementById('use-preview-template').addEventListener('click', () => {
        if (currentTemplateId) {
            window.location.href = `create-survey.php?template=${currentTemplateId}`;
        }
    });
});
</script>

<?php require_once 'templates/footer.php'; ?>
