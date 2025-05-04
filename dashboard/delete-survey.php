<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to access this page", "warning");
    redirect(SITE_URL . '/auth/login.php');
}

$db = Database::getInstance();
$user_id = $_SESSION['user_id'];
$survey_id = $_GET['id'] ?? null;

if ($survey_id) {
    // Verify the survey belongs to the logged-in user
    $survey = $db->query("SELECT id FROM surveys WHERE id = $survey_id AND user_id = $user_id");
    if ($survey->num_rows > 0) {
        // Start transaction to ensure data integrity
        $db->beginTransaction();
        
        try {
            // Delete questions, options, and related data
            $questions = $db->query("SELECT id FROM questions WHERE survey_id = $survey_id");
            if ($questions && $questions->num_rows > 0) {
                while ($question = $questions->fetch_assoc()) {
                    $question_id = $question['id'];
                    // Delete options
                    $db->query("DELETE FROM options WHERE question_id = $question_id");
                }
            }
            
            // Delete questions
            $db->query("DELETE FROM questions WHERE survey_id = $survey_id");
            
            // Delete responses
            $db->query("DELETE FROM responses WHERE survey_id = $survey_id");
            
            // Delete the survey
            $db->query("DELETE FROM surveys WHERE id = $survey_id");
            
            // Commit the transaction
            $db->commit();
            
            flash_message("Survey deleted successfully", "success");
        } catch (Exception $e) {
            // Rollback in case of error
            $db->rollback();
            flash_message("Error deleting survey: " . $e->getMessage(), "danger");
        }
    } else {
        flash_message("Survey not found or you do not have permission to delete it", "danger");
    }
} else {
    flash_message("Invalid survey ID", "danger");
}

redirect(SITE_URL . '/dashboard/index.php');
?>