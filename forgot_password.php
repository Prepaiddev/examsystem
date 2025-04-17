<?php
$page_title = 'Forgot Password';
require_once 'config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } else {
        redirect(SITE_URL . '/student/dashboard.php');
    }
}

$success = false;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    // Validate email
    if (empty($email)) {
        $error = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $conn = getDbConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = 'No account found with that email address.';
        } else {
            $user = $result->fetch_assoc();
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Delete any existing reset tokens for this user
            $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
            $deleteStmt->bind_param("i", $user['id']);
            $deleteStmt->execute();
            $deleteStmt->close();
            
            // Store new token
            $tokenStmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
            $tokenStmt->bind_param("iss", $user['id'], $token, $expires);
            
            if ($tokenStmt->execute()) {
                // In a real application, you would send an email here
                // For this demo, we'll just display the reset link
                $resetLink = SITE_URL . '/reset_password.php?token=' . $token;
                $success = true;
            } else {
                $error = 'Something went wrong. Please try again later.';
            }
            
            $tokenStmt->close();
        }
        
        $stmt->close();
        $conn->close();
    }
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
                        <p>Password reset instructions have been sent to your email address.</p>
                        <p class="mb-0">
                            <strong>Note:</strong> In a real deployment, an email would be sent. For this demo, use the following link:
                        </p>
                        <div class="mt-2 p-2 bg-light rounded">
                            <a href="<?php echo $resetLink; ?>"><?php echo $resetLink; ?></a>
                        </div>
                    </div>
                    <p class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Back to Login</a>
                    </p>
                <?php else: ?>
                    <p>
                        Enter your email address below and we'll send you a link to reset your password.
                    </p>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <p class="mb-0">Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>