<?php
/**
 * Simple setup script for PHP Exam System
 * 
 * This script initializes the database tables and creates default admin/student accounts.
 * It bypasses the installer for easier deployment on cPanel environments.
 */

// Include configuration
require_once 'config/config.php';

// Create tables if they don't exist
$tables = [
    // Users table
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `username` varchar(64) NOT NULL,
        `email` varchar(120) NOT NULL,
        `password` varchar(256) NOT NULL,
        `role` varchar(20) NOT NULL DEFAULT 'student',
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `theme_preference` varchar(10) NOT NULL DEFAULT 'light',
        `matric_number` varchar(20) DEFAULT NULL,
        `level` varchar(20) DEFAULT NULL,
        `last_active` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`),
        UNIQUE KEY `matric_number` (`matric_number`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Courses table
    "CREATE TABLE IF NOT EXISTS `courses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `code` varchar(20) NOT NULL,
        `title` varchar(200) NOT NULL,
        `description` text,
        `instructor_id` int(11) DEFAULT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `code` (`code`),
        KEY `instructor_id` (`instructor_id`),
        CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // User-Course relationship
    "CREATE TABLE IF NOT EXISTS `user_courses` (
        `user_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `enrolled_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`user_id`,`course_id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `user_courses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `user_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Exams table
    "CREATE TABLE IF NOT EXISTS `exams` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(200) NOT NULL,
        `description` text,
        `duration_minutes` int(11) NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `published` tinyint(1) NOT NULL DEFAULT '0',
        `start_date` datetime DEFAULT NULL,
        `end_date` datetime DEFAULT NULL,
        `course_id` int(11) DEFAULT NULL,
        `passing_score` float NOT NULL DEFAULT '60',
        `has_sections` tinyint(1) NOT NULL DEFAULT '0',
        `randomize_questions` tinyint(1) NOT NULL DEFAULT '0',
        `browser_security` tinyint(1) NOT NULL DEFAULT '0',
        `allow_browser_warnings` tinyint(1) NOT NULL DEFAULT '1',
        `max_violations` int(11) NOT NULL DEFAULT '3',
        `assessment_type` varchar(20) NOT NULL DEFAULT 'exam',
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `exams_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Sections table
    "CREATE TABLE IF NOT EXISTS `sections` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) NOT NULL,
        `title` varchar(200) NOT NULL,
        `description` text,
        `duration_minutes` int(11) NOT NULL,
        `position` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `exam_id` (`exam_id`),
        CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Questions table
    "CREATE TABLE IF NOT EXISTS `questions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) NOT NULL,
        `section_id` int(11) DEFAULT NULL,
        `type` varchar(20) NOT NULL,
        `text` text NOT NULL,
        `points` int(11) NOT NULL DEFAULT '1',
        `position` int(11) NOT NULL,
        `contains_math` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `exam_id` (`exam_id`),
        KEY `section_id` (`section_id`),
        CONSTRAINT `questions_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
        CONSTRAINT `questions_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Choices table
    "CREATE TABLE IF NOT EXISTS `choices` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `question_id` int(11) NOT NULL,
        `text` text NOT NULL,
        `is_correct` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `question_id` (`question_id`),
        CONSTRAINT `choices_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Exam Attempts table
    "CREATE TABLE IF NOT EXISTS `exam_attempts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `exam_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `started_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `completed_at` datetime DEFAULT NULL,
        `score` float DEFAULT NULL,
        `is_graded` tinyint(1) NOT NULL DEFAULT '0',
        `passed` tinyint(1) DEFAULT NULL,
        `current_section_id` int(11) DEFAULT NULL,
        `security_violations` int(11) NOT NULL DEFAULT '0',
        `security_warnings` int(11) NOT NULL DEFAULT '0',
        `security_log` text,
        PRIMARY KEY (`id`),
        KEY `exam_id` (`exam_id`),
        KEY `student_id` (`student_id`),
        KEY `current_section_id` (`current_section_id`),
        CONSTRAINT `exam_attempts_ibfk_1` FOREIGN KEY (`exam_id`) REFERENCES `exams` (`id`) ON DELETE CASCADE,
        CONSTRAINT `exam_attempts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `exam_attempts_ibfk_3` FOREIGN KEY (`current_section_id`) REFERENCES `sections` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Section Attempts table
    "CREATE TABLE IF NOT EXISTS `section_attempts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `attempt_id` int(11) NOT NULL,
        `section_id` int(11) NOT NULL,
        `started_at` datetime DEFAULT NULL,
        `completed_at` datetime DEFAULT NULL,
        `time_remaining_seconds` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `attempt_id` (`attempt_id`),
        KEY `section_id` (`section_id`),
        CONSTRAINT `section_attempts_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
        CONSTRAINT `section_attempts_ibfk_2` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Answers table
    "CREATE TABLE IF NOT EXISTS `answers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `attempt_id` int(11) NOT NULL,
        `question_id` int(11) NOT NULL,
        `selected_choice_id` int(11) DEFAULT NULL,
        `text_answer` text,
        `score` float DEFAULT NULL,
        `is_graded` tinyint(1) NOT NULL DEFAULT '0',
        `grader_feedback` text,
        `marked_for_review` tinyint(1) NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `attempt_question` (`attempt_id`,`question_id`),
        KEY `question_id` (`question_id`),
        KEY `selected_choice_id` (`selected_choice_id`),
        CONSTRAINT `answers_ibfk_1` FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts` (`id`) ON DELETE CASCADE,
        CONSTRAINT `answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
        CONSTRAINT `answers_ibfk_3` FOREIGN KEY (`selected_choice_id`) REFERENCES `choices` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Reported Questions table
    "CREATE TABLE IF NOT EXISTS `reported_questions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `question_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `reason` text NOT NULL,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `status` varchar(20) NOT NULL DEFAULT 'pending',
        `admin_response` text,
        PRIMARY KEY (`id`),
        KEY `question_id` (`question_id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `reported_questions_ibfk_1` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
        CONSTRAINT `reported_questions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Activity Logs table
    "CREATE TABLE IF NOT EXISTS `activity_logs` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `action` varchar(255) NOT NULL,
        `target_type` varchar(50) NOT NULL,
        `target_id` varchar(50) NOT NULL,
        `details` text,
        `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$success = true;
$messages = [];

// Create each table
foreach ($tables as $sql) {
    if (!$conn->query($sql)) {
        $success = false;
        $messages[] = "Error creating table: " . $conn->error;
    }
}

// Create default users if they don't exist
$admin_exists = false;
$student_exists = false;

// Check if admin exists
$check_admin = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check_admin->bind_param("s", $admin_username);
$admin_username = 'admin';
$check_admin->execute();
$check_admin->store_result();
$admin_exists = $check_admin->num_rows > 0;
$check_admin->close();

// Check if student exists
$check_student = $conn->prepare("SELECT id FROM users WHERE username = ?");
$check_student->bind_param("s", $student_username);
$student_username = 'student';
$check_student->execute();
$check_student->store_result();
$student_exists = $check_student->num_rows > 0;
$check_student->close();

// Create admin if doesn't exist
if (!$admin_exists) {
    $create_admin = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
    $create_admin->bind_param("ssss", $username, $email, $password, $role);
    
    $username = 'admin';
    $email = 'admin@example.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $role = USER_ROLE_ADMIN;
    
    if (!$create_admin->execute()) {
        $success = false;
        $messages[] = "Error creating admin user: " . $create_admin->error;
    } else {
        $messages[] = "Admin user created successfully.";
    }
    
    $create_admin->close();
}

// Create student if doesn't exist
if (!$student_exists) {
    $create_student = $conn->prepare("INSERT INTO users (username, email, password, role, matric_number, level) VALUES (?, ?, ?, ?, ?, ?)");
    $create_student->bind_param("ssssss", $username, $email, $password, $role, $matric, $level);
    
    $username = 'student';
    $email = 'student@example.com';
    $password = password_hash('student123', PASSWORD_DEFAULT);
    $role = USER_ROLE_STUDENT;
    $matric = 'STU12345';
    $level = '300';
    
    if (!$create_student->execute()) {
        $success = false;
        $messages[] = "Error creating student user: " . $create_student->error;
    } else {
        $messages[] = "Student user created successfully.";
    }
    
    $create_student->close();
}

// Create a sample course if no courses exist
$course_count = 0;
$course_check = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($row = $course_check->fetch_assoc()) {
    $course_count = $row['count'];
}

if ($course_count == 0) {
    // Get admin user ID
    $admin_id = null;
    $admin_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $admin_query->bind_param("s", $admin_username);
    $admin_username = 'admin';
    $admin_query->execute();
    $admin_query->bind_result($admin_id);
    $admin_query->fetch();
    $admin_query->close();
    
    if ($admin_id) {
        $create_course = $conn->prepare("INSERT INTO courses (code, title, description, instructor_id) VALUES (?, ?, ?, ?)");
        $create_course->bind_param("sssi", $code, $title, $description, $admin_id);
        
        $code = 'CS101';
        $title = 'Introduction to Computer Science';
        $description = 'This course introduces the fundamentals of computer science, including programming, algorithms, and data structures.';
        
        if (!$create_course->execute()) {
            $success = false;
            $messages[] = "Error creating sample course: " . $create_course->error;
        } else {
            $course_id = $conn->insert_id;
            $messages[] = "Sample course created successfully.";
            
            // Enroll the student in the course
            $student_id = null;
            $student_query = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $student_query->bind_param("s", $student_username);
            $student_username = 'student';
            $student_query->execute();
            $student_query->bind_result($student_id);
            $student_query->fetch();
            $student_query->close();
            
            if ($student_id) {
                $enroll_student = $conn->prepare("INSERT INTO user_courses (user_id, course_id) VALUES (?, ?)");
                $enroll_student->bind_param("ii", $student_id, $course_id);
                
                if (!$enroll_student->execute()) {
                    $messages[] = "Error enrolling student in course: " . $enroll_student->error;
                } else {
                    $messages[] = "Student enrolled in sample course.";
                }
                
                $enroll_student->close();
            }
        }
        
        $create_course->close();
    }
}

// Output setup results
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Exam System Setup</title>
    <!-- Bootstrap CSS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .setup-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e3e6f0;
        }
    </style>
</head>
<body>
    <div class="container setup-container">
        <div class="header">
            <h1 class="mb-3"><?php echo SITE_NAME; ?> Setup</h1>
            <p class="text-muted">Database and initial system configuration</p>
        </div>
        
        <div class="setup-status">
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-check-circle me-2"></i> Setup Completed Successfully!</h4>
                    <p>The system has been successfully configured and is ready to use.</p>
                </div>
            <?php else: ?>
                <div class="alert alert-danger" role="alert">
                    <h4 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Setup Encountered Errors</h4>
                    <p>There were some issues during the setup process. Please review the details below.</p>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i> Setup Details</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($messages as $message): ?>
                            <li class="list-group-item">
                                <?php echo htmlspecialchars($message); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i> Default Login Credentials</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Admin User</h5>
                                    <p class="card-text"><strong>Username:</strong> admin</p>
                                    <p class="card-text"><strong>Password:</strong> admin123</p>
                                    <div class="alert alert-warning small">
                                        <i class="fas fa-exclamation-triangle me-1"></i> Change these credentials after login!
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h5 class="card-title">Student User</h5>
                                    <p class="card-text"><strong>Username:</strong> student</p>
                                    <p class="card-text"><strong>Password:</strong> student123</p>
                                    <p class="card-text"><strong>Matric Number:</strong> STU12345</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home me-2"></i> Go to Homepage
                </a>
                <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-secondary">
                    <i class="fas fa-sign-in-alt me-2"></i> Login Now
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>