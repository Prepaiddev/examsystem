<?php
/**
 * Main configuration file for PHP Exam System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site configuration
define('SITE_NAME', 'PHP Exam System');
define('SITE_URL', 'https://techwave.com.ng'); // Updated for production
define('SITE_DESCRIPTION', 'A comprehensive online examination platform');
define('SITE_VERSION', '1.0.0');
define('SITE_EMAIL', 'admin@example.com');

// User roles
define('USER_ROLE_ADMIN', 'admin');
define('USER_ROLE_STUDENT', 'student');

// Database configuration
define('DB_HOST', '127.0.0.1'); // Use 127.0.0.1 for better compatibility
define('DB_USER', 'techwave_exam11');
define('DB_PASS', 'Caroboy2003!');
define('DB_NAME', 'techwave_exam11');

// Error reporting settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Date and time settings
date_default_timezone_set('Africa/Lagos');

// Load utilities and helper functions
require_once __DIR__ . '/functions.php';

// Database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Configure character set
mysqli_set_charset($conn, "utf8mb4");

// Register shutdown function to close database connection
register_shutdown_function(function() use (&$conn) {
    if ($conn) {
        mysqli_close($conn);
    }
});
?>