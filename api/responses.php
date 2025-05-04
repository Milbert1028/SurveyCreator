<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get database instance
$db = Database::getInstance();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

switch ($method) {
    case 'GET':
        // Ensure user is logged in for viewing responses
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $survey_id = $_GET['survey_id'] ?? null;
        
        if (!$survey_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Survey ID is required']);
            break;
        }

        // Verify survey ownership
        $user_id = $_SESSION['user_id'];
        $check = $db->query("SELECT id FROM surveys WHERE id = $survey_id AND user_id = $user_id");
        if (!$check || $check->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        // Get survey responses with questions and their options
        $sql = "
            SELECT 
                r.id as response_id,
                r.submitted_at,
                q.id as question_id,
                q.question_text,
                q.question_type,
                r.answer_text,
                r.option_id,
                GROUP_CONCAT(
                    DISTINCT 
                    CONCAT(o.id, ':', o.option_text, ':', o.order_position) 
                    ORDER BY o.order_position
                    SEPARATOR '|'
                ) as options_data
            FROM responses r
            JOIN questions q ON r.question_id = q.id
            LEFT JOIN options o ON q.id = o.question_id
            WHERE r.survey_id = ?
            GROUP BY r.id, r.submitted_at, q.id, q.question_text, q.question_type, r.answer_text, r.option_id
            ORDER BY r.submitted_at DESC, q.order_position ASC
        ";

        $stmt = $db->prepare($sql);
        $stmt->bind_param('i', $survey_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$result) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
            break;
        }

        // Group responses by submission
        $responses = [];
        $questions = [];
        
        while ($row = $result->fetch_assoc()) {
            $response_id = $row['response_id'];
            if (!isset($responses[$response_id])) {
                $responses[$response_id] = [
                    'id' => $response_id,
                    'submitted_at' => $row['submitted_at'],
                    'answers' => []
                ];
            }
            
            // Track unique questions
            if (!isset($questions[$row['question_id']])) {
                $questions[$row['question_id']] = [
                    'id' => $row['question_id'],
                    'text' => $row['question_text'],
                    'type' => $row['question_type'],
                    'options' => []
                ];
            }
            
            // Add options to questions data
            if ($row['options_data']) {
                foreach (explode('|', $row['options_data']) as $optionData) {
                    list($id, $text, $order_position) = explode(':', $optionData);
                    $questions[$row['question_id']]['options'][] = [
                        'id' => intval($id),
                        'text' => $text,
                        'order_position' => intval($order_position)
                    ];
                }
                // Remove duplicates
                if (isset($questions[$row['question_id']]['options'])) {
                    $options = [];
                    $seen_ids = [];
                    foreach ($questions[$row['question_id']]['options'] as $option) {
                        if (!in_array($option['id'], $seen_ids)) {
                            $options[] = $option;
                            $seen_ids[] = $option['id'];
                        }
                    }
                    $questions[$row['question_id']]['options'] = $options;
                }
            }
            
            // Process options
            $options = [];
            if ($row['options_data']) {
                foreach (explode('|', $row['options_data']) as $optionData) {
                    list($id, $text, $order_position) = explode(':', $optionData);
                    $options[$id] = [
                        'text' => $text,
                        'order_position' => $order_position
                    ];
                }
            }
            
            // Get answer based on question type
            $answer = '';
            if ($row['question_type'] === 'text') {
                $answer = $row['answer_text'];
            } else if ($row['question_type'] === 'rating' && $row['option_id']) {
                // For rating questions, get the actual rating value from options
                $answer = isset($options[$row['option_id']]) ? 
                    $options[$row['option_id']]['order_position'] : $row['option_id'];
            } else {
                // For other question types, use the option text
                $answer = ($row['option_id'] && isset($options[$row['option_id']])) ? $options[$row['option_id']]['text'] : null;
            }
            
            $responses[$response_id]['answers'][] = [
                'question_id' => $row['question_id'],
                'answer' => $answer,
                'options' => $options
            ];
        }
        
        // Format final response
        $response_data = [
            'questions' => array_values($questions),
            'responses' => array_values($responses)
        ];

        // Debug log
        error_log('Response data: ' . json_encode([
            'question_count' => count($questions),
            'response_count' => count($responses)
        ]));

        echo json_encode($response_data);
        break;

    case 'POST':
        // Submit new response
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['survey_id']) || !isset($data['answers'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            break;
        }

        $survey_id = (int)$data['survey_id'];
        
        // Verify survey exists and is published
        $survey_check = $db->query("SELECT id FROM surveys WHERE id = $survey_id AND status = 'published'");
        if (!$survey_check || $survey_check->num_rows === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Survey not found or not available']);
            break;
        }

        // Start transaction
        $db->query('START TRANSACTION');
        
        try {
            $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
            
            // Prepare statement for inserting responses
            $stmt = $db->prepare("
                INSERT INTO responses (
                    survey_id,
                    user_id,
                    question_id,
                    option_id,
                    answer_text,
                    submitted_at
                ) VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            foreach ($data['answers'] as $answer) {
                if (!isset($answer['question_id'])) {
                    throw new Exception('Question ID is required for each answer');
                }
                
                $option_id = $answer['option_id'] ?? null;
                $answer_text = $answer['text'] ?? null;
                
                $stmt->bind_param('iiiss',
                    $survey_id,
                    $user_id,
                    $answer['question_id'],
                    $option_id,
                    $answer_text
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to save response');
                }
            }
            
            $db->query('COMMIT');
            echo json_encode(['success' => true]);
            
        } catch (Exception $e) {
            $db->query('ROLLBACK');
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;
        
    case 'DELETE':
        // Ensure user is logged in for deleting responses
        if (!is_logged_in()) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $response_id = $_GET['id'] ?? null;
        
        if (!$response_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Response ID is required']);
            break;
        }

        // Verify response belongs to user's survey
        $user_id = $_SESSION['user_id'];
        $check = $db->query("
            SELECT r.id 
            FROM responses r
            JOIN surveys s ON r.survey_id = s.id
            WHERE r.id = $response_id AND s.user_id = $user_id
        ");

        if (!$check || $check->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        // Delete response
        if ($db->query("DELETE FROM responses WHERE id = $response_id")) {
            echo json_encode([
                'success' => true,
                'message' => 'Response deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to delete response'
            ]);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
