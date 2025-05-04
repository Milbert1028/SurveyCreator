<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to update the survey", "warning");
    redirect('/auth/login.php');
}

// CSRF token validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if the request is JSON
    $contentType = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Get the JSON data
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);
        
        if (!$data) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            exit;
        }
        
        // Set POST variables from JSON data
        $_POST = $data;
    }
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        if (strpos($contentType, 'application/json') !== false) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
            exit;
        } else {
            flash_message("CSRF token mismatch. Please try again.", "danger");
            redirect('/dashboard');
        }
    }
}

$survey_id = $_POST['survey_id'] ?? null;
if (!$survey_id || !is_numeric($survey_id)) {
    flash_message("Survey ID is required and must be a valid number", "danger");
    redirect('/dashboard');
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Delete questions that were removed in the UI
    if (isset($_POST['deleted_questions']) && is_array($_POST['deleted_questions'])) {
        $deleted_questions = $_POST['deleted_questions'];
        if (!empty($deleted_questions)) {
            // Prepare statement for deleting questions
            $delete_question = $conn->prepare("DELETE FROM questions WHERE id = ? AND survey_id = ?");
            
            foreach ($deleted_questions as $question_id) {
                // First delete the options associated with this question
                $delete_options = $conn->prepare("DELETE FROM options WHERE question_id = ?");
                $delete_options->bind_param('i', $question_id);
                $delete_options->execute();
                $delete_options->close();
                
                // Then delete the question
                $delete_question->bind_param('ii', $question_id, $survey_id);
                $delete_question->execute();
            }
            
            $delete_question->close();
        }
    }

    // Update survey details using prepared statement
    $stmt = $conn->prepare("UPDATE surveys SET title = ?, description = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ssii', $_POST['title'], $_POST['description'], $survey_id, $user_id);
    $stmt->execute();
    $stmt->close();

    // Function to save question options using prepared statements
    function save_question_options($conn, $question_id, $options) {
        // Delete existing options
        $stmt = $conn->prepare("DELETE FROM options WHERE question_id = ?");
        $stmt->bind_param('i', $question_id);
        $stmt->execute();
        $stmt->close();

        // Insert new options
        $stmt = $conn->prepare("INSERT INTO options (question_id, option_text, order_position) VALUES (?, ?, ?)");
        foreach ($options as $option_order => $option_text) {
            if (trim($option_text) !== '') {
                $order = intval($option_order) + 1;
                $stmt->bind_param('isi', $question_id, $option_text, $order);
                $stmt->execute();
            }
        }
        $stmt->close();
    }

    // Update questions
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
        // Prepare statements
        $update_question = $conn->prepare("
            UPDATE questions 
            SET question_text = ?, 
                question_type = ?, 
                required = ?, 
                order_position = ? 
            WHERE id = ? AND survey_id = ?
        ");

        $insert_question = $conn->prepare("
            INSERT INTO questions (
                survey_id, 
                question_text, 
                question_type, 
                required, 
                order_position
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($_POST['questions'] as $question_id => $question) {
            $question_text = $question['text'];
            $question_type = $question['type'];
            $required = isset($question['required']) ? 1 : 0;
            $order_position = intval($question_id);

            // Check if the question exists
            $check = $conn->prepare("SELECT id FROM questions WHERE id = ? AND survey_id = ?");
            $check->bind_param('ii', $question_id, $survey_id);
            $check->execute();
            $result = $check->get_result();
            $check->close();

            if ($result->num_rows > 0) {
                // Update existing question
                $update_question->bind_param('ssiiii', 
                    $question_text,
                    $question_type,
                    $required,
                    $order_position,
                    $question_id,
                    $survey_id
                );
                $update_question->execute();
            } else {
                // Insert new question
                $insert_question->bind_param('issii',
                    $survey_id,
                    $question_text,
                    $question_type,
                    $required,
                    $order_position
                );
                $insert_question->execute();
                $question_id = $conn->insert_id;
            }

            // Update options
            if (isset($question['options']) && is_array($question['options'])) {
                save_question_options($conn, $question_id, $question['options']);
            }
        }

        // Close prepared statements
        $update_question->close();
        $insert_question->close();
    }

    // Commit transaction
    $conn->commit();
    
    // Return JSON response if applicable
    if (isset($contentType) && strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Survey updated successfully']);
        exit;
    } else {
        flash_message("Survey updated successfully", "success");
    }

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    error_log("Error updating survey: " . $e->getMessage());
    
    // Return JSON response if applicable
    if (isset($contentType) && strpos($contentType, 'application/json') !== false) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error updating survey: ' . $e->getMessage()]);
        exit;
    } else {
        flash_message("Error updating survey. Please try again.", "danger");
    }
} 

// Only redirect if not a JSON request
if (!isset($contentType) || strpos($contentType, 'application/json') === false) {
    redirect('/dashboard');
}
