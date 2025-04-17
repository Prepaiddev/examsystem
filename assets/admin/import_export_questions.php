<?php
/**
 * Import/Export Questions for Exams
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Check if exam ID is provided
$exam_id = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Initialize variables
$errors = [];
$success_message = '';
$import_template_created = false;
$export_file_created = false;

// Get exam details if exam_id is provided
$exam = null;
if ($exam_id > 0) {
    $exam_sql = "SELECT * FROM exams WHERE id = ?";
    if ($stmt = $conn->prepare($exam_sql)) {
        $stmt->bind_param("i", $exam_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $exam = $row;
        }
        $stmt->close();
    }
    
    if (!$exam) {
        setFlashMessage('error', 'Exam not found.');
        redirect(SITE_URL . '/admin/exams.php');
    }
}

// Get all exams for dropdown
$all_exams = [];
$exams_sql = "SELECT * FROM exams ORDER BY title ASC";
$result = $conn->query($exams_sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_exams[] = $row;
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Import CSV file
    if (isset($_POST['import']) && isset($_FILES['import_file']) && !empty($_FILES['import_file']['tmp_name'])) {
        $import_exam_id = isset($_POST['import_exam_id']) ? intval($_POST['import_exam_id']) : 0;
        
        if ($import_exam_id <= 0) {
            $errors[] = "Please select an exam to import questions to.";
        } else {
            // Verify the exam exists
            $verify_sql = "SELECT id FROM exams WHERE id = ?";
            if ($stmt = $conn->prepare($verify_sql)) {
                $stmt->bind_param("i", $import_exam_id);
                $stmt->execute();
                $stmt->store_result();
                $exam_exists = ($stmt->num_rows > 0);
                $stmt->close();
                
                if (!$exam_exists) {
                    $errors[] = "Selected exam does not exist.";
                }
            }
            
            if (empty($errors)) {
                // Process the CSV file
                $file = $_FILES['import_file']['tmp_name'];
                $handle = fopen($file, "r");
                
                if ($handle !== FALSE) {
                    // Start transaction
                    $conn->begin_transaction();
                    
                    try {
                        $header = fgetcsv($handle, 1000, ",");
                        
                        // Verify header format
                        $expected_header = ["position", "type", "text", "points", "choice_1", "choice_1_correct", "choice_2", "choice_2_correct", "choice_3", "choice_3_correct", "choice_4", "choice_4_correct", "section_id"];
                        
                        if ($header !== $expected_header) {
                            throw new Exception("CSV format is incorrect. Please use the provided template.");
                        }
                        
                        $row_num = 1; // Skip header row in count
                        $imported_count = 0;
                        
                        // Get current max position
                        $max_position = 0;
                        $position_sql = "SELECT MAX(position) as max_pos FROM questions WHERE exam_id = ?";
                        if ($stmt = $conn->prepare($position_sql)) {
                            $stmt->bind_param("i", $import_exam_id);
                            $stmt->execute();
                            $stmt->bind_result($max_position);
                            $stmt->fetch();
                            $stmt->close();
                        }
                        
                        // Prepare question insertion
                        $insert_question_sql = "INSERT INTO questions (exam_id, section_id, type, text, points, position) VALUES (?, ?, ?, ?, ?, ?)";
                        $question_stmt = $conn->prepare($insert_question_sql);
                        
                        // Prepare choice insertion
                        $insert_choice_sql = "INSERT INTO choices (question_id, text, is_correct) VALUES (?, ?, ?)";
                        $choice_stmt = $conn->prepare($insert_choice_sql);
                        
                        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                            $row_num++;
                            
                            // Check if we have enough columns
                            if (count($data) < 13) {
                                throw new Exception("Row $row_num has incorrect number of columns.");
                            }
                            
                            // Extract data
                            $position = !empty($data[0]) ? intval($data[0]) : $max_position + 1;
                            $type = $data[1];
                            $text = $data[2];
                            $points = !empty($data[3]) ? floatval($data[3]) : 1;
                            $section_id = !empty($data[12]) ? intval($data[12]) : null;
                            
                            // Validate question type
                            if (!in_array($type, ['multiple_choice', 'short_answer', 'essay'])) {
                                throw new Exception("Row $row_num has invalid question type. Allowed types: multiple_choice, short_answer, essay.");
                            }
                            
                            // Validate section_id if provided
                            if ($section_id !== null) {
                                $section_exists = false;
                                $section_sql = "SELECT 1 FROM sections WHERE id = ? AND exam_id = ?";
                                if ($stmt = $conn->prepare($section_sql)) {
                                    $stmt->bind_param("ii", $section_id, $import_exam_id);
                                    $stmt->execute();
                                    $stmt->store_result();
                                    $section_exists = ($stmt->num_rows > 0);
                                    $stmt->close();
                                }
                                
                                if (!$section_exists) {
                                    throw new Exception("Row $row_num references a section that doesn't exist for this exam.");
                                }
                            }
                            
                            // Insert question
                            $question_stmt->bind_param("iisidi", $import_exam_id, $section_id, $type, $text, $points, $position);
                            $question_stmt->execute();
                            $question_id = $conn->insert_id;
                            
                            // For multiple choice questions, add choices
                            if ($type === 'multiple_choice') {
                                $has_choices = false;
                                $has_correct = false;
                                
                                for ($i = 0; $i < 4; $i++) {
                                    $choice_text = $data[4 + ($i * 2)];
                                    $is_correct = !empty($data[5 + ($i * 2)]) && strtolower($data[5 + ($i * 2)]) === 'true';
                                    
                                    if (!empty($choice_text)) {
                                        $has_choices = true;
                                        if ($is_correct) {
                                            $has_correct = true;
                                        }
                                        
                                        $choice_stmt->bind_param("isi", $question_id, $choice_text, $is_correct);
                                        $choice_stmt->execute();
                                    }
                                }
                                
                                // Validate multiple choice questions
                                if (!$has_choices) {
                                    throw new Exception("Row $row_num is a multiple choice question without any choices.");
                                }
                                
                                if (!$has_correct) {
                                    throw new Exception("Row $row_num is a multiple choice question without any correct answers.");
                                }
                            }
                            
                            $imported_count++;
                            $max_position = max($max_position, $position);
                        }
                        
                        // Close statements
                        $question_stmt->close();
                        $choice_stmt->close();
                        
                        // Commit transaction
                        $conn->commit();
                        $success_message = "Successfully imported $imported_count questions to the exam.";
                        
                    } catch (Exception $e) {
                        // Rollback transaction on error
                        $conn->rollback();
                        $errors[] = "Error on line $row_num: " . $e->getMessage();
                    }
                    
                    fclose($handle);
                } else {
                    $errors[] = "Could not open file for reading.";
                }
            }
        }
    }
    
    // Generate import template
    if (isset($_POST['generate_template'])) {
        $template_exam_id = isset($_POST['template_exam_id']) ? intval($_POST['template_exam_id']) : 0;
        
        // Create template file
        $filename = "question_import_template.csv";
        $template_path = "../temp/" . $filename;
        
        // Ensure temp directory exists
        if (!is_dir("../temp")) {
            mkdir("../temp", 0755, true);
        }
        
        $template_file = fopen($template_path, "w");
        
        if ($template_file) {
            // Write header
            $header = ["position", "type", "text", "points", "choice_1", "choice_1_correct", "choice_2", "choice_2_correct", "choice_3", "choice_3_correct", "choice_4", "choice_4_correct", "section_id"];
            fputcsv($template_file, $header);
            
            // Add sample rows
            $sample_rows = [
                [1, "multiple_choice", "What is 2+2?", 1, "3", "false", "4", "true", "5", "false", "None of the above", "false", ""],
                [2, "short_answer", "Briefly explain the concept of gravity.", 2, "", "", "", "", "", "", "", "", ""],
                [3, "essay", "Discuss the impacts of climate change on biodiversity.", 5, "", "", "", "", "", "", "", "", ""],
            ];
            
            // If a specific exam is selected, add section information
            if ($template_exam_id > 0) {
                $sections = [];
                $sections_sql = "SELECT id, title FROM sections WHERE exam_id = ? ORDER BY position";
                if ($stmt = $conn->prepare($sections_sql)) {
                    $stmt->bind_param("i", $template_exam_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $sections[] = $row;
                    }
                    $stmt->close();
                }
                
                if (!empty($sections)) {
                    // Add section information to the template
                    fputcsv($template_file, ["# Section Information for Exam:", $template_exam_id]);
                    foreach ($sections as $section) {
                        fputcsv($template_file, ["# Section:", $section['id'], $section['title']]);
                    }
                    fputcsv($template_file, ["# End Section Information"]);
                    fputcsv($template_file, []);
                    
                    // Add a section ID to a sample row
                    if (isset($sections[0])) {
                        $sample_rows[0][12] = $sections[0]['id']; // Add section ID to first sample row
                    }
                }
            }
            
            // Write sample rows
            foreach ($sample_rows as $row) {
                fputcsv($template_file, $row);
            }
            
            fclose($template_file);
            $import_template_created = true;
        } else {
            $errors[] = "Could not create template file.";
        }
    }
    
    // Export questions
    if (isset($_POST['export'])) {
        $export_exam_id = isset($_POST['export_exam_id']) ? intval($_POST['export_exam_id']) : 0;
        
        if ($export_exam_id <= 0) {
            $errors[] = "Please select an exam to export questions from.";
        } else {
            // Get exam information
            $exam_info = null;
            $exam_sql = "SELECT title FROM exams WHERE id = ?";
            if ($stmt = $conn->prepare($exam_sql)) {
                $stmt->bind_param("i", $export_exam_id);
                $stmt->execute();
                $stmt->bind_result($exam_title);
                if ($stmt->fetch()) {
                    $exam_info = ['id' => $export_exam_id, 'title' => $exam_title];
                }
                $stmt->close();
            }
            
            if (!$exam_info) {
                $errors[] = "Selected exam does not exist.";
            } else {
                // Get questions for this exam
                $questions = [];
                $questions_sql = "SELECT * FROM questions WHERE exam_id = ? ORDER BY position";
                if ($stmt = $conn->prepare($questions_sql)) {
                    $stmt->bind_param("i", $export_exam_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $questions[] = $row;
                    }
                    $stmt->close();
                }
                
                // Create export file
                $sanitized_title = sanitizeFilename($exam_info['title']);
                $filename = "questions_export_{$sanitized_title}.csv";
                $export_path = "../temp/" . $filename;
                
                // Ensure temp directory exists
                if (!is_dir("../temp")) {
                    mkdir("../temp", 0755, true);
                }
                
                $export_file = fopen($export_path, "w");
                
                if ($export_file) {
                    // Write header
                    $header = ["position", "type", "text", "points", "choice_1", "choice_1_correct", "choice_2", "choice_2_correct", "choice_3", "choice_3_correct", "choice_4", "choice_4_correct", "section_id"];
                    fputcsv($export_file, $header);
                    
                    // Process each question
                    foreach ($questions as $question) {
                        $row = [
                            $question['position'],
                            $question['type'],
                            $question['text'],
                            $question['points'],
                            "", "", "", "", "", "", "", "",
                            $question['section_id']
                        ];
                        
                        // If multiple choice, get choices
                        if ($question['type'] === 'multiple_choice') {
                            $choices = [];
                            $choices_sql = "SELECT text, is_correct FROM choices WHERE question_id = ? ORDER BY id";
                            if ($stmt = $conn->prepare($choices_sql)) {
                                $stmt->bind_param("i", $question['id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                while ($choice = $result->fetch_assoc()) {
                                    $choices[] = $choice;
                                }
                                $stmt->close();
                            }
                            
                            // Add up to 4 choices to the row
                            for ($i = 0; $i < min(count($choices), 4); $i++) {
                                $row[4 + ($i * 2)] = $choices[$i]['text'];
                                $row[5 + ($i * 2)] = $choices[$i]['is_correct'] ? 'true' : 'false';
                            }
                        }
                        
                        fputcsv($export_file, $row);
                    }
                    
                    fclose($export_file);
                    $export_file_created = true;
                } else {
                    $errors[] = "Could not create export file.";
                }
            }
        }
    }
}

// Set page title
$page_title = 'Import/Export Questions';
include '../includes/header.php';

// Helper function to sanitize filenames
function sanitizeFilename($filename) {
    // Replace spaces and unwanted characters
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    // Remove multiple consecutive underscores
    $filename = preg_replace('/_+/', '_', $filename);
    // Trim underscores from beginning and end
    $filename = trim($filename, '_');
    // If filename is empty, use a default name
    if (empty($filename)) {
        $filename = 'exam_questions';
    }
    return $filename;
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/exams.php">Exams</a></li>
                    <?php if ($exam): ?>
                        <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/edit_exam.php?id=<?php echo $exam_id; ?>">Edit Exam</a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page">Import/Export Questions</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-file-import me-2"></i> Import/Export Questions
            </h1>
            <p class="lead">
                Easily import and export questions in bulk using CSV files.
                <?php if ($exam): ?>
                    Currently working with: <strong><?php echo htmlspecialchars($exam['title']); ?></strong>
                <?php endif; ?>
            </p>
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
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($import_template_created): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> Template file created successfully.
            <a href="<?php echo SITE_URL; ?>/temp/question_import_template.csv" class="btn btn-outline-primary btn-sm ms-2" download>
                <i class="fas fa-download me-1"></i> Download Template
            </a>
        </div>
    <?php endif; ?>
    
    <?php if ($export_file_created): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> Questions exported successfully.
            <a href="<?php echo SITE_URL; ?>/temp/questions_export_<?php echo sanitizeFilename($exam_info['title']); ?>.csv" class="btn btn-outline-primary btn-sm ms-2" download>
                <i class="fas fa-download me-1"></i> Download CSV
            </a>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-6">
            <!-- Import Questions Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-upload me-2"></i> Import Questions</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="import_exam_id" class="form-label">Select Exam <span class="text-danger">*</span></label>
                            <select class="form-select" id="import_exam_id" name="import_exam_id" required>
                                <option value="">-- Select Exam --</option>
                                <?php foreach ($all_exams as $exam_item): ?>
                                    <option value="<?php echo $exam_item['id']; ?>" <?php echo ($exam_id == $exam_item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam_item['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select the exam you want to import questions into.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="import_file" class="form-label">Upload CSV File <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" id="import_file" name="import_file" accept=".csv" required>
                            <div class="form-text">Upload a CSV file with questions. <a href="#" data-bs-toggle="modal" data-bs-target="#formatModal">View required format</a>.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="import" class="btn btn-primary">
                                <i class="fas fa-file-import me-1"></i> Import Questions
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <p class="mb-2">Need a template file?</p>
                    <form method="post">
                        <div class="mb-3">
                            <select class="form-select form-select-sm" name="template_exam_id">
                                <option value="0">Generic Template</option>
                                <?php foreach ($all_exams as $exam_item): ?>
                                    <option value="<?php echo $exam_item['id']; ?>" <?php echo ($exam_id == $exam_item['id']) ? 'selected' : ''; ?>>
                                        Template for: <?php echo htmlspecialchars($exam_item['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="generate_template" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-file me-1"></i> Generate Template
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <!-- Export Questions Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-file-download me-2"></i> Export Questions</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="export_exam_id" class="form-label">Select Exam <span class="text-danger">*</span></label>
                            <select class="form-select" id="export_exam_id" name="export_exam_id" required>
                                <option value="">-- Select Exam --</option>
                                <?php foreach ($all_exams as $exam_item): ?>
                                    <option value="<?php echo $exam_item['id']; ?>" <?php echo ($exam_id == $exam_item['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($exam_item['title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Select the exam you want to export questions from.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="include_choices" name="include_choices" checked>
                                <label class="form-check-label" for="include_choices">
                                    Include answer choices
                                </label>
                            </div>
                            <div class="form-text">Include answer choices for multiple choice questions.</div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="export" class="btn btn-info">
                                <i class="fas fa-file-download me-1"></i> Export Questions
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <div class="d-flex align-items-center">
                        <div class="small text-muted me-auto">
                            You can export questions to modify them in bulk and then import them back.
                        </div>
                        <a href="<?php echo SITE_URL; ?>/admin/exams.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Back to Exams
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Format Information Card -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> CSV Format Information</h5>
                </div>
                <div class="card-body">
                    <p>The CSV file should have the following columns:</p>
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Column</th>
                                <th>Description</th>
                                <th>Required</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>position</td>
                                <td>Question order number</td>
                                <td>No (auto-assigned)</td>
                            </tr>
                            <tr>
                                <td>type</td>
                                <td>Question type (multiple_choice, short_answer, essay)</td>
                                <td>Yes</td>
                            </tr>
                            <tr>
                                <td>text</td>
                                <td>Question text</td>
                                <td>Yes</td>
                            </tr>
                            <tr>
                                <td>points</td>
                                <td>Points value for the question</td>
                                <td>No (default: 1)</td>
                            </tr>
                            <tr>
                                <td>choice_1, choice_1_correct</td>
                                <td>First choice text and if it's correct (true/false)</td>
                                <td>For multiple choice only</td>
                            </tr>
                            <tr>
                                <td>section_id</td>
                                <td>Section ID if the question belongs to a section</td>
                                <td>No</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Format Modal -->
<div class="modal fade" id="formatModal" tabindex="-1" aria-labelledby="formatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formatModalLabel">CSV File Format</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6>Required Format</h6>
                <p>Your CSV file must have these columns in this order:</p>
                <code>position,type,text,points,choice_1,choice_1_correct,choice_2,choice_2_correct,choice_3,choice_3_correct,choice_4,choice_4_correct,section_id</code>
                
                <h6 class="mt-4">Example Rows</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>position</th>
                                <th>type</th>
                                <th>text</th>
                                <th>points</th>
                                <th>choice_1</th>
                                <th>choice_1_correct</th>
                                <th>choice_2</th>
                                <th>choice_2_correct</th>
                                <th>...</th>
                                <th>section_id</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>multiple_choice</td>
                                <td>What is 2+2?</td>
                                <td>1</td>
                                <td>3</td>
                                <td>false</td>
                                <td>4</td>
                                <td>true</td>
                                <td>...</td>
                                <td></td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>short_answer</td>
                                <td>Explain gravity</td>
                                <td>2</td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td>...</td>
                                <td>1</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <h6 class="mt-4">Important Notes</h6>
                <ul>
                    <li>The first row must contain the column headers exactly as shown above.</li>
                    <li>For multiple choice questions, you must provide at least one choice and mark at least one choice as correct (true).</li>
                    <li>Valid question types are: multiple_choice, short_answer, essay</li>
                    <li>If section_id is provided, it must be a valid section ID for the selected exam.</li>
                    <li>If position is not specified, questions will be added in order at the end of the exam.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <form method="post">
                    <input type="hidden" name="template_exam_id" value="0">
                    <button type="submit" name="generate_template" class="btn btn-primary">
                        <i class="fas fa-file me-1"></i> Generate Template
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>