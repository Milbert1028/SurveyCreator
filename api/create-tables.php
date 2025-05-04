<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if user is logged in as admin
if (!is_logged_in() || !is_admin()) {
    die("You must be logged in as an administrator to run this script.");
}

// Get database connection
$db = Database::getInstance();

// Create responses table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    respondent_ip VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (survey_id),
    INDEX (user_id)
)");

echo "<p>Responses table created or verified.</p>";

// Create answers table if it doesn't exist
$db->query("CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    response_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT DEFAULT NULL,
    text_answer TEXT DEFAULT NULL,
    rating_value INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (response_id),
    INDEX (question_id)
)");

echo "<p>Answers table created or verified.</p>";

// Check for existing surveys
$surveys = $db->query("SELECT id, title FROM surveys ORDER BY id");
echo "<h2>Available Surveys:</h2>";
echo "<ul>";
if ($surveys && $surveys->num_rows > 0) {
    while ($survey = $surveys->fetch_assoc()) {
        echo "<li>ID: {$survey['id']} - {$survey['title']}</li>";
    }
} else {
    echo "<li>No surveys found.</li>";
}
echo "</ul>";

// Check for existing questions
$questions = $db->query("SELECT id, survey_id, question_text FROM questions ORDER BY survey_id, id");
echo "<h2>Available Questions:</h2>";
echo "<ul>";
if ($questions && $questions->num_rows > 0) {
    while ($question = $questions->fetch_assoc()) {
        echo "<li>ID: {$question['id']} - Survey: {$question['survey_id']} - {$question['question_text']}</li>";
    }
} else {
    echo "<li>No questions found.</li>";
}
echo "</ul>";

echo "<h2>Database Tables Structure:</h2>";

// Function to display table structure
function display_table_structure($db, $table) {
    $structure = $db->query("DESCRIBE $table");
    
    if ($structure && $structure->num_rows > 0) {
        echo "<h3>Table: $table</h3>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($column = $structure->fetch_assoc()) {
            echo "<tr>";
            foreach ($column as $key => $value) {
                echo "<td>" . htmlspecialchars($value ?? "NULL") . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>Could not retrieve structure for table $table.</p>";
    }
}

// Display structure of key tables
foreach (['surveys', 'questions', 'responses', 'answers'] as $table) {
    display_table_structure($db, $table);
}

echo "<p>Setup complete!</p>";
echo "<p><a href='../dashboard/'>Return to Dashboard</a></p>"; 