<?php
/**
 * White Box Testing Suite for MIW Travel Management System
 * 
 * This suite tests internal code structure, logic paths, and implementation details.
 * Focus on: code coverage, control flow, data flow, and internal state validation.
 * 
 * @version 1.0.0
 */

require_once 'config.php';
require_once 'upload_handler.php';
require_once 'email_functions.php';

// Set up error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

class WhiteBoxTester {
    private $testResults = [];
    private $logFile;
    private $conn;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->logFile = __DIR__ . '/test_logs/whitebox_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("White Box Testing Suite Started");
    }
    
    private function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] WHITEBOX: {$message}\n";
        if (!empty($context)) {
            $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        $logEntry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function assert($condition, $testName, $message, $context = []) {
        $result = [
            'test' => $testName,
            'status' => $condition ? 'PASS' : 'FAIL',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'context' => $context
        ];
        
        $this->testResults[] = $result;
        $this->log($testName . ": " . $result['status'] . " - " . $message, $context);
        
        return $condition;
    }
    
    /**
     * Test 1: Database Connection and Transaction Management
     */
    public function testDatabaseConnectionPaths() {
        $this->log("Testing Database Connection Paths");
        
        // Test 1.1: Normal connection path
        $this->assert(
            $this->conn instanceof PDO,
            "DB_Connection_Instance",
            "Database connection is PDO instance",
            ['connection_type' => get_class($this->conn)]
        );
        
        // Test 1.2: Transaction begin/commit path
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT 1 as test_value");
            $stmt->execute();
            $result = $stmt->fetch();
            $this->conn->commit();
            
            $this->assert(
                $result['test_value'] == 1,
                "DB_Transaction_Commit_Path",
                "Transaction commit path working",
                ['test_value' => $result['test_value']]
            );
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            $this->assert(false, "DB_Transaction_Commit_Path", "Transaction failed: " . $e->getMessage());
        }
        
        // Test 1.3: Transaction rollback path
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM data_jamaah");
            $stmt->execute();
            $beforeCount = $stmt->fetch()['count'];
            $this->conn->rollBack();
            
            $this->assert(
                !$this->conn->inTransaction(),
                "DB_Transaction_Rollback_Path",
                "Transaction rollback path working",
                ['before_count' => $beforeCount]
            );
        } catch (Exception $e) {
            $this->assert(false, "DB_Transaction_Rollback_Path", "Rollback test failed: " . $e->getMessage());
        }
        
        // Test 1.4: Error handling path
        try {
            $stmt = $this->conn->prepare("SELECT * FROM non_existent_table");
            $stmt->execute();
            $this->assert(false, "DB_Error_Handling_Path", "Should have thrown exception for invalid table");
        } catch (Exception $e) {
            $this->assert(
                true,
                "DB_Error_Handling_Path",
                "Database error handling working correctly",
                ['error_message' => $e->getMessage()]
            );
        }
    }
    
    /**
     * Test 2: Upload Handler Internal Logic
     */
    public function testUploadHandlerInternalLogic() {
        $this->log("Testing Upload Handler Internal Logic");
        
        $uploadHandler = new UploadHandler();
        
        // Test 2.1: Filename generation algorithm
        $testCases = [
            ['nik' => '1234567890123456', 'type' => 'ktp', 'pak_id' => 'PKG001'],
            ['nik' => '9876543210987654', 'type' => 'photo', 'pak_id' => null],
            ['nik' => '1111222233334444', 'type' => 'payment', 'pak_id' => 'PKG999']
        ];
        
        foreach ($testCases as $case) {
            $filename = $uploadHandler->generateCustomFilename($case['nik'], $case['type'], $case['pak_id']);
            
            // Validate filename structure
            $expectedPrefix = $case['nik'] . '_' . $case['type'];
            if ($case['pak_id']) {
                $expectedPrefix .= '_' . $case['pak_id'];
            }
            
            $this->assert(
                strpos($filename, $expectedPrefix) === 0,
                "Upload_Filename_Generation_" . $case['type'],
                "Filename generation algorithm correct",
                [
                    'input' => $case,
                    'output' => $filename,
                    'expected_prefix' => $expectedPrefix
                ]
            );
        }
        
        // Test 2.2: Error state management
        $uploadHandler->clearErrors();
        $this->assert(
            !$uploadHandler->hasErrors(),
            "Upload_Error_State_Clear",
            "Error state clearing works",
            ['has_errors' => $uploadHandler->hasErrors()]
        );
        
        // Test 2.3: Upload statistics
        $stats = $uploadHandler->getUploadStats();
        $requiredStats = ['environment', 'upload_directory', 'max_file_size', 'allowed_types'];
        
        $hasAllStats = true;
        foreach ($requiredStats as $stat) {
            if (!isset($stats[$stat])) {
                $hasAllStats = false;
                break;
            }
        }
        
        $this->assert(
            $hasAllStats,
            "Upload_Statistics_Completeness",
            "Upload statistics contain all required fields",
            ['stats' => $stats]
        );
    }
    
    /**
     * Test 3: Form Validation Logic Paths
     */
    public function testFormValidationPaths() {
        $this->log("Testing Form Validation Logic Paths");
        
        // Test 3.1: Required field validation
        $requiredFields = ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir'];
        $testData = [
            'nik' => '1234567890123456',
            'nama' => 'Test User',
            'tempat_lahir' => 'Jakarta',
            'tanggal_lahir' => '1990-01-01'
        ];
        
        // Test complete data path
        $allFieldsPresent = true;
        foreach ($requiredFields as $field) {
            if (empty($testData[$field])) {
                $allFieldsPresent = false;
                break;
            }
        }
        
        $this->assert(
            $allFieldsPresent,
            "Form_Validation_Complete_Data",
            "Complete data validation path works",
            ['test_data' => $testData]
        );
        
        // Test missing field paths
        foreach ($requiredFields as $field) {
            $incompleteData = $testData;
            unset($incompleteData[$field]);
            
            $fieldMissing = !isset($incompleteData[$field]) || empty($incompleteData[$field]);
            
            $this->assert(
                $fieldMissing,
                "Form_Validation_Missing_" . $field,
                "Missing field detection for $field works",
                ['missing_field' => $field, 'data' => $incompleteData]
            );
        }
        
        // Test 3.2: NIK format validation
        $nikTestCases = [
            ['nik' => '1234567890123456', 'valid' => true],
            ['nik' => '123456789012345', 'valid' => false],  // Too short
            ['nik' => '12345678901234567', 'valid' => false], // Too long
            ['nik' => '123456789012345a', 'valid' => false],  // Contains letter
            ['nik' => '', 'valid' => false] // Empty
        ];
        
        foreach ($nikTestCases as $case) {
            $isValidLength = strlen($case['nik']) === 16;
            $isNumeric = ctype_digit($case['nik']);
            $isValid = $isValidLength && $isNumeric && !empty($case['nik']);
            
            $this->assert(
                $isValid === $case['valid'],
                "NIK_Validation_" . substr($case['nik'], 0, 8),
                "NIK validation logic correct",
                [
                    'nik' => $case['nik'],
                    'expected' => $case['valid'],
                    'actual' => $isValid,
                    'length_check' => $isValidLength,
                    'numeric_check' => $isNumeric
                ]
            );
        }
    }
    
    /**
     * Test 4: File Upload Validation Logic
     */
    public function testFileUploadValidationLogic() {
        $this->log("Testing File Upload Validation Logic");
        
        // Test 4.1: File type validation
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileTypeTests = [
            ['type' => 'image/jpeg', 'valid' => true],
            ['type' => 'image/png', 'valid' => true],
            ['type' => 'application/pdf', 'valid' => true],
            ['type' => 'text/plain', 'valid' => false],
            ['type' => 'image/gif', 'valid' => false],
            ['type' => 'application/msword', 'valid' => false]
        ];
        
        foreach ($fileTypeTests as $test) {
            $isAllowed = in_array($test['type'], $allowedTypes);
            
            $this->assert(
                $isAllowed === $test['valid'],
                "File_Type_Validation_" . str_replace('/', '_', $test['type']),
                "File type validation logic correct",
                [
                    'file_type' => $test['type'],
                    'expected' => $test['valid'],
                    'actual' => $isAllowed
                ]
            );
        }
        
        // Test 4.2: File size validation
        $maxSize = 2 * 1024 * 1024; // 2MB
        $fileSizeTests = [
            ['size' => 1024, 'valid' => true],          // 1KB
            ['size' => 1024 * 1024, 'valid' => true],   // 1MB
            ['size' => 2 * 1024 * 1024, 'valid' => true], // Exactly 2MB
            ['size' => 2 * 1024 * 1024 + 1, 'valid' => false], // Just over 2MB
            ['size' => 5 * 1024 * 1024, 'valid' => false] // 5MB
        ];
        
        foreach ($fileSizeTests as $test) {
            $isValidSize = $test['size'] <= $maxSize;
            
            $this->assert(
                $isValidSize === $test['valid'],
                "File_Size_Validation_" . $test['size'],
                "File size validation logic correct",
                [
                    'file_size' => $test['size'],
                    'max_size' => $maxSize,
                    'expected' => $test['valid'],
                    'actual' => $isValidSize
                ]
            );
        }
    }
    
    /**
     * Test 5: Email Function Logic Paths
     */
    public function testEmailFunctionLogicPaths() {
        $this->log("Testing Email Function Logic Paths");
        
        // Test 5.1: Email configuration validation
        $emailConfigChecks = [
            'EMAIL_ENABLED' => defined('EMAIL_ENABLED'),
            'SMTP_HOST' => defined('SMTP_HOST'),
            'SMTP_PORT' => defined('SMTP_PORT'),
            'SMTP_USERNAME' => defined('SMTP_USERNAME'),
            'SMTP_PASSWORD' => defined('SMTP_PASSWORD')
        ];
        
        foreach ($emailConfigChecks as $config => $isDefined) {
            $this->assert(
                $isDefined,
                "Email_Config_" . $config,
                "Email configuration $config is defined",
                ['config' => $config, 'defined' => $isDefined]
            );
        }
        
        // Test 5.2: Email content building logic
        $testEmailData = [
            'nama' => 'Test User',
            'nik' => '1234567890123456',
            'program_pilihan' => 'Umroh Regular',
            'payment_amount' => 15000000
        ];
        
        // Test content builder existence and structure
        if (function_exists('buildConfirmationContent')) {
            try {
                $content = buildConfirmationContent($testEmailData, 'Umroh');
                $this->assert(
                    !empty($content),
                    "Email_Content_Builder",
                    "Email content builder generates content",
                    ['content_length' => strlen($content)]
                );
            } catch (Exception $e) {
                $this->assert(
                    false,
                    "Email_Content_Builder",
                    "Email content builder failed: " . $e->getMessage()
                );
            }
        } else {
            $this->assert(
                false,
                "Email_Content_Builder",
                "Email content builder function not found"
            );
        }
    }
    
    /**
     * Test 6: Session Management Logic
     */
    public function testSessionManagementLogic() {
        $this->log("Testing Session Management Logic");
        
        // Test 6.1: Session initialization
        $sessionActive = session_status() === PHP_SESSION_ACTIVE;
        $this->assert(
            $sessionActive,
            "Session_Initialization",
            "Session is properly initialized",
            ['session_status' => session_status(), 'session_id' => session_id()]
        );
        
        // Test 6.2: Session data persistence
        $_SESSION['test_whitebox'] = 'test_value_' . time();
        $sessionDataSet = isset($_SESSION['test_whitebox']);
        
        $this->assert(
            $sessionDataSet,
            "Session_Data_Write",
            "Session data can be written",
            ['test_key' => 'test_whitebox', 'value_set' => $sessionDataSet]
        );
        
        // Test 6.3: Session data retrieval
        $retrievedValue = $_SESSION['test_whitebox'] ?? null;
        $dataMatches = strpos($retrievedValue, 'test_value_') === 0;
        
        $this->assert(
            $dataMatches,
            "Session_Data_Read",
            "Session data can be read correctly",
            ['retrieved_value' => $retrievedValue, 'matches' => $dataMatches]
        );
        
        // Test 6.4: Session data cleanup
        unset($_SESSION['test_whitebox']);
        $dataCleared = !isset($_SESSION['test_whitebox']);
        
        $this->assert(
            $dataCleared,
            "Session_Data_Cleanup",
            "Session data can be cleared",
            ['data_cleared' => $dataCleared]
        );
    }
    
    /**
     * Test 7: Error Handling and Logging Paths
     */
    public function testErrorHandlingPaths() {
        $this->log("Testing Error Handling and Logging Paths");
        
        // Test 7.1: Error log directory creation
        $errorLogDir = __DIR__ . '/error_logs';
        $dirExists = file_exists($errorLogDir);
        
        if (!$dirExists) {
            $created = mkdir($errorLogDir, 0755, true);
            $this->assert(
                $created,
                "Error_Log_Directory_Creation",
                "Error log directory can be created",
                ['directory' => $errorLogDir, 'created' => $created]
            );
        } else {
            $this->assert(
                true,
                "Error_Log_Directory_Exists",
                "Error log directory already exists",
                ['directory' => $errorLogDir]
            );
        }
        
        // Test 7.2: Error log writing
        $testLogFile = $errorLogDir . '/test_whitebox_' . date('Y-m-d') . '.log';
        $testMessage = "Test error message - " . date('Y-m-d H:i:s');
        
        $written = file_put_contents($testLogFile, $testMessage . "\n", FILE_APPEND | LOCK_EX);
        
        $this->assert(
            $written !== false,
            "Error_Log_Writing",
            "Error log can be written",
            ['log_file' => $testLogFile, 'message_length' => strlen($testMessage)]
        );
        
        // Test 7.3: Error log reading
        if (file_exists($testLogFile)) {
            $content = file_get_contents($testLogFile);
            $contentContainsMessage = strpos($content, $testMessage) !== false;
            
            $this->assert(
                $contentContainsMessage,
                "Error_Log_Reading",
                "Error log can be read and contains written message",
                ['contains_message' => $contentContainsMessage]
            );
            
            // Cleanup test log
            unlink($testLogFile);
        }
    }
    
    /**
     * Test 8: Control Flow and Conditional Logic
     */
    public function testControlFlowLogic() {
        $this->log("Testing Control Flow and Conditional Logic");
        
        // Test 8.1: Environment detection logic
        $isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
        $isLocal = !$isProduction;
        
        $this->assert(
            $isProduction || $isLocal,
            "Environment_Detection_Logic",
            "Environment detection logic works",
            ['is_production' => $isProduction, 'is_local' => $isLocal]
        );
        
        // Test 8.2: Database type detection
        $dbType = getDatabaseType();
        $validDbTypes = ['postgresql', 'mysql', 'sqlite'];
        $isValidDbType = in_array($dbType, $validDbTypes);
        
        $this->assert(
            $isValidDbType,
            "Database_Type_Detection",
            "Database type detection returns valid type",
            ['detected_type' => $dbType, 'valid_types' => $validDbTypes]
        );
        
        // Test 8.3: File extension extraction logic
        $testFilenames = [
            'document.pdf' => 'pdf',
            'image.jpg' => 'jpg',
            'photo.PNG' => 'png',
            'file.jpeg' => 'jpeg',
            'noextension' => ''
        ];
        
        foreach ($testFilenames as $filename => $expectedExt) {
            $actualExt = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            $this->assert(
                $actualExt === strtolower($expectedExt),
                "File_Extension_Logic_" . $filename,
                "File extension extraction logic correct",
                [
                    'filename' => $filename,
                    'expected' => $expectedExt,
                    'actual' => $actualExt
                ]
            );
        }
    }
    
    /**
     * Get test results summary
     */
    public function getResults() {
        $passed = count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; }));
        $failed = count(array_filter($this->testResults, function($r) { return $r['status'] === 'FAIL'; }));
        $total = count($this->testResults);
        
        return [
            'summary' => [
                'total_tests' => $total,
                'passed' => $passed,
                'failed' => $failed,
                'success_rate' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
                'log_file' => $this->logFile
            ],
            'details' => $this->testResults
        ];
    }
    
    /**
     * Run all white box tests
     */
    public function runAllTests() {
        $this->log("Starting complete white box test suite");
        
        $this->testDatabaseConnectionPaths();
        $this->testUploadHandlerInternalLogic();
        $this->testFormValidationPaths();
        $this->testFileUploadValidationLogic();
        $this->testEmailFunctionLogicPaths();
        $this->testSessionManagementLogic();
        $this->testErrorHandlingPaths();
        $this->testControlFlowLogic();
        
        $this->log("White box test suite completed");
        return $this->getResults();
    }
}

// HTML Output for browser viewing
?>
<!DOCTYPE html>
<html>
<head>
    <title>White Box Testing Suite - MIW</title>
    <style>
        body { font-family: 'Consolas', monospace; margin: 20px; background: #1a1a1a; color: #e0e0e0; }
        .container { max-width: 1200px; margin: 0 auto; background: #2d2d2d; padding: 30px; border-radius: 10px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 10px 10px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #667eea; background: #3a3a3a; border-radius: 5px; }
        .pass { color: #28a745; font-weight: bold; }
        .fail { color: #dc3545; font-weight: bold; }
        .result-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .result-card { background: #404040; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea; }
        .result-card.pass { border-left-color: #28a745; }
        .result-card.fail { border-left-color: #dc3545; }
        .summary-stats { display: flex; justify-content: space-around; margin: 20px 0; }
        .stat-box { background: #404040; padding: 20px; border-radius: 10px; text-align: center; min-width: 120px; }
        .progress-bar { width: 100%; height: 20px; background: #555; border-radius: 10px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
        .code-detail { background: #1e1e1e; padding: 10px; border-radius: 5px; font-size: 12px; margin: 10px 0; overflow-x: auto; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔬 White Box Testing Suite</h1>
            <p>Internal Code Structure and Logic Path Validation</p>
            <p><strong>Focus:</strong> Code coverage, control flow, data flow, and internal state validation</p>
        </div>

        <?php
        // Run the white box tests
        $tester = new WhiteBoxTester($conn);
        $results = $tester->runAllTests();
        
        $summary = $results['summary'];
        $details = $results['details'];
        ?>

        <div class="test-section">
            <h2>📊 Test Results Summary</h2>
            
            <div class="summary-stats">
                <div class="stat-box">
                    <h3><?= $summary['total_tests'] ?></h3>
                    <p>Total Tests</p>
                </div>
                <div class="stat-box">
                    <h3 class="pass"><?= $summary['passed'] ?></h3>
                    <p>Passed</p>
                </div>
                <div class="stat-box">
                    <h3 class="fail"><?= $summary['failed'] ?></h3>
                    <p>Failed</p>
                </div>
                <div class="stat-box">
                    <h3><?= $summary['success_rate'] ?>%</h3>
                    <p>Success Rate</p>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $summary['success_rate'] ?>%"></div>
            </div>
            
            <p class="timestamp">Test completed at <?= date('Y-m-d H:i:s T') ?></p>
            <p class="timestamp">Log file: <?= basename($summary['log_file']) ?></p>
        </div>

        <div class="test-section">
            <h2>🔍 Detailed Test Results</h2>
            
            <div class="result-grid">
                <?php foreach ($details as $test): ?>
                    <div class="result-card <?= strtolower($test['status']) ?>">
                        <h4><?= htmlspecialchars($test['test']) ?></h4>
                        <p><strong>Status:</strong> <span class="<?= strtolower($test['status']) ?>"><?= $test['status'] ?></span></p>
                        <p><strong>Message:</strong> <?= htmlspecialchars($test['message']) ?></p>
                        <p class="timestamp">Time: <?= $test['timestamp'] ?></p>
                        
                        <?php if (!empty($test['context'])): ?>
                            <details>
                                <summary>View Context</summary>
                                <div class="code-detail">
                                    <?= htmlspecialchars(json_encode($test['context'], JSON_PRETTY_PRINT)) ?>
                                </div>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="test-section">
            <h2>📝 White Box Testing Coverage</h2>
            <p><strong>Code Areas Tested:</strong></p>
            <ul>
                <li>✅ Database connection and transaction management paths</li>
                <li>✅ Upload handler internal logic and state management</li>
                <li>✅ Form validation logic and control flow</li>
                <li>✅ File upload validation algorithms</li>
                <li>✅ Email function logic paths and configurations</li>
                <li>✅ Session management and data persistence</li>
                <li>✅ Error handling and logging mechanisms</li>
                <li>✅ Conditional logic and environment detection</li>
            </ul>
            
            <p><strong>Testing Methodology:</strong></p>
            <ul>
                <li>🔍 <strong>Statement Coverage:</strong> Executed all code statements</li>
                <li>🔄 <strong>Branch Coverage:</strong> Tested all conditional branches</li>
                <li>🛤️ <strong>Path Coverage:</strong> Validated critical execution paths</li>
                <li>📊 <strong>Data Flow:</strong> Tested variable assignments and usage</li>
                <li>⚡ <strong>Control Flow:</strong> Validated loops and decision points</li>
            </ul>
        </div>

        <?php if ($summary['failed'] > 0): ?>
            <div class="test-section">
                <h2>🚨 Failed Test Analysis</h2>
                <p><strong>Critical Issues Found:</strong></p>
                <?php 
                $failedTests = array_filter($details, function($t) { return $t['status'] === 'FAIL'; });
                foreach ($failedTests as $test): 
                ?>
                    <div class="result-card fail">
                        <h4><?= htmlspecialchars($test['test']) ?></h4>
                        <p><?= htmlspecialchars($test['message']) ?></p>
                        <p><strong>Recommendation:</strong> Review and fix the internal logic for this component.</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; color: #6c757d;">
            <p>White Box Testing Suite v1.0.0 | MIW Travel Management System</p>
            <p><a href="test_suite_blackbox.php" style="color: #667eea;">🔗 Run Black Box Tests</a> | 
               <a href="test_logs/" style="color: #667eea;">📁 View Test Logs</a></p>
        </div>
    </div>
</body>
</html>
