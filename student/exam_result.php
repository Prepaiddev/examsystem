<?php
/**
 * Student Exam Result Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    // Not logged in or not a student, redirect to login page
    setFlashMessage('error', 'You must be logged in as a student to view this page.');
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

// Get attempt ID if provided, otherwise get the latest attempt
$attempt_id = null;
if (isset($_GET['attempt']) && is_numeric($_GET['attempt'])) {
    $attempt_id = intval($_GET['attempt']);
}

// Get attempt details
$attempt = null;
if ($attempt_id) {
    $attempt_sql = "SELECT * FROM exam_attempts 
                   WHERE id = ? AND student_id = ? AND exam_id = ? AND completed_at IS NOT NULL";
    if ($stmt = $conn->prepare($attempt_sql)) {
        $stmt->bind_param("iii", $attempt_id, $user_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempt = fetchRowSafely($result);
        $stmt->close();
    }
} else {
    // Get the latest completed attempt
    $latest_sql = "SELECT * FROM exam_attempts 
                  WHERE student_id = ? AND exam_id = ? AND completed_at IS NOT NULL
                  ORDER BY completed_at DESC LIMIT 1";
    if ($stmt = $conn->prepare($latest_sql)) {
        $stmt->bind_param("ii", $user_id, $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempt = fetchRowSafely($result);
        $stmt->close();
    }
}

// If no attempt found, redirect
if (empty($attempt)) {
    setFlashMessage('error', 'No completed exam attempt found.');
    redirect(SITE_URL . '/student/exams.php');
}

// Get exam details
$exam = [];
$exam_sql = "SELECT e.*, c.title as course_title, c.code as course_code, c.id as course_id
             FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             WHERE e.id = ?";

if ($stmt = $conn->prepare($exam_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = fetchRowSafely($result);
    $stmt->close();
}

// If exam not found, redirect
if (empty($exam)) {
    setFlashMessage('error', 'Exam not found.');
    redirect(SITE_URL . '/student/exams.php');
}

// Get all answers for this attempt
$answers = [];
$answers_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points,
               (SELECT text FROM choices WHERE id = a.selected_choice_id) as selected_choice_text,
               (SELECT GROUP_CONCAT(CONCAT(id, ':', text, ':', is_correct) SEPARATOR '|') 
                FROM choices WHERE question_id = q.id) as choices_data
               FROM answers a
               JOIN questions q ON a.question_id = q.id
               WHERE a.attempt_id = ?
               ORDER BY q.position";

if ($stmt = $conn->prepare($answers_sql)) {
    $stmt->bind_param("i", $attempt['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        // Parse choices data
        $row['choices'] = [];
        if (!empty($row['choices_data'])) {
            $choices_parts = explode('|', $row['choices_data']);
            foreach ($choices_parts as $choice_part) {
                $choice_data = explode(':', $choice_part, 3);
                if (count($choice_data) === 3) {
                    $row['choices'][] = [
                        'id' => $choice_data[0],
                        'text' => $choice_data[1],
                        'is_correct' => (bool)$choice_data[2]
                    ];
                }
            }
        }
        unset($row['choices_data']);
        
        $answers[] = $row;
    }
    
    $stmt->close();
}

// Calculate statistics
$stats = [
    'total_questions' => count($answers),
    'correct_answers' => 0,
    'incorrect_answers' => 0,
    'total_points' => 0,
    'earned_points' => 0,
    'ungraded_questions' => 0
];

foreach ($answers as $answer) {
    $stats['total_points'] += $answer['question_points'];
    
    if ($answer['is_graded']) {
        $stats['earned_points'] += $answer['score'];
        
        // For multiple choice, count correct/incorrect
        if ($answer['question_type'] === 'multiple_choice') {
            if ($answer['score'] > 0) {
                $stats['correct_answers']++;
            } else {
                $stats['incorrect_answers']++;
            }
        }
    } else {
        $stats['ungraded_questions']++;
    }
}

// Update last active timestamp
$update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Set page title
$page_title = 'Exam Results: ' . $exam['title'];

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <!-- Results Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/exams.php">Exams</a></li>
                    <?php if ($exam['course_id']): ?>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $exam['course_id']; ?>"><?php echo htmlspecialchars($exam['course_code']); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/exam_detail.php?id=<?php echo $exam_id; ?>"><?php echo htmlspecialchars($exam['title']); ?></a></li>
                    <li class="breadcrumb-item active">Results</li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold">
                <i class="fas fa-clipboard-check me-2"></i> Exam Results
            </h1>
            <p class="lead">
                <?php echo htmlspecialchars($exam['title']); ?>
                <?php if ($exam['course_title']): ?>
                    | Course: <?php echo htmlspecialchars($exam['course_title']); ?>
                <?php endif; ?>
            </p>
        </div>
    </div>
    
    <!-- Results Summary -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Performance Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-center mb-3 mb-md-0">
                            <?php if ($attempt['is_graded']): ?>
                                <div class="score-circle mx-auto <?php echo $attempt['passed'] ? 'score-pass' : 'score-fail'; ?>">
                                    <div class="score-value"><?php echo number_format($attempt['score'], 1); ?>%</div>
                                    <div class="score-label">
                                        <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="fw-bold"><?php echo calculateGrade($attempt['score']); ?></div>
                                    <small class="text-muted">
                                        Required: <?php echo $exam['passing_score']; ?>% to pass
                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="score-circle mx-auto score-pending">
                                    <div class="score-value"><i class="fas fa-hourglass-half"></i></div>
                                    <div class="score-label">PENDING</div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Your exam is being graded
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">Date Completed</div>
                                    <div class="fw-bold"><?php echo formatDate($attempt['completed_at'], 'M j, Y g:i A'); ?></div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">Time Spent</div>
                                    <div class="fw-bold">
                                        <?php
                                        $start = new DateTime($attempt['started_at']);
                                        $end = new DateTime($attempt['completed_at']);
                                        $interval = $start->diff($end);
                                        $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                                        echo formatDuration($minutes);
                                        ?>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">Questions</div>
                                    <div class="fw-bold"><?php echo $stats['total_questions']; ?> total</div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="small text-muted">Points</div>
                                    <div class="fw-bold">
                                        <?php echo $stats['earned_points']; ?> / <?php echo $stats['total_points']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if ($stats['ungraded_questions'] === 0 && $stats['total_questions'] > 0): ?>
                                <div class="progress mb-2" style="height: 20px;">
                                    <?php if ($stats['correct_answers'] > 0): ?>
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo ($stats['correct_answers'] / $stats['total_questions'] * 100); ?>%" 
                                             aria-valuenow="<?php echo $stats['correct_answers']; ?>" 
                                             aria-valuemin="0" aria-valuemax="<?php echo $stats['total_questions']; ?>">
                                            <?php echo $stats['correct_answers']; ?> Correct
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($stats['incorrect_answers'] > 0): ?>
                                        <div class="progress-bar bg-danger" role="progressbar" 
                                             style="width: <?php echo ($stats['incorrect_answers'] / $stats['total_questions'] * 100); ?>%" 
                                             aria-valuenow="<?php echo $stats['incorrect_answers']; ?>" 
                                             aria-valuemin="0" aria-valuemax="<?php echo $stats['total_questions']; ?>">
                                            <?php echo $stats['incorrect_answers']; ?> Incorrect
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($stats['ungraded_questions'] > 0): ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $stats['ungraded_questions']; ?> questions are still being graded.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Answers Review -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i> Answer Review</h5>
                    
                    <?php if ($attempt['is_graded']): ?>
                        <span class="badge bg-success">Graded</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">Grading in Progress</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($answers)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No answers found for this attempt.
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="answersAccordion">
                            <?php foreach($answers as $index => $answer): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo $index > 0 ? 'collapsed' : ''; ?>" type="button" 
                                                data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" 
                                                aria-expanded="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                                aria-controls="collapse<?php echo $index; ?>">
                                            
                                            <div class="d-flex justify-content-between align-items-center w-100 pe-3">
                                                <div>
                                                    <span class="badge bg-secondary me-2">Q<?php echo $index + 1; ?></span>
                                                    <?php echo htmlspecialchars(truncateText(strip_tags($answer['question_text']), 100)); ?>
                                                </div>
                                                
                                                <?php if ($answer['is_graded']): ?>
                                                    <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                                        <?php if ($answer['score'] > 0): ?>
                                                            <span class="badge bg-success">Correct</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Incorrect</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?php echo $answer['score']; ?>/<?php echo $answer['question_points']; ?> pts</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo $index === 0 ? 'show' : ''; ?>" 
                                         aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#answersAccordion">
                                        <div class="accordion-body">
                                            <div class="mb-3">
                                                <h6>Question:</h6>
                                                <div class="question-text card p-3 bg-light">
                                                    <?php echo nl2br(htmlspecialchars($answer['question_text'])); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <h6>Your Answer:</h6>
                                                <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                                    <ul class="list-group">
                                                        <?php foreach($answer['choices'] as $choice): ?>
                                                            <?php
                                                                $list_class = '';
                                                                $icon = '';
                                                                
                                                                if ($choice['id'] == $answer['selected_choice_id']) {
                                                                    if ($choice['is_correct']) {
                                                                        $list_class = 'list-group-item-success';
                                                                        $icon = '<i class="fas fa-check-circle text-success me-2"></i>';
                                                                    } else {
                                                                        $list_class = 'list-group-item-danger';
                                                                        $icon = '<i class="fas fa-times-circle text-danger me-2"></i>';
                                                                    }
                                                                } elseif ($choice['is_correct']) {
                                                                    $list_class = 'border-success';
                                                                    $icon = '<i class="fas fa-check text-success me-2"></i>';
                                                                }
                                                            ?>
                                                            <li class="list-group-item <?php echo $list_class; ?>">
                                                                <?php echo $icon; ?>
                                                                <?php echo htmlspecialchars($choice['text']); ?>
                                                                <?php if ($choice['is_correct']): ?>
                                                                    <span class="badge bg-success ms-2">Correct</span>
                                                                <?php endif; ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <div class="card p-3">
                                                        <?php if (!empty($answer['text_answer'])): ?>
                                                            <?php echo nl2br(htmlspecialchars($answer['text_answer'])); ?>
                                                        <?php else: ?>
                                                            <em class="text-muted">No answer provided</em>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($answer['is_graded'] && $answer['grader_feedback']): ?>
                                                <div class="mb-3">
                                                    <h6>Feedback:</h6>
                                                    <div class="alert alert-info mb-0">
                                                        <?php echo nl2br(htmlspecialchars($answer['grader_feedback'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="small text-muted text-end">
                                                <?php if ($answer['is_graded']): ?>
                                                    Score: <?php echo $answer['score']; ?> of <?php echo $answer['question_points']; ?> points
                                                <?php else: ?>
                                                    Grading in progress
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Performance Insights -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-lightbulb me-2"></i> Insights</h5>
                </div>
                <div class="card-body">
                    <?php if (!$attempt['is_graded']): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Performance insights will be available once your exam is fully graded.
                        </div>
                    <?php elseif ($stats['ungraded_questions'] > 0): ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Complete insights will be available once all questions are graded.
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Exam Status</strong>
                                    <span class="badge <?php echo $attempt['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Final Score</strong>
                                    <span><?php echo number_format($attempt['score'], 1); ?>%</span>
                                </div>
                            </div>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong>Letter Grade</strong>
                                    <span><?php echo calculateGrade($attempt['score']); ?></span>
                                </div>
                            </div>
                            
                            <?php if ($stats['total_questions'] > 0): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong>Accuracy</strong>
                                        <span><?php echo number_format(($stats['correct_answers'] / max(1, $stats['correct_answers'] + $stats['incorrect_answers'])) * 100, 1); ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo ($stats['correct_answers'] / max(1, $stats['correct_answers'] + $stats['incorrect_answers'])) * 100; ?>%" 
                                             aria-valuenow="<?php echo $stats['correct_answers']; ?>" 
                                             aria-valuemin="0" aria-valuemax="<?php echo $stats['correct_answers'] + $stats['incorrect_answers']; ?>"></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center mt-3">
                            <?php if ($attempt['passed']): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-trophy me-2"></i>
                                    Congratulations on passing this exam!
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    You did not meet the passing score of <?php echo $exam['passing_score']; ?>%.
                                    <hr>
                                    <div class="d-grid">
                                        <a href="<?php echo SITE_URL; ?>/student/exam_detail.php?id=<?php echo $exam_id; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-redo-alt me-1"></i> Try Exam Again
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Security Log -->
            <?php if ($attempt['security_violations'] > 0 || $attempt['security_warnings'] > 0): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i> Security Report</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush mb-0">
                            <?php if ($attempt['security_violations'] > 0): ?>
                                <li class="list-group-item text-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong><?php echo $attempt['security_violations']; ?> security violations</strong> were detected during this exam.
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($attempt['security_warnings'] > 0): ?>
                                <li class="list-group-item text-warning">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <strong><?php echo $attempt['security_warnings']; ?> security warnings</strong> were issued during this exam.
                                </li>
                            <?php endif; ?>
                            
                            <li class="list-group-item small text-muted">
                                Security events may include tab switching, going out of focus, or other browser activities that could compromise exam integrity.
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Other Attempts -->
            <?php
            // Get other attempts for this exam
            $other_attempts = [];
            $other_sql = "SELECT * FROM exam_attempts 
                        WHERE student_id = ? AND exam_id = ? AND id != ? AND completed_at IS NOT NULL
                        ORDER BY completed_at DESC";
            if ($stmt = $conn->prepare($other_sql)) {
                $stmt->bind_param("iii", $user_id, $exam_id, $attempt['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $other_attempts[] = $row;
                }
                
                $stmt->close();
            }
            
            if (!empty($other_attempts)):
            ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Other Attempts</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach($other_attempts as $other): ?>
                                <a href="<?php echo SITE_URL; ?>/student/exam_result.php?id=<?php echo $exam_id; ?>&attempt=<?php echo $other['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <i class="fas fa-calendar-day me-1"></i>
                                            <?php echo formatDate($other['completed_at'], 'M j, Y'); ?>
                                        </div>
                                        <?php if ($other['is_graded']): ?>
                                            <span class="badge <?php echo $other['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo number_format($other['score'], 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="<?php echo SITE_URL; ?>/student/exam_detail.php?id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-1"></i> Back to Exam
            </a>
            
            <?php if ($exam['course_id']): ?>
                <a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $exam['course_id']; ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-book me-1"></i> Back to Course
                </a>
            <?php endif; ?>
            
            <a href="<?php echo SITE_URL; ?>/student/results.php" class="btn btn-outline-secondary">
                <i class="fas fa-chart-line me-1"></i> All Results
            </a>
        </div>
    </div>
</div>

<style>
/* Score Circle Styles */
.score-circle {
    position: relative;
    width: 120px;
    height: 120px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    margin-bottom: 10px;
    color: white;
}

.score-pass {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.score-fail {
    background: linear-gradient(135deg, #dc3545, #fd7e14);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
}

.score-pending {
    background: linear-gradient(135deg, #6c757d, #495057);
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.score-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.score-label {
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-top: 5px;
}
</style>

<?php include '../includes/footer.php'; ?>