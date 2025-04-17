<?php
/**
 * Admin Grade Exam
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

// Get attempt details
$attempt = [];
$attempt_sql = "SELECT ea.*, e.title as exam_title, e.assessment_type,
                u.username as student_name, u.matric_number
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
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

// Check if specific answer ID is provided for grading
$specific_answer_id = isset($_GET['answer_id']) ? intval($_GET['answer_id']) : null;

// Get ungraded answers that need manual grading (short answer and essay)
$answers = [];
$answers_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.attempt_id = ? AND (a.is_graded = 0 OR ?) 
                AND (q.type = 'short_answer' OR q.type = 'essay')
                AND a.text_answer IS NOT NULL AND a.text_answer != ''";

if ($stmt = $conn->prepare($answers_sql)) {
    $include_all = ($specific_answer_id !== null) ? 1 : 0;
    $stmt->bind_param("ii", $attempt_id, $include_all);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // If we're looking for a specific answer, only include that one
        if ($specific_answer_id !== null && $row['id'] == $specific_answer_id) {
            $answers = [$row];
            break;
        } else if ($specific_answer_id === null) {
            $answers[] = $row;
        }
    }
    $stmt->close();
}

// Handle form submission for grading
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer_id = isset($_POST['answer_id']) ? intval($_POST['answer_id']) : 0;
    $score = isset($_POST['score']) ? floatval($_POST['score']) : 0;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    $max_points = isset($_POST['max_points']) ? floatval($_POST['max_points']) : 0;
    
    // Validate input
    $errors = [];
    if ($answer_id <= 0) {
        $errors[] = "Invalid answer ID.";
    }
    if ($score < 0) {
        $errors[] = "Score cannot be negative.";
    }
    if ($score > $max_points) {
        $errors[] = "Score cannot exceed maximum points ($max_points).";
    }
    
    // Check if answer exists and belongs to this attempt
    $answer_exists = false;
    $verify_sql = "SELECT 1 FROM answers WHERE id = ? AND attempt_id = ?";
    if ($stmt = $conn->prepare($verify_sql)) {
        $stmt->bind_param("ii", $answer_id, $attempt_id);
        $stmt->execute();
        $stmt->store_result();
        $answer_exists = ($stmt->num_rows > 0);
        $stmt->close();
    }
    
    if (!$answer_exists) {
        $errors[] = "Answer not found or does not belong to this attempt.";
    }
    
    // Update the answer if no errors
    if (empty($errors)) {
        $update_sql = "UPDATE answers SET score = ?, is_graded = 1, grader_feedback = ? WHERE id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("dsi", $score, $feedback, $answer_id);
            if ($stmt->execute()) {
                // Update the attempt's overall score
                updateAttemptScore($attempt_id, $conn);
                
                setFlashMessage('success', 'Answer graded successfully!');
                
                // Determine redirect destination
                if ($specific_answer_id !== null) {
                    // If grading a specific answer, go back to results page
                    redirect(SITE_URL . "/admin/view_results.php?attempt_id=$attempt_id");
                } else {
                    // Otherwise reload the page to grade the next answer
                    redirect(SITE_URL . "/admin/grade_exam.php?attempt_id=$attempt_id");
                }
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Set page title
$page_title = 'Grade Exam';
include '../includes/header.php';

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
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/results.php">Results</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/view_results.php?attempt_id=<?php echo $attempt_id; ?>">View Results</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Grade Exam</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-pen-fancy me-2"></i> Grade <?php echo ucfirst($attempt['assessment_type']); ?>
                </h1>
                <a href="<?php echo SITE_URL; ?>/admin/view_results.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Results
                </a>
            </div>
            <p class="lead">
                Manually grade essay and short answer questions for <?php echo htmlspecialchars($attempt['student_name']); ?> 
                on <?php echo htmlspecialchars($attempt['exam_title']); ?>.
            </p>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if (empty($answers)): ?>
        <div class="alert alert-info">
            <div class="d-flex">
                <div class="me-3">
                    <i class="fas fa-info-circle fa-2x"></i>
                </div>
                <div>
                    <h4 class="alert-heading">No Answers to Grade</h4>
                    <p class="mb-0">All answers for this attempt have been graded or there are no short answer/essay questions that require manual grading.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo SITE_URL; ?>/admin/view_results.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left me-1"></i> Return to Results
            </a>
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-8">
                <?php foreach ($answers as $index => $answer): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <?php if ($answer['question_type'] === 'short_answer'): ?>
                                    <span class="badge bg-info me-2">Short Answer</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary me-2">Essay</span>
                                <?php endif; ?>
                                Question <?php echo $index + 1; ?>
                            </h5>
                            <span class="badge bg-primary">
                                <?php echo number_format($answer['question_points'], 1); ?> points
                            </span>
                        </div>
                        <div class="card-body">
                            <h6 class="card-subtitle mb-3 text-muted">Question:</h6>
                            <div class="mb-4">
                                <?php echo nl2br(htmlspecialchars($answer['question_text'])); ?>
                            </div>
                            
                            <h6 class="card-subtitle mb-3 text-muted">Student's Answer:</h6>
                            <div class="p-3 bg-light rounded mb-4">
                                <?php 
                                // Check if answer contains an image reference
                                $text_answer = $answer['text_answer'];
                                $has_image = false;
                                $image_info = null;
                                
                                if (preg_match('/\[Image: (.+?)\]/', $text_answer, $matches)) {
                                    $has_image = true;
                                    $image_info = $matches[1];
                                    // Remove image tag from display text for cleaner viewing
                                    $text_answer = trim(str_replace("\n\n[Image: {$matches[1]}]", '', $text_answer));
                                } else {
                                    $text_answer = $answer['text_answer'];
                                }
                                
                                echo nl2br(htmlspecialchars($text_answer)); 
                                ?>
                                
                                <?php if ($has_image): ?>
                                <div class="mt-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">
                                                <i class="fas fa-image me-2"></i> Uploaded Image
                                            </h6>
                                        </div>
                                        <div class="card-body text-center">
                                            <img src="<?php echo SITE_URL; ?>/uploads/answers/<?php echo $attempt_id; ?>/<?php echo htmlspecialchars($image_info); ?>" 
                                                 class="img-fluid mb-2" style="max-height: 300px;" alt="Student uploaded image">
                                            <div>
                                                <a href="<?php echo SITE_URL; ?>/uploads/answers/<?php echo $attempt_id; ?>/<?php echo htmlspecialchars($image_info); ?>" 
                                                   target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-external-link-alt me-1"></i> View Full Size
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <form method="post" action="">
                                <input type="hidden" name="answer_id" value="<?php echo $answer['id']; ?>">
                                <input type="hidden" name="max_points" value="<?php echo $answer['question_points']; ?>">
                                
                                <div class="mb-3">
                                    <label for="score" class="form-label">Score (out of <?php echo number_format($answer['question_points'], 1); ?>):</label>
                                    <input type="number" class="form-control" id="score" name="score" 
                                           value="<?php echo $answer['is_graded'] ? number_format($answer['score'], 1) : ''; ?>" 
                                           min="0" max="<?php echo $answer['question_points']; ?>" step="0.1" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="feedback" class="form-label">Feedback:</label>
                                    <textarea class="form-control" id="feedback" name="feedback" rows="3"><?php echo htmlspecialchars($answer['grader_feedback'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-outline-secondary" onclick="quickScore(0)">
                                        0 Points
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="quickScore(<?php echo $answer['question_points'] / 2; ?>)">
                                        Half Credit
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="quickScore(<?php echo $answer['question_points']; ?>)">
                                        Full Credit
                                    </button>
                                </div>
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Save and Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="col-md-4">
                <div class="card shadow-sm mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header">
                        <h5 class="mb-0">Grading Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><?php echo ucfirst($attempt['assessment_type']); ?>:</h6>
                            <p class="mb-0"><?php echo htmlspecialchars($attempt['exam_title']); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Student:</h6>
                            <p class="mb-0">
                                <?php echo htmlspecialchars($attempt['student_name']); ?> 
                                (<?php echo htmlspecialchars($attempt['matric_number']); ?>)
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Date Taken:</h6>
                            <p class="mb-0"><?php echo formatDateTime($attempt['started_at']); ?></p>
                        </div>
                        
                        <hr>
                        
                        <h6>Grading Guidelines:</h6>
                        <ul class="small">
                            <li>Read the student's answer carefully.</li>
                            <li>Consider partial credit for partially correct answers.</li>
                            <li>Provide constructive feedback to help the student understand their score.</li>
                            <li>Be consistent in your grading across all students.</li>
                        </ul>
                        
                        <div class="alert alert-warning small">
                            <i class="fas fa-info-circle me-2"></i>
                            Once you grade an answer, the overall exam score will be automatically recalculated.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function quickScore(score) {
    document.getElementById('score').value = score.toFixed(1);
}
</script>

<?php include '../includes/footer.php'; ?>