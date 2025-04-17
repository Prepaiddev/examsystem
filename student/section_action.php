<?php
/**
 * API to handle section actions during an exam
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
    $section_id = isset($_POST['section_id']) ? intval($_POST['section_id']) : 0;
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
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
    
    // Handle different actions
    switch ($action) {
        case 'start':
            // Start a section (set started_at time)
            $start_sql = "UPDATE section_attempts 
                        SET started_at = NOW() 
                        WHERE attempt_id = ? AND section_id = ? AND started_at IS NULL";
            
            if ($stmt = $conn->prepare($start_sql)) {
                $stmt->bind_param("ii", $attempt_id, $section_id);
                if ($stmt->execute()) {
                    // If no rows were affected, it might already be started
                    if ($stmt->affected_rows === 0) {
                        // Check if it exists but is already started
                        $check_sql = "SELECT 1 FROM section_attempts 
                                     WHERE attempt_id = ? AND section_id = ? AND started_at IS NOT NULL";
                        
                        if ($check_stmt = $conn->prepare($check_sql)) {
                            $check_stmt->bind_param("ii", $attempt_id, $section_id);
                            $check_stmt->execute();
                            $check_stmt->store_result();
                            $already_started = ($check_stmt->num_rows > 0);
                            $check_stmt->close();
                            
                            if ($already_started) {
                                echo json_encode(['success' => true, 'message' => 'Section was already started']);
                                exit;
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Failed to start section']);
                                exit;
                            }
                        }
                    } else {
                        echo json_encode(['success' => true]);
                        exit;
                    }
                }
                $stmt->close();
            }
            break;
            
        case 'update_time':
            // Update remaining time for a section
            $remaining_seconds = isset($_POST['remaining_seconds']) ? intval($_POST['remaining_seconds']) : 0;
            
            $update_time_sql = "UPDATE section_attempts 
                              SET time_remaining_seconds = ? 
                              WHERE attempt_id = ? AND section_id = ?";
            
            if ($stmt = $conn->prepare($update_time_sql)) {
                $stmt->bind_param("iii", $remaining_seconds, $attempt_id, $section_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                $stmt->close();
            }
            break;
            
        case 'complete':
            // Complete a section
            $complete_sql = "UPDATE section_attempts 
                           SET completed_at = NOW() 
                           WHERE attempt_id = ? AND section_id = ?";
            
            if ($stmt = $conn->prepare($complete_sql)) {
                $stmt->bind_param("ii", $attempt_id, $section_id);
                if ($stmt->execute()) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                $stmt->close();
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// If we get here, it's an invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request or database error']);