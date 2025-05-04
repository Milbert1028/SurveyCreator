<?php
// Production-ready survey submission handler

// Make sure errors are logged, not displayed
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

// Set JSON content type
header('Content-Type: application/json');

// Enable CORS for local development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Create logs directory if it doesn't exist
$log_dir = __DIR__ . '/../logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
}

// Log API call
$log_file = $log_dir . '/api_access.log';
$time = date('Y-m-d H:i:s');
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
file_put_contents($log_file, "[$time] API called from $ip\n", FILE_APPEND);

try {
    // Get POST data
    $raw_data = file_get_contents('php://input');
    if (empty($raw_data)) {
        throw new Exception('No data received');
    }
    
    // Log received data
    file_put_contents($log_file, "[$time] Received data: " . substr($raw_data, 0, 100) . "\n", FILE_APPEND);
    
    // Parse JSON
    $data = json_decode($raw_data, true);
    if ($data === null) {
        throw new Exception('Invalid JSON data: ' . json_last_error_msg());
    }
    
    // Check required fields
    if (empty($data['survey_id'])) {
        throw new Exception('Survey ID is required');
    }
    
    if (empty($data['responses']) || !is_array($data['responses'])) {
        throw new Exception('Responses are required');
    }
    
    // Load required files
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    
    // Get database connection
    try {
        $db = Database::getInstance();
        $conn = $db->getConnection();
        file_put_contents($log_file, "[$time] Database connection successful\n", FILE_APPEND);
    } catch (Exception $db_error) {
        error_log("Database connection failed: " . $db_error->getMessage());
        throw new Exception('Database connection failed: ' . $db_error->getMessage());
    }
    
    // Get survey ID
    $survey_id = (int)$data['survey_id'];
    file_put_contents($log_file, "[$time] Processing survey ID: $survey_id\n", FILE_APPEND);
    
    // Check if survey exists
    $survey_query = $conn->query("SELECT id, status FROM surveys WHERE id = $survey_id LIMIT 1");
    if (!$survey_query || $survey_query->num_rows === 0) {
        throw new Exception('Survey not found');
    }
    
    $survey = $survey_query->fetch_assoc();
    if ($survey['status'] !== 'published') {
        throw new Exception('Survey is not published');
    }
    
    // Begin transaction - using direct connection instead of wrapper
    $conn->autocommit(false);
    
    // Get user ID
    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL';
    file_put_contents($log_file, "[$time] User ID: $user_id\n", FILE_APPEND);
    
    // Process responses
    $processed = 0;
    
    foreach ($data['responses'] as $response) {
        if (empty($response['question_id'])) {
            continue;
        }
        
        $question_id = (int)$response['question_id'];
        
        // Check if question belongs to this survey
        $question_check = $conn->query("SELECT id, question_type FROM questions WHERE id = $question_id AND survey_id = $survey_id");
        if (!$question_check || $question_check->num_rows === 0) {
            continue; // Skip invalid questions
        }
        
        $question = $question_check->fetch_assoc();
        
        // Handle choice questions
        if (($question['question_type'] === 'multiple_choice' || 
            $question['question_type'] === 'single_choice' || 
            $question['question_type'] === 'rating') && 
           !empty($response['selected_options']) && 
           is_array($response['selected_options'])) {
            
            foreach ($response['selected_options'] as $option_id) {
                $option_id = (int)$option_id;
                
                $sql = "INSERT INTO responses (survey_id, user_id, question_id, option_id, submitted_at) 
                        VALUES ($survey_id, $user_id, $question_id, $option_id, NOW())";
                
                try {
                    if ($conn->query($sql)) {
                        $processed++;
                        file_put_contents($log_file, "[$time] Inserted response for question $question_id, option $option_id\n", FILE_APPEND);
                    } else {
                        throw new Exception('Error executing SQL: ' . $conn->error);
                    }
                } catch (Exception $sql_error) {
                    file_put_contents($log_file, "[$time] SQL Error: " . $sql_error->getMessage() . "\n", FILE_APPEND);
                    throw $sql_error;
                }
            }
        }
        // Handle text answers
        else if ($question['question_type'] === 'text' && isset($response['text_answer'])) {
            $text = $conn->real_escape_string($response['text_answer']);
            
            $sql = "INSERT INTO responses (survey_id, user_id, question_id, answer_text, submitted_at) 
                    VALUES ($survey_id, $user_id, $question_id, '$text', NOW())";
            
            try {
                if ($conn->query($sql)) {
                    $processed++;
                    file_put_contents($log_file, "[$time] Inserted text response for question $question_id\n", FILE_APPEND);
                } else {
                    throw new Exception('Error executing SQL: ' . $conn->error);
                }
            } catch (Exception $sql_error) {
                file_put_contents($log_file, "[$time] SQL Error: " . $sql_error->getMessage() . "\n", FILE_APPEND);
                throw $sql_error;
            }
        }
    }
    
    // Check if we processed anything
    if ($processed === 0) {
        $conn->rollback();
        $conn->autocommit(true);
        throw new Exception('No valid responses were processed');
    }
    
    // Commit transaction
    $conn->commit();
    $conn->autocommit(true);
    file_put_contents($log_file, "[$time] Transaction committed successfully - $processed responses processed\n", FILE_APPEND);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Survey submitted successfully',
        'count' => $processed
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Survey submission error: " . $e->getMessage());
    file_put_contents($log_file, "[$time] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Rollback transaction if needed
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
            $conn->autocommit(true);
            file_put_contents($log_file, "[$time] Transaction rolled back\n", FILE_APPEND);
        } catch (Exception $rollback_error) {
            file_put_contents($log_file, "[$time] Error rolling back transaction: " . $rollback_error->getMessage() . "\n", FILE_APPEND);
        }
    }
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// End script
exit; 