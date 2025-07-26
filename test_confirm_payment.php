<?php
/**
 * Test script to reproduce confirm_payment.php issue
 * 
 * This script tests the exact same operations that confirm_payment.php performs
 * without requiring actual form submission.
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Enhanced error logging
function testLog($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/error_logs/test_' . date('Y-m-d') . '.log';
    
    $logEntry = "[{$timestamp}] TEST: {$message}\n";
    if (!empty($context)) {
        $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    $logEntry .= str_repeat('-', 50) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    echo "<div>[$timestamp] $message</div>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Payment Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .header { background: #007bff; color: white; padding: 15px; margin: -20px -20px 20px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 15px 0; padding: 10px; border-left: 3px solid #007bff; background: #f8f9fa; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Confirm Payment Test</h1>
            <p>Testing confirm_payment.php operations step by step</p>
        </div>

        <?php
        
        testLog("Starting confirm_payment.php test simulation");
        
        // Test 1: Basic requirements
        echo "<div class='test-section'><h3>1. Testing Basic Requirements</h3>";
        
        try {
            testLog("Testing config.php inclusion");
            require_once 'config.php';
            echo "<div class='success'>✅ config.php loaded successfully</div>";
            
            testLog("Testing database connection", ['conn_type' => get_class($conn)]);
            if ($conn instanceof PDO) {
                $stmt = $conn->query("SELECT 1");
                echo "<div class='success'>✅ Database connection working</div>";
            } else {
                throw new Exception("Invalid database connection");
            }
            
        } catch (Exception $e) {
            testLog("Basic requirements failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 2: Email functions
        echo "<div class='test-section'><h3>2. Testing Email Functions</h3>";
        
        try {
            testLog("Testing email_functions.php inclusion");
            require_once 'email_functions.php';
            echo "<div class='success'>✅ email_functions.php loaded successfully</div>";
            
            testLog("Testing email configuration", [
                'EMAIL_ENABLED' => defined('EMAIL_ENABLED') ? EMAIL_ENABLED : 'Not defined',
                'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined'
            ]);
            
        } catch (Exception $e) {
            testLog("Email functions failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 3: Upload handler
        echo "<div class='test-section'><h3>3. Testing Upload Handler</h3>";
        
        try {
            testLog("Testing upload_handler.php inclusion");
            require_once 'upload_handler.php';
            echo "<div class='success'>✅ upload_handler.php loaded successfully</div>";
            
            $uploadHandler = new UploadHandler();
            echo "<div class='success'>✅ UploadHandler instance created</div>";
            
            // Test filename generation
            $testFilename = $uploadHandler->generateCustomFilename('1234567890', 'payment', null);
            testLog("Testing filename generation", ['filename' => $testFilename]);
            echo "<div class='success'>✅ Filename generation: $testFilename</div>";
            
        } catch (Exception $e) {
            testLog("Upload handler failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 4: Session handling
        echo "<div class='test-section'><h3>4. Testing Session Handling</h3>";
        
        try {
            testLog("Testing session functionality");
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $_SESSION['test_key'] = 'test_value';
            
            if (isset($_SESSION['test_key'])) {
                echo "<div class='success'>✅ Session handling working</div>";
                unset($_SESSION['test_key']);
            } else {
                throw new Exception("Session not working");
            }
            
        } catch (Exception $e) {
            testLog("Session handling failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 5: Database operations
        echo "<div class='test-section'><h3>5. Testing Database Operations</h3>";
        
        try {
            testLog("Testing database operations");
            
            // Test transaction
            $conn->beginTransaction();
            echo "<div class='success'>✅ Transaction started</div>";
            
            // Test data_jamaah table access
            $stmt = $conn->prepare("SELECT COUNT(*) FROM data_jamaah LIMIT 1");
            $stmt->execute();
            echo "<div class='success'>✅ data_jamaah table accessible</div>";
            
            // Test data_paket table access
            $stmt = $conn->prepare("SELECT COUNT(*) FROM data_paket LIMIT 1");
            $stmt->execute();
            echo "<div class='success'>✅ data_paket table accessible</div>";
            
            $conn->rollBack();
            echo "<div class='success'>✅ Transaction rolled back successfully</div>";
            
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            testLog("Database operations failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 6: Directory permissions
        echo "<div class='test-section'><h3>6. Testing Directory Permissions</h3>";
        
        try {
            testLog("Testing directory permissions");
            
            $errorLogDir = __DIR__ . '/error_logs';
            if (!file_exists($errorLogDir)) {
                mkdir($errorLogDir, 0755, true);
                echo "<div class='info'>📁 Created error_logs directory</div>";
            }
            
            if (is_writable($errorLogDir)) {
                echo "<div class='success'>✅ error_logs directory is writable</div>";
            } else {
                throw new Exception("error_logs directory is not writable");
            }
            
            // Test uploads directory
            $uploadsDir = __DIR__ . '/uploads';
            if (!file_exists($uploadsDir)) {
                mkdir($uploadsDir, 0755, true);
                echo "<div class='info'>📁 Created uploads directory</div>";
            }
            
            if (is_writable($uploadsDir)) {
                echo "<div class='success'>✅ uploads directory is writable</div>";
            } else {
                echo "<div class='warning'>⚠️ uploads directory is not writable (may use ephemeral storage)</div>";
            }
            
        } catch (Exception $e) {
            testLog("Directory permissions failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 7: Simulate key operations
        echo "<div class='test-section'><h3>7. Simulating Key Operations</h3>";
        
        try {
            testLog("Simulating key confirm_payment.php operations");
            
            // Simulate form data validation
            $testData = [
                'nik' => '1234567890123456',
                'transfer_account_name' => 'Test Account',
                'nama' => 'Test User',
                'program_pilihan' => 'Test Program'
            ];
            
            foreach (['nik', 'transfer_account_name', 'nama', 'program_pilihan'] as $field) {
                if (empty($testData[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            echo "<div class='success'>✅ Form data validation simulation passed</div>";
            
            // Simulate datetime operations
            $currentDateTime = new DateTime();
            $currentDate = $currentDateTime->format('Y-m-d');
            $currentTime = $currentDateTime->format('H:i:s');
            
            echo "<div class='success'>✅ DateTime operations: $currentDate $currentTime</div>";
            
            // Simulate upload handler operations
            $uploadHandler = new UploadHandler();
            $customName = $uploadHandler->generateCustomFilename($testData['nik'], 'payment', null);
            echo "<div class='success'>✅ Upload handler operations: $customName</div>";
            
        } catch (Exception $e) {
            testLog("Key operations simulation failed", ['error' => $e->getMessage()]);
            echo "<div class='error'>❌ " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        testLog("Test simulation completed");
        
        ?>
        
        <div class="test-section">
            <h3>🎯 Test Summary</h3>
            <p><strong>If all tests above passed, the issue might be:</strong></p>
            <ul>
                <li>File upload size limits being exceeded</li>
                <li>Invalid file types being uploaded</li>
                <li>Session issues in production</li>
                <li>Missing form data in actual requests</li>
                <li>Heroku-specific storage limitations</li>
            </ul>
            
            <p><strong>Next steps:</strong></p>
            <ul>
                <li>Check the production error logs in <a href="error_viewer.php" target="_blank">error_viewer.php</a></li>
                <li>Try uploading a small test file to confirm_payment.php</li>
                <li>Monitor the enhanced logging we just added</li>
            </ul>
        </div>
    </div>
</body>
</html>
