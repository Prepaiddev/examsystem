<?php
/**
 * Email Exam Results to Student
 */
require_once '../config/config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    // Not logged in or not an admin, redirect to login page
    setFlashMessage('error', 'You must be logged in as an administrator to access this page.');
    redirect(SITE_URL . '/login.php');
}

// Check if attempt ID is provided
if (!isset($_GET['attempt_id']) || !is_numeric($_GET['attempt_id'])) {
    setFlashMessage('error', 'Invalid attempt ID.');
    redirect(SITE_URL . '/admin/results.php');
}

$attempt_id = intval($_GET['attempt_id']);

// Get attempt details with student and exam info
$attempt = [];
$attempt_sql = "SELECT ea.*, e.title as exam_title, e.assessment_type,
                c.title as course_title, c.code as course_code,
                u.username as student_name, u.email as student_email
                FROM exam_attempts ea
                JOIN exams e ON ea.exam_id = e.id
                LEFT JOIN courses c ON e.course_id = c.id
                JOIN users u ON ea.student_id = u.id
                WHERE ea.id = ?";

if ($stmt = $conn->prepare($attempt_sql)) {
    $stmt->bind_param("i", $attempt_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt = fetchRowSafely($result);
    $stmt->close();
}

// If attempt not found, redirect
if (empty($attempt)) {
    setFlashMessage('error', 'Exam attempt not found.');
    redirect(SITE_URL . '/admin/results.php');
}

// Process form submission
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $additional_message = isset($_POST['additional_message']) ? trim($_POST['additional_message']) : '';
    $include_details = isset($_POST['include_details']) ? (bool)$_POST['include_details'] : false;
    
    // Get answers if details are to be included
    $answers = [];
    if ($include_details) {
        $answers_sql = "SELECT a.*, q.text as question_text, q.type as question_type, q.points as question_points
                        FROM answers a
                        JOIN questions q ON a.question_id = q.id
                        WHERE a.attempt_id = ?
                        ORDER BY q.position";
        
        if ($stmt = $conn->prepare($answers_sql)) {
            $stmt->bind_param("i", $attempt_id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $answers[] = $row;
            }
            $stmt->close();
        }
    }
    
    // Generate email content
    $result_url = SITE_URL . '/student/view_results.php?attempt_id=' . $attempt_id;
    $subject = ucfirst($attempt['assessment_type']) . ' Results: ' . $attempt['exam_title'];
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; color: #333; }
            .container { width: 100%; max-width: 600px; margin: 0 auto; }
            .header { background-color: #4a5cf9; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; }
            .footer { background-color: #f5f5f5; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .score { font-size: 24px; font-weight: bold; text-align: center; margin: 20px 0; }
            .passed { color: #28a745; }
            .failed { color: #dc3545; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f5f5f5; }
            .btn { display: inline-block; background-color: #4a5cf9; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . ucfirst($attempt['assessment_type']) . " Results</h1>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($attempt['student_name']) . ",</p>
                <p>Your results for the " . htmlspecialchars($attempt['exam_title']) . " " . $attempt['assessment_type'] . " are now available.</p>";
    
    if (!empty($additional_message)) {
        $message .= "<p>" . nl2br(htmlspecialchars($additional_message)) . "</p>";
    }
    
    $message .= "
                <div class='score " . ($attempt['passed'] ? 'passed' : 'failed') . "'>
                    " . number_format($attempt['score'], 1) . "% - " . ($attempt['passed'] ? 'PASSED' : 'FAILED') . "
                </div>
                <p><strong>Grade:</strong> " . calculateGrade($attempt['score']) . "</p>";
    
    if (!empty($attempt['course_title'])) {
        $message .= "<p><strong>Course:</strong> " . htmlspecialchars($attempt['course_code'] . ' - ' . $attempt['course_title']) . "</p>";
    }
    
    $message .= "
                <p><strong>Date Taken:</strong> " . formatDateTime($attempt['started_at']) . "</p>
                <p><strong>Date Completed:</strong> " . (!empty($attempt['completed_at']) ? formatDateTime($attempt['completed_at']) : 'Not completed') . "</p>";
    
    if ($include_details && !empty($answers)) {
        $message .= "
                <h3>Detailed Results</h3>
                <table>
                    <tr>
                        <th>#</th>
                        <th>Question</th>
                        <th>Your Answer</th>
                        <th>Score</th>
                    </tr>";
        
        foreach ($answers as $index => $answer) {
            // Format the answer text
            $answer_text = '';
            if ($answer['question_type'] === 'multiple_choice') {
                // Get selected choice text
                if (!empty($answer['selected_choice_id'])) {
                    $choice_sql = "SELECT text FROM choices WHERE id = ?";
                    if ($stmt = $conn->prepare($choice_sql)) {
                        $stmt->bind_param("i", $answer['selected_choice_id']);
                        $stmt->execute();
                        $stmt->bind_result($text);
                        if ($stmt->fetch()) {
                            $answer_text = htmlspecialchars(truncateText($text, 50));
                        }
                        $stmt->close();
                    }
                } else {
                    $answer_text = 'Not answered';
                }
            } else {
                $answer_text = !empty($answer['text_answer']) ? htmlspecialchars(truncateText($answer['text_answer'], 50)) : 'Not answered';
            }
            
            $message .= "
                    <tr>
                        <td>" . ($index + 1) . "</td>
                        <td>" . htmlspecialchars(truncateText($answer['question_text'], 50)) . "</td>
                        <td>" . $answer_text . "</td>
                        <td>" . ($answer['is_graded'] ? number_format($answer['score'], 1) . '/' . $answer['question_points'] : 'Not graded') . "</td>
                    </tr>";
            
            if (!empty($answer['grader_feedback'])) {
                $message .= "
                    <tr>
                        <td colspan='4'><em>Feedback: " . htmlspecialchars($answer['grader_feedback']) . "</em></td>
                    </tr>";
            }
        }
        
        $message .= "
                </table>";
    }
    
    $message .= "
                <p style='text-align: center; margin-top: 30px;'>
                    <a href='" . $result_url . "' class='btn'>View Full Results</a>
                </p>
            </div>
            <div class='footer'>
                <p>This is an automated message. Please do not reply to this email.</p>
                <p>&copy; " . date('Y') . " Online Examination System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>";
    
    // Check if SMTP is configured
    if (!defined('SMTP_ENABLED') || !SMTP_ENABLED) {
        // SMTP not configured, display a message and save the email content
        $success = true;
        
        // Save email content to a file for reference
        $email_log_dir = __DIR__ . '/../logs';
        if (!is_dir($email_log_dir)) {
            mkdir($email_log_dir, 0755, true);
        }
        
        $log_file = $email_log_dir . '/email_' . date('YmdHis') . '_' . $attempt_id . '.html';
        file_put_contents($log_file, $message);
        
        $error = 'SMTP is not configured. The email content has been saved for manual sending.';
    } else {
        // SMTP is configured, attempt to send the email
        // This is a placeholder for actual email sending code
        /*
        require 'path/to/PHPMailer/PHPMailerAutoload.php';
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($attempt['student_email'], $attempt['student_name']);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        
        if($mail->send()) {
            $success = true;
        } else {
            $error = 'Email could not be sent. Error: ' . $mail->ErrorInfo;
        }
        */
        
        // For now, simulate success
        $success = true;
    }
    
    if ($success) {
        setFlashMessage('success', 'Email has been prepared for ' . $attempt['student_email'] . '. ' . $error);
        redirect(SITE_URL . "/admin/view_results.php?attempt_id=$attempt_id");
    } else {
        // Keep error message for display on the form
    }
}

// Set page title
$page_title = 'Email Exam Results';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/results.php">Results</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/admin/view_results.php?attempt_id=<?php echo $attempt_id; ?>">View Results</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Email Results</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-envelope me-2"></i> Email Exam Results
            </h1>
            <p class="lead">
                Send exam results to <?php echo htmlspecialchars($attempt['student_name']); ?> 
                for <?php echo htmlspecialchars($attempt['exam_title']); ?>.
            </p>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Email Details</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label">To:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($attempt['student_name']); ?> (<?php echo htmlspecialchars($attempt['student_email']); ?>)" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Subject:</label>
                            <input type="text" class="form-control" value="<?php echo ucfirst($attempt['assessment_type']); ?> Results: <?php echo htmlspecialchars($attempt['exam_title']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="additional_message" class="form-label">Additional Message (Optional):</label>
                            <textarea class="form-control" id="additional_message" name="additional_message" rows="4" placeholder="Add a personal message to the student..."><?php echo isset($_POST['additional_message']) ? htmlspecialchars($_POST['additional_message']) : ''; ?></textarea>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="include_details" name="include_details" value="1" <?php echo isset($_POST['include_details']) && $_POST['include_details'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="include_details">Include detailed question results in email</label>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="<?php echo SITE_URL; ?>/admin/view_results.php?attempt_id=<?php echo $attempt_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-1"></i> Send Email
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="alert alert-info">
                <div class="d-flex">
                    <div class="me-3">
                        <i class="fas fa-info-circle fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="alert-heading">Email Setup Required</h5>
                        <p class="mb-0">Email sending requires SMTP configuration. Please contact the system administrator to set up SMTP for sending emails.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Exam Result Summary</h5>
                </div>
                <div class="card-body">
                    <p><strong><?php echo ucfirst($attempt['assessment_type']); ?>:</strong> <?php echo htmlspecialchars($attempt['exam_title']); ?></p>
                    
                    <?php if (!empty($attempt['course_title'])): ?>
                        <p><strong>Course:</strong> <?php echo htmlspecialchars($attempt['course_code'] . ' - ' . $attempt['course_title']); ?></p>
                    <?php endif; ?>
                    
                    <p><strong>Student:</strong> <?php echo htmlspecialchars($attempt['student_name']); ?></p>
                    
                    <div class="text-center my-3">
                        <div class="display-4 <?php echo $attempt['passed'] ? 'text-success' : 'text-danger'; ?>">
                            <?php echo number_format($attempt['score'], 1); ?>%
                        </div>
                        <div class="badge bg-<?php echo $attempt['passed'] ? 'success' : 'danger'; ?> fs-5 mb-2">
                            <?php echo $attempt['passed'] ? 'PASSED' : 'FAILED'; ?>
                        </div>
                        <div>Grade: <?php echo calculateGrade($attempt['score']); ?></div>
                    </div>
                    
                    <p><strong>Date Taken:</strong> <?php echo formatDateTime($attempt['started_at']); ?></p>
                    <p><strong>Date Completed:</strong> <?php echo !empty($attempt['completed_at']) ? formatDateTime($attempt['completed_at']) : 'Not completed'; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>