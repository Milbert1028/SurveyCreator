<?php
// Prevent direct access
if (!defined('ALLOWED')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access to this file is not allowed.");
}

require_once 'functions.php';
require_once '../vendor/autoload.php'; // Make sure TCPDF is installed via composer

use TCPDF as TCPDF;

/**
 * Generate a PDF report of survey analytics
 * 
 * @param int $user_id The user ID for filtering surveys
 * @param int $survey_id Optional survey ID for specific survey analytics
 * @return string The path to the generated PDF file
 */
function generate_analytics_pdf($user_id, $survey_id = 0) {
    global $conn;
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SurveyCreator');
    $pdf->SetAuthor('SurveyCreator.online');
    $pdf->SetTitle('Survey Analytics Report');
    
    // Remove header and footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);
    
    // Set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', 'B', 20);
    
    // Get statistics
    if ($survey_id > 0) {
        // Specific survey analytics
        $survey_query = $conn->prepare("SELECT title FROM surveys WHERE id = ? AND user_id = ?");
        $survey_query->bind_param("ii", $survey_id, $user_id);
        $survey_query->execute();
        $survey_result = $survey_query->get_result();
        
        if ($survey_result->num_rows > 0) {
            $survey_data = $survey_result->fetch_assoc();
            $survey_title = $survey_data['title'];
            
            // Title
            $pdf->Cell(0, 10, 'Survey Analytics: ' . $survey_title, 0, 1, 'C');
            
            // Get survey responses
            $responses_query = $conn->prepare("
                SELECT COUNT(*) as total_responses, 
                AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) as avg_completion_time
                FROM survey_responses 
                WHERE survey_id = ? AND completed = 1
            ");
            $responses_query->bind_param("i", $survey_id);
            $responses_query->execute();
            $responses_result = $responses_query->get_result();
            $responses_data = $responses_result->fetch_assoc();
            
            $total_responses = $responses_data['total_responses'];
            $avg_completion_time = $responses_data['avg_completion_time'];
            
            // Format time
            $formatted_time = "N/A";
            if (!is_null($avg_completion_time)) {
                $minutes = floor($avg_completion_time / 60);
                $seconds = $avg_completion_time % 60;
                $formatted_time = $minutes . " min " . $seconds . " sec";
            }
            
            // Line break
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 12);
            
            // Response statistics
            $pdf->Cell(0, 10, 'Total Responses: ' . $total_responses, 0, 1);
            $pdf->Cell(0, 10, 'Average Completion Time: ' . $formatted_time, 0, 1);
            
            // Add more specific survey analytics here
        } else {
            // Survey not found
            $pdf->Cell(0, 10, 'Survey not found or access denied', 0, 1, 'C');
        }
    } else {
        // General user analytics
        // Title
        $pdf->Cell(0, 10, 'Survey Analytics Dashboard', 0, 1, 'C');
        
        // Get user statistics
        $stats_query = $conn->prepare("
            SELECT 
                (SELECT COUNT(*) FROM surveys WHERE user_id = ?) as total_surveys,
                (SELECT COUNT(*) FROM survey_responses sr JOIN surveys s ON sr.survey_id = s.id WHERE s.user_id = ?) as total_responses,
                (SELECT COUNT(*) FROM surveys WHERE user_id = ? AND active = 1) as active_surveys,
                (SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, completed_at)) FROM survey_responses sr 
                 JOIN surveys s ON sr.survey_id = s.id 
                 WHERE s.user_id = ? AND sr.completed = 1) as avg_completion_time
        ");
        $stats_query->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
        $stats_query->execute();
        $stats_result = $stats_query->get_result();
        
        if ($stats_result->num_rows > 0) {
            $stats = $stats_result->fetch_assoc();
            
            // Format time
            $formatted_time = "N/A";
            if (!is_null($stats['avg_completion_time'])) {
                $minutes = floor($stats['avg_completion_time'] / 60);
                $seconds = $stats['avg_completion_time'] % 60;
                $formatted_time = $minutes . " min " . $seconds . " sec";
            }
            
            // Line break
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', '', 12);
            
            // Display statistics
            $pdf->Cell(0, 10, 'Total Surveys: ' . $stats['total_surveys'], 0, 1);
            $pdf->Cell(0, 10, 'Total Responses: ' . $stats['total_responses'], 0, 1);
            $pdf->Cell(0, 10, 'Active Surveys: ' . $stats['active_surveys'], 0, 1);
            $pdf->Cell(0, 10, 'Average Completion Time: ' . $formatted_time, 0, 1);
            
            // Get survey performance data
            $surveys_query = $conn->prepare("
                SELECT s.id, s.title, 
                       COUNT(sr.id) as responses,
                       SUM(CASE WHEN sr.completed = 1 THEN 1 ELSE 0 END) as completions
                FROM surveys s
                LEFT JOIN survey_responses sr ON s.id = sr.survey_id
                WHERE s.user_id = ?
                GROUP BY s.id
                ORDER BY responses DESC
                LIMIT 10
            ");
            $surveys_query->bind_param("i", $user_id);
            $surveys_query->execute();
            $surveys_result = $surveys_query->get_result();
            
            if ($surveys_result->num_rows > 0) {
                // Line break
                $pdf->Ln(10);
                $pdf->SetFont('helvetica', 'B', 14);
                $pdf->Cell(0, 10, 'Survey Performance', 0, 1);
                
                // Table header
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->Cell(100, 10, 'Survey Title', 1, 0, 'L');
                $pdf->Cell(30, 10, 'Responses', 1, 0, 'C');
                $pdf->Cell(30, 10, 'Completions', 1, 0, 'C');
                $pdf->Cell(30, 10, 'Completion %', 1, 1, 'C');
                
                // Table rows
                $pdf->SetFont('helvetica', '', 12);
                while ($row = $surveys_result->fetch_assoc()) {
                    $completion_rate = $row['responses'] > 0 ? round(($row['completions'] / $row['responses']) * 100) : 0;
                    
                    $pdf->Cell(100, 10, substr($row['title'], 0, 40) . (strlen($row['title']) > 40 ? '...' : ''), 1, 0, 'L');
                    $pdf->Cell(30, 10, $row['responses'], 1, 0, 'C');
                    $pdf->Cell(30, 10, $row['completions'], 1, 0, 'C');
                    $pdf->Cell(30, 10, $completion_rate . '%', 1, 1, 'C');
                }
            }
        } else {
            // No data found
            $pdf->Cell(0, 10, 'No data available', 0, 1, 'C');
        }
    }
    
    // Generate and save the PDF
    $filename = 'survey_analytics_' . time() . '.pdf';
    $filepath = '../temp/' . $filename;
    $pdf->Output($filepath, 'F');
    
    return $filename;
} 