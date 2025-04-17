<?php
/**
 * Create Course - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Get all instructors (admin users) for dropdown
$instructors = [];
$instructors_sql = "SELECT id, username, email FROM users WHERE role = 'admin' ORDER BY username ASC";
$result = $conn->query($instructors_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $instructors[] = $row;
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $code = trim($_POST['code']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $instructor_id = !empty($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : null;

    // Validate input
    $errors = [];
    if (empty($code)) {
        $errors[] = "Course code is required";
    }
    if (empty($title)) {
        $errors[] = "Course title is required";
    }

    // Check if course code already exists
    $check_sql = "SELECT id FROM courses WHERE code = ?";
    if ($stmt = $conn->prepare($check_sql)) {
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Course code already exists";
        }
        $stmt->close();
    }

    // Insert into database if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO courses (code, title, description, instructor_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssi", $code, $title, $description, $instructor_id);
            
            if ($stmt->execute()) {
                $course_id = $conn->insert_id;
                setFlashMessage('success', 'Course created successfully!');
                redirect(SITE_URL . "/admin/courses.php");
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Set page title
$page_title = 'Create New Course';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-book-medical me-2"></i> Create New Course
            </h1>
            <p class="lead">Fill out the form below to create a new course.</p>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Course Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="code" class="form-label">Course Code *</label>
                        <input type="text" class="form-control" id="code" name="code" required
                               value="<?php echo htmlspecialchars($_POST['code'] ?? ''); ?>" 
                               placeholder="E.g., CSC101, MTH201">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="title" class="form-label">Course Title *</label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                               placeholder="E.g., Introduction to Computer Science">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" 
                              placeholder="Course description and objectives"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="instructor_id" class="form-label">Instructor (Optional)</label>
                    <select class="form-select" id="instructor_id" name="instructor_id">
                        <option value="">No specific instructor</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>" <?php echo (isset($_POST['instructor_id']) && $_POST['instructor_id'] == $instructor['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($instructor['username'] . ' (' . $instructor['email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="<?php echo SITE_URL; ?>/admin/courses.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>