class SurveyBuilder {
    constructor() {
        console.log("SurveyBuilder initialized");
    }

    addQuestion(questionText, questionType, required) {
        const questionList = document.getElementById('sortable-questions');
        const questionBlock = document.getElementById('question-template').content.cloneNode(true);
        questionBlock.querySelector('.question-text').value = questionText;
        questionBlock.querySelector('.question-type').value = questionType;
        questionBlock.querySelector('.question-required').checked = required;

        // Append question block to the list
        questionList.appendChild(questionBlock);
    }

    removeQuestion(questionId) {
        const questionBlock = document.querySelector(`.question-block[data-question-id="${questionId}"]`);
        if (questionBlock) {
            questionBlock.remove();
            console.log(`Removed question with ID: ${questionId}`);
        }
    }

    saveSurvey(title, description) {
        const questions = [];
        document.querySelectorAll('.question-block').forEach(questionBlock => {
            const questionText = questionBlock.querySelector('.question-text').value;
            const questionType = questionBlock.querySelector('.question-type').value;
            const required = questionBlock.querySelector('.question-required').checked;
            questions.push({ questionText, questionType, required });
        });

        // Send data to the server
        fetch('/survey/api/surveys.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title, description, questions })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Survey saved successfully!');
            } else {
                alert('Error saving survey: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to save survey: ' + error.message);
        });
    }

    previewSurvey() {
        const title = document.getElementById('survey-title').value;
        const description = document.getElementById('survey-description').value;
        const previewContainer = document.getElementById('preview-container');
        previewContainer.querySelector('#preview-title').innerText = title;
        previewContainer.querySelector('#preview-description').innerText = description;

        const previewQuestions = previewContainer.querySelector('#preview-questions');
        previewQuestions.innerHTML = ''; // Clear previous questions

        document.querySelectorAll('.question-block').forEach(questionBlock => {
            const questionText = questionBlock.querySelector('.question-text').value;
            const questionType = questionBlock.querySelector('.question-type').value;
            const questionElement = document.createElement('div');
            questionElement.classList.add('preview-question');
            questionElement.innerHTML = `<strong>${questionText}</strong> (${questionType})`;

            // Add choices if applicable
            if (questionType === 'multiple_choice' || questionType === 'single_choice') {
                const optionsContainer = document.createElement('div');
                optionsContainer.classList.add('preview-options');
                questionBlock.querySelectorAll('.option-item input').forEach(option => {
                    const optionElement = document.createElement('div');
                    optionElement.innerText = option.value;
                    optionsContainer.appendChild(optionElement);
                });
                questionElement.appendChild(optionsContainer);
            }

            previewQuestions.appendChild(questionElement);
        });
    }

    loadTemplate(templateId) {
        fetch(`/survey/api/templates.php?id=${templateId}`)
            .then(response => {
                if (!response.ok) {
                    if (response.status === 404) {
                        throw new Error('Template not found');
                    }
                    throw new Error('Failed to load template');
                }
                return response.json();
            })
            .then(template => {
                if (template.success) {
                    const questions = JSON.parse(template.structure).questions;
                    questions.forEach(question => {
                        this.addQuestion(question.text, question.type, question.required);
                    });
                } else {
                    console.error('Error loading template:', template.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading template: ' + error.message);
            });
    }
}
document.addEventListener('DOMContentLoaded', () => {
    const surveyBuilder = new SurveyBuilder();

    // Handle template selection
    document.querySelectorAll('.template-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const templateId = item.getAttribute('data-template-id');

            try {
                const response = await fetch(`/survey/api/templates.php?id=${templateId}`);
                if (!response.ok) {
                    if (response.status === 404) {
                        throw new Error('Template not found');
                    }
                    throw new Error('Failed to load template');
                }
                const template = await response.json();

                if (response.ok) {
                    // Clear existing questions
                    const questionList = document.getElementById('sortable-questions');
                    questionList.innerHTML = '';

                    // Populate questions from the template
                    const questions = JSON.parse(template.structure).questions;
                    questions.forEach((question) => {
                        const questionBlock = document.getElementById('question-template').content.cloneNode(true);
                        questionBlock.querySelector('.question-text').value = question.text;
                        questionBlock.querySelector('.question-type').value = question.type;
                        questionBlock.querySelector('.question-required').checked = question.required;

                        // Append question block to the list
                        questionList.appendChild(questionBlock);
                    });

                    // Show template questions section
                    document.querySelector('.template-questions').style.display = 'block';
                } else {
                    throw new Error(template.error || 'Failed to load template');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load template: ' + error.message);
            }
        });
    });
});
// Implement drag-and-drop functionality
const sortableQuestions = document.getElementById('sortable-questions');
new Sortable(sortableQuestions, {
    handle: '.drag-handle', // Ensure this class is present in your question blocks
    animation: 150,
    onEnd: function (evt) {
        console.log('Question moved:', evt);
    }
});

// Save Survey Button functionality
document.getElementById('save-survey').addEventListener('click', () => {
    const title = document.getElementById('survey-title').value;
    const description = document.getElementById('survey-description').value;
    const questions = [];

    // Collect questions
    document.querySelectorAll('.question-block').forEach(questionBlock => {
        const questionText = questionBlock.querySelector('.question-text').value;
        const questionType = questionBlock.querySelector('.question-type').value;
        const required = questionBlock.querySelector('.question-required').checked;
        questions.push({ questionText, questionType, required });
    });
// Make question types draggable
document.querySelectorAll('.list-group-item').forEach(item => {
    item.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', item.getAttribute('data-type'));
    });
});

// Allow dropping in the question list area
const questionList = document.getElementById('sortable-questions');
questionList.addEventListener('dragover', (e) => {
    e.preventDefault(); // Prevent default to allow drop
});

// Handle drop event
questionList.addEventListener('drop', (e) => {
    e.preventDefault();
    const questionType = e.dataTransfer.getData('text/plain');
    addQuestionToSurvey('', questionType, true); // Add a new question with the dragged type
});
    // Send data to the server
    fetch('/survey/api/surveys.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ title, description, questions })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Survey saved successfully!');
            // Optionally redirect or clear the form
        } else {
            alert('Error saving survey: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save survey: ' + error.message);
    });
});

