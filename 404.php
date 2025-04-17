<?php
/**
 * 404 Not Found Page
 */
require_once 'config/config.php';

$page_title = '404 - Page Not Found';
include 'includes/header_minimal.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <div class="error-container">
                <div class="display-1 text-primary mb-4">404</div>
                <h1 class="h2 mb-4">Oops! Page Not Found</h1>
                <div class="mb-4">
                    <img src="<?php echo SITE_URL; ?>/assets/images/404-illustration.svg" alt="Page not found" class="img-fluid mb-4" style="max-height: 300px;">
                </div>
                <p class="lead mb-4">The page you're looking for doesn't exist or has been moved.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="javascript:history.back()" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Go Back
                    </a>
                    <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i> Return Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer_minimal.php'; ?>