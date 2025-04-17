<?php
/**
 * API to submit an exam or section
 */
require_once '../config/config.php';

// Ensure that the user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
    $force_submit = isset($_POST['force_submit']) && $_POST['force_submit'] == 1;
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT ea.id, e.id as exam_id, e.has_sections
                  FROM exam_attempts ea
                  JOIN exams e ON ea.exam_id = e.id
                  WHERE ea.id = ? AND ea.student_id = ?";
    $verified = false;
    $exam_id = 0;
    $has_sections = false;
    
    if ($stmt = $conn->prepare($verify_sql)) {
        $stmt->bind_param("ii", $attempt_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $verified = true;
            $exam_id = $row['exam_id'];
            $has_sections = (bool)$row['has_sections'];
        }
        $stmt->close();
    }
    
    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'Invalid attempt ID']);
        exit;
    }
    
    // Handle sectioned exams
    if ($has_sections && isset($_POST['section_id']) && isset($_POST['complete_section'])) {
        $section_id = intval($_POST['section_id']);
        
        // Mark this section as completed
        $complete_section_sql = "UPDATE section_attempts
                               SET completed_at = NOW()
                               WHERE attempt_id = ? AND section_id = ?";
        
        if ($stmt = $conn->prepare($complete_section_sql)) {
            $stmt->bind_param("ii", $attempt_id, $section_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Check if there are more sections
        $next_section_sql = "SELECT s.id 
                            FROM sections s
                            LEFT JOIN section_attempts sa ON s.id = sa.section_id AND sa.attempt_id = ?
                            WHERE s.exam_id = ? AND sa.completed_at IS NULL
                            ORDER BY s.position
                            LIMIT 1";
        
        $has_next_section = false;
        
        if ($stmt = $conn->prepare($next_section_sql)) {
            $stmt->bind_param("ii", $attempt_id, $exam_id);
            $stmt->execute();
            $stmt->store_result();
            $has_next_section = ($stmt->num_rows > 0);
            $stmt->close();
        }
        
        if ($has_next_section) {
            // More sections to do
            echo json_encode(['success' => true, 'next_section' => true]);
            exit;
        } else {
            // No more sections, complete the exam
            $complete_exam_sql = "UPDATE exam_attempts SET completed_at = NOW() WHERE id = ?";
            
            if ($stmt = $conn->prepare($complete_exam_sql)) {
                $stmt->bind_param("i", $attempt_id);
                $stmt->execute();
                $stmt->close();
            }
            
            // Grade the exam
            gradeExamAttempt($attempt_id, $conn);
            
            echo json_encode(['success' => true, 'next_section' => false]);
            exit;
        }
    } else {
        // Non-sectioned exam or force submit - complete the entire exam
        $complete_exam_sql = "UPDATE exam_attempts SET completed_at = NOW() WHERE id = ?";
            
        if ($stmt = $conn->prepare($complete_exam_sql)) {
            $stmt->bind_param("i", $attempt_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // If it's a sectioned exam and we're force submitting, mark all sections as completed
        if ($has_sections && $force_submit) {
            $complete_sections_sql = "UPDATE section_attempts 
                                    SET completed_at = NOW() 
                                    WHERE attempt_id = ? AND completed_at IS NULL";
            
            if ($stmt = $conn->prepare($complete_sections_sql)) {
                $stmt->bind_param("i", $attempt_id);
                $stmt->execute();
                $stmt->close();
            }
        }
        
        // Grade the exam
        gradeExamAttempt($attempt_id, $conn);
        
        echo json_encode(['success' => true]);
        exit;
    }
}

// If we get here, it's an invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);

/**
 * Grade an exam attempt
 * 
 * @param int $attempt_id The attempt ID
 * @param mysqli $conn The database connection
 */
function gradeExamAttempt($attempt_id, $conn) {
    // Get all multiple choice answers
    $multiple_choice_sql = "SELECT a.id, a.question_id, a.selected_choice_id, q.points,
                            (SELECT is_correct FROM choices 
                             WHERE id = a.selected_choice_id) as is_correct
                           FROM answers a
                           JOIN questions q ON a.question_id = q.id
                           WHERE a.attempt_id = ? AND q.type = 'multiple_choice'";
    
    if ($stmt = $conn->prepare($multiple_choice_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Grade this answer
            $score = $row['is_correct'] ? $row['points'] : 0;
            $answer_id = $row['id'];
            
            // Update the answer
            $update_sql = "UPDATE answers 
                          SET score = ?, is_graded = 1
                          WHERE id = ?";
            
            if ($update_stmt = $conn->prepare($update_sql)) {
                $update_stmt->bind_param("di", $score, $answer_id);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        $stmt->close();
    }
    
    // Get exam info and calculate score
    $exam_sql = "SELECT e.*, ea.id as attempt_id
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
                WHERE ea.id = ?";
    
    if ($stmt = $conn->prepare($exam_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exam = $result->fetch_assoc();
        $stmt->close();
        
        if ($exam) {
            // Calculate score for multiple choice questions
            $score_sql = "SELECT 
                          IFNULL(SUM(a.score), 0) as earned_points,
                          IFNULL(SUM(q.points), 0) as total_points
                         FROM answers a
                         JOIN questions q ON a.question_id = q.id
                         WHERE a.attempt_id = ? AND a.is_graded = 1";
            
            if ($score_stmt = $conn->prepare($score_sql)) {
                $score_stmt->bind_param("i", $attempt_id);
                $score_stmt->execute();
                $score_stmt->bind_result($earned_points, $total_points);
                $score_stmt->fetch();
                $score_stmt->close();
                
                // Check if all questions are multiple choice (fully auto-graded)
                $check_sql = "SELECT COUNT(*) as count FROM questions q
                             JOIN answers a ON q.id = a.question_id
                             WHERE a.attempt_id = ? AND q.type != 'multiple_choice'";
                
                if ($check_stmt = $conn->prepare($check_sql)) {
                    $check_stmt->bind_param("i", $attempt_id);
                    $check_stmt->execute();
                    $check_stmt->bind_result($non_mc_count);
                    $check_stmt->fetch();
                    $check_stmt->close();
                    
                    // If we have any non-multiple choice questions, the exam isn't fully graded yet
                    $is_graded = ($non_mc_count == 0);
                    
                    // Calculate score as percentage
                    $score_percent = ($total_points > 0) ? ($earned_points / $total_points * 100) : 0;
                    
                    // Update attempt with score
                    $update_attempt_sql = "UPDATE exam_attempts
                                          SET score = ?, is_graded = ?
                                          WHERE id = ?";
                    
                    if ($update_stmt = $conn->prepare($update_attempt_sql)) {
                        $update_stmt->bind_param("dii", $score_percent, $is_graded, $attempt_id);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    
                    // If fully graded, calculate if passed
                    if ($is_graded) {
                        $passed = ($score_percent >= $exam['passing_score']);
                        
                        $update_pass_sql = "UPDATE exam_attempts
                                          SET passed = ?
                                          WHERE id = ?";
                        
                        if ($pass_stmt = $conn->prepare($update_pass_sql)) {
                            $pass_stmt->bind_param("ii", $passed, $attempt_id);
                            $pass_stmt->execute();
                            $pass_stmt->close();
                        }
                    }
                }
            }
        }
    }
}