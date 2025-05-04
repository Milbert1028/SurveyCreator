<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/debug_submission.log');

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set content type
header('Content-Type: application/json');

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Log file
$log_file = $log_dir . '/debug_submission.log';
function logDebug($message, $data = null) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logMessage .= ': ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    file_put_contents($log_file, $logMessage . PHP_EOL, FILE_APPEND);
}

// Log start
logDebug("Debug submission script started");

// Get survey ID
$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Survey ID is required'
    ]);
    exit;
}

try {
    // Connect to database
    $db = Database::getInstance();
    logDebug("Connected to database");
    
    // Get survey details
    $survey_query = $db->query("SELECT * FROM surveys WHERE id = $survey_id");
    if (!$survey_query || $survey_query->num_rows === 0) {
        throw new Exception("Survey not found");
    }
    
    $survey = $survey_query->fetch_assoc();
    logDebug("Found survey", $survey);
    
    // Get questions
    $questions_query = $db->query("SELECT * FROM questions WHERE survey_id = $survey_id ORDER BY order_position");
    if (!$questions_query || $questions_query->num_rows === 0) {
        throw new Exception("No questions found for this survey");
    }
    
    $questions = [];
    while ($question = $questions_query->fetch_assoc()) {
        $question_id = $question['id'];
        
        // Get options for this question
        $options = [];
        $options_query = $db->query("SELECT * FROM options WHERE question_id = $question_id ORDER BY order_position");
        
        if ($options_query && $options_query->num_rows > 0) {
            while ($option = $options_query->fetch_assoc()) {
                $options[] = $option;
            }
        }
        
        $question['options'] = $options;
        $questions[] = $question;
    }
    
    logDebug("Found questions", count($questions));
    
    // Generate test submission data
    $submission_data = [
        'survey_id' => $survey_id,
        'responses' => []
    ];
    
    foreach ($questions as $question) {
        $response = [
            'question_id' => $question['id']
        ];
        
        switch ($question['question_type']) {
            case 'multiple_choice':
                // Select random options (1-3)
                $selected = [];
                $num_to_select = min(rand(1, 3), count($question['options']));
                $option_indexes = array_rand($question['options'], $num_to_select);
                
                if (!is_array($option_indexes)) {
                    $option_indexes = [$option_indexes];
                }
                
                foreach ($option_indexes as $index) {
                    $selected[] = (int)$question['options'][$index]['id'];
                }
                
                $response['selected_options'] = $selected;
                break;
                
            case 'single_choice':
                // Select one random option
                if (count($question['options']) > 0) {
                    $index = array_rand($question['options']);
                    $response['selected_options'] = [(int)$question['options'][$index]['id']];
                }
                break;
                
            case 'rating':
                // Select one random option
                if (count($question['options']) > 0) {
                    $index = array_rand($question['options']);
                    $response['selected_options'] = [(int)$question['options'][$index]['id']];
                }
                break;
                
            case 'text':
                // Generate random text
                $response['text_answer'] = "Test response: " . substr(md5(rand()), 0, 15);
                break;
        }
        
        $submission_data['responses'][] = $response;
    }
    
    logDebug("Generated submission data", $submission_data);
    
    // Try to submit via direct API call (without actual HTTP request)
    logDebug("Starting direct submission process");
    
    try {
        // Store original error reporting settings
        $original_reporting = error_reporting();
        $original_display_errors = ini_get('display_errors');
        
        // Capture output
        ob_start();
        
        // Include the submit-survey.php script in a controlled environment
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['CONTENT_TYPE'] = 'application/json';
        
        // Prepare the raw input data
        $raw_json = json_encode($submission_data);
        
        // Create a stream that php://input will read from
        $stream = fopen('php://temp', 'r+');
        fputs($stream, $raw_json);
        rewind($stream);
        
        // Override php://input with our custom stream
        // Note: This doesn't actually work in PHP, but we'll simulate it
        
        // Instead, we'll set a global variable for our script to use
        $GLOBALS['_MOCK_RAW_INPUT'] = $raw_json;
        
        // Define a function to override file_get_contents for php://input
        function mock_file_get_contents($filename) {
            if ($filename === 'php://input') {
                return $GLOBALS['_MOCK_RAW_INPUT'];
            }
            return file_get_contents($filename);
        }
        
        // Now we need to execute the submit-survey.php logic manually
        logDebug("Manually executing survey submission logic");
        
        // Get database connection
        $submission_db = Database::getInstance();
        
        // Start transaction
        $submission_db->beginTransaction();
        logDebug("Started transaction");
        
        // Process the data
        $submission_survey_id = $submission_data['survey_id'];
        
        // Check if survey exists
        $check_survey = $submission_db->query("SELECT id, status FROM surveys WHERE id = $submission_survey_id LIMIT 1");
        if (!$check_survey || $check_survey->num_rows === 0) {
            throw new Exception("Survey not found");
        }
        
        $survey_info = $check_survey->fetch_assoc();
        if ($survey_info['status'] !== 'published') {
            throw new Exception("Survey is not published");
        }
        
        // Get user ID (use NULL if not logged in)
        $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
        logDebug("Using user ID: $user_id");
        
        // Process each response
        $processed = 0;
        
        foreach ($submission_data['responses'] as $response_data) {
            if (empty($response_data['question_id'])) {
                continue;
            }
            
            $question_id = (int)$response_data['question_id'];
            
            // Check if question belongs to this survey
            $question_check = $submission_db->query("SELECT id, question_type FROM questions WHERE id = $question_id AND survey_id = $submission_survey_id");
            if (!$question_check || $question_check->num_rows === 0) {
                continue;
            }
            
            $question_info = $question_check->fetch_assoc();
            
            // Handle multiple choice, single choice, and rating questions
            if (($question_info['question_type'] === 'multiple_choice' || 
                 $question_info['question_type'] === 'single_choice' || 
                 $question_info['question_type'] === 'rating') && 
                !empty($response_data['selected_options']) && 
                is_array($response_data['selected_options'])) {
                
                foreach ($response_data['selected_options'] as $option_id) {
                    $option_id = (int)$option_id;
                    
                    $insert_sql = "INSERT INTO responses (survey_id, user_id, question_id, option_id, submitted_at) 
                                 VALUES ($submission_survey_id, $user_id, $question_id, $option_id, NOW())";
                    
                    logDebug("Executing SQL: $insert_sql");
                    
                    if ($submission_db->query($insert_sql)) {
                        $processed++;
                        logDebug("Inserted response for question $question_id, option $option_id");
                    } else {
                        logDebug("Error executing SQL: " . $submission_db->getLastError());
                        throw new Exception("Error saving response: " . $submission_db->getLastError());
                    }
                }
            }
            // Handle text answers
            else if ($question_info['question_type'] === 'text' && isset($response_data['text_answer'])) {
                $text = $submission_db->escape($response_data['text_answer']);
                
                $insert_sql = "INSERT INTO responses (survey_id, user_id, question_id, answer_text, submitted_at) 
                             VALUES ($submission_survey_id, $user_id, $question_id, '$text', NOW())";
                
                logDebug("Executing SQL: $insert_sql");
                
                if ($submission_db->query($insert_sql)) {
                    $processed++;
                    logDebug("Inserted text response for question $question_id");
                } else {
                    logDebug("Error executing SQL: " . $submission_db->getLastError());
                    throw new Exception("Error saving text response: " . $submission_db->getLastError());
                }
            }
        }
        
        // Check if we processed any responses
        if ($processed === 0) {
            $submission_db->rollback();
            logDebug("No responses processed, rolling back");
            throw new Exception("No valid responses were processed");
        }
        
        // Commit transaction
        $submission_db->commit();
        logDebug("Transaction committed successfully - processed $processed responses");
        
        $submission_result = [
            'success' => true,
            'message' => 'Survey submitted successfully',
            'count' => $processed
        ];
        
        // Restore output buffering
        ob_end_clean();
        
    } catch (Exception $submission_error) {
        // Rollback if needed
        if (isset($submission_db) && $submission_db instanceof mysqli && method_exists($submission_db, 'rollback')) {
            $submission_db->rollback();
            logDebug("Transaction rolled back due to error");
        }
        
        logDebug("Submission error: " . $submission_error->getMessage());
        
        $submission_result = [
            'success' => false,
            'message' => $submission_error->getMessage()
        ];
        
        // End output buffering
        ob_end_clean();
    }
    
    // Return debug response with all data
    echo json_encode([
        'success' => true,
        'survey' => $survey,
        'questions' => $questions,
        'submission_data' => $submission_data,
        'submission_result' => $submission_result,
        'message' => 'Debug process completed'
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    logDebug("Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 