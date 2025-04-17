<?php
// Start session
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect to appropriate dashboard based on user role
    if ($_SESSION['user_role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: student/dashboard.php");
    }
    exit();
}

// Include database connection
require_once 'config/database.php';

// Initialize variables
$username = $email = $matric_number = $level = "";
$password = $confirm_password = "";
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = sanitize_input($conn, $_POST['username']);
    $email = sanitize_input($conn, $_POST['email']);
    $matric_number = sanitize_input($conn, $_POST['matric_number']);
    $level = sanitize_input($conn, $_POST['level']);
    $password = $_POST['password']; // Don't sanitize password before hashing
    $confirm_password = $_POST['confirm_password'];
    
    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 4 || strlen($username) > 64) {
        $errors[] = "Username must be between 4 and 64 characters";
    } else {
        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = '$username'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
    }
    
    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    } else {
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = '$email'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // Validate matric number
    if (empty($matric_number)) {
        $errors[] = "Matric Number is required";
    } else {
        // Check if matric number already exists
        $query = "SELECT id FROM users WHERE matric_number = '$matric_number'";
        $result = $conn->query($query);
        if ($result && $result->num_rows > 0) {
            $errors[] = "Matric Number already registered";
        }
    }
    
    // Validate level
    if (empty($level)) {
        $errors[] = "Level is required";
    }
    
    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    // Validate password confirmation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into the database
        $query = "INSERT INTO users (username, email, password_hash, matric_number, level, role, created_at) 
                  VALUES ('$username', '$email', '$password_hash', '$matric_number', '$level', 'student', NOW())";
        
        if ($conn->query($query)) {
            // Registration successful, set session message
            $_SESSION['success_message'] = "Registration successful! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again later.";
            error_log("Registration error: " . $conn->error);
        }
    }
}

// Include header
$page_title = "Register";
include_once 'includes/header.php';
?>

<div class="container register-container">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow-lg border-0 rounded-lg mt-5">
                <div class="card-header bg-primary text-white">
                    <h3 class="text-center font-weight-light my-4">Create Account</h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo htmlspecialchars($username); ?>" required>
                                    <small class="form-text text-muted">Between 4-64 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="matric_number" class="form-label">Matric Number</label>
                                    <input type="text" class="form-control" id="matric_number" name="matric_number" 
                                           value="<?php echo htmlspecialchars($matric_number); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="level" class="form-label">Level</label>
                                    <select class="form-select" id="level" name="level" required>
                                        <option value="" <?php echo empty($level) ? 'selected' : ''; ?>>Select Level</option>
                                        <option value="100" <?php echo $level === '100' ? 'selected' : ''; ?>>100 Level</option>
                                        <option value="200" <?php echo $level === '200' ? 'selected' : ''; ?>>200 Level</option>
                                        <option value="300" <?php echo $level === '300' ? 'selected' : ''; ?>>300 Level</option>
                                        <option value="400" <?php echo $level === '400' ? 'selected' : ''; ?>>400 Level</option>
                                        <option value="500" <?php echo $level === '500' ? 'selected' : ''; ?>>500 Level</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">At least 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-block">Register</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    <div class="small">
                        Already have an account? <a href="login.php">Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>