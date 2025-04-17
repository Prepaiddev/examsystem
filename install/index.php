<?php
// Initialize variables
$db_host = 'localhost';
$db_name = '';
$db_user = '';
$db_pass = '';
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$errors = [];
$success = false;
$message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Database configuration step
    if (isset($_POST['db_setup'])) {
        $db_host = trim($_POST['db_host']);
        $db_name = trim($_POST['db_name']);
        $db_user = trim($_POST['db_user']);
        $db_pass = $_POST['db_pass'];
        
        // Validate inputs
        if (empty($db_host)) {
            $errors[] = "Database host is required";
        }
        if (empty($db_name)) {
            $errors[] = "Database name is required";
        }
        if (empty($db_user)) {
            $errors[] = "Database username is required";
        }
        
        // If no errors, test database connection
        if (empty($errors)) {
            // Try to connect to the database
            try {
                $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                
                if ($conn->connect_error) {
                    $errors[] = "Database connection failed: " . $conn->connect_error;
                } else {
                    // Connection successful, save configuration to file
                    $config_content = "<?php
/**
 * Database connection file
 * This file handles the database connection for the entire application
 */

// Database configuration
define('DB_HOST', '{$db_host}');
define('DB_USER', '{$db_user}');
define('DB_PASS', '{$db_pass}');
define('DB_NAME', '{$db_name}');

// Create database connection
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (\$conn->connect_error) {
    // If using in production, don't show the actual error (security risk)
    \$error_message = \"Database connection failed. Please contact the administrator.\";
    
    // For development, you might want to see the actual error
    if (defined('DEV_MODE') && DEV_MODE === true) {
        \$error_message = \"Connection failed: \" . \$conn->connect_error;
    }
    
    // Log the error
    error_log(\"Database connection error: \" . \$conn->connect_error);
    
    // Store the error in a session variable for display
    session_start();
    \$_SESSION['db_error'] = \$error_message;
    
    // Redirect to an error page or homepage with error parameter
    header(\"Location: ../index.php?error=db_connection\");
    exit();
}

// Set character set
\$conn->set_charset(\"utf8mb4\");

// Function to sanitize inputs (to prevent SQL injection)
function sanitize_input(\$conn, \$data) {
    \$data = trim(\$data);
    \$data = stripslashes(\$data);
    \$data = htmlspecialchars(\$data);
    return \$conn->real_escape_string(\$data);
}

// Function to handle database errors
function db_error(\$conn, \$query) {
    \$error = \"Query error: \" . \$conn->error;
    error_log(\$error . \" in query: \" . \$query);
    return \$error;
}

// Function to execute a query and return the result
function db_query(\$conn, \$query) {
    \$result = \$conn->query(\$query);
    if (!\$result) {
        return db_error(\$conn, \$query);
    }
    return \$result;
}

// Function to fetch all rows from a result as an associative array
function db_fetch_all(\$result) {
    return \$result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch a single row as an associative array
function db_fetch_assoc(\$result) {
    return \$result->fetch_assoc();
}

// Function to get the number of rows in a result
function db_num_rows(\$result) {
    return \$result->num_rows;
}

// Function to get the ID of the last inserted row
function db_insert_id(\$conn) {
    return \$conn->insert_id;
}

// Function to close the database connection
function db_close(\$conn) {
    \$conn->close();
}
?>";

                    $config_file = '../config/database.php';
                    if (file_put_contents($config_file, $config_content)) {
                        $success = true;
                        $step = 2; // Move to the next step
                    } else {
                        $errors[] = "Failed to write configuration file. Check file permissions.";
                    }
                    
                    $conn->close();
                }
            } catch (Exception $e) {
                $errors[] = "Connection error: " . $e->getMessage();
            }
        }
    }
    
    // Database tables creation step
    if (isset($_POST['create_tables']) && $step == 2) {
        // Include the database configuration
        require_once '../config/database.php';
        
        // SQL for creating tables
        $sql_queries = [
            // Users table
            "CREATE TABLE IF NOT EXISTS `users` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(64) NOT NULL UNIQUE,
                `email` VARCHAR(120) NOT NULL UNIQUE,
                `password_hash` VARCHAR(256) NOT NULL,
                `role` VARCHAR(20) NOT NULL DEFAULT 'student',
                `matric_number` VARCHAR(20) UNIQUE,
                `level` VARCHAR(20),
                `theme_preference` VARCHAR(10) DEFAULT 'light',
                `last_login` DATETIME,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Courses table
            "CREATE TABLE IF NOT EXISTS `courses` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(20) NOT NULL UNIQUE,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT,
                `instructor_id` INT(11),
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // User-Course relationship table
            "CREATE TABLE IF NOT EXISTS `user_courses` (
                `user_id` INT(11) NOT NULL,
                `course_id` INT(11) NOT NULL,
                PRIMARY KEY (`user_id`, `course_id`),
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Exams table
            "CREATE TABLE IF NOT EXISTS `exams` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT,
                `duration_minutes` INT(11) NOT NULL,
                `start_date` DATETIME,
                `end_date` DATETIME,
                `course_id` INT(11),
                `published` BOOLEAN DEFAULT FALSE,
                `passing_score` FLOAT DEFAULT 60.0,
                `has_sections` BOOLEAN DEFAULT FALSE,
                `randomize_questions` BOOLEAN DEFAULT FALSE,
                `browser_security` BOOLEAN DEFAULT FALSE,
                `allow_browser_warnings` BOOLEAN DEFAULT TRUE,
                `max_violations` INT(11) DEFAULT 3,
                `assessment_type` VARCHAR(20) DEFAULT 'exam',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Sections table
            "CREATE TABLE IF NOT EXISTS `sections` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `exam_id` INT(11) NOT NULL,
                `title` VARCHAR(200) NOT NULL,
                `description` TEXT,
                `duration_minutes` INT(11) NOT NULL,
                `position` INT(11) NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Questions table
            "CREATE TABLE IF NOT EXISTS `questions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `exam_id` INT(11) NOT NULL,
                `section_id` INT(11),
                `type` VARCHAR(20) NOT NULL,
                `text` TEXT NOT NULL,
                `points` INT(11) DEFAULT 1,
                `position` INT(11) NOT NULL,
                `contains_math` BOOLEAN DEFAULT FALSE,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Choices table
            "CREATE TABLE IF NOT EXISTS `choices` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `question_id` INT(11) NOT NULL,
                `text` TEXT NOT NULL,
                `is_correct` BOOLEAN DEFAULT FALSE,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Exam attempts table
            "CREATE TABLE IF NOT EXISTS `exam_attempts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `exam_id` INT(11) NOT NULL,
                `student_id` INT(11) NOT NULL,
                `started_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `completed_at` DATETIME,
                `score` FLOAT,
                `is_graded` BOOLEAN DEFAULT FALSE,
                `passed` BOOLEAN,
                `current_section_id` INT(11),
                `security_violations` INT(11) DEFAULT 0,
                `security_warnings` INT(11) DEFAULT 0,
                `security_log` TEXT,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`exam_id`) REFERENCES `exams`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`current_section_id`) REFERENCES `sections`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Section attempts table
            "CREATE TABLE IF NOT EXISTS `section_attempts` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `attempt_id` INT(11) NOT NULL,
                `section_id` INT(11) NOT NULL,
                `started_at` DATETIME,
                `completed_at` DATETIME,
                `time_remaining_seconds` INT(11),
                PRIMARY KEY (`id`),
                FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`section_id`) REFERENCES `sections`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Answers table
            "CREATE TABLE IF NOT EXISTS `answers` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `attempt_id` INT(11) NOT NULL,
                `question_id` INT(11) NOT NULL,
                `selected_choice_id` INT(11),
                `text_answer` TEXT,
                `score` FLOAT,
                `is_graded` BOOLEAN DEFAULT FALSE,
                `grader_feedback` TEXT,
                `marked_for_review` BOOLEAN DEFAULT FALSE,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`attempt_id`) REFERENCES `exam_attempts`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`selected_choice_id`) REFERENCES `choices`(`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Reported questions table
            "CREATE TABLE IF NOT EXISTS `reported_questions` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `question_id` INT(11) NOT NULL,
                `user_id` INT(11) NOT NULL,
                `reason` TEXT NOT NULL,
                `status` VARCHAR(20) DEFAULT 'pending',
                `admin_response` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
                FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",
            
            // Insert default admin user
            "INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `created_at`) 
             VALUES ('admin', 'admin@example.com', '" . password_hash('admin123', PASSWORD_DEFAULT) . "', 'admin', NOW())
             ON DUPLICATE KEY UPDATE `email` = 'admin@example.com';",
            
            // Insert default student user
            "INSERT INTO `users` (`username`, `email`, `password_hash`, `role`, `matric_number`, `level`, `created_at`) 
             VALUES ('student', 'student@example.com', '" . password_hash('student123', PASSWORD_DEFAULT) . "', 'student', 'STU12345', '200', NOW())
             ON DUPLICATE KEY UPDATE `email` = 'student@example.com';"
        ];
        
        // Execute each query
        $failed_queries = [];
        foreach ($sql_queries as $query) {
            if (!$conn->query($query)) {
                $failed_queries[] = $conn->error . " in query: " . substr($query, 0, 100) . "...";
            }
        }
        
        if (empty($failed_queries)) {
            $success = true;
            $message = "Database tables created successfully!";
            $step = 3; // Move to completion step
        } else {
            $errors = $failed_queries;
            $message = "Some database tables could not be created.";
        }
    }
}

// Function to display errors
function display_errors($errors) {
    if (!empty($errors)) {
        echo '<div class="alert alert-danger">';
        echo '<ul class="mb-0">';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}

// Function to display success message
function display_success($message) {
    if (!empty($message)) {
        echo '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Exam System - Installation</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .install-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .card {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            border: none;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            position: relative;
        }
        .step.active {
            background-color: #0d6efd;
            color: white;
        }
        .step.completed {
            background-color: #198754;
            color: white;
        }
        .step-connector {
            height: 2px;
            flex-grow: 1;
            background-color: #dee2e6;
            margin-top: 15px;
        }
        .step-connector.completed {
            background-color: #198754;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="logo">
            <h1>PHP Exam System</h1>
            <p class="text-muted">Installation Wizard</p>
        </div>
        
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">1</div>
            <div class="step-connector <?php echo $step > 1 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">2</div>
            <div class="step-connector <?php echo $step > 2 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">3</div>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <?php 
                    if ($step == 1) {
                        echo 'Step 1: Database Configuration';
                    } elseif ($step == 2) {
                        echo 'Step 2: Create Database Tables';
                    } else {
                        echo 'Step 3: Installation Complete';
                    }
                    ?>
                </h4>
            </div>
            <div class="card-body">
                <?php 
                // Display errors or success message
                display_errors($errors);
                if ($success) {
                    display_success($message);
                }
                
                // Step 1: Database Configuration Form
                if ($step == 1): 
                ?>
                <p>Please enter your database connection details:</p>
                <form action="?step=1" method="post">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($db_host); ?>" required>
                        <small class="text-muted">Usually "localhost"</small>
                    </div>
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($db_name); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($db_user); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($db_pass); ?>">
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="db_setup" class="btn btn-primary">Test Connection & Continue</button>
                    </div>
                </form>
                
                <?php 
                // Step 2: Create Database Tables
                elseif ($step == 2): 
                ?>
                <p>Database connection has been established successfully. Now, let's create the necessary tables:</p>
                <form action="?step=2" method="post">
                    <div class="alert alert-info">
                        <strong>Note:</strong> This step will create all required tables for the exam system, including:
                        <ul>
                            <li>Users table (with admin and student roles)</li>
                            <li>Courses, Exams, and Questions</li>
                            <li>Exam attempts and responses</li>
                            <li>And other related tables</li>
                        </ul>
                        <p class="mb-0">It will also create default admin and student accounts for testing.</p>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" name="create_tables" class="btn btn-primary">Create Tables</button>
                    </div>
                </form>
                
                <?php 
                // Step 3: Installation Complete
                else: 
                ?>
                <div class="text-center">
                    <i class="fas fa-check-circle text-success" style="font-size: 64px;"></i>
                    <h3 class="mt-3">Installation Complete!</h3>
                    <p>The PHP Exam System has been successfully installed.</p>
                    <div class="alert alert-success">
                        <strong>Default Login Credentials:</strong>
                        <hr>
                        <p><strong>Admin:</strong> Username: admin, Password: admin123</p>
                        <p class="mb-0"><strong>Student:</strong> Username: student, Password: student123</p>
                    </div>
                    <p>Please change these default passwords after your first login for security reasons.</p>
                    <div class="d-grid gap-2">
                        <a href="../index.php" class="btn btn-primary">Go to Homepage</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-muted">
                <div class="row">
                    <div class="col-6">
                        <?php if ($step > 1): ?>
                        <a href="?step=<?php echo $step - 1; ?>" class="btn btn-outline-secondary">&larr; Back</a>
                        <?php endif; ?>
                    </div>
                    <div class="col-6 text-end">
                        <?php if ($step < 3 && $success): ?>
                        <a href="?step=<?php echo $step + 1; ?>" class="btn btn-outline-primary">Skip &rarr;</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-4 text-center text-muted">
            <p>PHP Exam System &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>