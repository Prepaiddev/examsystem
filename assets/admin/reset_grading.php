<?php
/**
 * Admin Reset Exam Grading
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

// Verify attempt exists
$verify_sql = "SELECT id FROM exam_attempts WHERE id = ?";
$attempt_exists = false;

if ($stmt = $conn->prepare($verify_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $stmt->store_result();
    $attempt_exists = ($stmt->num_rows > 0);
    $stmt->close();
}

if (!$attempt_exists) {
    setFlashMessage('error', 'Exam attempt not found.');
    redirect(SITE_URL . '/admin/results.php');
}

// Reset grading for short answer and essay questions
$reset_sql = "UPDATE answers a
              JOIN questions q ON a.question_id = q.id
              SET a.is_graded = 0, a.score = NULL, a.grader_feedback = NULL
              WHERE a.attempt_id = ? AND (q.type = 'short_answer' OR q.type = 'essay')";

if ($stmt = $conn->prepare($reset_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $success = $stmt->execute();
    $affected_rows = $stmt->affected_rows;
    $stmt->close();
    
    if ($success) {
        // Auto-grade multiple-choice questions
        // For multiple choice questions, we can auto-grade by checking if selected choice is correct
        $auto_grade_sql = "UPDATE answers a
                          JOIN questions q ON a.question_id = q.id
                          LEFT JOIN choices c ON a.selected_choice_id = c.id
                          SET a.is_graded = 1,
                              a.score = CASE
                                  WHEN a.selected_choice_id IS NULL THEN 0
                                  WHEN c.is_correct = 1 THEN q.points
                                  ELSE 0
                              END
                          WHERE a.attempt_id = ? AND q.type = 'multiple_choice'";
        
        if ($stmt = $conn->prepare($auto_grade_sql)) {
            $stmt->bind_param("i", $attempt_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Update overall exam score
        updateAttemptScore($attempt_id, $conn);
        
        setFlashMessage('success', 'Grading has been reset. Multiple choice questions have been auto-graded.');
    } else {
        setFlashMessage('error', 'Failed to reset grading. Please try again.');
    }
} else {
    setFlashMessage('error', 'Database error: ' . $conn->error);
}

// Redirect back to results page
redirect(SITE_URL . "/admin/view_results.php?attempt_id=$attempt_id");

// Function to update the attempt's overall score
function updateAttemptScore($attempt_id, $conn) {
    // Get all answers for this attempt
    $answers_sql = "SELECT a.score, q.points 
                    FROM answers a
                    JOIN questions q ON a.question_id = q.id
                    WHERE a.attempt_id = ?";
    
    $total_points = 0;
    $earned_points = 0;
    
    if ($stmt = $conn->prepare($answers_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $total_points += $row['points'];
            if ($row['score'] !== null) {
                $earned_points += $row['score'];
            }
        }
        
        $stmt->close();
    }
    
    // Calculate percentage score
    $score_percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
    
    // Get passing score for this exam
    $passing_score = 60.0; // Default
    $exam_sql = "SELECT e.passing_score 
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
                WHERE ea.id = ?";
                
    if ($stmt = $conn->prepare($exam_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $stmt->bind_result($passing_score);
        $stmt->fetch();
        $stmt->close();
    }
    
    // Determine if passed
    $passed = ($score_percentage >= $passing_score);
    
    // Update the attempt with the new score
    $update_sql = "UPDATE exam_attempts 
                  SET score = ?, is_graded = 1, passed = ?
                  WHERE id = ?";
                  
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("dii", $score_percentage, $passed, $attempt_id);
        $stmt->execute();
        $stmt->close();
    }
}