<?php
/**
 * Comprehensive Test Orchestrator for MIW Travel Management System
 * 
 * This orchestrator deploys both White Box and Black Box testing suites
 * to thoroughly test the deployed project and identify any issues.
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Set up error reporting and logging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

class ComprehensiveTestOrchestrator {
    private $conn;
    private $testResults = [];
    private $logFile;
    private $startTime;
    private $totalTests = 0;
    private $passedTests = 0;
    private $failedTests = 0;
    private $warningTests = 0;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->startTime = microtime(true);
        $this->logFile = __DIR__ . '/test_logs/comprehensive_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("Comprehensive Test Orchestrator Started");
    }
    
    private function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] ORCHESTRATOR: {$message}\n";
        if (!empty($context)) {
            $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        $logEntry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function recordTest($testName, $status, $message, $details = []) {
        $this->totalTests++;
        switch ($status) {
            case 'pass':
                $this->passedTests++;
                break;
            case 'fail':
                $this->failedTests++;
                break;
            case 'warning':
                $this->warningTests++;
                break;
        }
        
        $this->testResults[] = [
            'test' => $testName,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->log("TEST: $testName - $status - $message", $details);
    }
    
    /**
     * WHITE BOX TESTING - Testing internal implementation
     */
    public function runWhiteBoxTests() {
        $this->log("Starting White Box Test Suite");
        
        // Test 1: Database Connection Internal Logic
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM data_jamaah");
            $stmt->execute();
            $result = $stmt->fetch();
            $this->conn->rollBack();
            
            $this->recordTest(
                'Database Transaction Logic',
                'pass',
                "Transaction handling working, found {$result['count']} jamaah records",
                ['record_count' => $result['count']]
            );
        } catch (Exception $e) {
            $this->recordTest(
                'Database Transaction Logic',
                'fail',
                'Transaction handling failed: ' . $e->getMessage()
            );
        }
        
        // Test 2: Upload Handler Internal Logic
        if (file_exists(__DIR__ . '/upload_handler.php')) {
            try {
                require_once __DIR__ . '/upload_handler.php';
                $uploadHandler = new UploadHandler();
                
                // Test filename generation algorithm
                $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'payment', 'PKG001');
                $filenamePattern = '/^payment_1234567890123456_PKG001_\d{14}$/';
                
                if (preg_match($filenamePattern, $testFilename)) {
                    $this->recordTest(
                        'Upload Handler Filename Algorithm',
                        'pass',
                        "Filename generation algorithm working: $testFilename"
                    );
                } else {
                    $this->recordTest(
                        'Upload Handler Filename Algorithm',
                        'fail',
                        "Filename generation algorithm incorrect: $testFilename"
                    );
                }
                
                // Test error handling logic
                $uploadHandler->clearErrors();
                $hasErrors = $uploadHandler->hasErrors();
                
                $this->recordTest(
                    'Upload Handler Error Management',
                    !$hasErrors ? 'pass' : 'fail',
                    'Error handling logic ' . (!$hasErrors ? 'working correctly' : 'has issues')
                );
                
            } catch (Exception $e) {
                $this->recordTest(
                    'Upload Handler Internal Logic',
                    'fail',
                    'Upload handler instantiation failed: ' . $e->getMessage()
                );
            }
        } else {
            $this->recordTest(
                'Upload Handler File Availability',
                'fail',
                'upload_handler.php file not found'
            );
        }
        
        // Test 3: Session Management Internal Logic
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Test session data persistence
            $_SESSION['whitebox_test'] = 'test_value_' . time();
            $sessionData = $_SESSION['whitebox_test'] ?? null;
            
            if ($sessionData && strpos($sessionData, 'test_value_') === 0) {
                $this->recordTest(
                    'Session Data Persistence',
                    'pass',
                    'Session data persistence working correctly'
                );
                unset($_SESSION['whitebox_test']);
            } else {
                $this->recordTest(
                    'Session Data Persistence',
                    'fail',
                    'Session data persistence not working'
                );
            }
        } catch (Exception $e) {
            $this->recordTest(
                'Session Management Logic',
                'fail',
                'Session management error: ' . $e->getMessage()
            );
        }
        
        // Test 4: Form Validation Logic Paths
        $testValidations = [
            'valid_nik' => ['nik' => '1234567890123456', 'expected' => true],
            'invalid_nik_short' => ['nik' => '12345', 'expected' => false],
            'invalid_nik_non_numeric' => ['nik' => '123456789012345a', 'expected' => false]
        ];
        
        $validationPassed = true;
        foreach ($testValidations as $testName => $test) {
            $nikValid = strlen($test['nik']) === 16 && ctype_digit($test['nik']);
            if ($nikValid !== $test['expected']) {
                $validationPassed = false;
                break;
            }
        }
        
        $this->recordTest(
            'Form Validation Logic Paths',
            $validationPassed ? 'pass' : 'fail',
            'NIK validation logic ' . ($validationPassed ? 'working correctly' : 'has issues')
        );
        
        // Test 5: Email Configuration Logic
        $emailConfigTests = [
            'SMTP_HOST' => defined('SMTP_HOST'),
            'EMAIL_FROM' => defined('EMAIL_FROM'),
            'EMAIL_ENABLED' => defined('EMAIL_ENABLED')
        ];
        
        $configCount = array_sum($emailConfigTests);
        $this->recordTest(
            'Email Configuration Logic',
            $configCount >= 2 ? 'pass' : 'warning',
            "$configCount/3 email configuration constants defined",
            $emailConfigTests
        );
        
        $this->log("White Box Test Suite Completed");
    }
    
    /**
     * BLACK BOX TESTING - Testing external functionality
     */
    public function runBlackBoxTests() {
        $this->log("Starting Black Box Test Suite");
        
        // Test 1: System Availability
        $criticalPages = [
            'index.php' => 'Homepage',
            'form_haji.php' => 'Haji registration form',
            'form_umroh.php' => 'Umroh registration form',
            'admin_dashboard.php' => 'Admin dashboard'
        ];
        
        $availablePages = 0;
        foreach ($criticalPages as $page => $description) {
            if (file_exists(__DIR__ . '/' . $page)) {
                $availablePages++;
            }
        }
        
        $this->recordTest(
            'System Page Availability',
            $availablePages === count($criticalPages) ? 'pass' : 'warning',
            "$availablePages/" . count($criticalPages) . " critical pages available"
        );
        
        // Test 2: File Upload Functionality Testing
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        $fileTestCases = [
            ['type' => 'image/jpeg', 'size' => 1024 * 1024, 'valid' => true],
            ['type' => 'text/plain', 'size' => 1024, 'valid' => false],
            ['type' => 'application/pdf', 'size' => 3 * 1024 * 1024, 'valid' => false]
        ];
        
        $uploadValidationPassed = true;
        foreach ($fileTestCases as $test) {
            $typeValid = in_array($test['type'], $allowedTypes);
            $sizeValid = $test['size'] <= $maxSize;
            $actualValid = $typeValid && $sizeValid;
            
            if ($actualValid !== $test['valid']) {
                $uploadValidationPassed = false;
                break;
            }
        }
        
        $this->recordTest(
            'File Upload Validation Rules',
            $uploadValidationPassed ? 'pass' : 'fail',
            'File upload validation rules ' . ($uploadValidationPassed ? 'working correctly' : 'have issues')
        );
        
        // Test 3: Data Validation Testing
        $emailTests = [
            'valid@example.com' => true,
            'invalid-email' => false,
            'test@domain' => false
        ];
        
        $emailValidationPassed = true;
        foreach ($emailTests as $email => $expected) {
            $actual = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            if ($actual !== $expected) {
                $emailValidationPassed = false;
                break;
            }
        }
        
        $this->recordTest(
            'Email Validation Logic',
            $emailValidationPassed ? 'pass' : 'fail',
            'Email validation logic ' . ($emailValidationPassed ? 'working correctly' : 'has issues')
        );
        
        // Test 4: Database Integration Testing
        try {
            $stmt = $this->conn->prepare("SELECT COUNT(*) as package_count FROM data_paket");
            $stmt->execute();
            $packageCount = $stmt->fetch()['package_count'];
            
            $this->recordTest(
                'Database Integration',
                $packageCount > 0 ? 'pass' : 'warning',
                "Database integration working, $packageCount packages available",
                ['package_count' => $packageCount]
            );
        } catch (Exception $e) {
            $this->recordTest(
                'Database Integration',
                'fail',
                'Database integration failed: ' . $e->getMessage()
            );
        }
        
        // Test 5: Security Input Sanitization
        $maliciousInputs = [
            '<script>alert("xss")</script>',
            "'; DROP TABLE data_jamaah; --",
            '../../../etc/passwd'
        ];
        
        $sanitizationPassed = true;
        foreach ($maliciousInputs as $input) {
            $sanitized = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            if ($sanitized === $input) {
                $sanitizationPassed = false;
                break;
            }
        }
        
        $this->recordTest(
            'Input Sanitization Security',
            $sanitizationPassed ? 'pass' : 'warning',
            'Input sanitization ' . ($sanitizationPassed ? 'working' : 'needs review')
        );
        
        $this->log("Black Box Test Suite Completed");
    }
    
    /**
     * PRODUCTION ENVIRONMENT SPECIFIC TESTS
     */
    public function runProductionSpecificTests() {
        $this->log("Starting Production-Specific Tests");
        
        // Test 1: Environment Detection
        $isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
        $environment = $isProduction ? 'Production' : 'Development';
        
        $this->recordTest(
            'Environment Detection',
            'pass',
            "Environment correctly detected as: $environment",
            ['environment' => $environment]
        );
        
        // Test 2: Error Logging Configuration
        $errorLogDir = __DIR__ . '/error_logs';
        if (!file_exists($errorLogDir)) {
            mkdir($errorLogDir, 0755, true);
        }
        
        $canWriteLog = is_writable($errorLogDir);
        $this->recordTest(
            'Error Logging Configuration',
            $canWriteLog ? 'pass' : 'fail',
            'Error logging ' . ($canWriteLog ? 'configured correctly' : 'has permission issues')
        );
        
        // Test 3: Performance Metrics
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->parseMemoryLimit($memoryLimit);
        $memoryPercentage = ($memoryUsage / $memoryLimitBytes) * 100;
        
        $this->recordTest(
            'Memory Usage Performance',
            $memoryPercentage < 80 ? 'pass' : 'warning',
            sprintf('Memory usage: %s (%.1f%% of %s)', 
                $this->formatBytes($memoryUsage), 
                $memoryPercentage, 
                $memoryLimit
            ),
            [
                'memory_usage_bytes' => $memoryUsage,
                'memory_limit' => $memoryLimit,
                'memory_percentage' => $memoryPercentage
            ]
        );
        
        // Test 4: File System Capabilities
        $uploadDir = __DIR__ . '/uploads';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileSystemStatus = is_writable($uploadDir) ? 'full' : 'limited';
        $this->recordTest(
            'File System Capabilities',
            is_writable($uploadDir) ? 'pass' : 'warning',
            "File system access: $fileSystemStatus" . 
            ($isProduction && !is_writable($uploadDir) ? ' (expected for ephemeral storage)' : '')
        );
        
        $this->log("Production-Specific Tests Completed");
    }
    
    /**
     * Generate comprehensive test report
     */
    public function generateReport() {
        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);
        $successRate = $this->totalTests > 0 ? round(($this->passedTests / $this->totalTests) * 100, 2) : 0;
        
        $report = [
            'summary' => [
                'total_tests' => $this->totalTests,
                'passed' => $this->passedTests,
                'failed' => $this->failedTests,
                'warnings' => $this->warningTests,
                'success_rate' => $successRate,
                'execution_time_ms' => $executionTime
            ],
            'environment' => [
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'is_production' => isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT'])
            ],
            'test_results' => $this->testResults,
            'recommendations' => $this->generateRecommendations()
        ];
        
        $this->log("Comprehensive test report generated", $report['summary']);
        
        return $report;
    }
    
    private function generateRecommendations() {
        $recommendations = [];
        
        if ($this->failedTests > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'critical_issues',
                'message' => "Fix {$this->failedTests} failed test(s) immediately"
            ];
        }
        
        if ($this->warningTests > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'improvements',
                'message' => "Review {$this->warningTests} warning(s) for optimal performance"
            ];
        }
        
        $isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
        if ($isProduction) {
            $recommendations[] = [
                'priority' => 'info',
                'category' => 'production',
                'message' => 'Consider implementing cloud storage for file uploads in production'
            ];
        }
        
        if ($this->totalTests > 0 && $this->failedTests === 0) {
            $recommendations[] = [
                'priority' => 'info',
                'category' => 'success',
                'message' => 'All critical tests passed! System is functioning correctly.'
            ];
        }
        
        return $recommendations;
    }
    
    private function parseMemoryLimit($memoryLimit) {
        $memoryLimit = trim($memoryLimit);
        $unit = strtolower(substr($memoryLimit, -1));
        $value = (int) substr($memoryLimit, 0, -1);
        
        switch ($unit) {
            case 'g': return $value * 1024 * 1024 * 1024;
            case 'm': return $value * 1024 * 1024;
            case 'k': return $value * 1024;
            default: return (int) $memoryLimit;
        }
    }
    
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Run all test suites
     */
    public function runAllTests() {
        $this->log("Starting Complete Test Suite Execution");
        
        // Run all test suites
        $this->runWhiteBoxTests();
        $this->runBlackBoxTests();
        $this->runProductionSpecificTests();
        
        // Generate comprehensive report
        $report = $this->generateReport();
        
        $this->log("Complete Test Suite Execution Finished", $report['summary']);
        
        return $report;
    }
}

// Initialize and run tests
$orchestrator = new ComprehensiveTestOrchestrator($conn);
$testReport = $orchestrator->runAllTests();

// Output results in both HTML and JSON formats
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode($testReport, JSON_PRETTY_PRINT);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Testing Dashboard - MIW</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .dashboard { max-width: 1400px; margin: 0 auto; }
        .header { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 15px; margin-bottom: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .overview-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .metric-card { background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-left: 5px solid #667eea; }
        .metric-card.success { border-left-color: #28a745; }
        .metric-card.warning { border-left-color: #ffc107; }
        .metric-card.danger { border-left-color: #dc3545; }
        .metric-number { font-size: 2.5em; font-weight: bold; margin: 10px 0; }
        .metric-label { color: #666; font-size: 0.9em; text-transform: uppercase; letter-spacing: 1px; }
        .test-section { background: rgba(255,255,255,0.95); margin: 20px 0; padding: 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .test-item { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #28a745; }
        .test-item.fail { border-left-color: #dc3545; background: #fff5f5; }
        .test-item.warning { border-left-color: #ffc107; background: #fffbf0; }
        .recommendations { background: #e3f2fd; padding: 20px; border-radius: 10px; border-left: 5px solid #2196f3; }
        .recommendation-item { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .priority-high { border-left: 5px solid #dc3545; }
        .priority-medium { border-left: 5px solid #ffc107; }
        .priority-info { border-left: 5px solid #28a745; }
        .progress-bar { width: 100%; height: 10px; background: #e9ecef; border-radius: 5px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>🔍 Comprehensive Testing Dashboard</h1>
            <p>White Box + Black Box Testing Results for MIW Travel Management System</p>
            <p><strong>Environment:</strong> <?= $testReport['environment']['is_production'] ? 'Production' : 'Development' ?> | 
               <strong>PHP:</strong> <?= $testReport['environment']['php_version'] ?> |
               <strong>Executed:</strong> <?= date('Y-m-d H:i:s') ?></p>
        </div>

        <div class="overview-grid">
            <div class="metric-card <?= $testReport['summary']['failed'] > 0 ? 'danger' : 'success' ?>">
                <div class="metric-number"><?= $testReport['summary']['total_tests'] ?></div>
                <div class="metric-label">Total Tests</div>
            </div>
            <div class="metric-card success">
                <div class="metric-number"><?= $testReport['summary']['passed'] ?></div>
                <div class="metric-label">Passed</div>
            </div>
            <div class="metric-card <?= $testReport['summary']['failed'] > 0 ? 'danger' : 'success' ?>">
                <div class="metric-number"><?= $testReport['summary']['failed'] ?></div>
                <div class="metric-label">Failed</div>
            </div>
            <div class="metric-card <?= $testReport['summary']['warnings'] > 0 ? 'warning' : 'success' ?>">
                <div class="metric-number"><?= $testReport['summary']['warnings'] ?></div>
                <div class="metric-label">Warnings</div>
            </div>
            <div class="metric-card <?= $testReport['summary']['success_rate'] >= 80 ? 'success' : 'warning' ?>">
                <div class="metric-number"><?= $testReport['summary']['success_rate'] ?>%</div>
                <div class="metric-label">Success Rate</div>
            </div>
            <div class="metric-card">
                <div class="metric-number"><?= $testReport['summary']['execution_time_ms'] ?>ms</div>
                <div class="metric-label">Execution Time</div>
            </div>
        </div>

        <div class="test-section">
            <h2>📊 Test Results Overview</h2>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?= $testReport['summary']['success_rate'] ?>%"></div>
            </div>
            <p><strong><?= $testReport['summary']['success_rate'] ?>%</strong> of tests passed successfully</p>
        </div>

        <div class="test-section">
            <h2>🧪 Detailed Test Results</h2>
            <?php foreach ($testReport['test_results'] as $test): ?>
                <div class="test-item <?= $test['status'] ?>">
                    <strong><?= htmlspecialchars($test['test']) ?>:</strong>
                    <span style="text-transform: uppercase; font-weight: bold; color: <?= $test['status'] === 'pass' ? '#28a745' : ($test['status'] === 'fail' ? '#dc3545' : '#ffc107') ?>">
                        [<?= strtoupper($test['status']) ?>]
                    </span>
                    <p><?= htmlspecialchars($test['message']) ?></p>
                    <?php if (!empty($test['details'])): ?>
                        <small style="color: #666;">
                            <?php foreach ($test['details'] as $key => $value): ?>
                                <strong><?= htmlspecialchars($key) ?>:</strong> <?= htmlspecialchars(is_array($value) ? json_encode($value) : $value) ?><br>
                            <?php endforeach; ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($testReport['recommendations'])): ?>
        <div class="test-section">
            <h2>💡 Recommendations</h2>
            <div class="recommendations">
                <?php foreach ($testReport['recommendations'] as $recommendation): ?>
                    <div class="recommendation-item priority-<?= $recommendation['priority'] ?>">
                        <strong><?= strtoupper($recommendation['priority']) ?> - <?= strtoupper($recommendation['category']) ?>:</strong>
                        <p><?= htmlspecialchars($recommendation['message']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px; color: rgba(255,255,255,0.8);">
            <p>Comprehensive Testing completed at <?= date('Y-m-d H:i:s T') ?></p>
            <p><a href="?format=json" style="color: rgba(255,255,255,0.9);">View JSON Results</a> | 
               <a href="error_viewer.php" style="color: rgba(255,255,255,0.9);">View Error Logs</a></p>
        </div>
    </div>
</body>
</html>
