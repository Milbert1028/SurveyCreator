<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    die("Survey ID is required");
}

// Get survey details
$db = Database::getInstance();
$survey = $db->query("
    SELECT s.*, u.username as creator 
    FROM surveys s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = $survey_id AND s.status = 'published'
");

if (!$survey || $survey->num_rows === 0) {
    die("Survey not found or not available");
}

$survey_data = $survey->fetch_assoc();
$share_settings = get_share_settings($survey_id); // Get share settings

// Check if access is restricted (assume you have the table and column)
$access_restricted = isset($share_settings['restrict_access']) && $share_settings['restrict_access'] == 1;

// If access is restricted, check if user has permission
if ($access_restricted) {
    // Must be logged in to access restricted surveys
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = SITE_URL . "/survey.php?id=" . $survey_id;
        redirect('/auth/login.php');
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Survey creator always has access
    if ($survey_data['user_id'] == $user_id) {
        // Continue with survey (creator has access)
    }
    else {
        // Check if user has been granted access
        $access_query = "SELECT 1 FROM survey_access 
                        WHERE survey_id = $survey_id AND user_id = $user_id
                        LIMIT 1";
        
        $has_access = $db->query($access_query);
        
        if (!$has_access || $has_access->num_rows === 0) {
            // No access - show error message
            require_once 'templates/header.php';
            ?>
            <div class="container py-5">
                <div class="alert alert-danger text-center p-5">
                    <h2><i class="fas fa-exclamation-triangle me-2"></i>Access Denied</h2>
                    <p class="lead">You do not have permission to access this survey.</p>
                    <p>This survey is restricted to selected users only. If you believe you should have access, please contact the survey creator.</p>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary mt-3">Go Home</a>
                </div>
            </div>
            <?php
            require_once 'templates/footer.php';
            exit;
        }
    }
}

// Check other survey access conditions (login, response limit, etc.)
if ($share_settings['require_login'] && !is_logged_in()) {
    redirect('/auth/login.php');
}

// 2. Check response limit
$response_count = $db->query("SELECT COUNT(*) FROM responses WHERE survey_id = $survey_id")->fetch_row()[0];
if (!empty($share_settings['response_limit']) && $response_count >= $share_settings['response_limit']) {
    die("This survey has reached its response limit and is no longer accepting responses.");
}

// 3. Check close date
if (!empty($share_settings['close_date']) && strtotime($share_settings['close_date']) < time()) {
    die("This survey is closed.");
}

// 4. Prevent multiple responses (if not allowed)
if (!$share_settings['allow_multiple_responses'] && is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $user_response = $db->query("
        SELECT COUNT(*) 
        FROM responses 
        WHERE survey_id = $survey_id AND user_id = $user_id
    ")->fetch_row()[0];

    if ($user_response > 0) {
        die("You have already submitted a response for this survey.");
    }
}

// Get questions with their options
$questions = $db->query("
    SELECT 
        q.*,
        GROUP_CONCAT(
            DISTINCT 
            CONCAT(o.id, ':', o.option_text)
            ORDER BY o.order_position
            SEPARATOR '|'
        ) as options_data
    FROM questions q
    LEFT JOIN options o ON q.id = o.question_id
    WHERE q.survey_id = $survey_id
    GROUP BY q.id
    ORDER BY q.order_position
");

require_once 'templates/header.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h1 class="card-title text-center mb-4"><?php echo htmlspecialchars($survey_data['title']); ?></h1>
                    
                    <?php if ($survey_data['description']): ?>
                        <p class="text-muted text-center mb-4">
                            <?php echo nl2br(htmlspecialchars($survey_data['description'])); ?>
                        </p>
                    <?php endif; ?>

                    <form id="survey-form" class="needs-validation" novalidate>
                        <input type="hidden" name="survey_id" value="<?php echo $survey_id; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                        <!-- Progress bar -->
                        <?php if ($share_settings['show_progress_bar']): ?>
                            <div class="progress mb-4" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                        <?php endif; ?>

                        <?php 
                        $total_questions = $questions->num_rows;
                        $current_question = 0;
                        
                        while ($question = $questions->fetch_assoc()): 
                            $current_question++;
                            
                            // Process options
                            $options = [];
                            if ($question['options_data']) {
                                foreach (explode('|', $question['options_data']) as $optionData) {
                                    list($id, $text) = explode(':', $optionData);
                                    $options[$id] = $text;
                                }
                            }
                        ?>
                            <div class="question-container" data-question="<?php echo $current_question; ?>" 
                                 style="display: <?php echo $current_question === 1 ? 'block' : 'none'; ?>">
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <?php echo htmlspecialchars($question['question_text']); ?>
                                        <?php if ($question['required']): ?>
                                            <span class="text-danger">*</span>
                                        <?php endif; ?>
                                    </label>

                                    <?php switch ($question['question_type']): 
                                        case 'multiple_choice': ?>
                                            <?php foreach ($options as $option_id => $option_text): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="answers[<?php echo $question['id']; ?>][]" 
                                                           value="<?php echo $option_id; ?>"
                                                           <?php echo $question['required'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label">
                                                        <?php echo htmlspecialchars($option_text); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php break; ?>

                                        <?php case 'single_choice': ?>
                                            <?php foreach ($options as $option_id => $option_text): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" 
                                                           id="option_<?php echo $question['id']; ?>_<?php echo $option_id; ?>"
                                                           name="answers[<?php echo $question['id']; ?>]" 
                                                           value="<?php echo $option_id; ?>"
                                                           <?php echo $question['required'] ? 'required' : ''; ?>>
                                                    <label class="form-check-label" for="option_<?php echo $question['id']; ?>_<?php echo $option_id; ?>">
                                                        <?php echo htmlspecialchars($option_text); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php break; ?>

                                        <?php case 'rating': ?>
                                            <div class="rating-container">
                                                <?php foreach ($options as $option_id => $option_text): ?>
                                                    <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio"
                                                               id="rating_<?php echo $question['id']; ?>_<?php echo $option_id; ?>"
                                                               name="answers[<?php echo $question['id']; ?>]" 
                                                               value="<?php echo $option_id; ?>"
                                                               <?php echo $question['required'] ? 'required' : ''; ?>>
                                                        <label class="form-check-label" for="rating_<?php echo $question['id']; ?>_<?php echo $option_id; ?>">
                                                            <?php echo htmlspecialchars($option_text); ?>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php break; ?>

                                        <?php case 'text': ?>
                                            <textarea class="form-control" 
                                                      name="answers[<?php echo $question['id']; ?>]" 
                                                      rows="3"
                                                      <?php echo $question['required'] ? 'required' : ''; ?>></textarea>
                                            <?php break; ?>
                                    <?php endswitch; ?>

                                    <?php if ($question['required']): ?>
                                        <div class="invalid-feedback">
                                            This question is required
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Navigation buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <?php if ($current_question > 1): ?>
                                        <button type="button" class="btn btn-outline-primary prev-question">
                                            <i class="fas fa-arrow-left"></i> Previous
                                        </button>
                                    <?php else: ?>
                                        <div></div>
                                    <?php endif; ?>

                                    <?php if ($current_question < $total_questions): ?>
                                        <button type="button" class="btn btn-primary next-question">
                                            Next <i class="fas fa-arrow-right"></i>
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" class="btn btn-success" id="submit-button">
                                            Submit <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                <h3>Thank You!</h3>
                <p class="mb-0">Your response has been recorded successfully.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('survey-form');
    const progressBar = document.querySelector('.progress-bar');
    const totalQuestions = <?php echo $total_questions; ?>;
    let currentQuestion = 1;

    // Update progress bar
    function updateProgress() {
        if (!progressBar) return; // Skip if progressBar doesn't exist
        const progress = (currentQuestion / totalQuestions) * 100;
        progressBar.style.width = `${progress}%`;
    }

    // Show question
    function showQuestion(questionNumber) {
        document.querySelectorAll('.question-container').forEach(container => {
            container.style.display = 'none';
        });
        document.querySelector(`[data-question="${questionNumber}"]`).style.display = 'block';
        currentQuestion = questionNumber;
        updateProgress();
    }

    // Next question button
    document.querySelectorAll('.next-question').forEach(button => {
        button.addEventListener('click', () => {
            const currentContainer = document.querySelector(`[data-question="${currentQuestion}"]`);
            const inputs = currentContainer.querySelectorAll('input[required], textarea[required]');
            let isValid = true;

            inputs.forEach(input => {
                if (input.type === 'radio') {
                    const name = input.name;
                    const checked = currentContainer.querySelector(`input[name="${name}"]:checked`);
                    if (!checked) {
                        isValid = false;
                        input.closest('.question-container').querySelector('.invalid-feedback').style.display = 'block';
                    }
                } else if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('is-invalid');
                }
            });

            if (isValid) {
                showQuestion(currentQuestion + 1);
            }
        });
    });

    // Previous question button
    document.querySelectorAll('.prev-question').forEach(button => {
        button.addEventListener('click', () => {
            showQuestion(currentQuestion - 1);
        });
    });

    // Form submission
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);
        const data = {
            survey_id: parseInt(<?php echo $survey_id; ?>),
            csrf_token: formData.get('csrf_token'),
            responses: []
        };

        // Process form data
        document.querySelectorAll('.question-container').forEach(container => {
            const questionIdMatch = container.querySelector('input, textarea')?.name?.match(/answers\[(\d+)\]/);
            if (!questionIdMatch) return;
            
            const questionId = questionIdMatch[1];
            const questionType = container.querySelector('.form-check-input')?.type || 'textarea';
            let response = {
                question_id: parseInt(questionId)
            };
            
            switch (questionType) {
                case 'checkbox':
                    // Multiple choice
                    const checkedBoxes = container.querySelectorAll('input[type="checkbox"]:checked');
                    if (checkedBoxes.length > 0) {
                        const selectedOptions = [];
                        checkedBoxes.forEach(cb => {
                            selectedOptions.push(parseInt(cb.value));
                        });
                        response.selected_options = selectedOptions;
                        data.responses.push(response);
                    }
                    break;

                case 'radio':
                    // Single choice or rating
                    const selectedRadio = container.querySelector('input[type="radio"]:checked');
                    if (selectedRadio) {
                        response.selected_options = [parseInt(selectedRadio.value)];
                        data.responses.push(response);
                    }
                    break;

                default:
                    // Text answer
                    const textArea = container.querySelector('textarea');
                    if (textArea && textArea.value.trim()) {
                        response.text_answer = textArea.value.trim();
                        data.responses.push(response);
                    }
                    break;
            }
        });

        try {
            console.log('Submitting data:', data);
            document.getElementById('submit-button').disabled = true;
            document.getElementById('submit-button').innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
            
            // First try the test API to verify connectivity
            try {
                const testResponse = await fetch('api/test-api.php');
                if (!testResponse.ok) {
                    console.warn('Test API check failed:', testResponse.status);
                }
            } catch (testError) {
                console.warn('Test API error:', testError);
            }
            
            // Now try to submit the survey
            const response = await fetch('api/submit-survey.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });

            // If server returns 500, we handle it differently
            if (!response.ok) {
                if (response.status === 500) {
                    console.error('Server error 500');
                    throw new Error('Server internal error (500). The server might be misconfigured or the PHP script is crashing.');
                }
                else {
                    throw new Error(`Server returned error: ${response.status}`);
                }
            }

            // Try to parse JSON response
            let result;
            try {
                const contentType = response.headers.get('content-type');
                const rawText = await response.text();
                console.log('Raw server response:', rawText);
                
                if (rawText.trim() === '') {
                    throw new Error('Server returned empty response');
                }
                
                if (contentType && contentType.includes('application/json')) {
                    // Parse the JSON ourselves
                    result = JSON.parse(rawText);
                } else {
                    throw new Error('Server did not return JSON. Response: ' + rawText.substring(0, 100) + '...');
                }
            } catch (parseError) {
                console.error('Error parsing response:', parseError);
                throw new Error('Failed to parse server response: ' + parseError.message);
            }
            
            // Process the result
            if (result.success) {
                const modal = new bootstrap.Modal(document.getElementById('successModal'));
                modal.show();
                
                modal._element.addEventListener('hidden.bs.modal', () => {
                    window.location.href = '<?php echo SITE_URL ?? "/"; ?>';
                });
            } else {
                alert('Error: ' + (result.message || 'Unknown error'));
                document.getElementById('submit-button').disabled = false;
                document.getElementById('submit-button').innerHTML = 'Submit';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Error submitting survey: ' + error.message);
            document.getElementById('submit-button').disabled = false;
            document.getElementById('submit-button').innerHTML = 'Submit';
        }
    });

    // Initialize progress bar
    updateProgress();

    // Fix for radio buttons - ensure they work properly when clicked
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('click', function() {
            // Clear any validation errors for this question group
            const questionContainer = this.closest('.question-container');
            const invalidFeedback = questionContainer.querySelector('.invalid-feedback');
            if (invalidFeedback) {
                invalidFeedback.style.display = 'none';
            }
            
            // Ensure the radio is checked (fix for some browsers/devices)
            setTimeout(() => {
                this.checked = true;
            }, 0);
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?>