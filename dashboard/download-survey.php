<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Check for vendor autoload file
$autoloadPath = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    error_log("Vendor autoload.php not found at: $autoloadPath");
    flash_message("PDF generation dependencies are missing. Please contact administrator.", "danger");
    redirect('/dashboard');
}

require_once $autoloadPath;

// Check if TCPDF class exists
if (!class_exists('\TCPDF')) {
    error_log("TCPDF class not found. Vendor autoload may not be including it.");
    flash_message("PDF generation library not available. Please contact administrator.", "danger");
    redirect('/dashboard');
}

// Ensure user is logged in
if (!is_logged_in()) {
    flash_message("Please login to download surveys", "warning");
    redirect('/auth/login.php');
}

$survey_id = $_GET['id'] ?? null;
if (!$survey_id) {
    flash_message("Survey ID is required", "danger");
    redirect('/dashboard');
}

// Get survey details
$db = Database::getInstance();
$user_id = $_SESSION['user_id'];

$survey = $db->query("
    SELECT s.*, 
           (SELECT COUNT(*) FROM responses WHERE survey_id = s.id) as response_count
    FROM surveys s 
    WHERE s.id = $survey_id AND s.user_id = $user_id
");

if (!$survey || $survey->num_rows === 0) {
    flash_message("Survey not found", "danger");
    redirect('/dashboard');
}

$survey_data = $survey->fetch_assoc();

// Get questions
$questions = $db->query("
    SELECT * FROM questions 
    WHERE survey_id = $survey_id 
    ORDER BY order_position
");

if (!$questions || $questions->num_rows === 0) {
    flash_message("No questions found for this survey", "warning");
    redirect('/dashboard');
}

try {
    // Create new PDF document
    $pdf = new \TCPDF();

    // Set document information
    $pdf->SetCreator('Survey Creator');
    $pdf->SetAuthor('Survey Creator');
    $pdf->SetTitle($survey_data['title']);
    $pdf->SetSubject('Survey Document');
    $pdf->SetKeywords('Survey, PDF, Download');

    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont('courier');

    // Set margins
    $pdf->SetMargins(15, 15, 15);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 15);

    // Add a page
    $pdf->AddPage();

    // Set font for title
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Cell(0, 10, $survey_data['title'], 0, 1, 'C');
    $pdf->Ln(5);

    // Add description
    if (!empty($survey_data['description'])) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->MultiCell(0, 10, $survey_data['description'], 0, 'L');
        $pdf->Ln(5);
    }

    // Add questions
    $question_number = 1;
    while ($question = $questions->fetch_assoc()) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->MultiCell(0, 10, $question_number . '. ' . $question['question_text'] . 
                       ($question['required'] ? ' *' : ''), 0, 'L');
        
        $pdf->SetFont('helvetica', '', 11);
        
        switch ($question['question_type']) {
            case 'multiple_choice':
            case 'single_choice':
                $options = json_decode($question['options'], true);
                if (is_array($options)) {
                    foreach ($options as $option) {
                        $symbol = $question['question_type'] === 'multiple_choice' ? '□' : '○';
                        $pdf->MultiCell(0, 10, "    $symbol  $option", 0, 'L');
                    }
                }
                break;
                
            case 'text':
                $pdf->MultiCell(0, 10, '_____________________________________', 0, 'L');
                $pdf->MultiCell(0, 10, '_____________________________________', 0, 'L');
                break;
                
            case 'rating':
                $pdf->Cell(0, 10, '1 ○  2 ○  3 ○  4 ○  5 ○', 0, 1, 'L');
                break;
        }
        
        $pdf->Ln(5);
        $question_number++;
    }

    // Add footer information
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->Cell(0, 10, 'Created: ' . date('F j, Y', strtotime($survey_data['created_at'])), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Status: ' . ucfirst($survey_data['status']), 0, 1, 'L');
    $pdf->Cell(0, 10, 'Total Responses: ' . $survey_data['response_count'], 0, 1, 'L');
    $pdf->Cell(0, 10, 'Generated on: ' . date('F j, Y \a\t g:i a'), 0, 1, 'L');

    // Clean filename
    $filename = preg_replace('/[^a-zA-Z0-9]+/', '_', $survey_data['title']);
    $filename = 'survey_' . strtolower($filename) . '_' . date('Ymd') . '.pdf';

    // Output PDF
    $pdf->Output($filename, 'D');
    exit;
    
} catch (Exception $e) {
    error_log('PDF Generation Error: ' . $e->getMessage());
    flash_message("Failed to generate PDF: " . $e->getMessage(), "danger");
    redirect('/dashboard');
}
