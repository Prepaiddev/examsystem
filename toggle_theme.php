<?php
// Include configuration
require_once 'config/config.php';

// Toggle theme preference
if (isset($_SESSION['theme']) && $_SESSION['theme'] === 'dark') {
    $_SESSION['theme'] = 'light';
} else {
    $_SESSION['theme'] = 'dark';
}

// If user is logged in, save preference to database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $theme = $_SESSION['theme'];
    
    $query = "UPDATE users SET theme_preference = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'si', $theme, $user_id);
    mysqli_stmt_execute($stmt);
}

// Redirect back to the referring page
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : SITE_URL . '/index.php';
header("Location: $redirect");
exit;
?>