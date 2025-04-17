<?php
/**
 * Student Course Detail Page
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

// Check if course ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid course ID.');
    redirect(SITE_URL . '/student/courses.php');
}

$course_id = intval($_GET['id']);

// Get course details
$course = [];
$course_sql = "SELECT c.*, u.username as instructor_name
              FROM courses c
              LEFT JOIN users u ON c.instructor_id = u.id
              WHERE c.id = ?";

if ($stmt = $conn->prepare($course_sql)) {
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $course = fetchRowSafely($result);
    $stmt->close();
}

// If course not found or student is not enrolled, redirect
if (empty($course)) {
    setFlashMessage('error', 'Course not found.');
    redirect(SITE_URL . '/student/courses.php');
}

// Check if student is enrolled in this course
$is_enrolled = false;
$enrollment_sql = "SELECT 1 FROM user_courses WHERE user_id = ? AND course_id = ?";
if ($stmt = $conn->prepare($enrollment_sql)) {
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    $stmt->store_result();
    $is_enrolled = ($stmt->num_rows > 0);
    $stmt->close();
}

if (!$is_enrolled) {
    setFlashMessage('error', 'You are not enrolled in this course.');
    redirect(SITE_URL . '/student/courses.php');
}

// Get exams for this course
$exams = [];
$exams_sql = "SELECT e.*, 
              (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id AND student_id = ? AND completed_at IS NOT NULL) as attempted,
              (SELECT score FROM exam_attempts WHERE exam_id = e.id AND student_id = ? AND is_graded = 1 ORDER BY completed_at DESC LIMIT 1) as latest_score,
              (SELECT passed FROM exam_attempts WHERE exam_id = e.id AND student_id = ? AND is_graded = 1 ORDER BY completed_at DESC LIMIT 1) as passed
              FROM exams e
              WHERE e.course_id = ? AND e.published = 1
              ORDER BY e.start_date DESC, e.created_at DESC";

if ($stmt = $conn->prepare($exams_sql)) {
    $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    
    $stmt->close();
}

// Update last active timestamp
$update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Set page title
$page_title = $course['title'];

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <!-- Course Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/student/courses.php">Courses</a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['code']); ?></li>
                </ol>
            </nav>
            <h1 class="display-5 fw-bold">
                <?php echo htmlspecialchars($course['title']); ?>
                <span class="badge bg-primary"><?php echo htmlspecialchars($course['code']); ?></span>
            </h1>
            <p class="lead">
                <?php if ($course['instructor_name']): ?>
                    Instructor: <?php echo htmlspecialchars($course['instructor_name']); ?>
                <?php else: ?>
                    <span class="text-muted">No instructor assigned</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="d-flex justify-content-md-end mt-3">
                <form method="post" action="<?php echo SITE_URL; ?>/student/courses.php" onsubmit="return confirm('Are you sure you want to unenroll from this course?');">
                    <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                    <button type="submit" name="unenroll" class="btn btn-outline-danger">
                        <i class="fas fa-times-circle me-1"></i> Unenroll
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Course Description -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Course Description</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($course['description'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                    <?php else: ?>
                        <p class="text-muted">No description available for this course.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Course Exams -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="h3 mb-4"><i class="fas fa-file-alt me-2"></i> Exams</h2>
            
            <?php if (empty($exams)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> No exams are currently available for this course.
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($exams as $exam): ?>
                        <?php
                            // Determine exam status
                            $status_class = '';
                            $status_text = '';
                            $can_take = false;
                            
                            if ($exam['attempted'] > 0) {
                                if ($exam['passed']) {
                                    $status_class = 'bg-success';
                                    $status_text = 'Passed';
                                } else {
                                    $status_class = 'bg-danger';
                                    $status_text = 'Failed';
                                }
                            } elseif ($exam['start_date'] && strtotime($exam['start_date']) > time()) {
                                $status_class = 'bg-warning text-dark';
                                $status_text = 'Scheduled';
                            } else {
                                $status_class = 'bg-primary';
                                $status_text = 'Available';
                                $can_take = true;
                            }
                        ?>
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
                                    <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <?php echo nl2br(htmlspecialchars(truncateText($exam['description'] ?? '', 150))); ?>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <span class="text-muted"><i class="fas fa-clock me-1"></i> Duration:</span>
                                                <div class="fw-bold"><?php echo formatDuration($exam['duration_minutes']); ?></div>
                                            </div>
                                            <div class="col-6">
                                                <span class="text-muted"><i class="fas fa-graduation-cap me-1"></i> Passing:</span>
                                                <div class="fw-bold"><?php echo $exam['passing_score']; ?>%</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="row">
                                            <?php if ($exam['start_date']): ?>
                                                <div class="col-6">
                                                    <span class="text-muted"><i class="fas fa-calendar-day me-1"></i> Starts:</span>
                                                    <div class="fw-bold"><?php echo formatDate($exam['start_date'], 'M j, Y g:i A'); ?></div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($exam['end_date']): ?>
                                                <div class="col-6">
                                                    <span class="text-muted"><i class="fas fa-calendar-check me-1"></i> Ends:</span>
                                                    <div class="fw-bold"><?php echo formatDate($exam['end_date'], 'M j, Y g:i A'); ?></div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if ($exam['attempted'] > 0): ?>
                                        <div class="mb-3">
                                            <div class="alert alert-light mb-0">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-chart-simple me-1"></i> Your Score:
                                                    </div>
                                                    <strong><?php echo number_format($exam['latest_score'], 1); ?>%</strong>
                                                </div>
                                                <div class="progress mt-2">
                                                    <div class="progress-bar <?php echo $exam['passed'] ? 'bg-success' : 'bg-danger'; ?>" 
                                                         role="progressbar" 
                                                         style="width: <?php echo min(100, $exam['latest_score']); ?>%" 
                                                         aria-valuenow="<?php echo $exam['latest_score']; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-flex gap-2">
                                        <?php if ($can_take): ?>
                                            <a href="<?php echo SITE_URL; ?>/student/take_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-play-circle me-1"></i> Take Exam
                                            </a>
                                        <?php elseif ($exam['attempted'] > 0): ?>
                                            <a href="<?php echo SITE_URL; ?>/student/exam_result.php?id=<?php echo $exam['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> View Results
                                            </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo SITE_URL; ?>/student/exam_detail.php?id=<?php echo $exam['id']; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-info-circle me-1"></i> Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Back Button -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="<?php echo SITE_URL; ?>/student/courses.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Back to Courses
            </a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>