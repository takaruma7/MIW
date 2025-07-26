<?php
/**
 * MIW Project Issues Analysis and Summary
 * 
 * This script analyzes all test results and provides a comprehensive summary
 * of any issues found in the deployed MIW travel management system.
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check production environment
$isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
$environment = $isProduction ? 'PRODUCTION' : 'DEVELOPMENT';

// Issues collection
$criticalIssues = [];
$warnings = [];
$recommendations = [];
$workingComponents = [];

// Function to add issue
function addIssue($severity, $component, $issue, $impact, $solution = '') {
    global $criticalIssues, $warnings;
    
    $issueData = [
        'component' => $component,
        'issue' => $issue,
        'impact' => $impact,
        'solution' => $solution,
        'detected_at' => date('Y-m-d H:i:s')
    ];
    
    if ($severity === 'critical') {
        $criticalIssues[] = $issueData;
    } else {
        $warnings[] = $issueData;
    }
}

// Function to add working component
function addWorking($component, $status) {
    global $workingComponents;
    $workingComponents[] = [
        'component' => $component,
        'status' => $status,
        'verified_at' => date('Y-m-d H:i:s')
    ];
}

// 1. Test Database Connectivity and Schema
try {
    $stmt = $conn->prepare("SELECT 1 as test");
    $stmt->execute();
    addWorking('Database Connection', 'Connected and responsive');
    
    // Test critical tables
    $criticalTables = ['data_jamaah', 'data_paket', 'data_pembatalan', 'data_invoice'];
    $missingTables = [];
    
    foreach ($criticalTables as $table) {
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM $table LIMIT 1");
            $stmt->execute();
        } catch (Exception $e) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        addWorking('Database Schema', 'All critical tables accessible');
    } else {
        addIssue('critical', 'Database Schema', 
            'Missing tables: ' . implode(', ', $missingTables),
            'Core functionality will not work',
            'Check database migration and table creation scripts');
    }
    
} catch (Exception $e) {
    addIssue('critical', 'Database Connection', 
        'Cannot connect to database: ' . $e->getMessage(),
        'Entire system non-functional',
        'Check database configuration and connection parameters');
}

// 2. Test Critical File Existence and Syntax
$criticalFiles = [
    'config.php' => 'Main configuration',
    'upload_handler.php' => 'File upload system',
    'confirm_payment.php' => 'Payment processing',
    'form_haji.php' => 'Haji registration',
    'form_umroh.php' => 'Umroh registration',
    'admin_dashboard.php' => 'Admin interface',
    'email_functions.php' => 'Email system'
];

$missingFiles = [];
$syntaxErrors = [];

foreach ($criticalFiles as $file => $description) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missingFiles[] = "$file ($description)";
    } else {
        // Quick syntax check
        $content = file_get_contents(__DIR__ . '/' . $file);
        if (strlen(trim($content)) < 50) {
            $syntaxErrors[] = "$file appears empty or minimal";
        }
    }
}

if (empty($missingFiles) && empty($syntaxErrors)) {
    addWorking('Critical Files', 'All critical files present and valid');
} else {
    if (!empty($missingFiles)) {
        addIssue('critical', 'Missing Files', 
            'Missing: ' . implode(', ', $missingFiles),
            'Core functionality unavailable',
            'Ensure all files are properly deployed');
    }
    if (!empty($syntaxErrors)) {
        addIssue('critical', 'File Content', 
            implode(', ', $syntaxErrors),
            'Components may not function',
            'Check file deployment and content integrity');
    }
}

// 3. Test Upload System
try {
    require_once 'upload_handler.php';
    $uploadHandler = new UploadHandler();
    $testFilename = $uploadHandler->generateCustomFilename('test', 'payment', 'PKG001');
    
    if (strpos($testFilename, 'test') !== false) {
        addWorking('Upload System', 'Upload handler functioning');
    } else {
        addIssue('warning', 'Upload System', 
            'Upload handler filename generation issue',
            'File uploads may fail',
            'Check upload_handler.php implementation');
    }
    
    // Check upload directories
    $uploadDirs = ['/uploads', '/uploads/documents', '/uploads/payments'];
    $inaccessibleDirs = [];
    
    foreach ($uploadDirs as $dir) {
        if (!is_dir(__DIR__ . $dir) || !is_writable(__DIR__ . $dir)) {
            if (!$isProduction) { // Only flag as issue in development
                $inaccessibleDirs[] = $dir;
            }
        }
    }
    
    if (!empty($inaccessibleDirs) && !$isProduction) {
        addIssue('warning', 'Upload Directories', 
            'Directories not writable: ' . implode(', ', $inaccessibleDirs),
            'File uploads will fail in development',
            'Create directories and set proper permissions');
    } else if ($isProduction) {
        addWorking('Upload Directories', 'Using ephemeral storage (Heroku)');
    }
    
} catch (Exception $e) {
    addIssue('critical', 'Upload System', 
        'Upload handler error: ' . $e->getMessage(),
        'File uploads will fail',
        'Fix upload_handler.php implementation');
}

// 4. Test Email System
try {
    require_once 'email_functions.php';
    
    $emailConfigured = defined('SMTP_HOST') && defined('SMTP_PORT') && 
                      defined('SMTP_USERNAME') && defined('SMTP_PASSWORD');
    
    if ($emailConfigured) {
        $emailEnabled = defined('EMAIL_ENABLED') ? EMAIL_ENABLED : false;
        if ($emailEnabled) {
            addWorking('Email System', 'Configured and enabled');
        } else {
            addIssue('warning', 'Email System', 
                'Email system configured but disabled',
                'No email notifications will be sent',
                'Enable EMAIL_ENABLED in configuration');
        }
    } else {
        addIssue('warning', 'Email System', 
            'Email system not fully configured',
            'Email notifications may fail',
            'Complete SMTP configuration in config.php');
    }
    
} catch (Exception $e) {
    addIssue('critical', 'Email System', 
        'Email functions error: ' . $e->getMessage(),
        'Email functionality unavailable',
        'Fix email_functions.php');
}

// 5. Test Session System
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $testKey = 'system_test_' . time();
    $_SESSION[$testKey] = 'test_value';
    
    if (isset($_SESSION[$testKey])) {
        unset($_SESSION[$testKey]);
        addWorking('Session System', 'Session handling working');
    } else {
        addIssue('critical', 'Session System', 
            'Session storage not working',
            'User authentication and state management will fail',
            'Check PHP session configuration');
    }
    
} catch (Exception $e) {
    addIssue('critical', 'Session System', 
        'Session system error: ' . $e->getMessage(),
        'User sessions unavailable',
        'Fix PHP session configuration');
}

// 6. Test Security Measures
$securityIssues = [];

// Check error reporting in production
if ($isProduction && ini_get('display_errors')) {
    $securityIssues[] = 'display_errors enabled in production';
}

// Check session security
if (!ini_get('session.cookie_httponly')) {
    $securityIssues[] = 'session.cookie_httponly not enabled';
}

if (!empty($securityIssues)) {
    addIssue('warning', 'Security Configuration', 
        implode(', ', $securityIssues),
        'Potential security vulnerabilities',
        'Review security settings in php.ini and config');
} else {
    addWorking('Security Configuration', 'Basic security measures in place');
}

// 7. Check Error Logging
$errorLogDir = __DIR__ . '/error_logs';
if (is_dir($errorLogDir) && is_writable($errorLogDir)) {
    addWorking('Error Logging', 'Error logging directory accessible');
} else {
    addIssue('warning', 'Error Logging', 
        'Error logging directory not accessible',
        'Cannot track and debug issues',
        'Create error_logs directory with write permissions');
}

// 8. Check for Recent Errors in confirm_payment.php
$confirmPaymentLogs = glob($errorLogDir . '/confirm_payment_*.log');
if (!empty($confirmPaymentLogs)) {
    $recentErrors = [];
    foreach ($confirmPaymentLogs as $logFile) {
        if (filemtime($logFile) > (time() - 3600)) { // Last hour
            $content = file_get_contents($logFile);
            if (strpos($content, 'ERROR') !== false || strpos($content, 'FAIL') !== false) {
                $recentErrors[] = basename($logFile);
            }
        }
    }
    
    if (!empty($recentErrors)) {
        addIssue('critical', 'Payment Processing', 
            'Recent errors detected in confirm_payment.php',
            'Payment confirmations may be failing',
            'Check error logs and fix confirm_payment.php issues');
    }
}

// 9. Performance Check
$memoryUsage = memory_get_usage(true);
$memoryLimit = ini_get('memory_limit');
$memoryLimitBytes = php_size_to_bytes($memoryLimit);

if ($memoryUsage > ($memoryLimitBytes * 0.8)) {
    addIssue('warning', 'Memory Usage', 
        'High memory usage: ' . formatBytes($memoryUsage) . ' of ' . $memoryLimit,
        'May cause out of memory errors',
        'Optimize code or increase memory limit');
} else {
    addWorking('Memory Usage', 'Memory usage within normal limits');
}

// Helper functions
function php_size_to_bytes($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    }
    return round($size);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Generate recommendations based on issues
if ($isProduction) {
    $recommendations[] = 'Use error_viewer.php to monitor production errors';
    $recommendations[] = 'Set up regular database backups';
    $recommendations[] = 'Consider implementing cloud storage for file uploads';
}

if (!empty($criticalIssues)) {
    $recommendations[] = 'Address critical issues immediately - system may be non-functional';
}

if (!empty($warnings)) {
    $recommendations[] = 'Review warnings to prevent potential issues';
}

if (empty($criticalIssues) && empty($warnings)) {
    $recommendations[] = 'System appears healthy - continue monitoring';
    $recommendations[] = 'Run tests periodically to catch issues early';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>MIW Project - Issues Analysis Report</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 20px; background: #f8f9fa; 
        }
        .container { 
            max-width: 1200px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); 
            color: white; padding: 25px; margin: -30px -30px 30px; 
            border-radius: 10px 10px 0 0; text-align: center; 
        }
        .status-card { 
            padding: 20px; margin: 15px 0; border-radius: 8px; 
            border-left: 5px solid; 
        }
        .critical { 
            background: #f8d7da; border-color: #dc3545; 
            border-left-color: #dc3545; 
        }
        .warning { 
            background: #fff3cd; border-color: #ffc107; 
            border-left-color: #ffc107; 
        }
        .success { 
            background: #d4edda; border-color: #28a745; 
            border-left-color: #28a745; 
        }
        .info { 
            background: #d1ecf1; border-color: #17a2b8; 
            border-left-color: #17a2b8; 
        }
        .badge { 
            padding: 4px 8px; border-radius: 12px; font-size: 12px; 
            font-weight: bold; text-transform: uppercase; margin-left: 10px; 
        }
        .badge-critical { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .badge-success { background: #28a745; color: white; }
        .summary-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; margin: 20px 0; 
        }
        .summary-item { 
            padding: 20px; border-radius: 8px; text-align: center; 
            color: white; font-weight: bold; 
        }
        .summary-critical { background: #dc3545; }
        .summary-warning { background: #ffc107; color: black; }
        .summary-success { background: #28a745; }
        .summary-info { background: #17a2b8; }
        h3 { margin-top: 0; }
        .issue-details { margin: 10px 0; font-size: 14px; }
        .solution { 
            background: #e9ecef; padding: 10px; border-radius: 5px; 
            margin-top: 10px; font-style: italic; 
        }
        .recommendations { 
            background: #e7f3ff; padding: 20px; border-radius: 8px; 
            border-left: 5px solid #007bff; margin: 20px 0; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 MIW Project Issues Analysis</h1>
            <p>Comprehensive analysis of deployed system health and issues</p>
            <p><strong>Environment:</strong> <?= $environment ?> | <strong>Analyzed:</strong> <?= date('Y-m-d H:i:s T') ?></p>
        </div>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-item summary-critical">
                <h2><?= count($criticalIssues) ?></h2>
                <p>Critical Issues</p>
            </div>
            <div class="summary-item summary-warning">
                <h2><?= count($warnings) ?></h2>
                <p>Warnings</p>
            </div>
            <div class="summary-item summary-success">
                <h2><?= count($workingComponents) ?></h2>
                <p>Working Components</p>
            </div>
            <div class="summary-item summary-info">
                <h2><?= count($recommendations) ?></h2>
                <p>Recommendations</p>
            </div>
        </div>

        <!-- Overall Status -->
        <?php if (empty($criticalIssues) && empty($warnings)): ?>
        <div class="status-card success">
            <h3>✅ System Status: HEALTHY</h3>
            <p>No critical issues or warnings detected. The MIW system appears to be functioning properly.</p>
        </div>
        <?php elseif (!empty($criticalIssues)): ?>
        <div class="status-card critical">
            <h3>🚨 System Status: CRITICAL ISSUES DETECTED</h3>
            <p><strong><?= count($criticalIssues) ?> critical issue(s)</strong> require immediate attention. System functionality may be impaired.</p>
        </div>
        <?php else: ?>
        <div class="status-card warning">
            <h3>⚠️ System Status: WARNINGS DETECTED</h3>
            <p><strong><?= count($warnings) ?> warning(s)</strong> detected that should be reviewed to prevent potential issues.</p>
        </div>
        <?php endif; ?>

        <!-- Critical Issues -->
        <?php if (!empty($criticalIssues)): ?>
        <h2>🚨 Critical Issues Requiring Immediate Attention</h2>
        <?php foreach ($criticalIssues as $issue): ?>
        <div class="status-card critical">
            <h3><?= htmlspecialchars($issue['component']) ?> <span class="badge badge-critical">Critical</span></h3>
            <div class="issue-details">
                <strong>Issue:</strong> <?= htmlspecialchars($issue['issue']) ?><br>
                <strong>Impact:</strong> <?= htmlspecialchars($issue['impact']) ?>
            </div>
            <?php if (!empty($issue['solution'])): ?>
            <div class="solution">
                <strong>Solution:</strong> <?= htmlspecialchars($issue['solution']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Warnings -->
        <?php if (!empty($warnings)): ?>
        <h2>⚠️ Warnings and Potential Issues</h2>
        <?php foreach ($warnings as $warning): ?>
        <div class="status-card warning">
            <h3><?= htmlspecialchars($warning['component']) ?> <span class="badge badge-warning">Warning</span></h3>
            <div class="issue-details">
                <strong>Issue:</strong> <?= htmlspecialchars($warning['issue']) ?><br>
                <strong>Impact:</strong> <?= htmlspecialchars($warning['impact']) ?>
            </div>
            <?php if (!empty($warning['solution'])): ?>
            <div class="solution">
                <strong>Recommended Action:</strong> <?= htmlspecialchars($warning['solution']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Working Components -->
        <h2>✅ Working Components</h2>
        <?php foreach ($workingComponents as $component): ?>
        <div class="status-card success">
            <h3><?= htmlspecialchars($component['component']) ?> <span class="badge badge-success">Working</span></h3>
            <div class="issue-details">
                <strong>Status:</strong> <?= htmlspecialchars($component['status']) ?><br>
                <strong>Verified:</strong> <?= $component['verified_at'] ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Recommendations -->
        <div class="recommendations">
            <h3>💡 Recommendations</h3>
            <ul>
                <?php foreach ($recommendations as $recommendation): ?>
                <li><?= htmlspecialchars($recommendation) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Additional Tools -->
        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>🛠️ Additional Diagnostic Tools</h3>
            <p>
                <a href="comprehensive_test_suite.php" style="margin: 5px; padding: 10px 15px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">🧪 Comprehensive Tests</a>
                <a href="production_flow_tester.php" style="margin: 5px; padding: 10px 15px; background: #28a745; color: white; text-decoration: none; border-radius: 5px;">🔄 Flow Tests</a>
                <a href="error_viewer.php" style="margin: 5px; padding: 10px 15px; background: #dc3545; color: white; text-decoration: none; border-radius: 5px;">📋 Error Logs</a>
                <a href="confirm_payment_diagnostic.php" style="margin: 5px; padding: 10px 15px; background: #ffc107; color: black; text-decoration: none; border-radius: 5px;">🔍 Payment Debug</a>
            </p>
        </div>

        <div style="text-align: center; margin-top: 20px; color: #666;">
            <p>Analysis completed at <?= date('Y-m-d H:i:s T') ?></p>
            <p><small>MIW Travel Management System - Issues Analysis Report v1.0</small></p>
        </div>
    </div>
</body>
</html>
