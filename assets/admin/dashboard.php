<?php
/**
 * Admin Dashboard
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to view this page.');
    redirect(SITE_URL . '/login.php');
}

// Get current user ID
$user_id = $_SESSION['user_id'];

// Get admin details
$admin = [];
$admin_sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = $conn->prepare($admin_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $admin = $row;
    }
    $stmt->close();
}

// Get system stats
$stats = [
    'total_students' => 0,
    'total_exams' => 0,
    'total_courses' => 0,
    'total_questions' => 0,
    'pending_grading' => 0,
    'reported_questions' => 0,
    'active_students' => 0
];

// Get total students
$sql = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['total_students'] = $row['count'];
}

// Get total exams
$sql = "SELECT COUNT(*) as count FROM exams";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['total_exams'] = $row['count'];
}

// Get total courses
$sql = "SELECT COUNT(*) as count FROM courses";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['total_courses'] = $row['count'];
}

// Get total questions
$sql = "SELECT COUNT(*) as count FROM questions";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['total_questions'] = $row['count'];
}

// Get pending grading
$sql = "SELECT COUNT(*) as count FROM exam_attempts WHERE completed_at IS NOT NULL AND is_graded = 0";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['pending_grading'] = $row['count'];
}

// Get reported questions
$sql = "SELECT COUNT(*) as count FROM reported_questions WHERE status = 'pending'";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['reported_questions'] = $row['count'];
}

// Get active students (active in the last 24 hours)
$sql = "SELECT COUNT(*) as count FROM users 
        WHERE role = 'student' 
        AND last_active IS NOT NULL 
        AND last_active > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
$result = $conn->query($sql);
$row = fetchRowSafely($result);
if ($row) {
    $stats['active_students'] = $row['count'];
}

// Get recent exam attempts
$recent_attempts = [];
$attempts_sql = "SELECT ea.*, u.username as student_name, e.title as exam_title
                FROM exam_attempts ea
                JOIN users u ON ea.student_id = u.id
                JOIN exams e ON ea.exam_id = e.id
                ORDER BY ea.started_at DESC
                LIMIT 5";
$result = $conn->query($attempts_sql);
while ($row = $result->fetch_assoc()) {
    $recent_attempts[] = $row;
}

// Get recently published exams
$recent_exams = [];
$exams_sql = "SELECT e.*, c.title as course_title, COUNT(ea.id) as attempt_count
             FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
             WHERE e.published = 1
             GROUP BY e.id
             ORDER BY e.created_at DESC
             LIMIT 5";
$result = $conn->query($exams_sql);
while ($row = $result->fetch_assoc()) {
    $recent_exams[] = $row;
}

// Update last active timestamp
$update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Set page title
$page_title = 'Admin Dashboard';

// Include header
include '../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">
            <i class="fas fa-tachometer-alt me-2"></i> Admin Dashboard
        </h1>
        <p class="lead">Welcome back, <?php echo htmlspecialchars($admin['username']); ?>!</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-gradient-primary text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_students']; ?></div>
                <div class="stat-label">Total Students</div>
                <div class="stat-icon"><i class="fas fa-user-graduate"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-gradient-success text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_exams']; ?></div>
                <div class="stat-label">Total Exams</div>
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-gradient-info text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_courses']; ?></div>
                <div class="stat-label">Total Courses</div>
                <div class="stat-icon"><i class="fas fa-book"></i></div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 mb-4">
        <div class="card bg-gradient-secondary text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_questions']; ?></div>
                <div class="stat-label">Total Questions</div>
                <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <!-- Quick Actions -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/admin/create_exam.php" class="btn btn-primary w-100 py-3">
                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                            <div>Create New Exam</div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/admin/create_course.php" class="btn btn-success w-100 py-3">
                            <i class="fas fa-book-medical fa-2x mb-2"></i>
                            <div>Add New Course</div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/admin/students.php" class="btn btn-info text-white w-100 py-3">
                            <i class="fas fa-user-plus fa-2x mb-2"></i>
                            <div>Manage Students</div>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="<?php echo SITE_URL; ?>/admin/results.php" class="btn btn-secondary w-100 py-3">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <div>View Results</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Tasks -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i> Pending Tasks</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php if ($stats['pending_grading'] > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/grade_exams.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-pen-fancy me-2"></i> 
                                Essays & Short Answers Needing Grading
                            </div>
                            <span class="badge bg-danger rounded-pill"><?php echo $stats['pending_grading']; ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($stats['reported_questions'] > 0): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/reported_questions.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-flag me-2"></i> 
                                Reported Questions to Review
                            </div>
                            <span class="badge bg-warning text-dark rounded-pill"><?php echo $stats['reported_questions']; ?></span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo SITE_URL; ?>/admin/statistics.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-line me-2"></i> 
                        Review Exam Statistics
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/admin/profile.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-cog me-2"></i> 
                        Update Your Profile
                    </a>
                </div>
                
                <?php if ($stats['pending_grading'] == 0 && $stats['reported_questions'] == 0): ?>
                    <div class="alert alert-success mt-3 mb-0">
                        <i class="fas fa-check-circle me-2"></i> All tasks are up to date!
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Exam Attempts -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i> Recent Exam Attempts</h5>
                <a href="<?php echo SITE_URL; ?>/admin/results.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_attempts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clock fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">No exam attempts yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_attempts as $attempt): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($attempt['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                        <td><?php echo formatDate($attempt['started_at']); ?></td>
                                        <td>
                                            <?php if ($attempt['completed_at']): ?>
                                                <?php if ($attempt['is_graded']): ?>
                                                    <span class="badge bg-success">Graded</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">Needs Grading</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-info text-white">In Progress</span>
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
    </div>
    
    <!-- Published Exams -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Recent Exams</h5>
                <a href="<?php echo SITE_URL; ?>/admin/exams.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_exams)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">No exams published yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Exam Name</th>
                                    <th>Course</th>
                                    <th>Attempts</th>
                                    <th>Duration</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_exams as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                        <td><?php echo $exam['attempt_count']; ?></td>
                                        <td><?php echo formatDuration($exam['duration_minutes']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Status -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i> System Status</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-2">Students Activity</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Active (24h):</span>
                                    <span class="fw-bold"><?php echo $stats['active_students']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Total:</span>
                                    <span class="fw-bold"><?php echo $stats['total_students']; ?></span>
                                </div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total_students'] ? min(100, ($stats['active_students'] / $stats['total_students'] * 100)) : 0; ?>%" aria-valuenow="<?php echo $stats['active_students']; ?>" aria-valuemin="0" aria-valuemax="<?php echo $stats['total_students']; ?>"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <h6 class="mb-2">Database Stats</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <span>Exams:</span>
                                    <span class="fw-bold"><?php echo $stats['total_exams']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Questions:</span>
                                    <span class="fw-bold"><?php echo $stats['total_questions']; ?></span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Courses:</span>
                                    <span class="fw-bold"><?php echo $stats['total_courses']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mb-0">
                    <div class="d-flex">
                        <div class="me-2">
                            <i class="fas fa-info-circle fa-2x"></i>
                        </div>
                        <div>
                            <div class="fw-bold">System Information</div>
                            <p class="mb-0">The exam system is running normally. PHP Version: <?php echo phpversion(); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help & Support -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-life-ring me-2"></i> Help & Support</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-book me-2"></i> Documentation</h6>
                                <p>View the system documentation to learn how to use all features.</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">View Docs</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body">
                                <h6><i class="fas fa-question-circle me-2"></i> FAQs</h6>
                                <p>Find answers to frequently asked questions about the system.</p>
                                <a href="#" class="btn btn-sm btn-outline-primary">View FAQs</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-primary mb-0">
                    <div class="d-flex">
                        <div class="me-2">
                            <i class="fas fa-headset fa-2x"></i>
                        </div>
                        <div>
                            <div class="fw-bold">Need Help?</div>
                            <p class="mb-0">If you need assistance or have any questions, contact our support team at support@example.com.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>