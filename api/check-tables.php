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
    'tables' => [],
    'errors' => []
];

try {
    // Connect to database
    $db = Database::getInstance();
    
    // Define expected tables and check if they exist
    $expected_tables = [
        'surveys',
        'questions',
        'options',
        'responses',
        'users',
        'share_settings'
    ];
    
    foreach ($expected_tables as $table) {
        $check = $db->query("SHOW TABLES LIKE '$table'");
        $exists = ($check && $check->num_rows > 0);
        
        $response['tables'][$table] = [
            'exists' => $exists,
            'columns' => []
        ];
        
        if ($exists) {
            // Get table structure
            $columns = $db->query("DESCRIBE $table");
            
            if ($columns) {
                while ($column = $columns->fetch_assoc()) {
                    $response['tables'][$table]['columns'][] = $column;
                }
            }
        } else {
            $response['errors'][] = "Table '$table' does not exist";
        }
    }
    
    // Add suggested SQL to create missing tables
    if (!empty($response['errors'])) {
        $response['suggested_sql'] = [];
        
        // Add SQL for responses table if it's missing
        if (!$response['tables']['responses']['exists']) {
            $response['suggested_sql']['responses'] = "
CREATE TABLE responses (
  id int(11) NOT NULL AUTO_INCREMENT,
  survey_id int(11) NOT NULL,
  question_id int(11) NOT NULL,
  user_id int(11) DEFAULT NULL,
  option_id int(11) DEFAULT NULL,
  answer_text text DEFAULT NULL,
  submitted_at datetime NOT NULL,
  PRIMARY KEY (id),
  KEY survey_id (survey_id),
  KEY question_id (question_id),
  KEY option_id (option_id),
  KEY user_id (user_id)
)";
        }
        
        // Add SQL for share_settings table if it's missing
        if (!$response['tables']['share_settings']['exists']) {
            $response['suggested_sql']['share_settings'] = "
CREATE TABLE share_settings (
  id int(11) NOT NULL AUTO_INCREMENT,
  survey_id int(11) NOT NULL,
  require_login tinyint(1) NOT NULL DEFAULT 0,
  allow_multiple_responses tinyint(1) NOT NULL DEFAULT 0,
  response_limit int(11) DEFAULT NULL,
  close_date datetime DEFAULT NULL,
  show_progress_bar tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id),
  UNIQUE KEY survey_id (survey_id)
)";
        }
    }
    
    // Count existing records
    $record_counts = [];
    foreach ($response['tables'] as $table => $info) {
        if ($info['exists']) {
            $count_query = $db->query("SELECT COUNT(*) as count FROM $table");
            if ($count_query && $count_query->num_rows > 0) {
                $record_counts[$table] = $count_query->fetch_assoc()['count'];
            } else {
                $record_counts[$table] = 'Error counting';
            }
        } else {
            $record_counts[$table] = 'Table does not exist';
        }
    }
    
    $response['record_counts'] = $record_counts;
    $response['success'] = true;
    $response['message'] = "Database structure checked successfully";
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = "Error: " . $e->getMessage();
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
exit; 