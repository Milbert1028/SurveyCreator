<?php
// Database connection test script
// This file is used to diagnose database connectivity issues

// Set proper headers
header('Content-Type: application/json');

// Start output buffering to prevent partial output on error
ob_start();

// Enable error display for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create a log file
$log_file = __DIR__ . '/../logs/db-test.log';
$time = date('Y-m-d H:i:s');
file_put_contents($log_file, "[$time] Running DB test\n", FILE_APPEND);

// Initialize response
$response = [
    'success' => false,
    'messages' => [],
    'errors' => [],
    'database_info' => [],
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'time' => $time
    ]
];

try {
    // Log config file inclusion
    file_put_contents($log_file, "[$time] Including configuration file\n", FILE_APPEND);
    require_once '../includes/config.php';
    $response['messages'][] = "Configuration file loaded successfully";
    $response['database_info'] = [
        'host' => DB_HOST,
        'user' => DB_USER,
        'database' => DB_NAME,
        // We don't include password for security reasons
    ];
    
    // Attempt direct database connection
    file_put_contents($log_file, "[$time] Attempting direct database connection\n", FILE_APPEND);
    $direct_conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($direct_conn->connect_error) {
        throw new Exception("Direct connection failed: " . $direct_conn->connect_error);
    }
    
    $response['messages'][] = "Direct database connection successful";
    
    // Test a simple query
    file_put_contents($log_file, "[$time] Testing simple query\n", FILE_APPEND);
    $result = $direct_conn->query("SELECT 1 AS test");
    if ($result) {
        $row = $result->fetch_assoc();
        $response['messages'][] = "Simple query executed successfully: " . json_encode($row);
    } else {
        throw new Exception("Simple query failed: " . $direct_conn->error);
    }
    
    // Try to load Database class
    file_put_contents($log_file, "[$time] Loading Database class\n", FILE_APPEND);
    require_once '../includes/db.php';
    $response['messages'][] = "Database class loaded successfully";
    
    // Test singleton connection
    file_put_contents($log_file, "[$time] Testing singleton connection\n", FILE_APPEND);
    $db = Database::getInstance();
    $response['messages'][] = "Database singleton instance created successfully";
    
    // Test a query using the singleton
    file_put_contents($log_file, "[$time] Testing query with singleton\n", FILE_APPEND);
    $test_query = $db->query("SELECT COUNT(*) AS survey_count FROM surveys");
    if ($test_query) {
        $row = $test_query->fetch_assoc();
        $response['messages'][] = "Survey count query successful. Total surveys: " . $row['survey_count'];
    } else {
        throw new Exception("Survey count query failed");
    }
    
    // Test settings table if it exists
    file_put_contents($log_file, "[$time] Testing settings table query\n", FILE_APPEND);
    $settings_query = $db->query("SHOW TABLES LIKE 'settings'");
    if ($settings_query && $settings_query->num_rows > 0) {
        $settings_result = $db->query("SELECT * FROM settings LIMIT 5");
        if ($settings_result) {
            $settings = [];
            while ($row = $settings_result->fetch_assoc()) {
                $settings[] = $row;
            }
            $response['messages'][] = "Settings retrieved: " . count($settings);
        } else {
            $response['errors'][] = "Could not query settings table";
        }
    } else {
        $response['messages'][] = "Settings table does not exist (this is not necessarily an error)";
    }
    
    // All tests passed
    $response['success'] = true;
    
    // Close the direct connection
    $direct_conn->close();
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['errors'][] = $e->getMessage();
    file_put_contents($log_file, "[$time] ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Log completion
file_put_contents($log_file, "[$time] Database test completed with success=" . ($response['success'] ? 'true' : 'false') . "\n", FILE_APPEND);

// Output JSON response
echo json_encode($response, JSON_PRETTY_PRINT);

// End output buffering
ob_end_flush();
exit; 