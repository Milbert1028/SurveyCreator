<?php
/**
 * API Proxy Script
 * This script forwards API requests to the appropriate endpoint
 * to solve cross-domain issues in production
 */
require_once '../includes/config.php';

// Allow from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Check if it's a preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the API endpoint from the query parameters
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : null;

if (!$endpoint) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing endpoint parameter']);
    exit;
}

// Security check - only allow specific endpoints
$allowed_endpoints = [
    'forgot-password',
    'save-survey',
    'templates',
    'get-template',
    'surveys',
    'responses',
    'export'
];

// Extract the base endpoint name
$base_endpoint = preg_replace('/\.php$/', '', basename($endpoint));

// Remove directory path from endpoint for checking allowed endpoints
if (!in_array($base_endpoint, $allowed_endpoints)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Endpoint not allowed']);
    exit;
}

// Build the path to the target endpoint
// First, check if it's a path with subdirectories
if (strpos($endpoint, '/') !== false) {
    // Endpoint includes subdirectories (e.g., auth/forgot-password.php)
    $target_path = dirname(__DIR__) . '/' . $endpoint;
    if (!file_exists($target_path)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $target_path]);
        exit;
    }
} else {
    // Direct endpoint in api directory
    $target_path = __DIR__ . '/' . $endpoint;
    if (!file_exists($target_path)) {
        $target_path = __DIR__ . '/' . $endpoint . '.php';
        if (!file_exists($target_path)) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found: ' . $target_path]);
            exit;
        }
    }
}

// Capture input data
$input = file_get_contents('php://input');
$_POST_BACKUP = $_POST;

// If JSON input, parse it and merge with $_POST
if (!empty($input) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $json_data = json_decode($input, true);
    if ($json_data) {
        $_POST = array_merge($_POST, $json_data);
    }
}

// Start output buffering to capture the API response
ob_start();
include $target_path;
$response = ob_get_clean();

// Restore original $_POST
$_POST = $_POST_BACKUP;

// Return the response
echo $response; 