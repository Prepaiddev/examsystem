<?php
// Include configuration
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    $_SESSION['error_message'] = 'You must be logged in as a student to access this page.';
    header('Location: ' . SITE_URL . '/login.php');
    exit;
}

// Get student information
$student_id = $_SESSION['user_id'];
$student_query = "SELECT * FROM users WHERE id = ? AND role = 'student'";
$stmt = mysqli_prepare($conn, $student_query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = 'Student not found.';
    header('Location: ' . SITE_URL . '/logout.php');
    exit;
}

$student = mysqli_fetch_assoc($result);

// Process form submission
$update_success = false;
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $matric_number = trim($_POST['matric_number']);
    $level = trim($_POST['level']);
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($username) || empty($email) || empty($matric_number) || empty($level)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email format.';
    } elseif (!isValidMatricNumber($matric_number)) {
        $error_message = 'Invalid matric number format.';
    } else {
        // Check if username or email already exists for a different user
        $check_query = "SELECT id FROM users WHERE (username = ? OR email = ? OR matric_number = ?) AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'sssi', $username, $email, $matric_number, $student_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Username, email, or matric number is already in use by another account.';
        } else {
            // Start transaction
            mysqli_begin_transaction($conn);
            $transaction_success = true;
            
            // Update basic profile information
            $update_query = "UPDATE users SET username = ?, email = ?, matric_number = ?, level = ? WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'ssssi', $username, $email, $matric_number, $level, $student_id);
            
            if (!mysqli_stmt_execute($update_stmt)) {
                $error_message = 'Failed to update profile information: ' . mysqli_error($conn);
                $transaction_success = false;
            }
            
            // Update password if provided
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    $error_message = 'New passwords do not match.';
                    $transaction_success = false;
                } else {
                    // Verify current password
                    if (!password_verify($current_password, $student['password_hash'])) {
                        $error_message = 'Current password is incorrect.';
                        $transaction_success = false;
                    } else {
                        // Hash new password and update
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $password_update_query = "UPDATE users SET password_hash = ? WHERE id = ?";
                        $password_stmt = mysqli_prepare($conn, $password_update_query);
                        mysqli_stmt_bind_param($password_stmt, 'si', $password_hash, $student_id);
                        
                        if (!mysqli_stmt_execute($password_stmt)) {
                            $error_message = 'Failed to update password: ' . mysqli_error($conn);
                            $transaction_success = false;
                        }
                    }
                }
            }
            
            // Commit or rollback transaction
            if ($transaction_success) {
                mysqli_commit($conn);
                $update_success = true;
                
                // Update session data
                $_SESSION['username'] = $username;
                
                // Refresh student data
                $result = mysqli_query($conn, "SELECT * FROM users WHERE id = $student_id");
                $student = mysqli_fetch_assoc($result);
            } else {
                mysqli_rollback($conn);
            }
        }
    }
}

// Set page title
$page_title = 'My Profile';

// Include header
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h1 class="display-5 fw-bold">
                <i class="fas fa-user-circle me-2"></i> My Profile
            </h1>
            <p class="lead">Update your personal information and password.</p>
        </div>
    </div>

    <?php if ($update_success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> Your profile has been updated successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="matric_number" class="form-label">Matric Number</label>
                                <input type="text" class="form-control" id="matric_number" name="matric_number" value="<?php echo htmlspecialchars($student['matric_number']); ?>" required>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="level" class="form-label">Level</label>
                                <select class="form-select" id="level" name="level" required>
                                    <option value="">Select your level</option>
                                    <option value="100" <?php echo $student['level'] === '100' ? 'selected' : ''; ?>>100 Level</option>
                                    <option value="200" <?php echo $student['level'] === '200' ? 'selected' : ''; ?>>200 Level</option>
                                    <option value="300" <?php echo $student['level'] === '300' ? 'selected' : ''; ?>>300 Level</option>
                                    <option value="400" <?php echo $student['level'] === '400' ? 'selected' : ''; ?>>400 Level</option>
                                    <option value="500" <?php echo $student['level'] === '500' ? 'selected' : ''; ?>>500 Level</option>
                                    <option value="PG" <?php echo $student['level'] === 'PG' ? 'selected' : ''; ?>>Postgraduate</option>
                                </select>
                            </div>
                        </div>
                        
                        <hr class="my-4">
                        <h6 class="mb-3">Change Password (Leave blank to keep current password)</h6>
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                            <div class="form-text">Enter your current password to authorize changes.</div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo SITE_URL; ?>/student/dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-center mb-4">
                        <div class="avatar-circle">
                            <i class="fas fa-user-graduate fa-4x text-primary"></i>
                        </div>
                    </div>
                    
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <span class="fw-bold">Account Type:</span> 
                            <span class="badge bg-primary">Student</span>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold">Joined:</span> 
                            <?php echo formatDate($student['created_at']); ?>
                        </li>
                        <li class="list-group-item">
                            <span class="fw-bold">Display Theme:</span> 
                            <?php echo ucfirst($theme ?? 'light'); ?>
                        </li>
                    </ul>
                    
                    <div class="mt-3">
                        <a href="<?php echo SITE_URL; ?>/toggle_theme.php" class="btn btn-sm btn-outline-primary d-block">
                            <?php if ($theme === 'dark'): ?>
                                <i class="fas fa-sun me-1"></i> Switch to Light Mode
                            <?php else: ?>
                                <i class="fas fa-moon me-1"></i> Switch to Dark Mode
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-key me-2"></i> Password Tips</h5>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>Use at least 8 characters</li>
                        <li>Include uppercase and lowercase letters</li>
                        <li>Include numbers and special characters</li>
                        <li>Avoid using common words or patterns</li>
                        <li>Don't reuse passwords from other sites</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>