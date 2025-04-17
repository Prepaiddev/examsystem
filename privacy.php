<?php
$page_title = 'Privacy Policy';
require_once 'config/config.php';
include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-4">Privacy Policy</h1>
            
            <div class="card mb-4">
                <div class="card-body">
                    <p class="lead">
                        This Privacy Policy describes how your personal information is collected, used, and shared 
                        when you use the <?php echo SITE_NAME; ?>.
                    </p>
                    
                    <h2>Information We Collect</h2>
                    <p>
                        When you register for an account, we collect the information you provide to us, such as your name,
                        email address, and academic information like matric number and level.
                    </p>
                    
                    <p>
                        During your use of the system, we automatically collect certain information about your device
                        and how you interact with the application, including:
                    </p>
                    
                    <ul>
                        <li>Login times and activity</li>
                        <li>Exam attempt information</li>
                        <li>Browser and device information (for security purposes)</li>
                        <li>Time spent on assessments</li>
                    </ul>
                    
                    <h2>How We Use Your Information</h2>
                    <p>We use the information we collect to:</p>
                    <ul>
                        <li>Provide, maintain, and improve the examination system</li>
                        <li>Create and manage your account</li>
                        <li>Process and record assessment results</li>
                        <li>Generate analytics about academic performance</li>
                        <li>Ensure the security and integrity of exams</li>
                        <li>Detect and prevent fraud or cheating attempts</li>
                        <li>Communicate with you about your account and assessments</li>
                    </ul>
                    
                    <h2>Security Monitoring</h2>
                    <p>
                        During exams, the system may implement various security measures depending on the settings
                        chosen by the administrator, including:
                    </p>
                    <ul>
                        <li>Full-screen mode enforcement</li>
                        <li>Detection of tab or window switching</li>
                        <li>Logging of security events (without capturing personal content)</li>
                        <li>Prevention of copy-paste actions</li>
                    </ul>
                    
                    <p>
                        These security measures are intended solely to maintain academic integrity during assessments 
                        and are only active during examination sessions.
                    </p>
                    
                    <h2>Information Sharing</h2>
                    <p>
                        We share your personal information only with academic staff and administrators who have
                        legitimate educational interests in the data.
                    </p>
                    
                    <p>We may also share your information in the following circumstances:</p>
                    <ul>
                        <li>With your consent</li>
                        <li>To comply with legal obligations</li>
                        <li>To protect our rights, privacy, safety, or property</li>
                        <li>In connection with a transfer of assets (e.g., if the system is acquired by another institution)</li>
                    </ul>
                    
                    <h2>Data Retention</h2>
                    <p>
                        We will maintain your personal information for as long as necessary to fulfill the purposes
                        outlined in this Privacy Policy, unless a longer retention period is required for legitimate
                        educational record-keeping or to comply with legal obligations.
                    </p>
                    
                    <h2>Your Rights</h2>
                    <p>
                        Depending on your location, you may have certain rights regarding your personal information,
                        such as the right to:
                    </p>
                    <ul>
                        <li>Access the personal information we have about you</li>
                        <li>Correct inaccurate information</li>
                        <li>Request deletion of your information (subject to educational record requirements)</li>
                        <li>Receive a copy of your information in a usable format</li>
                        <li>Object to certain processing of your information</li>
                    </ul>
                    
                    <h2>Changes to This Policy</h2>
                    <p>
                        We may update this Privacy Policy from time to time to reflect changes in our practices or for
                        other operational, legal, or regulatory reasons.
                    </p>
                    
                    <h2>Contact Us</h2>
                    <p>
                        If you have any questions about this Privacy Policy or our data practices, please contact us at:
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i> Email: privacy@examonline.com
                    </p>
                    
                    <div class="mt-4">
                        <p class="text-muted">Last updated: <?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>