    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <!-- Site Info -->
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5><i class="fas fa-graduation-cap me-2"></i> PHP Exam System</h5>
                    <p class="text-muted">A comprehensive online examination platform designed for educational institutions.</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-github"></i></a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'index.php' : dirname($_SERVER['PHP_SELF']) . '/index.php'; ?>" class="text-decoration-none text-light"><i class="fas fa-angle-right me-2"></i>Home</a></li>
                        <li><a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'about.php' : dirname($_SERVER['PHP_SELF']) . '/about.php'; ?>" class="text-decoration-none text-light"><i class="fas fa-angle-right me-2"></i>About</a></li>
                        <li><a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'privacy.php' : dirname($_SERVER['PHP_SELF']) . '/privacy.php'; ?>" class="text-decoration-none text-light"><i class="fas fa-angle-right me-2"></i>Privacy Policy</a></li>
                        <li><a href="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? 'terms.php' : dirname($_SERVER['PHP_SELF']) . '/terms.php'; ?>" class="text-decoration-none text-light"><i class="fas fa-angle-right me-2"></i>Terms of Service</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> 123 Education Street, Academia City</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i> +1 (234) 567-8900</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i> info@phpexamsystem.com</li>
                    </ul>
                </div>
            </div>
            
            <!-- Copyright -->
            <div class="row mt-3 pt-3 border-top border-secondary">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> PHP Exam System. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Designed for educational purposes.</p>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="<?php echo dirname($_SERVER['PHP_SELF']) === '/' ? '' : dirname($_SERVER['PHP_SELF']); ?>/assets/js/main.js"></script>
    
    <script>
    // Initialize Bootstrap tooltips and popovers
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Auto-close alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
    </script>
</body>
</html>