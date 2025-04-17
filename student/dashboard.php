<?php
/**
 * Student Dashboard
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

// Fetch student details
$student = [];
$student_sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = $conn->prepare($student_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $student = $row;
    }
    $stmt->close();
}

// Fetch enrolled courses
$courses = [];
$courses_sql = "SELECT c.* FROM courses c
               JOIN user_courses uc ON c.id = uc.course_id
               WHERE uc.user_id = ?
               ORDER BY c.title";
if ($stmt = $conn->prepare($courses_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    $stmt->close();
}

// Fetch upcoming exams
$upcoming_exams = [];
$exams_sql = "SELECT e.*, c.title as course_title FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             WHERE e.published = 1
             AND (e.start_date IS NULL OR e.start_date <= NOW())
             AND (e.end_date IS NULL OR e.end_date >= NOW())
             AND e.id NOT IN (
                 SELECT exam_id FROM exam_attempts 
                 WHERE student_id = ? AND completed_at IS NOT NULL
             )
             AND (e.course_id IS NULL OR e.course_id IN (
                 SELECT course_id FROM user_courses WHERE user_id = ?
             ))
             ORDER BY e.start_date, e.title
             LIMIT 5";

if ($stmt = $conn->prepare($exams_sql)) {
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $upcoming_exams[] = $row;
    }
    $stmt->close();
}

// Fetch recent results
$recent_results = [];
$results_sql = "SELECT ea.*, e.title as exam_title, c.title as course_title
               FROM exam_attempts ea
               JOIN exams e ON ea.exam_id = e.id
               LEFT JOIN courses c ON e.course_id = c.id
               WHERE ea.student_id = ? AND ea.completed_at IS NOT NULL
               ORDER BY ea.completed_at DESC
               LIMIT 5";

if ($stmt = $conn->prepare($results_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $recent_results[] = $row;
    }
    $stmt->close();
}

// Fetch student stats
$stats = [
    'total_exams' => 0,
    'exams_passed' => 0,
    'exams_failed' => 0,
    'avg_score' => 0,
    'total_courses' => count($courses)
];

$stats_sql = "SELECT 
                COUNT(*) as total_exams,
                SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as exams_passed,
                SUM(CASE WHEN passed = 0 THEN 1 ELSE 0 END) as exams_failed,
                AVG(score) as avg_score
              FROM exam_attempts
              WHERE student_id = ? AND completed_at IS NOT NULL";

if ($stmt = $conn->prepare($stats_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_exams'] = $row['total_exams'];
        $stats['exams_passed'] = $row['exams_passed'];
        $stats['exams_failed'] = $row['exams_failed'];
        $stats['avg_score'] = $row['avg_score'] ? round($row['avg_score'], 1) : 0;
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
$page_title = 'Student Dashboard';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-tachometer-alt me-2"></i> Student Dashboard
            </h1>
            <p class="lead">Welcome back, <?php echo htmlspecialchars($student['username']); ?>!</p>
        </div>
    </div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-4">
        <div class="card bg-gradient-primary text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_exams']; ?></div>
                <div class="stat-label">Exams Taken</div>
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-gradient-success text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['exams_passed']; ?></div>
                <div class="stat-label">Exams Passed</div>
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-gradient-info text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['avg_score']; ?>%</div>
                <div class="stat-label">Average Score</div>
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-4">
        <div class="card bg-gradient-secondary text-white h-100">
            <div class="card-body dashboard-stat">
                <div class="stat-count"><?php echo $stats['total_courses']; ?></div>
                <div class="stat-label">Enrolled Courses</div>
                <div class="stat-icon"><i class="fas fa-book"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Upcoming Exams -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i> Upcoming Exams</h5>
                <a href="<?php echo SITE_URL; ?>/student/exams.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_exams)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">No upcoming exams at the moment.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Course</th>
                                    <th>Duration</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($upcoming_exams as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                        <td><?php echo formatDuration($exam['duration_minutes']); ?></td>
                                        <td>
                                            <a href="<?php echo SITE_URL; ?>/student/take_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="fas fa-play-circle me-1"></i> Start
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
    </div>
    
    <!-- Recent Results -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-poll me-2"></i> Recent Results</h5>
                <a href="<?php echo SITE_URL; ?>/student/results.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_results)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-chart-bar fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">No exam results yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Exam</th>
                                    <th>Date</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_results as $result): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($result['exam_title']); ?></td>
                                        <td><?php echo formatDate($result['completed_at']); ?></td>
                                        <td>
                                            <?php if ($result['is_graded']): ?>
                                                <?php echo round($result['score'], 1); ?>%
                                            <?php else: ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($result['is_graded']): ?>
                                                <?php if ($result['passed']): ?>
                                                    <span class="badge bg-success">Passed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Grading</span>
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
</div>

<div class="row">
    <!-- Enrolled Courses -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i> My Courses</h5>
                <a href="<?php echo SITE_URL; ?>/student/courses.php" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($courses)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-graduation-cap fa-3x mb-3 text-muted"></i>
                        <p class="mb-0">You are not enrolled in any courses yet.</p>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($courses as $course): ?>
                            <a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $course['id']; ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($course['code']); ?> - <?php echo htmlspecialchars($course['title']); ?></h6>
                                </div>
                                <p class="mb-1 text-muted"><?php echo truncateText($course['description'], 100); ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Student Profile Summary -->
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i> My Profile</h5>
                <a href="<?php echo SITE_URL; ?>/student/profile.php" class="btn btn-sm btn-outline-primary">
                    Edit Profile
                </a>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3 text-center">
                        <div class="mb-3">
                            <i class="fas fa-user-graduate fa-5x text-primary"></i>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <h5><?php echo htmlspecialchars($student['username']); ?></h5>
                        <p class="text-muted mb-2">
                            <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($student['email']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-id-card me-2"></i> Matric: <?php echo htmlspecialchars($student['matric_number'] ?? 'Not set'); ?>
                        </p>
                        <p class="text-muted mb-0">
                            <i class="fas fa-layer-group me-2"></i> Level: <?php echo htmlspecialchars($student['level'] ?? 'Not set'); ?>
                        </p>
                    </div>
                </div>
                
                <h6>Performance Summary</h6>
                <?php if ($stats['total_exams'] > 0): ?>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Overall Performance
                            <span><?php echo round($stats['avg_score'], 1); ?>%</span>
                        </label>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min(100, $stats['avg_score']); ?>%" aria-valuenow="<?php echo $stats['avg_score']; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label d-flex justify-content-between">
                            Exams Passed Rate
                            <span><?php echo $stats['total_exams'] > 0 ? round(($stats['exams_passed'] / $stats['total_exams']) * 100, 1) : 0; ?>%</span>
                        </label>
                        <div class="progress">
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stats['total_exams'] > 0 ? min(100, ($stats['exams_passed'] / $stats['total_exams']) * 100) : 0; ?>%" aria-valuenow="<?php echo $stats['total_exams'] > 0 ? ($stats['exams_passed'] / $stats['total_exams']) * 100 : 0; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Complete some exams to see your performance stats.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div> <!-- Close container -->

<?php include '../includes/footer.php'; ?>