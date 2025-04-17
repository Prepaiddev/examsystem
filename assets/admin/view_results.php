<?php
/**
 * Admin View Exam Results
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

// Get attempt details with course and exam info
$attempt = [];
$attempt_sql = "SELECT ea.*, e.title as exam_title, e.description as exam_description, 
               e.duration_minutes, e.passing_score, e.assessment_type,
               c.title as course_title, c.code as course_code,
               u.username as student_name, u.matric_number, u.email as student_email
               FROM exam_attempts ea
               JOIN exams e ON ea.exam_id = e.id
               LEFT JOIN courses c ON e.course_id = c.id
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

// Get all answers for this attempt with question details
$answers = [];
$answers_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points
                FROM answers a
                JOIN questions q ON a.question_id = q.id
                WHERE a.attempt_id = ?
                ORDER BY q.position";

if ($stmt = $conn->prepare($answers_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $answers[] = $row;
    }
    $stmt->close();
}

// Calculate statistics
$total_questions = count($answers);
$answered_questions = 0;
$correct_answers = 0;
$total_points = 0;
$earned_points = 0;
$pending_grading = 0;

foreach ($answers as $answer) {
    // Count points
    $total_points += $answer['question_points'];
    
    // Count answered questions
    if (!empty($answer['selected_choice_id']) || !empty($answer['text_answer'])) {
        $answered_questions++;
    }
    
    // Count answers pending grading
    if (!$answer['is_graded'] && 
        ($answer['question_type'] === 'short_answer' || $answer['question_type'] === 'essay') && 
        !empty($answer['text_answer'])) {
        $pending_grading++;
    }
    
    // Count correct answers and earned points
    if ($answer['is_graded']) {
        $earned_points += $answer['score'];
        
        // For multiple choice, check if answer is fully correct
        if ($answer['question_type'] === 'multiple_choice' && $answer['score'] >= $answer['question_points']) {
            $correct_answers++;
        }
    }
}

// Calculate percentages
$percentage_completed = $total_questions > 0 ? ($answered_questions / $total_questions) * 100 : 0;
$percentage_correct = $answered_questions > 0 ? ($correct_answers / $answered_questions) * 100 : 0;
$score_percentage = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;

// Format for display
$percentage_completed = number_format($percentage_completed, 1);
$percentage_correct = number_format($percentage_correct, 1);

// Set page title
$page_title = 'View Exam Results';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/results.php">Results</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Results</li>
                </ol>
            </nav>
            
            <div class="d-flex justify-content-between align-items-center">
                <h1 class="display-5 fw-bold">
                    <i class="fas fa-chart-bar me-2"></i> <?php echo ucfirst($attempt['assessment_type']); ?> Results
                </h1>
                <div>
                    <a href="<?php echo SITE_URL; ?>/admin/results.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Results
                    </a>
                    
                    <?php if ($pending_grading > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/grade_exam.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-warning ms-2">
                        <i class="fas fa-pen me-1"></i> Grade Exam
                    </a>
                    <?php endif; ?>
                    
                    <button class="btn btn-primary ms-2" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Results
                    </button>
                </div>
            </div>
            <p class="lead">
                Review results for <?php echo htmlspecialchars($attempt['student_name']); ?> on <?php echo htmlspecialchars($attempt['exam_title']); ?>
                <?php if (!empty($attempt['course_title'])): ?>
                    in <?php echo htmlspecialchars($attempt['course_code'] . ' - ' . $attempt['course_title']); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Results Summary Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><?php echo ucfirst($attempt['assessment_type']); ?> Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <table class="table table-sm">
                                <tbody>
                                    <tr>
                                        <th>Student:</th>
                                        <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?php echo htmlspecialchars($attempt['student_email']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Matric Number:</th>
                                        <td><?php echo htmlspecialchars($attempt['matric_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date Taken:</th>
                                        <td><?php echo formatDateTime($attempt['started_at']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Date Completed:</th>
                                        <td><?php echo !empty($attempt['completed_at']) ? formatDateTime($attempt['completed_at']) : 'Not completed'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Assessment Type:</th>
                                        <td><span class="badge bg-info"><?php echo ucfirst($attempt['assessment_type']); ?></span></td>
                                    </tr>
                                    <?php if ($pending_grading > 0): ?>
                                    <tr>
                                        <th>Grading Status:</th>
                                        <td>
                                            <span class="badge bg-warning">
                                                <?php echo $pending_grading; ?> items need grading
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-lg-6">
                            <div class="text-center">
                                <h2 class="display-1 mb-0 <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo number_format($attempt['score'], 1); ?>%
                                </h2>
                                <p class="mb-2">Overall Score</p>
                                
                                <div class="badge bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?> fs-6 mb-3">
                                    <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                                </div>
                                
                                <div class="progress mb-2" style="height: 20px;">
                                    <div class="progress-bar bg-<?php echo getProgressBarColor($attempt['score']); ?>" 
                                         role="progressbar" style="width: <?php echo $attempt['score']; ?>%;" 
                                         aria-valuenow="<?php echo $attempt['score']; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo number_format($attempt['score'], 1); ?>%
                                    </div>
                                </div>
                                <small class="text-muted">Passing score: <?php echo $attempt['passing_score']; ?>%</small>
                                
                                <div class="mt-3">
                                    <div class="fs-4"><?php echo calculateGrade($attempt['score']); ?></div>
                                    <small class="text-muted">Equivalent Grade</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Performance Statistics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Performance Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <div class="display-6"><?php echo $answered_questions; ?> / <?php echo $total_questions; ?></div>
                            <p class="text-muted mb-0">Questions Answered</p>
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar bg-info" role="progressbar" 
                                     style="width: <?php echo $percentage_completed; ?>%;" 
                                     aria-valuenow="<?php echo $percentage_completed; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted"><?php echo $percentage_completed; ?>% Completion</small>
                        </div>
                        
                        <div class="col-md-4 text-center mb-3">
                            <div class="display-6"><?php echo $correct_answers; ?> / <?php echo $answered_questions; ?></div>
                            <p class="text-muted mb-0">Correct Answers</p>
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar bg-success" role="progressbar" 
                                     style="width: <?php echo $percentage_correct; ?>%;" 
                                     aria-valuenow="<?php echo $percentage_correct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted"><?php echo $percentage_correct; ?>% Accuracy</small>
                        </div>
                        
                        <div class="col-md-4 text-center mb-3">
                            <div class="display-6"><?php echo number_format($earned_points, 1); ?> / <?php echo $total_points; ?></div>
                            <p class="text-muted mb-0">Points Earned</p>
                            <div class="progress mt-2" style="height: 10px;">
                                <div class="progress-bar bg-<?php echo getProgressBarColor($score_percentage); ?>" role="progressbar" 
                                     style="width: <?php echo $score_percentage; ?>%;" 
                                     aria-valuenow="<?php echo $score_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <small class="text-muted"><?php echo number_format($score_percentage, 1); ?>% of Total Points</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Answers -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Detailed Answers</h5>
                    
                    <?php if ($pending_grading > 0): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/grade_exam.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-pen me-1"></i> Grade Pending Items
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Student Answer</th>
                                    <th>Score</th>
                                    <th class="text-center">Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($answers as $index => $answer): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars(truncateText($answer['question_text'], 80)); ?></td>
                                        <td>
                                            <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                                <span class="badge bg-primary">Multiple Choice</span>
                                            <?php elseif ($answer['question_type'] === 'short_answer'): ?>
                                                <span class="badge bg-info">Short Answer</span>
                                            <?php elseif ($answer['question_type'] === 'essay'): ?>
                                                <span class="badge bg-secondary">Essay</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                                <?php
                                                // Get selected choice text
                                                $choice_text = 'Not answered';
                                                $is_correct = false;
                                                if (!empty($answer['selected_choice_id'])) {
                                                    $choice_sql = "SELECT text, is_correct FROM choices WHERE id = ?";
                                                    if ($stmt = $conn->prepare($choice_sql)) {
                                                        $stmt->bind_param("i", $answer['selected_choice_id']);
                                                        $stmt->execute();
                                                        $stmt->bind_result($text, $is_correct);
                                                        if ($stmt->fetch()) {
                                                            $choice_text = htmlspecialchars(truncateText($text, 50));
                                                        }
                                                        $stmt->close();
                                                    }
                                                }
                                                echo $choice_text;
                                                
                                                // Show correct choice for reference
                                                if (!empty($answer['selected_choice_id']) && !$is_correct) {
                                                    $correct_sql = "SELECT text FROM choices WHERE question_id = ? AND is_correct = 1 LIMIT 1";
                                                    if ($stmt = $conn->prepare($correct_sql)) {
                                                        $stmt->bind_param("i", $answer['question_id']);
                                                        $stmt->execute();
                                                        $stmt->bind_result($correct_text);
                                                        if ($stmt->fetch()) {
                                                            echo '<div class="text-success mt-1"><small><i class="fas fa-check me-1"></i>';
                                                            echo htmlspecialchars(truncateText($correct_text, 50));
                                                            echo '</small></div>';
                                                        }
                                                        $stmt->close();
                                                    }
                                                }
                                                ?>
                                            <?php else: ?>
                                                <?php if (!empty($answer['text_answer'])): ?>
                                                    <?php echo htmlspecialchars(truncateText($answer['text_answer'], 50)); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not answered</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($answer['is_graded']): ?>
                                                <?php echo number_format($answer['score'], 1); ?> / <?php echo $answer['question_points']; ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not graded</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($answer['is_graded']): ?>
                                                <?php if ($answer['score'] >= $answer['question_points']): ?>
                                                    <span class="badge bg-success">Correct</span>
                                                <?php elseif ($answer['score'] > 0): ?>
                                                    <span class="badge bg-warning">Partial</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Incorrect</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                                    <span class="badge bg-success">Auto-graded</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Needs Grading</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!$answer['is_graded'] && 
                                                      ($answer['question_type'] === 'short_answer' || $answer['question_type'] === 'essay') && 
                                                      !empty($answer['text_answer'])): ?>
                                                <a href="<?php echo SITE_URL; ?>/admin/grade_exam.php?attempt_id=<?php echo $attempt_id; ?>&answer_id=<?php echo $answer['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-pen"></i> Grade
                                                </a>
                                            <?php elseif ($answer['is_graded']): ?>
                                                <button class="btn btn-sm btn-outline-secondary" 
                                                        data-bs-toggle="tooltip" data-bs-placement="top" 
                                                        title="<?php echo htmlspecialchars($answer['grader_feedback'] ?? 'No feedback provided'); ?>">
                                                    <i class="fas fa-comment-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Time Analysis -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Time Analysis</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Calculate time spent
                    $time_spent = 'N/A';
                    $time_spent_minutes = 0;
                    
                    if (!empty($attempt['completed_at'])) {
                        $start_time = new DateTime($attempt['started_at']);
                        $end_time = new DateTime($attempt['completed_at']);
                        $interval = $start_time->diff($end_time);
                        
                        $time_spent_minutes = ($interval->h * 60) + $interval->i;
                        $time_spent = $interval->format('%H:%I:%S');
                        
                        // Calculate percentage of allotted time used
                        $time_percentage = min(100, ($time_spent_minutes / $attempt['duration_minutes']) * 100);
                    }
                    ?>
                    
                    <div class="text-center mb-3">
                        <div class="display-6"><?php echo $time_spent; ?></div>
                        <p class="text-muted mb-0">Time Spent</p>
                    </div>
                    
                    <?php if (!empty($attempt['completed_at'])): ?>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: <?php echo $time_percentage; ?>%;" 
                                 aria-valuenow="<?php echo $time_percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <small class="text-muted d-block text-center mb-3">
                            <?php echo $time_spent_minutes; ?> of <?php echo $attempt['duration_minutes']; ?> allotted minutes (<?php echo number_format($time_percentage, 1); ?>%)
                        </small>
                        
                        <?php if ($time_spent_minutes < $attempt['duration_minutes']): ?>
                            <div class="alert alert-success mb-0">
                                <i class="fas fa-clock me-2"></i>
                                Completed <?php echo number_format(($attempt['duration_minutes'] - $time_spent_minutes), 1); ?> minutes early
                            </div>
                        <?php elseif ($time_spent_minutes > $attempt['duration_minutes']): ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Exceeded time limit by <?php echo number_format(($time_spent_minutes - $attempt['duration_minutes']), 1); ?> minutes
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-check-circle me-2"></i>
                                Used exactly allotted time
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Exam not completed
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Security Log Summary -->
            <?php if (!empty($attempt['security_log'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Security Events</h5>
                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#securityLogDetails">
                        <i class="fas fa-eye"></i> View All
                    </button>
                </div>
                <div class="card-body">
                    <?php 
                    $security_events = json_decode($attempt['security_log'], true); 
                    $total_events = count($security_events);
                    $violations = 0;
                    $warnings = 0;
                    
                    foreach ($security_events as $event) {
                        if (isset($event['is_violation']) && $event['is_violation']) {
                            $violations++;
                        } else {
                            $warnings++;
                        }
                    }
                    ?>
                    
                    <div class="row mb-3">
                        <div class="col-6 text-center">
                            <div class="display-6 <?php echo $violations > 0 ? 'text-danger' : 'text-muted'; ?>">
                                <?php echo $violations; ?>
                            </div>
                            <p class="text-muted mb-0">Violations</p>
                        </div>
                        <div class="col-6 text-center">
                            <div class="display-6 <?php echo $warnings > 0 ? 'text-warning' : 'text-muted'; ?>">
                                <?php echo $warnings; ?>
                            </div>
                            <p class="text-muted mb-0">Warnings</p>
                        </div>
                    </div>
                    
                    <?php if ($violations > 0 || $warnings > 0): ?>
                        <div class="collapse" id="securityLogDetails">
                            <div class="list-group list-group-flush">
                                <?php foreach ($security_events as $event): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <?php 
                                                $event_name = isset($event['type']) ? $event['type'] : 'unknown';
                                                $event_name = str_replace('_', ' ', $event_name);
                                                echo ucwords($event_name); 
                                                ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php 
                                                if (isset($event['timestamp'])) {
                                                    echo date('H:i:s', strtotime($event['timestamp']));
                                                }
                                                ?>
                                            </small>
                                        </div>
                                        <?php if (isset($event['is_violation']) && $event['is_violation']): ?>
                                            <span class="badge bg-danger">Violation</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Warning</span>
                                        <?php endif; ?>
                                        
                                        <?php if (isset($event['reason'])): ?>
                                            <div class="mt-1 small text-muted">
                                                <?php echo htmlspecialchars($event['reason']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-shield-alt me-2"></i>
                            No security events recorded
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Grading Actions -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Grading Actions</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>/admin/export_results.php?attempt_id=<?php echo $attempt_id; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-file-export me-2"></i> Export Results</h6>
                                <small><i class="fas fa-chevron-right"></i></small>
                            </div>
                            <p class="mb-1 small text-muted">Download as CSV or PDF</p>
                        </a>
                        
                        <a href="<?php echo SITE_URL; ?>/admin/email_results.php?attempt_id=<?php echo $attempt_id; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-envelope me-2"></i> Email Results</h6>
                                <small><i class="fas fa-chevron-right"></i></small>
                            </div>
                            <p class="mb-1 small text-muted">Send results to student</p>
                        </a>
                        
                        <?php if ($pending_grading > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/grade_exam.php?attempt_id=<?php echo $attempt_id; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-pen me-2"></i> Grade Pending Items</h6>
                                <small class="badge bg-warning rounded-pill"><?php echo $pending_grading; ?></small>
                            </div>
                            <p class="mb-1 small text-muted">Manual grading required</p>
                        </a>
                        <?php endif; ?>
                        
                        <a href="#" class="list-group-item list-group-item-action" onclick="resetGrading(<?php echo $attempt_id; ?>); return false;">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="fas fa-redo-alt me-2"></i> Reset Grading</h6>
                                <small><i class="fas fa-chevron-right"></i></small>
                            </div>
                            <p class="mb-1 small text-muted">Clear all manual grades</p>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

// Reset grading function
function resetGrading(attemptId) {
    if (confirm('Are you sure you want to reset all manual grading for this attempt? This action cannot be undone.')) {
        window.location.href = '<?php echo SITE_URL; ?>/admin/reset_grading.php?attempt_id=' + attemptId;
    }
}
</script>

<style>
@media print {
    header, footer, .breadcrumb, .btn, nav, button, .actions {
        display: none !important;
    }
    
    body {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
        break-inside: avoid;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .container {
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .collapse {
        display: block !important;
    }
}
</style>

<?php
// Helper function to determine progress bar color
function getProgressBarColor($percentage) {
    if ($percentage >= 80) {
        return 'success';
    } elseif ($percentage >= 60) {
        return 'info';
    } elseif ($percentage >= 40) {
        return 'warning';
    } else {
        return 'danger';
    }
}

include '../includes/footer.php';
?>