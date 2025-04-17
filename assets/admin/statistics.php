<?php
/**
 * Exam Statistics - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Get time period for statistics
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$valid_periods = ['week', 'month', 'quarter', 'year', 'all'];
if (!in_array($period, $valid_periods)) {
    $period = 'month';
}

// Calculate date ranges based on period
$start_date = null;
$date_format = '%Y-%m-%d'; // Default format
$group_by = 'DATE(started_at)'; // Default grouping
$current_date = date('Y-m-d');

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'quarter':
        $start_date = date('Y-m-d', strtotime('-90 days'));
        $date_format = '%Y-%m-%d'; // Group by week 
        $group_by = 'YEARWEEK(started_at)';
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        $date_format = '%Y-%m'; // Group by month
        $group_by = 'DATE_FORMAT(started_at, "%Y-%m")';
        break;
    case 'all':
        $start_date = null;
        $date_format = '%Y-%m'; // Group by month
        $group_by = 'DATE_FORMAT(started_at, "%Y-%m")';
        break;
}

// Get exam attempt statistics over time
$attempt_stats = [];
$time_sql = "SELECT 
             DATE_FORMAT(started_at, '$date_format') as date_label,
             $group_by as date_group,
             COUNT(*) as total_attempts,
             SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed,
             SUM(CASE WHEN completed_at IS NOT NULL AND passed = 1 THEN 1 ELSE 0 END) as passed,
             AVG(CASE WHEN completed_at IS NOT NULL AND is_graded = 1 THEN score ELSE NULL END) as avg_score
             FROM exam_attempts
             WHERE " . ($start_date ? "started_at >= '$start_date'" : "1=1") . "
             GROUP BY date_group
             ORDER BY date_group ASC";

$result = $conn->query($time_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $attempt_stats[] = $row;
    }
}

// Get top exams by attempt count
$top_exams = [];
$exams_sql = "SELECT e.id, e.title, e.assessment_type, c.code as course_code,
             COUNT(ea.id) as attempt_count,
             SUM(CASE WHEN ea.completed_at IS NOT NULL THEN 1 ELSE 0 END) as completed_count,
             SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) as passed_count,
             AVG(CASE WHEN ea.is_graded = 1 THEN ea.score ELSE NULL END) as avg_score
             FROM exams e
             LEFT JOIN courses c ON e.course_id = c.id
             LEFT JOIN exam_attempts ea ON e.id = ea.exam_id
             WHERE ea.id IS NOT NULL
             " . ($start_date ? "AND ea.started_at >= '$start_date'" : "") . "
             GROUP BY e.id
             ORDER BY attempt_count DESC
             LIMIT 10";

$result = $conn->query($exams_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_exams[] = $row;
    }
}

// Get assessment type distribution
$assessment_types = [];
$types_sql = "SELECT e.assessment_type, COUNT(ea.id) as attempt_count
             FROM exams e
             JOIN exam_attempts ea ON e.id = ea.exam_id
             WHERE e.assessment_type IS NOT NULL
             " . ($start_date ? "AND ea.started_at >= '$start_date'" : "") . "
             GROUP BY e.assessment_type
             ORDER BY attempt_count DESC";

$result = $conn->query($types_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $assessment_types[] = $row;
    }
}

// Get most active students
$active_students = [];
$students_sql = "SELECT u.id, u.username, u.matric_number, 
                COUNT(ea.id) as attempt_count,
                AVG(CASE WHEN ea.is_graded = 1 THEN ea.score ELSE NULL END) as avg_score,
                SUM(CASE WHEN ea.passed = 1 THEN 1 ELSE 0 END) as passed_count
                FROM users u
                JOIN exam_attempts ea ON u.id = ea.student_id
                WHERE u.role = 'student'
                " . ($start_date ? "AND ea.started_at >= '$start_date'" : "") . "
                GROUP BY u.id
                ORDER BY attempt_count DESC
                LIMIT 10";

$result = $conn->query($students_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $active_students[] = $row;
    }
}

// Set page title
$page_title = 'Exam Statistics';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-chart-line me-2"></i> Exam Statistics
            </h1>
            <p class="lead">View statistical data and insights across all assessments.</p>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <div class="btn-group">
                <a href="<?php echo SITE_URL; ?>/admin/statistics.php?period=week" class="btn btn<?php echo ($period !== 'week') ? '-outline' : ''; ?>-primary">Week</a>
                <a href="<?php echo SITE_URL; ?>/admin/statistics.php?period=month" class="btn btn<?php echo ($period !== 'month') ? '-outline' : ''; ?>-primary">Month</a>
                <a href="<?php echo SITE_URL; ?>/admin/statistics.php?period=quarter" class="btn btn<?php echo ($period !== 'quarter') ? '-outline' : ''; ?>-primary">Quarter</a>
                <a href="<?php echo SITE_URL; ?>/admin/statistics.php?period=year" class="btn btn<?php echo ($period !== 'year') ? '-outline' : ''; ?>-primary">Year</a>
                <a href="<?php echo SITE_URL; ?>/admin/statistics.php?period=all" class="btn btn<?php echo ($period !== 'all') ? '-outline' : ''; ?>-primary">All Time</a>
            </div>
        </div>
    </div>

    <!-- Main Stats Cards -->
    <div class="row mb-4">
        <?php
        // Calculate overall stats
        $total_attempts = 0;
        $total_completed = 0;
        $total_passed = 0;
        $scores_sum = 0;
        $scores_count = 0;
        
        foreach ($attempt_stats as $stat) {
            $total_attempts += $stat['total_attempts'];
            $total_completed += $stat['completed'];
            $total_passed += $stat['passed'];
            
            if ($stat['avg_score'] !== null) {
                $scores_sum += $stat['avg_score'] * $stat['completed']; // Weight by completion count
                $scores_count += $stat['completed'];
            }
        }
        
        $completion_rate = $total_attempts > 0 ? round(($total_completed / $total_attempts) * 100, 1) : 0;
        $pass_rate = $total_completed > 0 ? round(($total_passed / $total_completed) * 100, 1) : 0;
        $avg_score = $scores_count > 0 ? round($scores_sum / $scores_count, 1) : 0;
        ?>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total Attempts</h6>
                            <h2 class="mb-0"><?php echo $total_attempts; ?></h2>
                        </div>
                        <i class="fas fa-clipboard-list fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Completion Rate</h6>
                            <h2 class="mb-0"><?php echo $completion_rate; ?>%</h2>
                        </div>
                        <i class="fas fa-check-circle fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-info text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Pass Rate</h6>
                            <h2 class="mb-0"><?php echo $pass_rate; ?>%</h2>
                        </div>
                        <i class="fas fa-award fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card bg-secondary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Average Score</h6>
                            <h2 class="mb-0"><?php echo $avg_score; ?>%</h2>
                        </div>
                        <i class="fas fa-chart-bar fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Exam Activity Over Time</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($attempt_stats)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-line fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">No exam activity data available for this time period.</p>
                        </div>
                    <?php else: ?>
                        <canvas id="attemptChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Assessment Type Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($assessment_types)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-chart-pie fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">No assessment type data available.</p>
                        </div>
                    <?php else: ?>
                        <canvas id="typeChart" height="250"></canvas>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Top Assessments</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($top_exams)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">No exam data available for this time period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Assessment</th>
                                        <th>Type</th>
                                        <th>Attempts</th>
                                        <th>Avg. Score</th>
                                        <th>Pass Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($top_exams as $exam): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/admin/view_results.php?exam_id=<?php echo $exam['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($exam['title']); ?>
                                                </a>
                                                <?php if (!empty($exam['course_code'])): ?>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($exam['course_code']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-secondary"><?php echo ucfirst($exam['assessment_type']); ?></span></td>
                                            <td><?php echo $exam['attempt_count']; ?></td>
                                            <td>
                                                <?php 
                                                echo $exam['avg_score'] !== null ? 
                                                    number_format($exam['avg_score'], 1) . '%' : 
                                                    '-'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $pass_rate = $exam['completed_count'] > 0 ? 
                                                    round(($exam['passed_count'] / $exam['completed_count']) * 100, 1) : 0;
                                                echo $pass_rate . '%';
                                                ?>
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
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h5 class="mb-0">Most Active Students</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($active_students)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-user-graduate fa-3x mb-3 text-muted"></i>
                            <p class="mb-0">No student activity data available for this time period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Attempts</th>
                                        <th>Avg. Score</th>
                                        <th>Pass Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($active_students as $student): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($student['username']); ?>
                                                </a>
                                                <?php if (!empty($student['matric_number'])): ?>
                                                    <small class="text-muted d-block"><?php echo htmlspecialchars($student['matric_number']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $student['attempt_count']; ?></td>
                                            <td>
                                                <?php 
                                                echo $student['avg_score'] !== null ? 
                                                    number_format($student['avg_score'], 1) . '%' : 
                                                    '-'; 
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $pass_rate = $student['attempt_count'] > 0 ? 
                                                    round(($student['passed_count'] / $student['attempt_count']) * 100, 1) : 0;
                                                echo $pass_rate . '%';
                                                ?>
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
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if (!empty($attempt_stats)): ?>
    // Attempts over time chart
    const attemptCtx = document.getElementById('attemptChart').getContext('2d');
    new Chart(attemptCtx, {
        type: 'line',
        data: {
            labels: [<?php echo implode(', ', array_map(function($item) { return "'" . $item['date_label'] . "'"; }, $attempt_stats)); ?>],
            datasets: [
                {
                    label: 'Total Attempts',
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['total_attempts']; }, $attempt_stats)); ?>],
                    borderColor: '#0d6efd',
                    backgroundColor: '#0d6efd20',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Completed',
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['completed']; }, $attempt_stats)); ?>],
                    borderColor: '#198754',
                    backgroundColor: 'transparent',
                    tension: 0.1
                },
                {
                    label: 'Passed',
                    data: [<?php echo implode(', ', array_map(function($item) { return $item['passed']; }, $attempt_stats)); ?>],
                    borderColor: '#ffc107',
                    backgroundColor: 'transparent',
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: false
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    <?php endif; ?>

    <?php if (!empty($assessment_types)): ?>
    // Assessment type distribution chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo implode(', ', array_map(function($item) { return "'" . ucfirst($item['assessment_type']) . "'"; }, $assessment_types)); ?>],
            datasets: [{
                data: [<?php echo implode(', ', array_map(function($item) { return $item['attempt_count']; }, $assessment_types)); ?>],
                backgroundColor: [
                    '#0d6efd',
                    '#198754',
                    '#ffc107',
                    '#6f42c1',
                    '#fd7e14'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php include '../includes/footer.php'; ?>