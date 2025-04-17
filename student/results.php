<?php
/**
 * Student Results Page
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

// Get all completed exams for this student
$completed_exams = [];
$completed_sql = "SELECT ea.*, e.title as exam_title, c.title as course_title,
                 e.duration_minutes, e.passing_score
                 FROM exam_attempts ea
                 JOIN exams e ON ea.exam_id = e.id
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

// Get overall statistics
$stats = [
    'total_exams' => count($completed_exams),
    'passed_exams' => 0,
    'failed_exams' => 0,
    'pending_grading' => 0,
    'average_score' => 0,
    'total_time' => 0
];

// Calculate statistics
$total_score = 0;
$graded_count = 0;

foreach ($completed_exams as $exam) {
    if ($exam['is_graded']) {
        $graded_count++;
        $total_score += $exam['score'];
        
        if ($exam['passed']) {
            $stats['passed_exams']++;
        } else {
            $stats['failed_exams']++;
        }
    } else {
        $stats['pending_grading']++;
    }
    
    // Calculate time spent
    if ($exam['started_at'] && $exam['completed_at']) {
        $started = new DateTime($exam['started_at']);
        $completed = new DateTime($exam['completed_at']);
        $interval = $started->diff($completed);
        $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
        $stats['total_time'] += $minutes;
    }
}

// Calculate average score
if ($graded_count > 0) {
    $stats['average_score'] = $total_score / $graded_count;
}

// Get passing rate
$stats['passing_rate'] = ($stats['total_exams'] - $stats['pending_grading'] > 0) 
    ? ($stats['passed_exams'] / ($stats['total_exams'] - $stats['pending_grading']) * 100) 
    : 0;

// Update last active timestamp
$update_sql = "UPDATE users SET last_active = NOW() WHERE id = ?";
if ($update_stmt = $conn->prepare($update_sql)) {
    $update_stmt->bind_param("i", $user_id);
    $update_stmt->execute();
    $update_stmt->close();
}

// Set page title
$page_title = 'My Results';

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-chart-line me-2"></i> My Results
            </h1>
            <p class="lead">View your exam performance and results history.</p>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-gradient-primary text-white h-100">
                <div class="card-body dashboard-stat">
                    <div class="stat-count"><?php echo $stats['total_exams']; ?></div>
                    <div class="stat-label">Total Exams Taken</div>
                    <div class="stat-icon"><i class="fas fa-clipboard-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-gradient-success text-white h-100">
                <div class="card-body dashboard-stat">
                    <div class="stat-count"><?php echo number_format($stats['average_score'], 1); ?>%</div>
                    <div class="stat-label">Average Score</div>
                    <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-gradient-info text-white h-100">
                <div class="card-body dashboard-stat">
                    <div class="stat-count"><?php echo number_format($stats['passing_rate'], 1); ?>%</div>
                    <div class="stat-label">Passing Rate</div>
                    <div class="stat-icon"><i class="fas fa-award"></i></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card bg-gradient-secondary text-white h-100">
                <div class="card-body dashboard-stat">
                    <div class="stat-count"><?php echo formatDuration($stats['total_time']); ?></div>
                    <div class="stat-label">Total Time Spent</div>
                    <div class="stat-icon"><i class="fas fa-clock"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Chart (Placeholder) -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Score Distribution</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="progress-wrapper">
                        <h6 class="mb-2">Exam Status</h6>
                        <div class="progress mb-1">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $stats['total_exams'] ? ($stats['passed_exams'] / $stats['total_exams'] * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo $stats['passed_exams']; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $stats['total_exams']; ?>">
                                <?php echo $stats['passed_exams']; ?> Passed
                            </div>
                            <div class="progress-bar bg-danger" role="progressbar" 
                                 style="width: <?php echo $stats['total_exams'] ? ($stats['failed_exams'] / $stats['total_exams'] * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo $stats['failed_exams']; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $stats['total_exams']; ?>">
                                <?php echo $stats['failed_exams']; ?> Failed
                            </div>
                            <div class="progress-bar bg-warning" role="progressbar" 
                                 style="width: <?php echo $stats['total_exams'] ? ($stats['pending_grading'] / $stats['total_exams'] * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo $stats['pending_grading']; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $stats['total_exams']; ?>">
                                <?php echo $stats['pending_grading']; ?> Pending
                            </div>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>Passed: <?php echo $stats['passed_exams']; ?></span>
                            <span>Failed: <?php echo $stats['failed_exams']; ?></span>
                            <span>Pending: <?php echo $stats['pending_grading']; ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="score-distribution">
                        <h6 class="mb-2">Average Score by Month</h6>
                        <?php if ($stats['total_exams'] == 0): ?>
                            <div class="text-center py-3">
                                <span class="text-muted">No data available yet</span>
                            </div>
                        <?php else: ?>
                            <div class="text-center chart-placeholder py-5 bg-light">
                                <span class="text-muted">
                                    <i class="fas fa-chart-line fa-2x mb-2 d-block"></i>
                                    Score history chart will appear here as you take more exams.
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exam Results Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Exam History</h5>
        </div>
        <div class="card-body">
            <?php if (empty($completed_exams)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-clipboard-list fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">You haven't completed any exams yet.</p>
                    <a href="<?php echo SITE_URL; ?>/student/exams.php" class="btn btn-primary mt-3">
                        <i class="fas fa-search me-1"></i> Browse Available Exams
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Exam</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Duration</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($completed_exams as $exam): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($exam['exam_title']); ?></td>
                                    <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                    <td><?php echo formatDate($exam['completed_at']); ?></td>
                                    <td>
                                        <?php 
                                        if ($exam['started_at'] && $exam['completed_at']) {
                                            $started = new DateTime($exam['started_at']);
                                            $completed = new DateTime($exam['completed_at']);
                                            $interval = $started->diff($completed);
                                            $minutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                                            echo formatDuration($minutes);
                                        } else {
                                            echo '--';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($exam['is_graded']): ?>
                                            <strong><?php echo number_format($exam['score'], 1); ?>%</strong>
                                            <div class="small text-muted">
                                                <?php echo calculateGrade($exam['score']); ?> (<?php echo $exam['passing_score']; ?>% to pass)
                                            </div>
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
                                        <a href="<?php echo SITE_URL; ?>/student/exam_result.php?id=<?php echo $exam['exam_id']; ?>&attempt=<?php echo $exam['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-search me-1"></i> View Details
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

    <!-- Back to Dashboard -->
    <div class="text-center mb-4">
        <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>