<?php
/**
 * Edit Exam - Admin
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Check if exam ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setFlashMessage('error', 'Invalid exam ID.');
    redirect(SITE_URL . '/admin/dashboard.php');
}

$exam_id = (int)$_GET['id'];

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

// Get sections if the exam has them
$sections = [];
if ($exam['has_sections']) {
    $sections_sql = "SELECT * FROM sections WHERE exam_id = ? ORDER BY position";
    if ($stmt = $conn->prepare($sections_sql)) {
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $sections[] = $row;
        }
        $stmt->close();
    }
}

// Get questions
$questions = [];
$questions_sql = "SELECT q.*, COUNT(c.id) as choice_count, 
                 (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count
                 FROM questions q
                 LEFT JOIN choices c ON q.id = c.question_id
                 WHERE q.exam_id = ?
                 GROUP BY q.id
                 ORDER BY q.position";
if ($stmt = $conn->prepare($questions_sql)) {
    $stmt->bind_param("i", $exam_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    $stmt->close();
}

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

    // Update in database if no errors
    if (empty($errors)) {
        $sql = "UPDATE exams SET 
                title = ?, 
                description = ?, 
                duration_minutes = ?, 
                course_id = ?, 
                published = ?, 
                start_date = ?, 
                end_date = ?, 
                passing_score = ?, 
                has_sections = ?, 
                randomize_questions = ?, 
                browser_security = ?, 
                allow_browser_warnings = ?, 
                max_violations = ?,
                assessment_type = ?
                WHERE id = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssiisissiiiiisi", 
                $title, $description, $duration_minutes, $course_id, $published, 
                $start_date, $end_date, $passing_score, $has_sections, $randomize_questions, 
                $browser_security, $allow_browser_warnings, $max_violations, $assessment_type, $exam_id);
            
            if ($stmt->execute()) {
                setFlashMessage('success', 'Exam updated successfully!');
                redirect(SITE_URL . "/admin/edit_exam.php?id=$exam_id");
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
    }
}

// Format date values for display in form
$start_date_formatted = !empty($exam['start_date']) ? date('Y-m-d\TH:i', strtotime($exam['start_date'])) : '';
$end_date_formatted = !empty($exam['end_date']) ? date('Y-m-d\TH:i', strtotime($exam['end_date'])) : '';

// Set page title
$page_title = 'Edit ' . ucfirst($exam['assessment_type']);

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/exams.php">Exams</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Edit <?php echo ucfirst($exam['assessment_type']); ?></li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-edit me-2"></i> Edit <?php echo ucfirst($exam['assessment_type']); ?>
            </h1>
            <p class="lead">Update details, sections, and questions for this <?php echo $exam['assessment_type']; ?>.</p>
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

    <!-- Tabs for different sections of the form -->
    <ul class="nav nav-tabs mb-4" id="examTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">
                <i class="fas fa-info-circle me-1"></i> Details
            </button>
        </li>
        <?php if ($exam['has_sections']): ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="sections-tab" data-bs-toggle="tab" data-bs-target="#sections" type="button" role="tab" aria-controls="sections" aria-selected="false">
                <i class="fas fa-puzzle-piece me-1"></i> Sections
            </button>
        </li>
        <?php endif; ?>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="questions-tab" data-bs-toggle="tab" data-bs-target="#questions" type="button" role="tab" aria-controls="questions" aria-selected="false">
                <i class="fas fa-question-circle me-1"></i> Questions
            </button>
        </li>
    </ul>

    <div class="tab-content" id="examTabContent">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <input type="hidden" name="assessment_type" value="<?php echo htmlspecialchars($exam['assessment_type']); ?>">
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required
                                           value="<?php echo htmlspecialchars($exam['title']); ?>" 
                                           placeholder="Enter a descriptive title">
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" 
                                              placeholder="Instructions or details about this <?php echo $exam['assessment_type']; ?>"><?php echo htmlspecialchars($exam['description']); ?></textarea>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="course_id" class="form-label">Course (Optional)</label>
                                        <select class="form-select" id="course_id" name="course_id">
                                            <option value="">No specific course</option>
                                            <?php foreach ($all_courses as $course): ?>
                                                <option value="<?php echo $course['id']; ?>" <?php echo ($exam['course_id'] == $course['id']) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($course['code'] . ' - ' . $course['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="duration_minutes" class="form-label">Duration (minutes) *</label>
                                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes" required
                                               value="<?php echo $exam['duration_minutes']; ?>" min="1">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label">Start Date/Time (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="start_date" name="start_date"
                                               value="<?php echo $start_date_formatted; ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label">End Date/Time (Optional)</label>
                                        <input type="datetime-local" class="form-control" id="end_date" name="end_date"
                                               value="<?php echo $end_date_formatted; ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="passing_score" class="form-label">Passing Score (%)</label>
                                    <input type="number" class="form-control" id="passing_score" name="passing_score" 
                                           value="<?php echo $exam['passing_score']; ?>" 
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
                                                   <?php echo ($exam['published']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="published">
                                                Make <?php echo ucfirst($exam['assessment_type']); ?> Available to Students
                                            </label>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="has_sections" name="has_sections"
                                                   <?php echo ($exam['has_sections']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="has_sections">
                                                Enable Timed Sections
                                            </label>
                                            <div class="form-text">Divide into sections with separate timers</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="randomize_questions" name="randomize_questions"
                                                   <?php echo ($exam['randomize_questions']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="randomize_questions">
                                                Randomize Question Order
                                            </label>
                                        </div>
                                        
                                        <hr>
                                        <h6>Security Settings</h6>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="browser_security" name="browser_security"
                                                   <?php echo ($exam['browser_security']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="browser_security">
                                                Enable Browser Security
                                            </label>
                                            <div class="form-text">Prevent tab switching and monitors behavior</div>
                                        </div>
                                        
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="allow_browser_warnings" name="allow_browser_warnings"
                                                   <?php echo ($exam['allow_browser_warnings']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="allow_browser_warnings">
                                                Show Warnings Instead of Blocking
                                            </label>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="max_violations" class="form-label">Max Security Violations</label>
                                            <input type="number" class="form-control" id="max_violations" name="max_violations" 
                                                   value="<?php echo $exam['max_violations']; ?>" min="1">
                                            <div class="form-text">Auto-submits after this many violations</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h5 class="mb-0">Assessment Type</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <select class="form-select" id="assessment_type_select" name="assessment_type">
                                                <?php foreach ($assessment_types as $type): ?>
                                                    <option value="<?php echo $type; ?>" <?php echo ($exam['assessment_type'] === $type) ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($type); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                Changes how this assessment is labeled throughout the system.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="<?php echo SITE_URL; ?>/admin/exams.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <?php if ($exam['has_sections']): ?>
        <!-- Sections Tab -->
        <div class="tab-pane fade" id="sections" role="tabpanel" aria-labelledby="sections-tab">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Sections</h5>
                    <a href="<?php echo SITE_URL; ?>/admin/add_section.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> Add Section
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($sections)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-puzzle-piece fa-3x mb-3 text-muted"></i>
                            <p class="mb-3">No sections have been added yet.</p>
                            <a href="<?php echo SITE_URL; ?>/admin/add_section.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add First Section
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>Title</th>
                                        <th>Description</th>
                                        <th>Duration</th>
                                        <th>Questions</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($sections as $section): ?>
                                        <?php 
                                        // Count questions in this section
                                        $section_questions = 0;
                                        foreach($questions as $q) {
                                            if ($q['section_id'] == $section['id']) {
                                                $section_questions++;
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $section['position']; ?></td>
                                            <td><?php echo htmlspecialchars($section['title']); ?></td>
                                            <td><?php echo !empty($section['description']) ? htmlspecialchars(substr($section['description'], 0, 50)) . '...' : ''; ?></td>
                                            <td><?php echo formatDuration($section['duration_minutes']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $section_questions; ?></span>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/admin/edit_section.php?id=<?php echo $section['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/admin/delete_section.php?id=<?php echo $section['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this section?');">
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
        <?php endif; ?>
        
        <!-- Questions Tab -->
        <div class="tab-pane fade" id="questions" role="tabpanel" aria-labelledby="questions-tab">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Questions</h5>
                    <a href="<?php echo SITE_URL; ?>/admin/add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle me-1"></i> Add Question
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($questions)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-question-circle fa-3x mb-3 text-muted"></i>
                            <p class="mb-3">No questions have been added yet.</p>
                            <a href="<?php echo SITE_URL; ?>/admin/add_question.php?exam_id=<?php echo $exam_id; ?>" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i> Add First Question
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="5%">#</th>
                                        <th>Question</th>
                                        <th>Type</th>
                                        <th>Section</th>
                                        <th>Points</th>
                                        <th>Answers</th>
                                        <th width="15%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($questions as $question): ?>
                                        <?php 
                                        // Find section title if applicable
                                        $section_title = 'General';
                                        if ($question['section_id']) {
                                            foreach($sections as $s) {
                                                if ($s['id'] == $question['section_id']) {
                                                    $section_title = $s['title'];
                                                    break;
                                                }
                                            }
                                        }
                                        ?>
                                        <tr>
                                            <td><?php echo $question['position']; ?></td>
                                            <td><?php echo htmlspecialchars(substr($question['text'], 0, 50)) . '...'; ?></td>
                                            <td>
                                                <?php if ($question['type'] === 'multiple_choice'): ?>
                                                    <span class="badge bg-primary">Multiple Choice</span>
                                                <?php elseif ($question['type'] === 'short_answer'): ?>
                                                    <span class="badge bg-success">Short Answer</span>
                                                <?php elseif ($question['type'] === 'essay'): ?>
                                                    <span class="badge bg-info">Essay</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($section_title); ?></td>
                                            <td><?php echo $question['points']; ?></td>
                                            <td>
                                                <?php if ($question['type'] === 'multiple_choice'): ?>
                                                    <span class="badge bg-secondary"><?php echo $question['choice_count']; ?> choices</span>
                                                <?php endif; ?>
                                                
                                                <?php if ($question['answer_count'] > 0): ?>
                                                    <span class="badge bg-warning text-dark"><?php echo $question['answer_count']; ?> answers</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="<?php echo SITE_URL; ?>/admin/edit_question.php?id=<?php echo $question['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/admin/delete_question.php?id=<?php echo $question['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this question?');">
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check for hash in URL to activate specific tab
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`button[data-bs-target="${hash}"]`);
        if (tab) {
            tab.click();
        }
    }
    
    // Update assessment type labels when type changes
    const assessmentTypeSelect = document.getElementById('assessment_type_select');
    const updateLabels = () => {
        const type = assessmentTypeSelect.value;
        document.querySelectorAll('.assessment-type-label').forEach(el => {
            el.textContent = type.charAt(0).toUpperCase() + type.slice(1);
        });
    };
    
    assessmentTypeSelect.addEventListener('change', updateLabels);
    updateLabels(); // Initialize on page load
});
</script>

<?php include '../includes/footer.php'; ?>