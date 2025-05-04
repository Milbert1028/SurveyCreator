<?php
// This is a minimal test API endpoint
header('Content-Type: application/json');

// Create a response array
$response = [
    'success' => true,
    'message' => 'API test successful',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
    ]
];

// Output JSON
echo json_encode($response);
exit; 