    <?php
    require_once dirname(__DIR__) . '/includes/config.php';
    require_once dirname(__DIR__) . '/includes/functions.php';

    // Ensure user is logged in
    if (!is_logged_in()) {
        flash_message("Please login to create a survey", "warning");
        redirect('/auth/login.php');
    }

    // Get available templates
    $db = Database::getInstance();
    $templates = $db->query("SELECT id, name, description FROM templates ORDER BY name");

    // Check if template parameter is provided
    $template_id = isset($_GET['template']) ? intval($_GET['template']) : null;

    // Get template data if template_id is provided
    $template_data = null;
    $template_error = null;
    if ($template_id) {
        try {
            $template_query = $db->query("SELECT * FROM templates WHERE id = $template_id");
            if (!$template_query) {
                $template_error = "Database error: " . $db->getLastError();
                error_log("Error loading template in create-survey.php: " . $template_error);
            } else if ($template_query->num_rows > 0) {
                $template_data = $template_query->fetch_assoc();
                
                // Validate template structure
                if (empty($template_data['structure'])) {
                    $template_error = "Template has no structure data";
                    error_log($template_error);
                    $template_data = null;
                }
            } else {
                $template_error = "Template not found";
                error_log("Template not found: ID=$template_id");
            }
        } catch (Exception $e) {
            $template_error = "Error loading template: " . $e->getMessage();
            error_log("Exception loading template: " . $e->getMessage());
        }
    }

    $page_title = 'Create Survey';
    require_once dirname(__DIR__) . '/templates/header.php';
    ?>

    <!-- Link to external CSS file -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/survey-builder.css">

    <!-- Create Survey Hero -->
    <div class="survey-creator-hero mb-4 animate__animated animate__fadeIn">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-7">
                    <h1 class="display-5 fw-bold text-white mb-2">Create Your Survey</h1>
                    <p class="lead text-white-50">Design your perfect survey with our intuitive builder</p>
                </div>
                <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                    <button type="button" class="btn btn-light btn-lg me-2" id="preview-survey">
                        <i class="fas fa-eye me-2"></i> Preview
                    </button>
                    <button type="button" class="btn btn-primary btn-lg" id="save-survey-btn">
                        <i class="fas fa-save me-2"></i> Save Survey
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
    <?php if ($template_error): ?>
    <div class="alert alert-warning mb-4">
        <h5><i class="fas fa-exclamation-triangle me-2"></i>Template Loading Error</h5>
        <p><?php echo htmlspecialchars($template_error); ?></p>
        <p>Please continue creating your survey from scratch or try another template.</p>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Survey Builder -->
        <div class="col-lg-8">
                <div class="card survey-builder mb-4 shadow-sm animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                    <div class="card-body p-4">
                    <!-- Survey Details -->
                    <div class="mb-4">
                            <label for="survey-title" class="form-label text-muted small mb-1">Survey Title</label>
                        <input type="text" class="form-control form-control-lg mb-3" id="survey-title" 
                                placeholder="Enter an engaging title for your survey">
                            <label for="survey-description" class="form-label text-muted small mb-1">Survey Description</label>
                        <textarea class="form-control" id="survey-description" rows="2" 
                                    placeholder="Provide a brief description of your survey (optional)"></textarea>
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
                                <div class="empty-state text-center py-5" id="empty-questions">
                                    <div class="empty-icon-container mb-3">
                                        <i class="fas fa-clipboard-list fa-3x text-muted"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">No Questions Added Yet</h5>
                                    <p class="text-muted">Drag question types from the sidebar or click the Add Question button</p>
                    </div>
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

            <!-- Templates -->
                <div class="card shadow-sm animate__animated animate__fadeInRight" style="animation-delay: 0.3s">
                    <div class="card-header bg-white p-3">
                        <h5 class="mb-0"><i class="fas fa-copy me-2 text-info"></i> Templates</h5>
                </div>
                    <div class="card-body p-0">
                    <?php if ($templates && $templates->num_rows > 0): ?>
                            <div class="list-group list-group-flush">
                            <?php while ($template = $templates->fetch_assoc()): ?>
                                    <a href="#" class="list-group-item list-group-item-action template-item p-3" 
                                data-template-id="<?php echo $template['id']; ?>">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($template['name']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($template['description']); ?>
                                    </small>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                            <div class="p-4 text-center">
                        <p class="text-muted mb-0">No templates available</p>
                            </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

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
                        <input type="checkbox" class="form-check-input required-toggle" id="required-<?php echo uniqid(); ?>">
                        <label class="form-check-label" for="required-<?php echo uniqid(); ?>">Required</label>
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

    <!-- Option Template (Hidden) -->
    <template id="option-template">
        <div class="option-row input-group mb-2">
            <span class="input-group-text">
                <i class="far fa-square option-type-icon"></i>
            </span>
            <input type="text" class="form-control option-text" placeholder="Option text">
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

    /* Template Item */
    .template-item {
        transition: all 0.3s ease;
        border-left: 3px solid transparent;
    }

    .template-item:hover {
        background-color: rgba(58, 92, 219, 0.05);
        border-left-color: #3a5cdb;
        transform: translateX(5px);
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

    <?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>

    <!-- Add the Sortable.js library -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>

    <!-- Include the survey builder JS -->
    <script src="<?php echo SITE_URL; ?>/assets/js/survey-builder.js"></script>

    <!-- Include the drag-drop fix script -->
    <script src="<?php echo SITE_URL; ?>/dashboard/survey-drag-drop-fix.js"></script>

    <!-- Load template data if available -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to add a question from template data
        window.addQuestionFromTemplate = function(questionData) {
            try {
                const questionType = questionData.type || questionData.question_type;
                if (!questionType) {
                    console.error('Question type missing', questionData);
                    return;
                }
                
                console.log('Adding question of type:', questionType);
                
                // Get the template element
                const template = document.getElementById('question-template');
                if (!template) {
                    console.error('Question template not found');
                    return;
                }
                
                // Clone the template content
                const questionBlock = document.importNode(template.content, true).querySelector('.question-block');
                if (!questionBlock) {
                    console.error('Failed to create question block from template');
                    return;
                }
                
                // Set question type
                questionBlock.dataset.questionType = questionType;
                
                // Update question type label
                const typeLabel = questionBlock.querySelector('.question-type-label');
                if (typeLabel) {
                    switch (questionType) {
                        case 'multiple_choice':
                            typeLabel.textContent = 'Multiple Choice';
                            break;
                        case 'single_choice':
                            typeLabel.textContent = 'Single Choice';
                            break;
                        case 'text':
                            typeLabel.textContent = 'Text Answer';
                            break;
                        case 'rating':
                            typeLabel.textContent = 'Rating';
                            break;
                        default:
                            typeLabel.textContent = questionType;
                    }
                }
                
                // Set question text
                const questionTextInput = questionBlock.querySelector('.question-text');
                if (questionTextInput) {
                    const questionText = questionData.text || questionData.question_text || '';
                    questionTextInput.value = questionText;
                }
                
                // Set required toggle
                const requiredToggle = questionBlock.querySelector('.required-toggle');
                if (requiredToggle) {
                    requiredToggle.checked = !!questionData.required;
                }
                
                // Handle options container visibility
                const optionsContainer = questionBlock.querySelector('.options-container');
                if (optionsContainer) {
                    if (questionType === 'text') {
                        optionsContainer.style.display = 'none';
                    } else {
                        optionsContainer.style.display = 'block';
                        
                        // Set the icon appropriate for the question type
                        const optionIconClass = questionType === 'multiple_choice' ? 'fa-square' : 'fa-circle';
                        
                        // Clear existing options
                        const optionsList = optionsContainer.querySelector('.options-list');
                        if (optionsList) {
                            optionsList.innerHTML = '';
                            
                            // Add options from template data
                            const options = questionData.options || [];
                            if (options.length > 0) {
                                options.forEach(function(optionText) {
                                    addOptionToList(optionsList, optionText, optionIconClass);
                                });
                            } else {
                                // Add default options if none provided
                                addOptionToList(optionsList, 'Option 1', optionIconClass);
                                addOptionToList(optionsList, 'Option 2', optionIconClass);
                            }
                        }
                        
                        // Set up the add option button
                        const addOptionBtn = optionsContainer.querySelector('.add-option');
                        if (addOptionBtn) {
                            addOptionBtn.addEventListener('click', function() {
                                addOptionToList(optionsList, '', optionIconClass);
                            });
                        }
                    }
                }
                
                // Set up delete question button
                const deleteBtn = questionBlock.querySelector('.delete-question');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        questionBlock.remove();
                        
                        // Show empty state if no questions left
                        const questionBlocks = document.querySelectorAll('.question-block');
                        if (questionBlocks.length === 0) {
                            document.getElementById('empty-questions').style.display = 'block';
                        }
                    });
                }
                
                // Add to question list
                const questionList = document.getElementById('sortable-questions');
                if (questionList) {
                    questionList.appendChild(questionBlock);
                    console.log('Question added to the list successfully');
                } else {
                    console.error('Question list container not found');
                }
            } catch (e) {
                console.error('Error adding question from template:', e);
            }
        }
        
        // Helper function to add an option to the options list
        function addOptionToList(optionsList, optionText, iconClass) {
            try {
                // Get option template
                const optionTemplate = document.getElementById('option-template');
                if (!optionTemplate) {
                    console.error('Option template not found');
                    return;
                }
                
                // Clone the template
                const optionRow = document.importNode(optionTemplate.content, true).querySelector('.option-row');
                if (!optionRow) {
                    console.error('Failed to create option row from template');
                    return;
                }
                
                // Set the option text
                const optionInput = optionRow.querySelector('.option-text');
                if (optionInput) {
                    optionInput.value = optionText;
                }
                
                // Set the correct icon
                const optionIcon = optionRow.querySelector('.option-type-icon');
                if (optionIcon) {
                    optionIcon.className = `far ${iconClass} option-type-icon`;
                }
                
                // Set up delete option button
                const deleteBtn = optionRow.querySelector('.delete-option');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function() {
                        optionRow.remove();
                    });
                }
                
                // Add to options list
                optionsList.appendChild(optionRow);
            } catch (e) {
                console.error('Error adding option:', e);
            }
        }
    
        // Template loading handling
        <?php if ($template_data): ?>
        try {
            console.log('Loading template: <?php echo htmlspecialchars($template_data['name']); ?>');
            // Set survey title and description
            document.getElementById('survey-title').value = '<?php echo addslashes(htmlspecialchars($template_data['name'])); ?>';
            document.getElementById('survey-description').value = '<?php echo addslashes(htmlspecialchars($template_data['description'])); ?>';
            
            // Parse template structure
            const structure = JSON.parse('<?php echo addslashes($template_data['structure']); ?>');
            
            if (structure && structure.questions && Array.isArray(structure.questions)) {
                // Remove empty state
                document.getElementById('empty-questions').style.display = 'none';
                
                // Add questions from template
                structure.questions.forEach(function(q) {
                    addQuestionFromTemplate(q);
                });
                
                console.log('Template loaded successfully with ' + structure.questions.length + ' questions');
            } else {
                console.error('Invalid template structure format');
                alert('The template structure is invalid. You can still create a survey from scratch.');
            }
        } catch (error) {
            console.error('Error loading template:', error);
            alert('Error loading template: ' + error.message + '\nYou can still create a survey from scratch.');
        }
        <?php endif; ?>
        
        // Hook up preview button
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
                
                // Prepare preview container
                const previewTitle = document.getElementById('previewTitle');
                const previewDescription = document.getElementById('previewDescription');
                const previewQuestions = document.getElementById('previewQuestions');
                
                if (!previewTitle || !previewDescription || !previewQuestions) {
                    console.error('Preview elements not found');
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
                
                // Show the modal
                const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                previewModal.show();
                
                // Set up event listener for when modal is hidden to ensure no element inside retains focus
                const modalElement = document.getElementById('previewModal');
                modalElement.addEventListener('hidden.bs.modal', function () {
                    // Move focus back to the preview button when modal is closed
                    document.getElementById('preview-survey').focus();
                });
            });
        }
        
        // Hook up save button
        const saveButton = document.getElementById('save-survey-btn');
        if (saveButton) {
            saveButton.addEventListener('click', function() {
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
                
                // Gather survey data
                const surveyData = {
                    title: title,
                    description: document.getElementById('survey-description').value.trim(),
                    questions: []
                };
                
                // Gather questions data
                questionBlocks.forEach(function(block, index) {
                    const questionText = block.querySelector('.question-text').value.trim();
                    if (!questionText) {
                        alert(`Question ${index + 1} is missing text. Please fill in all questions.`);
                        block.querySelector('.question-text').focus();
                        return;
                    }
                    
                    const questionType = block.dataset.questionType;
                    const isRequired = block.querySelector('.required-toggle').checked;
                    
                    const question = {
                        question_text: questionText,
                        question_type: questionType,
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
                                question.options.push(optionText);
                            }
                        });
                        
                        if (question.options.length < 2 && (questionType === 'multiple_choice' || questionType === 'single_choice')) {
                            alert(`Question ${index + 1} needs at least two options. Please add more options.`);
                            return;
                        }
                    }
                    
                    surveyData.questions.push(question);
                });
                
                // Send data to server
                fetch(`${window.location.origin}/dashboard/save-survey.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(surveyData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Survey saved successfully!');
                        window.location.href = `${window.location.origin}/dashboard`;
                    } else {
                        alert('Error saving survey: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error saving survey:', error);
                    alert('Error saving survey. Please try again.');
                });
            });
        }

        // Hook up template selector
        document.querySelectorAll('.template-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const templateId = this.dataset.templateId;
                if (templateId) {
                    window.location.href = `${window.location.origin}/dashboard/create-survey.php?template=${templateId}`;
                }
            });
        });
    });
    </script>

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
                        <h3 id="previewTitle" class="mb-3">Survey Title</h3>
                        <p id="previewDescription" class="text-muted mb-4"></p>
                        
                        <div id="previewQuestions">
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

    <?php require_once dirname(__DIR__) . '/templates/footer.php'; ?>