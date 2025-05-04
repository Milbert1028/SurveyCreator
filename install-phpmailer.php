<?php
/**
 * This script downloads and installs PHPMailer from GitHub
 */

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<h1>PHPMailer Installation Script</h1>';

// Create lib directory if it doesn't exist
$lib_dir = __DIR__ . '/lib';
if (!is_dir($lib_dir)) {
    if (mkdir($lib_dir, 0755, true)) {
        echo "<p>Created directory: $lib_dir</p>";
    } else {
        die("<p>Error: Failed to create directory: $lib_dir</p>");
    }
}

// Create PHPMailer directory
$phpmailer_dir = $lib_dir . '/PHPMailer';
if (!is_dir($phpmailer_dir)) {
    if (mkdir($phpmailer_dir, 0755, true)) {
        echo "<p>Created directory: $phpmailer_dir</p>";
    } else {
        die("<p>Error: Failed to create directory: $phpmailer_dir</p>");
    }
}

// Create src directory
$src_dir = $phpmailer_dir . '/src';
if (!is_dir($src_dir)) {
    if (mkdir($src_dir, 0755, true)) {
        echo "<p>Created directory: $src_dir</p>";
    } else {
        die("<p>Error: Failed to create directory: $src_dir</p>");
    }
}

// Files to download
$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.8.0/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.8.0/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/v6.8.0/src/Exception.php'
];

// Download each file
foreach ($files as $filename => $url) {
    $file_path = $src_dir . '/' . $filename;
    
    echo "<p>Downloading $filename...</p>";
    
    // Try to get file contents
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
        ]
    ]);
    
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        echo "<p style='color: red;'>Error: Failed to download $url</p>";
        continue;
    }
    
    // Save file
    if (file_put_contents($file_path, $content) !== false) {
        echo "<p style='color: green;'>Successfully saved $filename</p>";
    } else {
        echo "<p style='color: red;'>Error: Failed to save $filename</p>";
    }
}

// Check if installation was successful
$all_files_exist = true;
foreach ($files as $filename => $url) {
    $file_path = $src_dir . '/' . $filename;
    if (!file_exists($file_path)) {
        $all_files_exist = false;
        echo "<p style='color: red;'>Missing file: $filename</p>";
    }
}

if ($all_files_exist) {
    echo "<h2 style='color: green;'>PHPMailer installed successfully!</h2>";
    echo "<p>You can now use the PHPMailerWrapper class to send emails.</p>";
    echo "<p><a href='test-mail.php'>Click here to test sending an email</a></p>";
} else {
    echo "<h2 style='color: red;'>PHPMailer installation incomplete</h2>";
    echo "<p>Some files are missing. Please try again or install manually.</p>";
}
?>
