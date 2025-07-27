<?php
/**
 * Debug Confirm Payment - Diagnostic Tool
 * Tests the exact conditions that occur during payment confirmation
 */

require_once 'config.php';

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

// Enhanced error logging function
function logDebugError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/error_logs/debug_confirm_payment_' . date('Y-m-d') . '.log';
    
    $logEntry = "[{$timestamp}] DEBUG: {$message}\n";
    
    if (!empty($context)) {
        $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    
    $logEntry .= str_repeat('-', 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment Debug Tool</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 20px; 
            background: #f8f9fa;
        }
        .container { 
            max-width: 1200px; 
            margin: 0 auto; 
            background: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status { 
            padding: 15px; 
            margin: 10px 0; 
            border-radius: 5px; 
            border-left: 4px solid;
        }
        .success { 
            background: #d4edda; 
            border-color: #28a745; 
            color: #155724; 
        }
        .error { 
            background: #f8d7da; 
            border-color: #dc3545; 
            color: #721c24; 
        }
        .warning { 
            background: #fff3cd; 
            border-color: #ffc107; 
            color: #856404; 
        }
        .info { 
            background: #d1ecf1; 
            border-color: #17a2b8; 
            color: #0c5460; 
        }
        pre { 
            background: #f8f9fa; 
            padding: 15px; 
            border-radius: 5px; 
            overflow-x: auto; 
            font-size: 12px;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .test-section h3 {
            margin-top: 0;
            color: #495057;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #0056b3;
        }
        .simulate-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .form-group {
            margin: 10px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Confirm Payment Debug Tool</h1>
        <p><strong>Environment:</strong> <?= getCurrentEnvironment() ?></p>
        <p><strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?></p>

        <?php
        
        // TEST 1: Database Connection
        echo '<div class="test-section">';
        echo '<h3>🗄️ Database Connection Test</h3>';
        logDebugError("Starting database connection test");
        
        try {
            global $conn, $pdo;
            
            if (!$conn && !$pdo) {
                throw new Exception("No database connection available");
            }
            
            $db = $conn ?? $pdo;
            
            // Test basic connection
            $stmt = $db->query("SELECT 1 as test");
            $result = $stmt->fetch();
            
            if ($result['test'] == 1) {
                echo '<div class="status success">✅ Database connection successful</div>';
                logDebugError("Database connection test passed");
                
                // Test jamaah table access
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM data_jamaah LIMIT 1");
                    $count = $stmt->fetch();
                    echo '<div class="status info">📊 Jamaah table accessible (records: ' . $count['count'] . ')</div>';
                    logDebugError("Jamaah table accessible", ['count' => $count['count']]);
                } catch (Exception $e) {
                    echo '<div class="status error">❌ Jamaah table access failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    logDebugError("Jamaah table access failed", ['error' => $e->getMessage()]);
                }
                
                // Test paket table access
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM data_paket LIMIT 1");
                    $count = $stmt->fetch();
                    echo '<div class="status info">📊 Paket table accessible (records: ' . $count['count'] . ')</div>';
                    logDebugError("Paket table accessible", ['count' => $count['count']]);
                } catch (Exception $e) {
                    echo '<div class="status error">❌ Paket table access failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    logDebugError("Paket table access failed", ['error' => $e->getMessage()]);
                }
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            logDebugError("Database connection test failed", ['error' => $e->getMessage()]);
        }
        echo '</div>';
        
        // TEST 2: File Upload Handler
        echo '<div class="test-section">';
        echo '<h3>📁 Upload Handler Test</h3>';
        logDebugError("Starting upload handler test");
        
        try {
            require_once 'upload_handler.php';
            $uploadHandler = new UploadHandler();
            echo '<div class="status success">✅ Upload Handler class loaded successfully</div>';
            logDebugError("Upload handler loaded successfully");
            
            // Test custom filename generation
            $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'payment', null);
            echo '<div class="status info">📝 Custom filename generation test: ' . htmlspecialchars($testFilename) . '</div>';
            logDebugError("Custom filename generated", ['filename' => $testFilename]);
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Upload Handler failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            logDebugError("Upload handler test failed", ['error' => $e->getMessage()]);
        }
        echo '</div>';
        
        // TEST 3: Email Functions
        echo '<div class="test-section">';
        echo '<h3>📧 Email Functions Test</h3>';
        logDebugError("Starting email functions test");
        
        try {
            require_once 'email_functions.php';
            echo '<div class="status success">✅ Email functions loaded successfully</div>';
            logDebugError("Email functions loaded successfully");
            
            // Check if email constants are defined
            $emailStatus = [];
            $emailStatus['SMTP_HOST'] = defined('SMTP_HOST') ? SMTP_HOST : 'Not defined';
            $emailStatus['SMTP_PORT'] = defined('SMTP_PORT') ? SMTP_PORT : 'Not defined';
            $emailStatus['SMTP_USERNAME'] = defined('SMTP_USERNAME') ? (SMTP_USERNAME ? 'Set' : 'Empty') : 'Not defined';
            $emailStatus['SMTP_PASSWORD'] = defined('SMTP_PASSWORD') ? (SMTP_PASSWORD ? 'Set' : 'Empty') : 'Not defined';
            
            echo '<div class="status info">📊 Email configuration:</div>';
            echo '<pre>' . htmlspecialchars(json_encode($emailStatus, JSON_PRETTY_PRINT)) . '</pre>';
            logDebugError("Email configuration checked", $emailStatus);
            
        } catch (Exception $e) {
            echo '<div class="status error">❌ Email functions failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            logDebugError("Email functions test failed", ['error' => $e->getMessage()]);
        }
        echo '</div>';
        
        // TEST 4: Session Management
        echo '<div class="test-section">';
        echo '<h3>🔐 Session Management Test</h3>';
        logDebugError("Starting session management test");
        
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            echo '<div class="status success">✅ Session started successfully</div>';
            echo '<div class="status info">📊 Session ID: ' . session_id() . '</div>';
            logDebugError("Session management test passed", ['session_id' => session_id()]);
        } catch (Exception $e) {
            echo '<div class="status error">❌ Session management failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
            logDebugError("Session management test failed", ['error' => $e->getMessage()]);
        }
        echo '</div>';
        
        // TEST 5: Directory Permissions
        echo '<div class="test-section">';
        echo '<h3>📂 Directory Permissions Test</h3>';
        logDebugError("Starting directory permissions test");
        
        $directories = ['uploads', 'error_logs', 'temp'];
        foreach ($directories as $dir) {
            $dirPath = __DIR__ . '/' . $dir;
            
            if (!file_exists($dirPath)) {
                if (mkdir($dirPath, 0755, true)) {
                    echo '<div class="status success">✅ Created directory: ' . $dir . '</div>';
                    logDebugError("Directory created", ['directory' => $dir]);
                } else {
                    echo '<div class="status error">❌ Failed to create directory: ' . $dir . '</div>';
                    logDebugError("Directory creation failed", ['directory' => $dir]);
                }
            } else {
                if (is_writable($dirPath)) {
                    echo '<div class="status success">✅ Directory writable: ' . $dir . '</div>';
                    logDebugError("Directory writable", ['directory' => $dir]);
                } else {
                    echo '<div class="status warning">⚠️ Directory not writable: ' . $dir . '</div>';
                    logDebugError("Directory not writable", ['directory' => $dir]);
                }
            }
        }
        echo '</div>';
        
        ?>
        
        <!-- Simulate Payment Form -->
        <div class="test-section">
            <h3>🧪 Simulate Payment Confirmation</h3>
            <div class="simulate-form">
                <form action="confirm_payment.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="nik">NIK (16 digits):</label>
                        <input type="text" id="nik" name="nik" value="1234567890123456" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nama">Nama:</label>
                        <input type="text" id="nama" name="nama" value="Test User" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="transfer_account_name">Transfer Account Name:</label>
                        <input type="text" id="transfer_account_name" name="transfer_account_name" value="Test Account" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="program_pilihan">Program:</label>
                        <select id="program_pilihan" name="program_pilihan" required>
                            <option value="Umroh Test Package">Umroh Test Package</option>
                            <option value="Haji Test Package">Haji Test Package</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="type_room_pilihan">Room Type:</label>
                        <select id="type_room_pilihan" name="type_room_pilihan">
                            <option value="Quad">Quad</option>
                            <option value="Triple">Triple</option>
                            <option value="Double">Double</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_path">Payment Proof (Image/PDF):</label>
                        <input type="file" id="payment_path" name="payment_path" accept="image/*,application/pdf" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_total">Payment Total:</label>
                        <input type="number" id="payment_total" name="payment_total" value="1000" step="0.01">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_type">Payment Type:</label>
                        <input type="text" id="payment_type" name="payment_type" value="Bank Transfer">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_method">Payment Method:</label>
                        <input type="text" id="payment_method" name="payment_method" value="BCA">
                    </div>
                    
                    <button type="submit">🚀 Test Payment Confirmation</button>
                </form>
            </div>
        </div>
        
        <!-- Manual Tests -->
        <div class="test-section">
            <h3>🔧 Manual Tests</h3>
            <p>Use these buttons to perform specific tests:</p>
            
            <button onclick="window.open('error_viewer.php', '_blank')">📊 View Error Logs</button>
            <button onclick="window.open('comprehensive_test_report.php', '_blank')">📋 Comprehensive Test Report</button>
            <button onclick="window.open('form_submission_tester.php', '_blank')">📝 Form Submission Tester</button>
            <button onclick="window.open('test_confirm_payment.php', '_blank')">🧪 Test Confirm Payment</button>
        </div>
        
        <div class="test-section">
            <h3>🔍 Environment Information</h3>
            <pre><?php
            $envInfo = [
                'PHP Version' => PHP_VERSION,
                'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
                'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
                'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'User Agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'Current Working Directory' => getcwd(),
                'Script Filename' => __FILE__,
                'Memory Limit' => ini_get('memory_limit'),
                'Max Execution Time' => ini_get('max_execution_time'),
                'Post Max Size' => ini_get('post_max_size'),
                'Upload Max Filesize' => ini_get('upload_max_filesize'),
                'File Uploads' => ini_get('file_uploads') ? 'Enabled' : 'Disabled',
                'Session Status' => session_status(),
                'Error Reporting' => error_reporting(),
                'Display Errors' => ini_get('display_errors'),
                'Log Errors' => ini_get('log_errors'),
                'Error Log' => ini_get('error_log')
            ];
            
            echo htmlspecialchars(json_encode($envInfo, JSON_PRETTY_PRINT));
            logDebugError("Environment information captured", $envInfo);
            ?></pre>
        </div>
        
        <div class="status info">
            <strong>🔍 Debug Complete!</strong><br>
            Check the log file: <code>error_logs/debug_confirm_payment_<?= date('Y-m-d') ?>.log</code><br>
            All test results have been logged for analysis.
        </div>
    </div>
</body>
</html>
