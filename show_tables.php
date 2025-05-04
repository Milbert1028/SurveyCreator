<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$db = Database::getInstance();
$result = $db->query("SHOW TABLES");

echo "<h1>Database Tables</h1>";
echo "<ul>";
while ($row = $result->fetch_row()) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

// For the 'questions' table, show its structure
$result = $db->query("DESCRIBE questions");
if ($result) {
    echo "<h2>Questions Table Structure</h2>";
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        foreach ($row as $key => $value) {
            echo "<td>" . ($value ?? "NULL") . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
}

// Check if question_options table exists
$result = $db->query("SHOW TABLES LIKE 'question_options'");
$optionsTableExists = ($result && $result->num_rows > 0);

if ($optionsTableExists) {
    $result = $db->query("DESCRIBE question_options");
    if ($result) {
        echo "<h2>Question Options Table Structure</h2>";
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $key => $value) {
                echo "<td>" . ($value ?? "NULL") . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<h2>Question Options Table does not exist yet.</h2>";
}
?>
