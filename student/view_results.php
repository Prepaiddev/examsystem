<?php
/**
 * Student View Exam Results
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    // Not logged in or not a student, redirect to login page
    setFlashMessage('error', 'You must be logged in as a student to view your results.');
    redirect(SITE_URL . '/login.php');
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Check if attempt ID is provided
if (!isset($_GET['attempt_id']) || !is_numeric($_GET['attempt_id'])) {
    setFlashMessage('error', 'Invalid attempt ID.');
    redirect(SITE_URL . '/student/results.php');
}

$attempt_id = intval($_GET['attempt_id']);

// Get attempt details with course and exam info
$attempt = [];
$attempt_sql = "SELECT ea.*, e.title as exam_title, e.description as exam_description, 
               e.duration_minutes, e.passing_score, e.assessment_type,
               c.title as course_title, c.code as course_code,
               u.username as student_name, u.matric_number
               FROM exam_attempts ea
               JOIN exams e ON ea.exam_id = e.id
               LEFT JOIN courses c ON e.course_id = c.id
               JOIN users u ON ea.student_id = u.id
               WHERE ea.id = ? AND ea.student_id = ?";

if ($stmt = $conn->prepare($attempt_sql)) {
    $stmt->bind_param("ii", $attempt_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt = fetchRowSafely($result);
    $stmt->close();
}

// If attempt not found or doesn't belong to current user, redirect
if (empty($attempt)) {
    setFlashMessage('error', 'Exam attempt not found or not authorized to view.');
    redirect(SITE_URL . '/student/results.php');
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

foreach ($answers as $answer) {
    // Count points
    $total_points += $answer['question_points'];
    
    // Count answered questions
    if (!empty($answer['selected_choice_id']) || !empty($answer['text_answer'])) {
        $answered_questions++;
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
$page_title = 'Exam Results';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/results.php">My Results</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Results</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-chart-bar me-2"></i> <?php echo ucfirst($attempt['assessment_type']); ?> Results
            </h1>
            <p class="lead">
                Review your performance on <?php echo htmlspecialchars($attempt['exam_title']); ?>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo ucfirst($attempt['assessment_type']); ?> Details</h5>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/student/results.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Results
                        </a>
                        <button class="btn btn-primary btn-sm ms-2" onclick="window.print()">
                            <i class="fas fa-print me-1"></i> Print Results
                        </button>
                    </div>
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
                                        <th>Duration:</th>
                                        <td><?php echo formatDuration($attempt['duration_minutes']); ?></td>
                                    </tr>
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
                <div class="card-header">
                    <h5 class="mb-0">Detailed Answers</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Question</th>
                                    <th>Type</th>
                                    <th>Your Answer</th>
                                    <th>Score</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($answers as $index => $answer): ?>
                                    <tr>
                                        <td>
                                            <strong>Q<?php echo $index + 1; ?>:</strong> 
                                            <?php echo htmlspecialchars(truncateText($answer['question_text'], 80)); ?>
                                        </td>
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
                                                ?>
                                            <?php else: ?>
                                                <?php if (!empty($answer['text_answer'])): ?>
                                                    <?php 
                                                    // Check if answer contains an image reference
                                                    $text_answer = $answer['text_answer'];
                                                    $has_image = false;
                                                    
                                                    if (preg_match('/\[Image: (.+?)\]/', $text_answer, $matches)) {
                                                        $has_image = true;
                                                        // Remove image tag from display text for cleaner viewing
                                                        $text_answer = trim(str_replace("\n\n[Image: {$matches[1]}]", '', $text_answer));
                                                        echo htmlspecialchars(truncateText($text_answer, 50));
                                                        echo ' <i class="fas fa-image text-info" title="Includes uploaded image"></i>';
                                                    } else {
                                                        echo htmlspecialchars(truncateText($text_answer, 50));
                                                    }
                                                    ?>
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
                                                <span class="badge bg-secondary">Pending</span>
                                            <?php endif; ?>
                                            <a href="<?php echo SITE_URL; ?>/student/view_answer.php?id=<?php echo $answer['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary ms-2" title="View detailed answer">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php if (!empty($answer['grader_feedback'])): ?>
                                    <tr class="table-light">
                                        <td colspan="5">
                                            <small class="text-muted">
                                                <strong>Feedback:</strong> <?php echo htmlspecialchars($answer['grader_feedback']); ?>
                                            </small>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Final Grade Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Final Grade</h5>
                </div>
                <div class="card-body text-center">
                    <div class="display-1 fw-bold mb-3"><?php echo calculateGrade($attempt['score']); ?></div>
                    <div class="text-muted">Equivalent Grade</div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <div class="mb-1">Starting Time</div>
                            <div class="fw-bold"><?php echo formatDateTime($attempt['started_at']); ?></div>
                        </div>
                        <div>
                            <div class="mb-1">Completion Time</div>
                            <div class="fw-bold">
                                <?php echo !empty($attempt['completed_at']) ? formatDateTime($attempt['completed_at']) : 'Not completed'; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security Log Summary -->
            <?php if (!empty($attempt['security_log'])): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Security Events</h5>
                </div>
                <div class="card-body">
                    <?php 
                    $security_events = json_decode($attempt['security_log'], true); 
                    $total_events = count($security_events);
                    $violations = 0;
                    
                    foreach ($security_events as $event) {
                        if (isset($event['is_violation']) && $event['is_violation']) {
                            $violations++;
                        }
                    }
                    ?>
                    
                    <div class="text-center mb-3">
                        <div class="display-6"><?php echo $violations; ?></div>
                        <p class="text-muted mb-0">Security Violations</p>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <?php 
                        // Show last 5 events
                        $events_to_show = array_slice($security_events, -5);
                        foreach ($events_to_show as $event): 
                        ?>
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
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_events > 5): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">Showing 5 of <?php echo $total_events; ?> events</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Recommendations -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recommendations</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php if ($percentage_completed < 100): ?>
                            <li class="list-group-item px-0">
                                <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                Complete all questions in future assessments
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($score_percentage < 70): ?>
                            <li class="list-group-item px-0">
                                <i class="fas fa-book text-primary me-2"></i>
                                Review course materials and notes
                            </li>
                            
                            <li class="list-group-item px-0">
                                <i class="fas fa-users text-info me-2"></i>
                                Consider forming a study group
                            </li>
                            
                            <li class="list-group-item px-0">
                                <i class="fas fa-chalkboard-teacher text-success me-2"></i>
                                Schedule time with your instructor for review
                            </li>
                        <?php else: ?>
                            <li class="list-group-item px-0">
                                <i class="fas fa-thumbs-up text-success me-2"></i>
                                Great job! Keep up the good work.
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($violations > 0): ?>
                            <li class="list-group-item px-0">
                                <i class="fas fa-shield-alt text-danger me-2"></i>
                                Avoid switching tabs/windows during future exams
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    header, footer, .breadcrumb, .btn, nav {
        display: none !important;
    }
    
    body {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
    }
    
    .container {
        width: 100% !important;
        max-width: 100% !important;
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