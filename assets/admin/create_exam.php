<?php
/**
 * Create Exam - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Get all courses for dropdown
$all_courses = [];
$courses_sql = "SELECT * FROM courses ORDER BY title ASC";
$result = $conn->query($courses_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_courses[] = $row;
    }
}

// Define assessment types
$assessment_types = ['exam', 'test', 'quiz'];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $duration_minutes = (int)$_POST['duration_minutes'];
    $course_id = !empty($_POST['course_id']) ? (int)$_POST['course_id'] : null;
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $passing_score = (float)$_POST['passing_score'];
    $published = isset($_POST['published']) ? 1 : 0;
    $has_sections = isset($_POST['has_sections']) ? 1 : 0;
    $randomize_questions = isset($_POST['randomize_questions']) ? 1 : 0;
    $browser_security = isset($_POST['browser_security']) ? 1 : 0;
    $allow_browser_warnings = isset($_POST['allow_browser_warnings']) ? 1 : 0;
    $max_violations = (int)$_POST['max_violations'];
    $assessment_type = $_POST['assessment_type'];

    // Validate input
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if ($duration_minutes <= 0) {
        $errors[] = "Duration must be a positive number";
    }
    if ($passing_score < 0 || $passing_score > 100) {
        $errors[] = "Passing score must be between 0 and 100";
    }

    // Convert date strings to MySQL format
    if (!empty($start_date)) {
        $start_date = date('Y-m-d H:i:s', strtotime($start_date));
    }
    if (!empty($end_date)) {
        $end_date = date('Y-m-d H:i:s', strtotime($end_date));
    }

    // If we have an end date, verify it's after the start date
    if (!empty($start_date) && !empty($end_date) && strtotime($end_date) <= strtotime($start_date)) {
        $errors[] = "End date must be after start date";
    }

    // Insert into database if no errors
    if (empty($errors)) {
        $sql = "INSERT INTO exams (title, description, duration_minutes, course_id, published, 
                start_date, end_date, passing_score, has_sections, randomize_questions, 
                browser_security, allow_browser_warnings, max_violations, assessment_type, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssiisssdiiiiis", 
                $title, $description, $duration_minutes, $course_id, $published, 
                $start_date, $end_date, $passing_score, $has_sections, $randomize_questions, 
                $browser_security, $allow_browser_warnings, $max_violations, $assessment_type);
            
            if ($stmt->execute()) {
                $exam_id = $conn->insert_id;
                setFlashMessage('success', 'Exam created successfully!');
                
                if ($has_sections) {
                    // If exam has sections, redirect to add sections
                    redirect(SITE_URL . "/admin/add_section.php?exam_id=$exam_id");
                } else {
                    // Otherwise, redirect to add questions
                    redirect(SITE_URL . "/admin/add_question.php?exam_id=$exam_id");
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
$page_title = 'Create New Exam';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-plus-circle me-2"></i> Create New <?php echo isset($_POST['assessment_type']) ? ucfirst($_POST['assessment_type']) : 'Exam'; ?>
            </h1>
            <p class="lead">Fill out the form below to create a new exam or assessment.</p>
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
            <ul class="nav nav-tabs card-header-tabs" id="assessmentType" role="tablist">
                <?php foreach ($assessment_types as $index => $type): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo ($index === 0) ? 'active' : ''; ?> <?php echo (isset($_POST['assessment_type']) && $_POST['assessment_type'] === $type) ? 'active' : ''; ?>" 
                            id="<?php echo $type; ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#<?php echo $type; ?>-content" 
                            type="button" 
                            role="tab" 
                            aria-controls="<?php echo $type; ?>-content" 
                            aria-selected="<?php echo ($index === 0) ? 'true' : 'false'; ?>">
                        <?php echo ucfirst($type); ?>
                    </button>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content" id="assessmentTypeContent">
                <?php foreach ($assessment_types as $index => $type): ?>
                <div class="tab-pane fade <?php echo ($index === 0) ? 'show active' : ''; ?> <?php echo (isset($_POST['assessment_type']) && $_POST['assessment_type'] === $type) ? 'show active' : ''; ?>" 
                     id="<?php echo $type; ?>-content" 
                     role="tabpanel" 
                     aria-labelledby="<?php echo $type; ?>-tab">
                    
                    <form method="post" action="">
                        <input type="hidden" name="assessment_type" value="<?php echo $type; ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" 
                                           placeholder="Enter a descriptive title">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Instructions or details about this <?php echo $type; ?>"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="course_id" class="form-label">Course (Optional)</label>
                                        <select class="form-select" id="course_id" name="course_id">
                                            <option value="">No specific course</option>
                                            <?php foreach ($all_courses as $course): ?>
                                                <option value="<?php echo $course['id']; ?>" <?php echo (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="duration_minutes" class="form-label">Duration (minutes) *</label>
                                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" required
                                               value="<?php echo htmlspecialchars($_POST['duration_minutes'] ?? '60'); ?>" min="1">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date/Time (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date"
                                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date/Time (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date"
                                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score (%)</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                           value="<?php echo htmlspecialchars($_POST['passing_score'] ?? '60'); ?>" 
                                           min="0" max="100" step="0.1">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Options</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="published" name="published" 
                                                   <?php echo (isset($_POST['published'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="published">
                                                Make <?php echo ucfirst($type); ?> Available to Students
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="has_sections" name="has_sections"
                                                   <?php echo (isset($_POST['has_sections'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="has_sections">
                                                Enable Timed Sections
                                            </label>
                                            <div class="form-text">Divide into sections with separate timers</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="randomize_questions" name="randomize_questions"
                                                   <?php echo (isset($_POST['randomize_questions'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="randomize_questions">
                                                Randomize Question Order
                                            </label>
                                        </div>
                                        
                                        <hr>
                                        <h6>Security Settings</h6>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="browser_security" name="browser_security"
                                                   <?php echo (isset($_POST['browser_security'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="browser_security">
                                                Enable Browser Security
                                            </label>
                                            <div class="form-text">Prevent tab switching and monitors behavior</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="allow_browser_warnings" name="allow_browser_warnings"
                                                   <?php echo (!isset($_POST) || isset($_POST['allow_browser_warnings'])) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="allow_browser_warnings">
                                                Show Warnings Instead of Blocking
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_violations" class="form-label">Max Security Violations</label>
                                            <input type="number" class="form-control" id="max_violations" name="max_violations" 
                                                   value="<?php echo htmlspecialchars($_POST['max_violations'] ?? '3'); ?>" min="1">
                                            <div class="form-text">Auto-submits after this many violations</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Create <?php echo ucfirst($type); ?>
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set up tab functionality for assessment types
    const triggerTabList = document.querySelectorAll('#assessmentType button');
    triggerTabList.forEach(triggerEl => {
        triggerEl.addEventListener('click', function(event) {
            event.preventDefault();
            
            // Update the form title based on tab selection
            const type = this.id.replace('-tab', '');
            document.querySelector('h1').innerHTML = `<i class="fas fa-plus-circle me-2"></i> Create New ${type.charAt(0).toUpperCase() + type.slice(1)}`;
            
            // Update hidden field
            const tabContent = document.querySelector(`#${type}-content`);
            tabContent.querySelector('input[name="assessment_type"]').value = type;
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>