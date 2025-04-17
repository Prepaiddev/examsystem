<?php
/**
 * View Detailed Answer Page
 */
require_once '../config/config.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    setFlashMessage('error', 'You must be logged in as a student to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Get answer ID from URL parameter
$answer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If no answer ID provided, redirect to results page
if ($answer_id <= 0) {
    setFlashMessage('error', 'No answer specified.');
    redirect(SITE_URL . '/student/results.php');
}

// Get the user ID of the current user
$user_id = $_SESSION['user_id'];

// Get the answer details, ensuring it belongs to the current user
$answer = [];
$answer_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points, e.title as exam_title,
              e.assessment_type, ea.student_id, ea.id as attempt_id
              FROM answers a
              JOIN questions q ON a.question_id = q.id
              JOIN exam_attempts ea ON a.attempt_id = ea.id
              JOIN exams e ON ea.exam_id = e.id
              WHERE a.id = ? AND ea.student_id = ?";

if ($stmt = $conn->prepare($answer_sql)) {
    $stmt->bind_param("ii", $answer_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $answer = $row;
    }
    $stmt->close();
}

// If answer not found or doesn't belong to current user, redirect
if (empty($answer)) {
    setFlashMessage('error', 'Answer not found or you do not have permission to view it.');
    redirect(SITE_URL . '/student/results.php');
}

// Process the answer text for display
$text_answer = $answer['text_answer'];
$has_image = false;
$image_info = null;

if (preg_match('/\[Image: (.+?)\]/', $text_answer, $matches)) {
    $has_image = true;
    $image_info = $matches[1];
    // Remove image tag from display text for cleaner viewing
    $text_answer = trim(str_replace("\n\n[Image: {$matches[1]}]", '', $text_answer));
}

// If multiple choice question, get selected choice
$selected_choice = null;
if ($answer['question_type'] === 'multiple_choice' && !empty($answer['selected_choice_id'])) {
    $choice_sql = "SELECT c.*, (c.is_correct = 1) as is_correct FROM choices c WHERE c.id = ?";
    if ($stmt = $conn->prepare($choice_sql)) {
        $stmt->bind_param("i", $answer['selected_choice_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $selected_choice = $row;
        }
        $stmt->close();
    }
    
    // Get correct choices for this question
    $correct_choices = [];
    $correct_sql = "SELECT * FROM choices WHERE question_id = ? AND is_correct = 1";
    if ($stmt = $conn->prepare($correct_sql)) {
        $stmt->bind_param("i", $answer['question_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $correct_choices[] = $row;
        }
        $stmt->close();
    }
}

// Set page title
$page_title = 'View Answer';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/results.php">Results</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/view_results.php?attempt_id=<?php echo $answer['attempt_id']; ?>">View Results</a></li>
                    <li class="breadcrumb-item active" aria-current="page">View Answer</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-file-alt me-2"></i> View Answer
            </h1>
            <p class="lead">
                Detailed view of your answer for <?php echo htmlspecialchars($answer['exam_title']); ?>
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Question Details</h5>
                    <span class="badge bg-<?php echo $answer['is_graded'] ? ($answer['score'] > 0 ? 'success' : 'danger') : 'secondary'; ?> fs-6">
                        <?php if ($answer['is_graded']): ?>
                            <?php echo number_format($answer['score'], 1); ?> / <?php echo $answer['question_points']; ?> points
                        <?php else: ?>
                            Not graded
                        <?php endif; ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h5>Question:</h5>
                        <div class="p-3 bg-light rounded">
                            <?php echo nl2br(htmlspecialchars($answer['question_text'])); ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Your Answer:</h5>
                        <div class="p-3 bg-light rounded">
                            <?php if ($answer['question_type'] === 'multiple_choice'): ?>
                                <?php if ($selected_choice): ?>
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            <?php if ($selected_choice['is_correct']): ?>
                                                <i class="fas fa-check-circle text-success"></i>
                                            <?php else: ?>
                                                <i class="fas fa-times-circle text-danger"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div><?php echo htmlspecialchars($selected_choice['text']); ?></div>
                                    </div>
                                    
                                    <?php if (!$selected_choice['is_correct'] && !empty($correct_choices)): ?>
                                        <div class="mt-3">
                                            <h6 class="text-success">Correct Answer(s):</h6>
                                            <ul class="mb-0">
                                                <?php foreach ($correct_choices as $choice): ?>
                                                    <li><?php echo htmlspecialchars($choice['text']); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-muted">No answer selected</div>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php if (!empty($text_answer)): ?>
                                    <div class="written-answer">
                                        <?php echo nl2br(htmlspecialchars($text_answer)); ?>
                                    </div>
                                    
                                    <?php if ($has_image): ?>
                                    <div class="mt-4">
                                        <h6><i class="fas fa-image me-2"></i> Uploaded Image:</h6>
                                        <div class="text-center mt-2">
                                            <img src="<?php echo SITE_URL; ?>/uploads/answers/<?php echo $answer['attempt_id']; ?>/<?php echo htmlspecialchars($image_info); ?>" 
                                                 class="img-fluid border" style="max-height: 400px;" alt="Your uploaded image">
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="text-muted">No answer provided</div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($answer['is_graded'] && !empty($answer['grader_feedback'])): ?>
                        <div class="mb-3">
                            <h5>Feedback:</h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($answer['grader_feedback'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?php echo SITE_URL; ?>/student/view_results.php?attempt_id=<?php echo $answer['attempt_id']; ?>" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Results
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Answer Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Assessment:</h6>
                        <p class="mb-1"><?php echo htmlspecialchars($answer['exam_title']); ?></p>
                        <small class="text-muted"><?php echo ucfirst($answer['assessment_type']); ?></small>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Question Type:</h6>
                        <p class="mb-0">
                            <?php 
                            $type_label = '';
                            switch($answer['question_type']) {
                                case 'multiple_choice':
                                    $type_label = 'Multiple Choice';
                                    break;
                                case 'short_answer':
                                    $type_label = 'Short Answer';
                                    break;
                                case 'essay':
                                    $type_label = 'Essay';
                                    break;
                                default:
                                    $type_label = ucfirst($answer['question_type']);
                            }
                            echo $type_label;
                            ?>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Points:</h6>
                        <p class="mb-0"><?php echo $answer['question_points']; ?></p>
                    </div>
                    
                    <div>
                        <h6>Status:</h6>
                        <?php if ($answer['is_graded']): ?>
                            <div class="d-flex align-items-center">
                                <?php if ($answer['score'] >= $answer['question_points']): ?>
                                    <div class="badge bg-success me-2">Correct</div>
                                <?php elseif ($answer['score'] > 0): ?>
                                    <div class="badge bg-warning text-dark me-2">Partial Credit</div>
                                <?php else: ?>
                                    <div class="badge bg-danger me-2">Incorrect</div>
                                <?php endif; ?>
                                <div><?php echo number_format(($answer['score'] / $answer['question_points']) * 100, 1); ?>%</div>
                            </div>
                        <?php else: ?>
                            <div class="badge bg-secondary">Not Graded</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($answer['question_type'] !== 'multiple_choice'): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tips for Improvement</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php if (!$answer['is_graded'] || $answer['score'] < $answer['question_points']): ?>
                            <li class="mb-2">Be sure to answer all parts of the question</li>
                            <li class="mb-2">Use specific examples to support your points</li>
                            <li class="mb-2">Structure your answer with clear paragraphs</li>
                            <li class="mb-2">Review relevant course materials before exams</li>
                            <li>Practice writing concise but complete answers</li>
                        <?php else: ?>
                            <li class="mb-2">Great job on this answer!</li>
                            <li class="mb-2">Continue using specific examples in your answers</li>
                            <li>Apply this level of detail to all questions</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>