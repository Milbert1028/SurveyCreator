<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set content type
header('Content-Type: application/json');

// Create a response array
$response = [
    'success' => false,
    'message' => '',
    'survey' => null,
    'questions' => [],
    'tables' => []
];

// Get survey ID
$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    $response['message'] = 'Survey ID is required';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

try {
    // Connect to database
    $db = Database::getInstance();
    
    // Check database tables
    $response['tables'] = [];
    $tables = ['surveys', 'questions', 'options', 'responses', 'share_settings'];
    
    foreach ($tables as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'");
        $response['tables'][$table] = ($check && $check->num_rows > 0);
    }
    
    // Get survey details
    $survey_query = $db->query("SELECT * FROM surveys WHERE id = $survey_id");
    if (!$survey_query || $survey_query->num_rows === 0) {
        $response['message'] = "Survey not found";
        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
    
    $response['survey'] = $survey_query->fetch_assoc();
    
    // Get questions
    $questions_query = $db->query("SELECT * FROM questions WHERE survey_id = $survey_id ORDER BY order_position");
    if ($questions_query && $questions_query->num_rows > 0) {
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
            $response['questions'][] = $question;
        }
    }
    
    // Check for share settings if table exists
    if ($response['tables']['share_settings']) {
        $share_query = $db->query("SELECT * FROM share_settings WHERE survey_id = $survey_id");
        if ($share_query && $share_query->num_rows > 0) {
            $response['share_settings'] = $share_query->fetch_assoc();
        }
    }
    
    $response['success'] = true;
    $response['message'] = "Survey details retrieved successfully";
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
exit; 