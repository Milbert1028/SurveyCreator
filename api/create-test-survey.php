<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set content type
header('Content-Type: application/json');

// Function for logging
function logMessage($message) {
    $log_file = __DIR__ . '/../logs/test_survey.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$time] $message\n", FILE_APPEND);
}

// Log start
logMessage("Starting test survey creation");

// Create a response array
$response = [
    'success' => false,
    'message' => '',
    'details' => []
];

try {
    // Get user ID (use the first admin user if no user is logged in)
    if (is_logged_in()) {
        $user_id = $_SESSION['user_id'];
        logMessage("Using logged in user: $user_id");
    } else {
        // Get the first admin user from the database
        $db = Database::getInstance();
        $admin_user = $db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
        
        if ($admin_user && $admin_user->num_rows > 0) {
            $user_id = $admin_user->fetch_assoc()['id'];
            logMessage("Using admin user: $user_id");
        } else {
            // Use ID 1 as a fallback
            $user_id = 1; 
            logMessage("No admin user found, using ID: $user_id");
        }
    }
    
    // Start transaction
    $db = Database::getInstance();
    $db->beginTransaction();
    logMessage("Transaction started");
    
    // Create survey
    $title = "Test Survey " . date('Y-m-d H:i:s');
    $description = "This is a test survey created by the system to test survey functionality.";
    
    $survey_sql = "INSERT INTO surveys (user_id, title, description, created_at, status) 
                   VALUES ($user_id, '$title', '$description', NOW(), 'published')";
    
    logMessage("Creating survey: $title");
    
    if (!$db->query($survey_sql)) {
        throw new Exception("Error creating survey: " . $db->getLastError());
    }
    
    $survey_id = $db->getLastId();
    logMessage("Survey created with ID: $survey_id");
    $response['details']['survey_id'] = $survey_id;
    
    // Create questions
    $questions = [
        [
            'text' => 'How would you rate our service?',
            'type' => 'rating',
            'required' => true,
            'options' => ['1', '2', '3', '4', '5']
        ],
        [
            'text' => 'Which of our products have you used?',
            'type' => 'multiple_choice',
            'required' => true,
            'options' => ['Product A', 'Product B', 'Product C', 'Other']
        ],
        [
            'text' => 'What is your favorite feature?',
            'type' => 'single_choice',
            'required' => true,
            'options' => ['Feature 1', 'Feature 2', 'Feature 3', 'Other']
        ],
        [
            'text' => 'Please provide any additional feedback',
            'type' => 'text',
            'required' => false,
            'options' => []
        ]
    ];
    
    foreach ($questions as $index => $question) {
        $question_text = $db->escape($question['text']);
        $question_type = $db->escape($question['type']);
        $required = $question['required'] ? 1 : 0;
        $position = $index + 1;
        
        $question_sql = "INSERT INTO questions (survey_id, question_text, question_type, required, order_position) 
                        VALUES ($survey_id, '$question_text', '$question_type', $required, $position)";
        
        logMessage("Creating question: $question_text");
        
        if (!$db->query($question_sql)) {
            throw new Exception("Error creating question: " . $db->getLastError());
        }
        
        $question_id = $db->getLastId();
        logMessage("Question created with ID: $question_id");
        
        // Create options if applicable
        if (in_array($question_type, ['multiple_choice', 'single_choice', 'rating']) && !empty($question['options'])) {
            foreach ($question['options'] as $opt_index => $option_text) {
                $opt_position = $opt_index + 1;
                $option_text = $db->escape($option_text);
                
                $option_sql = "INSERT INTO options (question_id, option_text, order_position) 
                              VALUES ($question_id, '$option_text', $opt_position)";
                
                logMessage("Creating option: $option_text");
                
                if (!$db->query($option_sql)) {
                    throw new Exception("Error creating option: " . $db->getLastError());
                }
            }
        }
    }
    
    // Check if share_settings table exists
    logMessage("Checking for share_settings table");
    $table_check = $db->query("SHOW TABLES LIKE 'share_settings'");
    if ($table_check && $table_check->num_rows > 0) {
        // Create share settings
        $share_sql = "INSERT INTO share_settings (survey_id, require_login, allow_multiple_responses, show_progress_bar) 
                      VALUES ($survey_id, 0, 1, 1)";
                      
        logMessage("Creating share settings");
        
        if (!$db->query($share_sql)) {
            throw new Exception("Error creating share settings: " . $db->getLastError());
        }
    } else {
        logMessage("share_settings table doesn't exist - skipping");
    }
    
    // Commit transaction
    $db->commit();
    logMessage("Transaction committed successfully");
    
    // Success response
    $response['success'] = true;
    $response['message'] = "Test survey created successfully with ID: $survey_id";
    $response['details']['title'] = $title;
    $response['details']['url'] = SITE_URL . "/survey.php?id=$survey_id";
    
} catch (Exception $e) {
    // Log error
    logMessage("ERROR: " . $e->getMessage());
    
    // Rollback transaction if needed
    if (isset($db) && $db->getConnection()) {
        try {
            $db->rollback();
            logMessage("Transaction rolled back");
        } catch (Exception $rollback_error) {
            logMessage("Error rolling back transaction: " . $rollback_error->getMessage());
        }
    }
    
    // Error response
    $response['success'] = false;
    $response['message'] = "Error creating test survey: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
exit; 