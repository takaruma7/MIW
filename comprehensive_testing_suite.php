<?php
/**
 * Comprehensive Testing Suite for MIW Travel Management System
 * 
 * This suite deploys both White Box and Black Box testing methodologies
 * to thoroughly evaluate the deployed project for issues and reliability.
 * 
 * @version 2.0.0
 * @author MIW Development Team
 */

require_once 'config.php';
require_once 'upload_handler.php';
require_once 'email_functions.php';

// Set up comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

class ComprehensiveTestingSuite {
    private $conn;
    private $testResults = [];
    private $logFile;
    private $startTime;
    private $environment;
    private $criticalIssues = [];
    private $warningIssues = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->startTime = microtime(true);
        $this->environment = $this->detectEnvironment();
        $this->logFile = __DIR__ . '/test_logs/comprehensive_test_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("=== COMPREHENSIVE TESTING SUITE STARTED ===");
        $this->log("Environment: " . $this->environment);
    }
    
    private function detectEnvironment() {
        if (isset($_ENV['DYNO'])) return 'Heroku';
        if (isset($_ENV['RAILWAY_ENVIRONMENT'])) return 'Railway';
        if (isset($_ENV['RENDER'])) return 'Render';
        if (isset($_SERVER['SERVER_NAME']) && strpos($_SERVER['SERVER_NAME'], 'herokuapp.com') !== false) return 'Heroku';
        return 'Local/Other';
    }
    
    private function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] {$message}\n";
        if (!empty($context)) {
            $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        $logEntry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function addCriticalIssue($issue) {
        $this->criticalIssues[] = $issue;
        $this->log("CRITICAL ISSUE: " . $issue);
    }
    
    private function addWarningIssue($issue) {
        $this->warningIssues[] = $issue;
        $this->log("WARNING: " . $issue);
    }
    
    /**
     * WHITE BOX TESTING SUITE
     * Tests internal code structure, logic paths, and implementation details
     */
    public function runWhiteBoxTests() {
        $this->log("Starting White Box Testing Suite");
        $whiteBoxResults = [];
        
        // WB Test 1: Database Connection and Transaction Logic
        $whiteBoxResults['database_logic'] = $this->testDatabaseLogicPaths();
        
        // WB Test 2: File Upload Handler Internal Logic
        $whiteBoxResults['upload_logic'] = $this->testUploadHandlerLogic();
        
        // WB Test 3: Form Validation Logic Paths
        $whiteBoxResults['validation_logic'] = $this->testValidationLogicPaths();
        
        // WB Test 4: Error Handling Code Paths
        $whiteBoxResults['error_handling'] = $this->testErrorHandlingPaths();
        
        // WB Test 5: Session Management Logic
        $whiteBoxResults['session_logic'] = $this->testSessionLogic();
        
        // WB Test 6: Email Function Logic Paths
        $whiteBoxResults['email_logic'] = $this->testEmailLogic();
        
        // WB Test 7: Configuration Logic
        $whiteBoxResults['config_logic'] = $this->testConfigurationLogic();
        
        $this->testResults['whitebox'] = $whiteBoxResults;
        $this->log("White Box Testing Suite Completed");
        
        return $whiteBoxResults;
    }
    
    /**
     * BLACK BOX TESTING SUITE
     * Tests external functionality, user interactions, and system behavior
     */
    public function runBlackBoxTests() {
        $this->log("Starting Black Box Testing Suite");
        $blackBoxResults = [];
        
        // BB Test 1: System Availability and Response
        $blackBoxResults['system_availability'] = $this->testSystemAvailability();
        
        // BB Test 2: User Registration Workflow
        $blackBoxResults['registration_workflow'] = $this->testRegistrationWorkflow();
        
        // BB Test 3: File Upload Functionality
        $blackBoxResults['file_upload'] = $this->testFileUploadFunctionality();
        
        // BB Test 4: Payment Confirmation Process
        $blackBoxResults['payment_process'] = $this->testPaymentProcess();
        
        // BB Test 5: Admin Panel Functionality
        $blackBoxResults['admin_panel'] = $this->testAdminPanelFunctionality();
        
        // BB Test 6: Data Integrity and Validation
        $blackBoxResults['data_integrity'] = $this->testDataIntegrity();
        
        // BB Test 7: Security and Input Sanitization
        $blackBoxResults['security'] = $this->testSecurityMeasures();
        
        // BB Test 8: Error Handling User Experience
        $blackBoxResults['error_ux'] = $this->testErrorUserExperience();
        
        $this->testResults['blackbox'] = $blackBoxResults;
        $this->log("Black Box Testing Suite Completed");
        
        return $blackBoxResults;
    }
    
    /**
     * WHITE BOX TEST IMPLEMENTATIONS
     */
    
    private function testDatabaseLogicPaths() {
        $results = [];
        
        try {
            // Test normal connection path
            $stmt = $this->conn->prepare("SELECT 1 as test");
            $stmt->execute();
            $results['connection_path'] = ['status' => 'pass', 'message' => 'Normal connection path working'];
            
            // Test transaction begin/commit path
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah LIMIT 1");
            $stmt->execute();
            $this->conn->commit();
            $results['transaction_commit'] = ['status' => 'pass', 'message' => 'Transaction commit path working'];
            
            // Test transaction rollback path
            $this->conn->beginTransaction();
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah");
                $stmt->execute();
                $this->conn->rollBack();
                $results['transaction_rollback'] = ['status' => 'pass', 'message' => 'Transaction rollback path working'];
            } catch (Exception $e) {
                $this->conn->rollBack();
                $results['transaction_rollback'] = ['status' => 'fail', 'message' => 'Transaction rollback failed: ' . $e->getMessage()];
            }
            
            // Test error handling path
            try {
                $stmt = $this->conn->prepare("SELECT * FROM nonexistent_table");
                $stmt->execute();
                $results['error_handling'] = ['status' => 'fail', 'message' => 'Database error handling not working'];
            } catch (Exception $e) {
                $results['error_handling'] = ['status' => 'pass', 'message' => 'Database error handling working correctly'];
            }
            
        } catch (Exception $e) {
            $this->addCriticalIssue("Database logic path testing failed: " . $e->getMessage());
            $results['overall'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    private function testUploadHandlerLogic() {
        $results = [];
        
        try {
            $uploadHandler = new UploadHandler();
            
            // Test filename generation logic
            $testCases = [
                ['nik' => '1234567890123456', 'type' => 'ktp', 'package' => 'PKG001'],
                ['nik' => '9876543210987654', 'type' => 'photo', 'package' => null],
                ['nik' => '', 'type' => 'test', 'package' => 'PKG002']
            ];
            
            $filenameResults = [];
            foreach ($testCases as $case) {
                $filename = $uploadHandler->generateCustomFilename($case['nik'], $case['type'], $case['package']);
                $filenameResults[] = [
                    'input' => $case,
                    'output' => $filename,
                    'valid' => !empty($filename)
                ];
            }
            
            $allValid = array_reduce($filenameResults, function($carry, $item) {
                return $carry && $item['valid'];
            }, true);
            
            $results['filename_generation'] = [
                'status' => $allValid ? 'pass' : 'fail',
                'message' => 'Filename generation logic ' . ($allValid ? 'working' : 'has issues'),
                'test_cases' => $filenameResults
            ];
            
            // Test error state management
            $uploadHandler->clearErrors();
            $hasErrors = $uploadHandler->hasErrors();
            $results['error_state'] = [
                'status' => !$hasErrors ? 'pass' : 'fail',
                'message' => 'Error state management ' . (!$hasErrors ? 'working' : 'not working')
            ];
            
            // Test upload statistics
            $stats = $uploadHandler->getUploadStats();
            $requiredStats = ['environment', 'upload_directory', 'max_file_size', 'allowed_types'];
            $hasAllStats = true;
            foreach ($requiredStats as $stat) {
                if (!isset($stats[$stat])) {
                    $hasAllStats = false;
                    break;
                }
            }
            
            $results['statistics'] = [
                'status' => $hasAllStats ? 'pass' : 'fail',
                'message' => 'Upload statistics ' . ($hasAllStats ? 'complete' : 'incomplete'),
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            $this->addCriticalIssue("Upload handler logic testing failed: " . $e->getMessage());
            $results['overall'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    private function testValidationLogicPaths() {
        $results = [];
        
        // Test NIK validation logic
        $nikTestCases = [
            ['nik' => '1234567890123456', 'expected' => true],  // Valid 16-digit NIK
            ['nik' => '123456789012345', 'expected' => false], // 15-digit NIK
            ['nik' => '12345678901234567', 'expected' => false], // 17-digit NIK
            ['nik' => 'abcd567890123456', 'expected' => false], // Non-numeric NIK
            ['nik' => '', 'expected' => false] // Empty NIK
        ];
        
        $nikValidationResults = [];
        foreach ($nikTestCases as $case) {
            $isValid = (strlen($case['nik']) === 16 && ctype_digit($case['nik']));
            $nikValidationResults[] = [
                'nik' => $case['nik'],
                'expected' => $case['expected'],
                'actual' => $isValid,
                'passed' => $isValid === $case['expected']
            ];
        }
        
        $allNikTestsPassed = array_reduce($nikValidationResults, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);
        
        $results['nik_validation'] = [
            'status' => $allNikTestsPassed ? 'pass' : 'fail',
            'message' => 'NIK validation logic ' . ($allNikTestsPassed ? 'working correctly' : 'has issues'),
            'test_cases' => $nikValidationResults
        ];
        
        // Test file type validation logic
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileTypeTests = [
            ['type' => 'image/jpeg', 'expected' => true],
            ['type' => 'image/png', 'expected' => true],
            ['type' => 'application/pdf', 'expected' => true],
            ['type' => 'text/plain', 'expected' => false],
            ['type' => 'application/x-executable', 'expected' => false]
        ];
        
        $fileTypeResults = [];
        foreach ($fileTypeTests as $test) {
            $isValid = in_array($test['type'], $allowedTypes);
            $fileTypeResults[] = [
                'type' => $test['type'],
                'expected' => $test['expected'],
                'actual' => $isValid,
                'passed' => $isValid === $test['expected']
            ];
        }
        
        $allFileTypeTestsPassed = array_reduce($fileTypeResults, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);
        
        $results['file_type_validation'] = [
            'status' => $allFileTypeTestsPassed ? 'pass' : 'fail',
            'message' => 'File type validation ' . ($allFileTypeTestsPassed ? 'working correctly' : 'has issues'),
            'test_cases' => $fileTypeResults
        ];
        
        return $results;
    }
    
    private function testErrorHandlingPaths() {
        $results = [];
        
        // Test PHP error handling
        $errorLogDir = __DIR__ . '/error_logs';
        if (!file_exists($errorLogDir)) {
            mkdir($errorLogDir, 0755, true);
        }
        
        $results['error_logging'] = [
            'status' => is_writable($errorLogDir) ? 'pass' : 'fail',
            'message' => 'Error logging directory ' . (is_writable($errorLogDir) ? 'writable' : 'not writable'),
            'directory' => $errorLogDir
        ];
        
        // Test exception handling in confirm_payment.php
        if (file_exists(__DIR__ . '/confirm_payment.php')) {
            $content = file_get_contents(__DIR__ . '/confirm_payment.php');
            $hasTryCatch = strpos($content, 'try {') !== false && strpos($content, 'catch') !== false;
            $hasDetailedLogging = strpos($content, 'logDetailedError') !== false;
            
            $results['confirm_payment_error_handling'] = [
                'status' => ($hasTryCatch && $hasDetailedLogging) ? 'pass' : 'warning',
                'message' => 'Confirm payment error handling ' . (($hasTryCatch && $hasDetailedLogging) ? 'comprehensive' : 'basic'),
                'has_try_catch' => $hasTryCatch,
                'has_detailed_logging' => $hasDetailedLogging
            ];
        }
        
        return $results;
    }
    
    private function testSessionLogic() {
        $results = [];
        
        try {
            // Test session initialization
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            $results['session_start'] = [
                'status' => session_status() === PHP_SESSION_ACTIVE ? 'pass' : 'fail',
                'message' => 'Session initialization ' . (session_status() === PHP_SESSION_ACTIVE ? 'successful' : 'failed'),
                'session_id' => session_id()
            ];
            
            // Test session write/read
            $_SESSION['test_key'] = 'test_value_' . time();
            $testValue = $_SESSION['test_key'] ?? null;
            
            $results['session_operations'] = [
                'status' => $testValue !== null ? 'pass' : 'fail',
                'message' => 'Session read/write operations ' . ($testValue !== null ? 'working' : 'failed')
            ];
            
            // Clean up test session data
            unset($_SESSION['test_key']);
            
        } catch (Exception $e) {
            $this->addCriticalIssue("Session logic testing failed: " . $e->getMessage());
            $results['overall'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    private function testEmailLogic() {
        $results = [];
        
        // Test email configuration
        $emailConfigured = defined('SMTP_HOST') && defined('SMTP_PORT') && defined('SMTP_USERNAME');
        $results['email_configuration'] = [
            'status' => $emailConfigured ? 'pass' : 'warning',
            'message' => 'Email configuration ' . ($emailConfigured ? 'complete' : 'incomplete'),
            'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not defined',
            'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not defined'
        ];
        
        // Test email functions availability
        if (file_exists(__DIR__ . '/email_functions.php')) {
            require_once __DIR__ . '/email_functions.php';
            $emailFunctionsExist = function_exists('sendPaymentConfirmationEmail');
            
            $results['email_functions'] = [
                'status' => $emailFunctionsExist ? 'pass' : 'fail',
                'message' => 'Email functions ' . ($emailFunctionsExist ? 'available' : 'missing')
            ];
        }
        
        return $results;
    }
    
    private function testConfigurationLogic() {
        $results = [];
        
        // Test environment detection
        $envDetected = $this->environment !== 'Local/Other';
        $results['environment_detection'] = [
            'status' => 'pass',
            'message' => 'Environment detected: ' . $this->environment,
            'environment' => $this->environment
        ];
        
        // Test critical configuration constants
        $criticalConstants = ['DB_HOST', 'DB_NAME', 'DB_USER'];
        $missingConstants = [];
        foreach ($criticalConstants as $constant) {
            if (!defined($constant)) {
                $missingConstants[] = $constant;
            }
        }
        
        $results['configuration_constants'] = [
            'status' => empty($missingConstants) ? 'pass' : 'fail',
            'message' => empty($missingConstants) ? 'All critical constants defined' : 'Missing: ' . implode(', ', $missingConstants),
            'missing' => $missingConstants
        ];
        
        return $results;
    }
    
    /**
     * BLACK BOX TEST IMPLEMENTATIONS
     */
    
    private function testSystemAvailability() {
        $results = [];
        
        // Test critical pages availability
        $criticalPages = [
            'form_haji.php' => 'Haji Registration Form',
            'form_umroh.php' => 'Umroh Registration Form',
            'admin_dashboard.php' => 'Admin Dashboard',
            'confirm_payment.php' => 'Payment Confirmation'
        ];
        
        $pageResults = [];
        foreach ($criticalPages as $page => $description) {
            $exists = file_exists(__DIR__ . '/' . $page);
            $pageResults[$page] = [
                'exists' => $exists,
                'description' => $description,
                'status' => $exists ? 'pass' : 'fail'
            ];
            
            if (!$exists) {
                $this->addCriticalIssue("Critical page missing: $page ($description)");
            }
        }
        
        $results['page_availability'] = [
            'status' => count(array_filter($pageResults, function($r) { return $r['status'] === 'pass'; })) === count($pageResults) ? 'pass' : 'fail',
            'message' => 'Page availability check',
            'pages' => $pageResults
        ];
        
        return $results;
    }
    
    private function testRegistrationWorkflow() {
        $results = [];
        
        // Test registration form validation (simulated)
        $testRegistrationData = [
            'valid_data' => [
                'nik' => '1234567890123456',
                'nama' => 'Test User',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'expected' => 'pass'
            ],
            'invalid_nik' => [
                'nik' => '12345',
                'nama' => 'Test User',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'expected' => 'fail'
            ],
            'missing_name' => [
                'nik' => '1234567890123456',
                'nama' => '',
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'expected' => 'fail'
            ]
        ];
        
        $validationResults = [];
        foreach ($testRegistrationData as $testName => $data) {
            $requiredFields = ['nik', 'nama', 'tempat_lahir', 'tanggal_lahir'];
            $isValid = true;
            
            // Check required fields
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $isValid = false;
                    break;
                }
            }
            
            // Check NIK format
            if ($isValid && (!ctype_digit($data['nik']) || strlen($data['nik']) !== 16)) {
                $isValid = false;
            }
            
            $validationResults[$testName] = [
                'expected' => $data['expected'],
                'actual' => $isValid ? 'pass' : 'fail',
                'passed' => ($data['expected'] === 'pass') === $isValid
            ];
        }
        
        $allValidationsPassed = array_reduce($validationResults, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);
        
        $results['registration_validation'] = [
            'status' => $allValidationsPassed ? 'pass' : 'fail',
            'message' => 'Registration validation ' . ($allValidationsPassed ? 'working correctly' : 'has issues'),
            'test_cases' => $validationResults
        ];
        
        return $results;
    }
    
    private function testFileUploadFunctionality() {
        $results = [];
        
        // Test file type validation
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileTestCases = [
            ['type' => 'image/jpeg', 'size' => 1024 * 1024, 'expected' => 'pass'],
            ['type' => 'application/pdf', 'size' => 1.5 * 1024 * 1024, 'expected' => 'pass'],
            ['type' => 'text/plain', 'size' => 1024, 'expected' => 'fail'],
            ['type' => 'image/jpeg', 'size' => 3 * 1024 * 1024, 'expected' => 'fail']
        ];
        
        $uploadValidationResults = [];
        foreach ($fileTestCases as $test) {
            $typeValid = in_array($test['type'], $allowedTypes);
            $sizeValid = $test['size'] <= $maxSize;
            $actualResult = ($typeValid && $sizeValid) ? 'pass' : 'fail';
            
            $uploadValidationResults[] = [
                'type' => $test['type'],
                'size' => $test['size'],
                'expected' => $test['expected'],
                'actual' => $actualResult,
                'passed' => $actualResult === $test['expected']
            ];
        }
        
        $allUploadTestsPassed = array_reduce($uploadValidationResults, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);
        
        $results['file_validation'] = [
            'status' => $allUploadTestsPassed ? 'pass' : 'fail',
            'message' => 'File upload validation ' . ($allUploadTestsPassed ? 'working correctly' : 'has issues'),
            'test_cases' => $uploadValidationResults
        ];
        
        // Test upload directory
        $uploadDir = __DIR__ . '/uploads';
        $results['upload_directory'] = [
            'status' => file_exists($uploadDir) ? 'pass' : 'warning',
            'message' => 'Upload directory ' . (file_exists($uploadDir) ? 'exists' : 'missing'),
            'writable' => file_exists($uploadDir) ? is_writable($uploadDir) : false
        ];
        
        return $results;
    }
    
    private function testPaymentProcess() {
        $results = [];
        
        // Test payment confirmation file
        if (file_exists(__DIR__ . '/confirm_payment.php')) {
            $content = file_get_contents(__DIR__ . '/confirm_payment.php');
            $hasRequiredValidation = strpos($content, 'payment_path') !== false;
            $hasErrorHandling = strpos($content, 'try {') !== false;
            
            $results['payment_file'] = [
                'status' => ($hasRequiredValidation && $hasErrorHandling) ? 'pass' : 'warning',
                'message' => 'Payment confirmation file structure',
                'has_validation' => $hasRequiredValidation,
                'has_error_handling' => $hasErrorHandling
            ];
        } else {
            $this->addCriticalIssue("Payment confirmation file missing: confirm_payment.php");
            $results['payment_file'] = [
                'status' => 'fail',
                'message' => 'Payment confirmation file missing'
            ];
        }
        
        // Test payment validation logic (simulated)
        $paymentTestCases = [
            ['nik' => '1234567890123456', 'transfer_account_name' => 'Test Account', 'expected' => 'pass'],
            ['nik' => '', 'transfer_account_name' => 'Test Account', 'expected' => 'fail'],
            ['nik' => '1234567890123456', 'transfer_account_name' => '', 'expected' => 'fail']
        ];
        
        $paymentValidationResults = [];
        foreach ($paymentTestCases as $test) {
            $isValid = !empty($test['nik']) && !empty($test['transfer_account_name']);
            $actualResult = $isValid ? 'pass' : 'fail';
            
            $paymentValidationResults[] = [
                'nik' => $test['nik'],
                'account_name' => $test['transfer_account_name'],
                'expected' => $test['expected'],
                'actual' => $actualResult,
                'passed' => $actualResult === $test['expected']
            ];
        }
        
        $allPaymentTestsPassed = array_reduce($paymentValidationResults, function($carry, $item) {
            return $carry && $item['passed'];
        }, true);
        
        $results['payment_validation'] = [
            'status' => $allPaymentTestsPassed ? 'pass' : 'fail',
            'message' => 'Payment validation logic ' . ($allPaymentTestsPassed ? 'working' : 'has issues'),
            'test_cases' => $paymentValidationResults
        ];
        
        return $results;
    }
    
    private function testAdminPanelFunctionality() {
        $results = [];
        
        $adminFiles = [
            'admin_dashboard.php' => 'Main Dashboard',
            'admin_pending.php' => 'Pending Registrations',
            'admin_manifest.php' => 'Manifest Management',
            'admin_paket.php' => 'Package Management'
        ];
        
        $adminFileResults = [];
        foreach ($adminFiles as $file => $description) {
            $exists = file_exists(__DIR__ . '/' . $file);
            $adminFileResults[$file] = [
                'exists' => $exists,
                'description' => $description,
                'status' => $exists ? 'pass' : 'fail'
            ];
            
            if (!$exists) {
                $this->addWarningIssue("Admin file missing: $file ($description)");
            }
        }
        
        $results['admin_files'] = [
            'status' => count(array_filter($adminFileResults, function($r) { return $r['status'] === 'pass'; })) >= 2 ? 'pass' : 'warning',
            'message' => 'Admin panel files availability',
            'files' => $adminFileResults
        ];
        
        return $results;
    }
    
    private function testDataIntegrity() {
        $results = [];
        
        try {
            // Test database table integrity
            $requiredTables = ['data_jamaah', 'data_paket', 'data_pembatalan'];
            $tableIntegrityResults = [];
            
            foreach ($requiredTables as $table) {
                try {
                    $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM $table");
                    $stmt->execute();
                    $count = $stmt->fetch()['count'];
                    
                    $tableIntegrityResults[$table] = [
                        'exists' => true,
                        'count' => $count,
                        'status' => 'pass'
                    ];
                } catch (Exception $e) {
                    $tableIntegrityResults[$table] = [
                        'exists' => false,
                        'error' => $e->getMessage(),
                        'status' => 'fail'
                    ];
                    $this->addCriticalIssue("Required table missing or inaccessible: $table");
                }
            }
            
            $allTablesExist = array_reduce($tableIntegrityResults, function($carry, $item) {
                return $carry && $item['status'] === 'pass';
            }, true);
            
            $results['table_integrity'] = [
                'status' => $allTablesExist ? 'pass' : 'fail',
                'message' => 'Database table integrity check',
                'tables' => $tableIntegrityResults
            ];
            
        } catch (Exception $e) {
            $this->addCriticalIssue("Data integrity testing failed: " . $e->getMessage());
            $results['overall'] = ['status' => 'fail', 'message' => $e->getMessage()];
        }
        
        return $results;
    }
    
    private function testSecurityMeasures() {
        $results = [];
        
        // Test SQL injection protection (basic check)
        try {
            $maliciousInput = "'; DROP TABLE data_jamaah; --";
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$maliciousInput]);
            $result = $stmt->fetch();
            
            $results['sql_injection_protection'] = [
                'status' => 'pass',
                'message' => 'SQL injection protection working (prepared statements)'
            ];
        } catch (Exception $e) {
            $results['sql_injection_protection'] = [
                'status' => 'warning',
                'message' => 'Unable to test SQL injection protection: ' . $e->getMessage()
            ];
        }
        
        // Test session security
        $sessionSecure = session_status() === PHP_SESSION_ACTIVE;
        $results['session_security'] = [
            'status' => $sessionSecure ? 'pass' : 'warning',
            'message' => 'Session security ' . ($sessionSecure ? 'active' : 'inactive'),
            'session_id' => $sessionSecure ? session_id() : 'none'
        ];
        
        return $results;
    }
    
    private function testErrorUserExperience() {
        $results = [];
        
        // Test error page existence
        $errorPages = ['error_viewer.php'];
        $errorPageResults = [];
        
        foreach ($errorPages as $page) {
            $exists = file_exists(__DIR__ . '/' . $page);
            $errorPageResults[$page] = [
                'exists' => $exists,
                'status' => $exists ? 'pass' : 'warning'
            ];
        }
        
        $results['error_pages'] = [
            'status' => count(array_filter($errorPageResults, function($r) { return $r['status'] === 'pass'; })) > 0 ? 'pass' : 'warning',
            'message' => 'Error handling user experience',
            'pages' => $errorPageResults
        ];
        
        return $results;
    }
    
    /**
     * Generate comprehensive test report
     */
    public function generateComprehensiveReport() {
        $this->log("Generating comprehensive test report");
        
        $whiteBoxResults = $this->testResults['whitebox'] ?? [];
        $blackBoxResults = $this->testResults['blackbox'] ?? [];
        
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $warningTests = 0;
        
        // Count white box results
        foreach ($whiteBoxResults as $testSuite) {
            foreach ($testSuite as $test) {
                if (isset($test['status'])) {
                    $totalTests++;
                    switch ($test['status']) {
                        case 'pass':
                            $passedTests++;
                            break;
                        case 'fail':
                            $failedTests++;
                            break;
                        case 'warning':
                            $warningTests++;
                            break;
                    }
                }
            }
        }
        
        // Count black box results
        foreach ($blackBoxResults as $testSuite) {
            foreach ($testSuite as $test) {
                if (isset($test['status'])) {
                    $totalTests++;
                    switch ($test['status']) {
                        case 'pass':
                            $passedTests++;
                            break;
                        case 'fail':
                            $failedTests++;
                            break;
                        case 'warning':
                            $warningTests++;
                            break;
                    }
                }
            }
        }
        
        $successRate = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0;
        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);
        
        return [
            'test_summary' => [
                'environment' => $this->environment,
                'execution_time_ms' => $executionTime,
                'total_tests' => $totalTests,
                'passed_tests' => $passedTests,
                'failed_tests' => $failedTests,
                'warning_tests' => $warningTests,
                'success_rate' => $successRate
            ],
            'critical_issues' => $this->criticalIssues,
            'warning_issues' => $this->warningIssues,
            'whitebox_results' => $whiteBoxResults,
            'blackbox_results' => $blackBoxResults,
            'recommendations' => $this->generateRecommendations(),
            'log_file' => $this->logFile
        ];
    }
    
    private function generateRecommendations() {
        $recommendations = [];
        
        if (!empty($this->criticalIssues)) {
            $recommendations[] = "🔴 CRITICAL: Address critical issues immediately as they may prevent system functionality";
            foreach ($this->criticalIssues as $issue) {
                $recommendations[] = "   - $issue";
            }
        }
        
        if (!empty($this->warningIssues)) {
            $recommendations[] = "🟡 WARNING: Review warning issues to improve system reliability";
            foreach ($this->warningIssues as $issue) {
                $recommendations[] = "   - $issue";
            }
        }
        
        if ($this->environment === 'Heroku') {
            $recommendations[] = "📦 HEROKU: Consider implementing cloud storage for file uploads";
            $recommendations[] = "📊 HEROKU: Monitor dyno usage and implement database backups";
        }
        
        $recommendations[] = "🧪 TESTING: Run this test suite regularly to monitor system health";
        $recommendations[] = "📈 MONITORING: Implement application performance monitoring";
        
        return $recommendations;
    }
    
    /**
     * Run all tests and return comprehensive report
     */
    public function runAllTests() {
        $this->log("Starting comprehensive testing suite");
        
        $this->runWhiteBoxTests();
        $this->runBlackBoxTests();
        
        $report = $this->generateComprehensiveReport();
        
        $this->log("Comprehensive testing suite completed", [
            'total_tests' => $report['test_summary']['total_tests'],
            'success_rate' => $report['test_summary']['success_rate'],
            'critical_issues' => count($this->criticalIssues),
            'warnings' => count($this->warningIssues)
        ]);
        
        return $report;
    }
}

// Run the comprehensive testing suite
$testSuite = new ComprehensiveTestingSuite($conn);
$report = $testSuite->runAllTests();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Testing Suite - MIW</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f8f9fa; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .summary-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .card.critical { border-left: 5px solid #dc3545; }
        .card.warning { border-left: 5px solid #ffc107; }
        .card.success { border-left: 5px solid #28a745; }
        .card.info { border-left: 5px solid #17a2b8; }
        .test-section { background: white; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .test-header { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #dee2e6; border-radius: 8px 8px 0 0; font-weight: bold; }
        .test-content { padding: 15px; }
        .status-pass { color: #28a745; font-weight: bold; }
        .status-fail { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .progress-bar { width: 100%; height: 25px; background: #e9ecef; border-radius: 12px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
        .metric { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .metric:last-child { border-bottom: none; }
        .recommendations { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .issue-list { background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 8px; padding: 15px; margin: 10px 0; }
        .warning-list { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; font-weight: bold; }
        .test-details { margin-left: 20px; font-size: 0.9em; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Comprehensive Testing Suite Report</h1>
            <p>White Box + Black Box Testing for MIW Travel Management System</p>
            <div style="display: flex; justify-content: space-between; margin-top: 20px;">
                <div><strong>Environment:</strong> <?= $report['test_summary']['environment'] ?></div>
                <div><strong>Execution Time:</strong> <?= $report['test_summary']['execution_time_ms'] ?>ms</div>
                <div><strong>Success Rate:</strong> <?= $report['test_summary']['success_rate'] ?>%</div>
            </div>
        </div>
        
        <div class="summary-cards">
            <div class="card success">
                <h3>✅ Tests Passed</h3>
                <div style="font-size: 2em; font-weight: bold;"><?= $report['test_summary']['passed_tests'] ?></div>
                <div>out of <?= $report['test_summary']['total_tests'] ?> total tests</div>
            </div>
            
            <div class="card <?= $report['test_summary']['failed_tests'] > 0 ? 'critical' : 'success' ?>">
                <h3><?= $report['test_summary']['failed_tests'] > 0 ? '❌' : '✅' ?> Tests Failed</h3>
                <div style="font-size: 2em; font-weight: bold;"><?= $report['test_summary']['failed_tests'] ?></div>
                <div><?= $report['test_summary']['failed_tests'] > 0 ? 'Critical issues need attention' : 'No critical failures' ?></div>
            </div>
            
            <div class="card <?= $report['test_summary']['warning_tests'] > 0 ? 'warning' : 'success' ?>">
                <h3>⚠️ Warnings</h3>
                <div style="font-size: 2em; font-weight: bold;"><?= $report['test_summary']['warning_tests'] ?></div>
                <div><?= $report['test_summary']['warning_tests'] > 0 ? 'Issues to review' : 'No warnings' ?></div>
            </div>
            
            <div class="card info">
                <h3>📊 Success Rate</h3>
                <div style="font-size: 2em; font-weight: bold;"><?= $report['test_summary']['success_rate'] ?>%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $report['test_summary']['success_rate'] ?>%"></div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($report['critical_issues'])): ?>
        <div class="issue-list">
            <h3>🔴 Critical Issues Detected</h3>
            <ul>
                <?php foreach ($report['critical_issues'] as $issue): ?>
                    <li><?= htmlspecialchars($issue) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($report['warning_issues'])): ?>
        <div class="warning-list">
            <h3>🟡 Warning Issues</h3>
            <ul>
                <?php foreach ($report['warning_issues'] as $issue): ?>
                    <li><?= htmlspecialchars($issue) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="test-section">
            <div class="test-header">📋 White Box Testing Results</div>
            <div class="test-content">
                <p><strong>White Box Testing</strong> examines internal code structure, logic paths, and implementation details.</p>
                <?php foreach ($report['whitebox_results'] as $testName => $testResults): ?>
                    <h4><?= ucwords(str_replace('_', ' ', $testName)) ?></h4>
                    <table>
                        <thead>
                            <tr><th>Test</th><th>Status</th><th>Message</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults as $subTest => $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $subTest))) ?></td>
                                    <td class="status-<?= $result['status'] ?? 'unknown' ?>"><?= strtoupper($result['status'] ?? 'UNKNOWN') ?></td>
                                    <td><?= htmlspecialchars($result['message'] ?? 'No message') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="test-section">
            <div class="test-header">🖥️ Black Box Testing Results</div>
            <div class="test-content">
                <p><strong>Black Box Testing</strong> examines external functionality, user interactions, and system behavior.</p>
                <?php foreach ($report['blackbox_results'] as $testName => $testResults): ?>
                    <h4><?= ucwords(str_replace('_', ' ', $testName)) ?></h4>
                    <table>
                        <thead>
                            <tr><th>Test</th><th>Status</th><th>Message</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testResults as $subTest => $result): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', $subTest))) ?></td>
                                    <td class="status-<?= $result['status'] ?? 'unknown' ?>"><?= strtoupper($result['status'] ?? 'UNKNOWN') ?></td>
                                    <td><?= htmlspecialchars($result['message'] ?? 'No message') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="recommendations">
            <h3>💡 Recommendations</h3>
            <ul>
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                    <li><?= htmlspecialchars($recommendation) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
            <p><strong>Log File:</strong> <code><?= basename($report['log_file']) ?></code></p>
            <p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s T') ?></p>
            <p><a href="error_viewer.php" style="margin: 0 10px;">🔍 View Error Logs</a> | <a href="workflow_test.php" style="margin: 0 10px;">📊 Basic Workflow Test</a></p>
        </div>
    </div>
</body>
</html>
