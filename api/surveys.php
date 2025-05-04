<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get database instance
$db = Database::getInstance();
$conn = $db->getConnection();
$user_id = $_SESSION['user_id'];

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

// Disable error display in output - errors should be properly logged
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '../logs/php_errors.log');

try {
    switch ($method) {
        case 'GET':
            // Get survey data
            $survey_id = $_GET['id'] ?? null;
            $include_responses = isset($_GET['include_responses']) && $_GET['include_responses'] == '1';
            
            error_log("API: GET request for survey_id=$survey_id, include_responses=$include_responses");
            
            if ($survey_id) {
                // Get specific survey
                $survey = get_survey($survey_id);
                
                if (!$survey) {
                    error_log("API: get_survey($survey_id) returned false");
                    http_response_code(404);
                    echo json_encode(['error' => 'Survey not found']);
                    exit;
                }
                
                if ($survey['user_id'] != $user_id) {
                    error_log("API: User ID mismatch. Survey user_id={$survey['user_id']}, logged in user_id=$user_id");
                    http_response_code(403);
                    echo json_encode(['error' => 'Unauthorized access to survey']);
                    exit;
                }
                
                if ($survey && $survey['user_id'] == $user_id) {
                    // Always initialize responses as an empty array for JSON consistency 
                    $survey['responses'] = [];
                    
                    // If responses are requested, fetch them
                    if ($include_responses) {
                        try {
                            error_log("API: Fetching individual answers for survey_id=$survey_id from responses table");
                            
                            // Check if the responses table exists
                            $tables_result = $conn->query("SHOW TABLES LIKE 'responses'");
                            if ($tables_result->num_rows == 0) {
                                error_log("API: responses table doesn't exist");
                                throw new Exception("Responses table doesn't exist in the database");
                            }
                            
                            // Check for required columns based on user description
                            $resp_cols = $conn->query("SHOW COLUMNS FROM responses");
                            $required_resp_cols = ['survey_id', 'user_id', 'question_id', 'answer_text', 'submitted_at']; // option_id is optional for this logic
                            $resp_cols_found = [];
                            while ($col = $resp_cols->fetch_assoc()) {
                                $resp_cols_found[] = $col['Field'];
                            }
                            
                            foreach ($required_resp_cols as $required_col) {
                                if (!in_array($required_col, $resp_cols_found)) {
                                    error_log("API: responses table missing required column: $required_col");
                                    throw new Exception("Responses table structure is missing required column: $required_col.");
                                }
                            }
                            
                            // Fetch all individual answer rows for the survey
                            $responses_stmt = $conn->prepare("
                                SELECT user_id, question_id, option_id, answer_text, submitted_at 
                                FROM responses
                                WHERE survey_id = ?
                                ORDER BY submitted_at ASC, user_id ASC, question_id ASC -- Order to help grouping
                            ");
                            
                            if ($responses_stmt === false) {
                                error_log("API: Failed to prepare responses statement: " . $conn->error);
                                throw new Exception('Failed to prepare responses statement: ' . $conn->error);
                            }
                            
                            $responses_stmt->bind_param("i", $survey_id);
                            
                            if (!$responses_stmt->execute()) {
                                error_log("API: Failed to execute responses query: " . $responses_stmt->error);
                                throw new Exception('Failed to execute responses query: ' . $responses_stmt->error);
                            }
                            
                            $responses_result = $responses_stmt->get_result();
                            if ($responses_result === false) {
                                error_log("API: Failed to get result from responses query: " . $conn->error);
                                throw new Exception('Failed to get result from responses query: ' . $conn->error);
                            }
                            
                            error_log("API: Grouping individual answers into submissions based on user_id and submitted_at");
                            $submissions = [];
                            $submission_map = [];
                            
                            while ($row = $responses_result->fetch_assoc()) {
                                // Create a unique key for the submission based on user and time
                                // Handle potential null user_id for anonymous surveys
                                $user_key = $row['user_id'] ?? 'anonymous'; 
                                $submission_key = $user_key . '_' . $row['submitted_at'];
                                
                                // If this is the first answer for this submission, create the submission entry
                                if (!isset($submission_map[$submission_key])) {
                                    $submission_map[$submission_key] = count($submissions);
                                    $submissions[] = [
                                        // We don't have a real response ID, use the generated key or index?
                                        // Let's use the key for now, though JS might need adjustment if it expects integer ID
                                        'id' => $submission_key, 
                                        'survey_id' => $survey_id, 
                                        'user_id' => $row['user_id'],
                                        // We don't have ip_address in the described schema
                                        // 'ip_address' => $row['ip_address'], 
                                        'submitted_at' => $row['submitted_at'],
                                        'answers' => []
                                    ];
                                }
                                
                                // Add the current answer to the correct submission
                                $submission_index = $submission_map[$submission_key];
                                $submissions[$submission_index]['answers'][] = [
                                    // We don't have a separate answer ID, use question ID maybe?
                                    'id' => $row['question_id'], // Or perhaps null? JS might need this.
                                    'question_id' => $row['question_id'],
                                    'answer' => $row['answer_text'],
                                     // Include option_id if needed by JS later, might be null
                                    'option_id' => $row['option_id'] 
                                ];
                            }
                            
                            $survey['responses'] = $submissions; // Assign the grouped submissions
                            error_log("API: Successfully grouped " . $responses_result->num_rows . " answers into " . count($submissions) . " submissions.");
                            
                        } catch (Exception $e) {
                            error_log("API: Error processing responses based on schema: " . $e->getMessage());
                            // Don't need to reset $survey['responses'] as it's already set to [] above
                            $survey['response_error'] = $e->getMessage();
                        }
                    }
                    
                    error_log("API: Successfully returning survey data for survey_id=$survey_id");
                    echo json_encode([
                        'success' => true,
                        'data' => $survey
                    ]);
                } else {
                    error_log("API: Survey not found or user not authorized");
                    http_response_code(404);
                    echo json_encode(['error' => 'Survey not found']);
                }
            } else {
                // Get all surveys for user
                $stmt = $conn->prepare("
                    SELECT id, title, description, status, created_at,
                        (SELECT COUNT(*) FROM responses WHERE survey_id = surveys.id) as response_count
                    FROM surveys 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC
                ");

                if ($stmt === false) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Database error: ' . $conn->error]);
                    exit;
                }

                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $surveys = $stmt->get_result();
                
                $result = [];
                while ($survey = $surveys->fetch_assoc()) {
                    $result[] = $survey;
                }
                
                echo json_encode($result);
            }
            break;

        case 'POST':
            // Create new survey
            $json_data = file_get_contents('php://input');
            $data = json_decode($json_data, true);
            
            if (!$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON data: ' . json_last_error_msg()]);
                exit;
            }

            // Validate required fields
            if (empty($data['title'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Survey title is required']);
                exit;
            }

            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Create survey
                $title = $data['title'];
                $description = $data['description'] ?? '';
                
                $survey_stmt = $conn->prepare("INSERT INTO surveys (user_id, title, description) VALUES (?, ?, ?)");
                
                if ($survey_stmt === false) {
                    throw new Exception('Failed to prepare survey insert statement: ' . $conn->error);
                }
                
                $survey_stmt->bind_param("iss", $user_id, $title, $description);
                if (!$survey_stmt->execute()) {
                    throw new Exception('Failed to create survey: ' . $conn->error);
                }
                
                $survey_id = $conn->insert_id;
                
                // Add questions
                if (!empty($data['questions'])) {
                    $question_stmt = $conn->prepare("INSERT INTO questions (survey_id, question_text, question_type, required, order_position) 
                             VALUES (?, ?, ?, ?, ?)");
                    
                    if ($question_stmt === false) {
                        throw new Exception('Failed to prepare question insert statement: ' . $conn->error);
                    }
                    
                    foreach ($data['questions'] as $index => $q) {
                        $question_text = $q['questionText'] ?? '';
                        $question_type = $q['questionType'] ?? 'text';
                        $required = isset($q['required']) && $q['required'] ? 1 : 0;
                        
                        $question_stmt->bind_param("issii", $survey_id, $question_text, $question_type, $required, $index);
                        if (!$question_stmt->execute()) {
                            throw new Exception('Failed to add question: ' . $conn->error);
                        }
                        
                        $question_id = $conn->insert_id;
                        
                        // Add options if they exist
                        if (!empty($q['options'])) {
                            $option_stmt = $conn->prepare("INSERT INTO options (question_id, option_text, order_position) 
                                 VALUES (?, ?, ?)");
                            
                            if ($option_stmt === false) {
                                throw new Exception('Failed to prepare option insert statement: ' . $conn->error);
                            }
                            
                            foreach ($q['options'] as $o_index => $option) {
                                if (is_array($option) && isset($option['text'])) {
                                    $option_text = $option['text'];
                                    $option_position = isset($option['position']) ? (int)$option['position'] : $o_index + 1;
                                    
                                    $option_stmt->bind_param("isi", $question_id, $option_text, $option_position);
                                    if (!$option_stmt->execute()) {
                                        throw new Exception('Failed to add option: ' . $conn->error);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'survey_id' => $survey_id,
                    'message' => 'Survey created successfully'
                ]);
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                
                http_response_code(500);
                echo json_encode([
                    'error' => $e->getMessage()
                ]);
            }
            break;

        case 'PUT':
            // Update existing survey
            $survey_id = $_GET['id'] ?? null;
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$survey_id || !$data) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
                break;
            }
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM surveys WHERE id = ? AND user_id = ?");

            if ($stmt === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                break;
            }

            $stmt->bind_param("ii", $survey_id, $user_id);
            $stmt->execute();
            $check = $stmt->get_result();

            if (!$check || $check->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Update survey details
                $update_stmt = $conn->prepare("UPDATE surveys SET title = ?, description = ? WHERE id = ?");
                
                if ($update_stmt === false) {
                    throw new Exception('Failed to prepare update statement: ' . $conn->error);
                }
                
                $title = $data['title'];
                $description = $data['description'] ?? '';
                
                $update_stmt->bind_param("ssi", $title, $description, $survey_id);
                if (!$update_stmt->execute()) {
                    throw new Exception('Failed to update survey: ' . $conn->error);
                }
                
                // Delete existing questions
                $delete_stmt = $conn->prepare("DELETE FROM questions WHERE survey_id = ?");
                
                if ($delete_stmt === false) {
                    throw new Exception('Failed to prepare delete statement: ' . $conn->error);
                }
                
                $delete_stmt->bind_param("i", $survey_id);
                if (!$delete_stmt->execute()) {
                    throw new Exception('Failed to delete existing questions: ' . $conn->error);
                }
                
                // Add updated questions
                if (!empty($data['questions'])) {
                    $question_stmt = $conn->prepare("INSERT INTO questions (survey_id, question_text, question_type, required, order_position) 
                                    VALUES (?, ?, ?, ?, ?)");
                    
                    if ($question_stmt === false) {
                        throw new Exception('Failed to prepare question insert statement: ' . $conn->error);
                    }
                    
                    $option_stmt = $conn->prepare("INSERT INTO options (question_id, option_text, order_position) 
                                   VALUES (?, ?, ?)");
                    
                    if ($option_stmt === false) {
                        throw new Exception('Failed to prepare option insert statement: ' . $conn->error);
                    }
                    
                    foreach ($data['questions'] as $index => $q) {
                        $question_text = $q['questionText'] ?? '';
                        $question_type = $q['questionType'] ?? 'text';
                        $required = isset($q['required']) && $q['required'] ? 1 : 0;
                        
                        $question_stmt->bind_param("issis", $survey_id, $question_text, $question_type, $required, $index);
                        if (!$question_stmt->execute()) {
                            throw new Exception('Failed to update questions: ' . $conn->error);
                        }
                        
                        $question_id = $conn->insert_id;
                        
                        // Add options if they exist
                        if (!empty($q['options'])) {
                            foreach ($q['options'] as $o_index => $option) {
                                if (is_array($option) && isset($option['text'])) {
                                    $option_text = $option['text'];
                                    $option_position = isset($option['position']) ? (int)$option['position'] : $o_index + 1;
                                    
                                    $option_stmt->bind_param("isi", $question_id, $option_text, $option_position);
                                    if (!$option_stmt->execute()) {
                                        throw new Exception('Failed to add option: ' . $conn->error);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Commit transaction
                $conn->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Survey updated successfully'
                ]);
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                
                http_response_code(500);
                echo json_encode([
                    'error' => $e->getMessage()
                ]);
            }
            break;

        case 'DELETE':
            // Delete survey
            $survey_id = $_GET['id'] ?? null;
            
            if (!$survey_id) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid request']);
                break;
            }
            
            // Verify ownership
            $stmt = $conn->prepare("SELECT id FROM surveys WHERE id = ? AND user_id = ?");

            if ($stmt === false) {
                http_response_code(500);
                echo json_encode(['error' => 'Database error: ' . $conn->error]);
                break;
            }

            $stmt->bind_param("ii", $survey_id, $user_id);
            $stmt->execute();
            $check = $stmt->get_result();

            if (!$check || $check->num_rows === 0) {
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get all questions for this survey
                $questions_stmt = $conn->prepare("SELECT id FROM questions WHERE survey_id = ?");
                
                if ($questions_stmt === false) {
                    throw new Exception("Failed to prepare questions statement: " . $conn->error);
                }
                
                $questions_stmt->bind_param("i", $survey_id);
                $questions_stmt->execute();
                $questions = $questions_stmt->get_result();
                
                if ($questions && $questions->num_rows > 0) {
                    while ($question = $questions->fetch_assoc()) {
                        $question_id = $question['id'];
                        
                        // Delete options for this question
                        $options_stmt = $conn->prepare("DELETE FROM options WHERE question_id = ?");
                        
                        if ($options_stmt === false) {
                            throw new Exception("Failed to prepare options delete statement: " . $conn->error);
                        }
                        
                        $options_stmt->bind_param("i", $question_id);
                        $options_stmt->execute();
                    }
                }
                
                // Delete questions
                $questions_delete_stmt = $conn->prepare("DELETE FROM questions WHERE survey_id = ?");
                
                if ($questions_delete_stmt === false) {
                    throw new Exception("Failed to prepare questions delete statement: " . $conn->error);
                }
                
                $questions_delete_stmt->bind_param("i", $survey_id);
                $questions_delete_stmt->execute();
                
                // Delete responses
                $responses_stmt = $conn->prepare("DELETE FROM responses WHERE survey_id = ?");
                
                if ($responses_stmt === false) {
                    throw new Exception("Failed to prepare responses delete statement: " . $conn->error);
                }
                
                $responses_stmt->bind_param("i", $survey_id);
                $responses_stmt->execute();
                
                // Delete survey
                $survey_stmt = $conn->prepare("DELETE FROM surveys WHERE id = ?");
                
                if ($survey_stmt === false) {
                    throw new Exception("Failed to prepare survey delete statement: " . $conn->error);
                }
                
                $survey_stmt->bind_param("i", $survey_id);
                if ($survey_stmt->execute()) {
                    // Commit transaction
                    $conn->commit();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Survey deleted successfully'
                    ]);
                } else {
                    throw new Exception("Failed to delete survey: " . $conn->error);
                }
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                
                http_response_code(500);
                echo json_encode([
                    'error' => 'Failed to delete survey: ' . $e->getMessage()
                ]);
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
} catch (Exception $e) {
    // Catch any unexpected exceptions
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
