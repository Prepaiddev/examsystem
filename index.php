<?php
// Start session
session_start();

// Include header
$page_title = "Home";
include_once 'includes/header.php';
?>

<div class="container home-container my-5">
    <!-- Hero Section -->
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold">Powerful Features for Modern Assessments</h1>
            <p class="lead">A comprehensive online examination system designed for educational institutions.</p>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- User is logged in, show dashboard button -->
                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <a href="admin/dashboard.php" class="btn btn-primary btn-lg">Admin Dashboard</a>
                <?php else: ?>
                    <a href="student/dashboard.php" class="btn btn-primary btn-lg">Student Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <!-- User is not logged in, show login/register buttons -->
                <a href="login.php" class="btn btn-primary btn-lg">Log In</a>
                <a href="register.php" class="btn btn-outline-secondary btn-lg ms-2">Register</a>
            <?php endif; ?>
        </div>
        <div class="col-lg-6">
            <img src="assets/images/hero-illustration.svg" alt="Online Examination" class="img-fluid">
        </div>
    </div>
    
    <!-- Features Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">Powerful Features for Modern Assessments</h2>
        </div>
        
        <!-- Feature 1 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-shield-alt text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="card-title">Secure Examinations</h3>
                    <p class="card-text">Advanced security measures prevent cheating while maintaining a stress-free environment for students.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Tab switching detection</li>
                        <li><i class="fas fa-check text-success me-2"></i> Copy/paste prevention</li>
                        <li><i class="fas fa-check text-success me-2"></i> Time monitoring</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Feature 2 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-list-check text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="card-title">Flexible Question Types</h3>
                    <p class="card-text">Create diverse assessments with multiple question formats to test different skills.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Multiple choice questions</li>
                        <li><i class="fas fa-check text-success me-2"></i> Short answer questions</li>
                        <li><i class="fas fa-check text-success me-2"></i> Essay questions</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Feature 3 -->
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="icon-wrapper mb-3">
                        <i class="fas fa-chart-line text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h3 class="card-title">Comprehensive Analytics</h3>
                    <p class="card-text">Gain insights into student performance with detailed reports and statistics.</p>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-check text-success me-2"></i> Individual performance tracking</li>
                        <li><i class="fas fa-check text-success me-2"></i> Course-level analysis</li>
                        <li><i class="fas fa-check text-success me-2"></i> Question difficulty metrics</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- How It Works Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">How It Works</h2>
        </div>
        
        <!-- Step 1 -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="step-circle mb-3">1</div>
                    <h3>Create Exams</h3>
                    <p>Instructors can easily create exams with various question types, time limits, and settings.</p>
                </div>
            </div>
        </div>
        
        <!-- Step 2 -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="step-circle mb-3">2</div>
                    <h3>Take Assessments</h3>
                    <p>Students complete exams in a secure, user-friendly environment with real-time feedback.</p>
                </div>
            </div>
        </div>
        
        <!-- Step 3 -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="step-circle mb-3">3</div>
                    <h3>Review Results</h3>
                    <p>Both students and instructors can review detailed performance analytics and feedback.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Testimonial Section -->
    <div class="row mb-5">
        <div class="col-12 text-center mb-4">
            <h2 class="fw-bold">What Our Users Say</h2>
        </div>
        
        <div class="col-md-6 offset-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center p-4">
                    <i class="fas fa-quote-left fa-3x text-primary opacity-25 position-absolute start-0 top-0 m-4"></i>
                    <p class="lead my-4">The PHP Exam System has transformed how we conduct assessments. Our students love the intuitive interface, and our instructors appreciate the detailed analytics and time-saving features.</p>
                    <div class="d-flex justify-content-center">
                        <div>
                            <h5 class="mb-1">Dr. Sarah Johnson</h5>
                            <p class="text-muted">Department Chair, Computer Science</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Call to Action -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <div class="card bg-primary text-white border-0 shadow-lg">
                <div class="card-body p-5">
                    <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
                    <p class="lead mb-4">Join thousands of educational institutions using our system for secure, efficient assessments.</p>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="register.php" class="btn btn-light btn-lg">Create Your Account</a>
                    <?php else: ?>
                        <a href="<?php echo $_SESSION['user_role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'; ?>" class="btn btn-light btn-lg">Go to Dashboard</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom styles -->
<style>
    .step-circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: #0d6efd;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: bold;
        margin: 0 auto;
    }
    
    .home-container {
        padding-top: 2rem;
        padding-bottom: 3rem;
    }
    
    .card {
        transition: transform 0.3s ease-in-out;
    }
    
    .card:hover {
        transform: translateY(-5px);
    }
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>