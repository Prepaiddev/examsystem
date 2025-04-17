<?php
/**
 * Student Courses Page
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

// Handle course enrollment/unenrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enroll']) && isset($_POST['course_id'])) {
        $course_id = intval($_POST['course_id']);
        
        // Check if already enrolled
        $check_sql = "SELECT 1 FROM user_courses WHERE user_id = ? AND course_id = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("ii", $user_id, $course_id);
            $check_stmt->execute();
            $check_stmt->store_result();
            
            if ($check_stmt->num_rows == 0) {
                // Not enrolled, so add enrollment
                $enroll_sql = "INSERT INTO user_courses (user_id, course_id, enrolled_at) VALUES (?, ?, NOW())";
                if ($enroll_stmt = $conn->prepare($enroll_sql)) {
                    $enroll_stmt->bind_param("ii", $user_id, $course_id);
                    if ($enroll_stmt->execute()) {
                        setFlashMessage('success', 'Successfully enrolled in the course.');
                    } else {
                        setFlashMessage('error', 'Failed to enroll in the course.');
                    }
                    $enroll_stmt->close();
                }
            }
            
            $check_stmt->close();
        }
    } elseif (isset($_POST['unenroll']) && isset($_POST['course_id'])) {
        $course_id = intval($_POST['course_id']);
        
        // Remove enrollment
        $unenroll_sql = "DELETE FROM user_courses WHERE user_id = ? AND course_id = ?";
        if ($unenroll_stmt = $conn->prepare($unenroll_sql)) {
            $unenroll_stmt->bind_param("ii", $user_id, $course_id);
            if ($unenroll_stmt->execute()) {
                setFlashMessage('success', 'Successfully unenrolled from the course.');
            } else {
                setFlashMessage('error', 'Failed to unenroll from the course.');
            }
            $unenroll_stmt->close();
        }
    }
    
    // Redirect to avoid form resubmission
    redirect(SITE_URL . '/student/courses.php');
}

// Get enrolled courses
$enrolled_courses = [];
$enrolled_sql = "SELECT c.*, u.username as instructor_name, 
                (SELECT COUNT(*) FROM exams WHERE course_id = c.id AND published = 1) as exam_count
                FROM courses c
                JOIN user_courses uc ON c.id = uc.course_id
                LEFT JOIN users u ON c.instructor_id = u.id
                WHERE uc.user_id = ?
                ORDER BY c.title ASC";

if ($stmt = $conn->prepare($enrolled_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $enrolled_courses[] = $row;
    }
    
    $stmt->close();
}

// Get available courses (not enrolled)
$available_courses = [];
$available_sql = "SELECT c.*, u.username as instructor_name, 
                 (SELECT COUNT(*) FROM exams WHERE course_id = c.id AND published = 1) as exam_count
                 FROM courses c
                 LEFT JOIN users u ON c.instructor_id = u.id
                 WHERE c.id NOT IN (
                    SELECT course_id FROM user_courses WHERE user_id = ?
                 )
                 ORDER BY c.title ASC";

if ($stmt = $conn->prepare($available_sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $available_courses[] = $row;
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
$page_title = 'My Courses';

// Include header
include '../includes/header.php';
?>

<div class="container my-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-book me-2"></i> My Courses
            </h1>
            <p class="lead">View and manage your course enrollments.</p>
        </div>
    </div>

    <!-- Enrolled Courses -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-graduation-cap me-2"></i> My Enrolled Courses</h5>
        </div>
        <div class="card-body">
            <?php if (empty($enrolled_courses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-graduation-cap fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">You're not enrolled in any courses yet.</p>
                    <p class="text-muted">Explore available courses below and enroll to see them here.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($enrolled_courses as $course): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-gradient-primary text-white">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <div class="small"><?php echo htmlspecialchars($course['code']); ?></div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <?php echo htmlspecialchars(truncateText($course['description'], 150)); ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <i class="fas fa-user-tie me-1"></i> 
                                            <?php echo htmlspecialchars($course['instructor_name'] ?? 'No instructor'); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $course['exam_count']; ?> exams
                                        </span>
                                    </div>
                                    
                                    <div class="text-muted small mb-3">
                                        <i class="fas fa-calendar-alt me-1"></i> Enrolled: 
                                        <?php echo formatDate($course['created_at'], 'M j, Y'); ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="<?php echo SITE_URL; ?>/student/course_detail.php?id=<?php echo $course['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-book-open me-1"></i> View Course
                                        </a>
                                        
                                        <form method="post" onsubmit="return confirm('Are you sure you want to unenroll from this course?');">
                                            <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                            <button type="submit" name="unenroll" class="btn btn-outline-danger">
                                                <i class="fas fa-times-circle me-1"></i> Unenroll
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Available Courses -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-search me-2"></i> Available Courses</h5>
        </div>
        <div class="card-body">
            <?php if (empty($available_courses)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                    <p class="mb-0">No additional courses available at this time.</p>
                    <p class="text-muted">You're enrolled in all available courses.</p>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach($available_courses as $course): ?>
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5 class="mb-0"><?php echo htmlspecialchars($course['title']); ?></h5>
                                    <div class="small"><?php echo htmlspecialchars($course['code']); ?></div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">
                                        <?php echo htmlspecialchars(truncateText($course['description'], 150)); ?>
                                    </p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div>
                                            <i class="fas fa-user-tie me-1"></i> 
                                            <?php echo htmlspecialchars($course['instructor_name'] ?? 'No instructor'); ?>
                                        </div>
                                        <span class="badge bg-primary rounded-pill">
                                            <?php echo $course['exam_count']; ?> exams
                                        </span>
                                    </div>
                                    
                                    <div class="text-muted small mb-3">
                                        <i class="fas fa-calendar-alt me-1"></i> Created: 
                                        <?php echo formatDate($course['created_at'], 'M j, Y'); ?>
                                    </div>
                                    
                                    <form method="post">
                                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                                        <button type="submit" name="enroll" class="btn btn-success w-100">
                                            <i class="fas fa-plus-circle me-1"></i> Enroll in Course
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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