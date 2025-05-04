document.addEventListener('DOMContentLoaded', () => {
    // Handle template selection
    document.querySelectorAll('.template-item').forEach(item => {
        item.addEventListener('click', async (e) => {
            e.preventDefault();
            const templateId = item.getAttribute('data-template-id');
            console.log(`Template ID selected: ${templateId}`); // Debug log

            try {
                const response = await fetch(`/survey/api/templates.php?id=${templateId}`);
                const template = await response.json();
                console.log('Template data received:', template); // Debug log

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
