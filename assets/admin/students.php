<?php
/**
 * Students Management - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Process student deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $student_id = (int)$_GET['delete'];
    
    // Check if student has exam attempts
    $check_attempts_sql = "SELECT COUNT(*) as count FROM exam_attempts WHERE student_id = ?";
    if ($stmt = $conn->prepare($check_attempts_sql)) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            setFlashMessage('error', 'Cannot delete student with exam attempts. Deactivate account instead.');
        } else {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Remove from course enrollments
                $delete_enrollments_sql = "DELETE FROM user_courses WHERE user_id = ?";
                if ($stmt_enrollments = $conn->prepare($delete_enrollments_sql)) {
                    $stmt_enrollments->bind_param("i", $student_id);
                    $stmt_enrollments->execute();
                    $stmt_enrollments->close();
                }
                
                // Delete the student
                $delete_student_sql = "DELETE FROM users WHERE id = ? AND role = 'student'";
                if ($stmt_student = $conn->prepare($delete_student_sql)) {
                    $stmt_student->bind_param("i", $student_id);
                    $stmt_student->execute();
                    
                    if ($stmt_student->affected_rows > 0) {
                        // Commit transaction
                        $conn->commit();
                        setFlashMessage('success', 'Student deleted successfully.');
                    } else {
                        throw new Exception("Student not found or is not a student account.");
                    }
                    $stmt_student->close();
                }
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                setFlashMessage('error', 'Error deleting student: ' . $e->getMessage());
            }
        }
        $stmt->close();
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/students.php');
}

// Get filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$level = isset($_GET['level']) ? trim($_GET['level']) : '';
$course = isset($_GET['course']) ? (int)$_GET['course'] : 0;

// Get all courses for filter dropdown
$all_courses = [];
$courses_sql = "SELECT * FROM courses ORDER BY title ASC";
$result = $conn->query($courses_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// Get distinct student levels
$levels = [];
$levels_sql = "SELECT DISTINCT level FROM users WHERE role = 'student' AND level IS NOT NULL ORDER BY level";
$result = $conn->query($levels_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['level'])) {
            $levels[] = $row['level'];
        }
    }
}

// Build query with filters
$students_sql = "SELECT u.*, 
                (SELECT COUNT(*) FROM exam_attempts WHERE student_id = u.id) as attempt_count,
                (SELECT COUNT(*) FROM user_courses WHERE user_id = u.id) as course_count
                FROM users u
                WHERE u.role = 'student' ";

// Apply filters
$params = [];
$param_types = "";

if (!empty($search)) {
    $students_sql .= "AND (u.username LIKE ? OR u.email LIKE ? OR u.matric_number LIKE ?) ";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "sss";
}

if (!empty($level)) {
    $students_sql .= "AND u.level = ? ";
    $params[] = $level;
    $param_types .= "s";
}

if ($course > 0) {
    $students_sql .= "AND u.id IN (SELECT user_id FROM user_courses WHERE course_id = ?) ";
    $params[] = $course;
    $param_types .= "i";
}

$students_sql .= "ORDER BY u.username ASC";

// Execute query with parameters
$students = [];
if ($stmt = $conn->prepare($students_sql)) {
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    $stmt->close();
}

// Set page title
$page_title = 'Student Management';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-user-graduate me-2"></i> Student Management
            </h1>
            <p class="lead">Manage student accounts, view progress, and export data.</p>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="fas fa-file-export me-1"></i> Export Student Data
            </button>
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
                <div class="col-md-4">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="Name, Email, or Matric Number"
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-3">
                    <label for="level" class="form-label">Level</label>
                    <select class="form-select" id="level" name="level">
                        <option value="">All Levels</option>
                        <?php foreach ($levels as $lvl): ?>
                            <option value="<?php echo $lvl; ?>" <?php echo ($level === $lvl) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($lvl); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="course" class="form-label">Course</label>
                    <select class="form-select" id="course" name="course">
                        <option value="0">All Courses</option>
                        <?php foreach ($all_courses as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($course == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Students</h5>
            <span class="badge bg-primary"><?php echo count($students); ?> Results</span>
        </div>
        <div class="card-body">
            <?php if (empty($students)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-user-graduate fa-3x mb-3 text-muted"></i>
                    <p class="mb-3">No students found matching your criteria.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Matric Number</th>
                                <th>Level</th>
                                <th>Courses</th>
                                <th>Exams</th>
                                <th>Last Active</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['matric_number'] ?? 'Not Set'); ?></td>
                                    <td><?php echo htmlspecialchars($student['level'] ?? 'Not Set'); ?></td>
                                    <td>
                                        <span class="badge bg-info text-dark"><?php echo $student['course_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $student['attempt_count']; ?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($student['last_active'])): ?>
                                            <span title="<?php echo formatDate($student['last_active']); ?>">
                                                <?php echo timeAgo($student['last_active']); ?>
                                            </span>
                                        <?php else: ?>
                                            Never
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/student_details.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                        <a href="<?php echo SITE_URL; ?>/admin/students.php?delete=<?php echo $student['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm <?php echo ($student['attempt_count'] > 0) ? 'disabled' : ''; ?>"
                                           onclick="return confirm('Are you sure you want to delete this student? This cannot be undone.');">
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

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportModalLabel">Export Student Data</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?php echo SITE_URL; ?>/admin/export_students.php" method="post" id="exportForm">
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
                                Basic Information (Name, Email, Matric Number, Level)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeCourses" value="courses" checked>
                            <label class="form-check-label" for="includeCourses">
                                Course Enrollments
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="include[]" id="includeExams" value="exams" checked>
                            <label class="form-check-label" for="includeExams">
                                Exam Results
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="filterType" class="form-label">Filter Students</label>
                        <select class="form-select" id="filterType" name="filter_type">
                            <option value="all">All Students</option>
                            <option value="level">By Level</option>
                            <option value="course">By Course</option>
                            <option value="active">Active in Last 30 Days</option>
                        </select>
                    </div>
                    
                    <div class="mb-3 filter-option" id="levelFilter" style="display:none;">
                        <label for="filterLevel" class="form-label">Select Level</label>
                        <select class="form-select" id="filterLevel" name="filter_level">
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?php echo $lvl; ?>">
                                    <?php echo htmlspecialchars($lvl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3 filter-option" id="courseFilter" style="display:none;">
                        <label for="filterCourse" class="form-label">Select Course</label>
                        <select class="form-select" id="filterCourse" name="filter_course">
                            <?php foreach ($all_courses as $c): ?>
                                <option value="<?php echo $c['id']; ?>">
                                    <?php echo htmlspecialchars($c['code'] . ' - ' . $c['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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
    const filterType = document.getElementById('filterType');
    const levelFilter = document.getElementById('levelFilter');
    const courseFilter = document.getElementById('courseFilter');
    
    filterType.addEventListener('change', function() {
        // Hide all filter options first
        document.querySelectorAll('.filter-option').forEach(el => {
            el.style.display = 'none';
        });
        
        // Show relevant filter based on selection
        if (this.value === 'level') {
            levelFilter.style.display = 'block';
        } else if (this.value === 'course') {
            courseFilter.style.display = 'block';
        }
    });
});

// Helper function to format time elapsed
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    let interval = Math.floor(seconds / 31536000);
    if (interval > 1) return interval + ' years ago';
    
    interval = Math.floor(seconds / 2592000);
    if (interval > 1) return interval + ' months ago';
    
    interval = Math.floor(seconds / 86400);
    if (interval > 1) return interval + ' days ago';
    
    interval = Math.floor(seconds / 3600);
    if (interval > 1) return interval + ' hours ago';
    
    interval = Math.floor(seconds / 60);
    if (interval > 1) return interval + ' minutes ago';
    
    if(seconds < 10) return 'just now';
    
    return Math.floor(seconds) + ' seconds ago';
}
</script>

<?php 
// Helper function for time ago display
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = floor($diff / 2592000);
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = floor($diff / 31536000);
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

include '../includes/footer.php'; 
?>