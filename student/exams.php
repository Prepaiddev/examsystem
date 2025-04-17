<?php
/**
 * Student Exams Page
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

// Get student details
$student = [];
$student_sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = $conn->prepare($student_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = fetchRowSafely($result);
    $stmt->close();
}

// Get available exams
$available_exams = [];
$available_sql = "SELECT e.*, c.title as course_title, 
                 (SELECT COUNT(*) FROM exam_attempts 
                  WHERE exam_id = e.id AND student_id = ? AND completed_at IS NOT NULL) as attempted
                 FROM exams e
                 LEFT JOIN courses c ON e.course_id = c.id
                 LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?
                 WHERE e.published = 1
                 AND (e.course_id IS NULL OR uc.user_id IS NOT NULL)
                 AND (e.start_date IS NULL OR e.start_date <= NOW())
                 AND (e.end_date IS NULL OR e.end_date >= NOW())
                 ORDER BY e.created_at DESC";

if ($stmt = $conn->prepare($available_sql)) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $available_exams[] = $row;
    }
    
    $stmt->close();
}

// Get upcoming exams
$upcoming_exams = [];
$upcoming_sql = "SELECT e.*, c.title as course_title 
                FROM exams e
                LEFT JOIN courses c ON e.course_id = c.id
                LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?
                WHERE e.published = 1
                AND (e.course_id IS NULL OR uc.user_id IS NOT NULL)
                AND e.start_date > NOW()
                ORDER BY e.start_date ASC";

if ($stmt = $conn->prepare($upcoming_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $upcoming_exams[] = $row;
    }
    
    $stmt->close();
}

// Get completed exams
$completed_exams = [];
$completed_sql = "SELECT e.*, c.title as course_title, ea.score, ea.is_graded, ea.passed,
                 ea.started_at, ea.completed_at
                 FROM exams e
                 JOIN exam_attempts ea ON e.id = ea.exam_id
                 LEFT JOIN courses c ON e.course_id = c.id
                 WHERE ea.student_id = ? AND ea.completed_at IS NOT NULL
                 ORDER BY ea.completed_at DESC";

if ($stmt = $conn->prepare($completed_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $completed_exams[] = $row;
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
$page_title = 'Exams';

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-file-alt me-2"></i> Exams
            </h1>
            <p class="lead">View all available, upcoming, and completed exams.</p>
        </div>
    </div>

    <!-- Available Exams -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Available Exams</h5>
        </div>
        <div class="card-body">
            <?php if (empty($available_exams)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">No available exams found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Course</th>
                                <th>Duration</th>
                                <th>End Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($available_exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                    <td><?php echo formatDuration($exam['duration_minutes']); ?></td>
                                    <td>
                                        <?php if ($exam['end_date']): ?>
                                            <?php echo formatDate($exam['end_date'], 'M j, Y g:i A'); ?>
                                        <?php else: ?>
                                            <span class="text-muted">No end date</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($exam['attempted'] > 0): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php else: ?>
                                            <a href="<?php echo SITE_URL; ?>/student/take_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-play-circle me-1"></i> Start Exam
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upcoming Exams -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Upcoming Exams</h5>
        </div>
        <div class="card-body">
            <?php if (empty($upcoming_exams)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-alt fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">No upcoming exams found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Course</th>
                                <th>Start Date</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($upcoming_exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                    <td><?php echo formatDate($exam['start_date'], 'M j, Y g:i A'); ?></td>
                                    <td><?php echo formatDuration($exam['duration_minutes']); ?></td>
                                    <td>
                                        <span class="badge bg-info text-white">
                                            <i class="fas fa-clock me-1"></i> Scheduled
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Completed Exams -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i> Completed Exams</h5>
        </div>
        <div class="card-body">
            <?php if (empty($completed_exams)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">You haven't completed any exams yet.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Exam Title</th>
                                <th>Course</th>
                                <th>Completion Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($completed_exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                    <td><?php echo formatDate($exam['completed_at']); ?></td>
                                    <td>
                                        <?php if ($exam['is_graded']): ?>
                                            <strong><?php echo number_format($exam['score'], 1); ?>%</strong>
                                        <?php else: ?>
                                            <span class="text-muted">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($exam['is_graded']): ?>
                                            <?php if ($exam['passed']): ?>
                                                <span class="badge bg-success">Passed</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Grading in progress</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/student/exam_result.php?id=<?php echo $exam['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i> View Result
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Back to Dashboard Button -->
    <div class="text-center">
        <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>