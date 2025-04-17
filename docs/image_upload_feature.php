<?php
/**
 * Image Upload Feature Documentation
 */
require_once '../config/config.php';

$page_title = 'Image Upload Feature Documentation';
include '../includes/header.php';
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Home</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/docs/index.php">Documentation</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Image Upload Feature</li>
                </ol>
            </nav>
            
            <h1 class="display-5 fw-bold">
                <i class="fas fa-images me-2"></i> Image Upload Feature
            </h1>
            <p class="lead">
                Enhancing written answers with visual elements
            </p>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Overview Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title border-bottom pb-3">Overview</h2>
                    <p>
                        The Image Upload feature allows students to enhance their short answer and essay responses 
                        with visual content. This is particularly useful for mathematics, science, art, and other 
                        subjects where diagrams, graphs, or visual explanations can better demonstrate understanding.
                    </p>
                    
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <div class="me-3">
                                <i class="fas fa-info-circle fa-2x"></i>
                            </div>
                            <div>
                                <h5 class="alert-heading">Key Benefits</h5>
                                <ul class="mb-0">
                                    <li>Support complex explanations with visual aids</li>
                                    <li>Show mathematical workings or diagrams</li>
                                    <li>Submit hand-drawn sketches or diagrams</li>
                                    <li>Demonstrate practical applications</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- For Students Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title border-bottom pb-3">For Students</h2>
                    
                    <h4 class="mt-4">How to Upload an Image</h4>
                    <p>
                        When taking an exam with short answer or essay questions, you'll see an image upload 
                        option below the text answer field.
                    </p>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Step-by-Step Guide</h5>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0">
                                <li class="mb-3">
                                    <strong>Type your text answer</strong> in the answer field provided.
                                </li>
                                <li class="mb-3">
                                    <strong>Click on the "Choose File" button</strong> under the "Upload Image (Optional)" section.
                                </li>
                                <li class="mb-3">
                                    <strong>Select an image file</strong> from your device. Supported formats include JPEG, PNG, and GIF.
                                </li>
                                <li>
                                    The image will be <strong>automatically uploaded</strong> when selected, and you'll see a 
                                    confirmation message once complete.
                                </li>
                            </ol>
                        </div>
                    </div>
                    
                    <h4>Viewing Uploaded Images</h4>
                    <p>
                        After submitting your exam, you can view your uploaded images in two ways:
                    </p>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Results Summary</h5>
                                    <p class="card-text">
                                        In your results summary page, questions with image uploads will be marked with 
                                        an image icon. Click the "View" button to see the complete answer including the image.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Detailed Answer View</h5>
                                    <p class="card-text">
                                        When viewing a specific answer in detail, your uploaded image will be 
                                        displayed below your text response, with options to view the full-size version.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h4>Best Practices for Image Uploads</h4>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Do</th>
                                    <th>Don't</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Keep images clear and readable</td>
                                    <td>Upload blurry or low-quality images</td>
                                </tr>
                                <tr>
                                    <td>Ensure diagrams are well-labeled</td>
                                    <td>Submit images larger than 5MB</td>
                                </tr>
                                <tr>
                                    <td>Use images to supplement text answers</td>
                                    <td>Rely solely on images without explanation</td>
                                </tr>
                                <tr>
                                    <td>Verify images uploaded successfully</td>
                                    <td>Upload unrelated or inappropriate content</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- For Instructors Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title border-bottom pb-3">For Instructors</h2>
                    
                    <h4 class="mt-4">Grading Answers with Images</h4>
                    <p>
                        When grading short answer or essay questions, images uploaded by students will be 
                        displayed alongside their text responses.
                    </p>
                    
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Grading Process</h5>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0">
                                <li class="mb-3">
                                    <strong>Navigate to the grading page</strong> for an exam attempt.
                                </li>
                                <li class="mb-3">
                                    For answers with uploaded images, you'll see the <strong>image displayed below the text response</strong>.
                                </li>
                                <li class="mb-3">
                                    You can <strong>click "View Full Size"</strong> to examine the image in greater detail.
                                </li>
                                <li>
                                    <strong>Assign a score</strong> and provide feedback based on both the text and visual components 
                                    of the student's answer.
                                </li>
                            </ol>
                        </div>
                    </div>
                    
                    <h4>Assessment Considerations</h4>
                    <p>
                        When creating exams that may benefit from image uploads, consider the following:
                    </p>
                    
                    <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Clear Instructions</h5>
                                    <p class="card-text">
                                        Specify in the question text when students should include images, diagrams, 
                                        or other visual elements in their answers.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Grading Criteria</h5>
                                    <p class="card-text">
                                        Establish clear criteria for how images will be evaluated alongside 
                                        text responses, and communicate this to students.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Image Requirements</h5>
                                    <p class="card-text">
                                        Specify any requirements for images, such as resolution, formatting, 
                                        or labeling expectations.
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Alternative Methods</h5>
                                    <p class="card-text">
                                        Consider providing alternative submission methods for students who 
                                        may have technical difficulties with image uploads.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Technical Specifications -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title border-bottom pb-3">Technical Specifications</h2>
                    
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <h5>Supported File Types</h5>
                            <ul>
                                <li>JPEG (.jpg, .jpeg)</li>
                                <li>PNG (.png)</li>
                                <li>GIF (.gif)</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>File Size Limits</h5>
                            <ul>
                                <li>Maximum file size: 5MB</li>
                                <li>Recommended: Under 2MB for optimal loading</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <h5>Storage and Security</h5>
                            <ul>
                                <li>Images are stored securely on the server</li>
                                <li>Only authorized users can access uploaded images</li>
                                <li>Images are associated with specific exam attempts</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5>Browser Compatibility</h5>
                            <ul>
                                <li>Chrome (recommended)</li>
                                <li>Firefox</li>
                                <li>Safari</li>
                                <li>Edge</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2 class="card-title border-bottom pb-3">Frequently Asked Questions</h2>
                    
                    <div class="accordion mt-4" id="faqAccordion">
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Can I upload multiple images for one answer?
                                </button>
                            </h3>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Currently, only one image can be uploaded per answer. If you need to include multiple diagrams or charts, 
                                    consider combining them into a single image using image editing software before uploading.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    What happens if I upload a new image after already uploading one?
                                </button>
                            </h3>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    The new image will replace the previously uploaded image. Only the most recent image upload will be saved and associated with your answer.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    How do I know if my image was uploaded successfully?
                                </button>
                            </h3>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    After selecting an image file, you'll see a confirmation message with a "View" button once the upload is complete. 
                                    You can click this button to preview your uploaded image. Additionally, a success alert will be displayed to confirm the upload.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    What should I do if image upload fails?
                                </button>
                            </h3>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    <p>If your image upload fails, try the following:</p>
                                    <ol>
                                        <li>Ensure your image is under the 5MB size limit</li>
                                        <li>Verify you're using a supported file format (JPEG, PNG, or GIF)</li>
                                        <li>Try a different browser or device</li>
                                        <li>Compress or resize the image to reduce its file size</li>
                                        <li>If problems persist, include a note in your text answer and contact your instructor</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h3 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Will images be included if I print my exam results?
                                </button>
                            </h3>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, when printing exam results from the detailed answer view, uploaded images will be included in the printed output. 
                                    However, to ensure the best quality, you may want to view and print each answer individually rather than printing the entire results page.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Quick Links -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-book me-2"></i> User Manual
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-question-circle me-2"></i> Help Center
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-video me-2"></i> Video Tutorials
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-graduation-cap me-2"></i> Best Practices
                        </a>
                        <a href="#" class="list-group-item list-group-item-action">
                            <i class="fas fa-bug me-2"></i> Report Issues
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Example Use Cases -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Example Use Cases</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <span class="badge rounded-pill bg-primary p-2">
                                <i class="fas fa-calculator"></i>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Mathematics</h6>
                            <p class="small mb-0">Upload handwritten equations, solution steps, or geometric diagrams.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <span class="badge rounded-pill bg-success p-2">
                                <i class="fas fa-flask"></i>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Science</h6>
                            <p class="small mb-0">Include experimental setups, chemical structures, or biological diagrams.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <span class="badge rounded-pill bg-warning p-2 text-dark">
                                <i class="fas fa-palette"></i>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Art & Design</h6>
                            <p class="small mb-0">Share sketches, artwork analysis, or design concepts.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-3">
                        <div class="flex-shrink-0">
                            <span class="badge rounded-pill bg-danger p-2">
                                <i class="fas fa-chart-line"></i>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Economics</h6>
                            <p class="small mb-0">Include graphs, charts, or economic models to support analysis.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <span class="badge rounded-pill bg-info p-2">
                                <i class="fas fa-map-marked-alt"></i>
                            </span>
                        </div>
                        <div class="ms-3">
                            <h6 class="mb-1">Geography</h6>
                            <p class="small mb-0">Upload maps, diagrams, or geographic illustrations to enhance answers.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tips and Tricks -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Tips & Tricks</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex">
                            <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                            <div>Take clear photos with good lighting for handwritten content.</div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                            <div>Use scanning apps on mobile devices for better quality than photos.</div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                            <div>Compress large images before uploading to avoid timeout errors.</div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                            <div>Label all parts of diagrams clearly before uploading.</div>
                        </li>
                        <li class="list-group-item d-flex">
                            <i class="fas fa-lightbulb text-warning me-3 mt-1"></i>
                            <div>Always include text explanation along with images for clearer understanding.</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>