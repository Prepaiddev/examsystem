<?php
/**
 * Help & Support Page
 */
require_once 'config/config.php';

$page_title = 'Help & Support';
include 'includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12 text-center">
            <h1 class="display-4 fw-bold">
                <i class="fas fa-life-ring me-2"></i> Help & Support
            </h1>
            <p class="lead">
                Get assistance and answers to your questions about the Examination System
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-search fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">Frequently Asked Questions</h3>
                            <p class="text-muted mb-0">Get quick answers to common questions</p>
                        </div>
                    </div>
                    
                    <div class="accordion" id="faqAccordion">
                        <!-- General Questions -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    <i class="fas fa-question-circle me-2"></i> General Questions
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <h5>What is the Online Examination System?</h5>
                                        <p>The Online Examination System is a comprehensive platform for creating, conducting, and evaluating assessments, exams, tests, and quizzes. It provides a secure environment for students to take exams online, while giving instructors powerful tools to create questions, grade responses, and analyze results.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>How do I reset my password?</h5>
                                        <p>If you forget your password, click on the "Forgot Password" link on the login page. Enter your registered email address, and you'll receive instructions to reset your password. If you don't receive the email, check your spam folder or contact the system administrator.</p>
                                    </div>
                                    
                                    <div>
                                        <h5>Can I use the system on mobile devices?</h5>
                                        <p>Yes, the Online Examination System is responsive and works on desktops, laptops, tablets, and smartphones. However, for the best experience, especially during exams with security features enabled, we recommend using a desktop or laptop computer.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- For Students -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    <i class="fas fa-user-graduate me-2"></i> For Students
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <h5>How do I take an exam?</h5>
                                        <p>To take an exam, log in to your student account and navigate to the "Exams" section. You'll see a list of available exams. Click on the exam you want to take, review the instructions, and click "Start Exam" when you're ready. Make sure to complete the exam before the time expires.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>What happens if I lose internet connection during an exam?</h5>
                                        <p>The system automatically saves your answers as you progress through the exam. If you lose internet connection, try to reconnect as soon as possible. When you log back in, you can continue from where you left off, as long as the exam time hasn't expired.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>Can I go back to review my answers?</h5>
                                        <p>Yes, you can navigate between questions using the question navigation panel. You can also mark questions for review and come back to them later before submitting the exam.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>What happens if I change tabs or try to open other applications during an exam?</h5>
                                        <p>If security features are enabled for the exam, the system will detect when you navigate away from the exam tab or try to access other applications. Depending on the exam settings, this may trigger a warning or be recorded as a security violation. Multiple violations may result in automatic submission of your exam.</p>
                                    </div>
                                    
                                    <div>
                                        <h5>How can I view my exam results?</h5>
                                        <p>After completing an exam, you may see your results immediately, depending on the exam settings. You can also view all your past exam results in the "Results" section of your dashboard.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- For Instructors/Admins -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    <i class="fas fa-chalkboard-teacher me-2"></i> For Instructors/Admins
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <h5>How do I create a new exam?</h5>
                                        <p>In the admin dashboard, navigate to "Exams" and click "Create New Exam." Fill out the basic exam information, add questions (multiple choice, short answer, essay), set time limits and security options, and publish the exam when it's ready for students.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>How do I grade essay or short answer questions?</h5>
                                        <p>After students complete an exam, go to the "Results" section in the admin dashboard. Find the student's attempt and click "Grade Exam." You'll see all the questions that require manual grading. Assign scores and provide feedback for each answer.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>Can I import questions from an external file?</h5>
                                        <p>Yes, you can import questions from CSV files. In the exam creation/editing page, look for the "Import Questions" option. Download the template file, fill it with your questions and answers, and upload it back to the system.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>How do I export exam results?</h5>
                                        <p>In the "Results" section, you can view individual student attempts or get an overview of all attempts. Click on "Export Results" to download the data in CSV format, which you can open in Excel or other spreadsheet applications.</p>
                                    </div>
                                    
                                    <div>
                                        <h5>How do I enable or disable security features?</h5>
                                        <p>When creating or editing an exam, you'll find security options in the "Settings" section. You can enable browser security features to prevent tab switching, set up maximum violation limits, and configure whether warnings are shown or violations are strictly enforced.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Technical Issues -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    <i class="fas fa-wrench me-2"></i> Technical Issues
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <div class="mb-3">
                                        <h5>What browsers are supported?</h5>
                                        <p>The Online Examination System works best with modern browsers like Google Chrome, Mozilla Firefox, Microsoft Edge, and Safari. Make sure your browser is updated to the latest version for optimal performance.</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <h5>I'm experiencing slow loading times. What should I do?</h5>
                                        <p>Slow loading times could be due to internet connectivity issues, browser extensions, or high server load. Try clearing your browser cache, disabling extensions, or using a different network connection. If the problem persists, contact the system administrator.</p>
                                    </div>
                                    
                                    <div>
                                        <h5>The exam timer isn't working correctly. What should I do?</h5>
                                        <p>Make sure your browser is updated and JavaScript is enabled. If there's still an issue with the timer, try refreshing the page or using a different browser. If the problem continues, contact the administrator immediately.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Support -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-headset fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">Contact Support</h3>
                            <p class="text-muted mb-0">Need further assistance? We're here to help!</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-envelope me-2"></i> Email Support</h5>
                                    <p class="card-text">Send us an email and we'll get back to you as soon as possible, usually within 24 hours.</p>
                                    <a href="mailto:support@examsystem.com" class="btn btn-primary">support@examsystem.com</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-phone-alt me-2"></i> Phone Support</h5>
                                    <p class="card-text">Call our support line during business hours (9am - 5pm, Monday to Friday).</p>
                                    <a href="tel:+1234567890" class="btn btn-primary">+1 (234) 567-890</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-comment-alt me-2"></i> Submit a Support Request</h5>
                                <form id="supportForm">
                                    <div class="mb-3">
                                        <label for="supportName" class="form-label">Your Name</label>
                                        <input type="text" class="form-control" id="supportName" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="supportEmail" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="supportEmail" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="supportCategory" class="form-label">Category</label>
                                        <select class="form-select" id="supportCategory" required>
                                            <option value="" selected disabled>Select a category</option>
                                            <option value="account">Account Issues</option>
                                            <option value="exam">Exam Problems</option>
                                            <option value="technical">Technical Support</option>
                                            <option value="feedback">Feedback & Suggestions</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="supportMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="supportMessage" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Documentation and Resources -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-4">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-book fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">Documentation & Resources</h3>
                            <p class="text-muted mb-0">Helpful guides and tutorials to get the most out of the system</p>
                        </div>
                    </div>
                    
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">User Guides</h5>
                                    <p class="card-text">Detailed documentation for students and instructors on how to use the system efficiently.</p>
                                    <a href="<?php echo SITE_URL; ?>/docs/" class="btn btn-outline-primary">View Guides</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Video Tutorials</h5>
                                    <p class="card-text">Step-by-step video guides to navigate the system and use its features.</p>
                                    <a href="#" class="btn btn-outline-primary">Watch Tutorials</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Best Practices</h5>
                                    <p class="card-text">Tips and recommendations for creating effective assessments and securing exams.</p>
                                    <a href="#" class="btn btn-outline-primary">Read Best Practices</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">System Updates</h5>
                                    <p class="card-text">Stay informed about the latest features, improvements, and bug fixes.</p>
                                    <a href="#" class="btn btn-outline-primary">View Changelog</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('supportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Thank you for your message! Our support team will contact you soon.');
    this.reset();
});
</script>

<?php include 'includes/footer.php'; ?>