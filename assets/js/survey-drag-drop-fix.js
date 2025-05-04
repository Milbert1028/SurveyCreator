document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded - initializing drag and drop...');
    
    // Prevent duplicate initialization
    if (window.dragDropInitialized) {
        console.log('Drag and drop already initialized, skipping duplicate initialization');
        return;
    }
    window.dragDropInitialized = true;
    
    // Wait a moment to ensure other scripts have loaded
    setTimeout(function() {
        initializeDragDrop();
    }, 500);
});

function initializeDragDrop() {
    // Initialize the sortable questions container
    if (typeof Sortable !== 'undefined' && document.getElementById('sortable-questions')) {
        console.log('Initializing Sortable for questions...');
        try {
            Sortable.create(document.getElementById('sortable-questions'), {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'bg-light'
            });
            console.log('Sortable initialized successfully');
        } catch (e) {
            console.error('Error initializing Sortable:', e);
        }
    } else {
        console.log('Sortable not available or sortable-questions not found');
    }
    
    // Make sure question types are draggable
    setupDraggableQuestionTypes();
}

// Setup draggable question types
function setupDraggableQuestionTypes() {
    console.log('Setting up draggable question types...');
    
    // Get all question type cards
    const questionTypes = document.querySelectorAll('.question-type-card');
    
    // Check if event listeners already added
    if (questionTypes.length > 0 && !questionTypes[0].dataset.dragInitialized) {
        console.log('Adding drag event listeners to question types...');
        
        questionTypes.forEach(type => {
            // Mark as initialized to prevent duplicate listeners
            type.dataset.dragInitialized = 'true';
            
            // Add drag start event
            type.addEventListener('dragstart', function(e) {
                console.log('Drag started for question type:', this.dataset.questionType);
                e.dataTransfer.setData('questionType', this.dataset.questionType);
            });
        });
        
        // Set up drop zone
        const sortableQuestions = document.getElementById('sortable-questions');
        if (sortableQuestions) {
            // Only add event if not already initialized
            if (!sortableQuestions.dataset.dropInitialized) {
                sortableQuestions.dataset.dropInitialized = 'true';
                
                sortableQuestions.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });
                
                sortableQuestions.addEventListener('dragleave', function(e) {
                    this.classList.remove('dragover');
                });
                
                sortableQuestions.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                    
                    try {
                        // Get dropped question type
                        const questionType = e.dataTransfer.getData('questionType');
                        console.log('Question type dropped:', questionType);
                        
                        if (!questionType) {
                            console.error('No question type data found in drop event');
                            return;
                        }
                        
                        // Remove empty state if it exists
                        const emptyState = this.querySelector('.empty-state');
                        if (emptyState) {
                            emptyState.remove();
                        }
                        
                        // Check for addQuestionToSurvey in different scopes
                        const addQuestion = window.addQuestionToSurvey || 
                                          (typeof addQuestionToSurvey === 'function' ? addQuestionToSurvey : null);
                        
                        if (addQuestion) {
                            console.log('Using ' + (window.addQuestionToSurvey ? 'window.' : '') + 'addQuestionToSurvey');
                            
                            // Create default options based on question type
                            let defaultOptions = [];
                            
                            if (questionType === 'multiple_choice' || questionType === 'single_choice') {
                                defaultOptions = ['Option 1', 'Option 2'];
                            } else if (questionType === 'rating') {
                                defaultOptions = ['1', '2', '3', '4', '5'];
                            }
                            
                            // Add the question to the survey
                            addQuestion('', questionType, false, defaultOptions);
                        } else {
                            console.error('addQuestionToSurvey function not found in any scope');
                            alert('Could not add question: The required function is not available. Please refresh the page and try again.');
                        }
                    } catch (error) {
                        console.error('Error during drop processing:', error);
                        alert('An error occurred while adding the question: ' + error.message);
                    }
                });
            }
        }
    } else {
        console.log('No question types found or already initialized');
    }
} 