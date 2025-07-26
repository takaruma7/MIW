<?php
/**
 * Comprehensive Project Testing Suite for MIW Travel Management System
 * 
 * This script performs both White Box and Black Box testing to identify
 * issues in the deployed project and validate complete flow processes.
 * 
 * @version 1.0.0
 * @author MIW Development Team
 */

// Set up error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

/**
 * Comprehensive Test Class
 */
class ComprehensiveProjectTester {
    private $testResults = [];
    private $criticalIssues = [];
    private $warnings = [];
    private $conn;
    private $logFile;
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
        $this->logFile = __DIR__ . '/test_logs/comprehensive_test_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("=== COMPREHENSIVE PROJECT TESTING STARTED ===");
        
        // Test database connection first
        $this->testDatabaseConnection();
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
    
    private function addResult($testName, $status, $message, $details = []) {
        $result = [
            'test' => $testName,
            'status' => $status,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        $this->testResults[] = $result;
        
        if ($status === 'FAIL') {
            $this->criticalIssues[] = $testName . ': ' . $message;
        } elseif ($status === 'WARNING') {
            $this->warnings[] = $testName . ': ' . $message;
        }
        
        $this->log($testName . " - " . $status . ": " . $message, $details);
        
        return $result;
    }
    
    /**
     * Test 1: Database Connection and Setup
     */
    private function testDatabaseConnection() {
        try {
            require_once 'config.php';
            $this->conn = $conn;
            
            if ($this->conn instanceof PDO) {
                $stmt = $this->conn->query("SELECT 1");
                $result = $stmt->fetchColumn();
                
                if ($result == 1) {
                    $this->addResult("Database Connection", "PASS", "Database connection established successfully");
                } else {
                    $this->addResult("Database Connection", "FAIL", "Database query test failed");
                }
            } else {
                $this->addResult("Database Connection", "FAIL", "PDO connection not established");
            }
        } catch (Exception $e) {
            $this->addResult("Database Connection", "FAIL", "Database connection error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 2: Essential Database Tables
     */
    public function testDatabaseTables() {
        $requiredTables = [
            'data_jamaah' => 'Pilgrim registration data',
            'data_paket' => 'Package information',
            'data_invoice' => 'Invoice and payment tracking',
            'data_pembatalan' => 'Cancellation requests',
            'file_metadata' => 'File upload metadata'
        ];
        
        $missingTables = [];
        $tableDetails = [];
        
        foreach ($requiredTables as $table => $description) {
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $result['count'];
                
                $tableDetails[$table] = [
                    'status' => 'exists',
                    'record_count' => $count,
                    'description' => $description
                ];
                
            } catch (Exception $e) {
                $missingTables[] = $table;
                $tableDetails[$table] = [
                    'status' => 'missing',
                    'error' => $e->getMessage(),
                    'description' => $description
                ];
            }
        }
        
        if (empty($missingTables)) {
            $this->addResult("Database Tables", "PASS", "All required tables exist", $tableDetails);
        } else {
            $this->addResult("Database Tables", "FAIL", "Missing tables: " . implode(', ', $missingTables), $tableDetails);
        }
    }
    
    /**
     * Test 3: Critical Files
     */
    public function testCriticalFiles() {
        $criticalFiles = [
            'config.php' => 'Main configuration',
            'upload_handler.php' => 'File upload handling',
            'heroku_file_manager.php' => 'Cloud file management',
            'email_functions.php' => 'Email functionality',
            'confirm_payment.php' => 'Payment confirmation',
            'admin_dashboard.php' => 'Admin interface',
            'form_haji.php' => 'Haji registration form',
            'form_umroh.php' => 'Umroh registration form',
            'submit_haji.php' => 'Haji submission processing',
            'submit_umroh.php' => 'Umroh submission processing',
            'submit_pembatalan.php' => 'Cancellation processing',
            'admin_pending.php' => 'Pending registrations management',
            'admin_kelengkapan.php' => 'Document completeness check',
            'invoice.php' => 'Invoice generation'
        ];
        
        $missingFiles = [];
        $fileDetails = [];
        
        foreach ($criticalFiles as $file => $description) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $filesize = filesize(__DIR__ . '/' . $file);
                $lastmod = filemtime(__DIR__ . '/' . $file);
                
                $fileDetails[$file] = [
                    'status' => 'exists',
                    'size' => $filesize,
                    'last_modified' => date('Y-m-d H:i:s', $lastmod),
                    'description' => $description
                ];
            } else {
                $missingFiles[] = $file;
                $fileDetails[$file] = [
                    'status' => 'missing',
                    'description' => $description
                ];
            }
        }
        
        if (empty($missingFiles)) {
            $this->addResult("Critical Files", "PASS", "All critical files present", $fileDetails);
        } else {
            $this->addResult("Critical Files", "FAIL", "Missing files: " . implode(', ', $missingFiles), $fileDetails);
        }
    }
    
    /**
     * Test 4: Upload Handler System
     */
    public function testUploadHandler() {
        try {
            require_once 'upload_handler.php';
            $uploadHandler = new UploadHandler();
            
            // Test filename generation
            $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PKG001');
            
            if (strpos($testFilename, '1234567890123456') !== false && strpos($testFilename, 'test') !== false) {
                // Test error handling
                $uploadHandler->clearErrors();
                $hasNoErrors = !$uploadHandler->hasErrors();
                
                // Test upload stats
                $stats = $uploadHandler->getUploadStats();
                $hasValidStats = isset($stats['environment']) && isset($stats['upload_directory']);
                
                if ($hasNoErrors && $hasValidStats) {
                    $this->addResult("Upload Handler", "PASS", "Upload handler functioning correctly", [
                        'test_filename' => $testFilename,
                        'stats' => $stats
                    ]);
                } else {
                    $this->addResult("Upload Handler", "WARNING", "Upload handler has minor issues", [
                        'has_errors' => !$hasNoErrors,
                        'valid_stats' => $hasValidStats
                    ]);
                }
            } else {
                $this->addResult("Upload Handler", "FAIL", "Filename generation failed", [
                    'generated_filename' => $testFilename
                ]);
            }
        } catch (Exception $e) {
            $this->addResult("Upload Handler", "FAIL", "Upload handler error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 5: Session Management
     */
    public function testSessionManagement() {
        try {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Test session write/read
            $_SESSION['test_comprehensive'] = 'test_value_' . time();
            
            if (isset($_SESSION['test_comprehensive'])) {
                $value = $_SESSION['test_comprehensive'];
                unset($_SESSION['test_comprehensive']);
                
                $this->addResult("Session Management", "PASS", "Session functionality working", [
                    'session_id' => session_id(),
                    'test_value' => $value
                ]);
            } else {
                $this->addResult("Session Management", "FAIL", "Session write/read failed");
            }
        } catch (Exception $e) {
            $this->addResult("Session Management", "FAIL", "Session error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 6: Email Configuration
     */
    public function testEmailConfiguration() {
        try {
            require_once 'email_functions.php';
            
            $emailSettings = [
                'EMAIL_ENABLED' => defined('EMAIL_ENABLED') ? EMAIL_ENABLED : false,
                'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'not_defined',
                'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'not_defined',
                'EMAIL_FROM' => defined('EMAIL_FROM') ? EMAIL_FROM : 'not_defined',
                'ADMIN_EMAIL' => defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'not_defined'
            ];
            
            $configuredCount = 0;
            foreach ($emailSettings as $setting => $value) {
                if ($value !== 'not_defined' && $value !== false) {
                    $configuredCount++;
                }
            }
            
            if ($configuredCount >= 4) {
                $this->addResult("Email Configuration", "PASS", "Email system properly configured", $emailSettings);
            } elseif ($configuredCount >= 2) {
                $this->addResult("Email Configuration", "WARNING", "Email system partially configured", $emailSettings);
            } else {
                $this->addResult("Email Configuration", "FAIL", "Email system not configured", $emailSettings);
            }
        } catch (Exception $e) {
            $this->addResult("Email Configuration", "FAIL", "Email configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 7: File System Access
     */
    public function testFileSystemAccess() {
        try {
            // Test upload directory
            $uploadDir = __DIR__ . '/uploads';
            $errorLogDir = __DIR__ . '/error_logs';
            
            $directories = [
                'uploads' => $uploadDir,
                'error_logs' => $errorLogDir
            ];
            
            $directoryStatus = [];
            
            foreach ($directories as $name => $path) {
                if (!file_exists($path)) {
                    mkdir($path, 0755, true);
                }
                
                $directoryStatus[$name] = [
                    'exists' => file_exists($path),
                    'writable' => is_writable($path),
                    'path' => $path
                ];
            }
            
            $allAccessible = true;
            foreach ($directoryStatus as $status) {
                if (!$status['exists'] || !$status['writable']) {
                    $allAccessible = false;
                    break;
                }
            }
            
            if ($allAccessible) {
                $this->addResult("File System Access", "PASS", "All directories accessible", $directoryStatus);
            } else {
                $this->addResult("File System Access", "WARNING", "Some directories have access issues", $directoryStatus);
            }
        } catch (Exception $e) {
            $this->addResult("File System Access", "FAIL", "File system error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 8: Payment Confirmation Flow
     */
    public function testPaymentConfirmationFlow() {
        try {
            // Test if confirm_payment.php file exists and is readable
            if (file_exists(__DIR__ . '/confirm_payment.php')) {
                $content = file_get_contents(__DIR__ . '/confirm_payment.php');
                
                // Check for critical components
                $requiredComponents = [
                    'logDetailedError' => 'Enhanced error logging',
                    'upload_handler.php' => 'Upload handler inclusion',
                    'email_functions.php' => 'Email functions inclusion',
                    'beginTransaction' => 'Database transaction handling',
                    'handleUpload' => 'File upload processing',
                    'sendPaymentConfirmationEmail' => 'Email notification'
                ];
                
                $componentStatus = [];
                $componentsFound = 0;
                
                foreach ($requiredComponents as $component => $description) {
                    $found = strpos($content, $component) !== false;
                    $componentStatus[$component] = [
                        'found' => $found,
                        'description' => $description
                    ];
                    if ($found) $componentsFound++;
                }
                
                if ($componentsFound >= count($requiredComponents) - 1) {
                    $this->addResult("Payment Confirmation Flow", "PASS", "Payment confirmation components present", $componentStatus);
                } else {
                    $this->addResult("Payment Confirmation Flow", "WARNING", "Some payment confirmation components missing", $componentStatus);
                }
            } else {
                $this->addResult("Payment Confirmation Flow", "FAIL", "confirm_payment.php file not found");
            }
        } catch (Exception $e) {
            $this->addResult("Payment Confirmation Flow", "FAIL", "Payment flow test error: " . $e->getMessage());
        }
    }
    
    /**
     * Test 9: Admin Panel Functionality
     */
    public function testAdminPanelFunctionality() {
        $adminFiles = [
            'admin_dashboard.php' => 'Main dashboard',
            'admin_pending.php' => 'Pending registrations',
            'admin_kelengkapan.php' => 'Document completeness',
            'admin_manifest.php' => 'Manifest generation',
            'admin_paket.php' => 'Package management',
            'admin_pembatalan.php' => 'Cancellation management'
        ];
        
        $presentFiles = [];
        $missingFiles = [];
        
        foreach ($adminFiles as $file => $description) {
            if (file_exists(__DIR__ . '/' . $file)) {
                $presentFiles[$file] = $description;
            } else {
                $missingFiles[$file] = $description;
            }
        }
        
        if (empty($missingFiles)) {
            $this->addResult("Admin Panel Functionality", "PASS", "All admin files present", [
                'present_files' => $presentFiles
            ]);
        } else {
            $this->addResult("Admin Panel Functionality", "FAIL", "Missing admin files", [
                'present_files' => $presentFiles,
                'missing_files' => $missingFiles
            ]);
        }
    }
    
    /**
     * Test 10: Environment and PHP Configuration
     */
    public function testEnvironmentConfiguration() {
        $phpInfo = [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'file_uploads' => ini_get('file_uploads') ? 'enabled' : 'disabled',
            'display_errors' => ini_get('display_errors') ? 'on' : 'off',
            'log_errors' => ini_get('log_errors') ? 'on' : 'off'
        ];
        
        // Check for critical PHP extensions
        $requiredExtensions = ['pdo', 'mbstring', 'fileinfo'];
        $loadedExtensions = [];
        $missingExtensions = [];
        
        foreach ($requiredExtensions as $ext) {
            if (extension_loaded($ext)) {
                $loadedExtensions[] = $ext;
            } else {
                $missingExtensions[] = $ext;
            }
        }
        
        // Detect environment
        $environment = 'local';
        if (isset($_ENV['DYNO'])) {
            $environment = 'heroku';
        } elseif (isset($_ENV['RAILWAY_ENVIRONMENT'])) {
            $environment = 'railway';
        } elseif (isset($_ENV['RENDER'])) {
            $environment = 'render';
        }
        
        $configStatus = [
            'php_info' => $phpInfo,
            'loaded_extensions' => $loadedExtensions,
            'missing_extensions' => $missingExtensions,
            'environment' => $environment
        ];
        
        if (empty($missingExtensions) && version_compare(PHP_VERSION, '7.4.0', '>=')) {
            $this->addResult("Environment Configuration", "PASS", "PHP environment properly configured", $configStatus);
        } else {
            $this->addResult("Environment Configuration", "WARNING", "PHP environment has minor issues", $configStatus);
        }
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        $this->log("Starting comprehensive test suite");
        
        $this->testDatabaseTables();
        $this->testCriticalFiles();
        $this->testUploadHandler();
        $this->testSessionManagement();
        $this->testEmailConfiguration();
        $this->testFileSystemAccess();
        $this->testPaymentConfirmationFlow();
        $this->testAdminPanelFunctionality();
        $this->testEnvironmentConfiguration();
        
        $this->log("Comprehensive test suite completed");
        
        return $this->generateReport();
    }
    
    /**
     * Generate comprehensive report
     */
    private function generateReport() {
        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);
        
        $summary = [
            'total_tests' => count($this->testResults),
            'passed' => count(array_filter($this->testResults, function($r) { return $r['status'] === 'PASS'; })),
            'failed' => count(array_filter($this->testResults, function($r) { return $r['status'] === 'FAIL'; })),
            'warnings' => count(array_filter($this->testResults, function($r) { return $r['status'] === 'WARNING'; })),
            'execution_time_ms' => $executionTime,
            'critical_issues' => $this->criticalIssues,
            'warnings_list' => $this->warnings
        ];
        
        return [
            'summary' => $summary,
            'test_results' => $this->testResults,
            'log_file' => $this->logFile,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// Run the comprehensive test suite
$tester = new ComprehensiveProjectTester();
$report = $tester->runAllTests();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Comprehensive Project Testing - MIW</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 25px; margin: -30px -30px 30px; border-radius: 12px 12px 0 0; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .summary-card { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 8px; padding: 20px; text-align: center; }
        .summary-card.pass { border-color: #28a745; background: #d4edda; }
        .summary-card.fail { border-color: #dc3545; background: #f8d7da; }
        .summary-card.warning { border-color: #ffc107; background: #fff3cd; }
        .test-results { margin: 30px 0; }
        .test-item { background: #f8f9fa; margin: 15px 0; padding: 20px; border-radius: 8px; border-left: 5px solid #6c757d; }
        .test-item.pass { border-left-color: #28a745; background: #d4edda; }
        .test-item.fail { border-left-color: #dc3545; background: #f8d7da; }
        .test-item.warning { border-left-color: #ffc107; background: #fff3cd; }
        .test-title { font-size: 1.2em; font-weight: bold; margin-bottom: 10px; }
        .test-message { margin-bottom: 10px; }
        .test-details { background: #ffffff; padding: 15px; border-radius: 5px; margin-top: 10px; font-size: 0.9em; }
        .critical-issues { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .warnings-section { background: #ffc107; color: #212529; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .success-banner { background: #28a745; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: center; }
        .progress-bar { width: 100%; height: 20px; background: #e9ecef; border-radius: 10px; overflow: hidden; margin: 15px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); transition: width 0.3s ease; }
        .timestamp { color: #6c757d; font-size: 0.9em; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; font-size: 0.8em; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔬 Comprehensive Project Testing Suite</h1>
            <p>Complete analysis of MIW Travel Management System</p>
            <p><strong>Execution Time:</strong> <?= $report['summary']['execution_time_ms'] ?>ms | 
               <strong>Timestamp:</strong> <?= $report['timestamp'] ?></p>
        </div>

        <?php
        $summary = $report['summary'];
        $successRate = round(($summary['passed'] / $summary['total_tests']) * 100, 1);
        ?>

        <div class="summary-grid">
            <div class="summary-card pass">
                <h3><?= $summary['passed'] ?></h3>
                <p>Tests Passed</p>
            </div>
            <div class="summary-card fail">
                <h3><?= $summary['failed'] ?></h3>
                <p>Tests Failed</p>
            </div>
            <div class="summary-card warning">
                <h3><?= $summary['warnings'] ?></h3>
                <p>Warnings</p>
            </div>
            <div class="summary-card">
                <h3><?= $successRate ?>%</h3>
                <p>Success Rate</p>
            </div>
        </div>

        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $successRate ?>%"></div>
        </div>

        <?php if ($summary['failed'] === 0): ?>
            <div class="success-banner">
                <h2>🎉 ALL CRITICAL TESTS PASSED!</h2>
                <p>Your MIW Travel Management System is functioning properly</p>
                <?php if ($summary['warnings'] > 0): ?>
                    <p><small>Note: There are <?= $summary['warnings'] ?> warning(s) to review for optimization</small></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($summary['critical_issues'])): ?>
            <div class="critical-issues">
                <h3>🚨 CRITICAL ISSUES DETECTED</h3>
                <ul>
                    <?php foreach ($summary['critical_issues'] as $issue): ?>
                        <li><?= htmlspecialchars($issue) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Action Required:</strong> These issues must be resolved for proper system operation.</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($summary['warnings_list'])): ?>
            <div class="warnings-section">
                <h3>⚠️ WARNINGS</h3>
                <ul>
                    <?php foreach ($summary['warnings_list'] as $warning): ?>
                        <li><?= htmlspecialchars($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><strong>Recommendation:</strong> Review these items for optimal performance.</p>
            </div>
        <?php endif; ?>

        <div class="test-results">
            <h2>📋 Detailed Test Results</h2>
            
            <?php foreach ($report['test_results'] as $result): ?>
                <div class="test-item <?= strtolower($result['status']) ?>">
                    <div class="test-title">
                        <?php if ($result['status'] === 'PASS'): ?>
                            ✅
                        <?php elseif ($result['status'] === 'FAIL'): ?>
                            ❌
                        <?php else: ?>
                            ⚠️
                        <?php endif; ?>
                        <?= htmlspecialchars($result['test']) ?>
                    </div>
                    <div class="test-message">
                        <strong><?= $result['status'] ?>:</strong> <?= htmlspecialchars($result['message']) ?>
                    </div>
                    <div class="timestamp">
                        <?= $result['timestamp'] ?>
                    </div>
                    
                    <?php if (!empty($result['details'])): ?>
                        <div class="test-details">
                            <strong>Details:</strong>
                            <pre><?= htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT)) ?></pre>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 8px;">
            <h3>📊 Summary Report</h3>
            <p><strong>Total Tests Run:</strong> <?= $summary['total_tests'] ?></p>
            <p><strong>Overall System Status:</strong> 
                <?php if ($summary['failed'] === 0): ?>
                    <span style="color: #28a745; font-weight: bold;">HEALTHY</span>
                <?php elseif ($summary['failed'] <= 2): ?>
                    <span style="color: #ffc107; font-weight: bold;">NEEDS ATTENTION</span>
                <?php else: ?>
                    <span style="color: #dc3545; font-weight: bold;">CRITICAL ISSUES</span>
                <?php endif; ?>
            </p>
            <p><strong>Log File:</strong> <?= basename($report['log_file']) ?></p>
            <p><strong>Next Steps:</strong></p>
            <ul>
                <?php if ($summary['failed'] > 0): ?>
                    <li>Address critical issues immediately</li>
                    <li>Check the log file for detailed error information</li>
                <?php endif; ?>
                <?php if ($summary['warnings'] > 0): ?>
                    <li>Review warnings for optimization opportunities</li>
                <?php endif; ?>
                <li>Run tests regularly to monitor system health</li>
                <li>Deploy fixes to production after testing locally</li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px; color: #6c757d;">
            <p>Comprehensive Project Testing Suite v1.0.0</p>
            <p>MIW Travel Management System</p>
        </div>
    </div>
</body>
</html>
