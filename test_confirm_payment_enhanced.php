<?php
/**
 * Enhanced test for confirm_payment.php issues
 * 
 * This script will simulate various error conditions to help identify
 * the exact cause of HTTP 500 errors in production.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

// Enhanced logging function
function testLog($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/error_logs/enhanced_test_' . date('Y-m-d') . '.log';
    
    $logEntry = "[{$timestamp}] ENHANCED_TEST: {$message}\n";
    if (!empty($context)) {
        $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    $logEntry .= str_repeat('-', 50) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Enhanced Confirm Payment Test</title>
    <style>
        body { font-family: 'Consolas', monospace; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .header { background: #dc3545; color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #dc3545; background: #f8f9fa; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .critical { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .code-block { background: #2d2d2d; color: #fff; padding: 15px; border-radius: 4px; font-family: 'Consolas', monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔥 Enhanced Confirm Payment Test</h1>
            <p>Simulating production environment issues and edge cases</p>
        </div>

        <?php
        
        testLog("Starting enhanced confirm_payment.php test");
        
        // Test 1: Environment detection
        echo "<div class='test-section'><h3>1. Environment Detection</h3>";
        
        $isHeroku = isset($_ENV['DYNO']);
        $isProduction = $isHeroku || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
        
        echo "<div class='info'>Environment: " . ($isProduction ? 'Production' : 'Local') . "</div>";
        echo "<div class='info'>Heroku: " . ($isHeroku ? 'Yes' : 'No') . "</div>";
        echo "<div class='info'>PHP Version: " . PHP_VERSION . "</div>";
        echo "<div class='info'>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</div>";
        
        // Check critical PHP settings
        $criticalSettings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads') ? 'On' : 'Off',
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir()
        ];
        
        foreach ($criticalSettings as $setting => $value) {
            echo "<div class='info'>$setting: $value</div>";
        }
        
        echo "</div>";
        
        // Test 2: Core dependencies
        echo "<div class='test-section'><h3>2. Core Dependencies Test</h3>";
        
        try {
            testLog("Testing core dependencies");
            
            // Test config.php
            require_once 'config.php';
            echo "<div class='success'>✅ config.php loaded</div>";
            
            // Test database connection
            if ($conn instanceof PDO) {
                echo "<div class='success'>✅ Database connection active</div>";
                
                // Test essential tables
                $tables = ['data_jamaah', 'data_paket'];
                foreach ($tables as $table) {
                    try {
                        $stmt = $conn->query("SELECT COUNT(*) FROM $table LIMIT 1");
                        echo "<div class='success'>✅ Table $table: accessible</div>";
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Table $table: " . $e->getMessage() . "</div>";
                    }
                }
            } else {
                echo "<div class='error'>❌ Database connection failed</div>";
            }
            
            // Test upload handler
            require_once 'upload_handler.php';
            $uploadHandler = new UploadHandler();
            echo "<div class='success'>✅ UploadHandler loaded</div>";
            
            // Test email functions
            require_once 'email_functions.php';
            echo "<div class='success'>✅ Email functions loaded</div>";
            
        } catch (Exception $e) {
            testLog("Core dependencies failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ Core dependency error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 3: File upload simulation
        echo "<div class='test-section'><h3>3. File Upload Simulation</h3>";
        
        try {
            testLog("Testing file upload simulation");
            
            // Create a test file to simulate upload
            $testContent = "Test payment proof content - " . date('Y-m-d H:i:s');
            $testFileName = 'test_payment_' . time() . '.txt';
            $tempDir = sys_get_temp_dir();
            $testFilePath = $tempDir . '/' . $testFileName;
            
            file_put_contents($testFilePath, $testContent);
            echo "<div class='success'>✅ Test file created: $testFilePath</div>";
            
            // Simulate $_FILES array
            $simulatedFile = [
                'name' => $testFileName,
                'type' => 'text/plain',
                'tmp_name' => $testFilePath,
                'error' => UPLOAD_ERR_OK,
                'size' => strlen($testContent)
            ];
            
            echo "<div class='info'>Simulated file size: " . $simulatedFile['size'] . " bytes</div>";
            
            // Test UploadHandler with simulated file
            $uploadHandler = new UploadHandler();
            $customName = $uploadHandler->generateCustomFilename('1234567890123456', 'payment', null);
            echo "<div class='success'>✅ Custom filename: $customName</div>";
            
            // Clean up test file
            if (file_exists($testFilePath)) {
                unlink($testFilePath);
                echo "<div class='info'>Test file cleaned up</div>";
            }
            
        } catch (Exception $e) {
            testLog("File upload simulation failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ File upload simulation error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 4: Session handling
        echo "<div class='test-section'><h3>4. Session Handling Test</h3>";
        
        try {
            testLog("Testing session handling");
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
                echo "<div class='success'>✅ Session started</div>";
            } else {
                echo "<div class='info'>Session already active</div>";
            }
            
            // Test session operations
            $_SESSION['test_confirm_payment'] = 'test_value_' . time();
            
            if (isset($_SESSION['test_confirm_payment'])) {
                echo "<div class='success'>✅ Session write/read working</div>";
                unset($_SESSION['test_confirm_payment']);
            } else {
                throw new Exception("Session not working properly");
            }
            
        } catch (Exception $e) {
            testLog("Session handling failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ Session error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 5: Transaction simulation
        echo "<div class='test-section'><h3>5. Database Transaction Test</h3>";
        
        try {
            testLog("Testing database transaction");
            
            $conn->beginTransaction();
            echo "<div class='success'>✅ Transaction started</div>";
            
            // Test a safe SELECT operation
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM data_jamaah LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            echo "<div class='success'>✅ Query executed: " . $result['count'] . " records</div>";
            
            $conn->rollBack();
            echo "<div class='success'>✅ Transaction rolled back</div>";
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            testLog("Transaction test failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ Transaction error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 6: Error logging test
        echo "<div class='test-section'><h3>6. Error Logging Test</h3>";
        
        try {
            testLog("Testing error logging functionality");
            
            $errorLogDir = __DIR__ . '/error_logs';
            if (is_writable($errorLogDir)) {
                echo "<div class='success'>✅ Error log directory writable</div>";
                
                // Test log writing
                $testLogFile = $errorLogDir . '/test_confirm_' . date('Y-m-d') . '.log';
                $logContent = "[" . date('Y-m-d H:i:s') . "] Test log entry for confirm_payment.php\n";
                
                if (file_put_contents($testLogFile, $logContent, FILE_APPEND | LOCK_EX)) {
                    echo "<div class='success'>✅ Log writing successful</div>";
                    
                    // Clean up test log
                    if (file_exists($testLogFile)) {
                        unlink($testLogFile);
                    }
                } else {
                    throw new Exception("Failed to write to log file");
                }
            } else {
                throw new Exception("Error log directory not writable");
            }
            
        } catch (Exception $e) {
            testLog("Error logging test failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ Error logging error: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 7: Memory and performance
        echo "<div class='test-section'><h3>7. Memory and Performance Test</h3>";
        
        echo "<div class='info'>Memory Usage: " . number_format(memory_get_usage(true)) . " bytes</div>";
        echo "<div class='info'>Peak Memory: " . number_format(memory_get_peak_usage(true)) . " bytes</div>";
        echo "<div class='info'>Memory Limit: " . ini_get('memory_limit') . "</div>";
        
        // Test large data handling
        try {
            $testData = str_repeat('x', 1024 * 1024); // 1MB string
            echo "<div class='success'>✅ 1MB data allocation successful</div>";
            unset($testData);
        } catch (Exception $e) {
            echo "<div class='warning'>⚠️ Large data allocation failed: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Summary and recommendations
        echo "<div class='test-section'><h3>🎯 Enhanced Test Summary</h3>";
        echo "<div class='critical'><strong>Critical Issues to Check in Production:</strong></div>";
        echo "<ul>";
        echo "<li>File upload limits (check if files exceed 2MB or server limits)</li>";
        echo "<li>Database connection stability under load</li>";
        echo "<li>Upload handler initialization in production environment</li>";
        echo "<li>Session handling on Heroku (check session storage)</li>";
        echo "<li>Transaction handling under concurrent requests</li>";
        echo "<li>Error log directory permissions on production</li>";
        echo "<li>Memory limits for large file uploads</li>";
        echo "</ul>";
        
        echo "<div class='critical'><strong>Next Steps:</strong></div>";
        echo "<ul>";
        echo "<li>1. Access production error logs via <a href='error_viewer.php' target='_blank'>error_viewer.php</a></li>";
        echo "<li>2. Test actual file upload to confirm_payment.php with small file (&lt;1MB)</li>";
        echo "<li>3. Monitor enhanced logging we added to confirm_payment.php</li>";
        echo "<li>4. Check for memory/timeout issues during peak usage</li>";
        echo "</ul>";
        
        echo "</div>";
        
        testLog("Enhanced test completed");
        
        ?>
        
        <div style="text-align: center; margin-top: 30px; color: #666;">
            <p>Enhanced diagnostic completed at <?= date('Y-m-d H:i:s T') ?></p>
            <p><a href="error_viewer.php" target="_blank">🔍 View Production Error Logs</a> | <a href="confirm_payment_diagnostic.php" target="_blank">📋 Basic Diagnostic</a></p>
        </div>
    </div>
</body>
</html>
