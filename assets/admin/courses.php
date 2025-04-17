<?php
/**
 * Courses Management - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Process course deletion if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    // Check if course has associated exams
    $check_exams_sql = "SELECT COUNT(*) as count FROM exams WHERE course_id = ?";
    if ($stmt = $conn->prepare($check_exams_sql)) {
        $stmt->bind_param("i", $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            setFlashMessage('error', 'Cannot delete course that has associated exams. Remove exams first.');
        } else {
            // Delete course
            $delete_sql = "DELETE FROM courses WHERE id = ?";
            if ($delete_stmt = $conn->prepare($delete_sql)) {
                $delete_stmt->bind_param("i", $course_id);
                if ($delete_stmt->execute()) {
                    setFlashMessage('success', 'Course deleted successfully.');
                } else {
                    setFlashMessage('error', 'Error deleting course: ' . $delete_stmt->error);
                }
                $delete_stmt->close();
            } else {
                setFlashMessage('error', 'Database error: ' . $conn->error);
            }
        }
        $stmt->close();
    }
    
    // Redirect to refresh the page
    redirect(SITE_URL . '/admin/courses.php');
}

// Get all courses with additional info
$courses = [];
$courses_sql = "SELECT c.*, u.username as instructor_name, 
               (SELECT COUNT(*) FROM exams WHERE course_id = c.id) as exam_count,
               (SELECT COUNT(*) FROM user_courses uc WHERE uc.course_id = c.id) as student_count
               FROM courses c
               LEFT JOIN users u ON c.instructor_id = u.id
               ORDER BY c.code ASC";
$result = $conn->query($courses_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
}

// Set page title
$page_title = 'Course Management';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-book me-2"></i> Course Management
            </h1>
            <p class="lead">Create and manage courses, assign instructors, and enroll students.</p>
        </div>
        <div class="col-md-6 text-end d-flex align-items-center justify-content-end">
            <a href="<?php echo SITE_URL; ?>/admin/create_course.php" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Create New Course
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
            <h5 class="mb-0">All Courses</h5>
        </div>
        <div class="card-body">
            <?php if (empty($courses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-book fa-3x mb-3 text-muted"></i>
                    <p class="mb-3">No courses have been created yet.</p>
                    <a href="<?php echo SITE_URL; ?>/admin/create_course.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-1"></i> Create First Course
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Title</th>
                                <th>Instructor</th>
                                <th>Students</th>
                                <th>Exams</th>
                                <th>Created</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($courses as $course): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($course['code']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($course['title']); ?></td>
                                    <td><?php echo htmlspecialchars($course['instructor_name'] ?? 'Not Assigned'); ?></td>
                                    <td>
                                        <span class="badge bg-primary"><?php echo $course['student_count']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-success"><?php echo $course['exam_count']; ?></span>
                                    </td>
                                    <td><?php echo formatDate($course['created_at']); ?></td>
                                    <td>
                                        <a href="<?php echo SITE_URL; ?>/admin/manage_course.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        
                                        <a href="<?php echo SITE_URL; ?>/admin/courses.php?delete=<?php echo $course['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm <?php echo ($course['exam_count'] > 0) ? 'disabled' : ''; ?>"
                                           onclick="return confirm('Are you sure you want to delete this course? This cannot be undone.');">
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
    
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Course Management Tips</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-info-circle me-2"></i> About Courses</h6>
                        <p class="small">Courses provide a way to organize exams and students. Each course can have multiple exams and enrolled students.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-lightbulb me-2"></i> Best Practices</h6>
                        <ul class="small">
                            <li>Use consistent course codes</li>
                            <li>Assign instructors to courses</li>
                            <li>Create exams within relevant courses</li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <h6><i class="fas fa-exclamation-triangle me-2"></i> Important Notes</h6>
                        <ul class="small">
                            <li>Courses with exams cannot be deleted</li>
                            <li>Deleting a course removes all student enrollments</li>
                            <li>Course codes must be unique</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>