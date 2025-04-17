<?php
/**
 * Add Section - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Check if exam ID is provided
if (!isset($_GET['exam_id']) || !is_numeric($_GET['exam_id'])) {
    setFlashMessage('error', 'Invalid exam ID.');
    redirect(SITE_URL . '/admin/dashboard.php');
}

$exam_id = (int)$_GET['exam_id'];

// Get exam details
$exam = null;
$exam_sql = "SELECT * FROM exams WHERE id = ?";
if ($stmt = $conn->prepare($exam_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $exam = $row;
    } else {
        setFlashMessage('error', 'Exam not found.');
        redirect(SITE_URL . '/admin/dashboard.php');
    }
    $stmt->close();
}

// Verify exam has sections enabled
if (!$exam['has_sections']) {
    setFlashMessage('error', 'This exam does not have sections enabled.');
    redirect(SITE_URL . "/admin/edit_exam.php?id=$exam_id");
}

// Get existing sections count for position
$position = 1;
$count_sql = "SELECT COUNT(*) as count FROM sections WHERE exam_id = ?";
if ($stmt = $conn->prepare($count_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $position = $row['count'] + 1;
    }
    $stmt->close();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $position = isset($_POST['position']) ? (int)$_POST['position'] : $position;

    // Validate input
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if ($duration_minutes <= 0) {
        $errors[] = "Duration must be a positive number";
    }

    // Insert into database if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO sections (exam_id, title, description, duration_minutes, position) 
                VALUES (?, ?, ?, ?, ?)";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("issii", $exam_id, $title, $description, $duration_minutes, $position);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Section added successfully!');
                
                // Redirect based on "Save and Add Another" or just "Save"
                if (isset($_POST['save_and_add'])) {
                    redirect(SITE_URL . "/admin/add_section.php?exam_id=$exam_id");
                } else {
                    redirect(SITE_URL . "/admin/edit_exam.php?id=$exam_id");
                }
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
$page_title = 'Add Section';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam_id; ?>"><?php echo htmlspecialchars($exam['title']); ?></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Add Section</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-puzzle-piece me-2"></i> Add Section
            </h1>
            <p class="lead">Adding section to: <strong><?php echo htmlspecialchars($exam['title']); ?></strong></p>
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
            <h5 class="mb-0">Section Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <input type="hidden" name="position" value="<?php echo $position; ?>">
                
                <div class="mb-3">
                    <label for="title" class="form-label">Section Title *</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                           placeholder="E.g., Section 1: Multiple Choice">
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" 
                              placeholder="Instructions or details about this section"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="duration_minutes" class="form-label">Duration (minutes) *</label>
                    <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" required
                           value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '20'); ?>" min="1">
                    <div class="form-text">
                        Recommended: Keep the total of all section durations ≤ exam duration (<?php echo $exam['duration_minutes']; ?> minutes)
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" name="save_and_add" class="btn btn-outline-primary me-md-2">
                        <i class="fas fa-save me-1"></i> Save and Add Another
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>