/**
 * Survey Creator Drag and Drop Fix
 * This script ensures that drag and drop functionality works correctly on the deployed site
 */
console.log('Survey drag-drop fix loaded');

// Set a flag that will be checked by other scripts to prevent duplicate initialization
window.surveyDragDropInitialized = true;

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure everything is properly loaded
    setTimeout(initializeSurveyBuilder, 100);
});

/**
 * Initialize all survey builder functionality
 */
function initializeSurveyBuilder() {
    console.log('Initializing survey builder...');
    
    // Initialize question sorting (drag to reorder)
    initializeQuestionSorting();
    
    // Initialize question type drag and drop
    initializeQuestionTypeDragDrop();
    
    // Initialize event handlers for dynamic elements
    initializeEventHandlers();
}

/**
 * Initialize Sortable.js for question reordering
 */
function initializeQuestionSorting() {
    const questionList = document.getElementById('sortable-questions');
    if (questionList) {
        // Check if Sortable is available
        if (typeof Sortable !== 'undefined') {
            new Sortable(questionList, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag'
            });
            console.log('Question sorting initialized');
        } else {
            console.error('Sortable.js library not found');
        }
    } else {
        console.log('Question list container not found');
    }
}

/**
 * Initialize drag and drop for question types
 */
function initializeQuestionTypeDragDrop() {
    // Find all draggable question type items
    const draggableItems = document.querySelectorAll('.question-type-item');
    const questionList = document.getElementById('sortable-questions');
    
    if (!draggableItems.length || !questionList) {
        console.log('Draggable items or question list not found');
        return;
    }
    
    console.log('Found draggable items:', draggableItems.length);
    
    // Remove any existing event listeners from draggable items
    draggableItems.forEach(function(item) {
        const newItem = item.cloneNode(true);
        item.parentNode.replaceChild(newItem, item);
        
        // Add dragstart event listener
        newItem.addEventListener('dragstart', function(e) {
            const type = this.dataset.type;
            console.log('Drag started:', type);
            e.dataTransfer.setData('text/plain', type);
        });
    });
    
    // Remove any existing drop zone event listeners
    const newQuestionList = questionList.cloneNode(false);
    while (questionList.firstChild) {
        newQuestionList.appendChild(questionList.firstChild);
    }
    questionList.parentNode.replaceChild(newQuestionList, questionList);
    
    // Set up drop zone event listeners
    newQuestionList.addEventListener('dragover', function(e) {
        e.preventDefault();
    });
    
    newQuestionList.addEventListener('drop', function(e) {
        e.preventDefault();
        
        const questionType = e.dataTransfer.getData('text/plain');
        console.log('Question type dropped:', questionType);
        
        if (questionType) {
            // Create a new question
            addQuestion(questionType);
            
            // Hide empty state if it's showing
            const emptyState = document.getElementById('empty-questions');
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        }
    });
    
    console.log('Question type drag and drop initialized');
}

/**
 * Add a new question of the specified type
 */
function addQuestion(questionType) {
    console.log('Adding question of type:', questionType);
    
    // Hide empty state if it exists
    const emptyState = document.getElementById('empty-questions');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
    
    // Get the question template
    const template = document.getElementById('question-template');
    if (!template) {
        console.error('Question template not found');
        return;
    }
    
    // Clone the template content
    const clone = document.importNode(template.content, true);
    const questionBlock = clone.querySelector('.question-block');
    
    if (!questionBlock) {
        console.error('Question block not found in template');
        return;
    }
    
    // Set the question type
    questionBlock.dataset.questionType = questionType;
    questionBlock.dataset.questionId = 'new_' + Date.now();
    
    // Set question type label
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
                typeLabel.textContent = 'Question';
        }
    }
    
    // Set up required toggle with unique ID
    const requiredToggle = questionBlock.querySelector('.required-toggle');
    if (requiredToggle) {
        const uniqueId = 'required-' + Date.now();
        requiredToggle.id = uniqueId;
        
        const label = questionBlock.querySelector('.form-check-label');
        if (label) {
            label.setAttribute('for', uniqueId);
        }
    }
    
    // Set up options for choice questions
    const optionsContainer = questionBlock.querySelector('.options-container');
    if (optionsContainer) {
        if (questionType === 'text') {
            optionsContainer.style.display = 'none';
        } else {
            optionsContainer.style.display = 'block';
            
            // Add default options for choice questions
            const optionsList = optionsContainer.querySelector('.options-list');
            if (optionsList) {
                // Clear any existing options
                optionsList.innerHTML = '';
                
                // Add default options
                if (questionType === 'multiple_choice' || questionType === 'single_choice') {
                    addOptionToList(optionsList, 'Option 1', questionType);
                    addOptionToList(optionsList, 'Option 2', questionType);
                } else if (questionType === 'rating') {
                    addOptionToList(optionsList, '1', questionType);
                    addOptionToList(optionsList, '2', questionType);
                    addOptionToList(optionsList, '3', questionType);
                    addOptionToList(optionsList, '4', questionType);
                    addOptionToList(optionsList, '5', questionType);
                }
            }
        }
    }
    
    // Add the question to the container
    const questionList = document.getElementById('sortable-questions');
    if (questionList) {
        questionList.appendChild(questionBlock);
    } else {
        console.error('Question list container not found');
    }
}

/**
 * Add an option to the options list
 */
function addOptionToList(optionsList, optionText, questionType) {
    // Get the option template
    const optionTemplate = document.getElementById('option-template');
    if (!optionTemplate) {
        // Create an option manually if template doesn't exist
        const optionItem = document.createElement('div');
        optionItem.className = 'option-item input-group mb-2';
        
        let iconClass = questionType === 'multiple_choice' ? 'fa-check-square' : 
                       (questionType === 'rating' ? 'fa-star' : 'fa-dot-circle');
        
        optionItem.innerHTML = `
            <span class="input-group-text">
                <i class="fas ${iconClass}"></i>
            </span>
            <input type="text" class="form-control option-text" value="${optionText}" placeholder="Option">
            <button class="btn btn-outline-danger delete-option" type="button">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        optionsList.appendChild(optionItem);
        return;
    }
    
    // Clone the template
    const clone = document.importNode(optionTemplate.content, true);
    const optionRow = clone.querySelector('.option-row');
    
    if (!optionRow) {
        console.error('Option row not found in template');
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
        if (questionType === 'multiple_choice') {
            optionIcon.className = 'fas fa-check-square option-type-icon';
        } else if (questionType === 'rating') {
            optionIcon.className = 'fas fa-star option-type-icon';
        } else {
            optionIcon.className = 'fas fa-dot-circle option-type-icon';
        }
    }
    
    // Add to the options list
    optionsList.appendChild(optionRow);
}

/**
 * Initialize event handlers for dynamic elements
 */
function initializeEventHandlers() {
    // Handle add question button
    const addQuestionBtn = document.getElementById('add-question');
    if (addQuestionBtn) {
        // Remove existing event listeners
        const newAddBtn = addQuestionBtn.cloneNode(true);
        addQuestionBtn.parentNode.replaceChild(newAddBtn, addQuestionBtn);
        
        newAddBtn.addEventListener('click', function() {
            // Default to text question when clicking add question
            addQuestion('text');
        });
    }
    
    // Use event delegation for dynamically added elements
    document.addEventListener('click', function(e) {
        // Delete question handler
        if (e.target.closest('.delete-question')) {
            const questionBlock = e.target.closest('.question-block');
            if (questionBlock) {
                questionBlock.remove();
                
                // Show empty state if no questions remain
                const questionBlocks = document.querySelectorAll('.question-block');
                if (questionBlocks.length === 0) {
                    const emptyState = document.getElementById('empty-questions');
                    if (emptyState) {
                        emptyState.style.display = 'block';
                    }
                }
            }
        }
        
        // Add option handler
        if (e.target.closest('.add-option')) {
            const addOptionBtn = e.target.closest('.add-option');
            const questionBlock = addOptionBtn.closest('.question-block');
            const questionType = questionBlock.dataset.questionType;
            const optionsList = questionBlock.querySelector('.options-list');
            
            if (optionsList) {
                const optionCount = optionsList.querySelectorAll('.option-item, .option-row').length + 1;
                addOptionToList(optionsList, `Option ${optionCount}`, questionType);
            }
        }
        
        // Delete option handler
        if (e.target.closest('.delete-option')) {
            const optionItem = e.target.closest('.option-item') || e.target.closest('.option-row');
            if (optionItem) {
                optionItem.remove();
            }
        }
    });
    
    console.log('Event handlers initialized');
}

// Define global function that can be called from other scripts
window.addQuestionToSurvey = function(questionText, questionType, isRequired = false, options = []) {
    addQuestion(questionType);
    
    // Find the last added question and update its properties
    const questions = document.querySelectorAll('.question-block');
    if (questions.length > 0) {
        const lastQuestion = questions[questions.length - 1];
        
        // Set question text
        const questionInput = lastQuestion.querySelector('.question-text');
        if (questionInput && questionText) {
            questionInput.value = questionText;
        }
        
        // Set required toggle
        const requiredToggle = lastQuestion.querySelector('.required-toggle');
        if (requiredToggle) {
            requiredToggle.checked = isRequired;
        }
        
        // Add options if provided
        if (options.length > 0) {
            const optionsList = lastQuestion.querySelector('.options-list');
            if (optionsList) {
                // Clear default options
                optionsList.innerHTML = '';
                
                // Add provided options
                options.forEach(function(optionText) {
                    addOptionToList(optionsList, optionText, questionType);
                });
            }
        }
    }
}; 