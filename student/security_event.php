<?php
/**
 * API to log security events during an exam
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
    $event_type = isset($_POST['event_type']) ? $_POST['event_type'] : '';
    $event_data = isset($_POST['event_data']) ? $_POST['event_data'] : '{}';
    
    // Verify this attempt belongs to the current user
    $verify_sql = "SELECT ea.id, e.browser_security, e.allow_browser_warnings, e.max_violations, 
                  ea.security_violations, ea.security_warnings, e.id as exam_id
                  FROM exam_attempts ea
                  JOIN exams e ON ea.exam_id = e.id
                  WHERE ea.id = ? AND ea.student_id = ?";
    
    $verified = false;
    $browser_security = false;
    $allow_warnings = true;
    $max_violations = 3;
    $current_violations = 0;
    $current_warnings = 0;
    $exam_id = 0;
    
    if ($stmt = $conn->prepare($verify_sql)) {
        $stmt->bind_param("ii", $attempt_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $verified = true;
            $browser_security = (bool)$row['browser_security'];
            $allow_warnings = (bool)$row['allow_browser_warnings'];
            $max_violations = (int)$row['max_violations'];
            $current_violations = (int)$row['security_violations'];
            $current_warnings = (int)$row['security_warnings'];
            $exam_id = (int)$row['exam_id'];
        }
        $stmt->close();
    }
    
    if (!$verified) {
        echo json_encode(['success' => false, 'message' => 'Invalid attempt ID']);
        exit;
    }
    
    // If browser security is not enabled, don't record events
    if (!$browser_security) {
        echo json_encode(['success' => true, 'message' => 'Security monitoring is disabled']);
        exit;
    }
    
    // Map event types to whether they should count as violations
    $violation_events = [
        'visibility_change' => true,
        'fullscreen_exit' => true,
        'tab_switch' => true,
        'copy_attempt' => true,
        'paste_attempt' => true
    ];
    
    // Determine if this is a violation or just a warning
    $is_violation = isset($violation_events[$event_type]) && $violation_events[$event_type];
    
    // Update violation/warning counters
    if ($is_violation) {
        // If we're in warning mode, increment warnings instead of violations
        if ($allow_warnings) {
            $update_sql = "UPDATE exam_attempts 
                          SET security_warnings = security_warnings + 1
                          WHERE id = ?";
        } else {
            $update_sql = "UPDATE exam_attempts 
                          SET security_violations = security_violations + 1
                          WHERE id = ?";
        }
        
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("i", $attempt_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Log the event
    $current_log_sql = "SELECT security_log FROM exam_attempts WHERE id = ?";
    $current_log = '';
    
    if ($stmt = $conn->prepare($current_log_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $stmt->bind_result($current_log);
        $stmt->fetch();
        $stmt->close();
    }
    
    // Parse existing log or create new log array
    $log_array = !empty($current_log) ? json_decode($current_log, true) : [];
    if (!is_array($log_array)) {
        $log_array = [];
    }
    
    // Add new event
    $log_array[] = [
        'type' => $event_type,
        'is_violation' => $is_violation,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $event_data
    ];
    
    // Update log in database
    $update_log_sql = "UPDATE exam_attempts 
                      SET security_log = ?
                      WHERE id = ?";
    
    if ($stmt = $conn->prepare($update_log_sql)) {
        $log_json = json_encode($log_array);
        $stmt->bind_param("si", $log_json, $attempt_id);
        $stmt->execute();
        $stmt->close();
    }
    
    // Get updated counters
    $counters_sql = "SELECT security_violations, security_warnings 
                    FROM exam_attempts 
                    WHERE id = ?";
    
    $violation_count = 0;
    $warning_count = 0;
    
    if ($stmt = $conn->prepare($counters_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $stmt->bind_result($violation_count, $warning_count);
        $stmt->fetch();
        $stmt->close();
    }
    
    // Check if we need to auto-submit the exam
    $should_auto_submit = false;
    $auto_submit_reason = '';
    
    // If in strict mode, check violations, otherwise check warnings
    if (!$allow_warnings && $violation_count >= $max_violations) {
        $should_auto_submit = true;
        $auto_submit_reason = 'You have exceeded the maximum number of security violations.';
    } else if ($allow_warnings && $warning_count >= $max_violations) {
        $should_auto_submit = true;
        $auto_submit_reason = 'You have exceeded the maximum number of security warnings.';
    }
    
    // Auto-submit the exam if needed
    if ($should_auto_submit) {
        // Mark the attempt as complete
        $complete_sql = "UPDATE exam_attempts 
                        SET completed_at = NOW(), 
                            security_log = JSON_ARRAY_APPEND(IFNULL(security_log, JSON_ARRAY()), '$', 
                                JSON_OBJECT(
                                    'type', 'auto_submit',
                                    'timestamp', NOW(),
                                    'reason', ?
                                )
                            )
                        WHERE id = ?";
        
        if ($stmt = $conn->prepare($complete_sql)) {
            $stmt->bind_param("si", $auto_submit_reason, $attempt_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Auto-grade multiple-choice questions
        include_once 'submit_exam.php';
        
        echo json_encode([
            'success' => true,
            'violation_count' => $violation_count,
            'warning_count' => $warning_count,
            'auto_submitted' => true,
            'message' => $auto_submit_reason,
            'redirect_url' => SITE_URL . '/student/exam_result.php?attempt_id=' . $attempt_id
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'violation_count' => $violation_count,
        'warning_count' => $warning_count,
        'max_violations' => $max_violations,
        'should_auto_submit' => $should_auto_submit
    ]);
    exit;
}

// If we get here, it's an invalid request
echo json_encode(['success' => false, 'message' => 'Invalid request']);