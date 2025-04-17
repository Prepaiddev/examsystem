<?php
/**
 * API to mark an answer for review
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
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $mark_for_review = isset($_POST['mark_for_review']) ? (bool)$_POST['mark_for_review'] : false;
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT 1 FROM exam_attempts 
                  WHERE id = ? AND student_id = ?";
    $verified = false;
    
    if ($stmt = $conn->prepare($verify_sql)) {
        $stmt->bind_param("ii", $attempt_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $verified = ($stmt->num_rows > 0);
        $stmt->close();
    }
    
    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'Invalid attempt ID']);
        exit;
    }
    
    // Check if an answer already exists for this question
    $check_sql = "SELECT id FROM answers 
                 WHERE attempt_id = ? AND question_id = ?";
    $answer_exists = false;
    $answer_id = 0;
    
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("ii", $attempt_id, $question_id);
        $stmt->execute();
        $stmt->store_result();
        $answer_exists = ($stmt->num_rows > 0);
        
        if ($answer_exists) {
            $stmt->bind_result($answer_id);
            $stmt->fetch();
        }
        
        $stmt->close();
    }
    
    if ($answer_exists) {
        // Update existing answer
        $update_sql = "UPDATE answers 
                      SET marked_for_review = ?
                      WHERE id = ?";
        
        if ($stmt = $conn->prepare($update_sql)) {
            $mark = $mark_for_review ? 1 : 0;
            $stmt->bind_param("ii", $mark, $answer_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update review status']);
            }
            
            $stmt->close();
            exit;
        }
    } else {
        // Create a placeholder answer
        $insert_sql = "INSERT INTO answers 
                      (attempt_id, question_id, marked_for_review)
                      VALUES (?, ?, ?)";
        
        if ($stmt = $conn->prepare($insert_sql)) {
            $mark = $mark_for_review ? 1 : 0;
            $stmt->bind_param("iii", $attempt_id, $question_id, $mark);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create review status']);
            }
            
            $stmt->close();
            exit;
        }
    }
}

// If we get here, it's an invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);