<?php
// Initialize session and include required files
session_start();
define('ALLOWED', true);
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/export_pdf.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Create temp directory if it doesn't exist
$temp_dir = "../temp";
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Check if survey ID is provided
$survey_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Generate the PDF
try {
    $filename = generate_analytics_pdf($user_id, $survey_id);
    
    // Set headers for download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($temp_dir . '/' . $filename));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output the file
    readfile($temp_dir . '/' . $filename);
    
    // Delete the file after sending
    unlink($temp_dir . '/' . $filename);
    
    exit();
} catch (Exception $e) {
    // Redirect back with error
    header("Location: analytics.php" . ($survey_id > 0 ? "?id=" . $survey_id : "") . "&error=export_failed");
    exit();
} 