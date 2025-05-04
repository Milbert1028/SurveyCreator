<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't show errors in the output
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Start output buffering to prevent PHP errors from breaking JSON output
ob_start();

// Include required files - Database class is included within functions.php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Create a log file for debugging
$logFile = $logDir . '/api_debug.log';
function logDebug($message, $data = null) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}";
    if ($data !== null) {
        $logMessage .= ': ' . (is_array($data) || is_object($data) ? json_encode($data) : $data);
    }
    file_put_contents($logFile, $logMessage . PHP_EOL, FILE_APPEND);
}

// Log API request start
logDebug('API Request started', $_SERVER['REQUEST_URI']);

// Enable CORS 
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle OPTIONS requests for CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    logDebug('OPTIONS request handled');
    ob_end_clean();
    exit();
}

// Ensure user is logged in
if (!is_logged_in()) {
    logDebug('User not authenticated');
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Check if request is POST and contains JSON data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug('Invalid request method', $_SERVER['REQUEST_METHOD']);
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the raw POST data and decode it
$json_data = file_get_contents('php://input');
logDebug('Received data', substr($json_data, 0, 500)); // Log first 500 chars to avoid huge logs

$data = json_decode($json_data, true);

if (!$data) {
    logDebug('Invalid JSON data', json_last_error_msg());
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data: ' . json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
if (empty($data['title'])) {
    logDebug('Missing title');
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Survey title is required'
    ]);
    exit;
}

if (empty($data['questions']) || !is_array($data['questions'])) {
    logDebug('Missing or invalid questions', $data['questions'] ?? null);
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'At least one question is required'
    ]);
    exit;
}

try {
    // Get database connection
    $db = Database::getInstance();
    logDebug('Database connection established');
    
    // Start transaction
    $db->beginTransaction();
    logDebug('Transaction started');
    
    // Insert survey record
    $user_id = $_SESSION['user_id'];
    $title = $db->escape($data['title']);
    $description = $db->escape($data['description'] ?? '');
    
    $survey_sql = "INSERT INTO surveys (user_id, title, description, created_at, status) 
                   VALUES ($user_id, '$title', '$description', NOW(), 'draft')";
    
    logDebug('Executing survey SQL', $survey_sql);
    if (!$db->query($survey_sql)) {
        throw new Exception("Error creating survey: " . $db->getLastError());
    }
    
    $survey_id = $db->getLastId();
    logDebug('Survey created with ID', $survey_id);
    
    // Process questions
    foreach ($data['questions'] as $index => $question) {
        // Extract question data - handle different structures the frontend might send
        $question_text = $db->escape($question['question'] ?? $question['question_text'] ?? '');
        $question_type = $db->escape($question['type'] ?? $question['question_type'] ?? 'text');
        $required = isset($question['required']) && $question['required'] ? 1 : 0;
        $position = $index + 1;
        
        logDebug('Processing question', [
            'text' => $question_text,
            'type' => $question_type,
            'required' => $required,
            'position' => $position
        ]);
        
        $question_sql = "INSERT INTO questions (survey_id, question_text, question_type, required, order_position) 
                        VALUES ($survey_id, '$question_text', '$question_type', $required, $position)";
        
        logDebug('Executing question SQL', $question_sql);
        if (!$db->query($question_sql)) {
            throw new Exception("Error creating question: " . $db->getLastError());
        }
        
        $question_id = $db->getLastId();
        logDebug('Question created with ID', $question_id);
        
        // Insert options if applicable
        if (in_array($question_type, ['multiple_choice', 'single_choice', 'rating']) && 
            isset($question['options']) && is_array($question['options'])) {
            
            $options = $question['options'];
            logDebug('Processing options', ['count' => count($options), 'options' => $options]);
            
            foreach ($options as $opt_index => $option) {
                $opt_position = $opt_index + 1;
                
                // Handle both option formats (string or object)
                if (is_array($option) && isset($option['text'])) {
                    $option_text = $db->escape($option['text']);
                } elseif (is_string($option)) {
                    $option_text = $db->escape($option);
                } else {
                    $option_text = $db->escape("Option " . $opt_position);
                }
                
                $option_sql = "INSERT INTO options (question_id, option_text, order_position) 
                              VALUES ($question_id, '$option_text', $opt_position)";
                
                logDebug('Executing option SQL', $option_sql);
                if (!$db->query($option_sql)) {
                    throw new Exception("Error creating option: " . $db->getLastError());
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    logDebug('Transaction committed successfully');
    
    // Return success response
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'survey_id' => $survey_id,
        'message' => 'Survey created successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && method_exists($db, 'rollback')) {
        $db->rollback();
    }
    
    logDebug('Error occurred, transaction rolled back', $e->getMessage());
    
    // Clear any output so far
    ob_end_clean();
    
    // Return error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End script
exit;
