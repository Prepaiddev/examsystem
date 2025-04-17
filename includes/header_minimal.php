<!DOCTYPE html>
<html lang="en" data-bs-theme="<?php echo isset($_SESSION['theme']) ? $_SESSION['theme'] : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Exam System</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?php echo SITE_URL; ?>/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    
    <!-- MathJax for Math Equations (if needed) -->
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    
    <!-- Prevent backspace navigation -->
    <script>
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && (e.target.nodeName !== 'INPUT' && e.target.nodeName !== 'TEXTAREA' && !e.target.isContentEditable)) {
            e.preventDefault();
        }
    });
    </script>
</head>
<body class="exam-mode">
    <!-- Empty header for exam mode - no navigation to minimize distractions -->
    <header class="bg-primary text-white">
        <!-- No nav menu in exam mode -->
    </header>
    
    <main>
    <!-- Main content starts here -->