<?php
/**
 * Submit Written Answer with Image Upload
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
    $attempt_id = isset($_POST['attempt_id']) ? intval($_POST['attempt_id']) : 0;
    $question_id = isset($_POST['question_id']) ? intval($_POST['question_id']) : 0;
    $text_answer = isset($_POST['text_answer']) ? trim($_POST['text_answer']) : '';
    
    // Validate input
    if ($attempt_id <= 0 || $question_id <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid attempt or question ID']);
        exit;
    }
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT ea.id FROM exam_attempts ea WHERE ea.id = ? AND ea.student_id = ?";
    $verified = false;
    
    if ($stmt = $conn->prepare($verify_sql)) {
        $stmt->bind_param("ii", $attempt_id, $user_id);
        $stmt->execute();
        $stmt->store_result();
        $verified = ($stmt->num_rows > 0);
        $stmt->close();
    }
    
    if (!$verified) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized access to this attempt']);
        exit;
    }
    
    // Check if there's already an answer for this question
    $existing_answer_id = null;
    $check_sql = "SELECT id FROM answers WHERE attempt_id = ? AND question_id = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("ii", $attempt_id, $question_id);
        $stmt->execute();
        $stmt->bind_result($existing_id);
        if ($stmt->fetch()) {
            $existing_answer_id = $existing_id;
        }
        $stmt->close();
    }
    
    // Process file upload if present
    $image_path = null;
    if (isset($_FILES['answer_image']) && $_FILES['answer_image']['error'] === UPLOAD_ERR_OK) {
        // Define allowed file types and max size
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $file = $_FILES['answer_image'];
        
        // Validate file type
        if (!in_array($file['type'], $allowed_types)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPEG, PNG, and GIF are allowed.']);
            exit;
        }
        
        // Validate file size
        if ($file['size'] > $max_size) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
            exit;
        }
        
        // Create upload directory if it doesn't exist
        $upload_dir = '../uploads/answers/' . $attempt_id;
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $filename = 'q' . $question_id . '_' . uniqid() . '_' . basename($file['name']);
        $image_path = 'uploads/answers/' . $attempt_id . '/' . $filename;
        $full_path = '../' . $image_path;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to upload file. Please try again.']);
            exit;
        }
        
        // If we have text and image, combine them
        if (!empty($text_answer)) {
            $text_answer .= "\n\n[Image: " . basename($filename) . "]";
        } else {
            $text_answer = "[Image: " . basename($filename) . "]";
        }
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        if ($existing_answer_id) {
            // Update existing answer
            $update_sql = "UPDATE answers SET text_answer = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $text_answer, $existing_answer_id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new answer
            $insert_sql = "INSERT INTO answers (attempt_id, question_id, text_answer, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("iis", $attempt_id, $question_id, $text_answer);
            $stmt->execute();
            $stmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // Return success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Answer saved successfully',
            'has_image' => ($image_path !== null),
            'image_path' => $image_path
        ]);
        exit;
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

// If not a POST request, return error
header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
exit;