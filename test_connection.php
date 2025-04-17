<?php
/**
 * Test database connection
 * 
 * This script attempts to connect to the database using the configured settings
 * and displays detailed information to help troubleshoot connection issues.
 */

// Define database settings
$host = '127.0.0.1'; // Using 127.0.0.1 instead of 'localhost' for better compatibility on some hosts
$user = 'techwave_exam11';
$pass = 'Caroboy2003!';
$name = 'techwave_exam11';

// Display PHP info
$php_version = phpversion();
$mysql_client = function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Unknown';
$server_software = isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown';

// Test Connection Function
function testConnection($host, $user, $pass, $name) {
    $result = [
        'success' => false,
        'error' => '',
        'connection_time' => 0,
        'details' => []
    ];
    
    $start_time = microtime(true);
    
    // Try to connect
    try {
        $conn = mysqli_connect($host, $user, $pass, $name);
        
        $result['connection_time'] = round((microtime(true) - $start_time) * 1000, 2); // time in ms
        
        if (!$conn) {
            $result['error'] = mysqli_connect_error();
            return $result;
        }
        
        $result['success'] = true;
        
        // Get some details about the server
        $result['details']['server_version'] = mysqli_get_server_info($conn);
        $result['details']['server_host'] = mysqli_get_host_info($conn);
        $result['details']['charset'] = mysqli_character_set_name($conn);
        
        // Check if tables exist
        $tables_query = mysqli_query($conn, "SHOW TABLES");
        $tables = [];
        if ($tables_query) {
            while ($table = mysqli_fetch_array($tables_query)) {
                $tables[] = $table[0];
            }
        }
        $result['details']['tables_count'] = count($tables);
        $result['details']['tables'] = $tables;
        
        // Close connection
        mysqli_close($conn);
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

// Run the test
$test_result = testConnection($host, $user, $pass, $name);

// HTML Output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
            padding: 20px;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .connection-badge {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1 class="mb-4">Database Connection Test</h1>
        
        <!-- Connection Status -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Connection Status</h5>
                <?php if ($test_result['success']): ?>
                    <span class="badge bg-success connection-badge">
                        <i class="fas fa-check-circle me-1"></i> Connected Successfully
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger connection-badge">
                        <i class="fas fa-times-circle me-1"></i> Connection Failed
                    </span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if ($test_result['success']): ?>
                    <p class="card-text">Successfully connected to the database in <?php echo $test_result['connection_time']; ?> ms.</p>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h5 class="alert-heading">Connection Error</h5>
                        <p><?php echo htmlspecialchars($test_result['error']); ?></p>
                    </div>
                    
                    <div class="alert alert-info">
                        <h5 class="alert-heading">Troubleshooting Tips</h5>
                        <ul>
                            <li>Check if the database server is running</li>
                            <li>Verify username and password are correct</li>
                            <li>Confirm the database exists and is accessible</li>
                            <li>Try using IP address (127.0.0.1) instead of 'localhost'</li>
                            <li>Check for firewall or network issues</li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Connection Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Connection Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%;">Database Host</th>
                        <td><?php echo htmlspecialchars($host); ?></td>
                    </tr>
                    <tr>
                        <th>Database Name</th>
                        <td><?php echo htmlspecialchars($name); ?></td>
                    </tr>
                    <tr>
                        <th>Database User</th>
                        <td><?php echo htmlspecialchars($user); ?></td>
                    </tr>
                    <tr>
                        <th>Password</th>
                        <td><span class="text-muted">[Hidden for security reasons]</span></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Environment Info -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Environment Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 30%;">PHP Version</th>
                        <td><?php echo htmlspecialchars($php_version); ?></td>
                    </tr>
                    <tr>
                        <th>MySQL Client</th>
                        <td><?php echo htmlspecialchars($mysql_client); ?></td>
                    </tr>
                    <tr>
                        <th>Server Software</th>
                        <td><?php echo htmlspecialchars($server_software); ?></td>
                    </tr>
                    <?php if ($test_result['success']): ?>
                        <tr>
                            <th>MySQL Server Version</th>
                            <td><?php echo htmlspecialchars($test_result['details']['server_version']); ?></td>
                        </tr>
                        <tr>
                            <th>Connection Info</th>
                            <td><?php echo htmlspecialchars($test_result['details']['server_host']); ?></td>
                        </tr>
                        <tr>
                            <th>Character Set</th>
                            <td><?php echo htmlspecialchars($test_result['details']['charset']); ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        
        <?php if ($test_result['success'] && isset($test_result['details']['tables'])): ?>
        <!-- Database Tables -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Database Tables</h5>
            </div>
            <div class="card-body">
                <?php if (count($test_result['details']['tables']) > 0): ?>
                    <p>Found <?php echo count($test_result['details']['tables']); ?> tables in the database:</p>
                    <ul class="list-group">
                        <?php foreach ($test_result['details']['tables'] as $table): ?>
                            <li class="list-group-item"><?php echo htmlspecialchars($table); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> No tables found in the database.
                    </div>
                    <p>You may need to run the setup script to initialize the database tables.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Next Steps -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Next Steps</h5>
            </div>
            <div class="card-body">
                <?php if ($test_result['success']): ?>
                    <p>Your database connection is working correctly. You can now:</p>
                    <div class="d-grid gap-2">
                        <?php if (isset($test_result['details']['tables']) && count($test_result['details']['tables']) == 0): ?>
                            <a href="simple_setup.php" class="btn btn-primary">
                                <i class="fas fa-cogs me-2"></i> Run Setup Script
                            </a>
                        <?php else: ?>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i> Go to Homepage
                            </a>
                        <?php endif; ?>
                        <a href="#" class="btn btn-outline-secondary" onclick="window.location.reload()">
                            <i class="fas fa-sync me-2"></i> Test Connection Again
                        </a>
                    </div>
                <?php else: ?>
                    <p>Please fix the connection issues before proceeding:</p>
                    <ol>
                        <li>Check the database configuration in <code>config/config.php</code></li>
                        <li>Ensure the database server is running and accessible</li>
                        <li>Verify that the database user has proper permissions</li>
                        <li>After making changes, refresh this page to test again</li>
                    </ol>
                    <div class="d-grid">
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="fas fa-sync me-2"></i> Test Connection Again
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>