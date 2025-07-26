<?php
/**
 * Comprehensive Registration Flow Testing Suite
 * 
 * This script performs thorough white box and black box testing of the complete 
 * registration workflow from form submission to admin verification and email notification.
 * 
 * @version 1.0.0
 */

require_once 'config.php';
require_once 'email_functions.php';
require_once 'upload_handler.php';

// Set comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Test environment check
$isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
$environment = $isProduction ? 'PRODUCTION' : 'DEVELOPMENT';

// Test results storage
$testResults = [];
$criticalIssues = [];
$warnings = [];
$passedTests = 0;
$failedTests = 0;

// Helper function to log test results
function logTestResult($testName, $status, $message, $details = []) {
    global $testResults, $passedTests, $failedTests, $criticalIssues, $warnings;
    
    $testResults[] = [
        'test' => $testName,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($status === 'PASS') {
        $passedTests++;
    } elseif ($status === 'FAIL') {
        $failedTests++;
        $criticalIssues[] = "$testName: $message";
    } else {
        $warnings[] = "$testName: $message";
    }
}

// Test 1: White Box Testing - Database Schema and Tables
function testDatabaseSchema() {
    global $conn;
    
    try {
        // Test critical tables
        $criticalTables = [
            'data_jamaah' => 'Main registration table',
            'data_paket' => 'Package information table',
            'data_pembatalan' => 'Cancellation requests table',
            'data_invoice' => 'Invoice tracking table'
        ];
        
        $missingTables = [];
        $tableDetails = [];
        
        foreach ($criticalTables as $table => $description) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM $table LIMIT 1");
                $stmt->execute();
                $count = $stmt->fetchColumn();
                $tableDetails[$table] = "Accessible (rows: $count)";
            } catch (Exception $e) {
                $missingTables[] = $table;
                $tableDetails[$table] = "ERROR: " . $e->getMessage();
            }
        }
        
        if (empty($missingTables)) {
            logTestResult('Database Schema', 'PASS', 'All critical tables accessible', $tableDetails);
        } else {
            logTestResult('Database Schema', 'FAIL', 
                'Missing tables: ' . implode(', ', $missingTables), $tableDetails);
        }
        
        // Test table relationships
        try {
            $stmt = $conn->prepare("
                SELECT j.nik, j.nama, p.program_pilihan 
                FROM data_jamaah j 
                JOIN data_paket p ON j.pak_id = p.pak_id 
                LIMIT 1
            ");
            $stmt->execute();
            logTestResult('Table Relationships', 'PASS', 'Foreign key relationships working');
        } catch (Exception $e) {
            logTestResult('Table Relationships', 'FAIL', 'Foreign key relationship error: ' . $e->getMessage());
        }
        
    } catch (Exception $e) {
        logTestResult('Database Schema', 'FAIL', 'Database connection error: ' . $e->getMessage());
    }
}

// Test 2: White Box Testing - Form Processing Logic
function testFormProcessingLogic() {
    $formProcessors = [
        'submit_haji.php' => 'Haji registration processor',
        'submit_umroh.php' => 'Umroh registration processor',
        'submit_pembatalan.php' => 'Cancellation processor',
        'confirm_payment.php' => 'Payment confirmation processor'
    ];
    
    $processorDetails = [];
    $missingProcessors = [];
    
    foreach ($formProcessors as $file => $description) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $missingProcessors[] = $file;
            $processorDetails[$file] = "MISSING";
            continue;
        }
        
        // Check file content and basic syntax
        $content = file_get_contents(__DIR__ . '/' . $file);
        $analysis = [];
        
        // White box analysis
        $analysis['file_size'] = strlen($content) . ' bytes';
        $analysis['has_error_handling'] = (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) ? 'Yes' : 'No';
        $analysis['has_validation'] = (strpos($content, 'empty(') !== false || strpos($content, 'filter_var') !== false) ? 'Yes' : 'No';
        $analysis['has_database_transaction'] = (strpos($content, 'beginTransaction') !== false) ? 'Yes' : 'No';
        $analysis['has_email_integration'] = (strpos($content, 'email_functions') !== false) ? 'Yes' : 'No';
        $analysis['has_file_upload'] = (strpos($content, 'upload_handler') !== false || strpos($content, '$_FILES') !== false) ? 'Yes' : 'No';
        
        $processorDetails[$file] = $analysis;
    }
    
    if (empty($missingProcessors)) {
        logTestResult('Form Processors', 'PASS', 'All form processors present and analyzed', $processorDetails);
    } else {
        logTestResult('Form Processors', 'FAIL', 
            'Missing processors: ' . implode(', ', $missingProcessors), $processorDetails);
    }
}

// Test 3: White Box Testing - Email System Components
function testEmailSystemComponents() {
    try {
        require_once 'email_functions.php';
        
        $emailAnalysis = [];
        
        // Check email configuration
        $emailAnalysis['email_enabled'] = defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'Yes' : 'No') : 'Not defined';
        $emailAnalysis['smtp_configured'] = defined('SMTP_HOST') && defined('SMTP_USERNAME') ? 'Yes' : 'No';
        $emailAnalysis['from_address'] = defined('EMAIL_FROM') ? EMAIL_FROM : 'Not defined';
        $emailAnalysis['admin_email'] = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'Not defined';
        
        // Test email functions existence
        $emailFunctions = [
            'sendRegistrationEmail',
            'sendConfirmationEmail',
            'sendPaymentConfirmationEmail',
            'sendPaymentVerificationEmail',
            'sendPaymentRejectionEmail',
            'sendCancellationEmail'
        ];
        
        $missingFunctions = [];
        foreach ($emailFunctions as $function) {
            if (!function_exists($function)) {
                $missingFunctions[] = $function;
            }
        }
        
        $emailAnalysis['missing_functions'] = empty($missingFunctions) ? 'None' : implode(', ', $missingFunctions);
        
        if (empty($missingFunctions) && $emailAnalysis['smtp_configured'] === 'Yes') {
            logTestResult('Email System', 'PASS', 'Email system fully configured', $emailAnalysis);
        } else {
            logTestResult('Email System', 'WARNING', 'Email system partially configured', $emailAnalysis);
        }
        
    } catch (Exception $e) {
        logTestResult('Email System', 'FAIL', 'Email system error: ' . $e->getMessage());
    }
}

// Test 4: White Box Testing - Upload Handler System
function testUploadHandlerSystem() {
    try {
        require_once 'upload_handler.php';
        
        $uploadHandler = new UploadHandler();
        $uploadAnalysis = [];
        
        // Test filename generation
        $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PKG001');
        $uploadAnalysis['filename_generation'] = !empty($testFilename) ? 'Working' : 'Failed';
        
        // Check upload directories
        $uploadDirs = ['/uploads', '/uploads/documents', '/uploads/payments'];
        $accessibleDirs = [];
        $inaccessibleDirs = [];
        
        foreach ($uploadDirs as $dir) {
            $fullPath = __DIR__ . $dir;
            if (is_dir($fullPath)) {
                $accessibleDirs[] = $dir;
            } else {
                $inaccessibleDirs[] = $dir;
            }
        }
        
        $uploadAnalysis['accessible_dirs'] = implode(', ', $accessibleDirs);
        $uploadAnalysis['inaccessible_dirs'] = implode(', ', $inaccessibleDirs);
        $uploadAnalysis['max_file_size'] = ini_get('upload_max_filesize');
        $uploadAnalysis['post_max_size'] = ini_get('post_max_size');
        
        if ($uploadAnalysis['filename_generation'] === 'Working') {
            logTestResult('Upload Handler', 'PASS', 'Upload handler functioning', $uploadAnalysis);
        } else {
            logTestResult('Upload Handler', 'FAIL', 'Upload handler issues detected', $uploadAnalysis);
        }
        
    } catch (Exception $e) {
        logTestResult('Upload Handler', 'FAIL', 'Upload handler error: ' . $e->getMessage());
    }
}

// Test 5: Black Box Testing - Form Accessibility
function testFormAccessibility() {
    $forms = [
        'form_haji.php' => 'Haji Registration Form',
        'form_umroh.php' => 'Umroh Registration Form',
        'form_pembatalan.php' => 'Cancellation Form'
    ];
    
    $formAnalysis = [];
    $inaccessibleForms = [];
    
    foreach ($forms as $file => $description) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $inaccessibleForms[] = $file;
            $formAnalysis[$file] = 'MISSING';
            continue;
        }
        
        // Black box analysis - check form structure
        $content = file_get_contents(__DIR__ . '/' . $file);
        $analysis = [];
        
        $analysis['has_form_tag'] = (strpos($content, '<form') !== false) ? 'Yes' : 'No';
        $analysis['has_file_upload'] = (strpos($content, 'enctype="multipart/form-data"') !== false) ? 'Yes' : 'No';
        $analysis['has_required_fields'] = (strpos($content, 'required') !== false) ? 'Yes' : 'No';
        $analysis['has_javascript'] = (strpos($content, '<script') !== false) ? 'Yes' : 'No';
        $analysis['form_method'] = preg_match('/method=["\']?(POST|GET)["\']?/i', $content, $matches) ? 
            $matches[1] : 'Not specified';
        
        $formAnalysis[$file] = $analysis;
    }
    
    if (empty($inaccessibleForms)) {
        logTestResult('Form Accessibility', 'PASS', 'All registration forms accessible', $formAnalysis);
    } else {
        logTestResult('Form Accessibility', 'FAIL', 
            'Inaccessible forms: ' . implode(', ', $inaccessibleForms), $formAnalysis);
    }
}

// Test 6: Black Box Testing - Admin Interface
function testAdminInterface() {
    $adminPages = [
        'admin_dashboard.php' => 'Main admin dashboard',
        'admin_pending.php' => 'Pending registrations',
        'admin_paket.php' => 'Package management',
        'admin_pembatalan.php' => 'Cancellation management',
        'admin_manifest.php' => 'Manifest generation'
    ];
    
    $adminAnalysis = [];
    $missingPages = [];
    
    foreach ($adminPages as $file => $description) {
        if (!file_exists(__DIR__ . '/' . $file)) {
            $missingPages[] = $file;
            $adminAnalysis[$file] = 'MISSING';
            continue;
        }
        
        // Black box analysis
        $content = file_get_contents(__DIR__ . '/' . $file);
        $analysis = [];
        
        $analysis['has_navigation'] = (strpos($content, 'admin_nav.php') !== false) ? 'Yes' : 'No';
        $analysis['has_bootstrap'] = (strpos($content, 'bootstrap') !== false) ? 'Yes' : 'No';
        $analysis['has_datatables'] = (strpos($content, 'DataTable') !== false || strpos($content, 'datatables') !== false) ? 'Yes' : 'No';
        $analysis['has_modals'] = (strpos($content, 'modal') !== false) ? 'Yes' : 'No';
        
        $adminAnalysis[$file] = $analysis;
    }
    
    if (empty($missingPages)) {
        logTestResult('Admin Interface', 'PASS', 'All admin pages accessible', $adminAnalysis);
    } else {
        logTestResult('Admin Interface', 'FAIL', 
            'Missing admin pages: ' . implode(', ', $missingPages), $adminAnalysis);
    }
}

// Test 7: Integration Testing - Full Registration Workflow
function testRegistrationWorkflow() {
    global $conn;
    
    $workflowSteps = [];
    
    try {
        // Step 1: Check if packages exist
        $stmt = $conn->prepare("SELECT COUNT(*) FROM data_paket WHERE pak_id > 0");
        $stmt->execute();
        $packageCount = $stmt->fetchColumn();
        $workflowSteps['packages_available'] = $packageCount > 0 ? "Yes ($packageCount packages)" : 'No packages found';
        
        // Step 2: Test form submission validation
        $workflowSteps['form_validation'] = 'Checking validation logic...';
        
        // Check if submit scripts have proper validation
        $validationChecks = [];
        
        foreach (['submit_haji.php', 'submit_umroh.php'] as $script) {
            if (file_exists(__DIR__ . '/' . $script)) {
                $content = file_get_contents(__DIR__ . '/' . $script);
                $hasValidation = (
                    strpos($content, 'empty($_POST') !== false ||
                    strpos($content, 'filter_var') !== false ||
                    strpos($content, 'preg_match') !== false
                );
                $validationChecks[$script] = $hasValidation ? 'Has validation' : 'Missing validation';
            }
        }
        $workflowSteps['validation_scripts'] = $validationChecks;
        
        // Step 3: Check admin pending system
        if (file_exists(__DIR__ . '/admin_pending.php')) {
            $content = file_get_contents(__DIR__ . '/admin_pending.php');
            $hasVerificationLogic = (
                strpos($content, 'verify_payment') !== false &&
                strpos($content, 'reject_payment') !== false
            );
            $workflowSteps['admin_verification'] = $hasVerificationLogic ? 'Available' : 'Missing logic';
        } else {
            $workflowSteps['admin_verification'] = 'admin_pending.php missing';
        }
        
        // Step 4: Check email notification system
        $workflowSteps['email_notifications'] = 'Testing email integration...';
        
        if (function_exists('sendRegistrationEmail') && function_exists('sendPaymentConfirmationEmail')) {
            $workflowSteps['email_notifications'] = 'Email functions available';
        } else {
            $workflowSteps['email_notifications'] = 'Email functions missing';
        }
        
        // Overall workflow assessment
        $criticalMissing = [];
        if ($packageCount === 0) $criticalMissing[] = 'No packages';
        if ($workflowSteps['admin_verification'] !== 'Available') $criticalMissing[] = 'Admin verification';
        if ($workflowSteps['email_notifications'] !== 'Email functions available') $criticalMissing[] = 'Email system';
        
        if (empty($criticalMissing)) {
            logTestResult('Registration Workflow', 'PASS', 'Complete workflow available', $workflowSteps);
        } else {
            logTestResult('Registration Workflow', 'FAIL', 
                'Critical missing: ' . implode(', ', $criticalMissing), $workflowSteps);
        }
        
    } catch (Exception $e) {
        logTestResult('Registration Workflow', 'FAIL', 'Workflow test error: ' . $e->getMessage());
    }
}

// Test 8: Security Testing
function testSecurityMeasures() {
    $securityChecks = [];
    
    // Check for SQL injection protection
    $submitFiles = ['submit_haji.php', 'submit_umroh.php', 'confirm_payment.php'];
    $sqlProtection = [];
    
    foreach ($submitFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            $hasPreparedStatements = (strpos($content, '->prepare(') !== false);
            $sqlProtection[$file] = $hasPreparedStatements ? 'Uses prepared statements' : 'Potential SQL injection risk';
        }
    }
    $securityChecks['sql_injection_protection'] = $sqlProtection;
    
    // Check for XSS protection
    $adminFiles = ['admin_pending.php', 'admin_dashboard.php'];
    $xssProtection = [];
    
    foreach ($adminFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            $hasEscaping = (strpos($content, 'htmlspecialchars') !== false);
            $xssProtection[$file] = $hasEscaping ? 'Has output escaping' : 'Potential XSS risk';
        }
    }
    $securityChecks['xss_protection'] = $xssProtection;
    
    // Check file upload security
    if (file_exists(__DIR__ . '/upload_handler.php')) {
        $content = file_get_contents(__DIR__ . '/upload_handler.php');
        $hasFileValidation = (
            strpos($content, 'mime') !== false ||
            strpos($content, 'extension') !== false ||
            strpos($content, 'size') !== false
        );
        $securityChecks['file_upload_security'] = $hasFileValidation ? 'Has validation' : 'Missing validation';
    }
    
    // Environment-specific security
    global $isProduction;
    if ($isProduction) {
        $securityChecks['display_errors'] = ini_get('display_errors') ? 'Enabled (Security Risk)' : 'Disabled (Good)';
    } else {
        $securityChecks['display_errors'] = 'Development environment';
    }
    
    $securityIssues = 0;
    foreach ($securityChecks as $check => $result) {
        if (is_array($result)) {
            foreach ($result as $item => $status) {
                if (strpos($status, 'risk') !== false || strpos($status, 'Missing') !== false) {
                    $securityIssues++;
                }
            }
        } elseif (strpos($result, 'Risk') !== false || strpos($result, 'Missing') !== false) {
            $securityIssues++;
        }
    }
    
    if ($securityIssues === 0) {
        logTestResult('Security Measures', 'PASS', 'No major security issues detected', $securityChecks);
    } else {
        logTestResult('Security Measures', 'WARNING', 
            "$securityIssues potential security issues found", $securityChecks);
    }
}

// Test 9: Performance Testing
function testPerformance() {
    $performanceMetrics = [];
    
    // Database performance
    $startTime = microtime(true);
    try {
        global $conn;
        $stmt = $conn->prepare("SELECT COUNT(*) FROM data_jamaah");
        $stmt->execute();
        $dbTime = microtime(true) - $startTime;
        $performanceMetrics['database_query_time'] = round($dbTime * 1000, 2) . 'ms';
    } catch (Exception $e) {
        $performanceMetrics['database_query_time'] = 'Error: ' . $e->getMessage();
    }
    
    // Memory usage
    $performanceMetrics['memory_usage'] = round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB';
    $performanceMetrics['memory_peak'] = round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB';
    
    // File system performance
    $startTime = microtime(true);
    $testFile = __DIR__ . '/temp_test_file.txt';
    file_put_contents($testFile, 'test');
    $writeTime = microtime(true) - $startTime;
    
    $startTime = microtime(true);
    $content = file_get_contents($testFile);
    $readTime = microtime(true) - $startTime;
    unlink($testFile);
    
    $performanceMetrics['file_write_time'] = round($writeTime * 1000, 2) . 'ms';
    $performanceMetrics['file_read_time'] = round($readTime * 1000, 2) . 'ms';
    
    // Performance assessment
    $performanceIssues = [];
    if (isset($performanceMetrics['database_query_time']) && 
        is_numeric(str_replace('ms', '', $performanceMetrics['database_query_time'])) &&
        floatval(str_replace('ms', '', $performanceMetrics['database_query_time'])) > 1000) {
        $performanceIssues[] = 'Slow database queries';
    }
    
    if (floatval(str_replace('MB', '', $performanceMetrics['memory_usage'])) > 128) {
        $performanceIssues[] = 'High memory usage';
    }
    
    if (empty($performanceIssues)) {
        logTestResult('Performance', 'PASS', 'Performance within acceptable limits', $performanceMetrics);
    } else {
        logTestResult('Performance', 'WARNING', 
            'Performance issues: ' . implode(', ', $performanceIssues), $performanceMetrics);
    }
}

// Run all tests
function runAllTests() {
    echo "<h2>Running Comprehensive Registration Flow Testing...</h2>";
    echo "<p><strong>Environment:</strong> " . ($GLOBALS['environment']) . "</p>";
    echo "<p><strong>Test Started:</strong> " . date('Y-m-d H:i:s') . "</p>";
    
    testDatabaseSchema();
    testFormProcessingLogic();
    testEmailSystemComponents();
    testUploadHandlerSystem();
    testFormAccessibility();
    testAdminInterface();
    testRegistrationWorkflow();
    testSecurityMeasures();
    testPerformance();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Registration Flow Testing Results</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 20px; background: #f8f9fa; line-height: 1.6;
        }
        .container { 
            max-width: 1400px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 12px; box-shadow: 0 0 25px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); 
            color: white; padding: 25px; margin: -30px -30px 30px; 
            border-radius: 12px 12px 0 0; text-align: center; 
        }
        .summary-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 20px; margin: 30px 0; 
        }
        .summary-card { 
            padding: 25px; border-radius: 10px; text-align: center; 
            color: white; font-weight: bold; box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .summary-pass { background: linear-gradient(135deg, #28a745, #20c997); }
        .summary-fail { background: linear-gradient(135deg, #dc3545, #fd7e14); }
        .summary-warning { background: linear-gradient(135deg, #ffc107, #fd7e14); color: black; }
        .test-result { 
            margin: 20px 0; padding: 20px; border-radius: 10px; 
            border-left: 5px solid; position: relative;
        }
        .test-pass { background: #d4edda; border-color: #28a745; }
        .test-fail { background: #f8d7da; border-color: #dc3545; }
        .test-warning { background: #fff3cd; border-color: #ffc107; }
        .test-header { 
            font-weight: bold; font-size: 18px; margin-bottom: 10px; 
            display: flex; align-items: center; gap: 10px;
        }
        .test-details { 
            background: rgba(0,0,0,0.05); padding: 15px; border-radius: 8px; 
            margin-top: 15px; font-family: 'Courier New', monospace; font-size: 14px;
        }
        .status-badge { 
            padding: 5px 10px; border-radius: 15px; font-size: 12px; 
            font-weight: bold; text-transform: uppercase;
        }
        .badge-pass { background: #28a745; color: white; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .navigation { 
            position: sticky; top: 20px; background: white; 
            padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .nav-link { 
            display: inline-block; margin: 5px 10px; padding: 8px 15px; 
            background: #007bff; color: white; text-decoration: none; 
            border-radius: 5px; font-size: 14px;
        }
        .nav-link:hover { background: #0056b3; }
        .collapsible-content { display: none; }
        .collapsible-button { 
            background: none; border: none; color: #007bff; 
            cursor: pointer; font-weight: bold; text-decoration: underline;
        }
        .test-icons { font-size: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 MIW Registration Flow Testing Suite</h1>
            <p>Comprehensive White Box & Black Box Testing Results</p>
            <p><strong>Environment:</strong> <?= $environment ?> | <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s T') ?></p>
        </div>

        <?php runAllTests(); ?>

        <!-- Summary -->
        <div class="summary-grid">
            <div class="summary-card summary-pass">
                <h2><?= $passedTests ?></h2>
                <p>Tests Passed</p>
            </div>
            <div class="summary-card summary-fail">
                <h2><?= $failedTests ?></h2>
                <p>Tests Failed</p>
            </div>
            <div class="summary-card summary-warning">
                <h2><?= count($warnings) ?></h2>
                <p>Warnings</p>
            </div>
            <div class="summary-card summary-pass">
                <h2><?= count($testResults) ?></h2>
                <p>Total Tests</p>
            </div>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <strong>Quick Navigation:</strong>
            <?php foreach ($testResults as $index => $result): ?>
                <a href="#test-<?= $index ?>" class="nav-link"><?= $result['test'] ?></a>
            <?php endforeach; ?>
        </div>

        <!-- Test Results -->
        <?php foreach ($testResults as $index => $result): ?>
            <div id="test-<?= $index ?>" class="test-result test-<?= strtolower($result['status']) ?>">
                <div class="test-header">
                    <span class="test-icons">
                        <?= $result['status'] === 'PASS' ? '✅' : ($result['status'] === 'FAIL' ? '❌' : '⚠️') ?>
                    </span>
                    <span><?= htmlspecialchars($result['test']) ?></span>
                    <span class="status-badge badge-<?= strtolower($result['status']) ?>">
                        <?= $result['status'] ?>
                    </span>
                </div>
                
                <div class="test-message">
                    <strong>Result:</strong> <?= htmlspecialchars($result['message']) ?>
                </div>
                
                <?php if (!empty($result['details'])): ?>
                    <button class="collapsible-button" onclick="toggleDetails(<?= $index ?>)">
                        Show Technical Details ▼
                    </button>
                    <div id="details-<?= $index ?>" class="test-details collapsible-content">
                        <pre><?= htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT)) ?></pre>
                    </div>
                <?php endif; ?>
                
                <div style="text-align: right; font-size: 12px; color: #666; margin-top: 10px;">
                    Tested at: <?= $result['timestamp'] ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Critical Issues Summary -->
        <?php if (!empty($criticalIssues)): ?>
            <div style="background: #f8d7da; padding: 20px; border-radius: 10px; border: 2px solid #dc3545; margin-top: 30px;">
                <h3 style="color: #721c24; margin-top: 0;">🚨 Critical Issues Requiring Immediate Attention</h3>
                <ul>
                    <?php foreach ($criticalIssues as $issue): ?>
                        <li style="color: #721c24;"><?= htmlspecialchars($issue) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Recommendations -->
        <div style="background: #e7f3ff; padding: 20px; border-radius: 10px; border-left: 5px solid #007bff; margin-top: 30px;">
            <h3 style="color: #004085; margin-top: 0;">💡 Testing Recommendations</h3>
            <ul style="color: #004085;">
                <li><strong>Run Functional Tests:</strong> Use the form testing scripts below to test actual registration flows</li>
                <li><strong>Test Email Delivery:</strong> Submit test registrations to verify email notifications work</li>
                <li><strong>Monitor Admin Interface:</strong> Check admin_pending.php for verification functionality</li>
                <li><strong>Performance Monitoring:</strong> Run these tests periodically to catch degradation</li>
                <li><strong>Security Audits:</strong> Address any security warnings found in the tests</li>
            </ul>
        </div>

        <!-- Testing Tools -->
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
            <h3>🛠️ Additional Testing Tools</h3>
            <p>
                <a href="form_haji.php" style="margin: 5px; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 6px;">🕋 Test Haji Registration</a>
                <a href="form_umroh.php" style="margin: 5px; padding: 12px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 6px;">🕌 Test Umroh Registration</a>
                <a href="admin_pending.php" style="margin: 5px; padding: 12px 20px; background: #ffc107; color: black; text-decoration: none; border-radius: 6px;">👨‍💼 Admin Verification</a>
                <a href="comprehensive_test_suite.php" style="margin: 5px; padding: 12px 20px; background: #6f42c1; color: white; text-decoration: none; border-radius: 6px;">🧪 Full Test Suite</a>
            </p>
        </div>

        <div style="text-align: center; margin-top: 20px; color: #666;">
            <p>Registration Flow Testing completed at <?= date('Y-m-d H:i:s T') ?></p>
            <p><small>MIW Travel Management System - Registration Flow Testing Suite v1.0</small></p>
        </div>
    </div>

    <script>
        function toggleDetails(index) {
            const details = document.getElementById('details-' + index);
            const button = details.previousElementSibling;
            
            if (details.style.display === 'none' || details.style.display === '') {
                details.style.display = 'block';
                button.innerHTML = 'Hide Technical Details ▲';
            } else {
                details.style.display = 'none';
                button.innerHTML = 'Show Technical Details ▼';
            }
        }

        // Auto-scroll to failed tests
        window.onload = function() {
            const failedTest = document.querySelector('.test-fail');
            if (failedTest) {
                failedTest.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        };
    </script>
</body>
</html>
