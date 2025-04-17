<?php
/**
 * Documentation Index Page
 */
require_once '../config/config.php';

$page_title = 'System Documentation';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Documentation</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-book me-2"></i> Online Examination System Documentation
            </h1>
            <p class="lead">
                Comprehensive guides and resources for students, instructors, and administrators
            </p>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-user-graduate fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">For Students</h3>
                            <p class="text-muted mb-0">Guides for exam takers</p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="student_guide.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Getting Started
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="taking_exams.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Taking Exams
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="exam_timer.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Exam Timer Tutorial
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="image_upload_feature.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Image Upload Feature
                            <span class="badge bg-primary rounded-pill">New</span>
                        </a>
                        <a href="viewing_results.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Viewing Results
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-chalkboard-teacher fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">For Instructors</h3>
                            <p class="text-muted mb-0">Tools for educators</p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="instructor_overview.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            System Overview
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="creating_exams.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Creating Exams
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="question_management.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Question Management
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="grading_system.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Grading System
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="results_analysis.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Results Analysis
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="flex-shrink-0 me-3">
                            <div class="bg-info rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                                <i class="fas fa-tools fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="mb-0">For Administrators</h3>
                            <p class="text-muted mb-0">System management</p>
                        </div>
                    </div>
                    
                    <div class="list-group list-group-flush">
                        <a href="system_requirements.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            System Requirements
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="user_management.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            User Management
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="security_settings.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Security Settings
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="backup_restore.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Backup & Restore
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                        <a href="troubleshooting.php" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            Troubleshooting
                            <i class="fas fa-chevron-right text-muted"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-8">
            <!-- Feature Documentation -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Feature Documentation</h4>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-lock text-primary me-2"></i> Exam Security</h5>
                                    <p class="card-text">Learn about the advanced security features including browser monitoring, auto-submission, and violation tracking.</p>
                                    <a href="security_system.php" class="btn btn-outline-primary">Read More</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-clock text-primary me-2"></i> Timer System</h5>
                                    <p class="card-text">Detailed documentation on the exam timer, section timers, and automatic submission when time expires.</p>
                                    <a href="timer_system.php" class="btn btn-outline-primary">Read More</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-images text-primary me-2"></i> Image Upload</h5>
                                    <p class="card-text">How to use the image upload feature for enhancing short answer and essay responses.</p>
                                    <a href="image_upload_feature.php" class="btn btn-outline-primary">Read More</a>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fas fa-file-export text-primary me-2"></i> Results Export</h5>
                                    <p class="card-text">Instructions for exporting, printing, and emailing exam results in various formats.</p>
                                    <a href="results_export.php" class="btn btn-outline-primary">Read More</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Latest Updates -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Latest Updates</h4>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Image Upload Feature Added</h5>
                                <small class="text-muted">April 1, 2025</small>
                            </div>
                            <p class="mb-1">Students can now upload images with their short answer and essay responses to enhance their explanations with visual elements.</p>
                            <small><a href="image_upload_feature.php">Read documentation</a></small>
                        </div>
                        
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Email Results Feature</h5>
                                <small class="text-muted">March 25, 2025</small>
                            </div>
                            <p class="mb-1">Instructors can now email exam results directly to students, with options to include detailed feedback and question breakdowns.</p>
                            <small><a href="results_export.php">Learn more</a></small>
                        </div>
                        
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Bulk Import/Export Questions</h5>
                                <small class="text-muted">March 15, 2025</small>
                            </div>
                            <p class="mb-1">New functionality for importing and exporting questions in bulk using CSV files for easier exam creation and management.</p>
                            <small><a href="question_management.php">View documentation</a></small>
                        </div>
                        
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">Security System Enhancements</h5>
                                <small class="text-muted">March 5, 2025</small>
                            </div>
                            <p class="mb-1">Improved exam security with enhanced tab switching detection and auto-submission when maximum violations are reached.</p>
                            <small><a href="security_system.php">Read more</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Quick Start Guides -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Quick Start Guides</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="student_quick_start.php" class="btn btn-outline-success">
                            <i class="fas fa-user-graduate me-2"></i> Student Quick Start
                        </a>
                        <a href="instructor_quick_start.php" class="btn btn-outline-success">
                            <i class="fas fa-chalkboard-teacher me-2"></i> Instructor Quick Start
                        </a>
                        <a href="admin_quick_start.php" class="btn btn-outline-success">
                            <i class="fas fa-user-shield me-2"></i> Administrator Quick Start
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Video Tutorials -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Video Tutorials</h4>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-play-circle text-danger me-3 fa-lg"></i>
                            <div>
                                <div class="fw-bold">System Overview</div>
                                <small class="text-muted">5:30 mins</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-play-circle text-danger me-3 fa-lg"></i>
                            <div>
                                <div class="fw-bold">Creating Your First Exam</div>
                                <small class="text-muted">8:45 mins</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-play-circle text-danger me-3 fa-lg"></i>
                            <div>
                                <div class="fw-bold">Taking an Exam</div>
                                <small class="text-muted">6:20 mins</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-play-circle text-danger me-3 fa-lg"></i>
                            <div>
                                <div class="fw-bold">Grading and Feedback</div>
                                <small class="text-muted">7:15 mins</small>
                            </div>
                        </a>
                        <a href="#" class="list-group-item list-group-item-action d-flex align-items-center">
                            <i class="fas fa-play-circle text-danger me-3 fa-lg"></i>
                            <div>
                                <div class="fw-bold">Advanced Features</div>
                                <small class="text-muted">10:30 mins</small>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="video_tutorials.php" class="btn btn-sm btn-outline-secondary">View All Videos</a>
                </div>
            </div>
            
            <!-- Useful Resources -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h4 class="mb-0">Useful Resources</h4>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-file-pdf text-danger me-3"></i>
                            <a href="resources/user_manual.pdf" class="text-decoration-none">Complete User Manual (PDF)</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-file-alt text-primary me-3"></i>
                            <a href="faq.php" class="text-decoration-none">Frequently Asked Questions</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-download text-success me-3"></i>
                            <a href="resources/quickref_card.pdf" class="text-decoration-none">Quick Reference Card</a>
                        </li>
                        <li class="list-group-item d-flex align-items-center">
                            <i class="fas fa-globe text-info me-3"></i>
                            <a href="glossary.php" class="text-decoration-none">System Terminology Glossary</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Documentation-specific styles */
.card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php include '../includes/footer.php'; ?>