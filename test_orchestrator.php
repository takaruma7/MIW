<?php
/**
 * Comprehensive Testing Orchestrator for MIW Travel Management System
 * 
 * This orchestrator runs both White Box and Black Box testing suites
 * and provides a unified dashboard for test results analysis.
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

class TestOrchestrator {
    private $conn;
    private $testResults = [];
    private $logFile;
    private $startTime;
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->startTime = microtime(true);
        $this->logFile = __DIR__ . '/test_logs/orchestrator_' . date('Y-m-d_H-i-s') . '.log';
        $this->log("Test Orchestrator Started");
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
    
    /**
     * Run White Box Tests
     */
    public function runWhiteBoxTests() {
        $this->log("Starting White Box Test Suite");
        
        try {
            // Include the white box tester
            require_once __DIR__ . '/test_suite_whitebox.php';
            
            if (class_exists('WhiteBoxTester')) {
                $whiteBoxTester = new WhiteBoxTester($this->conn);
                $results = $whiteBoxTester->runAllTests();
                
                $this->testResults['whitebox'] = $results;
                $this->log("White Box Tests Completed", $results['summary']);
                
                return $results;
            } else {
                throw new Exception("WhiteBoxTester class not found");
            }
        } catch (Exception $e) {
            $errorResult = [
                'summary' => [
                    'total_tests' => 0,
                    'passed' => 0,
                    'failed' => 1,
                    'success_rate' => 0,
                    'error' => $e->getMessage()
                ],
                'details' => []
            ];
            
            $this->testResults['whitebox'] = $errorResult;
            $this->log("White Box Tests Failed", ['error' => $e->getMessage()]);
            
            return $errorResult;
        }
    }
    
    /**
     * Run Black Box Tests
     */
    public function runBlackBoxTests() {
        $this->log("Starting Black Box Test Suite");
        
        try {
            // Include the black box tester
            require_once __DIR__ . '/test_suite_blackbox.php';
            
            if (class_exists('BlackBoxTester')) {
                $blackBoxTester = new BlackBoxTester($this->conn);
                $results = $blackBoxTester->runAllTests();
                
                $this->testResults['blackbox'] = $results;
                $this->log("Black Box Tests Completed", $results['summary']);
                
                return $results;
            } else {
                throw new Exception("BlackBoxTester class not found");
            }
        } catch (Exception $e) {
            $errorResult = [
                'summary' => [
                    'total_tests' => 0,
                    'passed' => 0,
                    'failed' => 1,
                    'success_rate' => 0,
                    'error' => $e->getMessage()
                ],
                'details' => []
            ];
            
            $this->testResults['blackbox'] = $errorResult;
            $this->log("Black Box Tests Failed", ['error' => $e->getMessage()]);
            
            return $errorResult;
        }
    }
    
    /**
     * Run Production Health Check
     */
    public function runProductionHealthCheck() {
        $this->log("Starting Production Health Check");
        
        $healthChecks = [];
        
        // Check 1: Database connectivity
        try {
            $stmt = $this->conn->prepare("SELECT 1 as health_check");
            $stmt->execute();
            $healthChecks['database'] = [
                'status' => 'healthy',
                'message' => 'Database connection active',
                'response_time' => microtime(true)
            ];
        } catch (Exception $e) {
            $healthChecks['database'] = [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage(),
                'response_time' => null
            ];
        }
        
        // Check 2: Critical files
        $criticalFiles = [
            'config.php',
            'upload_handler.php',
            'confirm_payment.php',
            'admin_dashboard.php',
            'form_umroh.php',
            'form_haji.php'
        ];
        
        $missingFiles = [];
        foreach ($criticalFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missingFiles[] = $file;
            }
        }
        
        $healthChecks['critical_files'] = [
            'status' => empty($missingFiles) ? 'healthy' : 'unhealthy',
            'message' => empty($missingFiles) ? 'All critical files present' : 'Missing files: ' . implode(', ', $missingFiles),
            'missing_files' => $missingFiles,
            'total_files' => count($criticalFiles)
        ];
        
        // Check 3: Error log directory
        $errorLogDir = __DIR__ . '/error_logs';
        $healthChecks['error_logging'] = [
            'status' => (file_exists($errorLogDir) && is_writable($errorLogDir)) ? 'healthy' : 'warning',
            'message' => is_writable($errorLogDir) ? 'Error logging functional' : 'Error log directory issues',
            'directory' => $errorLogDir,
            'writable' => is_writable($errorLogDir)
        ];
        
        // Check 4: PHP configuration
        $phpConfig = [
            'version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time')
        ];
        
        $healthChecks['php_config'] = [
            'status' => 'healthy',
            'message' => 'PHP configuration loaded',
            'config' => $phpConfig
        ];
        
        // Check 5: Environment detection
        $environment = [
            'is_production' => isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
        ];
        
        $healthChecks['environment'] = [
            'status' => 'healthy',
            'message' => 'Environment detected successfully',
            'details' => $environment
        ];
        
        $this->testResults['health_check'] = $healthChecks;
        $this->log("Production Health Check Completed", $healthChecks);
        
        return $healthChecks;
    }
    
    /**
     * Generate comprehensive test report
     */
    public function generateComprehensiveReport() {
        $this->log("Generating Comprehensive Test Report");
        
        $whiteBoxResults = $this->testResults['whitebox'] ?? null;
        $blackBoxResults = $this->testResults['blackbox'] ?? null;
        $healthCheck = $this->testResults['health_check'] ?? null;
        
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        
        if ($whiteBoxResults) {
            $totalTests += $whiteBoxResults['summary']['total_tests'];
            $totalPassed += $whiteBoxResults['summary']['passed'];
            $totalFailed += $whiteBoxResults['summary']['failed'];
        }
        
        if ($blackBoxResults) {
            $totalTests += $blackBoxResults['summary']['total_tests'];
            $totalPassed += $blackBoxResults['summary']['passed'];
            $totalFailed += $blackBoxResults['summary']['failed'];
        }
        
        $overallSuccessRate = $totalTests > 0 ? round(($totalPassed / $totalTests) * 100, 2) : 0;
        $executionTime = round((microtime(true) - $this->startTime) * 1000, 2);
        
        return [
            'overview' => [
                'total_tests' => $totalTests,
                'total_passed' => $totalPassed,
                'total_failed' => $totalFailed,
                'overall_success_rate' => $overallSuccessRate,
                'execution_time_ms' => $executionTime,
                'timestamp' => date('Y-m-d H:i:s T')
            ],
            'whitebox' => $whiteBoxResults,
            'blackbox' => $blackBoxResults,
            'health_check' => $healthCheck,
            'recommendations' => $this->generateRecommendations(),
            'log_file' => $this->logFile
        ];
    }
    
    /**
     * Generate recommendations based on test results
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        $whiteBoxResults = $this->testResults['whitebox'] ?? null;
        $blackBoxResults = $this->testResults['blackbox'] ?? null;
        $healthCheck = $this->testResults['health_check'] ?? null;
        
        // White box recommendations
        if ($whiteBoxResults && $whiteBoxResults['summary']['failed'] > 0) {
            $recommendations[] = [
                'category' => 'Code Quality',
                'priority' => 'high',
                'message' => 'White box tests found ' . $whiteBoxResults['summary']['failed'] . ' issues in internal code logic',
                'action' => 'Review and fix internal code structure, control flow, and data handling'
            ];
        }
        
        // Black box recommendations
        if ($blackBoxResults && $blackBoxResults['summary']['failed'] > 0) {
            $recommendations[] = [
                'category' => 'User Experience',
                'priority' => 'high',
                'message' => 'Black box tests found ' . $blackBoxResults['summary']['failed'] . ' issues in user-facing functionality',
                'action' => 'Review and fix user interface, workflows, and external integrations'
            ];
        }
        
        // Health check recommendations
        if ($healthCheck) {
            foreach ($healthCheck as $check => $result) {
                if ($result['status'] === 'unhealthy') {
                    $recommendations[] = [
                        'category' => 'System Health',
                        'priority' => 'critical',
                        'message' => "Health check failed for $check: " . $result['message'],
                        'action' => 'Immediate attention required for system stability'
                    ];
                } elseif ($result['status'] === 'warning') {
                    $recommendations[] = [
                        'category' => 'System Health',
                        'priority' => 'medium',
                        'message' => "Health check warning for $check: " . $result['message'],
                        'action' => 'Monitor and consider improvements'
                    ];
                }
            }
        }
        
        // Performance recommendations
        $executionTime = microtime(true) - $this->startTime;
        if ($executionTime > 30) {
            $recommendations[] = [
                'category' => 'Performance',
                'priority' => 'medium',
                'message' => 'Test execution took longer than expected (' . round($executionTime, 2) . ' seconds)',
                'action' => 'Consider optimizing database queries and code efficiency'
            ];
        }
        
        // Success recommendations
        if (empty($recommendations)) {
            $recommendations[] = [
                'category' => 'System Status',
                'priority' => 'info',
                'message' => 'All tests passed successfully! System is functioning well.',
                'action' => 'Continue monitoring and regular testing to maintain quality'
            ];
        }
        
        return $recommendations;
    }
    
    /**
     * Run all test suites
     */
    public function runAllTests() {
        $this->log("Starting Complete Test Suite Execution");
        
        // Run health check first
        $this->runProductionHealthCheck();
        
        // Run white box tests
        $this->runWhiteBoxTests();
        
        // Run black box tests
        $this->runBlackBoxTests();
        
        // Generate comprehensive report
        $report = $this->generateComprehensiveReport();
        
        $this->log("Complete Test Suite Execution Finished", $report['overview']);
        
        return $report;
    }
}

// Initialize and run tests
$orchestrator = new TestOrchestrator($conn);
$testReport = $orchestrator->runAllTests();

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
        .test-results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin: 20px 0; }
        .test-suite { background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 5px solid #667eea; }
        .health-check-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; }
        .health-item { background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745; }
        .health-item.warning { border-left-color: #ffc107; }
        .health-item.danger { border-left-color: #dc3545; }
        .recommendations { background: #e3f2fd; padding: 20px; border-radius: 10px; border-left: 5px solid #2196f3; }
        .recommendation-item { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .priority-critical { border-left: 5px solid #dc3545; }
        .priority-high { border-left: 5px solid #ff6b6b; }
        .priority-medium { border-left: 5px solid #ffc107; }
        .priority-info { border-left: 5px solid #28a745; }
        .progress-circle { width: 120px; height: 120px; border-radius: 50%; margin: 20px auto; position: relative; }
        .progress-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.2em; font-weight: bold; }
        .action-buttons { display: flex; gap: 15px; margin: 20px 0; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
        .btn-primary { background: #667eea; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .timestamp { color: #666; font-size: 0.9em; }
        .execution-time { color: #28a745; font-weight: bold; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>🧪 Comprehensive Testing Dashboard</h1>
            <p><strong>MIW Travel Management System</strong> - Production Quality Assurance</p>
            <p class="timestamp">Test executed at <?= $testReport['overview']['timestamp'] ?></p>
            <p class="execution-time">Total execution time: <?= $testReport['overview']['execution_time_ms'] ?>ms</p>
            
            <div class="overview-grid">
                <div class="metric-card <?= $testReport['overview']['total_failed'] == 0 ? 'success' : 'danger' ?>">
                    <div class="metric-number"><?= $testReport['overview']['total_tests'] ?></div>
                    <div class="metric-label">Total Tests</div>
                </div>
                <div class="metric-card success">
                    <div class="metric-number"><?= $testReport['overview']['total_passed'] ?></div>
                    <div class="metric-label">Passed</div>
                </div>
                <div class="metric-card <?= $testReport['overview']['total_failed'] == 0 ? 'success' : 'danger' ?>">
                    <div class="metric-number"><?= $testReport['overview']['total_failed'] ?></div>
                    <div class="metric-label">Failed</div>
                </div>
                <div class="metric-card <?= $testReport['overview']['overall_success_rate'] >= 90 ? 'success' : ($testReport['overview']['overall_success_rate'] >= 70 ? 'warning' : 'danger') ?>">
                    <div class="metric-number"><?= $testReport['overview']['overall_success_rate'] ?>%</div>
                    <div class="metric-label">Success Rate</div>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>🔬 Testing Suite Results</h2>
            
            <div class="test-results-grid">
                <div class="test-suite">
                    <h3>🔍 White Box Testing</h3>
                    <p><strong>Focus:</strong> Internal code structure and logic</p>
                    <?php if ($testReport['whitebox']): ?>
                        <p>✅ Tests Passed: <strong><?= $testReport['whitebox']['summary']['passed'] ?></strong></p>
                        <p>❌ Tests Failed: <strong><?= $testReport['whitebox']['summary']['failed'] ?></strong></p>
                        <p>📊 Success Rate: <strong><?= $testReport['whitebox']['summary']['success_rate'] ?>%</strong></p>
                        <a href="test_suite_whitebox.php" class="btn btn-primary">View Detailed Results</a>
                    <?php else: ?>
                        <p style="color: #dc3545;">⚠️ White box tests could not be executed</p>
                    <?php endif; ?>
                </div>
                
                <div class="test-suite">
                    <h3>🎯 Black Box Testing</h3>
                    <p><strong>Focus:</strong> External functionality and user experience</p>
                    <?php if ($testReport['blackbox']): ?>
                        <p>✅ Tests Passed: <strong><?= $testReport['blackbox']['summary']['passed'] ?></strong></p>
                        <p>❌ Tests Failed: <strong><?= $testReport['blackbox']['summary']['failed'] ?></strong></p>
                        <p>📊 Success Rate: <strong><?= $testReport['blackbox']['summary']['success_rate'] ?>%</strong></p>
                        <a href="test_suite_blackbox.php" class="btn btn-primary">View Detailed Results</a>
                    <?php else: ?>
                        <p style="color: #dc3545;">⚠️ Black box tests could not be executed</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="test-section">
            <h2>🏥 System Health Check</h2>
            
            <div class="health-check-grid">
                <?php if ($testReport['health_check']): ?>
                    <?php foreach ($testReport['health_check'] as $checkName => $checkResult): ?>
                        <div class="health-item <?= $checkResult['status'] === 'healthy' ? '' : ($checkResult['status'] === 'warning' ? 'warning' : 'danger') ?>">
                            <h4><?= ucwords(str_replace('_', ' ', $checkName)) ?></h4>
                            <p><strong>Status:</strong> <?= ucfirst($checkResult['status']) ?></p>
                            <p><?= htmlspecialchars($checkResult['message']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Health check data not available</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="test-section">
            <h2>💡 Recommendations</h2>
            
            <div class="recommendations">
                <?php if (!empty($testReport['recommendations'])): ?>
                    <?php foreach ($testReport['recommendations'] as $recommendation): ?>
                        <div class="recommendation-item priority-<?= $recommendation['priority'] ?>">
                            <h4><?= htmlspecialchars($recommendation['category']) ?> 
                                <span style="font-size: 0.8em; background: #<?= $recommendation['priority'] === 'critical' ? 'dc3545' : ($recommendation['priority'] === 'high' ? 'ff6b6b' : ($recommendation['priority'] === 'medium' ? 'ffc107' : '28a745')) ?>; color: white; padding: 2px 8px; border-radius: 4px;">
                                    <?= strtoupper($recommendation['priority']) ?>
                                </span>
                            </h4>
                            <p><strong>Issue:</strong> <?= htmlspecialchars($recommendation['message']) ?></p>
                            <p><strong>Action:</strong> <?= htmlspecialchars($recommendation['action']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="recommendation-item priority-info">
                        <h4>System Status ✅</h4>
                        <p>No critical issues found. System is operating normally.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="test-section">
            <h2>🔧 Actions & Tools</h2>
            
            <div class="action-buttons">
                <a href="test_suite_whitebox.php" class="btn btn-primary">🔬 Run White Box Tests</a>
                <a href="test_suite_blackbox.php" class="btn btn-info">🎯 Run Black Box Tests</a>
                <a href="error_viewer.php" class="btn btn-secondary">📋 View Error Logs</a>
                <a href="workflow_test.php" class="btn btn-success">⚡ Quick Workflow Test</a>
                <a href="test_logs/" class="btn btn-secondary">📁 Browse Test Logs</a>
                <button onclick="location.reload()" class="btn btn-primary">🔄 Refresh Dashboard</button>
            </div>
            
            <p><strong>Deployed Testing URLs:</strong></p>
            <ul>
                <li><a href="https://miw-travel-app-576ab80a8cab.herokuapp.com/test_orchestrator.php" target="_blank">🌐 Production Testing Dashboard</a></li>
                <li><a href="https://miw-travel-app-576ab80a8cab.herokuapp.com/test_suite_whitebox.php" target="_blank">🔬 Production White Box Tests</a></li>
                <li><a href="https://miw-travel-app-576ab80a8cab.herokuapp.com/test_suite_blackbox.php" target="_blank">🎯 Production Black Box Tests</a></li>
                <li><a href="https://miw-travel-app-576ab80a8cab.herokuapp.com/error_viewer.php" target="_blank">🔍 Production Error Viewer</a></li>
            </ul>
        </div>

        <div style="text-align: center; margin-top: 30px; color: rgba(255,255,255,0.8);">
            <p>Comprehensive Testing Suite v1.0.0 | MIW Travel Management System</p>
            <p>Log file: <?= basename($testReport['log_file']) ?></p>
        </div>
    </div>
</body>
</html>
