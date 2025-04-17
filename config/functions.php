<?php
/**
 * Helper functions for PHP Exam System
 */

/**
 * Check if a user is logged in
 *
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if the logged-in user is an admin
 *
 * @return bool True if user is an admin, false otherwise
 */
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === USER_ROLE_ADMIN;
}

/**
 * Check if the logged-in user is a student
 *
 * @return bool True if user is a student, false otherwise
 */
function isStudent() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === USER_ROLE_STUDENT;
}

/**
 * Redirect to a specified URL
 *
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Set a flash message to be displayed on the next page load
 *
 * @param string $type The type of message (success, error, warning, info)
 * @param string $message The message content
 * @return void
 */
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get all flash messages and clear them from the session
 *
 * @return array Array of flash messages
 */
function getFlashMessages() {
    $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
    $_SESSION['flash_messages'] = [];
    return $messages;
}

/**
 * Log an activity in the system
 *
 * @param string $action Description of the action
 * @param int $user_id ID of the user performing the action
 * @param string $target_type Type of the target (user, exam, question, etc.)
 * @param int $target_id ID of the target
 * @param string $details Additional details (optional)
 * @return bool True if successful, false otherwise
 */
function logActivity($action, $user_id, $target_type, $target_id, $details = null) {
    global $conn;
    
    $sql = "INSERT INTO activity_logs (user_id, action, target_type, target_id, details, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("issss", $user_id, $action, $target_type, $target_id, $details);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
    
    return false;
}

/**
 * Format a date/time string
 *
 * @param string $date Date string to format
 * @param string $format Format string (default: 'M j, Y g:i A')
 * @return string Formatted date string
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

/**
 * Format a duration in minutes to a readable string
 *
 * @param int $minutes Duration in minutes
 * @return string Formatted duration string
 */
function formatDuration($minutes) {
    if ($minutes < 60) {
        return $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes');
    } else {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($mins == 0) {
            return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        } else {
            return $hours . ' ' . ($hours == 1 ? 'hour' : 'hours') . ' ' . $mins . ' ' . ($mins == 1 ? 'minute' : 'minutes');
        }
    }
}

/**
 * Format a time remaining in seconds
 *
 * @param int $seconds Time remaining in seconds
 * @return string Formatted time string
 */
function formatTimeRemaining($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $secs);
}

/**
 * Calculate letter grade based on percentage
 *
 * @param float $percentage Score percentage
 * @return string Letter grade
 */
function calculateGrade($percentage) {
    if ($percentage >= 90) return 'A';
    if ($percentage >= 80) return 'B';
    if ($percentage >= 70) return 'C';
    if ($percentage >= 60) return 'D';
    return 'F';
}

/**
 * Check if a matric number is valid
 *
 * @param string $matric_number Matric number to check
 * @return bool True if valid, false otherwise
 */
function isValidMatricNumber($matric_number) {
    // Basic validation - can be customized based on institution's format
    return preg_match('/^[A-Z0-9]{4,20}$/i', $matric_number);
}

/**
 * Truncate text to a specified length
 *
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @param string $append String to append if truncated
 * @return string Truncated text
 */
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));
    
    return $text . $append;
}

/**
 * Generate a random token
 *
 * @param int $length Length of the token
 * @return string Random token
 */
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check if an exam is accessible to a student
 *
 * @param array $exam Exam data
 * @param int $user_id Student ID
 * @return bool True if accessible, false otherwise
 */
function isExamAccessible($exam, $user_id) {
    global $conn;
    
    // Check if exam is published
    if (!$exam['published']) {
        return false;
    }
    
    // Check date restrictions
    $now = date('Y-m-d H:i:s');
    
    if ($exam['start_date'] && $now < $exam['start_date']) {
        return false;
    }
    
    if ($exam['end_date'] && $now > $exam['end_date']) {
        return false;
    }
    
    // Check if student has already completed the exam
    $sql = "SELECT id FROM exam_attempts 
            WHERE exam_id = ? AND student_id = ? AND completed_at IS NOT NULL";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $exam['id'], $user_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $stmt->close();
            return false;
        }
        
        $stmt->close();
    }
    
    // Check course enrollment if exam is associated with a course
    if ($exam['course_id']) {
        $sql = "SELECT 1 FROM user_courses WHERE user_id = ? AND course_id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $user_id, $exam['course_id']);
            $stmt->execute();
            $stmt->store_result();
            
            $is_enrolled = $stmt->num_rows > 0;
            $stmt->close();
            
            return $is_enrolled;
        }
    }
    
    return true;
}

/**
 * Auto-grade a multiple choice answer
 *
 * @param int $question_id Question ID
 * @param int $selected_choice_id Selected choice ID
 * @return float Score for the answer
 */
function gradeMultipleChoiceAnswer($question_id, $selected_choice_id) {
    global $conn;
    
    // Get the question points and check if selected choice is correct
    $sql = "SELECT q.points, c.is_correct 
            FROM questions q
            JOIN choices c ON c.question_id = q.id 
            WHERE q.id = ? AND c.id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $question_id, $selected_choice_id);
        $stmt->execute();
        $stmt->bind_result($points, $is_correct);
        
        if ($stmt->fetch()) {
            $stmt->close();
            return $is_correct ? $points : 0;
        }
        
        $stmt->close();
    }
    
    return 0;
}

/**
 * Update an exam attempt's score
 *
 * @param int $attempt_id Attempt ID
 * @return bool True if successful, false otherwise
 */
function updateAttemptScore($attempt_id) {
    global $conn;
    
    // Get the exam attempt and exam details
    $attempt_sql = "SELECT ea.*, e.passing_score
                   FROM exam_attempts ea
                   JOIN exams e ON ea.exam_id = e.id
                   WHERE ea.id = ?";
    
    if ($stmt = $conn->prepare($attempt_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $attempt_result = $stmt->get_result();
        
        if ($attempt = $attempt_result->fetch_assoc()) {
            $stmt->close();
            
            // Calculate total points possible
            $total_points_sql = "SELECT SUM(points) as total_points FROM questions WHERE exam_id = ?";
            if ($total_stmt = $conn->prepare($total_points_sql)) {
                $total_stmt->bind_param("i", $attempt['exam_id']);
                $total_stmt->execute();
                $total_result = $total_stmt->get_result();
                $total_row = $total_result->fetch_assoc();
                $total_points = $total_row['total_points'];
                $total_stmt->close();
                
                if ($total_points > 0) {
                    // Calculate points earned
                    $earned_points_sql = "SELECT SUM(score) as earned_points 
                                         FROM answers 
                                         WHERE attempt_id = ?";
                    
                    if ($earned_stmt = $conn->prepare($earned_points_sql)) {
                        $earned_stmt->bind_param("i", $attempt_id);
                        $earned_stmt->execute();
                        $earned_result = $earned_stmt->get_result();
                        $earned_row = $earned_result->fetch_assoc();
                        $earned_points = $earned_row['earned_points'] ?: 0;
                        $earned_stmt->close();
                        
                        // Calculate percentage score
                        $percentage_score = ($earned_points / $total_points) * 100;
                        
                        // Determine if passed based on passing threshold
                        $passed = $percentage_score >= $attempt['passing_score'];
                        
                        // Update the attempt
                        $update_sql = "UPDATE exam_attempts 
                                      SET score = ?, is_graded = 1, passed = ?
                                      WHERE id = ?";
                        
                        if ($update_stmt = $conn->prepare($update_sql)) {
                            $update_stmt->bind_param("dii", $percentage_score, $passed, $attempt_id);
                            $result = $update_stmt->execute();
                            $update_stmt->close();
                            return $result;
                        }
                    }
                }
            }
        }
    }
    
    return false;
}

/**
 * Get the count of pending answers that need manual grading
 *
 * @param int $attempt_id Attempt ID
 * @return int Count of answers needing grading
 */
function countPendingGradingAnswers($attempt_id) {
    global $conn;
    
    $sql = "SELECT COUNT(*) as count
            FROM answers a
            JOIN questions q ON a.question_id = q.id
            WHERE a.attempt_id = ?
            AND q.type IN ('short_answer', 'essay')
            AND a.is_graded = 0";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $stmt->close();
            return $row['count'];
        }
        
        $stmt->close();
    }
    
    return 0;
}

/**
 * Check if all answers for an attempt are graded
 *
 * @param int $attempt_id Attempt ID
 * @return bool True if all answers are graded, false otherwise
 */
function areAllAnswersGraded($attempt_id) {
    return countPendingGradingAnswers($attempt_id) === 0;
}

/**
 * Get user theme preference
 *
 * @return string Theme preference ('light' or 'dark')
 */
function getUserTheme() {
    return isset($_SESSION['theme_preference']) ? $_SESSION['theme_preference'] : 'light';
}

/**
 * Toggle user theme preference
 *
 * @return string New theme preference
 */
function toggleUserTheme() {
    global $conn;
    
    if (!isLoggedIn()) {
        // Just toggle session value for non-logged in users
        $_SESSION['theme_preference'] = ($_SESSION['theme_preference'] ?? 'light') === 'light' ? 'dark' : 'light';
        return $_SESSION['theme_preference'];
    }
    
    // For logged in users, update the database
    $new_theme = $_SESSION['theme_preference'] === 'light' ? 'dark' : 'light';
    
    $sql = "UPDATE users SET theme_preference = ? WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $new_theme, $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }
    
    $_SESSION['theme_preference'] = $new_theme;
    return $new_theme;
}

/**
 * Safely fetch a row from a query result
 * This function helps handle database query errors gracefully
 *
 * @param mysqli_result|bool $result The result from a mysqli query
 * @return array|null The fetched row as an associative array, or null if error or no results
 */
function fetchRowSafely($result) {
    if ($result && $result instanceof mysqli_result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}
?>