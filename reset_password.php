<?php
$page_title = 'Reset Password';
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/student/dashboard.php');
    }
}

$token = isset($_GET['token']) ? $_GET['token'] : '';
$validToken = false;
$userId = null;
$success = false;
$error = null;

// Validate token
if (!empty($token)) {
    $conn = getDbConnection();
    
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $resetData = $result->fetch_assoc();
        
        // Check if token is expired
        if (strtotime($resetData['expires_at']) > time()) {
            $validToken = true;
            $userId = $resetData['user_id'];
        } else {
            $error = 'Password reset link has expired. Please request a new one.';
        }
    } else {
        $error = 'Invalid password reset link. Please request a new one.';
    }
    
    $stmt->close();
    
    // Handle form submission for password reset
    if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate passwords
        if (empty($password)) {
            $error = 'Password is required.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Update user's password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $updateStmt->bind_param("si", $passwordHash, $userId);
            
            if ($updateStmt->execute()) {
                // Delete all reset tokens for this user
                $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $deleteStmt->bind_param("i", $userId);
                $deleteStmt->execute();
                $deleteStmt->close();
                
                $success = true;
            } else {
                $error = 'Something went wrong. Please try again.';
            }
            
            $updateStmt->close();
        }
    }
    
    $conn->close();
} else {
    $error = 'No reset token provided.';
}

include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-6 mx-auto">
        <div class="card auth-card">
            <div class="card-header">
                <h4>Reset Your Password</h4>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p>Your password has been successfully reset!</p>
                    </div>
                    <p class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Login with Your New Password</a>
                    </p>
                <?php elseif ($validToken): ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] . '?token=' . $token); ?>" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            <div class="mt-2">
                                <div class="progress">
                                    <div id="password-strength-meter" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            <div class="invalid-feedback">Passwords must match.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <?php echo $error; ?>
                    </div>
                    <p class="text-center mt-3">
                        <a href="forgot_password.php" class="btn btn-outline-primary">Request New Reset Link</a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0"><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>