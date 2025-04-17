<?php
/**
 * Export Exam Results
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Check if attempt ID is provided
if (!isset($_GET['attempt_id']) || !is_numeric($_GET['attempt_id'])) {
    setFlashMessage('error', 'Invalid attempt ID.');
    redirect(SITE_URL . '/admin/results.php');
}

$attempt_id = intval($_GET['attempt_id']);

// Get exam attempt details
$attempt = [];
$attempt_sql = "SELECT ea.*, e.title as exam_title, e.assessment_type,
                c.title as course_title, c.code as course_code,
                u.username as student_name, u.matric_number, u.email as student_email
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
                LEFT JOIN courses c ON e.course_id = c.id
                JOIN users u ON ea.student_id = u.id
                WHERE ea.id = ?";

if ($stmt = $conn->prepare($attempt_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt = fetchRowSafely($result);
    $stmt->close();
}

// If attempt not found, redirect
if (empty($attempt)) {
    setFlashMessage('error', 'Exam attempt not found.');
    redirect(SITE_URL . '/admin/results.php');
}

// Get all answers for this attempt with question details
$answers = [];
$answers_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.attempt_id = ?
                ORDER BY q.position";

if ($stmt = $conn->prepare($answers_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    $stmt->close();
}

// Check the requested format
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';

// Generate filename
$filename = sanitizeFilename($attempt['student_name'] . '_' . $attempt['exam_title']);
$date_str = date('Y-m-d');

// Handle the export format
if ($format === 'pdf') {
    exportPDF($attempt, $answers, "{$filename}_{$date_str}.pdf");
} else {
    // Default to CSV
    exportCSV($attempt, $answers, "{$filename}_{$date_str}.csv");
}

/**
 * Export results as CSV
 */
function exportCSV($attempt, $answers, $filename) {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Create a file handle for PHP output
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM to fix Excel encoding issues
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write exam information
    fputcsv($output, ['Exam Results Summary']);
    fputcsv($output, ['']);
    fputcsv($output, ['Student', $attempt['student_name']]);
    fputcsv($output, ['Matric Number', $attempt['matric_number']]);
    fputcsv($output, ['Email', $attempt['student_email']]);
    fputcsv($output, ['Assessment Type', ucfirst($attempt['assessment_type'])]);
    fputcsv($output, ['Title', $attempt['exam_title']]);
    
    if (!empty($attempt['course_title'])) {
        fputcsv($output, ['Course', $attempt['course_code'] . ' - ' . $attempt['course_title']]);
    }
    
    fputcsv($output, ['Date Taken', formatDateTime($attempt['started_at'])]);
    fputcsv($output, ['Date Completed', !empty($attempt['completed_at']) ? formatDateTime($attempt['completed_at']) : 'Not completed']);
    fputcsv($output, ['Score', number_format($attempt['score'], 2) . '%']);
    fputcsv($output, ['Status', $attempt['passed'] ? 'PASSED' : 'FAILED']);
    fputcsv($output, ['Grade', calculateGrade($attempt['score'])]);
    fputcsv($output, ['']);
    
    // Write header row for answers
    fputcsv($output, ['Question #', 'Question Type', 'Question Text', 'Student Answer', 'Points Possible', 'Score', 'Status', 'Feedback']);
    
    // Write answer rows
    foreach ($answers as $index => $answer) {
        $question_num = $index + 1;
        
        // Format the answer text
        $answer_text = '';
        if ($answer['question_type'] === 'multiple_choice') {
            // Get selected choice text
            if (!empty($answer['selected_choice_id'])) {
                $choice_sql = "SELECT text FROM choices WHERE id = ?";
                if ($stmt = $GLOBALS['conn']->prepare($choice_sql)) {
                    $stmt->bind_param("i", $answer['selected_choice_id']);
                    $stmt->execute();
                    $stmt->bind_result($text);
                    if ($stmt->fetch()) {
                        $answer_text = $text;
                    }
                    $stmt->close();
                }
            } else {
                $answer_text = 'Not answered';
            }
        } else {
            $answer_text = !empty($answer['text_answer']) ? $answer['text_answer'] : 'Not answered';
        }
        
        // Determine status
        $status = 'Not graded';
        if ($answer['is_graded']) {
            if ($answer['score'] >= $answer['question_points']) {
                $status = 'Correct';
            } elseif ($answer['score'] > 0) {
                $status = 'Partial';
            } else {
                $status = 'Incorrect';
            }
        }
        
        // Write the row
        fputcsv($output, [
            $question_num,
            ucfirst(str_replace('_', ' ', $answer['question_type'])),
            $answer['question_text'],
            $answer_text,
            $answer['question_points'],
            $answer['is_graded'] ? number_format($answer['score'], 2) : 'N/A',
            $status,
            $answer['grader_feedback'] ?? ''
        ]);
    }
    
    // Close the file handle
    fclose($output);
    exit();
}

/**
 * Export results as PDF
 */
function exportPDF($attempt, $answers, $filename) {
    // Since we don't have a PDF generation library installed,
    // redirect back with an error message
    setFlashMessage('error', 'PDF export is not available. Please use CSV format instead.');
    redirect(SITE_URL . "/admin/view_results.php?attempt_id={$attempt['id']}");
    
    /* 
     * PDF export would typically use a library like FPDF, TCPDF, or mPDF
     * Implementation would be similar to this:
     
    require_once('library/tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('Online Examination System');
    $pdf->SetAuthor('Administrator');
    $pdf->SetTitle('Exam Results');
    $pdf->SetSubject('Exam Results for ' . $attempt['student_name']);
    
    // Set default header/footer data
    $pdf->SetHeaderData('', 0, 'Exam Results', 'Generated on ' . date('Y-m-d H:i:s'));
    
    // Set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    
    // Set margins
    $pdf->SetMargins(15, 27, 15);
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(10);
    
    // Set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, 25);
    
    // Add a page
    $pdf->AddPage();
    
    // Write the content
    // ... format the data and write to PDF ...
    
    // Close and output PDF document
    $pdf->Output($filename, 'D');
    exit();
    */
}

/**
 * Sanitize filename to make it safe for various filesystems
 */
function sanitizeFilename($filename) {
    // Replace spaces and unwanted characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Trim underscores from beginning and end
    $filename = trim($filename, '_');
    // If filename is empty, use a default name
    if (empty($filename)) {
        $filename = 'exam_results';
    }
    return $filename;
}