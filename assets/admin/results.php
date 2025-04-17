<?php
/**
 * Exam Results - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Get filter parameters
$filter_exam = isset($_GET['exam_id']) ? (int)$_GET['exam_id'] : 0;
$filter_student = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$filter_course = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_from = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filter_to = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Get all exams for filter dropdown
$all_exams = [];
$exams_sql = "SELECT e.id, e.title, e.assessment_type, c.code as course_code 
             FROM exams e 
             LEFT JOIN courses c ON e.course_id = c.id 
             ORDER BY e.title ASC";
$result = $conn->query($exams_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_exams[] = $row;
    }
}

// Get all courses for filter dropdown
$all_courses = [];
$courses_sql = "SELECT * FROM courses ORDER BY title ASC";
$result = $conn->query($courses_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// Get all students for filter dropdown
$all_students = [];
$students_sql = "SELECT id, username, matric_number FROM users WHERE role = 'student' ORDER BY username ASC";
$result = $conn->query($students_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_students[] = $row;
    }
}

// Build query with filters
$results_sql = "SELECT ea.*, 
               e.title as exam_title, e.passing_score, e.assessment_type,
               u.username as student_name, u.matric_number, u.email,
               c.title as course_title, c.code as course_code
               FROM exam_attempts ea
               JOIN exams e ON ea.exam_id = e.id
               JOIN users u ON ea.student_id = u.id
               LEFT JOIN courses c ON e.course_id = c.id
               WHERE 1=1 ";

// Apply filters
$params = [];
$param_types = "";

if ($filter_exam > 0) {
    $results_sql .= "AND ea.exam_id = ? ";
    $params[] = $filter_exam;
    $param_types .= "i";
}

if ($filter_student > 0) {
    $results_sql .= "AND ea.student_id = ? ";
    $params[] = $filter_student;
    $param_types .= "i";
}

if ($filter_course > 0) {
    $results_sql .= "AND e.course_id = ? ";
    $params[] = $filter_course;
    $param_types .= "i";
}

if (!empty($filter_type)) {
    $results_sql .= "AND e.assessment_type = ? ";
    $params[] = $filter_type;
    $param_types .= "s";
}

if (!empty($filter_status)) {
    if ($filter_status === 'completed') {
        $results_sql .= "AND ea.completed_at IS NOT NULL ";
    } elseif ($filter_status === 'in_progress') {
        $results_sql .= "AND ea.completed_at IS NULL ";
    } elseif ($filter_status === 'passed') {
        $results_sql .= "AND ea.passed = 1 ";
    } elseif ($filter_status === 'failed') {
        $results_sql .= "AND ea.passed = 0 AND ea.completed_at IS NOT NULL ";
    } elseif ($filter_status === 'need_grading') {
        $results_sql .= "AND ea.completed_at IS NOT NULL AND ea.is_graded = 0 ";
    }
}

if (!empty($filter_from)) {
    $from_date = date('Y-m-d 00:00:00', strtotime($filter_from));
    $results_sql .= "AND ea.started_at >= ? ";
    $params[] = $from_date;
    $param_types .= "s";
}

if (!empty($filter_to)) {
    $to_date = date('Y-m-d 23:59:59', strtotime($filter_to));
    $results_sql .= "AND ea.started_at <= ? ";
    $params[] = $to_date;
    $param_types .= "s";
}

$results_sql .= "ORDER BY ea.started_at DESC";

// Execute query with parameters
$results = [];
if ($stmt = $conn->prepare($results_sql)) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $results[] = $row;
    }
    $stmt->close();
}

// Get assessment types from database
$assessment_types = ['exam', 'test', 'quiz'];
$types_sql = "SELECT DISTINCT assessment_type FROM exams WHERE assessment_type IS NOT NULL";
$result = $conn->query($types_sql);
if ($result) {
    $assessment_types = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['assessment_type'])) {
            $assessment_types[] = $row['assessment_type'];
        }
    }
    
    if (empty($assessment_types)) {
        $assessment_types = ['exam', 'test', 'quiz']; // Default if no types found
    }
}

// Set page title
$page_title = 'Exam Results';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-chart-line me-2"></i> Assessment Results
            </h1>
            <p class="lead">View and analyze student performance across exams, tests, and quizzes.</p>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-file-export me-1"></i> Export Results
            </button>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="exam_id" class="form-label">Assessment</label>
                    <select class="form-select" id="exam_id" name="exam_id">
                        <option value="0">All Assessments</option>
                        <?php foreach ($all_exams as $exam): ?>
                            <option value="<?php echo $exam['id']; ?>" <?php echo ($filter_exam == $exam['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(ucfirst($exam['assessment_type']) . ': ' . $exam['title'] . (!empty($exam['course_code']) ? ' (' . $exam['course_code'] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="student_id" class="form-label">Student</label>
                    <select class="form-select" id="student_id" name="student_id">
                        <option value="0">All Students</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?php echo $student['id']; ?>" <?php echo ($filter_student == $student['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['username'] . (!empty($student['matric_number']) ? ' (' . $student['matric_number'] . ')' : '')); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="course_id" class="form-label">Course</label>
                    <select class="form-select" id="course_id" name="course_id">
                        <option value="0">All Courses</option>
                        <?php foreach ($all_courses as $course): ?>
                            <option value="<?php echo $course['id']; ?>" <?php echo ($filter_course == $course['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($assessment_types as $type): ?>
                            <option value="<?php echo $type; ?>" <?php echo ($filter_type === $type) ? 'selected' : ''; ?>>
                                <?php echo ucfirst($type); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="" <?php echo ($filter_status === '') ? 'selected' : ''; ?>>All Status</option>
                        <option value="completed" <?php echo ($filter_status === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="in_progress" <?php echo ($filter_status === 'in_progress') ? 'selected' : ''; ?>>In Progress</option>
                        <option value="passed" <?php echo ($filter_status === 'passed') ? 'selected' : ''; ?>>Passed</option>
                        <option value="failed" <?php echo ($filter_status === 'failed') ? 'selected' : ''; ?>>Failed</option>
                        <option value="need_grading" <?php echo ($filter_status === 'need_grading') ? 'selected' : ''; ?>>Needs Grading</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="from_date" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="from_date" name="from_date" 
                           value="<?php echo htmlspecialchars($filter_from); ?>">
                </div>
                <div class="col-md-3">
                    <label for="to_date" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="to_date" name="to_date"
                           value="<?php echo htmlspecialchars($filter_to); ?>">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/results.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Results</h5>
            <span class="badge bg-primary"><?php echo count($results); ?> Results</span>
        </div>
        <div class="card-body">
            <?php if (empty($results)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-chart-bar fa-3x mb-3 text-muted"></i>
                    <p class="mb-3">No results found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Assessment</th>
                                <th>Course</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($results as $attempt): ?>
                                <tr>
                                    <td>
                                        <span data-bs-toggle="tooltip" title="<?php echo htmlspecialchars($attempt['email']); ?>">
                                            <?php echo htmlspecialchars($attempt['student_name']); ?>
                                        </span>
                                        <?php if (!empty($attempt['matric_number'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($attempt['matric_number']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo htmlspecialchars($attempt['exam_title']); ?></div>
                                        <small class="badge bg-secondary"><?php echo ucfirst($attempt['assessment_type']); ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($attempt['course_title'])): ?>
                                            <div><?php echo htmlspecialchars($attempt['course_title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($attempt['course_code']); ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">General</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?php echo formatDate($attempt['started_at']); ?></div>
                                        <small class="text-muted">
                                            <?php if ($attempt['completed_at']): ?>
                                                Completed: <?php echo formatDate($attempt['completed_at']); ?>
                                            <?php else: ?>
                                                In progress
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php if ($attempt['completed_at'] && $attempt['is_graded']): ?>
                                            <div class="fw-bold">
                                                <?php echo number_format($attempt['score'], 1); ?>%
                                            </div>
                                            <small>
                                                <?php echo calculateGrade($attempt['score']); ?>
                                                <?php if ($attempt['passed']): ?>
                                                    <span class="text-success">(Passed)</span>
                                                <?php else: ?>
                                                    <span class="text-danger">(Failed)</span>
                                                <?php endif; ?>
                                            </small>
                                        <?php elseif ($attempt['completed_at'] && !$attempt['is_graded']): ?>
                                            <span class="badge bg-warning text-dark">Needs Grading</span>
                                        <?php else: ?>
                                            <span class="text-muted">Not completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$attempt['completed_at']): ?>
                                            <span class="badge bg-info text-white">In Progress</span>
                                        <?php elseif (!$attempt['is_graded']): ?>
                                            <span class="badge bg-warning text-dark">Needs Grading</span>
                                        <?php elseif ($attempt['passed']): ?>
                                            <span class="badge bg-success">Passed</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($attempt['security_violations'] > 0): ?>
                                            <span class="badge bg-danger" data-bs-toggle="tooltip" 
                                                title="Security violations: <?php echo $attempt['security_violations']; ?>">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/view_attempt.php?id=<?php echo $attempt['id']; ?>" class="btn btn-outline-primary btn-sm mb-1">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <?php if ($attempt['completed_at'] && !$attempt['is_graded']): ?>
                                            <a href="<?php echo SITE_URL; ?>/admin/grade_exam.php?id=<?php echo $attempt['id']; ?>" class="btn btn-outline-warning btn-sm mb-1">
                                                <i class="fas fa-pen"></i> Grade
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

    <?php if (!empty($results)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Summary Statistics</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Completion Rate</h6>
                            <?php
                            $completed = 0;
                            foreach($results as $attempt) {
                                if ($attempt['completed_at']) {
                                    $completed++;
                                }
                            }
                            $completion_rate = count($results) > 0 ? round(($completed / count($results)) * 100, 1) : 0;
                            ?>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%">
                                    <?php echo $completion_rate; ?>%
                                </div>
                            </div>
                            <div class="small text-muted">
                                <?php echo $completed; ?> out of <?php echo count($results); ?> attempts completed
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Pass Rate</h6>
                            <?php
                            $passed = 0;
                            $total_graded = 0;
                            foreach($results as $attempt) {
                                if ($attempt['completed_at'] && $attempt['is_graded']) {
                                    $total_graded++;
                                    if ($attempt['passed']) {
                                        $passed++;
                                    }
                                }
                            }
                            $pass_rate = $total_graded > 0 ? round(($passed / $total_graded) * 100, 1) : 0;
                            ?>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $pass_rate; ?>%">
                                    <?php echo $pass_rate; ?>%
                                </div>
                            </div>
                            <div class="small text-muted">
                                <?php echo $passed; ?> passed out of <?php echo $total_graded; ?> graded attempts
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h6 class="card-title">Average Score</h6>
                            <?php
                            $total_score = 0;
                            $scored_attempts = 0;
                            foreach($results as $attempt) {
                                if ($attempt['completed_at'] && $attempt['is_graded'] && $attempt['score'] !== null) {
                                    $total_score += $attempt['score'];
                                    $scored_attempts++;
                                }
                            }
                            $avg_score = $scored_attempts > 0 ? round($total_score / $scored_attempts, 1) : 0;
                            ?>
                            <div class="progress mb-2" style="height: 25px;">
                                <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $avg_score; ?>%">
                                    <?php echo $avg_score; ?>%
                                </div>
                            </div>
                            <div class="small text-muted">
                                Average across <?php echo $scored_attempts; ?> scored attempts
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Results</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo SITE_URL; ?>/admin/export_results.php" method="post" id="exportForm">
                    <div class="mb-3">
                        <label class="form-label">Export Format</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatCSV" value="csv" checked>
                            <label class="form-check-label" for="formatCSV">
                                CSV (Excel compatible)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="format" id="formatPDF" value="pdf">
                            <label class="form-check-label" for="formatPDF">
                                PDF
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Data to Include</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeBasic" value="basic" checked>
                            <label class="form-check-label" for="includeBasic">
                                Basic Result Information
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeStudent" value="student" checked>
                            <label class="form-check-label" for="includeStudent">
                                Student Details
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeAnswers" value="answers">
                            <label class="form-check-label" for="includeAnswers">
                                Individual Question Answers
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeSecurity" value="security">
                            <label class="form-check-label" for="includeSecurity">
                                Security Information
                            </label>
                        </div>
                    </div>
                    
                    <!-- Apply current filters to export -->
                    <input type="hidden" name="exam_id" value="<?php echo $filter_exam; ?>">
                    <input type="hidden" name="student_id" value="<?php echo $filter_student; ?>">
                    <input type="hidden" name="course_id" value="<?php echo $filter_course; ?>">
                    <input type="hidden" name="type" value="<?php echo htmlspecialchars($filter_type); ?>">
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter_status); ?>">
                    <input type="hidden" name="from_date" value="<?php echo htmlspecialchars($filter_from); ?>">
                    <input type="hidden" name="to_date" value="<?php echo htmlspecialchars($filter_to); ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="document.getElementById('exportForm').submit();">
                    <i class="fas fa-download me-1"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl)
    });
});
</script>

<?php include '../includes/footer.php'; ?>