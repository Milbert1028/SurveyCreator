<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get database instance
$db = Database::getInstance();

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

switch ($method) {
    case 'GET':
        // Get template data
        $template_id = $_GET['id'] ?? null;
        
        if ($template_id) {
            // Get specific template
            $template = $db->query("SELECT * FROM templates WHERE id = $template_id");
            
            if ($template && $template->num_rows > 0) {
                echo json_encode($template->fetch_assoc());
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Template not found']);
            }
        } else {
            // Get all templates
            $templates = $db->query("SELECT * FROM templates ORDER BY name");
            
            $result = [];
            while ($template = $templates->fetch_assoc()) {
                $result[] = $template;
            }
            
            echo json_encode($result);
        }
        break;

    case 'POST':
        // Create new template (admin only)
        if (!is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['name']) || !isset($data['structure'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request data']);
            break;
        }

        $name = $db->escape($data['name']);
        $description = $db->escape($data['description'] ?? '');
        $structure = $db->escape(json_encode($data['structure']));
        
        $sql = "INSERT INTO templates (name, description, structure) VALUES ('$name', '$description', '$structure')";
        
        if ($db->query($sql)) {
            echo json_encode([
                'success' => true,
                'template_id' => $db->getLastId(),
                'message' => 'Template created successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create template']);
        }
        break;

    case 'PUT':
        // Update existing template (admin only)
        if (!is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $template_id = $_GET['id'] ?? null;
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$template_id || !$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid request']);
            break;
        }
        
        $updates = [];
        if (isset($data['name'])) {
            $updates[] = "name = '" . $db->escape($data['name']) . "'";
        }
        if (isset($data['description'])) {
            $updates[] = "description = '" . $db->escape($data['description']) . "'";
        }
        if (isset($data['structure'])) {
            $updates[] = "structure = '" . $db->escape(json_encode($data['structure'])) . "'";
        }
        
        if (empty($updates)) {
            http_response_code(400);
            echo json_encode(['error' => 'No updates provided']);
            break;
        }
        
        $sql = "UPDATE templates SET " . implode(', ', $updates) . " WHERE id = $template_id";
        
        if ($db->query($sql)) {
            echo json_encode([
                'success' => true,
                'message' => 'Template updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update template']);
        }
        break;

    case 'DELETE':
        // Delete template (admin only)
        if (!is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            break;
        }

        $template_id = $_GET['id'] ?? null;
        
        if (!$template_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Template ID is required']);
            break;
        }
        
        if ($db->query("DELETE FROM templates WHERE id = $template_id")) {
            echo json_encode([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete template']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
