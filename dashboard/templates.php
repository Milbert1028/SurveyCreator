<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to access templates", "warning");
    redirect('/auth/login.php');
}

// Get available templates
try {
    $db = Database::getInstance();
    $templates = $db->query("SELECT * FROM templates ORDER BY name");
    
    if (!$templates) {
        throw new Exception("Database error: " . $db->getLastError());
    }
} catch (Exception $e) {
    error_log("Database error in templates.php: " . $e->getMessage());
    flash_message("Error loading templates: " . $e->getMessage(), "danger");
    redirect('/error.php?code=500&message=' . urlencode("Database connection error"));
}

// Insert default templates if none exist
if ($templates->num_rows === 0) {
    $default_templates = [
        [
            'name' => 'Customer Satisfaction',
            'description' => 'Gather feedback about your products or services',
            'structure' => json_encode([
                'questions' => [
                    [
                        'text' => 'How satisfied are you with our product/service?',
                        'type' => 'rating',
                        'required' => true
                    ],
                    [
                        'text' => 'What aspects of our product/service do you like the most?',
                        'type' => 'multiple_choice',
                        'required' => true,
                        'options' => ['Quality', 'Price', 'Customer Service', 'Features', 'Ease of Use']
                    ],
                    [
                        'text' => 'Would you recommend our product/service to others?',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['Definitely', 'Probably', 'Not Sure', 'Probably Not', 'Definitely Not']
                    ],
                    [
                        'text' => 'How can we improve our product/service?',
                        'type' => 'text',
                        'required' => false
                    ]
                ]
            ])
        ],
        [
            'name' => 'Event Feedback',
            'description' => 'Collect feedback from event attendees',
            'structure' => json_encode([
                'questions' => [
                    [
                        'text' => 'How would you rate the overall event?',
                        'type' => 'rating',
                        'required' => true
                    ],
                    [
                        'text' => 'Which sessions did you attend?',
                        'type' => 'multiple_choice',
                        'required' => true,
                        'options' => ['Keynote', 'Workshops', 'Networking', 'Panel Discussions']
                    ],
                    [
                        'text' => 'Would you attend this event again?',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['Yes', 'Maybe', 'No']
                    ],
                    [
                        'text' => 'What suggestions do you have for future events?',
                        'type' => 'text',
                        'required' => false
                    ]
                ]
            ])
        ],
        [
            'name' => 'Market Research',
            'description' => 'Research market trends and consumer preferences',
            'structure' => json_encode([
                'questions' => [
                    [
                        'text' => 'Which age group do you belong to?',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['18-24', '25-34', '35-44', '45-54', '55+']
                    ],
                    [
                        'text' => 'What factors influence your purchasing decisions?',
                        'type' => 'multiple_choice',
                        'required' => true,
                        'options' => ['Price', 'Quality', 'Brand', 'Reviews', 'Recommendations']
                    ],
                    [
                        'text' => 'How often do you purchase similar products?',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['Weekly', 'Monthly', 'Quarterly', 'Yearly']
                    ],
                    [
                        'text' => 'What improvements would you like to see in this product category?',
                        'type' => 'text',
                        'required' => false
                    ]
                ]
            ])
        ],
        [
            'name' => 'Academic Research',
            'description' => 'Collect data for academic studies and research projects',
            'structure' => json_encode([
                'questions' => [
                    [
                        'text' => 'Informed Consent: I understand that my participation is voluntary and my responses will be used for academic research purposes only.',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['I agree to participate', 'I do not wish to participate']
                    ],
                    [
                        'text' => 'What is your highest level of education?',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['High School', 'Some College', 'Bachelor\'s Degree', 'Master\'s Degree', 'Doctoral Degree', 'Other']
                    ],
                    [
                        'text' => 'How familiar are you with the research topic?',
                        'type' => 'rating',
                        'required' => true
                    ],
                    [
                        'text' => 'Which of the following resources have you used in your studies? (Select all that apply)',
                        'type' => 'multiple_choice',
                        'required' => true,
                        'options' => ['Academic Journals', 'Textbooks', 'Online Courses', 'Research Databases', 'Expert Interviews', 'Field Studies']
                    ],
                    [
                        'text' => 'Rate your agreement with the following statement: "Academic research is essential for societal progress."',
                        'type' => 'single_choice',
                        'required' => true,
                        'options' => ['Strongly Agree', 'Agree', 'Neutral', 'Disagree', 'Strongly Disagree']
                    ],
                    [
                        'text' => 'What challenges have you faced in conducting or participating in academic research?',
                        'type' => 'text',
                        'required' => false
                    ],
                    [
                        'text' => 'Would you be willing to participate in a follow-up study?',
                        'type' => 'single_choice',
                        'required' => false,
                        'options' => ['Yes', 'No', 'Maybe']
                    ]
                ]
            ])
        ]
    ];

    foreach ($default_templates as $template) {
        $name = $db->escape($template['name']);
        $description = $db->escape($template['description']);
        $structure = $db->escape($template['structure']);
        
        $db->query("INSERT INTO templates (name, description, structure) 
                   VALUES ('$name', '$description', '$structure')");
    }

    // Refresh templates list
    $templates = $db->query("SELECT * FROM templates ORDER BY name");
}

require_once '../templates/header.php';
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Survey Templates</h1>
        <p class="text-muted">Choose a template to start your survey quickly</p>
    </div>
</div>

<div class="row">
    <?php while ($template = $templates->fetch_assoc()): ?>
        <div class="col-md-6 col-lg-4 mb-4">
            <div class="card template-card h-100">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($template['name']); ?></h5>
                    <p class="card-text text-muted">
                        <?php echo htmlspecialchars($template['description']); ?>
                    </p>
                    <div class="mt-3">
                        <button class="btn btn-primary use-template" 
                                data-template-id="<?php echo $template['id']; ?>">
                            <i class="fas fa-plus"></i> Use Template
                        </button>
                        <button class="btn btn-outline-secondary preview-template" 
                                data-template-id="<?php echo $template['id']; ?>">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
</div>

<!-- Template Preview Modal -->
<div class="modal fade" id="templatePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Template Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="template-preview-content"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="use-preview-template">
                    Use Template
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Handle template preview
    document.querySelectorAll('.preview-template').forEach(button => {
        button.addEventListener('click', async () => {
            const templateId = button.dataset.templateId;
            try {
                const response = await fetch(`<?php echo SITE_URL; ?>/api/templates.php?id=${templateId}`);
                const template = await response.json();
                
                if (template.id) {
                    const previewContent = document.getElementById('template-preview-content');
                    const structure = JSON.parse(template.structure);
                    
                    let html = `
                        <h4>${template.name}</h4>
                        <p class="text-muted">${template.description}</p>
                        <hr>
                        <form class="preview-form">
                    `;
                    
                    structure.questions.forEach((question, index) => {
                        html += `
                            <div class="mb-4">
                                <label class="form-label">
                                    ${index + 1}. ${question.text}
                                    ${question.required ? '<span class="text-danger">*</span>' : ''}
                                </label>
                        `;
                        
                        switch (question.type) {
                            case 'multiple_choice':
                                question.options.forEach(option => {
                                    html += `
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" disabled>
                                            <label class="form-check-label">${option}</label>
                                        </div>
                                    `;
                                });
                                break;
                                
                            case 'single_choice':
                                question.options.forEach(option => {
                                    html += `
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" disabled>
                                            <label class="form-check-label">${option}</label>
                                        </div>
                                    `;
                                });
                                break;
                                
                            case 'text':
                                html += `<textarea class="form-control" rows="3" disabled></textarea>`;
                                break;
                                
                            case 'rating':
                                html += `
                                    <div class="rating-preview">
                                        ${[1,2,3,4,5].map(num => `
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" disabled>
                                                <label class="form-check-label">${num}</label>
                                            </div>
                                        `).join('')}
                                    </div>
                                `;
                                break;
                        }
                        
                        html += '</div>';
                    });
                    
                    html += '</form>';
                    previewContent.innerHTML = html;
                    
                    // Store template ID for use button
                    document.getElementById('use-preview-template').dataset.templateId = templateId;
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('templatePreviewModal'));
                    modal.show();
                } else {
                    throw new Error(template.error || 'Failed to load template');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to load template preview');
            }
        });
    });

    // Handle use template
    const useTemplate = async (templateId) => {
        try {
            console.log('Loading template with ID:', templateId);
            const response = await fetch(`<?php echo SITE_URL; ?>/api/templates.php?id=${templateId}`);
            
            if (!response.ok) {
                throw new Error(`Server returned ${response.status}: ${response.statusText}`);
            }
            
            const template = await response.json();
            
            if (template.id) {
                console.log('Template loaded successfully:', template.name);
                // Redirect to create survey with template data
                window.location.href = '<?php echo SITE_URL; ?>/dashboard/create-survey.php?template=' + templateId;
            } else {
                throw new Error(template.error || 'Failed to use template');
            }
        } catch (error) {
            console.error('Error details:', error);
            alert('Failed to load template: ' + error.message + '\nPlease try creating a survey from scratch or contact support if this issue persists.');
        }
    };

    // Direct use template buttons
    document.querySelectorAll('.use-template').forEach(button => {
        button.addEventListener('click', () => useTemplate(button.dataset.templateId));
    });

    // Modal use template button
    document.getElementById('use-preview-template').addEventListener('click', function() {
        useTemplate(this.dataset.templateId);
    });
});
</script>

<?php require_once '../templates/footer.php'; ?>
