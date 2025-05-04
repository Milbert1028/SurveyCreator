<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Function to return JSON response
function json_response($success, $message = '', $data = null) {
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Ensure user is logged in
if (!is_logged_in()) {
    json_response(false, 'Please login to access templates');
}

// Get template ID
$template_id = $_GET['id'] ?? null;
if (!$template_id || !is_numeric($template_id)) {
    json_response(false, 'Invalid template ID');
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Get template data
    $stmt = $conn->prepare("SELECT name, description, structure FROM templates WHERE id = ?");
    $stmt->bind_param('i', $template_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $template = $result->fetch_assoc();
    $stmt->close();
    
    if (!$template) {
        json_response(false, 'Template not found');
    }
    
    // Decode JSON structure
    $structure = json_decode($template['structure'], true);
    if (!$structure) {
        json_response(false, 'Invalid template structure');
    }
    
    json_response(true, 'Template loaded successfully', $structure);
    
} catch (Exception $e) {
    json_response(false, 'Error loading template: ' . $e->getMessage());
}
