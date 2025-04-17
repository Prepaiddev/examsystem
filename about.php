<?php
/**
 * About Page
 */
require_once 'config/config.php';

// Set page title
$page_title = 'About';

// Include header
include 'includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1 class="display-5 fw-bold">
            <i class="fas fa-info-circle me-2"></i> About <?php echo SITE_NAME; ?>
        </h1>
        <p class="lead">A comprehensive online examination platform designed for educational institutions.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="mb-4">Our Exam System</h2>
                
                <h5 class="mt-4 mb-3">Overview</h5>
                <p>
                    <?php echo SITE_NAME; ?> is a state-of-the-art online examination system designed to provide a secure, reliable, and user-friendly platform for conducting assessments in educational institutions. Our system combines advanced technology with pedagogical best practices to create an ideal testing environment.
                </p>
                
                <h5 class="mt-4 mb-3">Key Features</h5>
                <div class="row">
                    <div class="col-md-6">
                        <ul class="list-group mb-4">
                            <li class="list-group-item">
                                <i class="fas fa-shield-alt text-primary me-2"></i> 
                                <strong>Advanced Security</strong>
                                <p class="mb-0 small text-muted">Prevents tab switching, copy-paste, and maintains exam integrity</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-clock text-primary me-2"></i> 
                                <strong>Timed Assessments</strong>
                                <p class="mb-0 small text-muted">Configurable time limits with auto-submission</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-random text-primary me-2"></i> 
                                <strong>Question Randomization</strong>
                                <p class="mb-0 small text-muted">Unique question order for each student</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-save text-primary me-2"></i> 
                                <strong>Auto-Save Answers</strong>
                                <p class="mb-0 small text-muted">No work lost due to connection issues</p>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <ul class="list-group mb-4">
                            <li class="list-group-item">
                                <i class="fas fa-file-alt text-primary me-2"></i> 
                                <strong>Multiple Question Types</strong>
                                <p class="mb-0 small text-muted">Multiple choice, short answer, and essay questions</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-chart-bar text-primary me-2"></i> 
                                <strong>Comprehensive Reports</strong>
                                <p class="mb-0 small text-muted">Detailed analytics for instructors</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-users text-primary me-2"></i> 
                                <strong>Course Management</strong>
                                <p class="mb-0 small text-muted">Organize exams by courses and manage student enrollments</p>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-mobile-alt text-primary me-2"></i> 
                                <strong>Responsive Design</strong>
                                <p class="mb-0 small text-muted">Works on desktops, tablets, and mobile devices</p>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <h5 class="mt-4 mb-3">Our Philosophy</h5>
                <p>
                    We believe in creating assessments that are fair, accessible, and meaningful. Our platform is designed to:
                </p>
                <ul>
                    <li>Reduce exam anxiety with an intuitive interface</li>
                    <li>Provide immediate feedback when appropriate</li>
                    <li>Ensure academic integrity through modern security features</li>
                    <li>Support various assessment strategies and pedagogical approaches</li>
                    <li>Give instructors valuable insights into student performance</li>
                </ul>
                
                <div class="mt-5">
                    <h5 class="mb-3">Technical Specifications</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <th scope="row" style="width: 30%;">Supported Browsers</th>
                                    <td>Chrome, Firefox, Safari, Edge (latest 2 versions)</td>
                                </tr>
                                <tr>
                                    <th scope="row">Platform Requirements</th>
                                    <td>Any modern operating system (Windows, macOS, Linux, Android, iOS)</td>
                                </tr>
                                <tr>
                                    <th scope="row">Internet Requirements</th>
                                    <td>Stable internet connection (minimum 1 Mbps)</td>
                                </tr>
                                <tr>
                                    <th scope="row">Security</th>
                                    <td>HTTPS encryption, secure session management, hashed passwords</td>
                                </tr>
                                <tr>
                                    <th scope="row">Accessibility</th>
                                    <td>WCAG 2.1 AA compliant</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i> System Stats</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="h2 fw-bold text-primary"><?php echo getSystemStat('total_exams'); ?></div>
                        <div class="text-muted">Exams Created</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h2 fw-bold text-success"><?php echo getSystemStat('total_questions'); ?></div>
                        <div class="text-muted">Questions</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h2 fw-bold text-info"><?php echo getSystemStat('total_courses'); ?></div>
                        <div class="text-muted">Courses</div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="h2 fw-bold text-secondary"><?php echo getSystemStat('total_students'); ?></div>
                        <div class="text-muted">Students</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Frequently Asked Questions</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqOne">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                How secure is the exam system?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Our system employs multiple security measures including tab switching detection, copy-paste prevention, and time monitoring. However, we believe in a balanced approach that maintains integrity while reducing student stress.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                What happens if I lose internet during an exam?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Our auto-save feature saves your answers frequently. If you lose connection, simply reconnect and continue - your previous answers will be preserved. The timer continues to run during disconnections.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Can I report issues with questions?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, during the exam you can flag any question for review by clicking the "Report Question" button. Instructors will be notified and can address any issues.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faqFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                How are exams graded?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Multiple-choice questions are graded automatically. Short answer and essay questions require instructor grading, which is typically completed within the timeframe specified by your institution.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i> Contact Us</h5>
            </div>
            <div class="card-body">
                <p>Have questions or need assistance? Contact our support team:</p>
                <div class="d-grid gap-2">
                    <a href="mailto:support@example.com" class="btn btn-primary">
                        <i class="fas fa-envelope me-2"></i> Email Support
                    </a>
                    <a href="#" class="btn btn-outline-secondary">
                        <i class="fas fa-comments me-2"></i> Live Chat
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper function to get system stats
function getSystemStat($stat) {
    global $conn;
    
    switch ($stat) {
        case 'total_exams':
            $sql = "SELECT COUNT(*) as count FROM exams";
            break;
        case 'total_questions':
            $sql = "SELECT COUNT(*) as count FROM questions";
            break;
        case 'total_courses':
            $sql = "SELECT COUNT(*) as count FROM courses";
            break;
        case 'total_students':
            $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
            break;
        default:
            return 0;
    }
    
    $result = $conn->query($sql);
    if ($row = $result->fetch_assoc()) {
        return $row['count'];
    }
    
    return 0;
}

include 'includes/footer.php';
?>