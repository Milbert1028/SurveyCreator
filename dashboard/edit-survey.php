<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to edit surveys", "warning");
    redirect('/auth/login.php');
}

// CSRF token validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        flash_message("CSRF token mismatch. Please try again.", "danger");
        redirect('/dashboard');
    }
}

// Generate a new CSRF token for the form
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$survey_id = $_GET['id'] ?? null;
if (!$survey_id || !is_numeric($survey_id)) {
    flash_message("Survey ID is required and must be a valid number", "danger");
    redirect('/dashboard');
}

// Get survey details using query()
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

// Escape the survey_id and user_id to prevent SQL injection
$survey_id = intval($survey_id);
$user_id = intval($user_id);

// Get survey with questions and options
$survey = $db->query("
    SELECT s.*, 
           (SELECT GROUP_CONCAT(q.id, ':', q.question_text, ':', q.question_type, ':', 
                                IFNULL((SELECT GROUP_CONCAT(o.option_text ORDER BY o.order_position SEPARATOR ',') 
                                        FROM options o WHERE o.question_id = q.id), ''), ':', 
                                q.required, ':', q.order_position 
                                SEPARATOR '||') as questions
            FROM questions q 
            WHERE q.survey_id = s.id 
            ORDER BY q.order_position) as questions
    FROM surveys s 
    WHERE s.id = $survey_id AND s.user_id = $user_id
");

if (!$survey || $survey->num_rows === 0) {
    flash_message("Survey not found", "danger");
    redirect('/dashboard');
}

$survey_data = $survey->fetch_assoc();

// Parse questions data
$questions = [];
if ($survey_data['questions']) {
    foreach (explode('||', $survey_data['questions']) as $question) {
        list($id, $text, $type, $options, $required, $order) = explode(':', $question);
        $questions[] = [
            'id' => $id,
            'text' => htmlspecialchars($text),
            'type' => $type,
            'options' => $options ? explode(',', $options) : [],
            'required' => $required === '1',
            'order' => $order
        ];
    }
}

// Get available templates
$templates = $db->query("SELECT id, name, description FROM templates ORDER BY name");

$page_title = 'Edit Survey';
require_once '../templates/header.php';
?>
<!-- Link to external CSS file -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/survey-builder.css">

<!-- Edit Survey Hero -->
<div class="survey-creator-hero mb-4 animate__animated animate__fadeIn">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <h1 class="display-5 fw-bold text-white mb-2">Edit Survey</h1>
                <p class="lead text-white-50">Modify your survey design with our intuitive builder</p>
    </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <button type="button" class="btn btn-light btn-lg me-2" id="preview-survey">
                    <i class="fas fa-eye me-2"></i> Preview
        </button>
                <button type="button" class="btn btn-primary btn-lg" id="save-survey-btn">
                    <i class="fas fa-save me-2"></i> Save Changes
        </button>
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
<form id="edit-survey-form" action="<?php echo SITE_URL; ?>/dashboard/update-survey.php" method="post">
    <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

    <div class="row">
        <!-- Survey Builder -->
        <div class="col-lg-8">
            <div class="card survey-builder mb-4 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                <div class="card-body p-4">
                    <!-- Survey Details -->
                    <div class="mb-4">
                        <label for="survey-title" class="form-label text-muted small mb-1">Survey Title</label>
                        <input type="text" class="form-control form-control-lg mb-3" id="survey-title" name="title" 
                               placeholder="Enter an engaging title for your survey" value="<?php echo htmlspecialchars($survey_data['title']); ?>">
                        <label for="survey-description" class="form-label text-muted small mb-1">Survey Description</label>
                        <textarea class="form-control" id="survey-description" name="description" rows="2" 
                                  placeholder="Provide a brief description of your survey (optional)"><?php echo htmlspecialchars($survey_data['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Questions</h5>
                        <button type="button" class="btn btn-primary rounded-pill px-3" id="add-question">
                            <i class="fas fa-plus me-2"></i> Add Question
                        </button>
                    </div>
                    <p class="text-muted mb-4">Drag question types from the right panel or use the Add Question button above.</p>

                    <!-- Questions Area -->
                    <div class="questions-container">
                            <!-- Questions List -->
                            <div class="question-list" id="sortable-questions">
                            <?php if (empty($questions)): ?>
                                <div class="empty-state text-center py-5" id="empty-questions">
                                    <div class="empty-icon-container mb-3">
                                        <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No Questions Added Yet</h5>
                                    <p class="text-muted">Drag question types from the sidebar or click the Add Question button</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($questions as $question): ?>
                                    <div class="question-block animate__animated animate__fadeIn" data-question-id="<?php echo $question['id']; ?>" data-question-type="<?php echo $question['type']; ?>">
                                        <input type="hidden" name="questions[<?php echo $question['id']; ?>][id]" value="<?php echo $question['id']; ?>">
                                        <div class="card mb-3 shadow-sm">
                                            <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
                                                <div class="d-flex align-items-center">
                                            <div class="drag-handle me-2">
                                                <i class="fas fa-grip-vertical text-muted"></i>
                                            </div>
                                                    <h5 class="mb-0 question-type-label">
                                                        <?php 
                                                            $type_labels = [
                                                                'multiple_choice' => 'Multiple Choice',
                                                                'single_choice' => 'Single Choice',
                                                                'text' => 'Text Answer',
                                                                'rating' => 'Rating'
                                                            ];
                                                            echo $type_labels[$question['type']] ?? 'Question';
                                                        ?>
                                                    </h5>
                                            </div>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-question">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                            <div class="card-body p-4">
                                                <div class="mb-3">
                                                    <label class="form-label">Question Text</label>
                                                    <input type="text" class="form-control question-text" 
                                                           name="questions[<?php echo $question['id']; ?>][text]" 
                                                           placeholder="Enter your question" 
                                                           value="<?php echo $question['text']; ?>">
                                                </div>
                                                <div class="form-check mb-3">
                                                    <input type="checkbox" class="form-check-input required-toggle" 
                                                           id="required-<?php echo $question['id']; ?>" 
                                                           name="questions[<?php echo $question['id']; ?>][required]" 
                                                           <?php echo $question['required'] ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="required-<?php echo $question['id']; ?>">Required</label>
                                                </div>
                                                
                                                <input type="hidden" name="questions[<?php echo $question['id']; ?>][type]" value="<?php echo $question['type']; ?>">
                                                
                                                <!-- Options Container - Will be shown/hidden based on question type -->
                                        <div class="options-container" style="display: <?php echo in_array($question['type'], ['multiple_choice', 'single_choice', 'rating']) ? 'block' : 'none'; ?>;">
                                                    <label class="form-label">Options</label>
                                                    <div class="options-list">
                                                        <?php if (!empty($question['options'])): foreach ($question['options'] as $index => $option): ?>
                                                            <div class="option-item input-group mb-2">
                                                        <span class="input-group-text">
                                                                    <i class="fas <?php echo $question['type'] === 'multiple_choice' ? 'fa-check-square' : ($question['type'] === 'rating' ? 'fa-star' : 'fa-dot-circle'); ?>"></i>
                                                        </span>
                                                                <input type="text" class="form-control option-text" 
                                                                       name="questions[<?php echo $question['id']; ?>][options][]" 
                                                                       placeholder="Option <?php echo $index + 1; ?>" 
                                                               value="<?php echo htmlspecialchars($option); ?>">
                                                        <button class="btn btn-outline-danger delete-option" type="button">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                <?php endforeach; endif; ?>
                                        </div>

                                                    <!-- Only show for choice questions -->
                                                    <button type="button" class="btn btn-sm btn-outline-primary add-option mt-2">
                                                        <i class="fas fa-plus me-1"></i> Add Option
                                                            </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Survey Tools and Templates -->
        <div class="col-lg-4">
            <!-- Question Types -->
            <div class="card mb-4 question-types shadow-sm animate__animated animate__fadeInRight" style="animation-delay: 0.2s">
                <div class="card-header bg-white p-3">
                    <h5 class="mb-0"><i class="fas fa-th-list me-2 text-primary"></i> Question Types</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item question-type-item" draggable="true" data-type="multiple_choice">
                            <div class="d-flex align-items-center">
                                <div class="question-type-icon me-3">
                                    <i class="fas fa-check-square"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Multiple Choice</h6>
                                    <small class="text-muted">Select multiple options</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item question-type-item" draggable="true" data-type="single_choice">
                            <div class="d-flex align-items-center">
                                <div class="question-type-icon me-3">
                                    <i class="fas fa-dot-circle"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Single Choice</h6>
                                    <small class="text-muted">Select one option</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item question-type-item" draggable="true" data-type="text">
                            <div class="d-flex align-items-center">
                                <div class="question-type-icon me-3">
                                    <i class="fas fa-font"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Text Answer</h6>
                                    <small class="text-muted">Free-form text response</small>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item question-type-item" draggable="true" data-type="rating">
                            <div class="d-flex align-items-center">
                                <div class="question-type-icon me-3">
                                    <i class="fas fa-star"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0">Rating</h6>
                                    <small class="text-muted">Scale-based rating</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<!-- Add CSS for edit survey page -->
<style>
/* Survey Creator Hero */
.survey-creator-hero {
    background: linear-gradient(135deg, #3a5cdb 0%, #1a3aa0 100%);
    margin-top: -1.5rem;
    padding: 2rem 0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    position: relative;
    overflow: hidden;
}

.survey-creator-hero:before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}

/* Questions Container */
.questions-container {
    border-radius: 10px;
    min-height: 300px;
}

/* Empty State */
.empty-icon-container {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(58, 92, 219, 0.1);
}

/* Question Type Items */
.question-type-item {
    cursor: grab;
    transition: all 0.3s ease;
    padding: 1rem;
    border-left: 3px solid transparent;
}

.question-type-item:hover {
    background-color: rgba(58, 92, 219, 0.05);
    border-left-color: #3a5cdb;
    transform: translateX(5px);
}

.question-type-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background-color: rgba(58, 92, 219, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #3a5cdb;
}

/* Question Block */
.question-block {
    margin-bottom: 1.5rem;
}

.btn-icon {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

.drag-handle {
    cursor: grab;
    padding: 5px;
}

/* Add question button hover effect */
#add-question {
    transition: all 0.3s ease;
}

#add-question:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}
</style>

<!-- Modal for Survey Preview -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Survey Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="preview-container p-3">
                    <h3 id="preview-title" class="mb-3">Survey Title</h3>
                    <p id="preview-description" class="text-muted mb-4"></p>
                    
                    <div id="preview-questions">
                        <!-- Questions will be populated here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close Preview</button>
            </div>
        </div>
    </div>
</div>

<!-- Include jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Add the Sortable.js library -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>

<!-- Include the survey builder JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/survey-builder.js"></script>

<!-- Include the drag-drop fix script -->
<script src="<?php echo SITE_URL; ?>/dashboard/survey-drag-drop-fix.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up dynamic question numbers
    updateQuestionNumbers();
    
    // Initialize array to track deleted questions
    window.deletedQuestions = [];
    
    // DO NOT initialize survey builder - it's causing duplicates
    // window.surveyBuilder = new SurveyBuilder();
    
    // Set up document-level event handler for delete question button
    document.addEventListener('click', function(event) {
        const deleteButton = event.target.closest('.delete-question');
        if (deleteButton) {
            const questionBlock = deleteButton.closest('.question-block');
            if (questionBlock) {
                // Get the question ID and add it to deleted questions array if it's an existing question
                const questionId = questionBlock.dataset.questionId;
                if (questionId && !questionId.startsWith('new_')) {
                    window.deletedQuestions.push(questionId);
                    console.log('Question marked for deletion:', questionId);
                }
                
                // Remove the question block from the DOM
                questionBlock.remove();
                
                // If no questions remain, show empty state
                if (document.querySelectorAll('#sortable-questions .question-block').length === 0) {
                    const questionsContainer = document.getElementById('sortable-questions');
                    if (questionsContainer) {
                        questionsContainer.innerHTML = `
                            <div class="empty-state text-center py-5" id="empty-questions">
                                <div class="empty-icon-container mb-3">
                                    <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                                </div>
                                <h5 class="text-muted mb-2">No Questions Added Yet</h5>
                                <p class="text-muted">Drag question types from the sidebar or click the Add Question button</p>
                            </div>
                        `;
                    }
                }
                
                // Update question numbers
                updateQuestionNumbers();
            }
        }
    });
    
    // Add event listener for Add Question button
    const addQuestionButton = document.getElementById('add-question');
    if (addQuestionButton) {
        // Make sure we remove any existing event listeners first
        const newAddBtn = addQuestionButton.cloneNode(true);
        addQuestionButton.parentNode.replaceChild(newAddBtn, addQuestionButton);
        
        newAddBtn.addEventListener('click', function() {
            window.addQuestionToSurvey('', 'multiple_choice', false, ['Option 1', 'Option 2']);
        });
    }
    
    // Hook up the preview button
    const previewButton = document.getElementById('preview-survey');
    if (previewButton) {
        previewButton.addEventListener('click', function() {
            // Check if there are any questions before showing preview
            const questionBlocks = document.querySelectorAll('.question-block');
            if (questionBlocks.length === 0) {
                alert('Please add at least one question before previewing.');
                return;
            }
            
            // Get survey title and description
            const title = document.getElementById('survey-title').value || 'Untitled Survey';
            const description = document.getElementById('survey-description').value || '';
            
            // Ensure modal exists
            const previewModalElement = document.getElementById('previewModal');
            if (!previewModalElement) {
                console.error('Preview modal not found');
                alert('Preview functionality is not available. Please try refreshing the page.');
                return;
            }
            
            // Prepare preview container
            const previewTitle = document.getElementById('preview-title');
            const previewDescription = document.getElementById('preview-description');
            const previewQuestions = document.getElementById('preview-questions');
            
            if (!previewTitle || !previewDescription || !previewQuestions) {
                console.error('Preview elements not found:', { 
                    previewTitle: !!previewTitle, 
                    previewDescription: !!previewDescription, 
                    previewQuestions: !!previewQuestions 
                });
                alert('Preview functionality is not available. Please try refreshing the page.');
                return;
            }
            
            // Set title and description
            previewTitle.textContent = title;
            previewDescription.textContent = description;
            
            // Clear previous questions
            previewQuestions.innerHTML = '';
            
            // Generate preview questions
            questionBlocks.forEach(function(block, index) {
                const questionText = block.querySelector('.question-text').value || 'Untitled Question';
                const questionType = block.dataset.questionType || 'text';
                const isRequired = block.querySelector('.required-toggle')?.checked || false;
                
                // Get options for this question
                const optionInputs = block.querySelectorAll('.option-text');
                const options = Array.from(optionInputs).map(input => input.value || 'Option');
                
                // Create question div
                const questionDiv = document.createElement('div');
                questionDiv.className = 'mb-4 p-3 bg-light rounded';
                
                // Add question text and required indicator
                questionDiv.innerHTML = `
                    <h5 class="mb-3">
                        ${index + 1}. ${questionText}
                        ${isRequired ? '<span class="text-danger">*</span>' : ''}
                    </h5>
                `;
                
                // Add appropriate input based on question type
                const inputContainer = document.createElement('div');
                
                if (questionType === 'multiple_choice') {
                    options.forEach((option, i) => {
                        inputContainer.innerHTML += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="preview-q${index}-opt${i}">
                                <label class="form-check-label" for="preview-q${index}-opt${i}">${option}</label>
                            </div>
                        `;
                    });
                } else if (questionType === 'single_choice') {
                    options.forEach((option, i) => {
                        inputContainer.innerHTML += `
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="preview-q${index}" id="preview-q${index}-opt${i}">
                                <label class="form-check-label" for="preview-q${index}-opt${i}">${option}</label>
                            </div>
                        `;
                    });
                } else if (questionType === 'text') {
                    inputContainer.innerHTML = `
                        <textarea class="form-control" rows="3" placeholder="Your answer"></textarea>
                    `;
                } else if (questionType === 'rating') {
                    inputContainer.innerHTML = `
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            ${options.length > 0 ? options.map((option, i) => `
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> ${option}
                                </button>
                            `).join('') : `
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> 1
                                </button>
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> 2
                                </button>
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> 3
                                </button>
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> 4
                                </button>
                                <button type="button" class="btn btn-outline-warning">
                                    <i class="fas fa-star me-1"></i> 5
                                </button>
                            `}
                        </div>
                    `;
                }
                
                questionDiv.appendChild(inputContainer);
                previewQuestions.appendChild(questionDiv);
            });
            
            try {
                // Show the modal
                const previewModal = new bootstrap.Modal(previewModalElement);
                previewModal.show();
                
                // Set up event listener for when modal is hidden to ensure no element inside retains focus
                previewModalElement.addEventListener('hidden.bs.modal', function () {
                    // Move focus back to the preview button when modal is closed
                    document.getElementById('preview-survey').focus();
                });
            } catch (error) {
                console.error('Error showing preview modal:', error);
                alert('There was an error displaying the preview. Please try refreshing the page.');
            }
        });
    }
    
    // Hook up save button
    const saveButton = document.getElementById('save-survey-btn');
    if (saveButton) {
        // Clone to remove existing event listeners
        const newSaveBtn = saveButton.cloneNode(true);
        saveButton.parentNode.replaceChild(newSaveBtn, saveButton);
        
        newSaveBtn.addEventListener('click', function() {
            // Check if there are any questions
            const questionBlocks = document.querySelectorAll('.question-block');
            if (questionBlocks.length === 0) {
                alert('Please add at least one question before saving.');
                return;
            }
            
            // Get survey title
            const title = document.getElementById('survey-title').value.trim();
            if (!title) {
                alert('Please enter a survey title.');
                document.getElementById('survey-title').focus();
                return;
            }
            
            // Get the form element
            const form = document.getElementById('edit-survey-form');
            
            // Create a temporary form-data element to collect all form values
            const formData = new FormData(form);
            
            // Ensure hidden inputs for survey_id and csrf_token exist and are filled
            const surveyId = formData.get('survey_id');
            const csrfToken = formData.get('csrf_token');
            
            if (!surveyId || !csrfToken) {
                alert('Missing required form fields. Please refresh the page and try again.');
                return;
            }
            
            // Create a JSON object to hold the survey data
            const surveyData = {
                survey_id: surveyId,
                csrf_token: csrfToken,
                title: title,
                description: document.getElementById('survey-description').value.trim(),
                questions: {},
                deleted_questions: window.deletedQuestions || []
            };
            
            // Process each question
            let validQuestions = true;
            
            questionBlocks.forEach(function(block, index) {
                const questionId = block.dataset.questionId || 'new_' + index;
                const questionText = block.querySelector('.question-text').value.trim();
                
                if (!questionText) {
                    alert(`Question ${index + 1} is missing text. Please fill in all questions.`);
                    block.querySelector('.question-text').focus();
                    validQuestions = false;
                    return;
                }
                
                const questionType = block.dataset.questionType;
                const isRequired = block.querySelector('.required-toggle').checked;
                
                surveyData.questions[questionId] = {
                    id: questionId,
                    text: questionText,
                    type: questionType,
                    required: isRequired,
                    order: index,
                    options: []
                };
                
                // Get options if applicable
                if (questionType === 'multiple_choice' || questionType === 'single_choice' || questionType === 'rating') {
                    const optionInputs = block.querySelectorAll('.option-text');
                    
                    optionInputs.forEach(function(input) {
                        const optionText = input.value.trim();
                        if (optionText) {
                            surveyData.questions[questionId].options.push(optionText);
                        }
                    });
                    
                    if (surveyData.questions[questionId].options.length < 2 && 
                        (questionType === 'multiple_choice' || questionType === 'single_choice')) {
                        alert(`Question ${index + 1} needs at least two options. Please add more options.`);
                        validQuestions = false;
                        return;
                    }
                }
            });
            
            if (!validQuestions) return;
            
            // Submit the form with AJAX
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(surveyData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Survey updated successfully!');
                    window.location.href = `${window.location.origin}/dashboard`;
                } else {
                    alert('Error updating survey: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error updating survey:', error);
                alert('Error updating survey. Please try again.');
            });
        });
    }
    
    // Function to update question numbers
    function updateQuestionNumbers() {
        document.querySelectorAll('.question-block').forEach((block, index) => {
            const numberEl = block.querySelector('.question-number');
            if (numberEl) {
                numberEl.textContent = index + 1;
            }
        });
    }
});
</script>

<!-- Question Template (Hidden) -->
<template id="question-template">
    <div class="question-block animate__animated animate__fadeIn" data-question-type="">
        <div class="card mb-3 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
                <div class="d-flex align-items-center">
                    <div class="drag-handle me-2">
                        <i class="fas fa-grip-vertical text-muted"></i>
                    </div>
                    <h5 class="mb-0 question-type-label"></h5>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-question">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label">Question Text</label>
                    <input type="text" class="form-control question-text" placeholder="Enter your question">
                </div>
                <div class="form-check mb-3">
                    <input type="checkbox" class="form-check-input required-toggle" id="required-new">
                    <label class="form-check-label" for="required-new">Required</label>
                </div>
                
                <!-- Options Container - Will be shown/hidden based on question type -->
                <div class="options-container">
                    <label class="form-label">Options</label>
                    <div class="options-list">
                        <!-- Options will be added here dynamically -->
                    </div>
                    
                    <!-- Only show for choice questions -->
                    <button type="button" class="btn btn-sm btn-outline-primary add-option mt-2">
                        <i class="fas fa-plus me-1"></i> Add Option
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<?php require_once '../templates/footer.php'; ?>
