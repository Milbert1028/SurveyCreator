/**
 * SurveyCreator main.js
 * Consolidated script for survey creation functionality
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('SurveyCreator main.js loaded');
    initializeSurveyBuilder();
});

function initializeSurveyBuilder() {
    // Initialize drag and drop
    initializeDragAndDrop();
    
    // Initialize question buttons
    initializeQuestionButtons();
    
    // Initialize save functionality
    initializeSaveButton();
}

/**
 * Initialize drag and drop functionality
 */
function initializeDragAndDrop() {
    const questionTypeItems = document.querySelectorAll('.question-types .list-group-item[draggable="true"]');
    const questionList = document.getElementById('sortable-questions');
    
    if (!questionList) {
        console.warn('Question list container not found');
        return;
    }
    
    console.log('Initializing drag and drop with', questionTypeItems.length, 'question types');
    
    // Make question types draggable
    questionTypeItems.forEach(item => {
        item.addEventListener('dragstart', function(e) {
            console.log('Drag started for', this.textContent.trim());
            e.dataTransfer.setData('text/plain', this.dataset.type);
            this.classList.add('dragging');
        });
        
        item.addEventListener('dragend', function() {
            this.classList.remove('dragging');
        });
    });
    
    // Make the question list a drop zone
    questionList.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('dragover');
    });
    
    questionList.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('dragover');
    });
    
    questionList.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
        
        const questionType = e.dataTransfer.getData('text/plain');
        console.log('Question type dropped:', questionType);
        
        if (questionType) {
            addQuestionToSurvey('', questionType, false, []);
        }
    });
    
    // Make existing questions sortable
    try {
        new Sortable(questionList, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function(evt) {
                console.log('Question reordered');
            }
        });
    } catch (e) {
        console.error('Error initializing Sortable:', e);
    }
}

/**
 * Initialize question-related buttons
 */
function initializeQuestionButtons() {
    // Add Question button
    const addQuestionBtn = document.getElementById('add-question');
    if (addQuestionBtn) {
        addQuestionBtn.addEventListener('click', function() {
            console.log('Add question button clicked');
            addQuestionToSurvey('', 'multiple_choice', false, []);
        });
    } else {
        console.warn('Add Question button not found');
    }
    
    // Global event delegation for dynamic elements
    document.addEventListener('click', function(e) {
        // Delete question button
        if (e.target.classList.contains('delete-question') || e.target.closest('.delete-question')) {
            const questionBlock = e.target.closest('.question-block');
            if (questionBlock && confirm('Are you sure you want to delete this question?')) {
                questionBlock.remove();
                
                // Show empty state if no questions left
                const questionList = document.getElementById('sortable-questions');
                if (questionList && questionList.querySelectorAll('.question-block').length === 0) {
                    const emptyState = document.createElement('div');
                    emptyState.className = 'empty-state text-center py-5';
                    emptyState.id = 'empty-questions';
                    emptyState.innerHTML = '<i class="fas fa-clipboard fa-3x text-muted mb-3"></i><p class="lead">Drag questions here or click Add Question</p>';
                    questionList.appendChild(emptyState);
                }
            }
        }
        
        // Add option button
        if (e.target.classList.contains('add-option') || e.target.closest('.add-option')) {
            const optionsContainer = e.target.closest('.options-container');
            if (optionsContainer) {
                const optionsList = optionsContainer.querySelector('.options-list');
                const questionBlock = e.target.closest('.question-block');
                const questionType = questionBlock ? questionBlock.dataset.questionType : 'multiple_choice';
                
                let newOption;
                if (questionType === 'rating') {
                    const existingOptions = optionsList.querySelectorAll('.option-item');
                    const nextValue = existingOptions.length + 1;
                    newOption = createRatingOption(nextValue);
                } else {
                    newOption = createChoiceOption(questionType, '');
                }
                
                if (newOption && optionsList) {
                    optionsList.appendChild(newOption);
                }
            }
        }
        
        // Delete option button
        if (e.target.classList.contains('delete-option') || e.target.closest('.delete-option')) {
            const optionItem = e.target.closest('.option-item') || e.target.closest('.option-row');
            if (optionItem) {
                optionItem.remove();
            }
        }
    });
}

/**
 * Initialize save survey button
 */
function initializeSaveButton() {
    const saveBtn = document.getElementById('save-survey-btn');
    if (!saveBtn) {
        console.warn('Save Survey button not found');
        return;
    }
    
    saveBtn.addEventListener('click', function(e) {
        e.preventDefault();
        console.log('Save survey button clicked');
        
        // Show loading state
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        this.disabled = true;
        
        // Get survey title and description
        const title = document.getElementById('survey-title').value.trim();
        const description = document.getElementById('survey-description').value.trim();
        
        if (!title) {
            alert('Please enter a survey title');
            this.innerHTML = '<i class="fas fa-save"></i> Save Survey';
            this.disabled = false;
            return;
        }
        
        // Get all questions
        const questionBlocks = document.querySelectorAll('#sortable-questions .question-block');
        if (questionBlocks.length === 0) {
            alert('Please add at least one question to your survey');
            this.innerHTML = '<i class="fas fa-save"></i> Save Survey';
            this.disabled = false;
            return;
        }
        
        // Prepare questions array
        const questions = [];
        questionBlocks.forEach(function(block, index) {
            // Get question text and type
            const questionText = block.querySelector('.question-text').value.trim();
            const questionType = block.dataset.questionType;
            
            // Get required status
            const requiredToggle = block.querySelector('.required-toggle');
            const isRequired = requiredToggle && requiredToggle.checked;
            
            // Get options if applicable
            let options = [];
            
            if (questionType !== 'text') {
                const optionInputs = block.querySelectorAll('.option-text');
                optionInputs.forEach(function(input) {
                    if (input.value.trim()) {
                        options.push(input.value.trim());
                    }
                });
            }
            
            questions.push({
                question: questionText || 'Untitled Question',
                type: questionType,
                required: isRequired,
                options: options
            });
        });
        
        // Prepare survey data
        const surveyData = {
            title: title,
            description: description,
            questions: questions
        };
        
        console.log('Saving survey with data:', surveyData);
        
        // Determine API URL
        let apiUrl;
        if (typeof SITE_URL !== 'undefined' && SITE_URL) {
            apiUrl = SITE_URL + '/api/save-survey.php';
        } else {
            // Try to build relative URL
            const pathParts = window.location.pathname.split('/');
            // Remove the last two parts (dashboard/create-survey.php)
            pathParts.splice(-2, 2);
            const basePath = pathParts.join('/');
            apiUrl = window.location.origin + basePath + '/api/save-survey.php';
        }
        
        console.log('Using API URL:', apiUrl);
        
        // Send data to server
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(surveyData)
        })
        .then(response => {
            console.log('Server response:', response);
            if (!response.ok) {
                throw new Error('Server returned ' + response.status + ' ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('Survey saved successfully:', data);
            
            if (data.success) {
                alert('Survey saved successfully!');
                // Redirect to dashboard instead of view-surveys page
                window.location.href = SITE_URL + '/dashboard/index.php';
            } else {
                throw new Error(data.message || 'Failed to save survey');
            }
        })
        .catch(error => {
            console.error('Error saving survey:', error);
            alert('Error saving survey: ' + error.message);
            
            // Reset button
            saveBtn.innerHTML = '<i class="fas fa-save"></i> Save Survey';
            saveBtn.disabled = false;
        });
    });
}

/**
 * Add a new question to the survey
 */
function addQuestionToSurvey(questionText = '', questionType = 'multiple_choice', isRequired = false, options = []) {
    console.log('Adding question:', { text: questionText, type: questionType, required: isRequired, options: options });
    
    const questionList = document.getElementById('sortable-questions');
    if (!questionList) {
        console.error('Question list container not found');
        return null;
    }
    
    // Remove empty state if it exists
    const emptyState = document.getElementById('empty-questions');
    if (emptyState) {
        emptyState.remove();
    }
    
    // Get template
    const template = document.getElementById('question-template');
    if (!template) {
        console.error('Question template not found');
        return null;
    }
    
    // Clone template
    const questionBlock = template.content.cloneNode(true).querySelector('.question-block');
    
    // Set question type
    questionBlock.dataset.questionType = questionType;
    
    // Set question type label
    const typeLabel = questionBlock.querySelector('.question-type-label');
    if (typeLabel) {
        const labels = {
            'text': 'Text',
            'multiple_choice': 'Multiple Choice',
            'single_choice': 'Single Choice',
            'rating': 'Rating'
        };
        typeLabel.textContent = labels[questionType] || 'Unknown Type';
    }
    
    // Set question text
    const questionInput = questionBlock.querySelector('.question-text');
    if (questionInput) {
        questionInput.value = questionText;
    }
    
    // Set required status
    const requiredToggle = questionBlock.querySelector('.required-toggle');
    if (requiredToggle) {
        requiredToggle.checked = isRequired;
    }
    
    // Handle options
    const optionsContainer = questionBlock.querySelector('.options-container');
    if (optionsContainer) {
        // Show/hide options based on question type
        if (questionType === 'text') {
            optionsContainer.style.display = 'none';
        } else {
            optionsContainer.style.display = 'block';
            
            // Add options
            const optionsList = optionsContainer.querySelector('.options-list');
            if (optionsList) {
                // Clear existing options
                optionsList.innerHTML = '';
                
                // Add specified options if any
                if (options && options.length > 0) {
                    options.forEach(function(optionText) {
                        if (questionType === 'rating') {
                            optionsList.appendChild(createRatingOption(optionText));
                        } else {
                            optionsList.appendChild(createChoiceOption(questionType, optionText));
                        }
                    });
                } else {
                    // Add default options
                    if (questionType === 'rating') {
                        for (let i = 1; i <= 5; i++) {
                            optionsList.appendChild(createRatingOption(i));
                        }
                    } else {
                        optionsList.appendChild(createChoiceOption(questionType, 'Option 1'));
                        optionsList.appendChild(createChoiceOption(questionType, 'Option 2'));
                    }
                }
            }
        }
    }
    
    // Add to question list
    questionList.appendChild(questionBlock);
    return questionBlock;
}

/**
 * Create a choice option element
 */
function createChoiceOption(questionType, optionText) {
    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-item input-group mb-2';
    
    // Create icon based on question type
    const icon = questionType === 'multiple_choice' ? 'far fa-square' : 'far fa-circle';
    
    optionDiv.innerHTML = `
        <span class="input-group-text">
            <i class="${icon}"></i>
        </span>
        <input type="text" class="form-control option-text" placeholder="Option text" value="${optionText}">
        <button type="button" class="btn btn-outline-danger delete-option">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    return optionDiv;
}

/**
 * Create a rating option element
 */
function createRatingOption(value) {
    const optionDiv = document.createElement('div');
    optionDiv.className = 'option-item input-group mb-2';
    
    optionDiv.innerHTML = `
        <span class="input-group-text">
            <i class="fas fa-star"></i>
        </span>
        <input type="text" class="form-control option-text" placeholder="Rating value" value="${value}">
        <button type="button" class="btn btn-outline-danger delete-option">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    return optionDiv;
}

/**
 * Preview the survey
 */
function previewSurvey() {
    const previewModal = document.getElementById('previewModal');
    if (!previewModal) {
        console.error('Preview modal not found');
        return;
    }
    
    const title = document.getElementById('survey-title').value.trim() || 'Untitled Survey';
    const description = document.getElementById('survey-description').value.trim();
    
    // Set title and description
    const previewTitle = previewModal.querySelector('#preview-title');
    const previewDescription = previewModal.querySelector('#preview-description');
    
    if (previewTitle) previewTitle.textContent = title;
    if (previewDescription) previewDescription.textContent = description;
    
    // Get questions container
    const previewQuestions = previewModal.querySelector('#preview-questions');
    if (!previewQuestions) {
        console.error('Preview questions container not found');
        return;
    }
    
    // Clear previous questions
    previewQuestions.innerHTML = '';
    
    // Add each question
    const questionBlocks = document.querySelectorAll('#sortable-questions .question-block');
    questionBlocks.forEach(function(block, index) {
        const questionText = block.querySelector('.question-text').value.trim() || 'Untitled Question';
        const questionType = block.dataset.questionType;
        const isRequired = block.querySelector('.required-toggle')?.checked;
        
        // Create question container
        const questionDiv = document.createElement('div');
        questionDiv.className = 'mb-4 preview-question';
        
        // Add question text
        const questionLabel = document.createElement('label');
        questionLabel.className = 'form-label fw-bold';
        questionLabel.innerHTML = `${index + 1}. ${questionText} ${isRequired ? '<span class="text-danger">*</span>' : ''}`;
        questionDiv.appendChild(questionLabel);
        
        // Add appropriate input based on question type
        if (questionType === 'multiple_choice') {
            // Get options
            const options = Array.from(block.querySelectorAll('.option-text')).map(input => input.value.trim()).filter(Boolean);
            
            // Create checkboxes
            options.forEach(function(option, optIndex) {
                const checkDiv = document.createElement('div');
                checkDiv.className = 'form-check';
                checkDiv.innerHTML = `
                    <input class="form-check-input" type="checkbox" id="preview-q${index}-opt${optIndex}">
                    <label class="form-check-label" for="preview-q${index}-opt${optIndex}">${option}</label>
                `;
                questionDiv.appendChild(checkDiv);
            });
        } else if (questionType === 'single_choice') {
            // Get options
            const options = Array.from(block.querySelectorAll('.option-text')).map(input => input.value.trim()).filter(Boolean);
            
            // Create radio buttons
            options.forEach(function(option, optIndex) {
                const radioDiv = document.createElement('div');
                radioDiv.className = 'form-check';
                radioDiv.innerHTML = `
                    <input class="form-check-input" type="radio" name="preview-q${index}" id="preview-q${index}-opt${optIndex}">
                    <label class="form-check-label" for="preview-q${index}-opt${optIndex}">${option}</label>
                `;
                questionDiv.appendChild(radioDiv);
            });
        } else if (questionType === 'rating') {
            // Create rating scale
            const ratingDiv = document.createElement('div');
            ratingDiv.className = 'd-flex align-items-center gap-2';
            
            // Get rating values
            const ratings = Array.from(block.querySelectorAll('.option-text')).map(input => input.value.trim()).filter(Boolean);
            
            // Create stars
            ratings.forEach(function(rating) {
                const starBtn = document.createElement('button');
                starBtn.type = 'button';
                starBtn.className = 'btn btn-outline-warning';
                starBtn.innerHTML = `<i class="fas fa-star"></i><span class="d-block">${rating}</span>`;
                ratingDiv.appendChild(starBtn);
            });
            
            questionDiv.appendChild(ratingDiv);
        } else if (questionType === 'text') {
            // Create textarea
            const textarea = document.createElement('textarea');
            textarea.className = 'form-control';
            textarea.rows = 3;
            textarea.placeholder = 'Your answer';
            questionDiv.appendChild(textarea);
        }
        
        // Add to preview
        previewQuestions.appendChild(questionDiv);
    });
    
    // Show the modal
    const bsModal = new bootstrap.Modal(previewModal);
    bsModal.show();
}

// Make functions globally available
window.addQuestionToSurvey = addQuestionToSurvey;
window.createChoiceOption = createChoiceOption;
window.createRatingOption = createRatingOption;
window.previewSurvey = previewSurvey;
