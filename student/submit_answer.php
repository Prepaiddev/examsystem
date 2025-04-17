<?php
/**
 * API to save answers during an exam
 */
require_once '../config/config.php';

// Ensure that the user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

// GET request to get existing answers
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['attempt_id'])) {
    $attempt_id = intval($_GET['attempt_id']);
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT 1 FROM exam_attempts WHERE id = ? AND student_id = ?";
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
    
    // Get all answers for this attempt
    $answers = [];
    $answers_sql = "SELECT * FROM answers WHERE attempt_id = ?";
    
    if ($stmt = $conn->prepare($answers_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $answers[] = $row;
        }
        
        $stmt->close();
    }
    
    echo json_encode(['success' => true, 'answers' => $answers]);
    exit;
}

// POST request to save answers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT 1 FROM exam_attempts WHERE id = ? AND student_id = ?";
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
    
    // Process each question
    if (isset($_POST['question_ids']) && is_array($_POST['question_ids'])) {
        foreach ($_POST['question_ids'] as $question_id) {
            $answer_field = 'answer_' . $question_id;
            
            if (isset($_POST[$answer_field])) {
                // Check question type
                $question_type = '';
                $type_sql = "SELECT type FROM questions WHERE id = ?";
                
                if ($stmt = $conn->prepare($type_sql)) {
                    $stmt->bind_param("i", $question_id);
                    $stmt->execute();
                    $stmt->bind_result($question_type);
                    $stmt->fetch();
                    $stmt->close();
                }
                
                // Prepare variables based on question type
                $selected_choice_id = null;
                $text_answer = null;
                
                if ($question_type === 'multiple_choice') {
                    $selected_choice_id = intval($_POST[$answer_field]);
                } else {
                    $text_answer = $_POST[$answer_field];
                }
                
                // Check if an answer already exists
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
                
                // Insert or update the answer
                if ($answer_exists) {
                    // Update existing answer
                    if ($question_type === 'multiple_choice') {
                        $update_sql = "UPDATE answers 
                                      SET selected_choice_id = ?
                                      WHERE id = ?";
                        if ($stmt = $conn->prepare($update_sql)) {
                            $stmt->bind_param("ii", $selected_choice_id, $answer_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    } else {
                        $update_sql = "UPDATE answers 
                                      SET text_answer = ?
                                      WHERE id = ?";
                        if ($stmt = $conn->prepare($update_sql)) {
                            $stmt->bind_param("si", $text_answer, $answer_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                } else {
                    // Insert new answer
                    if ($question_type === 'multiple_choice') {
                        $insert_sql = "INSERT INTO answers 
                                      (attempt_id, question_id, selected_choice_id)
                                      VALUES (?, ?, ?)";
                        if ($stmt = $conn->prepare($insert_sql)) {
                            $stmt->bind_param("iii", $attempt_id, $question_id, $selected_choice_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    } else {
                        $insert_sql = "INSERT INTO answers 
                                      (attempt_id, question_id, text_answer)
                                      VALUES (?, ?, ?)";
                        if ($stmt = $conn->prepare($insert_sql)) {
                            $stmt->bind_param("iis", $attempt_id, $question_id, $text_answer);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
        }
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No question IDs provided']);
    }
    
    exit;
}

// If we get here, it's an invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request method']);