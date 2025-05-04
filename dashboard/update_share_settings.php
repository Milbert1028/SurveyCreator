<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to update settings", "warning");
    redirect('/auth/login.php');
}

// Validate CSRF token
if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    flash_message("Invalid request", "danger");
    redirect('/dashboard');
}

// Get survey ID and user ID
$survey_id = $_POST['survey_id'] ?? null; // Change from $_GET to $_POST
$user_id = $_SESSION['user_id'];

if (!$survey_id) {
    flash_message("Survey ID is required", "danger");
    redirect('/dashboard');
}

// Sanitize and validate input
$show_progress_bar = isset($_POST['show_progress_bar']) ? 1 : 0;
$allow_multiple_responses = isset($_POST['allow_multiple_responses']) ? 1 : 0;
$require_login = isset($_POST['require_login']) ? 1 : 0;
$response_limit = is_numeric($_POST['response_limit']) ? (int)$_POST['response_limit'] : null;
$close_date = !empty($_POST['close_date']) ? $_POST['close_date'] : null;

// Update database
$db = Database::getInstance();
$conn = $db->getConnection(); // Get the connection object
$stmt = $conn->prepare("
    UPDATE surveys
    SET show_progress_bar = ?, 
        allow_multiple_responses = ?, 
        require_login = ?, 
        response_limit = ?, 
        close_date = ?
    WHERE id = ? AND user_id = ?
");

if ($stmt === false) {
    // Log the error message
    error_log("Failed to prepare the statement: " . $conn->error);
    flash_message("Failed to prepare the statement", "danger");
    redirect("/dashboard/share-survey.php?id=" . $survey_id);
}

$stmt->bind_param("iiiissi", $show_progress_bar, $allow_multiple_responses, $require_login, $response_limit, $close_date, $survey_id, $user_id);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    flash_message("Settings updated successfully", "success");
} else {
    flash_message("No changes were made or an error occurred", "danger");
}

redirect("/dashboard/share-survey.php?id=$survey_id");
?>
