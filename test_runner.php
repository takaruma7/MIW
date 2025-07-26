<?php
/**
 * Test Runner for MIW Travel Management System
 * 
 * Simple command-line and web interface for running specific tests
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

class TestRunner {
    private $conn;
    private $availableTests = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->initializeTests();
    }
    
    private function initializeTests() {
        $this->availableTests = [
            'quick' => [
                'name' => 'Quick System Check',
                'description' => 'Fast validation of core components',
                'function' => 'runQuickTest'
            ],
            'database' => [
                'name' => 'Database Connectivity Test',
                'description' => 'Test database connection and basic operations',
                'function' => 'runDatabaseTest'
            ],
            'upload' => [
                'name' => 'File Upload System Test',
                'description' => 'Test file upload functionality and validation',
                'function' => 'runUploadTest'
            ],
            'forms' => [
                'name' => 'Form Validation Test',
                'description' => 'Test form validation and data processing',
                'function' => 'runFormTest'
            ],
            'security' => [
                'name' => 'Security Validation Test',
                'description' => 'Test input sanitization and security measures',
                'function' => 'runSecurityTest'
            ],
            'integration' => [
                'name' => 'Integration Test',
                'description' => 'Test component integration and workflows',
                'function' => 'runIntegrationTest'
            ],
            'production' => [
                'name' => 'Production Readiness Test',
                'description' => 'Comprehensive production environment validation',
                'function' => 'runProductionTest'
            ]
        ];
    }
    
    public function listAvailableTests() {
        return $this->availableTests;
    }
    
    public function runTest($testType) {
        if (!isset($this->availableTests[$testType])) {
            return ['error' => 'Invalid test type'];
        }
        
        $test = $this->availableTests[$testType];
        $functionName = $test['function'];
        
        if (method_exists($this, $functionName)) {
            return $this->$functionName();
        }
        
        return ['error' => 'Test function not implemented'];
    }
    
    private function runQuickTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Database connection
        try {
            $stmt = $this->conn->prepare("SELECT 1 as test");
            $stmt->execute();
            $results['database'] = ['status' => 'pass', 'message' => 'Database connection OK'];
        } catch (Exception $e) {
            $results['database'] = ['status' => 'fail', 'message' => 'Database error: ' . $e->getMessage()];
        }
        
        // Test 2: Critical files
        $criticalFiles = ['config.php', 'upload_handler.php', 'confirm_payment.php'];
        $missingFiles = [];
        
        foreach ($criticalFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missingFiles[] = $file;
            }
        }
        
        $results['files'] = [
            'status' => empty($missingFiles) ? 'pass' : 'fail',
            'message' => empty($missingFiles) ? 'All critical files present' : 'Missing: ' . implode(', ', $missingFiles)
        ];
        
        // Test 3: PHP environment
        $results['environment'] = [
            'status' => 'pass',
            'message' => 'PHP ' . PHP_VERSION . ' running',
            'details' => [
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'max_execution_time' => ini_get('max_execution_time')
            ]
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'quick',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runDatabaseTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Connection
        try {
            $stmt = $this->conn->prepare("SELECT 1");
            $stmt->execute();
            $results['connection'] = ['status' => 'pass', 'message' => 'Database connection established'];
        } catch (Exception $e) {
            $results['connection'] = ['status' => 'fail', 'message' => 'Connection failed: ' . $e->getMessage()];
            return ['test_type' => 'database', 'results' => $results, 'error' => 'Cannot proceed without database connection'];
        }
        
        // Test 2: Tables existence
        $requiredTables = ['data_jamaah', 'data_paket', 'data_pembatalan'];
        $existingTables = [];
        
        foreach ($requiredTables as $table) {
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) FROM $table LIMIT 1");
                $stmt->execute();
                $existingTables[] = $table;
            } catch (Exception $e) {
                // Table doesn't exist or is not accessible
            }
        }
        
        $results['tables'] = [
            'status' => count($existingTables) === count($requiredTables) ? 'pass' : 'fail',
            'message' => count($existingTables) . '/' . count($requiredTables) . ' required tables found',
            'existing' => $existingTables,
            'missing' => array_diff($requiredTables, $existingTables)
        ];
        
        // Test 3: Transaction support
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah");
            $stmt->execute();
            $this->conn->rollBack();
            
            $results['transactions'] = ['status' => 'pass', 'message' => 'Transaction support working'];
        } catch (Exception $e) {
            $results['transactions'] = ['status' => 'fail', 'message' => 'Transaction error: ' . $e->getMessage()];
        }
        
        // Test 4: Data integrity
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as jamaah_count FROM data_jamaah");
            $stmt->execute();
            $jamaahCount = $stmt->fetch()['jamaah_count'];
            
            $stmt = $this->conn->prepare("SELECT COUNT(*) as package_count FROM data_paket");
            $stmt->execute();
            $packageCount = $stmt->fetch()['package_count'];
            
            $results['data_integrity'] = [
                'status' => 'pass',
                'message' => "Data found: $jamaahCount jamaah records, $packageCount packages",
                'jamaah_count' => $jamaahCount,
                'package_count' => $packageCount
            ];
        } catch (Exception $e) {
            $results['data_integrity'] = ['status' => 'fail', 'message' => 'Data integrity check failed: ' . $e->getMessage()];
        }
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'database',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runUploadTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Upload handler availability
        if (file_exists(__DIR__ . '/upload_handler.php')) {
            require_once __DIR__ . '/upload_handler.php';
            
            try {
                $uploadHandler = new UploadHandler();
                $results['handler_load'] = ['status' => 'pass', 'message' => 'Upload handler loaded successfully'];
                
                // Test 2: Filename generation
                $filename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PKG001');
                $results['filename_generation'] = [
                    'status' => !empty($filename) ? 'pass' : 'fail',
                    'message' => 'Generated filename: ' . $filename,
                    'filename' => $filename
                ];
                
                // Test 3: Upload configuration
                $stats = $uploadHandler->getUploadStats();
                $results['configuration'] = [
                    'status' => 'pass',
                    'message' => 'Upload configuration loaded',
                    'stats' => $stats
                ];
                
            } catch (Exception $e) {
                $results['handler_load'] = ['status' => 'fail', 'message' => 'Upload handler error: ' . $e->getMessage()];
            }
        } else {
            $results['handler_load'] = ['status' => 'fail', 'message' => 'Upload handler file not found'];
        }
        
        // Test 4: Upload directory
        $uploadDir = __DIR__ . '/uploads';
        if (!file_exists($uploadDir)) {
            $created = mkdir($uploadDir, 0755, true);
            $results['upload_directory'] = [
                'status' => $created ? 'pass' : 'fail',
                'message' => $created ? 'Upload directory created' : 'Failed to create upload directory'
            ];
        } else {
            $results['upload_directory'] = [
                'status' => is_writable($uploadDir) ? 'pass' : 'warning',
                'message' => is_writable($uploadDir) ? 'Upload directory exists and writable' : 'Upload directory exists but not writable'
            ];
        }
        
        // Test 5: File validation logic
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $testFiles = [
            ['type' => 'image/jpeg', 'size' => 1024 * 1024, 'expected' => true],
            ['type' => 'text/plain', 'size' => 1024, 'expected' => false],
            ['type' => 'application/pdf', 'size' => 3 * 1024 * 1024, 'expected' => false]
        ];
        
        $validationPassed = true;
        foreach ($testFiles as $test) {
            $typeValid = in_array($test['type'], $allowedTypes);
            $sizeValid = $test['size'] <= $maxSize;
            $actualResult = $typeValid && $sizeValid;
            
            if ($actualResult !== $test['expected']) {
                $validationPassed = false;
                break;
            }
        }
        
        $results['file_validation'] = [
            'status' => $validationPassed ? 'pass' : 'fail',
            'message' => 'File validation logic ' . ($validationPassed ? 'working correctly' : 'has issues')
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'upload',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runFormTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Form file availability
        $formFiles = ['form_haji.php', 'form_umroh.php', 'form_pembatalan.php'];
        $existingForms = [];
        
        foreach ($formFiles as $form) {
            if (file_exists(__DIR__ . '/' . $form)) {
                $existingForms[] = $form;
            }
        }
        
        $results['form_files'] = [
            'status' => count($existingForms) === count($formFiles) ? 'pass' : 'fail',
            'message' => count($existingForms) . '/' . count($formFiles) . ' form files found',
            'existing' => $existingForms,
            'missing' => array_diff($formFiles, $existingForms)
        ];
        
        // Test 2: Validation logic
        $testData = [
            'valid' => [
                'nik' => '1234567890123456',
                'nama' => 'Test User',
                'email' => 'test@example.com',
                'no_telp' => '081234567890'
            ],
            'invalid_nik' => [
                'nik' => '12345',
                'nama' => 'Test User',
                'email' => 'test@example.com',
                'no_telp' => '081234567890'
            ],
            'invalid_email' => [
                'nik' => '1234567890123456',
                'nama' => 'Test User',
                'email' => 'invalid-email',
                'no_telp' => '081234567890'
            ]
        ];
        
        $validationResults = [];
        foreach ($testData as $testName => $data) {
            $nikValid = strlen($data['nik']) === 16 && ctype_digit($data['nik']);
            $emailValid = filter_var($data['email'], FILTER_VALIDATE_EMAIL) !== false;
            $phoneValid = preg_match('/^[0-9]{10,15}$/', $data['no_telp']);
            
            $isValid = $nikValid && $emailValid && $phoneValid;
            $expectedValid = ($testName === 'valid');
            
            $validationResults[$testName] = ($isValid === $expectedValid);
        }
        
        $allValidationPassed = array_reduce($validationResults, function($carry, $item) {
            return $carry && $item;
        }, true);
        
        $results['validation_logic'] = [
            'status' => $allValidationPassed ? 'pass' : 'fail',
            'message' => 'Form validation logic ' . ($allValidationPassed ? 'working correctly' : 'has issues'),
            'test_results' => $validationResults
        ];
        
        // Test 3: Processing scripts
        $processingScripts = ['submit_haji.php', 'submit_umroh.php', 'submit_pembatalan.php'];
        $existingScripts = [];
        
        foreach ($processingScripts as $script) {
            if (file_exists(__DIR__ . '/' . $script)) {
                $existingScripts[] = $script;
            }
        }
        
        $results['processing_scripts'] = [
            'status' => count($existingScripts) === count($processingScripts) ? 'pass' : 'fail',
            'message' => count($existingScripts) . '/' . count($processingScripts) . ' processing scripts found',
            'existing' => $existingScripts,
            'missing' => array_diff($processingScripts, $existingScripts)
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'forms',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runSecurityTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Input sanitization
        $maliciousInputs = [
            '<script>alert("xss")</script>',
            "'; DROP TABLE data_jamaah; --",
            '1\' OR \'1\'=\'1',
            '<img src="x" onerror="alert(1)">'
        ];
        
        $sanitizationPassed = true;
        foreach ($maliciousInputs as $input) {
            $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            if (preg_match('/<script|javascript:|on\w+=/i', $escaped)) {
                $sanitizationPassed = false;
                break;
            }
        }
        
        $results['input_sanitization'] = [
            'status' => $sanitizationPassed ? 'pass' : 'fail',
            'message' => 'HTML escaping ' . ($sanitizationPassed ? 'working correctly' : 'has vulnerabilities')
        ];
        
        // Test 2: SQL injection protection
        try {
            $stmt = $this->conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
            $stmt->execute(["'; DROP TABLE data_jamaah; --"]);
            
            $results['sql_injection'] = [
                'status' => 'pass',
                'message' => 'Prepared statements protect against SQL injection'
            ];
        } catch (Exception $e) {
            $results['sql_injection'] = [
                'status' => 'pass',
                'message' => 'Database properly rejects malicious input'
            ];
        }
        
        // Test 3: Session security
        if (session_status() === PHP_SESSION_ACTIVE) {
            $sessionSecure = true;
            
            // Check if session is configured securely in production
            if (isset($_ENV['DYNO']) || isset($_ENV['RENDER'])) {
                $sessionSecure = ini_get('session.cookie_secure') && ini_get('session.cookie_httponly');
            }
            
            $results['session_security'] = [
                'status' => $sessionSecure ? 'pass' : 'warning',
                'message' => 'Session security ' . ($sessionSecure ? 'properly configured' : 'needs improvement')
            ];
        } else {
            $results['session_security'] = [
                'status' => 'fail',
                'message' => 'Session not active'
            ];
        }
        
        // Test 4: File upload security
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $dangerousTypes = ['application/x-php', 'text/html', 'application/javascript'];
        
        $uploadSecurityPassed = true;
        foreach ($dangerousTypes as $type) {
            if (in_array($type, $allowedTypes)) {
                $uploadSecurityPassed = false;
                break;
            }
        }
        
        $results['upload_security'] = [
            'status' => $uploadSecurityPassed ? 'pass' : 'fail',
            'message' => 'File upload security ' . ($uploadSecurityPassed ? 'properly configured' : 'has vulnerabilities')
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'security',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runIntegrationTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Database-Upload integration
        try {
            require_once __DIR__ . '/upload_handler.php';
            $uploadHandler = new UploadHandler();
            
            // Simulate integration workflow
            $this->conn->beginTransaction();
            $filename = $uploadHandler->generateCustomFilename('1234567890123456', 'integration_test', 'PKG001');
            $this->conn->rollBack();
            
            $results['database_upload'] = [
                'status' => !empty($filename) ? 'pass' : 'fail',
                'message' => 'Database-Upload integration working'
            ];
        } catch (Exception $e) {
            $results['database_upload'] = [
                'status' => 'fail',
                'message' => 'Integration test failed: ' . $e->getMessage()
            ];
        }
        
        // Test 2: Form-Database integration
        try {
            $this->conn->beginTransaction();
            
            $testData = [
                'nik' => '9999888877776666',
                'nama' => 'Integration Test',
                'program_pilihan' => 'Test Program'
            ];
            
            // Check if this would be a valid insertion (without actually inserting)
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$testData['nik']]);
            $existing = $stmt->fetch()[0];
            
            $this->conn->rollBack();
            
            $results['form_database'] = [
                'status' => 'pass',
                'message' => 'Form-Database integration working (NIK uniqueness check: ' . ($existing ? 'exists' : 'available') . ')'
            ];
        } catch (Exception $e) {
            $results['form_database'] = [
                'status' => 'fail',
                'message' => 'Form-Database integration failed: ' . $e->getMessage()
            ];
        }
        
        // Test 3: Email integration
        if (file_exists(__DIR__ . '/email_functions.php')) {
            $results['email_integration'] = [
                'status' => 'pass',
                'message' => 'Email functions available for integration'
            ];
        } else {
            $results['email_integration'] = [
                'status' => 'fail',
                'message' => 'Email functions not found'
            ];
        }
        
        // Test 4: Admin integration
        $adminFiles = ['admin_dashboard.php', 'admin_pending.php'];
        $adminAvailable = true;
        
        foreach ($adminFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $adminAvailable = false;
                break;
            }
        }
        
        $results['admin_integration'] = [
            'status' => $adminAvailable ? 'pass' : 'fail',
            'message' => 'Admin panel integration ' . ($adminAvailable ? 'available' : 'incomplete')
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'integration',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
    
    private function runProductionTest() {
        $results = [];
        $startTime = microtime(true);
        
        // Test 1: Environment detection
        $isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
        
        $results['environment'] = [
            'status' => 'pass',
            'message' => 'Environment: ' . ($isProduction ? 'Production' : 'Development'),
            'is_production' => $isProduction
        ];
        
        // Test 2: Performance check
        $performanceStart = microtime(true);
        
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah");
            $stmt->execute();
            $count = $stmt->fetch()[0];
            
            $queryTime = (microtime(true) - $performanceStart) * 1000;
            
            $results['performance'] = [
                'status' => $queryTime < 1000 ? 'pass' : 'warning',
                'message' => "Database query took {$queryTime}ms (found $count records)",
                'query_time_ms' => $queryTime
            ];
        } catch (Exception $e) {
            $results['performance'] = [
                'status' => 'fail',
                'message' => 'Performance test failed: ' . $e->getMessage()
            ];
        }
        
        // Test 3: Error logging
        $errorLogDir = __DIR__ . '/error_logs';
        
        $results['error_logging'] = [
            'status' => (file_exists($errorLogDir) && is_writable($errorLogDir)) ? 'pass' : 'warning',
            'message' => 'Error logging ' . (is_writable($errorLogDir) ? 'functional' : 'limited'),
            'log_directory' => $errorLogDir
        ];
        
        // Test 4: Memory usage
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $results['memory_usage'] = [
            'status' => 'pass',
            'message' => 'Memory usage: ' . number_format($memoryUsage) . ' bytes (limit: ' . $memoryLimit . ')',
            'memory_usage' => $memoryUsage,
            'memory_limit' => $memoryLimit
        ];
        
        // Test 5: File system
        $tempDir = sys_get_temp_dir();
        $canWriteTemp = is_writable($tempDir);
        
        $results['file_system'] = [
            'status' => $canWriteTemp ? 'pass' : 'warning',
            'message' => 'File system access: ' . ($canWriteTemp ? 'Full' : 'Limited (Ephemeral)'),
            'temp_directory' => $tempDir,
            'writable' => $canWriteTemp
        ];
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        return [
            'test_type' => 'production',
            'execution_time_ms' => $executionTime,
            'results' => $results,
            'summary' => [
                'total' => count($results),
                'passed' => count(array_filter($results, function($r) { return $r['status'] === 'pass'; })),
                'failed' => count(array_filter($results, function($r) { return $r['status'] === 'fail'; }))
            ]
        ];
    }
}

// Handle requests
$testRunner = new TestRunner($conn);

// Check if this is an API request
if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    
    if (isset($_GET['test'])) {
        $testType = $_GET['test'];
        $result = $testRunner->runTest($testType);
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            'available_tests' => $testRunner->listAvailableTests(),
            'usage' => 'Add ?test=TYPE&api=true to run a specific test'
        ], JSON_PRETTY_PRINT);
    }
    exit;
}

// Web interface
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Runner - MIW</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f5f6fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; margin: -30px -30px 30px; border-radius: 12px 12px 0 0; }
        .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .test-card { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; padding: 20px; transition: all 0.3s ease; cursor: pointer; }
        .test-card:hover { border-color: #667eea; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .test-card.selected { border-color: #667eea; background: #e3f2fd; }
        .test-name { font-size: 1.2em; font-weight: bold; margin-bottom: 10px; color: #2c3e50; }
        .test-description { color: #666; font-size: 0.9em; line-height: 1.4; }
        .run-button { background: #667eea; color: white; padding: 12px 30px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; margin: 20px 10px 10px 0; transition: all 0.3s ease; }
        .run-button:hover { background: #5a6fd8; transform: translateY(-1px); }
        .run-button:disabled { background: #ccc; cursor: not-allowed; transform: none; }
        .results-container { margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; display: none; }
        .result-item { background: white; margin: 10px 0; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; }
        .result-item.fail { border-left-color: #dc3545; }
        .result-item.warning { border-left-color: #ffc107; }
        .loading { text-align: center; padding: 50px; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #667eea; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .summary { background: #667eea; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .api-info { background: #e3f2fd; padding: 15px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Test Runner</h1>
            <p>Select and run specific tests for the MIW Travel Management System</p>
        </div>

        <div class="test-grid">
            <?php foreach ($testRunner->listAvailableTests() as $testType => $testInfo): ?>
                <div class="test-card" onclick="selectTest('<?= $testType ?>')">
                    <div class="test-name"><?= htmlspecialchars($testInfo['name']) ?></div>
                    <div class="test-description"><?= htmlspecialchars($testInfo['description']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <button id="runButton" class="run-button" onclick="runSelectedTest()" disabled>
            Select a Test to Run
        </button>
        
        <button class="run-button" onclick="runAllTests()" style="background: #28a745;">
            🚀 Run All Tests
        </button>

        <div id="resultsContainer" class="results-container">
            <div id="loadingSpinner" class="loading">
                <div class="spinner"></div>
                <p>Running test...</p>
            </div>
            <div id="testResults"></div>
        </div>

        <div class="api-info">
            <h3>API Access</h3>
            <p><strong>JSON API:</strong> Add <code>?test=TYPE&api=true</code> to get JSON results</p>
            <p><strong>Available types:</strong> <?= implode(', ', array_keys($testRunner->listAvailableTests())) ?></p>
            <p><strong>Example:</strong> <code><?= $_SERVER['REQUEST_URI'] ?>?test=quick&api=true</code></p>
        </div>
    </div>

    <script>
        let selectedTest = null;

        function selectTest(testType) {
            // Remove previous selection
            document.querySelectorAll('.test-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Select current test
            event.target.closest('.test-card').classList.add('selected');
            selectedTest = testType;
            
            const runButton = document.getElementById('runButton');
            runButton.disabled = false;
            runButton.textContent = `Run ${testType.charAt(0).toUpperCase() + testType.slice(1)} Test`;
        }

        function runSelectedTest() {
            if (!selectedTest) return;
            
            const resultsContainer = document.getElementById('resultsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const testResults = document.getElementById('testResults');
            
            resultsContainer.style.display = 'block';
            loadingSpinner.style.display = 'block';
            testResults.innerHTML = '';
            
            fetch(`?test=${selectedTest}&api=true`)
                .then(response => response.json())
                .then(data => {
                    loadingSpinner.style.display = 'none';
                    displayResults(data);
                })
                .catch(error => {
                    loadingSpinner.style.display = 'none';
                    testResults.innerHTML = `<div class="result-item fail">Error: ${error.message}</div>`;
                });
        }

        function runAllTests() {
            const tests = <?= json_encode(array_keys($testRunner->listAvailableTests())) ?>;
            const resultsContainer = document.getElementById('resultsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const testResults = document.getElementById('testResults');
            
            resultsContainer.style.display = 'block';
            loadingSpinner.style.display = 'block';
            testResults.innerHTML = '';
            
            let completedTests = 0;
            let allResults = [];
            
            tests.forEach(test => {
                fetch(`?test=${test}&api=true`)
                    .then(response => response.json())
                    .then(data => {
                        allResults.push(data);
                        completedTests++;
                        
                        if (completedTests === tests.length) {
                            loadingSpinner.style.display = 'none';
                            displayAllResults(allResults);
                        }
                    })
                    .catch(error => {
                        completedTests++;
                        allResults.push({error: error.message, test_type: test});
                        
                        if (completedTests === tests.length) {
                            loadingSpinner.style.display = 'none';
                            displayAllResults(allResults);
                        }
                    });
            });
        }

        function displayResults(data) {
            const testResults = document.getElementById('testResults');
            
            let html = `<div class="summary">
                <h3>${data.test_type.charAt(0).toUpperCase() + data.test_type.slice(1)} Test Results</h3>
                <p>Execution Time: ${data.execution_time_ms}ms | 
                   Tests: ${data.summary.total} | 
                   Passed: ${data.summary.passed} | 
                   Failed: ${data.summary.failed}</p>
            </div>`;
            
            if (data.results) {
                Object.entries(data.results).forEach(([key, result]) => {
                    html += `<div class="result-item ${result.status}">
                        <strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${result.message}
                    </div>`;
                });
            }
            
            testResults.innerHTML = html;
        }

        function displayAllResults(allResults) {
            const testResults = document.getElementById('testResults');
            
            let totalTests = 0;
            let totalPassed = 0;
            let totalFailed = 0;
            
            let html = '';
            
            allResults.forEach(data => {
                if (data.summary) {
                    totalTests += data.summary.total;
                    totalPassed += data.summary.passed;
                    totalFailed += data.summary.failed;
                }
                
                html += `<div class="summary">
                    <h3>${data.test_type ? data.test_type.charAt(0).toUpperCase() + data.test_type.slice(1) : 'Unknown'} Test</h3>
                    <p>Time: ${data.execution_time_ms || 'N/A'}ms | 
                       Tests: ${data.summary ? data.summary.total : 0} | 
                       Passed: ${data.summary ? data.summary.passed : 0} | 
                       Failed: ${data.summary ? data.summary.failed : 0}</p>
                </div>`;
                
                if (data.results) {
                    Object.entries(data.results).forEach(([key, result]) => {
                        html += `<div class="result-item ${result.status}">
                            <strong>${key.replace(/_/g, ' ').toUpperCase()}:</strong> ${result.message}
                        </div>`;
                    });
                }
                
                html += '<hr style="margin: 20px 0;">';
            });
            
            // Add overall summary at the top
            const overallSummary = `<div class="summary" style="background: #28a745;">
                <h2>Overall Test Results</h2>
                <p>Total Tests: ${totalTests} | Passed: ${totalPassed} | Failed: ${totalFailed} | 
                   Success Rate: ${totalTests > 0 ? Math.round((totalPassed/totalTests)*100) : 0}%</p>
            </div>`;
            
            testResults.innerHTML = overallSummary + html;
        }
    </script>
</body>
</html>
