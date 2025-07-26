<?php
/**
 * Production Flow Process Tester
 * 
 * This script tests each critical file and workflow process in the deployed project
 * to identify any issues or failures in the production environment.
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Test results collection
$testResults = [];
$testStartTime = microtime(true);

function logTest($testName, $status, $message, $details = []) {
    global $testResults;
    $testResults[] = [
        'test' => $testName,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function testFile($filename, $description) {
    if (file_exists(__DIR__ . '/' . $filename)) {
        // Check for syntax errors
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg(__DIR__ . '/' . $filename) . " 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            logTest("File: $filename", 'pass', "$description - File exists and syntax OK");
            return true;
        } else {
            logTest("File: $filename", 'fail', "$description - Syntax error: " . implode(' ', $output));
            return false;
        }
    } else {
        logTest("File: $filename", 'fail', "$description - File not found");
        return false;
    }
}

function testDatabaseConnection() {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['test'] == 1) {
            logTest("Database Connection", 'pass', 'Database connection successful');
            return true;
        } else {
            logTest("Database Connection", 'fail', 'Database test query failed');
            return false;
        }
    } catch (Exception $e) {
        logTest("Database Connection", 'fail', 'Database connection failed: ' . $e->getMessage());
        return false;
    }
}

function testDatabaseTables() {
    global $conn;
    $tables = [
        'data_jamaah' => 'Pilgrim registration data',
        'data_paket' => 'Package information',
        'data_pembatalan' => 'Cancellation requests',
        'data_invoice' => 'Invoice records'
    ];
    
    $allTablesOk = true;
    foreach ($tables as $table => $description) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table LIMIT 1");
            $stmt->execute();
            $result = $stmt->fetch();
            logTest("Table: $table", 'pass', "$description - Table accessible, {$result['count']} records");
        } catch (Exception $e) {
            logTest("Table: $table", 'fail', "$description - Table error: " . $e->getMessage());
            $allTablesOk = false;
        }
    }
    return $allTablesOk;
}

function testFormWorkflow() {
    // Test form files
    $forms = [
        'form_haji.php' => 'Haji registration form',
        'form_umroh.php' => 'Umroh registration form',
        'form_pembatalan.php' => 'Cancellation form'
    ];
    
    $formsOk = true;
    foreach ($forms as $form => $description) {
        if (!testFile($form, $description)) {
            $formsOk = false;
        }
    }
    
    // Test processing scripts
    $processors = [
        'submit_haji.php' => 'Haji registration processor',
        'submit_umroh.php' => 'Umroh registration processor', 
        'submit_pembatalan.php' => 'Cancellation processor'
    ];
    
    foreach ($processors as $processor => $description) {
        if (!testFile($processor, $description)) {
            $formsOk = false;
        }
    }
    
    return $formsOk;
}

function testPaymentWorkflow() {
    $paymentFiles = [
        'invoice.php' => 'Invoice generation',
        'confirm_payment.php' => 'Payment confirmation processor',
        'closing_page.php' => 'Payment success page'
    ];
    
    $paymentOk = true;
    foreach ($paymentFiles as $file => $description) {
        if (!testFile($file, $description)) {
            $paymentOk = false;
        }
    }
    
    return $paymentOk;
}

function testAdminWorkflow() {
    $adminFiles = [
        'admin_dashboard.php' => 'Admin dashboard',
        'admin_pending.php' => 'Pending registrations management',
        'admin_kelengkapan.php' => 'Document completeness check',
        'admin_manifest.php' => 'Manifest generation',
        'admin_paket.php' => 'Package management',
        'admin_pembatalan.php' => 'Cancellation management'
    ];
    
    $adminOk = true;
    foreach ($adminFiles as $file => $description) {
        if (!testFile($file, $description)) {
            $adminOk = false;
        }
    }
    
    return $adminOk;
}

function testUploadSystem() {
    // Test upload handler
    if (!testFile('upload_handler.php', 'Upload handler')) {
        return false;
    }
    
    // Test file manager
    if (!testFile('heroku_file_manager.php', 'Heroku file manager')) {
        return false;
    }
    
    // Test upload functionality
    try {
        require_once 'upload_handler.php';
        $uploadHandler = new UploadHandler();
        
        $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PKG001');
        
        if (strpos($testFilename, '1234567890123456') !== false) {
            logTest("Upload System", 'pass', 'Upload handler working, test filename: ' . $testFilename);
            return true;
        } else {
            logTest("Upload System", 'fail', 'Upload handler filename generation failed');
            return false;
        }
    } catch (Exception $e) {
        logTest("Upload System", 'fail', 'Upload system error: ' . $e->getMessage());
        return false;
    }
}

function testEmailSystem() {
    if (!testFile('email_functions.php', 'Email functions')) {
        return false;
    }
    
    try {
        require_once 'email_functions.php';
        
        $emailConfigured = defined('SMTP_HOST') && defined('SMTP_PORT') && 
                          defined('SMTP_USERNAME') && defined('SMTP_PASSWORD');
        
        if ($emailConfigured) {
            logTest("Email System", 'pass', 'Email system configured properly');
            return true;
        } else {
            logTest("Email System", 'warning', 'Email system configuration incomplete');
            return false;
        }
    } catch (Exception $e) {
        logTest("Email System", 'fail', 'Email system error: ' . $e->getMessage());
        return false;
    }
}

function testSessionSystem() {
    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $testKey = 'flow_test_' . time();
        $testValue = 'test_value_' . rand(1000, 9999);
        
        $_SESSION[$testKey] = $testValue;
        
        if (isset($_SESSION[$testKey]) && $_SESSION[$testKey] === $testValue) {
            unset($_SESSION[$testKey]);
            logTest("Session System", 'pass', 'Session system working properly');
            return true;
        } else {
            logTest("Session System", 'fail', 'Session system not working');
            return false;
        }
    } catch (Exception $e) {
        logTest("Session System", 'fail', 'Session system error: ' . $e->getMessage());
        return false;
    }
}

function testCriticalComponents() {
    $components = [
        'config.php' => 'Main configuration',
        'terbilang.php' => 'Number to text converter',
        'paket_functions.php' => 'Package utility functions'
    ];
    
    $componentsOk = true;
    foreach ($components as $component => $description) {
        if (!testFile($component, $description)) {
            $componentsOk = false;
        }
    }
    
    return $componentsOk;
}

function testErrorHandling() {
    // Check if error_viewer.php exists for production debugging
    if (file_exists(__DIR__ . '/error_viewer.php')) {
        logTest("Error Handling", 'pass', 'Error viewer available for production debugging');
        
        // Check error logs directory
        $errorLogDir = __DIR__ . '/error_logs';
        if (is_dir($errorLogDir) && is_writable($errorLogDir)) {
            logTest("Error Logging", 'pass', 'Error logging directory accessible');
        } else {
            logTest("Error Logging", 'warning', 'Error logging directory may not be writable');
        }
        
        return true;
    } else {
        logTest("Error Handling", 'fail', 'Error viewer not available');
        return false;
    }
}

function testSecurityMeasures() {
    $securityOk = true;
    
    // Check for .htaccess protection
    $uploadsDir = __DIR__ . '/uploads';
    if (file_exists($uploadsDir . '/.htaccess')) {
        logTest("Security", 'pass', 'Upload directory protected with .htaccess');
    } else {
        logTest("Security", 'warning', 'Upload directory may not be protected');
        $securityOk = false;
    }
    
    // Check session security settings
    $sessionSecure = ini_get('session.cookie_secure') || !isset($_ENV['DYNO']);
    $sessionHttpOnly = ini_get('session.cookie_httponly');
    
    if ($sessionSecure && $sessionHttpOnly) {
        logTest("Session Security", 'pass', 'Session security properly configured');
    } else {
        logTest("Session Security", 'warning', 'Session security could be improved');
        $securityOk = false;
    }
    
    return $securityOk;
}

function performSpecificFileTests() {
    // Test confirm_payment.php specifically for issues
    if (file_exists(__DIR__ . '/confirm_payment.php')) {
        $content = file_get_contents(__DIR__ . '/confirm_payment.php');
        
        // Check for enhanced error logging
        if (strpos($content, 'logDetailedError') !== false) {
            logTest("confirm_payment.php", 'pass', 'Enhanced error logging implemented');
        } else {
            logTest("confirm_payment.php", 'warning', 'Enhanced error logging may be missing');
        }
        
        // Check for proper exception handling
        if (strpos($content, 'try {') !== false && strpos($content, 'catch') !== false) {
            logTest("confirm_payment.php Exception Handling", 'pass', 'Exception handling implemented');
        } else {
            logTest("confirm_payment.php Exception Handling", 'fail', 'Exception handling missing');
        }
    }
    
    // Test upload_handler.php
    if (file_exists(__DIR__ . '/upload_handler.php')) {
        $content = file_get_contents(__DIR__ . '/upload_handler.php');
        
        // Check if it's not empty (was an issue before)
        if (strlen(trim($content)) > 100) {
            logTest("upload_handler.php", 'pass', 'Upload handler has content');
        } else {
            logTest("upload_handler.php", 'fail', 'Upload handler appears empty or minimal');
        }
    }
}

// Run all tests
echo "<!DOCTYPE html><html><head><title>MIW Production Flow Test Results</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
.container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
.header { background: #dc3545; color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 8px 8px 0 0; }
.test-result { padding: 10px; margin: 5px 0; border-radius: 5px; display: flex; justify-content: space-between; }
.pass { background: #d4edda; border-left: 4px solid #28a745; }
.fail { background: #f8d7da; border-left: 4px solid #dc3545; }
.warning { background: #fff3cd; border-left: 4px solid #ffc107; }
.status { padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: bold; }
.status-pass { background: #28a745; color: white; }
.status-fail { background: #dc3545; color: white; }
.status-warning { background: #ffc107; color: black; }
.summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
.summary-card { padding: 15px; border-radius: 8px; text-align: center; color: white; font-weight: bold; }
.card-total { background: #6c757d; }
.card-pass { background: #28a745; }
.card-fail { background: #dc3545; }
.card-warning { background: #ffc107; color: black; }
</style></head><body>";

echo "<div class='container'>";
echo "<div class='header'>";
echo "<h1>🔍 MIW Production Flow Process Test</h1>";
echo "<p>Testing each file and workflow process in the deployed project</p>";
echo "<p><strong>Environment:</strong> " . (isset($_ENV['DYNO']) ? 'Production (Heroku)' : 'Development') . "</p>";
echo "</div>";

// Run all tests
echo "<h2>🚀 Running Tests...</h2>";

logTest("Test Session Start", 'info', 'Starting comprehensive flow process tests');

testDatabaseConnection();
testDatabaseTables();
testFormWorkflow();
testPaymentWorkflow();
testAdminWorkflow();
testUploadSystem();
testEmailSystem();
testSessionSystem();
testCriticalComponents();
testErrorHandling();
testSecurityMeasures();
performSpecificFileTests();

// Calculate summary
$totalTests = count($testResults);
$passedTests = count(array_filter($testResults, function($r) { return $r['status'] === 'pass'; }));
$failedTests = count(array_filter($testResults, function($r) { return $r['status'] === 'fail'; }));
$warningTests = count(array_filter($testResults, function($r) { return $r['status'] === 'warning'; }));

// Display summary
echo "<div class='summary'>";
echo "<div class='summary-card card-total'><h3>$totalTests</h3><p>Total Tests</p></div>";
echo "<div class='summary-card card-pass'><h3>$passedTests</h3><p>Passed</p></div>";
echo "<div class='summary-card card-fail'><h3>$failedTests</h3><p>Failed</p></div>";
echo "<div class='summary-card card-warning'><h3>$warningTests</h3><p>Warnings</p></div>";
echo "</div>";

// Display detailed results
echo "<h2>📋 Detailed Test Results</h2>";
foreach ($testResults as $result) {
    $statusClass = $result['status'];
    echo "<div class='test-result $statusClass'>";
    echo "<div>";
    echo "<strong>{$result['test']}</strong><br>";
    echo "<small>{$result['message']}</small>";
    if (!empty($result['details'])) {
        echo "<br><small style='color: #666;'>Details: " . json_encode($result['details']) . "</small>";
    }
    echo "</div>";
    echo "<span class='status status-{$result['status']}'>{$result['status']}</span>";
    echo "</div>";
}

$executionTime = round((microtime(true) - $testStartTime) * 1000, 2);

// Issues summary
if ($failedTests > 0) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc3545;'>";
    echo "<h3>🚨 Issues Found</h3>";
    echo "<p><strong>$failedTests critical issue(s) detected that need immediate attention:</strong></p>";
    echo "<ul>";
    foreach ($testResults as $result) {
        if ($result['status'] === 'fail') {
            echo "<li><strong>{$result['test']}:</strong> {$result['message']}</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

if ($warningTests > 0) {
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;'>";
    echo "<h3>⚠️ Warnings</h3>";
    echo "<p><strong>$warningTests warning(s) that should be reviewed:</strong></p>";
    echo "<ul>";
    foreach ($testResults as $result) {
        if ($result['status'] === 'warning') {
            echo "<li><strong>{$result['test']}:</strong> {$result['message']}</li>";
        }
    }
    echo "</ul>";
    echo "</div>";
}

if ($failedTests === 0 && $warningTests === 0) {
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #28a745;'>";
    echo "<h3>✅ All Tests Passed!</h3>";
    echo "<p>The MIW system appears to be running smoothly with no critical issues detected.</p>";
    echo "</div>";
}

echo "<div style='text-align: center; margin-top: 30px; color: #666;'>";
echo "<p>Test completed in {$executionTime}ms | " . date('Y-m-d H:i:s T') . "</p>";
echo "<p><a href='comprehensive_test_suite.php'>🧪 Run Comprehensive Test Suite</a> | ";
echo "<a href='error_viewer.php'>🔍 View Error Logs</a></p>";
echo "</div>";

echo "</div></body></html>";
?>
