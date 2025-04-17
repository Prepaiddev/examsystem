<?php
$page_title = 'Terms of Service';
require_once 'config/config.php';
include 'includes/header.php';
?>

<div class="container">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <h1 class="text-center mb-4">Terms of Service</h1>
            
            <div class="card mb-4">
                <div class="card-body">
                    <p class="lead">
                        These Terms of Service ("Terms") govern your access to and use of the <?php echo SITE_NAME; ?>.
                        Please read these Terms carefully before using the system.
                    </p>
                    
                    <h2>Acceptance of Terms</h2>
                    <p>
                        By accessing or using the <?php echo SITE_NAME; ?>, you agree to be bound by these Terms and 
                        our Privacy Policy. If you do not agree to these Terms, you may not access or use the service.
                    </p>
                    
                    <h2>Account Registration and Security</h2>
                    <p>
                        To use the <?php echo SITE_NAME; ?>, you must register for an account. You agree to provide 
                        accurate and complete information when registering and to keep your account information up to date.
                    </p>
                    
                    <p>
                        You are responsible for maintaining the security of your account and password. You agree not to 
                        disclose your password to any third party. You must notify us immediately of any breach of security 
                        or unauthorized use of your account.
                    </p>
                    
                    <h2>Academic Integrity</h2>
                    <p>
                        You agree to use the system for its intended purpose of legitimate educational assessment. The 
                        following actions are strictly prohibited:
                    </p>
                    <ul>
                        <li>Sharing account credentials with others</li>
                        <li>Having someone else take an exam on your behalf</li>
                        <li>Using unauthorized resources during closed-book examinations</li>
                        <li>Attempting to circumvent the system's security measures</li>
                        <li>Screen capturing, recording, or sharing exam content</li>
                        <li>Exploiting technical vulnerabilities in the system</li>
                        <li>Any other form of cheating or academic dishonesty</li>
                    </ul>
                    <p>
                        Violations of academic integrity may result in immediate termination of your account, academic 
                        disciplinary actions, and potential legal consequences.
                    </p>
                    
                    <h2>Security Monitoring</h2>
                    <p>
                        You acknowledge that during examinations, the system may implement various security measures, 
                        including but not limited to:
                    </p>
                    <ul>
                        <li>Full-screen mode enforcement</li>
                        <li>Detection of tab or window switching</li>
                        <li>Prevention of copy-paste functions</li>
                        <li>Browser activity monitoring</li>
                        <li>Logging of potential security violations</li>
                    </ul>
                    <p>
                        These measures are designed solely to maintain academic integrity during assessments.
                    </p>
                    
                    <h2>Intellectual Property Rights</h2>
                    <p>
                        The <?php echo SITE_NAME; ?>, including its content, features, and functionality, are owned by 
                        the institution or its licensors and are protected by copyright, trademark, and other intellectual 
                        property laws.
                    </p>
                    
                    <p>
                        You may not reproduce, distribute, modify, create derivative works of, publicly display, publicly 
                        perform, republish, download, store, or transmit any materials from the system, except as necessary 
                        for your personal, non-commercial use.
                    </p>
                    
                    <h2>User Content</h2>
                    <p>
                        When you submit content to the system (such as exam answers, essay responses, or reported questions), 
                        you grant the institution a non-exclusive, royalty-free license to use, reproduce, and store that 
                        content for educational and assessment purposes.
                    </p>
                    
                    <h2>Prohibited Uses</h2>
                    <p>You agree not to use the <?php echo SITE_NAME; ?>:</p>
                    <ul>
                        <li>In any way that violates any applicable law or regulation</li>
                        <li>To attempt to gain unauthorized access to any part of the system</li>
                        <li>To interfere with the proper working of the system</li>
                        <li>To introduce any viruses, trojans, worms, or other harmful material</li>
                        <li>To automate access or actions within the system</li>
                    </ul>
                    
                    <h2>Termination</h2>
                    <p>
                        We may terminate or suspend your account and access to the system immediately, without prior notice 
                        or liability, for any reason, including but not limited to a breach of these Terms.
                    </p>
                    
                    <h2>Disclaimer of Warranties</h2>
                    <p>
                        The system is provided "as is" and "as available" without any warranties of any kind, either 
                        express or implied. We do not guarantee that the system will be uninterrupted, timely, secure, 
                        or error-free.
                    </p>
                    
                    <h2>Limitation of Liability</h2>
                    <p>
                        To the maximum extent permitted by law, the institution shall not be liable for any indirect, 
                        incidental, special, consequential, or punitive damages arising out of or in connection with 
                        your use of the system.
                    </p>
                    
                    <h2>Changes to Terms</h2>
                    <p>
                        We may revise these Terms from time to time. The most current version will always be posted on 
                        this page. By continuing to use the system after revisions become effective, you agree to be bound 
                        by the revised Terms.
                    </p>
                    
                    <h2>Governing Law</h2>
                    <p>
                        These Terms shall be governed by and construed in accordance with the laws of the jurisdiction 
                        where the institution is located, without regard to its conflict of law provisions.
                    </p>
                    
                    <h2>Contact Information</h2>
                    <p>
                        If you have any questions about these Terms, please contact us at:
                    </p>
                    <p>
                        <i class="fas fa-envelope me-2"></i> Email: terms@examonline.com
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