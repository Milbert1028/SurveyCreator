<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Only allow admin users or in development mode
if (!is_admin() && !defined('DEVELOPMENT_MODE')) {
    http_response_code(403);
    echo "Access denied";
    exit;
}

header('Content-Type: text/plain');
echo "Template Debug Information\n";
echo "=========================\n\n";

// Check database connection
echo "Database Connection:\n";
try {
    $db = Database::getInstance();
    echo "- Connection successful\n";
    
    // Test a simple query
    $result = $db->query("SELECT 1");
    if ($result) {
        echo "- Test query successful\n";
    } else {
        echo "- Test query failed: " . $db->getLastError() . "\n";
    }
} catch (Exception $e) {
    echo "- Connection failed: " . $e->getMessage() . "\n";
}

// Check templates table
echo "\nTemplates Table:\n";
try {
    $tables = $db->query("SHOW TABLES LIKE 'templates'");
    if ($tables && $tables->num_rows > 0) {
        echo "- Table exists\n";
        
        // Get table structure
        $structure = $db->query("DESCRIBE templates");
        echo "- Table structure:\n";
        while ($row = $structure->fetch_assoc()) {
            echo "  - " . $row['Field'] . ": " . $row['Type'] . " " . 
                 ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . 
                 ($row['Key'] === 'PRI' ? ' (PRIMARY KEY)' : '') . "\n";
        }
        
        // Count templates
        $count = $db->query("SELECT COUNT(*) as count FROM templates");
        $countData = $count->fetch_assoc();
        echo "- Number of templates: " . $countData['count'] . "\n";
        
        // List templates
        $templates = $db->query("SELECT id, name FROM templates ORDER BY id");
        echo "- Available templates:\n";
        while ($template = $templates->fetch_assoc()) {
            echo "  - ID: " . $template['id'] . ", Name: " . $template['name'] . "\n";
        }
    } else {
        echo "- Table does not exist\n";
    }
} catch (Exception $e) {
    echo "- Error checking templates table: " . $e->getMessage() . "\n";
}

// Test template loading
echo "\nTemplate Loading Test:\n";
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    try {
        $template = $db->query("SELECT * FROM templates WHERE id = $id");
        if ($template && $template->num_rows > 0) {
            $templateData = $template->fetch_assoc();
            echo "- Found template ID $id: " . $templateData['name'] . "\n";
            
            // Check structure
            $structure = $templateData['structure'] ?? '';
            if (!empty($structure)) {
                echo "- Structure length: " . strlen($structure) . " bytes\n";
                
                // Validate JSON
                $decoded = json_decode($structure);
                if (json_last_error() === JSON_ERROR_NONE) {
                    echo "- Structure is valid JSON\n";
                    
                    // Check for expected format
                    if (isset($decoded->questions) && is_array($decoded->questions)) {
                        echo "- Has " . count($decoded->questions) . " questions\n";
                    } else {
                        echo "- Invalid structure format: 'questions' array missing\n";
                    }
                } else {
                    echo "- Invalid JSON: " . json_last_error_msg() . "\n";
                    echo "- First 200 chars: " . substr($structure, 0, 200) . "...\n";
                }
            } else {
                echo "- No structure data\n";
            }
        } else {
            echo "- Template ID $id not found\n";
        }
    } catch (Exception $e) {
        echo "- Error loading template: " . $e->getMessage() . "\n";
    }
} else {
    echo "- No template ID specified. Add ?id=X to the URL to test a specific template.\n";
}

echo "\nEnd of Debug Information"; 