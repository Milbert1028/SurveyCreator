<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Please login to export responses']);
    exit;
}

// Get survey ID
$survey_id = $_GET['survey_id'] ?? null;
if (!$survey_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Survey ID is required']);
    exit;
}

// Get database instance
$db = Database::getInstance();

// Verify survey ownership
$user_id = $_SESSION['user_id'];
$check = $db->query("SELECT title FROM surveys WHERE id = $survey_id AND user_id = $user_id");
if (!$check || $check->num_rows === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$survey_title = $check->fetch_assoc()['title'];

// Get all questions for this survey
$questions = $db->query("
    SELECT 
        q.id,
        q.question_text,
        q.question_type,
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

if (!$questions) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch questions']);
    exit;
}

// Prepare questions data
$question_data = [];
$headers = ['Response ID', 'Submitted At'];

while ($question = $questions->fetch_assoc()) {
    $headers[] = $question['question_text'];
    $question_data[$question['id']] = [
        'text' => $question['question_text'],
        'type' => $question['question_type'],
        'options' => []
    ];
    
    if ($question['options_data']) {
        foreach (explode('|', $question['options_data']) as $optionData) {
            list($id, $text) = explode(':', $optionData);
            $question_data[$question['id']]['options'][$id] = $text;
        }
    }
}

// Get all responses
$responses = $db->query("
    SELECT 
        r.id as response_id,
        r.submitted_at,
        r.question_id,
        r.option_id,
        r.answer_text
    FROM responses r
    WHERE r.survey_id = $survey_id
    ORDER BY r.submitted_at, r.question_id
");

if (!$responses) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch responses']);
    exit;
}

// Group responses by submission
$grouped_responses = [];
while ($response = $responses->fetch_assoc()) {
    $response_id = $response['response_id'];
    $submitted_at = $response['submitted_at'];
    
    if (!isset($grouped_responses[$response_id])) {
        $grouped_responses[$response_id] = [
            'id' => $response_id,
            'submitted_at' => $submitted_at,
            'answers' => []
        ];
    }
    
    $question_id = $response['question_id'];
    $question = $question_data[$question_id];
    
    // Format answer based on question type
    if ($question['type'] === 'text') {
        $answer = $response['answer_text'];
    } else {
        $option_id = $response['option_id'];
        $answer = $question['options'][$option_id] ?? '';
    }
    
    $grouped_responses[$response_id]['answers'][$question_id] = $answer;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . sanitize_filename($survey_title) . '_responses_' . date('Y-m-d') . '.csv"');

// Create output buffer
$output = fopen('php://output', 'w');

// Write UTF-8 BOM
fputs($output, "\xEF\xBB\xBF");

// Write headers
fputcsv($output, $headers);

// Write data rows
foreach ($grouped_responses as $response) {
    $row = [$response['id'], $response['submitted_at']];
    
    foreach ($question_data as $question_id => $question) {
        $row[] = $response['answers'][$question_id] ?? '';
    }
    
    fputcsv($output, $row);
}

fclose($output);

// Helper function to sanitize filename
function sanitize_filename($filename) {
    // Remove any character that isn't a letter, number, space, hyphen, or underscore
    $filename = preg_replace('/[^a-zA-Z0-9 \-_]/', '', $filename);
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Trim underscores from beginning and end
    return trim($filename, '_');
}
