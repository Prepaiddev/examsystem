<?php
/**
 * Student Exam Detail Page
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

// Get exam details
$exam = [];
$exam_sql = "SELECT e.*, c.title as course_title, c.code as course_code, c.id as course_id,
             (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count,
             (SELECT COUNT(*) FROM sections WHERE exam_id = e.id) as section_count,
             u.username as instructor_name
             FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             LEFT JOIN users u ON c.instructor_id = u.id
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

// Get student's attempt history for this exam
$attempts = [];
$attempt_sql = "SELECT * FROM exam_attempts 
               WHERE student_id = ? AND exam_id = ?
               ORDER BY started_at DESC";
if ($stmt = $conn->prepare($attempt_sql)) {
    $stmt->bind_param("ii", $user_id, $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $attempts[] = $row;
    }
    
    $stmt->close();
}

// Get information about sections if the exam has them
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
            $sections[] = $row;
        }
        
        $stmt->close();
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
$page_title = $exam['title'];

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <!-- Exam Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/exams.php">Exams</a></li>
                    <?php if ($exam['course_id']): ?>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $exam['course_id']; ?>"><?php echo htmlspecialchars($exam['course_code']); ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($exam['title']); ?></li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold">
                <?php echo htmlspecialchars($exam['title']); ?>
                <span class="badge bg-primary"><?php echo $exam['assessment_type']; ?></span>
            </h1>
            <p class="lead">
                <?php if ($exam['course_title']): ?>
                    Course: <?php echo htmlspecialchars($exam['course_title']); ?>
                <?php else: ?>
                    <span class="text-muted">General Assessment</span>
                <?php endif; ?>
                <?php if ($exam['instructor_name']): ?>
                    | Instructor: <?php echo htmlspecialchars($exam['instructor_name']); ?>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="d-flex justify-content-md-end mt-3">
                <?php
                // Check if exam is available to take now
                $can_take = true;
                $status_text = '';
                
                if ($exam['start_date'] && strtotime($exam['start_date']) > time()) {
                    $can_take = false;
                    $status_text = 'This exam is scheduled to start on ' . formatDate($exam['start_date'], 'M j, Y g:i A');
                }
                
                if ($exam['end_date'] && strtotime($exam['end_date']) < time()) {
                    $can_take = false;
                    $status_text = 'This exam has ended on ' . formatDate($exam['end_date'], 'M j, Y g:i A');
                }
                
                // Check if already passed
                foreach ($attempts as $attempt) {
                    if ($attempt['completed_at'] && $attempt['is_graded'] && $attempt['passed']) {
                        $can_take = false;
                        $status_text = 'You have already passed this exam.';
                        break;
                    }
                }
                ?>
                
                <?php if ($can_take): ?>
                    <a href="<?php echo SITE_URL; ?>/student/take_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-primary">
                        <i class="fas fa-play-circle me-1"></i> Take Exam
                    </a>
                <?php elseif (!empty($status_text)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle me-1"></i> <?php echo $status_text; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Exam Information -->
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Exam Description</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($exam['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No description available for this exam.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Exam Sections (if applicable) -->
            <?php if ($exam['has_sections'] && !empty($sections)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i> Exam Sections</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Section</th>
                                        <th>Duration</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sections as $index => $section): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($section['title']); ?></td>
                                            <td><?php echo formatDuration($section['duration_minutes']); ?></td>
                                            <td>
                                                <?php echo !empty($section['description']) ? 
                                                      htmlspecialchars(truncateText($section['description'], 100)) : 
                                                      '<span class="text-muted">No description</span>'; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Exam Rules & Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list-check me-2"></i> Exam Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-group mb-3">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-clock me-2"></i> Duration</span>
                                    <span class="fw-bold"><?php echo formatDuration($exam['duration_minutes']); ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-question-circle me-2"></i> Questions</span>
                                    <span class="fw-bold"><?php echo $exam['question_count']; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-graduation-cap me-2"></i> Passing Score</span>
                                    <span class="fw-bold"><?php echo $exam['passing_score']; ?>%</span>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-group mb-3">
                                <?php if ($exam['start_date']): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-day me-2"></i> Start Date</span>
                                        <span class="fw-bold"><?php echo formatDate($exam['start_date'], 'M j, Y g:i A'); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($exam['end_date']): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-check me-2"></i> End Date</span>
                                        <span class="fw-bold"><?php echo formatDate($exam['end_date'], 'M j, Y g:i A'); ?></span>
                                    </li>
                                <?php endif; ?>
                                <?php if ($exam['randomize_questions']): ?>
                                    <li class="list-group-item">
                                        <i class="fas fa-random me-2"></i> Questions will be presented in random order
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if ($exam['browser_security']): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <h6 class="alert-heading"><i class="fas fa-shield-alt me-2"></i> Security Features Enabled</h6>
                            <p class="mb-0">This exam has browser security features enabled. During the exam:</p>
                            <ul class="mb-0 mt-2">
                                <li>Full-screen mode will be enforced</li>
                                <li>Leaving the exam tab may be restricted</li>
                                <li>Multiple attempts to violate security measures may result in automatic submission</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Previous Attempts -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i> Your Attempts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attempts)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">You haven't attempted this exam yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach($attempts as $index => $attempt): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Attempt #<?php echo count($attempts) - $index; ?></h6>
                                        <small><?php echo formatDate($attempt['started_at']); ?></small>
                                    </div>
                                    
                                    <?php if ($attempt['completed_at']): ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">Duration: 
                                                    <?php 
                                                    $start = new DateTime($attempt['started_at']);
                                                    $end = new DateTime($attempt['completed_at']);
                                                    $interval = $start->diff($end);
                                                    $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                                                    echo formatDuration($minutes);
                                                    ?>
                                                </small>
                                            </div>
                                            <div>
                                                <?php if ($attempt['is_graded']): ?>
                                                    <span class="badge <?php echo $attempt['passed'] ? 'bg-success' : 'bg-danger'; ?>">
                                                        <?php echo $attempt['score']; ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <a href="<?php echo SITE_URL; ?>/student/exam_result.php?id=<?php echo $exam_id; ?>&attempt=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> View Results
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <small class="text-muted">Started but not completed</small>
                                            </div>
                                            <span class="badge bg-info text-white">In Progress</span>
                                        </div>
                                        
                                        <div class="mt-2">
                                            <a href="<?php echo SITE_URL; ?>/student/take_exam.php?id=<?php echo $exam_id; ?>&resume=<?php echo $attempt['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-redo-alt me-1"></i> Resume
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <?php if ($exam['course_id']): ?>
                <a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $exam['course_id']; ?>" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left me-1"></i> Back to Course
                </a>
            <?php endif; ?>
            <a href="<?php echo SITE_URL; ?>/student/exams.php" class="btn btn-outline-secondary">
                <i class="fas fa-clipboard-list me-1"></i> All Exams
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>