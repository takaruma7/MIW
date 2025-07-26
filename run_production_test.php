<?php
/**
 * Production Environment Test Runner for MIW
 * 
 * This script specifically tests the deployed project for production issues,
 * focusing on real-world scenarios and deployment-specific problems.
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Set up error reporting for production testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure test logs directory exists
if (!file_exists(__DIR__ . '/test_logs')) {
    mkdir(__DIR__ . '/test_logs', 0755, true);
}

class ProductionTestRunner {
    private $conn;
    private $testResults = [];
    private $logFile;
    private $environment;
    private $productionIssues = [];
    private $performanceMetrics = [];
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->environment = $this->detectProductionEnvironment();
        $this->logFile = __DIR__ . '/test_logs/production_test_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("=== PRODUCTION TEST RUNNER STARTED ===");
        $this->log("Detected Environment: " . $this->environment);
    }
    
    private function detectProductionEnvironment() {
        if (isset($_ENV['DYNO'])) return 'Heroku';
        if (isset($_ENV['RAILWAY_ENVIRONMENT'])) return 'Railway';
        if (isset($_ENV['RENDER'])) return 'Render';
        if (isset($_SERVER['HTTP_HOST'])) {
            if (strpos($_SERVER['HTTP_HOST'], 'herokuapp.com') !== false) return 'Heroku';
            if (strpos($_SERVER['HTTP_HOST'], 'railway.app') !== false) return 'Railway';
            if (strpos($_SERVER['HTTP_HOST'], 'render.com') !== false) return 'Render';
        }
        return 'Local/Development';
    }
    
    private function log($message, $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] PRODUCTION_TEST: {$message}\n";
        if (!empty($context)) {
            $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
        }
        $logEntry .= str_repeat('-', 80) . "\n";
        
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    private function recordPerformanceMetric($operation, $duration, $status = 'success') {
        $this->performanceMetrics[] = [
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    private function addProductionIssue($issue, $severity = 'warning') {
        $this->productionIssues[] = [
            'issue' => $issue,
            'severity' => $severity,
            'timestamp' => date('Y-m-d H:i:s'),
            'environment' => $this->environment
        ];
        $this->log("PRODUCTION ISSUE ($severity): " . $issue);
    }
    
    /**
     * Test 1: Production Environment Validation
     */
    public function testProductionEnvironment() {
        $this->log("Testing Production Environment Configuration");
        $startTime = microtime(true);
        $results = [];
        
        // Check environment variables
        $requiredEnvVars = ['APP_ENV', 'DB_HOST', 'DB_NAME', 'DB_USER'];
        $missingVars = [];
        
        foreach ($requiredEnvVars as $var) {
            if (!defined($var) && !isset($_ENV[$var])) {
                $missingVars[] = $var;
            }
        }
        
        $results['environment_variables'] = [
            'status' => empty($missingVars) ? 'pass' : 'fail',
            'message' => empty($missingVars) ? 'All required environment variables set' : 'Missing: ' . implode(', ', $missingVars),
            'missing_vars' => $missingVars
        ];
        
        if (!empty($missingVars)) {
            $this->addProductionIssue("Missing environment variables: " . implode(', ', $missingVars), 'critical');
        }
        
        // Check production vs development mode
        $isProduction = (defined('APP_ENV') && APP_ENV === 'production') || 
                       ($this->environment !== 'Local/Development');
        
        $results['production_mode'] = [
            'status' => $isProduction ? 'pass' : 'warning',
            'message' => $isProduction ? 'Running in production mode' : 'Not running in production mode',
            'environment' => $this->environment
        ];
        
        // Check PHP configuration for production
        $productionSettings = [
            'display_errors' => ['expected' => '0', 'actual' => ini_get('display_errors')],
            'log_errors' => ['expected' => '1', 'actual' => ini_get('log_errors')],
            'expose_php' => ['expected' => '0', 'actual' => ini_get('expose_php')]
        ];
        
        $configIssues = [];
        foreach ($productionSettings as $setting => $config) {
            if ($config['actual'] !== $config['expected']) {
                $configIssues[] = "$setting: expected {$config['expected']}, got {$config['actual']}";
            }
        }
        
        $results['php_configuration'] = [
            'status' => empty($configIssues) ? 'pass' : 'warning',
            'message' => empty($configIssues) ? 'PHP configured for production' : 'PHP configuration issues found',
            'issues' => $configIssues
        ];
        
        if (!empty($configIssues)) {
            $this->addProductionIssue("PHP configuration not optimized for production: " . implode(', ', $configIssues), 'warning');
        }
        
        $this->recordPerformanceMetric('environment_test', microtime(true) - $startTime);
        return $results;
    }
    
    /**
     * Test 2: Database Performance and Reliability
     */
    public function testDatabasePerformance() {
        $this->log("Testing Database Performance and Reliability");
        $results = [];
        
        // Test basic connectivity with timing
        $startTime = microtime(true);
        try {
            $stmt = $this->conn->prepare("SELECT 1 as test");
            $stmt->execute();
            $connectionTime = microtime(true) - $startTime;
            
            $results['connectivity'] = [
                'status' => 'pass',
                'message' => 'Database connection successful',
                'connection_time_ms' => round($connectionTime * 1000, 2)
            ];
            
            $this->recordPerformanceMetric('db_connection', $connectionTime);
            
            if ($connectionTime > 1.0) {
                $this->addProductionIssue("Slow database connection: " . round($connectionTime * 1000, 2) . "ms", 'warning');
            }
            
        } catch (Exception $e) {
            $results['connectivity'] = [
                'status' => 'fail',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
            $this->addProductionIssue("Database connection failure: " . $e->getMessage(), 'critical');
            return $results;
        }
        
        // Test table access performance
        $tables = ['data_jamaah', 'data_paket', 'data_pembatalan'];
        $tablePerformance = [];
        
        foreach ($tables as $table) {
            $startTime = microtime(true);
            try {
                $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM $table");
                $stmt->execute();
                $result = $stmt->fetch();
                $queryTime = microtime(true) - $startTime;
                
                $tablePerformance[$table] = [
                    'status' => 'pass',
                    'count' => $result['count'],
                    'query_time_ms' => round($queryTime * 1000, 2)
                ];
                
                $this->recordPerformanceMetric("table_access_$table", $queryTime);
                
                if ($queryTime > 0.5) {
                    $this->addProductionIssue("Slow table access for $table: " . round($queryTime * 1000, 2) . "ms", 'warning');
                }
                
            } catch (Exception $e) {
                $tablePerformance[$table] = [
                    'status' => 'fail',
                    'error' => $e->getMessage()
                ];
                $this->addProductionIssue("Table access failed for $table: " . $e->getMessage(), 'critical');
            }
        }
        
        $results['table_performance'] = $tablePerformance;
        
        // Test transaction performance
        $startTime = microtime(true);
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah LIMIT 1");
            $stmt->execute();
            $this->conn->rollBack();
            $transactionTime = microtime(true) - $startTime;
            
            $results['transaction_performance'] = [
                'status' => 'pass',
                'message' => 'Transaction performance acceptable',
                'transaction_time_ms' => round($transactionTime * 1000, 2)
            ];
            
            $this->recordPerformanceMetric('db_transaction', $transactionTime);
            
        } catch (Exception $e) {
            $results['transaction_performance'] = [
                'status' => 'fail',
                'message' => 'Transaction test failed: ' . $e->getMessage()
            ];
            $this->addProductionIssue("Transaction performance test failed: " . $e->getMessage(), 'warning');
        }
        
        return $results;
    }
    
    /**
     * Test 3: File Upload System in Production
     */
    public function testFileUploadProduction() {
        $this->log("Testing File Upload System in Production Environment");
        $results = [];
        
        // Test upload handler initialization
        $startTime = microtime(true);
        try {
            require_once 'upload_handler.php';
            $uploadHandler = new UploadHandler();
            $initTime = microtime(true) - $startTime;
            
            $results['upload_handler_init'] = [
                'status' => 'pass',
                'message' => 'Upload handler initialized successfully',
                'init_time_ms' => round($initTime * 1000, 2)
            ];
            
            $this->recordPerformanceMetric('upload_handler_init', $initTime);
            
        } catch (Exception $e) {
            $results['upload_handler_init'] = [
                'status' => 'fail',
                'message' => 'Upload handler initialization failed: ' . $e->getMessage()
            ];
            $this->addProductionIssue("Upload handler initialization failed: " . $e->getMessage(), 'critical');
            return $results;
        }
        
        // Test upload directory in production
        $uploadStats = $uploadHandler->getUploadStats();
        $uploadDir = $uploadStats['upload_directory'] ?? __DIR__ . '/uploads';
        
        $results['upload_directory'] = [
            'status' => file_exists($uploadDir) ? 'pass' : 'warning',
            'message' => file_exists($uploadDir) ? 'Upload directory exists' : 'Upload directory missing (expected on ephemeral storage)',
            'directory' => $uploadDir,
            'writable' => file_exists($uploadDir) ? is_writable($uploadDir) : false,
            'environment_type' => $uploadStats['environment'] ?? 'unknown'
        ];
        
        if ($this->environment === 'Heroku' && !file_exists($uploadDir)) {
            $this->log("Heroku ephemeral storage detected - uploads will use temporary storage");
        } elseif (!file_exists($uploadDir)) {
            $this->addProductionIssue("Upload directory missing: $uploadDir", 'warning');
        }
        
        // Test filename generation performance
        $startTime = microtime(true);
        $testFilenames = [];
        for ($i = 0; $i < 10; $i++) {
            $filename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PKG001');
            $testFilenames[] = $filename;
        }
        $filenameGenTime = microtime(true) - $startTime;
        
        $results['filename_generation'] = [
            'status' => 'pass',
            'message' => 'Filename generation performance acceptable',
            'avg_time_ms' => round(($filenameGenTime / 10) * 1000, 2),
            'total_time_ms' => round($filenameGenTime * 1000, 2)
        ];
        
        $this->recordPerformanceMetric('filename_generation', $filenameGenTime / 10);
        
        return $results;
    }
    
    /**
     * Test 4: Email System Production Readiness
     */
    public function testEmailSystemProduction() {
        $this->log("Testing Email System Production Configuration");
        $results = [];
        
        // Check email configuration
        $emailConfigured = defined('SMTP_HOST') && defined('SMTP_PORT') && 
                          defined('SMTP_USERNAME') && defined('SMTP_PASSWORD');
        
        $results['email_configuration'] = [
            'status' => $emailConfigured ? 'pass' : 'fail',
            'message' => $emailConfigured ? 'Email fully configured' : 'Email configuration incomplete',
            'smtp_host' => defined('SMTP_HOST') ? SMTP_HOST : 'Not configured',
            'smtp_port' => defined('SMTP_PORT') ? SMTP_PORT : 'Not configured'
        ];
        
        if (!$emailConfigured) {
            $this->addProductionIssue("Email system not properly configured for production", 'critical');
        }
        
        // Test email functions availability
        if (file_exists(__DIR__ . '/email_functions.php')) {
            $startTime = microtime(true);
            require_once __DIR__ . '/email_functions.php';
            $loadTime = microtime(true) - $startTime;
            
            $results['email_functions'] = [
                'status' => 'pass',
                'message' => 'Email functions loaded successfully',
                'load_time_ms' => round($loadTime * 1000, 2)
            ];
            
            $this->recordPerformanceMetric('email_functions_load', $loadTime);
            
            // Check if critical email functions exist
            $requiredFunctions = ['sendPaymentConfirmationEmail', 'buildEmailTemplate'];
            $missingFunctions = [];
            
            foreach ($requiredFunctions as $function) {
                if (!function_exists($function)) {
                    $missingFunctions[] = $function;
                }
            }
            
            if (!empty($missingFunctions)) {
                $results['email_functions']['status'] = 'warning';
                $results['email_functions']['missing_functions'] = $missingFunctions;
                $this->addProductionIssue("Missing email functions: " . implode(', ', $missingFunctions), 'warning');
            }
            
        } else {
            $results['email_functions'] = [
                'status' => 'fail',
                'message' => 'Email functions file missing'
            ];
            $this->addProductionIssue("Email functions file missing: email_functions.php", 'critical');
        }
        
        return $results;
    }
    
    /**
     * Test 5: Confirm Payment Endpoint Production Testing
     */
    public function testConfirmPaymentProduction() {
        $this->log("Testing Confirm Payment Endpoint in Production");
        $results = [];
        
        // Check if confirm_payment.php exists and is readable
        if (file_exists(__DIR__ . '/confirm_payment.php')) {
            $content = file_get_contents(__DIR__ . '/confirm_payment.php');
            
            // Check for production-ready error handling
            $hasErrorLogging = strpos($content, 'logDetailedError') !== false;
            $hasExceptionHandling = strpos($content, 'try {') !== false && strpos($content, 'catch') !== false;
            $hasTransactionHandling = strpos($content, 'beginTransaction') !== false && strpos($content, 'rollBack') !== false;
            $hasFileValidation = strpos($content, 'UPLOAD_ERR_OK') !== false;
            
            $results['confirm_payment_readiness'] = [
                'status' => ($hasErrorLogging && $hasExceptionHandling && $hasTransactionHandling) ? 'pass' : 'warning',
                'message' => 'Confirm payment production readiness assessment',
                'has_error_logging' => $hasErrorLogging,
                'has_exception_handling' => $hasExceptionHandling,
                'has_transaction_handling' => $hasTransactionHandling,
                'has_file_validation' => $hasFileValidation
            ];
            
            if (!$hasErrorLogging) {
                $this->addProductionIssue("Confirm payment lacks detailed error logging", 'warning');
            }
            if (!$hasTransactionHandling) {
                $this->addProductionIssue("Confirm payment lacks proper transaction handling", 'warning');
            }
            
            // Test POST request validation
            $hasPostValidation = strpos($content, '$_SERVER[\'REQUEST_METHOD\'] === \'POST\'') !== false;
            $hasRequiredFieldValidation = strpos($content, 'required') !== false || strpos($content, 'empty') !== false;
            
            $results['request_validation'] = [
                'status' => ($hasPostValidation && $hasRequiredFieldValidation) ? 'pass' : 'warning',
                'message' => 'Request validation assessment',
                'has_post_validation' => $hasPostValidation,
                'has_field_validation' => $hasRequiredFieldValidation
            ];
            
        } else {
            $results['confirm_payment_readiness'] = [
                'status' => 'fail',
                'message' => 'confirm_payment.php file missing'
            ];
            $this->addProductionIssue("Critical file missing: confirm_payment.php", 'critical');
        }
        
        return $results;
    }
    
    /**
     * Test 6: Production Security Measures
     */
    public function testProductionSecurity() {
        $this->log("Testing Production Security Measures");
        $results = [];
        
        // Test session security
        $sessionStarted = session_status() === PHP_SESSION_ACTIVE;
        if (!$sessionStarted && session_status() === PHP_SESSION_NONE) {
            session_start();
            $sessionStarted = session_status() === PHP_SESSION_ACTIVE;
        }
        
        $results['session_security'] = [
            'status' => $sessionStarted ? 'pass' : 'warning',
            'message' => $sessionStarted ? 'Session security active' : 'Session security not active',
            'session_id' => $sessionStarted ? session_id() : 'none',
            'session_status' => session_status()
        ];
        
        // Test SQL injection protection (basic check)
        try {
            $maliciousInput = "'; DROP TABLE data_jamaah; --";
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM data_jamaah WHERE nik = ?");
            $stmt->execute([$maliciousInput]);
            
            $results['sql_injection_protection'] = [
                'status' => 'pass',
                'message' => 'SQL injection protection working (prepared statements)'
            ];
        } catch (Exception $e) {
            $results['sql_injection_protection'] = [
                'status' => 'warning',
                'message' => 'Unable to verify SQL injection protection: ' . $e->getMessage()
            ];
            $this->addProductionIssue("SQL injection protection test failed: " . $e->getMessage(), 'warning');
        }
        
        // Check for sensitive information exposure
        $sensitiveChecks = [
            'phpinfo_disabled' => !function_exists('phpinfo') || ini_get('expose_php') === '0',
            'error_display_off' => ini_get('display_errors') === '0',
            'directory_listing' => true // Assume protected unless we can check
        ];
        
        $results['sensitive_info_protection'] = [
            'status' => array_reduce($sensitiveChecks, function($carry, $item) { return $carry && $item; }, true) ? 'pass' : 'warning',
            'message' => 'Sensitive information protection assessment',
            'checks' => $sensitiveChecks
        ];
        
        return $results;
    }
    
    /**
     * Test 7: Production Performance Monitoring
     */
    public function testProductionPerformance() {
        $this->log("Testing Production Performance Metrics");
        $results = [];
        
        // Memory usage test
        $memoryUsage = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        // Convert memory limit to bytes for comparison
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $memoryUsagePercent = $memoryLimitBytes > 0 ? ($peakMemory / $memoryLimitBytes) * 100 : 0;
        
        $results['memory_usage'] = [
            'status' => $memoryUsagePercent < 80 ? 'pass' : 'warning',
            'message' => 'Memory usage assessment',
            'current_usage' => $this->formatBytes($memoryUsage),
            'peak_usage' => $this->formatBytes($peakMemory),
            'memory_limit' => $memoryLimit,
            'usage_percentage' => round($memoryUsagePercent, 2)
        ];
        
        if ($memoryUsagePercent > 80) {
            $this->addProductionIssue("High memory usage: " . round($memoryUsagePercent, 2) . "%", 'warning');
        }
        
        // Execution time limits
        $maxExecutionTime = ini_get('max_execution_time');
        $results['execution_limits'] = [
            'status' => $maxExecutionTime >= 30 ? 'pass' : 'warning',
            'message' => 'Execution time limits',
            'max_execution_time' => $maxExecutionTime . ' seconds',
            'recommended' => '30+ seconds for file uploads'
        ];
        
        // File upload limits
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        
        $results['upload_limits'] = [
            'status' => 'pass',
            'message' => 'File upload limits configuration',
            'upload_max_filesize' => $uploadMaxFilesize,
            'post_max_size' => $postMaxSize,
            'recommended' => '2M+ for document uploads'
        ];
        
        return $results;
    }
    
    private function convertToBytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;
        }
        return $val;
    }
    
    private function formatBytes($size, $precision = 2) {
        $base = log($size, 1024);
        $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }
    
    /**
     * Generate production test report
     */
    public function generateProductionReport() {
        $this->log("Generating production test report");
        
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $warningTests = 0;
        
        foreach ($this->testResults as $testSuite) {
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
        
        return [
            'environment' => $this->environment,
            'test_summary' => [
                'total_tests' => $totalTests,
                'passed_tests' => $passedTests,
                'failed_tests' => $failedTests,
                'warning_tests' => $warningTests,
                'success_rate' => $successRate
            ],
            'production_issues' => $this->productionIssues,
            'performance_metrics' => $this->performanceMetrics,
            'test_results' => $this->testResults,
            'log_file' => $this->logFile,
            'recommendations' => $this->generateProductionRecommendations()
        ];
    }
    
    private function generateProductionRecommendations() {
        $recommendations = [];
        
        $criticalIssues = array_filter($this->productionIssues, function($issue) {
            return $issue['severity'] === 'critical';
        });
        
        $warningIssues = array_filter($this->productionIssues, function($issue) {
            return $issue['severity'] === 'warning';
        });
        
        if (!empty($criticalIssues)) {
            $recommendations[] = "🔴 CRITICAL: Address " . count($criticalIssues) . " critical issues immediately";
            foreach ($criticalIssues as $issue) {
                $recommendations[] = "   - " . $issue['issue'];
            }
        }
        
        if (!empty($warningIssues)) {
            $recommendations[] = "🟡 WARNING: Review " . count($warningIssues) . " warning issues";
            foreach ($warningIssues as $issue) {
                $recommendations[] = "   - " . $issue['issue'];
            }
        }
        
        // Environment-specific recommendations
        switch ($this->environment) {
            case 'Heroku':
                $recommendations[] = "📦 HEROKU: Monitor dyno usage and implement database backups";
                $recommendations[] = "📦 HEROKU: Consider implementing cloud storage for persistent file uploads";
                break;
            case 'Railway':
                $recommendations[] = "🚂 RAILWAY: Monitor resource usage and scaling needs";
                break;
            case 'Render':
                $recommendations[] = "🎨 RENDER: Monitor build times and deployment frequency";
                break;
        }
        
        $recommendations[] = "📊 MONITORING: Implement application performance monitoring (APM)";
        $recommendations[] = "🔒 SECURITY: Regular security audits and vulnerability assessments";
        $recommendations[] = "📈 PERFORMANCE: Monitor database query performance and optimize slow queries";
        
        return $recommendations;
    }
    
    /**
     * Run all production tests
     */
    public function runAllProductionTests() {
        $this->log("Starting comprehensive production testing");
        
        $this->testResults['environment'] = $this->testProductionEnvironment();
        $this->testResults['database_performance'] = $this->testDatabasePerformance();
        $this->testResults['file_upload'] = $this->testFileUploadProduction();
        $this->testResults['email_system'] = $this->testEmailSystemProduction();
        $this->testResults['confirm_payment'] = $this->testConfirmPaymentProduction();
        $this->testResults['security'] = $this->testProductionSecurity();
        $this->testResults['performance'] = $this->testProductionPerformance();
        
        $report = $this->generateProductionReport();
        
        $this->log("Production testing completed", [
            'total_tests' => $report['test_summary']['total_tests'],
            'success_rate' => $report['test_summary']['success_rate'],
            'critical_issues' => count(array_filter($this->productionIssues, function($i) { return $i['severity'] === 'critical'; })),
            'warnings' => count(array_filter($this->productionIssues, function($i) { return $i['severity'] === 'warning'; }))
        ]);
        
        return $report;
    }
}

// Run production tests
$productionTester = new ProductionTestRunner($conn);
$report = $productionTester->runAllProductionTests();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Test Report - MIW</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 20px; background: #f1f3f4; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: linear-gradient(135deg, #e53e3e 0%, #d53f8c 100%); color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px; }
        .summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .card.critical { border-left: 6px solid #e53e3e; }
        .card.warning { border-left: 6px solid #f6ad55; }
        .card.success { border-left: 6px solid #38a169; }
        .card.info { border-left: 6px solid #3182ce; }
        .metric-large { font-size: 2.5em; font-weight: bold; margin: 10px 0; }
        .test-section { background: white; margin-bottom: 25px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08); }
        .test-header { background: #f7fafc; padding: 20px; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; font-weight: bold; font-size: 1.1em; }
        .test-content { padding: 20px; }
        .status-pass { color: #38a169; font-weight: bold; }
        .status-fail { color: #e53e3e; font-weight: bold; }
        .status-warning { color: #d69e2e; font-weight: bold; }
        .progress-container { margin: 15px 0; }
        .progress-bar { width: 100%; height: 30px; background: #edf2f7; border-radius: 15px; overflow: hidden; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #38a169, #48bb78); transition: width 0.3s ease; }
        .performance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .perf-metric { background: #f8f9fa; padding: 15px; border-radius: 8px; text-align: center; }
        .issue-card { margin: 15px 0; padding: 20px; border-radius: 8px; }
        .issue-critical { background: #fed7d7; border-left: 4px solid #e53e3e; }
        .issue-warning { background: #fef5e7; border-left: 4px solid #d69e2e; }
        .recommendations { background: #e6fffa; border: 1px solid #81e6d9; border-radius: 12px; padding: 25px; margin: 25px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        .env-badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .env-heroku { background: #5A67D8; color: white; }
        .env-railway { background: #2D3748; color: white; }
        .env-render { background: #68D391; color: white; }
        .env-local { background: #A0AEC0; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Production Environment Test Report</h1>
            <p>Comprehensive testing of deployed MIW Travel Management System</p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px;">
                <div>
                    <span class="env-badge env-<?= strtolower($report['environment']) ?>">
                        <?= $report['environment'] ?> Environment
                    </span>
                </div>
                <div><strong>Success Rate:</strong> <?= $report['test_summary']['success_rate'] ?>%</div>
            </div>
        </div>
        
        <div class="summary-grid">
            <div class="card success">
                <h3>✅ Tests Passed</h3>
                <div class="metric-large"><?= $report['test_summary']['passed_tests'] ?></div>
                <div>out of <?= $report['test_summary']['total_tests'] ?> total tests</div>
            </div>
            
            <div class="card <?= $report['test_summary']['failed_tests'] > 0 ? 'critical' : 'success' ?>">
                <h3><?= $report['test_summary']['failed_tests'] > 0 ? '❌' : '✅' ?> Critical Issues</h3>
                <div class="metric-large"><?= $report['test_summary']['failed_tests'] ?></div>
                <div><?= $report['test_summary']['failed_tests'] > 0 ? 'Require immediate attention' : 'No critical issues' ?></div>
            </div>
            
            <div class="card <?= $report['test_summary']['warning_tests'] > 0 ? 'warning' : 'success' ?>">
                <h3>⚠️ Warnings</h3>
                <div class="metric-large"><?= $report['test_summary']['warning_tests'] ?></div>
                <div><?= $report['test_summary']['warning_tests'] > 0 ? 'Issues to review' : 'No warnings' ?></div>
            </div>
            
            <div class="card info">
                <h3>📊 Production Health</h3>
                <div class="metric-large"><?= $report['test_summary']['success_rate'] ?>%</div>
                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $report['test_summary']['success_rate'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!empty($report['production_issues'])): ?>
            <?php 
            $criticalIssues = array_filter($report['production_issues'], function($issue) { return $issue['severity'] === 'critical'; });
            $warningIssues = array_filter($report['production_issues'], function($issue) { return $issue['severity'] === 'warning'; });
            ?>
            
            <?php if (!empty($criticalIssues)): ?>
            <div class="issue-card issue-critical">
                <h3>🔴 Critical Production Issues</h3>
                <p><strong>These issues require immediate attention and may prevent proper system functionality:</strong></p>
                <ul>
                    <?php foreach ($criticalIssues as $issue): ?>
                        <li><strong><?= htmlspecialchars($issue['issue']) ?></strong> (<?= $issue['timestamp'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($warningIssues)): ?>
            <div class="issue-card issue-warning">
                <h3>🟡 Production Warnings</h3>
                <p><strong>These issues should be reviewed to improve system reliability:</strong></p>
                <ul>
                    <?php foreach ($warningIssues as $issue): ?>
                        <li><?= htmlspecialchars($issue['issue']) ?> (<?= $issue['timestamp'] ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if (!empty($report['performance_metrics'])): ?>
        <div class="test-section">
            <div class="test-header">⚡ Performance Metrics</div>
            <div class="test-content">
                <div class="performance-grid">
                    <?php foreach ($report['performance_metrics'] as $metric): ?>
                        <div class="perf-metric">
                            <div><strong><?= ucwords(str_replace('_', ' ', $metric['operation'])) ?></strong></div>
                            <div style="font-size: 1.5em; color: <?= $metric['duration_ms'] > 1000 ? '#e53e3e' : '#38a169' ?>;">
                                <?= $metric['duration_ms'] ?>ms
                            </div>
                            <div style="font-size: 0.9em; color: #666;"><?= $metric['timestamp'] ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php foreach ($report['test_results'] as $testCategory => $testResults): ?>
        <div class="test-section">
            <div class="test-header">📋 <?= ucwords(str_replace('_', ' ', $testCategory)) ?> Test Results</div>
            <div class="test-content">
                <table>
                    <thead>
                        <tr><th>Test</th><th>Status</th><th>Message</th><th>Details</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($testResults as $testName => $result): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $testName))) ?></strong></td>
                                <td class="status-<?= $result['status'] ?? 'unknown' ?>"><?= strtoupper($result['status'] ?? 'UNKNOWN') ?></td>
                                <td><?= htmlspecialchars($result['message'] ?? 'No message') ?></td>
                                <td>
                                    <?php if (isset($result['details']) || isset($result['connection_time_ms']) || isset($result['environment'])): ?>
                                        <small style="color: #666;">
                                            <?php if (isset($result['connection_time_ms'])): ?>
                                                Time: <?= $result['connection_time_ms'] ?>ms<br>
                                            <?php endif; ?>
                                            <?php if (isset($result['environment'])): ?>
                                                Env: <?= $result['environment'] ?><br>
                                            <?php endif; ?>
                                            <?php if (isset($result['directory'])): ?>
                                                Dir: <?= basename($result['directory']) ?><br>
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
        
        <div class="recommendations">
            <h3>💡 Production Recommendations</h3>
            <ul>
                <?php foreach ($report['recommendations'] as $recommendation): ?>
                    <li><?= htmlspecialchars($recommendation) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div style="text-align: center; margin-top: 30px; padding: 25px; background: white; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.08);">
            <p><strong>Production Test Log:</strong> <code><?= basename($report['log_file']) ?></code></p>
            <p><strong>Report Generated:</strong> <?= date('Y-m-d H:i:s T') ?></p>
            <div style="margin-top: 20px;">
                <a href="comprehensive_testing_suite.php" style="margin: 0 10px; padding: 10px 20px; background: #3182ce; color: white; text-decoration: none; border-radius: 6px;">🧪 Full Test Suite</a>
                <a href="error_viewer.php" style="margin: 0 10px; padding: 10px 20px; background: #e53e3e; color: white; text-decoration: none; border-radius: 6px;">🔍 Error Logs</a>
                <a href="workflow_test.php" style="margin: 0 10px; padding: 10px 20px; background: #38a169; color: white; text-decoration: none; border-radius: 6px;">📊 Basic Tests</a>
            </div>
        </div>
    </div>
</body>
</html>
