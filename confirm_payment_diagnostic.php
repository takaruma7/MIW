<?php
/**
 * Diagnostic script for confirm_payment.php dependencies
 * 
 * This script tests all components that confirm_payment.php depends on
 * to help identify the source of HTTP 500 errors.
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html><html><head><title>Confirm Payment Diagnostic</title>";
echo "<style>
    body { font-family: 'Consolas', monospace; margin: 20px; background: #f8f9fa; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .header { background: #007bff; color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 8px 8px 0 0; }
    .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #007bff; background: #f8f9fa; }
    .success { color: #28a745; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #17a2b8; font-weight: bold; }
    pre { background: #2d2d2d; color: #fff; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 Confirm Payment Diagnostic</h1>";
echo "<p>Testing all dependencies for confirm_payment.php</p>";
echo "</div>";

// Test 1: Basic PHP and environment
echo "<div class='test-section'>";
echo "<h2>1. PHP Environment Test</h2>";
echo "<span class='success'>✅ PHP Version: " . PHP_VERSION . "</span><br>";
echo "<span class='info'>📊 Memory Limit: " . ini_get('memory_limit') . "</span><br>";
echo "<span class='info'>⏱️ Max Execution Time: " . ini_get('max_execution_time') . " seconds</span><br>";
echo "<span class='info'>📁 Upload Max Filesize: " . ini_get('upload_max_filesize') . "</span><br>";
echo "<span class='info'>🌐 Environment: " . (isset($_ENV['DYNO']) ? 'Heroku' : 'Local/Other') . "</span><br>";
echo "</div>";

// Test 2: File dependencies
echo "<div class='test-section'>";
echo "<h2>2. Required Files Test</h2>";

$requiredFiles = [
    'config.php' => 'Main configuration',
    'email_functions.php' => 'Email functionality',
    'upload_handler.php' => 'File upload handling',
    'heroku_file_manager.php' => 'File management'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<span class='success'>✅ $file: $description</span><br>";
    } else {
        echo "<span class='error'>❌ $file: Missing ($description)</span><br>";
    }
}
echo "</div>";

// Test 3: Database connection
echo "<div class='test-section'>";
echo "<h2>3. Database Connection Test</h2>";
try {
    require_once 'config.php';
    
    if ($conn instanceof PDO) {
        $stmt = $conn->query("SELECT 1");
        echo "<span class='success'>✅ Database connection: WORKING</span><br>";
        
        // Test required tables
        $requiredTables = ['data_jamaah', 'data_paket'];
        foreach ($requiredTables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<span class='success'>✅ Table $table: $count records</span><br>";
            } catch (Exception $e) {
                echo "<span class='error'>❌ Table $table: " . $e->getMessage() . "</span><br>";
            }
        }
    } else {
        echo "<span class='error'>❌ Database connection: PDO object not created</span><br>";
    }
} catch (Exception $e) {
    echo "<span class='error'>❌ Database connection: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Test 4: Upload handler
echo "<div class='test-section'>";
echo "<h2>4. Upload Handler Test</h2>";
try {
    require_once 'upload_handler.php';
    
    $uploadHandler = new UploadHandler();
    echo "<span class='success'>✅ UploadHandler class: Loaded successfully</span><br>";
    
    // Test filename generation
    $testFilename = $uploadHandler->generateCustomFilename('1234567890', 'payment', null);
    echo "<span class='success'>✅ Filename generation: $testFilename</span><br>";
    
    // Test error handling
    $uploadHandler->clearErrors();
    if (!$uploadHandler->hasErrors()) {
        echo "<span class='success'>✅ Error handling: Working</span><br>";
    } else {
        echo "<span class='warning'>⚠️ Error handling: Has errors</span><br>";
    }
    
    // Test upload directory
    $uploadStats = $uploadHandler->getUploadStats();
    echo "<span class='info'>📂 Upload directory: " . $uploadStats['upload_directory'] . "</span><br>";
    echo "<span class='info'>🔧 Environment: " . $uploadStats['environment'] . "</span><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Upload handler: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Test 5: Email functions
echo "<div class='test-section'>";
echo "<h2>5. Email Functions Test</h2>";
try {
    require_once 'email_functions.php';
    echo "<span class='success'>✅ Email functions: Loaded successfully</span><br>";
    
    // Check email constants
    $emailSettings = [
        'EMAIL_ENABLED' => defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'Yes' : 'No') : 'Not defined',
        'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
        'EMAIL_FROM' => defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined'
    ];
    
    foreach ($emailSettings as $setting => $value) {
        $status = ($value !== 'Not defined') ? 'success' : 'warning';
        echo "<span class='$status'>📧 $setting: $value</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Email functions: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Test 6: Session functionality
echo "<div class='test-section'>";
echo "<h2>6. Session Test</h2>";
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['test_key'] = 'test_value';
    
    if (isset($_SESSION['test_key']) && $_SESSION['test_key'] === 'test_value') {
        echo "<span class='success'>✅ Session handling: Working</span><br>";
        unset($_SESSION['test_key']);
    } else {
        echo "<span class='error'>❌ Session handling: Failed</span><br>";
    }
    
    echo "<span class='info'>📋 Session ID: " . session_id() . "</span><br>";
    echo "<span class='info'>🔧 Session status: " . session_status() . "</span><br>";
    
} catch (Exception $e) {
    echo "<span class='error'>❌ Session test: " . $e->getMessage() . "</span><br>";
}
echo "</div>";

// Test 7: Error logging
echo "<div class='test-section'>";
echo "<h2>7. Error Logging Test</h2>";

$errorLogDir = __DIR__ . '/error_logs';
if (!file_exists($errorLogDir)) {
    mkdir($errorLogDir, 0755, true);
    echo "<span class='info'>📁 Created error_logs directory</span><br>";
} else {
    echo "<span class='success'>✅ Error logs directory exists</span><br>";
}

if (is_writable($errorLogDir)) {
    echo "<span class='success'>✅ Error logs directory is writable</span><br>";
    
    // Test writing to log
    $testLogFile = $errorLogDir . '/test_' . date('Y-m-d') . '.log';
    $testMessage = "[" . date('Y-m-d H:i:s') . "] Diagnostic test log entry\n";
    
    if (file_put_contents($testLogFile, $testMessage, FILE_APPEND | LOCK_EX)) {
        echo "<span class='success'>✅ Log writing test: Success</span><br>";
        
        // Clean up test file
        if (file_exists($testLogFile)) {
            unlink($testLogFile);
        }
    } else {
        echo "<span class='error'>❌ Log writing test: Failed</span><br>";
    }
} else {
    echo "<span class='error'>❌ Error logs directory is not writable</span><br>";
}

echo "<span class='info'>📋 PHP Error Log: " . (ini_get('error_log') ?: 'Not set') . "</span><br>";
echo "<span class='info'>🔧 Log Errors: " . (ini_get('log_errors') ? 'Enabled' : 'Disabled') . "</span><br>";
echo "</div>";

// Test 8: File upload capabilities
echo "<div class='test-section'>";
echo "<h2>8. File Upload Configuration</h2>";

echo "<span class='info'>📏 Upload Max Filesize: " . ini_get('upload_max_filesize') . "</span><br>";
echo "<span class='info'>📊 Post Max Size: " . ini_get('post_max_size') . "</span><br>";
echo "<span class='info'>⏱️ Max Input Time: " . ini_get('max_input_time') . " seconds</span><br>";
echo "<span class='info'>🔢 Max File Uploads: " . ini_get('max_file_uploads') . "</span><br>";

$uploadTmpDir = ini_get('upload_tmp_dir') ?: sys_get_temp_dir();
echo "<span class='info'>📁 Upload Temp Dir: $uploadTmpDir</span><br>";

if (is_dir($uploadTmpDir) && is_writable($uploadTmpDir)) {
    echo "<span class='success'>✅ Upload temp directory is accessible</span><br>";
} else {
    echo "<span class='error'>❌ Upload temp directory is not accessible</span><br>";
}
echo "</div>";

// Test 9: Current request simulation
echo "<div class='test-section'>";
echo "<h2>9. Request Environment</h2>";

echo "<span class='info'>🌐 Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</span><br>";
echo "<span class='info'>📋 Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'Unknown') . "</span><br>";
echo "<span class='info'>🔗 Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Unknown') . "</span><br>";
echo "<span class='info'>🏠 Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</span><br>";
echo "<span class='info'>📝 Script Name: " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</span><br>";

echo "</div>";

// Summary
echo "<div class='test-section'>";
echo "<h2>🎯 Diagnostic Summary</h2>";
echo "<p><strong>This diagnostic should help identify issues with confirm_payment.php.</strong></p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Check the production error logs using the error_viewer.php tool</li>";
echo "<li>Test file upload functionality manually</li>";
echo "<li>Verify database connectivity on production</li>";
echo "<li>Check if session handling works on production</li>";
echo "</ul>";

echo "<p><strong>Access error logs:</strong></p>";
echo "<p>Visit <a href='error_viewer.php' target='_blank'>error_viewer.php</a> (password: MIW2025!)</p>";
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px; color: #666;'>";
echo "<p>Diagnostic completed at " . date('Y-m-d H:i:s T') . "</p>";
echo "</div>";

echo "</div></body></html>";
?>
