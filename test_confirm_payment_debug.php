<?php
/**
 * Debug script for confirm_payment.php HTTP 500 error
 * This will test each component systematically to identify the issue
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Payment Debug</title>
    <style>
        body { font-family: 'Consolas', monospace; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .header { background: #dc3545; color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #dc3545; background: #f8f9fa; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .critical { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #2d2d2d; color: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Confirm Payment Debug</h1>
            <p>Systematic testing of confirm_payment.php components</p>
        </div>

        <?php
        
        // Debug logging function
        function debugLog($message, $context = []) {
            $timestamp = date('Y-m-d H:i:s');
            if (!empty($context)) {
                echo "<div>[$timestamp] $message<br><pre>" . json_encode($context, JSON_PRETTY_PRINT) . "</pre></div>";
            } else {
                echo "<div>[$timestamp] $message</div>";
            }
        }
        
        // Test 1: Environment Check
        echo "<div class='test-section'><h3>1. Environment Check</h3>";
        
        debugLog("PHP Version: " . PHP_VERSION);
        debugLog("Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'));
        debugLog("Environment: " . (isset($_ENV['DYNO']) ? 'Heroku' : 'Local'));
        
        // Critical PHP settings
        $settings = [
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'file_uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled'
        ];
        
        foreach ($settings as $setting => $value) {
            echo "<div class='info'>$setting: $value</div>";
        }
        
        echo "</div>";
        
        // Test 2: Core Files
        echo "<div class='test-section'><h3>2. Core Files Test</h3>";
        
        $coreFiles = [
            'config.php' => 'Database configuration',
            'email_functions.php' => 'Email functionality',
            'upload_handler.php' => 'File upload handling',
            'heroku_file_manager.php' => 'File management'
        ];
        
        foreach ($coreFiles as $file => $description) {
            if (file_exists($file)) {
                echo "<div class='success'>✅ $file: Available ($description)</div>";
                
                // Check if file is readable
                if (is_readable($file)) {
                    echo "<div class='info'>  - File is readable</div>";
                } else {
                    echo "<div class='error'>  - File is NOT readable</div>";
                }
                
                // Check file size
                $size = filesize($file);
                if ($size > 0) {
                    echo "<div class='info'>  - File size: $size bytes</div>";
                } else {
                    echo "<div class='warning'>  - File is empty or size is 0</div>";
                }
            } else {
                echo "<div class='error'>❌ $file: Missing ($description)</div>";
            }
        }
        
        echo "</div>";
        
        // Test 3: Database Connection
        echo "<div class='test-section'><h3>3. Database Connection Test</h3>";
        
        try {
            require_once 'config.php';
            
            if (isset($conn) && $conn instanceof PDO) {
                echo "<div class='success'>✅ Database connection established</div>";
                
                // Test basic query
                try {
                    $stmt = $conn->query("SELECT 1 as test");
                    $result = $stmt->fetch();
                    if ($result['test'] == 1) {
                        echo "<div class='success'>✅ Database query test passed</div>";
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Database query failed: " . $e->getMessage() . "</div>";
                }
                
                // Test required tables
                $requiredTables = ['data_jamaah', 'data_paket'];
                foreach ($requiredTables as $table) {
                    try {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table LIMIT 1");
                        $result = $stmt->fetch();
                        echo "<div class='success'>✅ Table $table: {$result['count']} records</div>";
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Table $table: Error - " . $e->getMessage() . "</div>";
                    }
                }
                
            } else {
                echo "<div class='error'>❌ Database connection failed or invalid</div>";
                if (isset($conn)) {
                    echo "<div class='info'>Connection type: " . gettype($conn) . "</div>";
                } else {
                    echo "<div class='info'>$conn variable not set</div>";
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Config loading failed: " . $e->getMessage() . "</div>";
            echo "<div class='info'>Error in file: " . $e->getFile() . " at line " . $e->getLine() . "</div>";
        }
        
        echo "</div>";
        
        // Test 4: Email Functions
        echo "<div class='test-section'><h3>4. Email Functions Test</h3>";
        
        try {
            require_once 'email_functions.php';
            echo "<div class='success'>✅ email_functions.php loaded</div>";
            
            // Check email configuration constants
            $emailConstants = [
                'EMAIL_ENABLED' => defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'Enabled' : 'Disabled') : 'Not defined',
                'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
                'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined',
                'EMAIL_FROM' => defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined'
            ];
            
            foreach ($emailConstants as $constant => $value) {
                if ($value === 'Not defined') {
                    echo "<div class='warning'>⚠️ $constant: $value</div>";
                } else {
                    echo "<div class='info'>📧 $constant: $value</div>";
                }
            }
            
            // Check if PHPMailer exists
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                echo "<div class='success'>✅ PHPMailer class available</div>";
            } else {
                echo "<div class='error'>❌ PHPMailer class not found</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Email functions loading failed: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 5: Upload Handler
        echo "<div class='test-section'><h3>5. Upload Handler Test</h3>";
        
        try {
            require_once 'upload_handler.php';
            echo "<div class='success'>✅ upload_handler.php loaded</div>";
            
            // Try to instantiate UploadHandler
            try {
                $uploadHandler = new UploadHandler();
                echo "<div class='success'>✅ UploadHandler instance created</div>";
                
                // Test method availability
                if (method_exists($uploadHandler, 'generateCustomFilename')) {
                    echo "<div class='success'>✅ generateCustomFilename method available</div>";
                    
                    try {
                        $testFilename = $uploadHandler->generateCustomFilename('1234567890', 'payment', null);
                        echo "<div class='success'>✅ Filename generation test: $testFilename</div>";
                    } catch (Exception $e) {
                        echo "<div class='error'>❌ Filename generation failed: " . $e->getMessage() . "</div>";
                    }
                } else {
                    echo "<div class='error'>❌ generateCustomFilename method not found</div>";
                }
                
                if (method_exists($uploadHandler, 'handleUpload')) {
                    echo "<div class='success'>✅ handleUpload method available</div>";
                } else {
                    echo "<div class='error'>❌ handleUpload method not found</div>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>❌ UploadHandler instantiation failed: " . $e->getMessage() . "</div>";
                echo "<div class='info'>Error in file: " . $e->getFile() . " at line " . $e->getLine() . "</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Upload handler loading failed: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 6: Directory Permissions
        echo "<div class='test-section'><h3>6. Directory Permissions Test</h3>";
        
        $directories = [
            'uploads' => __DIR__ . '/uploads',
            'error_logs' => __DIR__ . '/error_logs',
            'temp' => __DIR__ . '/temp'
        ];
        
        foreach ($directories as $name => $path) {
            if (file_exists($path)) {
                echo "<div class='success'>✅ Directory $name: Exists</div>";
                
                if (is_readable($path)) {
                    echo "<div class='info'>  - Readable: Yes</div>";
                } else {
                    echo "<div class='warning'>  - Readable: No</div>";
                }
                
                if (is_writable($path)) {
                    echo "<div class='info'>  - Writable: Yes</div>";
                } else {
                    echo "<div class='error'>  - Writable: No</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ Directory $name: Missing ($path)</div>";
                
                // Try to create it
                try {
                    mkdir($path, 0755, true);
                    echo "<div class='success'>  - Created successfully</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>  - Failed to create: " . $e->getMessage() . "</div>";
                }
            }
        }
        
        echo "</div>";
        
        // Test 7: Session Functionality
        echo "<div class='test-section'><h3>7. Session Functionality Test</h3>";
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
                echo "<div class='success'>✅ Session started</div>";
            } else {
                echo "<div class='info'>📝 Session already active</div>";
            }
            
            $_SESSION['test_key'] = 'test_value_' . time();
            
            if (isset($_SESSION['test_key'])) {
                echo "<div class='success'>✅ Session write/read test passed</div>";
                unset($_SESSION['test_key']);
            } else {
                echo "<div class='error'>❌ Session write/read test failed</div>";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Session test failed: " . $e->getMessage() . "</div>";
        }
        
        echo "</div>";
        
        // Test 8: POST Data Simulation
        echo "<div class='test-section'><h3>8. POST Data Requirements Test</h3>";
        
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        echo "<div class='info'>Required POST fields for confirm_payment.php:</div>";
        
        foreach ($requiredFields as $field) {
            echo "<div class='info'>  - $field</div>";
        }
        
        echo "<div class='info'>Required FILES:</div>";
        echo "<div class='info'>  - payment_path (image/jpeg, image/png, or application/pdf, max 2MB)</div>";
        
        echo "</div>";
        
        // Summary
        echo "<div class='test-section'><h3>🎯 Summary & Recommendations</h3>";
        echo "<div class='info'>Based on the tests above, check for:</div>";
        echo "<div class='info'>1. Missing required files or classes</div>";
        echo "<div class='info'>2. Database connection issues</div>";
        echo "<div class='info'>3. Directory permission problems</div>";
        echo "<div class='info'>4. PHP configuration limits</div>";
        echo "<div class='info'>5. Missing POST/FILES data in form submission</div>";
        echo "<div class='info'>6. Email configuration issues</div>";
        echo "</div>";
        
        ?>
        
    </div>
</body>
</html>
