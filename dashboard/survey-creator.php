<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to create a survey", "warning");
    redirect('/auth/login.php');
}

// Get available templates
$db = Database::getInstance();
$templates = $db->query("SELECT id, name, description FROM templates ORDER BY name");

require_once '../templates/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Create New Survey</h1>
    </div>
    <div class="col-md-4 text-end">
        <button type="button" class="btn btn-outline-secondary me-2" id="preview-survey">
            <i class="fas fa-eye"></i> Preview
        </button>
        <button type="button" class="btn btn-primary" id="save-survey">
            <i class="fas fa-save"></i> Save Survey
        </button>
    </div>
</div>

<div class="row">
    <!-- Survey Builder -->
    <div class="col-lg-8">
        <div class="card survey-builder mb-4">
            <div class="card-body">
                <!-- Survey Details -->
                <div class="mb-4">
                    <input type="text" class="form-control form-control-lg mb-3" id="survey-title" 
                           placeholder="Survey Title">
                    <textarea class="form-control" id="survey-description" rows="2" 
                              placeholder="Survey Description (optional)"></textarea>
                </div>

                <!-- Questions Area -->
                <div class="row">
                    <div class="col-md-8">
                        <!-- Questions List -->
                        <div class="question-list" id="sortable-questions">
                            <div class="empty-state text-center py-5" id="empty-questions">
                                <i class="fas fa-clipboard fa-3x text-muted mb-3"></i>
                                <p class="lead">Drag questions here or click Add Question</p>
                            </div>
                        </div>

                        <!-- Add Question Button -->
                <div class="text-center mt-4">
                    <button type="button" class="btn btn-success" id="add-question">
                        <i class="fas fa-plus"></i> Add Question
                    </button>
                </div>

                <!-- Template Questions Display -->
                <div class="template-questions mt-4" style="display: none;">
                    <h5>Template Questions</h5>
                    <div class="question-list" id="template-questions"></div>
                </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Survey Tools and Templates -->
    <div class="col-lg-4">
        <!-- Question Types -->
        <div class="card mb-4 question-types">
            <div class="card-header">
                <h5 class="mb-0">Question Types</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item" draggable="true" data-type="multiple_choice">
                        <i class="fas fa-check-square me-2"></i> Multiple Choice
                    </div>
                    <div class="list-group-item" draggable="true" data-type="single_choice">
                        <i class="fas fa-dot-circle me-2"></i> Single Choice
                    </div>
                    <div class="list-group-item" draggable="true" data-type="text">
                        <i class="fas fa-font me-2"></i> Text Answer
                    </div>
                    <div class="list-group-item" draggable="true" data-type="rating">
                        <i class="fas fa-star me-2"></i> Rating
                    </div>
                </div>
            </div>
        </div>

        <!-- Templates -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Templates</h5>
            </div>
            <div class="card-body">
                <?php if ($templates && $templates->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while ($template = $templates->fetch_assoc()): ?>
                            <a href="#" class="list-group-item list-group-item-action template-item" 
                               data-template-id="<?php echo $template['id']; ?>">
                                <h6 class="mb-1"><?php echo htmlspecialchars($template['name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($template['description']); ?>
                                </small>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No templates available</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Preview Container -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Survey Preview</h5>
            </div>
            <div class="card-body" id="preview-container">
                <h3 id="preview-title"></h3>
                <p id="preview-description"></p>
                <div id="preview-questions"></div>
            </div>
        </div>
    </div>
</div>

<!-- Question Template (Hidden) -->
<template id="question-template">
    <div class="question-block" data-question-id="">
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="drag-handle me-2">
                <i class="fas fa-grip-vertical text-muted"></i>
            </div>
            <div class="flex-grow-1">
                <input type="text" class="form-control question-text mb-2" placeholder="Enter your question">
                <select class="form-select question-type mb-2">
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="single_choice">Single Choice</option>
                    <option value="text">Text Answer</option>
                    <option value="rating">Rating</option>
                </select>
            </div>
            <div class="ms-2">
                <button type="button" class="btn btn-outline-danger btn-sm delete-question">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        
        <div class="options-container" style="display: none;">
            <div class="options-list mb-2"></div>
            <button type="button" class="btn btn-sm btn-outline-secondary add-option">
                <i class="fas fa-plus"></i> Add Option
            </button>
        </div>

        <div class="question-settings mt-2">
            <div class="form-check">
                <input type="checkbox" class="form-check-input question-required" checked>
                <label class="form-check-label">Required</label>
            </div>
        </div>
    </div>
</template>

<!-- Option Template (Hidden) -->
<template id="option-template">
    <div class="input-group mb-2 option-item">
        <span class="input-group-text">
            <i class="fas fa-grip-vertical text-muted"></i>
        </span>
        <input type="text" class="form-control" placeholder="Option text">
        <button class="btn btn-outline-danger delete-option" type="button">
            <i class="fas fa-times"></i>
        </button>
    </div>
</template>

<!-- Preview Question Template (Hidden) -->
<template id="preview-question-template">
    <div class="preview-question mb-4">
    <label class="form-label fw-bold"></label>
<div class="question-content" id="sortable-list"></div>
</div>
</template>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const surveyBuilder = new SurveyBuilder();

    // Initialize Sortable.js
    const sortableList = document.getElementById('sortable-list');
    if (sortableList) {
        new Sortable(sortableList, {
            animation: 150,
            ghostClass: 'sortable-ghost'
        });
    }

    // Preview Survey Button functionality
    document.getElementById('preview-survey').addEventListener('click', () => {
        surveyBuilder.previewSurvey();
    });
});
</script>

<?php require_once '../templates/footer.php'; ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script> 
<script src="main.js"></script>