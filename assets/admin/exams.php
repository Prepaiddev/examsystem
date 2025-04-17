<?php
/**
 * Exams Management - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Process exam deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $exam_id = (int)$_GET['delete'];
    
    // Check if exam has attempts
    $check_attempts_sql = "SELECT COUNT(*) as count FROM exam_attempts WHERE exam_id = ?";
    if ($stmt = $conn->prepare($check_attempts_sql)) {
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            setFlashMessage('error', 'Cannot delete exam that has student attempts. Archive it instead.');
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Delete questions and choices
                $delete_choices_sql = "DELETE c FROM choices c 
                                      INNER JOIN questions q ON c.question_id = q.id
                                      WHERE q.exam_id = ?";
                if ($stmt_choices = $conn->prepare($delete_choices_sql)) {
                    $stmt_choices->bind_param("i", $exam_id);
                    $stmt_choices->execute();
                    $stmt_choices->close();
                }
                
                // Delete questions
                $delete_questions_sql = "DELETE FROM questions WHERE exam_id = ?";
                if ($stmt_questions = $conn->prepare($delete_questions_sql)) {
                    $stmt_questions->bind_param("i", $exam_id);
                    $stmt_questions->execute();
                    $stmt_questions->close();
                }
                
                // Delete sections
                $delete_sections_sql = "DELETE FROM sections WHERE exam_id = ?";
                if ($stmt_sections = $conn->prepare($delete_sections_sql)) {
                    $stmt_sections->bind_param("i", $exam_id);
                    $stmt_sections->execute();
                    $stmt_sections->close();
                }
                
                // Delete exam
                $delete_exam_sql = "DELETE FROM exams WHERE id = ?";
                if ($stmt_exam = $conn->prepare($delete_exam_sql)) {
                    $stmt_exam->bind_param("i", $exam_id);
                    $stmt_exam->execute();
                    $stmt_exam->close();
                }
                
                // Commit transaction
                $conn->commit();
                setFlashMessage('success', 'Exam deleted successfully.');
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                setFlashMessage('error', 'Error deleting exam: ' . $e->getMessage());
            }
        }
        $stmt->close();
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/exams.php');
}

// Get filter parameters
$filter_course = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_published = isset($_GET['published']) ? (int)$_GET['published'] : -1;

// Get all courses for filter dropdown
$all_courses = [];
$courses_sql = "SELECT * FROM courses ORDER BY title ASC";
$result = $conn->query($courses_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// Build query with filters
$exams_sql = "SELECT e.*, c.title as course_title, c.code as course_code,
              (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.id) as attempt_count,
              (SELECT COUNT(*) FROM questions WHERE exam_id = e.id) as question_count
              FROM exams e
              LEFT JOIN courses c ON e.course_id = c.id
              WHERE 1=1 ";

// Apply filters
$params = [];
$param_types = "";

if ($filter_course > 0) {
    $exams_sql .= "AND e.course_id = ? ";
    $params[] = $filter_course;
    $param_types .= "i";
}

if (!empty($filter_type)) {
    $exams_sql .= "AND e.assessment_type = ? ";
    $params[] = $filter_type;
    $param_types .= "s";
}

if ($filter_published >= 0) {
    $exams_sql .= "AND e.published = ? ";
    $params[] = $filter_published;
    $param_types .= "i";
}

$exams_sql .= "ORDER BY e.created_at DESC";

// Execute query with parameters
$exams = [];
if ($stmt = $conn->prepare($exams_sql)) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $exams[] = $row;
    }
    $stmt->close();
}

// Get assessment types from database
$assessment_types = ['exam', 'test', 'quiz'];
$types_sql = "SELECT DISTINCT assessment_type FROM exams";
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
$page_title = 'Exam Management';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-file-alt me-2"></i> Exam Management
            </h1>
            <p class="lead">Create, edit, and manage all exams, tests, and quizzes.</p>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <a href="<?php echo SITE_URL; ?>/admin/create_exam.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Create New Assessment
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['flash_type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['flash_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filters</h5>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="course" class="form-label">Course</label>
                    <select class="form-select" id="course" name="course">
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
                    <label for="published" class="form-label">Status</label>
                    <select class="form-select" id="published" name="published">
                        <option value="-1" <?php echo ($filter_published == -1) ? 'selected' : ''; ?>>All</option>
                        <option value="1" <?php echo ($filter_published == 1) ? 'selected' : ''; ?>>Published</option>
                        <option value="0" <?php echo ($filter_published == 0 && $filter_published !== -1) ? 'selected' : ''; ?>>Draft</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-1"></i> Apply Filters
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/exams.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Assessments</h5>
            <span class="badge bg-primary"><?php echo count($exams); ?> Results</span>
        </div>
        <div class="card-body">
            <?php if (empty($exams)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-file-alt fa-3x mb-3 text-muted"></i>
                    <p class="mb-3">No assessments found matching your criteria.</p>
                    <a href="<?php echo SITE_URL; ?>/admin/create_exam.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Create New Assessment
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Course</th>
                                <th>Questions</th>
                                <th>Attempts</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($exams as $exam): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam['id']; ?>" class="text-decoration-none">
                                            <?php echo htmlspecialchars($exam['title']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo ucfirst($exam['assessment_type']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($exam['course_title'] ?? 'General'); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo $exam['question_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $exam['attempt_count']; ?></span>
                                    </td>
                                    <td><?php echo formatDuration($exam['duration_minutes']); ?></td>
                                    <td>
                                        <?php if ($exam['published']): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Draft</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($exam['start_date']) && strtotime($exam['start_date']) > time()): ?>
                                            <span class="badge bg-info text-dark">Scheduled</span>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($exam['end_date']) && strtotime($exam['end_date']) < time()): ?>
                                            <span class="badge bg-secondary">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/admin/view_results.php?exam_id=<?php echo $exam['id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-chart-bar"></i>
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/admin/exams.php?delete=<?php echo $exam['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm <?php echo ($exam['attempt_count'] > 0) ? 'disabled' : ''; ?>"
                                           onclick="return confirm('Are you sure you want to delete this <?php echo $exam['assessment_type']; ?>? This cannot be undone.');">
                                            <i class="fas fa-trash-alt"></i>
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

<?php include '../includes/footer.php'; ?>