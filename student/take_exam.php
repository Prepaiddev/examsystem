<?php
/**
 * Student Take Exam Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    // Not logged in or not a student, redirect to login page
    setFlashMessage('error', 'You must be logged in as a student to take an exam.');
    redirect(SITE_URL . '/login.php');
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Check if exam ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid exam ID.');
    redirect(SITE_URL . '/student/exams.php');
}

$exam_id = intval($_GET['id']);

// Get exam details
$exam = [];
$exam_sql = "SELECT e.*, c.title as course_title, c.id as course_id
             FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             WHERE e.id = ? AND e.published = 1";

if ($stmt = $conn->prepare($exam_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = fetchRowSafely($result);
    $stmt->close();
}

// If exam not found, redirect
if (empty($exam)) {
    setFlashMessage('error', 'Exam not found or not available.');
    redirect(SITE_URL . '/student/exams.php');
}

// Check if exam is available to take (start and end dates)
$current_time = time();
if ($exam['start_date'] && strtotime($exam['start_date']) > $current_time) {
    setFlashMessage('error', 'This exam is not yet available. It starts on ' . formatDate($exam['start_date'], 'M j, Y g:i A'));
    redirect(SITE_URL . '/student/exam_detail.php?id=' . $exam_id);
}

if ($exam['end_date'] && strtotime($exam['end_date']) < $current_time) {
    setFlashMessage('error', 'This exam is no longer available. It ended on ' . formatDate($exam['end_date'], 'M j, Y g:i A'));
    redirect(SITE_URL . '/student/exam_detail.php?id=' . $exam_id);
}

// Check if student has access to this exam (enrolled in the course)
if ($exam['course_id']) {
    $access_sql = "SELECT 1 FROM user_courses 
                  WHERE user_id = ? AND course_id = ?";
    if ($stmt = $conn->prepare($access_sql)) {
        $stmt->bind_param("ii", $user_id, $exam['course_id']);
        $stmt->execute();
        $stmt->store_result();
        $has_access = ($stmt->num_rows > 0);
        $stmt->close();
        
        if (!$has_access) {
            setFlashMessage('error', 'You do not have access to this exam. Please enroll in the course first.');
            redirect(SITE_URL . '/student/courses.php');
        }
    }
}

// Check if student has already completed this exam
$completed_sql = "SELECT 1 FROM exam_attempts 
                 WHERE student_id = ? AND exam_id = ? AND completed_at IS NOT NULL";
if ($stmt = $conn->prepare($completed_sql)) {
    $stmt->bind_param("ii", $user_id, $exam_id);
    $stmt->execute();
    $stmt->store_result();
    $has_completed = ($stmt->num_rows > 0);
    $stmt->close();
    
    if ($has_completed) {
        setFlashMessage('info', 'You have already completed this exam. You can view your results instead.');
        redirect(SITE_URL . '/student/exam_result.php?id=' . $exam_id);
    }
}

// Check if there's an existing in-progress attempt to resume
$attempt_id = null;
$existing_attempt_sql = "SELECT id FROM exam_attempts 
                        WHERE student_id = ? AND exam_id = ? AND completed_at IS NULL";
if ($stmt = $conn->prepare($existing_attempt_sql)) {
    $stmt->bind_param("ii", $user_id, $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $attempt_id = $row['id'];
    }
    $stmt->close();
}

// If no existing attempt, create a new one
if (!$attempt_id) {
    $create_attempt_sql = "INSERT INTO exam_attempts (exam_id, student_id, started_at)
                          VALUES (?, ?, NOW())";
    if ($stmt = $conn->prepare($create_attempt_sql)) {
        $stmt->bind_param("ii", $exam_id, $user_id);
        if ($stmt->execute()) {
            $attempt_id = $stmt->insert_id;
        } else {
            setFlashMessage('error', 'Failed to create exam attempt. Please try again.');
            redirect(SITE_URL . '/student/exam_detail.php?id=' . $exam_id);
        }
        $stmt->close();
    }
}

// Get questions for this exam
$questions = [];
$questions_sql = "SELECT q.*, 
                 (SELECT COUNT(*) FROM choices WHERE question_id = q.id) as choice_count
                 FROM questions q
                 WHERE q.exam_id = ?";

// Add order by clause
if ($exam['randomize_questions']) {
    $questions_sql .= " ORDER BY RAND()";
} else {
    $questions_sql .= " ORDER BY q.position";
}

if ($stmt = $conn->prepare($questions_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    $stmt->close();
}

// If exam has sections, get section info
$sections = [];
if ($exam['has_sections']) {
    $sections_sql = "SELECT * FROM sections 
                    WHERE exam_id = ?
                    ORDER BY position";
    if ($stmt = $conn->prepare($sections_sql)) {
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // Map questions to sections
            $row['questions'] = array_filter($questions, function($q) use ($row) {
                return $q['section_id'] == $row['id'];
            });
            $sections[] = $row;
        }
        
        $stmt->close();
    }
    
    // Check if we need to setup section attempts
    foreach ($sections as $section) {
        $check_section_sql = "SELECT 1 FROM section_attempts 
                             WHERE attempt_id = ? AND section_id = ?";
        if ($check_stmt = $conn->prepare($check_section_sql)) {
            $check_stmt->bind_param("ii", $attempt_id, $section['id']);
            $check_stmt->execute();
            $check_stmt->store_result();
            $has_section_attempt = ($check_stmt->num_rows > 0);
            $check_stmt->close();
            
            if (!$has_section_attempt) {
                // Create section attempt
                $create_section_sql = "INSERT INTO section_attempts 
                                      (attempt_id, section_id, time_remaining_seconds)
                                      VALUES (?, ?, ?)";
                if ($create_stmt = $conn->prepare($create_section_sql)) {
                    $time_remaining = $section['duration_minutes'] * 60;
                    $create_stmt->bind_param("iii", $attempt_id, $section['id'], $time_remaining);
                    $create_stmt->execute();
                    $create_stmt->close();
                }
            }
        }
    }
}

// For exams without sections, make sure all questions get loaded
if (!$exam['has_sections']) {
    $first_section = [
        'id' => 0,
        'title' => 'Exam Questions',
        'duration_minutes' => $exam['duration_minutes'],
        'questions' => $questions
    ];
    $sections = [$first_section];
}

// Get current active section (for sectioned exams)
$current_section = null;
$current_section_attempt = null;
if ($exam['has_sections']) {
    $current_section_sql = "SELECT s.*, sa.started_at, sa.time_remaining_seconds
                           FROM section_attempts sa 
                           JOIN sections s ON sa.section_id = s.id
                           WHERE sa.attempt_id = ? AND sa.completed_at IS NULL
                           ORDER BY s.position 
                           LIMIT 1";
    if ($stmt = $conn->prepare($current_section_sql)) {
        $stmt->bind_param("i", $attempt_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_section_attempt = fetchRowSafely($result);
        $stmt->close();
        
        if ($current_section_attempt) {
            // Find the current section data with questions
            foreach ($sections as $section) {
                if ($section['id'] == $current_section_attempt['id']) {
                    $current_section = $section;
                    $current_section['time_remaining_seconds'] = $current_section_attempt['time_remaining_seconds'];
                    $current_section['started_at'] = $current_section_attempt['started_at'];
                    break;
                }
            }
        } else {
            // All sections completed
            // Mark the exam as completed
            $complete_exam_sql = "UPDATE exam_attempts SET completed_at = NOW() WHERE id = ?";
            if ($complete_stmt = $conn->prepare($complete_exam_sql)) {
                $complete_stmt->bind_param("i", $attempt_id);
                $complete_stmt->execute();
                $complete_stmt->close();
                
                // Redirect to results
                redirect(SITE_URL . '/student/exam_result.php?id=' . $exam_id);
            }
        }
    }
} else {
    // For non-sectioned exams, just use the first section
    $current_section = $sections[0];
}

// Update last active timestamp
$update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Set page title
$page_title = 'Taking Exam: ' . $exam['title'];

// Include a minimal header for the exam
include '../includes/header_minimal.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Exam Header -->
        <div class="col-12 bg-primary text-white py-3">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h4 mb-0"><?php echo htmlspecialchars($exam['title']); ?></h1>
                        <?php if ($exam['course_title']): ?>
                            <div class="small">Course: <?php echo htmlspecialchars($exam['course_title']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="text-center">
                        <div class="h4 mb-0" id="examTimer">
                            <i class="fas fa-clock me-1"></i> <span id="timerDisplay">--:--:--</span>
                        </div>
                        <div class="small">Time Remaining</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Exam Main Content -->
        <div class="col-12 py-4">
            <div class="container">
                <div class="row">
                    <!-- Exam Questions -->
                    <div class="col-lg-9">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <?php if ($exam['has_sections']): ?>
                                        Section: <?php echo htmlspecialchars($current_section['title']); ?>
                                    <?php else: ?>
                                        Exam Questions
                                    <?php endif; ?>
                                </h5>
                                
                                <div id="progressDisplay">
                                    Question <span id="currentQuestionNum">1</span> of <?php echo count($current_section['questions']); ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <form id="examForm" method="post" action="submit_exam.php">
                                    <input type="hidden" name="exam_id" value="<?php echo $exam_id; ?>">
                                    <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
                                    <?php if ($exam['has_sections']): ?>
                                        <input type="hidden" name="section_id" value="<?php echo $current_section['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <div id="questionsContainer">
                                        <?php foreach($current_section['questions'] as $index => $question): ?>
                                            <div class="question-slide" data-question-index="<?php echo $index; ?>" style="<?php echo $index > 0 ? 'display: none;' : ''; ?>">
                                                <div class="question-header mb-3">
                                                    <h5>Question <?php echo $index + 1; ?></h5>
                                                    <div class="question-points text-muted">
                                                        <i class="fas fa-star me-1"></i> <?php echo $question['points']; ?> point<?php echo $question['points'] != 1 ? 's' : ''; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="question-text mb-4">
                                                    <?php echo nl2br(htmlspecialchars($question['text'])); ?>
                                                </div>
                                                
                                                <div class="question-answer">
                                                    <input type="hidden" name="question_ids[]" value="<?php echo $question['id']; ?>">
                                                    
                                                    <?php if ($question['type'] === 'multiple_choice'): ?>
                                                        <?php
                                                        // Get choices for this question
                                                        $choices = [];
                                                        $choices_sql = "SELECT * FROM choices WHERE question_id = ?";
                                                        if ($stmt = $conn->prepare($choices_sql)) {
                                                            $stmt->bind_param("i", $question['id']);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            
                                                            while ($row = $result->fetch_assoc()) {
                                                                $choices[] = $row;
                                                            }
                                                            
                                                            $stmt->close();
                                                        }
                                                        
                                                        // Check for existing answer
                                                        $selected_choice = null;
                                                        $answer_sql = "SELECT * FROM answers 
                                                                      WHERE attempt_id = ? AND question_id = ?";
                                                        if ($stmt = $conn->prepare($answer_sql)) {
                                                            $stmt->bind_param("ii", $attempt_id, $question['id']);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            if ($row = $result->fetch_assoc()) {
                                                                $selected_choice = $row['selected_choice_id'];
                                                            }
                                                            $stmt->close();
                                                        }
                                                        ?>
                                                        
                                                        <div class="list-group mb-3">
                                                            <?php foreach($choices as $choice): ?>
                                                                <label class="list-group-item d-flex align-items-center">
                                                                    <input type="radio" 
                                                                           name="answer_<?php echo $question['id']; ?>" 
                                                                           value="<?php echo $choice['id']; ?>"
                                                                           class="form-check-input me-2"
                                                                           <?php echo $selected_choice == $choice['id'] ? 'checked' : ''; ?>>
                                                                    <?php echo htmlspecialchars($choice['text']); ?>
                                                                </label>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        
                                                    <?php elseif ($question['type'] === 'short_answer' || $question['type'] === 'essay'): ?>
                                                        <?php
                                                        // Check for existing answer
                                                        $existing_answer = '';
                                                        $has_image = false;
                                                        $image_info = null;
                                                        
                                                        $answer_sql = "SELECT * FROM answers 
                                                                      WHERE attempt_id = ? AND question_id = ?";
                                                        if ($stmt = $conn->prepare($answer_sql)) {
                                                            $stmt->bind_param("ii", $attempt_id, $question['id']);
                                                            $stmt->execute();
                                                            $result = $stmt->get_result();
                                                            if ($row = $result->fetch_assoc()) {
                                                                $existing_answer = $row['text_answer'];
                                                                
                                                                // Check if answer contains an image reference
                                                                if (preg_match('/\[Image: (.+?)\]/', $existing_answer, $matches)) {
                                                                    $has_image = true;
                                                                    $image_info = $matches[1];
                                                                    // Remove image tag from display text
                                                                    $existing_answer = trim(str_replace("\n\n[Image: {$matches[1]}]", '', $existing_answer));
                                                                }
                                                            }
                                                            $stmt->close();
                                                        }
                                                        
                                                        $rows = $question['type'] === 'essay' ? 8 : 3;
                                                        ?>
                                                        
                                                        <div class="form-group">
                                                            <textarea class="form-control answer-text" 
                                                                      name="answer_<?php echo $question['id']; ?>" 
                                                                      id="answer_text_<?php echo $question['id']; ?>"
                                                                      data-question-id="<?php echo $question['id']; ?>"
                                                                      rows="<?php echo $rows; ?>"
                                                                      placeholder="Enter your answer here..."><?php echo htmlspecialchars($existing_answer); ?></textarea>
                                                        </div>
                                                        
                                                        <!-- Image upload for short answer and essay questions -->
                                                        <div class="mb-3">
                                                            <label class="form-label">Upload Image (Optional):</label>
                                                            <input type="file" class="form-control answer-image-upload" 
                                                                   id="answer_image_<?php echo $question['id']; ?>" 
                                                                   data-question-id="<?php echo $question['id']; ?>"
                                                                   data-attempt-id="<?php echo $attempt_id; ?>"
                                                                   accept="image/jpeg,image/png,image/gif">
                                                            <div class="form-text">Upload an image to support your answer (max 5MB).</div>
                                                            
                                                            <?php if ($has_image): ?>
                                                            <div class="mt-2 uploaded-image-container">
                                                                <div class="alert alert-info">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="fas fa-image me-2"></i>
                                                                        <div>
                                                                            <strong>Image uploaded:</strong> <?php echo htmlspecialchars($image_info); ?>
                                                                        </div>
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto view-image-btn" 
                                                                                data-image-path="<?php echo SITE_URL; ?>/uploads/answers/<?php echo $attempt_id; ?>/<?php echo htmlspecialchars($image_info); ?>">
                                                                            <i class="fas fa-eye me-1"></i> View
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <?php endif; ?>
                                                            
                                                            <div id="image_preview_<?php echo $question['id']; ?>" class="mt-2 d-none">
                                                                <div class="alert alert-success">
                                                                    <div class="d-flex align-items-center">
                                                                        <i class="fas fa-check-circle me-2"></i>
                                                                        <div>Image uploaded successfully</div>
                                                                        <button type="button" class="btn btn-sm btn-outline-secondary ms-auto preview-image-btn" 
                                                                                data-question-id="<?php echo $question['id']; ?>">
                                                                            <i class="fas fa-eye me-1"></i> View
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="d-flex justify-content-between mt-4">
                                                    <?php if ($index > 0): ?>
                                                        <button type="button" class="btn btn-outline-secondary prev-question">
                                                            <i class="fas fa-arrow-left me-1"></i> Previous
                                                        </button>
                                                    <?php else: ?>
                                                        <div></div> <!-- Placeholder for flex alignment -->
                                                    <?php endif; ?>
                                                    
                                                    <div>
                                                        <div class="form-check mb-2">
                                                            <?php
                                                            // Check if this question is marked for review
                                                            $marked_for_review = false;
                                                            $review_sql = "SELECT marked_for_review FROM answers 
                                                                          WHERE attempt_id = ? AND question_id = ?";
                                                            if ($stmt = $conn->prepare($review_sql)) {
                                                                $stmt->bind_param("ii", $attempt_id, $question['id']);
                                                                $stmt->execute();
                                                                $result = $stmt->get_result();
                                                                if ($row = $result->fetch_assoc()) {
                                                                    $marked_for_review = (bool)$row['marked_for_review'];
                                                                }
                                                                $stmt->close();
                                                            }
                                                            ?>
                                                            <input class="form-check-input review-checkbox" 
                                                                   type="checkbox" 
                                                                   id="reviewCheckbox_<?php echo $question['id']; ?>"
                                                                   data-question-id="<?php echo $question['id']; ?>"
                                                                   <?php echo $marked_for_review ? 'checked' : ''; ?>>
                                                            <label class="form-check-label" for="reviewCheckbox_<?php echo $question['id']; ?>">
                                                                Mark for review
                                                            </label>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if ($index < count($current_section['questions']) - 1): ?>
                                                        <button type="button" class="btn btn-primary next-question">
                                                            Next <i class="fas fa-arrow-right ms-1"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button" class="btn btn-success" id="completeExamBtn">
                                                            <i class="fas fa-check-circle me-1"></i> Complete Exam
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Exam Navigation Sidebar -->
                    <div class="col-lg-3">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Question Navigation</h5>
                            </div>
                            <div class="card-body">
                                <div class="question-grid">
                                    <?php foreach($current_section['questions'] as $index => $question): ?>
                                        <button type="button" 
                                                class="btn question-btn" 
                                                data-question-index="<?php echo $index; ?>">
                                            <?php echo $index + 1; ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="mt-3">
                                    <div class="small mb-2">Legend:</div>
                                    <div class="d-flex flex-wrap">
                                        <div class="me-3 mb-2">
                                            <span class="badge bg-light border text-dark">&nbsp;</span> Not answered
                                        </div>
                                        <div class="me-3 mb-2">
                                            <span class="badge bg-success">&nbsp;</span> Answered
                                        </div>
                                        <div class="mb-2">
                                            <span class="badge bg-warning text-dark">&nbsp;</span> Marked for review
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Exam Controls</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-primary" id="saveProgressBtn">
                                        <i class="fas fa-save me-1"></i> Save Progress
                                    </button>
                                    <button type="button" class="btn btn-success" id="submitExamBtn">
                                        <i class="fas fa-paper-plane me-1"></i> Submit Exam
                                    </button>
                                    <?php if (!empty($exam['browser_security'])): ?>
                                        <div class="alert alert-warning mb-0 mt-3">
                                            <div class="d-flex">
                                                <div class="me-2">
                                                    <i class="fas fa-shield-alt fa-lg"></i>
                                                </div>
                                                <div>
                                                    <strong>Security Enabled</strong>
                                                    <p class="mb-0 small">Please stay in this tab. Changing tabs may be recorded as a security violation.</p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Submit Modal -->
<div class="modal fade" id="submitConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Exam?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to submit this exam? This action cannot be undone.</p>
                
                <div class="alert alert-info">
                    <div class="d-flex">
                        <div class="me-2">
                            <i class="fas fa-info-circle fa-lg"></i>
                        </div>
                        <div id="questionsStatusSummary">
                            Calculating question status...
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Exam</button>
                <button type="button" class="btn btn-primary" id="confirmSubmitBtn">
                    Yes, Submit Exam
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Security Warning Modal -->
<div class="modal fade" id="securityWarningModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i> Security Warning
                </h5>
            </div>
            <div class="modal-body">
                <p>A potential security violation has been detected. Please return to the exam window immediately.</p>
                <p>Multiple violations may result in your exam being automatically submitted.</p>
                
                <div class="alert alert-danger">
                    <div class="d-flex">
                        <div class="me-2">
                            <i class="fas fa-shield-alt fa-lg"></i>
                        </div>
                        <div>
                            <strong>Security Notice</strong>
                            <p class="mb-0 small">
                                Warning <span id="warningCount">1</span> of <?php echo $exam['max_violations']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="acknowledgeWarningBtn">
                    I Understand
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Question navigation grid */
.question-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 8px;
}

.question-btn {
    width: 100%;
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #343a40;
}

.question-btn.active {
    background-color: #0d6efd;
    color: white;
    border-color: #0d6efd;
}

.question-btn.answered {
    background-color: #198754;
    color: white;
    border-color: #198754;
}

.question-btn.review {
    background-color: #ffc107;
    color: #343a40;
    border-color: #ffc107;
}

/* Timer styling */
#examTimer {
    background-color: rgba(0, 0, 0, 0.2);
    padding: 5px 15px;
    border-radius: 4px;
}

/* Fullscreen mode */
.fullscreen-warning {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 2rem;
    text-align: center;
}
</style>

<!-- Include exam timer script -->
<script src="<?php echo SITE_URL; ?>/assets/js/exam-timer.js"></script>

<script>
// Image View Modal
document.addEventListener('DOMContentLoaded', function() {
    // Create the image view modal dynamically
    const modalHtml = `
        <div class="modal fade" id="imageViewModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Image View</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img id="modalImage" class="img-fluid" alt="Uploaded image">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Initialize the modal
    const imageViewModal = new bootstrap.Modal(document.getElementById('imageViewModal'));
    
    // Handle image file uploads
    document.querySelectorAll('.answer-image-upload').forEach(fileInput => {
        fileInput.addEventListener('change', function(e) {
            if (!this.files || !this.files[0]) return;
            
            const file = this.files[0];
            const questionId = this.dataset.questionId;
            const attemptId = this.dataset.attemptId;
            const textArea = document.getElementById('answer_text_' + questionId);
            const previewContainer = document.getElementById('image_preview_' + questionId);
            
            // Create form data
            const formData = new FormData();
            formData.append('attempt_id', attemptId);
            formData.append('question_id', questionId);
            formData.append('text_answer', textArea.value);
            formData.append('answer_image', file);
            
            // Show loading state
            const originalText = textArea.closest('.form-group').querySelector('label').textContent;
            textArea.closest('.form-group').querySelector('label').textContent = 'Uploading image...';
            this.disabled = true;
            
            // Submit via AJAX
            fetch('submit_written_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Reset loading state
                textArea.closest('.form-group').querySelector('label').textContent = originalText;
                this.disabled = false;
                
                if (data.success) {
                    // Show success message with preview
                    previewContainer.classList.remove('d-none');
                    
                    // Create a blob URL for quick preview
                    const imageUrl = URL.createObjectURL(file);
                    previewContainer.dataset.imageUrl = imageUrl;
                    
                    // Mark question as answered in the navigation
                    const questionIndex = textArea.closest('.question-container').dataset.questionIndex;
                    document.querySelector(`.question-btn[data-question-index="${questionIndex}"]`).classList.add('answered');
                    
                    // Notify user
                    alert('Image uploaded successfully!');
                } else {
                    // Show error
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                // Reset loading state and show error
                textArea.closest('.form-group').querySelector('label').textContent = originalText;
                this.disabled = false;
                alert('Error uploading image: ' + error);
            });
        });
    });
    
    // Handle view image buttons (for existing uploads)
    document.querySelectorAll('.view-image-btn').forEach(button => {
        button.addEventListener('click', function() {
            const imagePath = this.dataset.imagePath;
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imagePath;
            imageViewModal.show();
        });
    });
    
    // Handle preview buttons (for new uploads)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('preview-image-btn') || e.target.closest('.preview-image-btn')) {
            const button = e.target.classList.contains('preview-image-btn') ? 
                          e.target : e.target.closest('.preview-image-btn');
            const questionId = button.dataset.questionId;
            const previewContainer = document.getElementById('image_preview_' + questionId);
            const imageUrl = previewContainer.dataset.imageUrl;
            
            if (imageUrl) {
                const modalImage = document.getElementById('modalImage');
                modalImage.src = imageUrl;
                imageViewModal.show();
            }
        }
    });
    
    // Update answers when textarea changes
    document.querySelectorAll('.answer-text').forEach(textarea => {
        textarea.addEventListener('blur', function() {
            if (this.value.trim() === '') return;
            
            const questionId = this.dataset.questionId;
            const attemptId = document.querySelector('.answer-image-upload[data-question-id="' + questionId + '"]').dataset.attemptId;
            
            // Create form data
            const formData = new FormData();
            formData.append('attempt_id', attemptId);
            formData.append('question_id', questionId);
            formData.append('text_answer', this.value);
            
            // Submit via AJAX
            fetch('submit_written_answer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mark question as answered in the navigation
                    const questionIndex = this.closest('.question-container').dataset.questionIndex;
                    document.querySelector(`.question-btn[data-question-index="${questionIndex}"]`).classList.add('answered');
                }
            })
            .catch(error => {
                console.error('Error saving answer:', error);
            });
        });
    });
});
// Exam variables
let securityEnabled = <?php echo $exam['browser_security'] ? 'true' : 'false'; ?>;
let allowWarnings = <?php echo $exam['allow_browser_warnings'] ? 'true' : 'false'; ?>;
let maxViolations = <?php echo $exam['max_violations']; ?>;
let violationCount = 0;
let warningCount = 0;
let answeredQuestions = {};
let reviewQuestions = {};

// Initialize exam timer
let examTimer;

// Question navigation
let currentQuestionIndex = 0;
const totalQuestions = <?php echo count($current_section['questions']); ?>;

// DOM elements
const questionsContainer = document.getElementById('questionsContainer');
const questionSlides = document.querySelectorAll('.question-slide');
const questionBtns = document.querySelectorAll('.question-btn');
const nextBtns = document.querySelectorAll('.next-question');
const prevBtns = document.querySelectorAll('.prev-question');
const currentQuestionNumEl = document.getElementById('currentQuestionNum');
const timerDisplayEl = document.getElementById('timerDisplay');
const saveProgressBtn = document.getElementById('saveProgressBtn');
const submitExamBtn = document.getElementById('submitExamBtn');
const completeExamBtn = document.getElementById('completeExamBtn');
const examForm = document.getElementById('examForm');
const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
const questionsStatusSummary = document.getElementById('questionsStatusSummary');
const acknowledgeBtnEl = document.getElementById('acknowledgeWarningBtn');
const warningCountEl = document.getElementById('warningCount');

// Document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize the timer
    startTimer();
    
    // Mark questions that have been answered
    checkAnsweredQuestions();
    
    // Update navigation buttons
    updateQuestionButtons();
    
    // Initialize event listeners
    initEventListeners();
    
    // Initialize security features if enabled
    if (securityEnabled) {
        initSecurityFeatures();
    }
    
    // Auto-save answers periodically
    setInterval(saveProgress, 30000); // Every 30 seconds
    
    // If this is a sectioned exam, start the section if needed
    <?php if ($exam['has_sections'] && empty($current_section['started_at'])): ?>
    startSection();
    <?php endif; ?>
});

// Start a section (set started_at time)
function startSection() {
    fetch('section_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'start',
            'attempt_id': <?php echo $attempt_id; ?>,
            'section_id': <?php echo $current_section['id']; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to start section:', data.message);
        }
    })
    .catch(error => {
        console.error('Error starting section:', error);
    });
}

// Save time remaining for section
function saveTimeRemaining() {
    // Get current time from the timer if available
    const currentTimeRemaining = examTimer ? examTimer.getTimeRemaining() : remainingTime;
    
    fetch('section_action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'update_time',
            'attempt_id': <?php echo $attempt_id; ?>,
            'section_id': <?php echo $current_section['id']; ?>,
            'remaining_seconds': currentTimeRemaining
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to save time remaining:', data.message);
        }
    })
    .catch(error => {
        console.error('Error saving time remaining:', error);
    });
}

// Initialize all event listeners
function initEventListeners() {
    // Question navigation
    questionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const index = parseInt(this.dataset.questionIndex);
            goToQuestion(index);
        });
    });
    
    // Next buttons
    nextBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            goToQuestion(currentQuestionIndex + 1);
        });
    });
    
    // Previous buttons
    prevBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            goToQuestion(currentQuestionIndex - 1);
        });
    });
    
    // Review checkboxes
    document.querySelectorAll('.review-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const questionId = this.dataset.questionId;
            markForReview(questionId, this.checked);
        });
    });
    
    // Save progress button
    saveProgressBtn.addEventListener('click', function() {
        saveProgress(true); // true = show success message
    });
    
    // Submit exam buttons
    submitExamBtn.addEventListener('click', showSubmitConfirmation);
    completeExamBtn.addEventListener('click', showSubmitConfirmation);
    confirmSubmitBtn.addEventListener('click', submitExam);
    
    // Acknowledge warning button
    acknowledgeBtnEl.addEventListener('click', function() {
        const warningModal = new bootstrap.Modal(document.getElementById('securityWarningModal'));
        warningModal.hide();
    });
    
    // Auto-save on input changes
    document.querySelectorAll('input[type="radio"], textarea').forEach(input => {
        input.addEventListener('change', function() {
            // Mark question as answered
            const questionId = this.name.replace('answer_', '');
            answeredQuestions[questionId] = true;
            updateQuestionButtons();
            
            // Save after short delay
            setTimeout(saveProgress, 1000);
        });
    });
}

// Initialize security features
function initSecurityFeatures() {
    // Tab visibility change
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            logSecurityEvent('visibility_change');
        }
    });
    
    // Detect fullscreen exit
    document.addEventListener('fullscreenchange', function() {
        if (!document.fullscreenElement) {
            logSecurityEvent('fullscreen_exit');
        }
    });
    
    // Enter fullscreen mode
    requestFullscreen();
}

// Request fullscreen mode
function requestFullscreen() {
    const docEl = document.documentElement;
    
    if (docEl.requestFullscreen) {
        docEl.requestFullscreen();
    } else if (docEl.mozRequestFullScreen) {
        docEl.mozRequestFullScreen();
    } else if (docEl.webkitRequestFullscreen) {
        docEl.webkitRequestFullscreen();
    } else if (docEl.msRequestFullscreen) {
        docEl.msRequestFullscreen();
    }
}

// Log security event
function logSecurityEvent(eventType) {
    if (!securityEnabled) return;
    
    fetch('security_event.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'attempt_id': <?php echo $attempt_id; ?>,
            'event_type': eventType,
            'event_data': JSON.stringify({
                'url': window.location.href,
                'timestamp': new Date().toISOString()
            })
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            violationCount = data.violation_count || 0;
            warningCount = data.warning_count || 0;
            
            // Show warning if applicable
            if (allowWarnings && warningCount > 0) {
                showSecurityWarning();
            }
            
            // Handle auto-submission
            if (data.auto_submitted) {
                alert(data.message);
                window.location.href = data.redirect_url;
                return;
            }
            
            // Otherwise check max violations
            const relevantCount = allowWarnings ? warningCount : violationCount;
            if (relevantCount >= maxViolations) {
                alert("You have exceeded the maximum allowed security violations. Your exam will be submitted automatically.");
                submitExam(true); // force submit
            }
        }
    })
    .catch(error => {
        console.error('Error logging security event:', error);
    });
}

// Show security warning modal
function showSecurityWarning() {
    warningCountEl.textContent = warningCount;
    const warningModal = new bootstrap.Modal(document.getElementById('securityWarningModal'));
    warningModal.show();
}

// Navigate to a specific question
function goToQuestion(index) {
    if (index < 0 || index >= totalQuestions) return;
    
    // Hide all slides
    questionSlides.forEach(slide => {
        slide.style.display = 'none';
    });
    
    // Show the selected slide
    questionSlides[index].style.display = 'block';
    
    // Update current question index
    currentQuestionIndex = index;
    currentQuestionNumEl.textContent = index + 1;
    
    // Update active state on buttons
    questionBtns.forEach(btn => {
        btn.classList.remove('active');
    });
    questionBtns[index].classList.add('active');
}

// Mark a question for review
function markForReview(questionId, isReviewed) {
    reviewQuestions[questionId] = isReviewed;
    updateQuestionButtons();
    
    fetch('mark_answer_for_review.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'attempt_id': <?php echo $attempt_id; ?>,
            'question_id': questionId,
            'mark_for_review': isReviewed ? 1 : 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to mark for review:', data.message);
        }
    })
    .catch(error => {
        console.error('Error marking for review:', error);
    });
}

// Update question navigation buttons
function updateQuestionButtons() {
    questionBtns.forEach(btn => {
        const index = parseInt(btn.dataset.questionIndex);
        const questionId = document.querySelector(`.question-slide[data-question-index="${index}"] input[name="question_ids[]"]`).value;
        
        btn.classList.remove('answered', 'review');
        
        if (reviewQuestions[questionId]) {
            btn.classList.add('review');
        } else if (answeredQuestions[questionId]) {
            btn.classList.add('answered');
        }
    });
}

// Check which questions have been answered
function checkAnsweredQuestions() {
    // Get all existing answers
    fetch('submit_answer.php?attempt_id=<?php echo $attempt_id; ?>', {
        method: 'GET'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.answers) {
            data.answers.forEach(answer => {
                answeredQuestions[answer.question_id] = true;
                
                if (answer.marked_for_review) {
                    reviewQuestions[answer.question_id] = true;
                }
            });
            
            updateQuestionButtons();
        }
    })
    .catch(error => {
        console.error('Error checking answered questions:', error);
    });
}

// Start the exam timer
function startTimer() {
    // Get the initial time remaining
    const initialTimeRemaining = <?php echo $exam['has_sections'] ? $current_section['time_remaining_seconds'] : ($exam['duration_minutes'] * 60); ?>;
    
    // Initialize the ExamTimer with options
    examTimer = new ExamTimer({
        timeRemainingSeconds: initialTimeRemaining,
        countdownElementId: 'timerDisplay',
        formId: 'examForm',
        warningThreshold: 600, // 10 minutes warning
        dangerThreshold: 300,  // 5 minutes danger
        updateUrl: 'section_action.php',
        sectionId: <?php echo $exam['has_sections'] ? $current_section['id'] : 'null'; ?>,
        onTimeExpired: function() {
            submitExam(true); // Force submit when time expires
        },
        onTimeWarning: function(timeRemaining) {
            // Show warning notification
            alert(`Warning: Only ${Math.floor(timeRemaining / 60)} minutes remaining!`);
        },
        onTimeDanger: function() {
            // You could play a sound or show a more urgent notification here
        },
        onTimeUpdate: function(timeRemaining) {
            // This function is called every second
            // We can use it to update our internal time tracking
            remainingTime = timeRemaining;
            
            // Save time remaining periodically
            if (timeRemaining % 30 === 0) {
                saveTimeRemaining();
            }
        }
    });
    
    // Start the timer
    examTimer.start();
}

// Save exam progress
function saveProgress(showMessage = false) {
    const formData = new FormData(examForm);
    
    fetch('submit_answer.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (showMessage) {
                alert('Progress saved successfully!');
            }
        } else {
            console.error('Failed to save progress:', data.message);
            if (showMessage) {
                alert('Failed to save progress. Please try again.');
            }
        }
    })
    .catch(error => {
        console.error('Error saving progress:', error);
        if (showMessage) {
            alert('Error saving progress. Please try again.');
        }
    });
}

// Show submit confirmation modal
function showSubmitConfirmation() {
    // Count answered/unanswered questions
    let answered = 0;
    let unanswered = 0;
    let reviewed = 0;
    
    for (let i = 0; i < totalQuestions; i++) {
        const questionId = document.querySelector(`.question-slide[data-question-index="${i}"] input[name="question_ids[]"]`).value;
        
        if (reviewQuestions[questionId]) {
            reviewed++;
        }
        
        if (answeredQuestions[questionId]) {
            answered++;
        } else {
            unanswered++;
        }
    }
    
    // Update summary text
    questionsStatusSummary.innerHTML = `
        <strong>Question Summary:</strong>
        <ul class="mb-0">
            <li>${answered} questions answered</li>
            <li>${unanswered} questions unanswered</li>
            ${reviewed > 0 ? `<li>${reviewed} questions marked for review</li>` : ''}
        </ul>
    `;
    
    // Show the modal
    const submitModal = new bootstrap.Modal(document.getElementById('submitConfirmModal'));
    submitModal.show();
}

// Submit the exam
function submitExam(force = false) {
    // Save progress first
    saveProgress();
    
    // Save remaining time
    saveTimeRemaining();
    
    // Submit the exam
    fetch('submit_exam.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'attempt_id': <?php echo $attempt_id; ?>,
            'force_submit': force ? 1 : 0,
            <?php if ($exam['has_sections']): ?>
            'section_id': <?php echo $current_section['id']; ?>,
            'complete_section': 1
            <?php endif; ?>
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Stop the timer
            if (examTimer) {
                examTimer.stop();
            }
            
            <?php if ($exam['has_sections']): ?>
            // If there are more sections, go to the next section
            if (data.next_section) {
                window.location.href = 'take_exam.php?id=<?php echo $exam_id; ?>';
            } else {
                // Otherwise go to results
                window.location.href = 'exam_result.php?id=<?php echo $exam_id; ?>';
            }
            <?php else: ?>
            // Go to results page
            window.location.href = 'exam_result.php?id=<?php echo $exam_id; ?>';
            <?php endif; ?>
        } else {
            alert('Failed to submit exam: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error submitting exam:', error);
        alert('Error submitting exam. Please try again.');
    });
}

// Before page unload, save progress and warn the user
window.addEventListener('beforeunload', function(e) {
    // Save progress
    saveProgress();
    
    // Save time remaining
    saveTimeRemaining();
    
    // Show warning to user
    const confirmationMessage = 'Are you sure you want to leave? Your progress has been saved, but the exam is not complete.';
    e.returnValue = confirmationMessage;
    return confirmationMessage;
});
</script>

<?php include '../includes/footer_minimal.php'; ?>