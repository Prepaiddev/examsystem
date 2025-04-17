<?php
/**
 * Database connection file
 * This file handles the database connection for the entire application
 */

// Database configuration
define('DB_HOST', 'localhost'); // Most cPanel servers use 'localhost'
define('DB_USER', 'techwave_exam11'); // Your database username
define('DB_PASS', 'Caroboy2003!'); // Your database password
define('DB_NAME', 'techwave_exam11'); // Your database name

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    // If using in production, don't show the actual error (security risk)
    $error_message = "Database connection failed. Please contact the administrator.";
    
    // For development, you might want to see the actual error
    if (defined('DEV_MODE') && DEV_MODE === true) {
        $error_message = "Connection failed: " . $conn->connect_error;
    }
    
    // Log the error
    error_log("Database connection error: " . $conn->connect_error);
    
    // Store the error in a session variable for display
    session_start();
    $_SESSION['db_error'] = $error_message;
    
    // Redirect to an error page or homepage with error parameter
    header("Location: ../index.php?error=db_connection");
    exit();
}

// Set character set
$conn->set_charset("utf8mb4");

// Function to sanitize inputs (to prevent SQL injection)
function sanitize_input($conn, $data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to handle database errors
function db_error($conn, $query) {
    $error = "Query error: " . $conn->error;
    error_log($error . " in query: " . $query);
    return $error;
}

// Function to execute a query and return the result
function db_query($conn, $query) {
    $result = $conn->query($query);
    if (!$result) {
        return db_error($conn, $query);
    }
    return $result;
}

// Function to fetch all rows from a result as an associative array
function db_fetch_all($result) {
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to fetch a single row as an associative array
function db_fetch_assoc($result) {
    return $result->fetch_assoc();
}

// Function to get the number of rows in a result
function db_num_rows($result) {
    return $result->num_rows;
}

// Function to get the ID of the last inserted row
function db_insert_id($conn) {
    return $conn->insert_id;
}

// Function to close the database connection
function db_close($conn) {
    $conn->close();
}
?>