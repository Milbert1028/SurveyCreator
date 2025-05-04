<?php
// Set content type
header('Content-Type: application/json');

// Collect system information
$info = [
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'php' => [
        'version' => PHP_VERSION,
        'extensions' => get_loaded_extensions(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time')
    ],
    'server' => [
        'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
    ],
    'file_access' => [],
    'database' => [
        'status' => 'not checked'
    ]
];

// Check file access
$required_files = [
    '../includes/config.php',
    '../includes/functions.php'
];

foreach ($required_files as $file) {
    $info['file_access'][$file] = [
        'exists' => file_exists($file),
        'readable' => is_readable($file)
    ];
}

// Try to include required files
$config_included = false;
$functions_included = false;
try {
    if (file_exists('../includes/config.php')) {
        require_once '../includes/config.php';
        $config_included = true;
    }
    
    if (file_exists('../includes/functions.php')) {
        require_once '../includes/functions.php';
        $functions_included = true;
    }
    
    $info['includes'] = [
        'config' => $config_included,
        'functions' => $functions_included
    ];
} catch (Exception $e) {
    $info['includes_error'] = $e->getMessage();
}

// Check database connection
try {
    if (class_exists('Database')) {
        $info['database']['class_exists'] = true;
        
        if (method_exists('Database', 'getInstance')) {
            $info['database']['method_exists'] = true;
            
            $db = Database::getInstance();
            if ($db) {
                $info['database']['status'] = 'connected';
                
                // Test a simple query
                $test_query = $db->query("SELECT 1 AS test");
                if ($test_query) {
                    $info['database']['query_test'] = 'success';
                } else {
                    $info['database']['query_test'] = 'failed';
                    $info['database']['error'] = $db->error;
                }
                
                // Check tables
                $tables_query = $db->query("SHOW TABLES");
                if ($tables_query) {
                    $tables = [];
                    while ($row = $tables_query->fetch_row()) {
                        $tables[] = $row[0];
                    }
                    $info['database']['tables'] = $tables;
                    
                    // Check responses table structure
                    if (in_array('responses', $tables)) {
                        $structure_query = $db->query("DESCRIBE responses");
                        if ($structure_query) {
                            $columns = [];
                            while ($row = $structure_query->fetch_assoc()) {
                                $columns[] = $row;
                            }
                            $info['database']['responses_structure'] = $columns;
                        }
                    }
                }
            } else {
                $info['database']['status'] = 'connection failed';
            }
        } else {
            $info['database']['method_exists'] = false;
        }
    } else {
        $info['database']['class_exists'] = false;
    }
} catch (Exception $e) {
    $info['database']['error'] = $e->getMessage();
}

// Check for errors
if (!$config_included || !$functions_included || $info['database']['status'] !== 'connected') {
    $info['success'] = false;
}

// Output the diagnostic information
echo json_encode($info, JSON_PRETTY_PRINT);
exit; 