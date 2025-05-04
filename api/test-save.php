<?php
// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Set content type
header('Content-Type: text/html');

// Create a log output
function log_message($message) {
    echo "<div>$message</div>";
}

// Ensure user is logged in
if (!is_logged_in()) {
    log_message("User not logged in. Please login first.");
    exit;
}

// Get database connection
try {
    $db = Database::getInstance();
    log_message("Database connection successful");
} catch (Exception $e) {
    log_message("Error connecting to database: " . $e->getMessage());
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Save Survey API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    </style>
</head>
<body>
    <h1>Test Save Survey API</h1>
    
    <p>This script will send a test survey to the save-survey.php API.</p>
    
    <h2>Current user info:</h2>
    <div>User ID: <?php echo $_SESSION['user_id'] ?? 'Not logged in'; ?></div>
    <div>Username: <?php echo $_SESSION['username'] ?? 'Not logged in'; ?></div>
    
    <h2>Test Survey Data</h2>
    <pre id="test-data"></pre>
    
    <button id="send-test">Send Test Survey</button>
    
    <h2>API Response</h2>
    <pre id="response"></pre>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create test data
            const testData = {
                title: "Test Survey " + new Date().toLocaleString(),
                description: "This is a test survey created by test-save.php",
                questions: [
                    {
                        question: "Test multiple choice question",
                        type: "multiple_choice",
                        required: true,
                        options: ["Option 1", "Option 2", "Option 3"]
                    },
                    {
                        question: "Test text question",
                        type: "text",
                        required: false,
                        options: []
                    }
                ]
            };
            
            // Display test data
            document.getElementById('test-data').textContent = JSON.stringify(testData, null, 2);
            
            // Add event listener to button
            document.getElementById('send-test').addEventListener('click', async function() {
                try {
                    const response = document.getElementById('response');
                    response.textContent = "Sending request...";
                    response.className = "";
                    
                    const result = await fetch('<?php echo SITE_URL; ?>/api/save-survey.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(testData)
                    });
                    
                    // Get response text first to see what was returned
                    const rawResponse = await result.text();
                    
                    // Try to parse as JSON
                    let jsonData;
                    try {
                        jsonData = JSON.parse(rawResponse);
                        response.textContent = JSON.stringify(jsonData, null, 2);
                        response.className = jsonData.success ? "success" : "error";
                    } catch (e) {
                        // If not JSON, just show raw response
                        response.textContent = "Error parsing JSON response: " + e.message + "\n\nRaw response:\n" + rawResponse;
                        response.className = "error";
                    }
                } catch (error) {
                    response.textContent = "Error sending request: " + error.message;
                    response.className = "error";
                }
            });
        });
    </script>
</body>
</html> 