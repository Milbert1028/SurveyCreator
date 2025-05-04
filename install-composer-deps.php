<?php
/**
 * Composer Dependencies Installation Script
 * 
 * This script helps install Composer dependencies for Survey Creator
 * Run it from the command line with: php install-composer-deps.php
 */

// Display header
echo "=======================================================\n";
echo "Survey Creator - Composer Dependencies Installation\n";
echo "=======================================================\n\n";

// Check if Composer is installed
echo "Checking for Composer... ";
$composerPath = exec('which composer 2>/dev/null');
if (empty($composerPath)) {
    echo "NOT FOUND\n\n";
    echo "Composer is not installed or not in your PATH.\n";
    echo "Please install Composer first:\n";
    echo "Visit https://getcomposer.org/download/ for installation instructions.\n\n";
    exit(1);
}
echo "FOUND at $composerPath\n";

// Check for composer.json
echo "Checking for composer.json... ";
if (!file_exists('composer.json')) {
    echo "NOT FOUND\n\n";
    echo "composer.json file is missing. Cannot proceed with installation.\n";
    exit(1);
}
echo "FOUND\n";

// Read composer.json to display what will be installed
echo "Reading composer.json... ";
$composerJson = file_get_contents('composer.json');
$composerData = json_decode($composerJson, true);
if (!$composerData) {
    echo "ERROR\n\n";
    echo "Failed to parse composer.json. The file may be corrupted.\n";
    exit(1);
}
echo "OK\n\n";

echo "The following dependencies will be installed:\n";
foreach ($composerData['require'] as $package => $version) {
    echo "- $package: $version\n";
}
echo "\n";

// Install dependencies
echo "Installing dependencies using Composer...\n";
echo "This may take a few minutes...\n\n";

// Execute composer install
passthru('composer install --no-dev', $exitCode);

// Check result
if ($exitCode !== 0) {
    echo "\n\nERROR: Composer installation failed with exit code $exitCode\n";
    echo "Please try running 'composer install' manually.\n";
    exit(1);
}

echo "\n=======================================================\n";
echo "Dependencies installed successfully!\n";

// Check for TCPDF specifically
echo "Checking for TCPDF library... ";
if (file_exists('vendor/tecnickcom/tcpdf/tcpdf.php')) {
    echo "FOUND\n";
} else {
    echo "NOT FOUND - PDF functionality may not work correctly.\n";
    echo "Try running 'composer require tecnickcom/tcpdf:^6.6' manually.\n";
}

echo "\nYou can now use the Survey Creator application.\n";
echo "=======================================================\n"; 