<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated'
    ]);
    exit;
}

// Check if request is POST and contains JSON data
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get the raw POST data and decode it
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON data'
    ]);
    exit;
}

// Validate required fields
if (empty($data['title'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Survey title is required'
    ]);
    exit;
}

if (empty($data['questions']) || !is_array($data['questions'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'At least one question is required'
    ]);
    exit;
}

// Get database connection
$db = Database::getInstance();

try {
    // Start transaction
    $db->beginTransaction();
    
    // Insert survey record
    $user_id = $_SESSION['user_id'];
    $title = $db->escape($data['title']);
    $description = $db->escape($data['description'] ?? '');
    
    $survey_sql = "INSERT INTO surveys (user_id, title, description, created_at, status) 
                   VALUES ($user_id, '$title', '$description', NOW(), 'draft')";
    
    if (!$db->query($survey_sql)) {
        throw new Exception("Error creating survey: " . $db->getLastError());
    }
    
    $survey_id = $db->getLastId();
    
    // Insert questions
    foreach ($data['questions'] as $index => $question) {
        // Extract question data - handle different property names
        $question_text = $db->escape($question['question'] ?? $question['question_text'] ?? '');
        $question_type = $db->escape($question['type'] ?? $question['question_type'] ?? 'text');
        $required = isset($question['required']) && $question['required'] ? 1 : 0;
        $position = $index + 1;
        
        $question_sql = "INSERT INTO questions (survey_id, question_text, question_type, required, order_position) 
                        VALUES ($survey_id, '$question_text', '$question_type', $required, $position)";
        
        if (!$db->query($question_sql)) {
            throw new Exception("Error creating question: " . $db->getLastError());
        }
        
        $question_id = $db->getLastId();
        
        // Insert options if applicable
        if (in_array($question_type, ['multiple_choice', 'single_choice', 'rating']) && 
            isset($question['options']) && is_array($question['options'])) {
            
            foreach ($question['options'] as $opt_index => $option) {
                $opt_position = $opt_index + 1;
                
                // Handle different option formats
                if (is_array($option) && isset($option['text'])) {
                    $option_text = $db->escape($option['text']);
                } elseif (is_string($option)) {
                    $option_text = $db->escape($option);
                } else {
                    $option_text = $db->escape("Option " . $opt_position);
                }
                
                $option_sql = "INSERT INTO options (question_id, option_text, order_position) 
                              VALUES ($question_id, '$option_text', $opt_position)";
                
                if (!$db->query($option_sql)) {
                    throw new Exception("Error creating option: " . $db->getLastError());
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Return success response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'survey_id' => $survey_id,
        'message' => 'Survey created successfully'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $db->rollback();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 