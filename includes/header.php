<?php
// Make sure config is included
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

// Define site information
$site_name = defined('SITE_NAME') ? SITE_NAME : "PHP Exam System";
$site_description = defined('SITE_DESCRIPTION') ? SITE_DESCRIPTION : "A Comprehensive Online Examination Platform";

// Get the current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Default page title if not set
if (!isset($page_title)) {
    $page_title = $site_name;
} else {
    $page_title = $page_title . " | " . $site_name;
}

// Check user theme preference (default to light)
$theme = isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light';

// If user is logged in but theme not in session, try to get from database
if (isset($_SESSION['user_id']) && !isset($_SESSION['theme'])) {
    $user_id = $_SESSION['user_id'];
    $theme_query = "SELECT theme_preference FROM users WHERE id = ?";
    $theme_stmt = mysqli_prepare($conn, $theme_query);
    mysqli_stmt_bind_param($theme_stmt, 'i', $user_id);
    mysqli_stmt_execute($theme_stmt);
    $theme_result = mysqli_stmt_get_result($theme_stmt);
    
    if ($theme_row = mysqli_fetch_assoc($theme_result)) {
        if (!empty($theme_row['theme_preference'])) {
            $theme = $theme_row['theme_preference'];
            $_SESSION['theme'] = $theme;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/img/favicon.png">
</head>
<body>
    <!-- Header/Navigation -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <!-- Logo and Brand -->
                <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
                    <i class="fas fa-graduation-cap me-2"></i>
                    <span><?php echo htmlspecialchars($site_name); ?></span>
                </a>
                
                <!-- Mobile Toggle Button -->
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <!-- Navigation Links -->
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/index.php">
                                <i class="fas fa-home me-1"></i> Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'about.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/about.php">
                                <i class="fas fa-info-circle me-1"></i> About
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Right-aligned items (login/user menu) -->
                    <ul class="navbar-nav">
                        <!-- Theme Toggle Button -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/toggle_theme.php" title="Toggle Theme">
                                <?php if ($theme === 'dark'): ?>
                                    <i class="fas fa-sun me-1"></i> Light Mode
                                <?php else: ?>
                                    <i class="fas fa-moon me-1"></i> Dark Mode
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <!-- User is logged in, show user menu -->
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/dashboard.php">
                                                <i class="fas fa-tachometer-alt me-1"></i> Admin Dashboard
                                            </a>
                                        </li>
                                    <?php else: ?>
                                        <li>
                                            <a class="dropdown-item" href="<?php echo SITE_URL; ?>/student/dashboard.php">
                                                <i class="fas fa-tachometer-alt me-1"></i> Student Dashboard
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php">
                                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <!-- User is not logged in, show login/register buttons -->
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'login.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/login.php">
                                    <i class="fas fa-sign-in-alt me-1"></i> Login
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'register.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/register.php">
                                    <i class="fas fa-user-plus me-1"></i> Register
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Flash Message Display -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']); 
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Content Begins -->