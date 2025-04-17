<?php
/**
 * Add Question - Admin
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

// Get exam sections if applicable
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

// Get existing questions count for position
$position = 1;
$count_sql = "SELECT COUNT(*) as count FROM questions WHERE exam_id = ?";
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
    $question_text = trim($_POST['text']);
    $question_type = $_POST['type'];
    $points = (int)$_POST['points'];
    $section_id = !empty($_POST['section_id']) ? (int)$_POST['section_id'] : null;
    $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
    $correct_answers = isset($_POST['correct_answers']) ? $_POST['correct_answers'] : [];
    $position = isset($_POST['position']) ? (int)$_POST['position'] : $position;

    // Validate input
    $errors = [];
    if (empty($question_text)) {
        $errors[] = "Question text is required";
    }
    if ($points <= 0) {
        $errors[] = "Points must be a positive number";
    }
    if ($question_type === 'multiple_choice' && empty($choices)) {
        $errors[] = "Multiple choice questions must have at least one choice";
    }

    // Insert into database if no errors
    if (empty($errors)) {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Insert question
            $question_sql = "INSERT INTO questions (exam_id, section_id, type, text, points, position) 
                            VALUES (?, ?, ?, ?, ?, ?)";
            
            if ($stmt = $conn->prepare($question_sql)) {
                $stmt->bind_param("iissii", $exam_id, $section_id, $question_type, $question_text, $points, $position);
                
                if ($stmt->execute()) {
                    $question_id = $conn->insert_id;
                    
                    // Insert choices for multiple choice questions
                    if ($question_type === 'multiple_choice' && !empty($choices)) {
                        $choice_sql = "INSERT INTO choices (question_id, text, is_correct) VALUES (?, ?, ?)";
                        
                        if ($choice_stmt = $conn->prepare($choice_sql)) {
                            foreach ($choices as $index => $choice_text) {
                                if (!empty(trim($choice_text))) {
                                    $is_correct = in_array($index, $correct_answers) ? 1 : 0;
                                    $choice_stmt->bind_param("isi", $question_id, $choice_text, $is_correct);
                                    $choice_stmt->execute();
                                }
                            }
                            $choice_stmt->close();
                        }
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    setFlashMessage('success', 'Question added successfully!');
                    
                    // Redirect based on "Save and Add Another" or just "Save"
                    if (isset($_POST['save_and_add'])) {
                        redirect(SITE_URL . "/admin/add_question.php?exam_id=$exam_id");
                    } else {
                        redirect(SITE_URL . "/admin/edit_exam.php?id=$exam_id");
                    }
                } else {
                    throw new Exception("Error adding question: " . $stmt->error);
                }
                $stmt->close();
            } else {
                throw new Exception("Database error: " . $conn->error);
            }
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

// Set page title
$page_title = 'Add Question';

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
                    <li class="breadcrumb-item active" aria-current="page">Add Question</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-question-circle me-2"></i> Add Question
            </h1>
            <p class="lead">Adding question to: <strong><?php echo htmlspecialchars($exam['title']); ?></strong></p>
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
            <h5 class="mb-0">Question Details</h5>
        </div>
        <div class="card-body">
            <form method="post" action="" id="questionForm">
                <input type="hidden" name="position" value="<?php echo $position; ?>">
                
                <div class="mb-3">
                    <label for="text" class="form-label">Question Text *</label>
                    <textarea class="form-control" id="text" name="text" rows="3" required><?php echo htmlspecialchars($_POST['text'] ?? ''); ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="type" class="form-label">Question Type *</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="multiple_choice" <?php echo (isset($_POST['type']) && $_POST['type'] === 'multiple_choice') ? 'selected' : ''; ?>>Multiple Choice</option>
                            <option value="short_answer" <?php echo (isset($_POST['type']) && $_POST['type'] === 'short_answer') ? 'selected' : ''; ?>>Short Answer</option>
                            <option value="essay" <?php echo (isset($_POST['type']) && $_POST['type'] === 'essay') ? 'selected' : ''; ?>>Essay</option>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="points" class="form-label">Points *</label>
                        <input type="number" class="form-control" id="points" name="points" required
                               value="<?php echo htmlspecialchars($_POST['points'] ?? '1'); ?>" min="1">
                    </div>
                </div>
                
                <?php if ($exam['has_sections'] && !empty($sections)): ?>
                <div class="mb-3">
                    <label for="section_id" class="form-label">Section (Optional)</label>
                    <select class="form-select" id="section_id" name="section_id">
                        <option value="">No specific section</option>
                        <?php foreach ($sections as $section): ?>
                            <option value="<?php echo $section['id']; ?>" <?php echo (isset($_POST['section_id']) && $_POST['section_id'] == $section['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($section['title']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div id="choicesContainer" class="mb-3" style="<?php echo (!isset($_POST['type']) || $_POST['type'] === 'multiple_choice') ? '' : 'display: none;'; ?>">
                    <label class="form-label">Answer Choices</label>
                    <div id="choicesList">
                        <?php 
                        $choices = $_POST['choices'] ?? ['', '', '', ''];
                        $correct_answers = $_POST['correct_answers'] ?? [];
                        
                        foreach ($choices as $index => $choice): 
                        ?>
                            <div class="input-group mb-2 choice-row">
                                <div class="input-group-text">
                                    <input class="form-check-input mt-0" type="checkbox" name="correct_answers[]" value="<?php echo $index; ?>"
                                           <?php echo in_array($index, $correct_answers) ? 'checked' : ''; ?>>
                                </div>
                                <input type="text" class="form-control" name="choices[]" placeholder="Enter choice text" 
                                       value="<?php echo htmlspecialchars($choice); ?>">
                                <button type="button" class="btn btn-outline-danger remove-choice">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <button type="button" class="btn btn-outline-secondary mt-2" id="addChoice">
                        <i class="fas fa-plus"></i> Add Another Choice
                    </button>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam_id; ?>" class="btn btn-outline-secondary me-md-2">Cancel</a>
                    <button type="submit" name="save_and_add" class="btn btn-outline-primary me-md-2">
                        <i class="fas fa-save me-1"></i> Save and Add Another
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Question
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionType = document.getElementById('type');
    const choicesContainer = document.getElementById('choicesContainer');
    const choicesList = document.getElementById('choicesList');
    const addChoiceBtn = document.getElementById('addChoice');
    const questionForm = document.getElementById('questionForm');
    let choiceIndex = <?php echo count($choices ?? ['', '', '', '']); ?>;
    
    // Show/hide choices based on question type
    questionType.addEventListener('change', function() {
        if (this.value === 'multiple_choice') {
            choicesContainer.style.display = '';
        } else {
            choicesContainer.style.display = 'none';
        }
    });
    
    // Add new choice
    addChoiceBtn.addEventListener('click', function() {
        const newChoice = document.createElement('div');
        newChoice.className = 'input-group mb-2 choice-row';
        newChoice.innerHTML = `
            <div class="input-group-text">
                <input class="form-check-input mt-0" type="checkbox" name="correct_answers[]" value="${choiceIndex}">
            </div>
            <input type="text" class="form-control" name="choices[]" placeholder="Enter choice text">
            <button type="button" class="btn btn-outline-danger remove-choice">
                <i class="fas fa-times"></i>
            </button>
        `;
        choicesList.appendChild(newChoice);
        choiceIndex++;
    });
    
    // Remove choice
    choicesList.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-choice') || e.target.parentElement.classList.contains('remove-choice')) {
            const row = e.target.closest('.choice-row');
            if (row && choicesList.children.length > 1) {
                row.remove();
            }
        }
    });
    
    // Form validation
    questionForm.addEventListener('submit', function(e) {
        if (questionType.value === 'multiple_choice') {
            // Get all choice inputs
            const choices = document.querySelectorAll('input[name="choices[]"]');
            const correctAnswers = document.querySelectorAll('input[name="correct_answers[]"]:checked');
            
            let hasValidChoices = false;
            choices.forEach(choice => {
                if (choice.value.trim()) {
                    hasValidChoices = true;
                }
            });
            
            if (!hasValidChoices) {
                e.preventDefault();
                alert('Please add at least one choice for the multiple choice question.');
                return;
            }
            
            if (correctAnswers.length === 0) {
                e.preventDefault();
                alert('Please select at least one correct answer.');
                return;
            }
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>