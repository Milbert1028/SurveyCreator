<?php
// Simple test file to verify the API is accessible
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'API test endpoint is working!',
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 