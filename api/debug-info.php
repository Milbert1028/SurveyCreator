<?php
// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>PHP Debug Info</h1>";

// Check for session
echo "<h2>Session</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";

// Check for includes
echo "<h2>Includes</h2>";
echo "Including config.php... ";
if (file_exists('../includes/config.php')) {
    echo "File exists, attempting to include.<br>";
    include_once '../includes/config.php';
    echo "Included successfully.<br>";
    
    // Check for constants
    echo "<h3>Constants Defined</h3>";
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'Not defined') . "<br>";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'Not defined') . "<br>";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'Not defined') . "<br>";
    echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'Not defined') . "<br>";
} else {
    echo "File does not exist.<br>";
}

echo "<br>Including functions.php... ";
if (file_exists('../includes/functions.php')) {
    echo "File exists, attempting to include.<br>";
    include_once '../includes/functions.php';
    echo "Included successfully.<br>";
    
    // Check for Database class
    echo "<h3>Classes Defined</h3>";
    echo "Database class: " . (class_exists('Database') ? 'Exists' : 'Not found') . "<br>";
    
    // Try to get a database connection
    echo "<h3>Database Connection Test</h3>";
    try {
        $db = Database::getInstance();
        echo "Database connection successful!<br>";
        
        // Test a simple query
        $result = $db->query("SELECT 1 as test");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "Query test: " . ($row['test'] == 1 ? 'Success' : 'Failed') . "<br>";
        } else {
            echo "Query test failed<br>";
        }
    } catch (Exception $e) {
        echo "Database connection failed: " . $e->getMessage() . "<br>";
    }
} else {
    echo "File does not exist.<br>";
}

// Look for Database.php
echo "<br>Looking for Database.php... ";
if (file_exists('../includes/Database.php')) {
    echo "File exists.<br>";
} else {
    echo "File does not exist.<br>";
}

// Check directory content
echo "<h2>Directory Content</h2>";
echo "<h3>includes directory</h3>";
if (is_dir('../includes')) {
    $files = scandir('../includes');
    echo "<pre>";
    print_r($files);
    echo "</pre>";
} else {
    echo "includes directory does not exist.<br>";
}

// PHP Info
echo "<h2>PHP Info</h2>";
phpinfo(); 