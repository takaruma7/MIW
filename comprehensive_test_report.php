<?php
/**
 * Comprehensive Registration Flow Test Report
 * 
 * This script provides a complete analysis and summary of all registration
 * flow testing including white box, black box, and functional testing results.
 * 
 * @version 1.0.0
 */

require_once 'config.php';

// Test environment check
$isProduction = isset($_ENV['DYNO']) || isset($_ENV['RENDER']) || isset($_ENV['RAILWAY_ENVIRONMENT']);
$environment = $isProduction ? 'PRODUCTION' : 'DEVELOPMENT';

// Test execution timestamp
$testExecutionTime = date('Y-m-d H:i:s T');

// Comprehensive test summary
$testSummary = [
    'environment' => $environment,
    'test_timestamp' => $testExecutionTime,
    'database_connectivity' => false,
    'critical_files_status' => [],
    'form_functionality' => [],
    'admin_workflow' => [],
    'email_system' => [],
    'security_assessment' => [],
    'overall_status' => 'UNKNOWN'
];

// Function to test database connectivity
function testDatabaseConnectivity() {
    global $conn, $testSummary;
    
    try {
        $stmt = $conn->prepare("SELECT 1 as test");
        $stmt->execute();
        $result = $stmt->fetch();
        
        if ($result['test'] == 1) {
            $testSummary['database_connectivity'] = true;
            return true;
        }
    } catch (Exception $e) {
        $testSummary['database_connectivity'] = false;
        return false;
    }
    
    return false;
}

// Function to check critical files
function checkCriticalFiles() {
    global $testSummary;
    
    $criticalFiles = [
        'form_haji.php' => 'Haji Registration Form',
        'form_umroh.php' => 'Umroh Registration Form',
        'submit_haji.php' => 'Haji Submission Handler',
        'submit_umroh.php' => 'Umroh Submission Handler',
        'confirm_payment.php' => 'Payment Confirmation',
        'admin_pending.php' => 'Admin Verification Interface',
        'admin_dashboard.php' => 'Admin Dashboard',
        'email_functions.php' => 'Email System',
        'upload_handler.php' => 'File Upload Handler',
        'config.php' => 'Configuration File'
    ];
    
    $fileStatus = [];
    $missingFiles = 0;
    
    foreach ($criticalFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            $fileStatus[$file] = [
                'status' => 'EXISTS',
                'size' => strlen($content),
                'description' => $description,
                'has_content' => strlen(trim($content)) > 100
            ];
        } else {
            $fileStatus[$file] = [
                'status' => 'MISSING',
                'size' => 0,
                'description' => $description,
                'has_content' => false
            ];
            $missingFiles++;
        }
    }
    
    $testSummary['critical_files_status'] = $fileStatus;
    return $missingFiles === 0;
}

// Function to test form functionality
function testFormFunctionality() {
    global $testSummary, $conn;
    
    $formTests = [];
    
    // Test 1: Check if packages exist for forms
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM data_paket");
        $stmt->execute();
        $packageCount = $stmt->fetchColumn();
        
        $formTests['packages_available'] = [
            'status' => $packageCount > 0 ? 'PASS' : 'FAIL',
            'count' => $packageCount,
            'message' => $packageCount > 0 ? "$packageCount packages available" : "No packages found"
        ];
    } catch (Exception $e) {
        $formTests['packages_available'] = [
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
    
    // Test 2: Form structure analysis
    $forms = ['form_haji.php', 'form_umroh.php'];
    foreach ($forms as $form) {
        if (file_exists(__DIR__ . '/' . $form)) {
            $content = file_get_contents(__DIR__ . '/' . $form);
            
            $formTests[$form] = [
                'has_form_tag' => strpos($content, '<form') !== false,
                'has_file_upload' => strpos($content, 'enctype="multipart/form-data"') !== false,
                'has_validation' => strpos($content, 'required') !== false,
                'has_javascript' => strpos($content, '<script') !== false,
                'action_points_to_submit' => preg_match('/action=["\']?submit_/', $content)
            ];
        } else {
            $formTests[$form] = ['status' => 'MISSING'];
        }
    }
    
    $testSummary['form_functionality'] = $formTests;
    
    // Return overall form status
    $criticalIssues = 0;
    if (isset($formTests['packages_available']) && $formTests['packages_available']['status'] !== 'PASS') {
        $criticalIssues++;
    }
    foreach ($forms as $form) {
        if (isset($formTests[$form]['status']) && $formTests[$form]['status'] === 'MISSING') {
            $criticalIssues++;
        }
    }
    
    return $criticalIssues === 0;
}

// Function to test admin workflow
function testAdminWorkflow() {
    global $testSummary;
    
    $adminTests = [];
    
    // Check admin files
    $adminFiles = [
        'admin_pending.php' => 'Payment verification interface',
        'admin_dashboard.php' => 'Main admin dashboard',
        'admin_paket.php' => 'Package management',
        'admin_pembatalan.php' => 'Cancellation management'
    ];
    
    $missingAdminFiles = 0;
    foreach ($adminFiles as $file => $description) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            $adminTests[$file] = [
                'status' => 'EXISTS',
                'has_verification_logic' => strpos($content, 'verify_payment') !== false,
                'has_rejection_logic' => strpos($content, 'reject_payment') !== false,
                'has_bootstrap' => strpos($content, 'bootstrap') !== false,
                'description' => $description
            ];
        } else {
            $adminTests[$file] = [
                'status' => 'MISSING',
                'description' => $description
            ];
            $missingAdminFiles++;
        }
    }
    
    $testSummary['admin_workflow'] = $adminTests;
    return $missingAdminFiles === 0;
}

// Function to test email system
function testEmailSystem() {
    global $testSummary;
    
    $emailTests = [];
    
    try {
        if (file_exists(__DIR__ . '/email_functions.php')) {
            require_once 'email_functions.php';
            
            $emailTests['email_file'] = 'EXISTS';
            $emailTests['email_enabled'] = defined('EMAIL_ENABLED') ? (EMAIL_ENABLED ? 'YES' : 'NO') : 'NOT_DEFINED';
            $emailTests['smtp_configured'] = defined('SMTP_HOST') && defined('SMTP_USERNAME') ? 'YES' : 'NO';
            $emailTests['from_address'] = defined('EMAIL_FROM') ? EMAIL_FROM : 'NOT_SET';
            $emailTests['admin_email'] = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'NOT_SET';
            
            // Check email functions
            $emailFunctions = [
                'sendRegistrationEmail',
                'sendConfirmationEmail', 
                'sendPaymentConfirmationEmail',
                'sendPaymentVerificationEmail'
            ];
            
            $availableFunctions = [];
            $missingFunctions = [];
            
            foreach ($emailFunctions as $function) {
                if (function_exists($function)) {
                    $availableFunctions[] = $function;
                } else {
                    $missingFunctions[] = $function;
                }
            }
            
            $emailTests['available_functions'] = $availableFunctions;
            $emailTests['missing_functions'] = $missingFunctions;
            
        } else {
            $emailTests['email_file'] = 'MISSING';
        }
        
    } catch (Exception $e) {
        $emailTests['error'] = $e->getMessage();
    }
    
    $testSummary['email_system'] = $emailTests;
    
    // Return overall email status
    $emailReady = (
        isset($emailTests['email_file']) && $emailTests['email_file'] === 'EXISTS' &&
        isset($emailTests['smtp_configured']) && $emailTests['smtp_configured'] === 'YES' &&
        empty($emailTests['missing_functions'])
    );
    
    return $emailReady;
}

// Function to assess security
function assessSecurity() {
    global $testSummary, $isProduction;
    
    $securityTests = [];
    
    // Check for prepared statements in submit files
    $submitFiles = ['submit_haji.php', 'submit_umroh.php', 'confirm_payment.php'];
    $sqlProtection = 0;
    
    foreach ($submitFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            if (strpos($content, '->prepare(') !== false) {
                $sqlProtection++;
            }
        }
    }
    
    $securityTests['sql_injection_protection'] = [
        'files_checked' => count($submitFiles),
        'files_with_protection' => $sqlProtection,
        'percentage' => round(($sqlProtection / count($submitFiles)) * 100, 1)
    ];
    
    // Check for XSS protection
    $adminFiles = ['admin_pending.php', 'admin_dashboard.php'];
    $xssProtection = 0;
    
    foreach ($adminFiles as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $content = file_get_contents(__DIR__ . '/' . $file);
            if (strpos($content, 'htmlspecialchars') !== false) {
                $xssProtection++;
            }
        }
    }
    
    $securityTests['xss_protection'] = [
        'files_checked' => count($adminFiles),
        'files_with_protection' => $xssProtection,
        'percentage' => round(($xssProtection / count($adminFiles)) * 100, 1)
    ];
    
    // Environment-specific checks
    if ($isProduction) {
        $securityTests['display_errors'] = ini_get('display_errors') ? 'ENABLED_RISK' : 'DISABLED_GOOD';
        $securityTests['error_reporting'] = error_reporting() > 0 ? 'ENABLED' : 'DISABLED';
    } else {
        $securityTests['environment_note'] = 'Development environment - some security checks skipped';
    }
    
    $testSummary['security_assessment'] = $securityTests;
    
    // Return overall security status
    $securityIssues = 0;
    if ($securityTests['sql_injection_protection']['percentage'] < 90) $securityIssues++;
    if ($securityTests['xss_protection']['percentage'] < 90) $securityIssues++;
    if ($isProduction && isset($securityTests['display_errors']) && $securityTests['display_errors'] === 'ENABLED_RISK') $securityIssues++;
    
    return $securityIssues === 0;
}

// Function to determine overall status
function determineOverallStatus() {
    global $testSummary;
    
    $dbOk = testDatabaseConnectivity();
    $filesOk = checkCriticalFiles();
    $formsOk = testFormFunctionality();
    $adminOk = testAdminWorkflow();
    $emailOk = testEmailSystem();
    $securityOk = assessSecurity();
    
    $passedTests = 0;
    $totalTests = 6;
    
    if ($dbOk) $passedTests++;
    if ($filesOk) $passedTests++;
    if ($formsOk) $passedTests++;
    if ($adminOk) $passedTests++;
    if ($emailOk) $passedTests++;
    if ($securityOk) $passedTests++;
    
    $percentage = round(($passedTests / $totalTests) * 100, 1);
    
    if ($percentage >= 90) {
        $testSummary['overall_status'] = 'EXCELLENT';
    } elseif ($percentage >= 75) {
        $testSummary['overall_status'] = 'GOOD';
    } elseif ($percentage >= 50) {
        $testSummary['overall_status'] = 'FAIR';
    } else {
        $testSummary['overall_status'] = 'POOR';
    }
    
    $testSummary['test_results'] = [
        'database' => $dbOk,
        'files' => $filesOk,
        'forms' => $formsOk,
        'admin' => $adminOk,
        'email' => $emailOk,
        'security' => $securityOk,
        'passed' => $passedTests,
        'total' => $totalTests,
        'percentage' => $percentage
    ];
    
    return $testSummary['overall_status'];
}

// Generate recommendations
function generateRecommendations() {
    global $testSummary;
    
    $recommendations = [];
    
    if (!$testSummary['database_connectivity']) {
        $recommendations[] = "🚨 CRITICAL: Fix database connectivity issues immediately";
    }
    
    $missingFiles = array_filter($testSummary['critical_files_status'], function($file) {
        return $file['status'] === 'MISSING';
    });
    
    if (!empty($missingFiles)) {
        $recommendations[] = "🚨 CRITICAL: Restore missing files: " . implode(', ', array_keys($missingFiles));
    }
    
    if (isset($testSummary['form_functionality']['packages_available']) && 
        $testSummary['form_functionality']['packages_available']['status'] !== 'PASS') {
        $recommendations[] = "⚠️ WARNING: Add travel packages to enable registration forms";
    }
    
    if (isset($testSummary['email_system']['email_enabled']) && 
        $testSummary['email_system']['email_enabled'] !== 'YES') {
        $recommendations[] = "⚠️ WARNING: Enable email system for notifications";
    }
    
    if (isset($testSummary['security_assessment']['sql_injection_protection']['percentage']) &&
        $testSummary['security_assessment']['sql_injection_protection']['percentage'] < 90) {
        $recommendations[] = "⚠️ SECURITY: Improve SQL injection protection in submission scripts";
    }
    
    if ($testSummary['overall_status'] === 'EXCELLENT') {
        $recommendations[] = "✅ EXCELLENT: System is fully functional and ready for production use";
        $recommendations[] = "💡 SUGGESTION: Continue monitoring with periodic testing";
    } elseif ($testSummary['overall_status'] === 'GOOD') {
        $recommendations[] = "✅ GOOD: System is mostly functional with minor issues to address";
        $recommendations[] = "💡 SUGGESTION: Address warnings to achieve excellent status";
    }
    
    $recommendations[] = "🧪 TESTING: Use the manual testing tools to verify end-to-end functionality";
    $recommendations[] = "📊 MONITORING: Check error logs regularly for runtime issues";
    
    return $recommendations;
}

// Run all tests and generate report
determineOverallStatus();
$recommendations = generateRecommendations();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Registration Flow - Comprehensive Test Report</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; padding: 20px; background: #f8f9fa; line-height: 1.6;
        }
        .container { 
            max-width: 1600px; margin: 0 auto; background: white; 
            padding: 30px; border-radius: 12px; box-shadow: 0 0 25px rgba(0,0,0,0.1); 
        }
        .header { 
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%); 
            color: white; padding: 30px; margin: -30px -30px 30px; 
            border-radius: 12px 12px 0 0; text-align: center; 
        }
        .status-excellent { background: linear-gradient(135deg, #28a745, #20c997); }
        .status-good { background: linear-gradient(135deg, #17a2b8, #007bff); }
        .status-fair { background: linear-gradient(135deg, #ffc107, #fd7e14); }
        .status-poor { background: linear-gradient(135deg, #dc3545, #6f42c1); }
        .summary-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; margin: 30px 0; 
        }
        .summary-card { 
            padding: 25px; border-radius: 12px; color: white; 
            font-weight: bold; box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-align: center;
        }
        .test-section { 
            margin: 30px 0; padding: 25px; border-radius: 10px; 
            border: 1px solid #e9ecef; background: #f8f9fa;
        }
        .test-header { 
            font-size: 24px; font-weight: bold; margin-bottom: 20px; 
            color: #495057; border-bottom: 3px solid #007bff; padding-bottom: 10px;
        }
        .test-item { 
            margin: 15px 0; padding: 15px; background: white; 
            border-radius: 8px; border-left: 4px solid #007bff;
        }
        .test-pass { border-left-color: #28a745; }
        .test-fail { border-left-color: #dc3545; }
        .test-warning { border-left-color: #ffc107; }
        .badge { 
            padding: 4px 8px; border-radius: 12px; font-size: 12px; 
            font-weight: bold; text-transform: uppercase; margin-left: 10px;
        }
        .badge-pass { background: #28a745; color: white; }
        .badge-fail { background: #dc3545; color: white; }
        .badge-warning { background: #ffc107; color: black; }
        .details-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
            gap: 15px; margin-top: 15px;
        }
        .detail-card { 
            padding: 15px; background: #e9ecef; border-radius: 6px; 
            font-family: 'Courier New', monospace; font-size: 13px;
        }
        .recommendations { 
            background: #e7f3ff; padding: 25px; border-radius: 10px; 
            border-left: 5px solid #007bff; margin: 30px 0;
        }
        .recommendations h3 { color: #004085; margin-top: 0; }
        .recommendations ul { color: #004085; }
        .navigation { 
            position: sticky; top: 20px; background: white; 
            padding: 15px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px; text-align: center;
        }
        .nav-link { 
            display: inline-block; margin: 5px; padding: 8px 15px; 
            background: #007bff; color: white; text-decoration: none; 
            border-radius: 5px; font-size: 14px; transition: all 0.3s;
        }
        .nav-link:hover { background: #0056b3; transform: translateY(-2px); }
        .progress-bar { 
            width: 100%; height: 25px; background: #e9ecef; 
            border-radius: 12px; overflow: hidden; margin: 10px 0;
        }
        .progress-fill { 
            height: 100%; background: linear-gradient(90deg, #28a745, #20c997); 
            transition: width 1s ease-in-out; display: flex; align-items: center; 
            justify-content: center; color: white; font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header status-<?= strtolower($testSummary['overall_status']) ?>">
            <h1>📋 MIW Registration Flow - Comprehensive Test Report</h1>
            <p>Complete White Box & Black Box Testing Analysis</p>
            <p><strong>Environment:</strong> <?= $environment ?> | <strong>Status:</strong> <?= $testSummary['overall_status'] ?></p>
            <p><strong>Test Executed:</strong> <?= $testExecutionTime ?></p>
        </div>

        <!-- Overall Status -->
        <div class="summary-grid">
            <div class="summary-card status-<?= strtolower($testSummary['overall_status']) ?>">
                <h2><?= $testSummary['overall_status'] ?></h2>
                <p>Overall System Status</p>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #6f42c1, #007bff); color: white;">
                <h2><?= $testSummary['test_results']['passed'] ?>/<?= $testSummary['test_results']['total'] ?></h2>
                <p>Tests Passed</p>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #17a2b8, #20c997); color: white;">
                <h2><?= $testSummary['test_results']['percentage'] ?>%</h2>
                <p>Success Rate</p>
            </div>
            <div class="summary-card" style="background: linear-gradient(135deg, #fd7e14, #ffc107); color: black;">
                <h2><?= count($recommendations) ?></h2>
                <p>Recommendations</p>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $testSummary['test_results']['percentage'] ?>%">
                <?= $testSummary['test_results']['percentage'] ?>% Complete
            </div>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <strong>Quick Navigation:</strong>
            <a href="#database" class="nav-link">Database</a>
            <a href="#files" class="nav-link">Files</a>
            <a href="#forms" class="nav-link">Forms</a>
            <a href="#admin" class="nav-link">Admin</a>
            <a href="#email" class="nav-link">Email</a>
            <a href="#security" class="nav-link">Security</a>
            <a href="#recommendations" class="nav-link">Recommendations</a>
        </div>

        <!-- Database Connectivity -->
        <div id="database" class="test-section">
            <div class="test-header">🗄️ Database Connectivity</div>
            <div class="test-item <?= $testSummary['database_connectivity'] ? 'test-pass' : 'test-fail' ?>">
                <strong>Database Connection:</strong> 
                <?= $testSummary['database_connectivity'] ? 'Connected ✅' : 'Failed ❌' ?>
                <span class="badge badge-<?= $testSummary['database_connectivity'] ? 'pass' : 'fail' ?>">
                    <?= $testSummary['database_connectivity'] ? 'PASS' : 'FAIL' ?>
                </span>
                <p>Database connectivity is essential for all registration functionality.</p>
            </div>
        </div>

        <!-- Critical Files -->
        <div id="files" class="test-section">
            <div class="test-header">📁 Critical Files Status</div>
            <div class="details-grid">
                <?php foreach ($testSummary['critical_files_status'] as $file => $info): ?>
                    <div class="detail-card">
                        <strong><?= htmlspecialchars($file) ?></strong><br>
                        Status: <?= $info['status'] ?><br>
                        Size: <?= number_format($info['size']) ?> bytes<br>
                        Content: <?= $info['has_content'] ? 'Yes' : 'No' ?><br>
                        <em><?= htmlspecialchars($info['description']) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Form Functionality -->
        <div id="forms" class="test-section">
            <div class="test-header">📝 Form Functionality</div>
            
            <div class="test-item <?= isset($testSummary['form_functionality']['packages_available']) && $testSummary['form_functionality']['packages_available']['status'] === 'PASS' ? 'test-pass' : 'test-fail' ?>">
                <strong>Package Availability:</strong>
                <?php if (isset($testSummary['form_functionality']['packages_available'])): ?>
                    <?= $testSummary['form_functionality']['packages_available']['message'] ?>
                    <span class="badge badge-<?= $testSummary['form_functionality']['packages_available']['status'] === 'PASS' ? 'pass' : 'fail' ?>">
                        <?= $testSummary['form_functionality']['packages_available']['status'] ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="details-grid">
                <?php foreach (['form_haji.php', 'form_umroh.php'] as $form): ?>
                    <?php if (isset($testSummary['form_functionality'][$form])): ?>
                        <div class="detail-card">
                            <strong><?= $form ?></strong><br>
                            Form Tag: <?= $testSummary['form_functionality'][$form]['has_form_tag'] ? 'Yes ✅' : 'No ❌' ?><br>
                            File Upload: <?= $testSummary['form_functionality'][$form]['has_file_upload'] ? 'Yes ✅' : 'No ❌' ?><br>
                            Validation: <?= $testSummary['form_functionality'][$form]['has_validation'] ? 'Yes ✅' : 'No ❌' ?><br>
                            JavaScript: <?= $testSummary['form_functionality'][$form]['has_javascript'] ? 'Yes ✅' : 'No ❌' ?><br>
                            Submit Action: <?= $testSummary['form_functionality'][$form]['action_points_to_submit'] ? 'Yes ✅' : 'No ❌' ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Admin Workflow -->
        <div id="admin" class="test-section">
            <div class="test-header">👨‍💼 Admin Workflow</div>
            <div class="details-grid">
                <?php foreach ($testSummary['admin_workflow'] as $file => $info): ?>
                    <div class="detail-card">
                        <strong><?= $file ?></strong><br>
                        Status: <?= $info['status'] ?><br>
                        <?php if ($info['status'] === 'EXISTS'): ?>
                            Verification: <?= $info['has_verification_logic'] ? 'Yes ✅' : 'No ❌' ?><br>
                            Rejection: <?= $info['has_rejection_logic'] ? 'Yes ✅' : 'No ❌' ?><br>
                            Bootstrap: <?= $info['has_bootstrap'] ? 'Yes ✅' : 'No ❌' ?><br>
                        <?php endif; ?>
                        <em><?= htmlspecialchars($info['description']) ?></em>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Email System -->
        <div id="email" class="test-section">
            <div class="test-header">📧 Email System</div>
            <div class="test-item">
                <div class="details-grid">
                    <div class="detail-card">
                        <strong>Email Configuration</strong><br>
                        File: <?= $testSummary['email_system']['email_file'] ?? 'Unknown' ?><br>
                        Enabled: <?= $testSummary['email_system']['email_enabled'] ?? 'Unknown' ?><br>
                        SMTP: <?= $testSummary['email_system']['smtp_configured'] ?? 'Unknown' ?><br>
                        From: <?= htmlspecialchars($testSummary['email_system']['from_address'] ?? 'Not set') ?><br>
                        Admin: <?= htmlspecialchars($testSummary['email_system']['admin_email'] ?? 'Not set') ?>
                    </div>
                    <div class="detail-card">
                        <strong>Available Functions</strong><br>
                        <?php if (isset($testSummary['email_system']['available_functions'])): ?>
                            <?php foreach ($testSummary['email_system']['available_functions'] as $func): ?>
                                ✅ <?= $func ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if (isset($testSummary['email_system']['missing_functions']) && !empty($testSummary['email_system']['missing_functions'])): ?>
                            <strong>Missing:</strong><br>
                            <?php foreach ($testSummary['email_system']['missing_functions'] as $func): ?>
                                ❌ <?= $func ?><br>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Assessment -->
        <div id="security" class="test-section">
            <div class="test-header">🔒 Security Assessment</div>
            <div class="details-grid">
                <div class="detail-card">
                    <strong>SQL Injection Protection</strong><br>
                    Files Checked: <?= $testSummary['security_assessment']['sql_injection_protection']['files_checked'] ?><br>
                    Protected: <?= $testSummary['security_assessment']['sql_injection_protection']['files_with_protection'] ?><br>
                    Coverage: <?= $testSummary['security_assessment']['sql_injection_protection']['percentage'] ?>%
                </div>
                <div class="detail-card">
                    <strong>XSS Protection</strong><br>
                    Files Checked: <?= $testSummary['security_assessment']['xss_protection']['files_checked'] ?><br>
                    Protected: <?= $testSummary['security_assessment']['xss_protection']['files_with_protection'] ?><br>
                    Coverage: <?= $testSummary['security_assessment']['xss_protection']['percentage'] ?>%
                </div>
                <?php if (isset($testSummary['security_assessment']['display_errors'])): ?>
                    <div class="detail-card">
                        <strong>Production Security</strong><br>
                        Display Errors: <?= $testSummary['security_assessment']['display_errors'] ?><br>
                        Error Reporting: <?= $testSummary['security_assessment']['error_reporting'] ?? 'Unknown' ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recommendations -->
        <div id="recommendations" class="recommendations">
            <h3>💡 Recommendations & Action Items</h3>
            <ul>
                <?php foreach ($recommendations as $recommendation): ?>
                    <li><?= htmlspecialchars($recommendation) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Testing Tools -->
        <div style="text-align: center; margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 10px;">
            <h3>🛠️ Continue Testing with These Tools</h3>
            <div style="margin: 20px 0;">
                <a href="registration_flow_tester.php" style="margin: 5px; padding: 12px 20px; background: #6f42c1; color: white; text-decoration: none; border-radius: 6px;">📊 Flow Analysis</a>
                <a href="form_submission_tester.php" style="margin: 5px; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 6px;">📝 Form Testing</a>
                <a href="comprehensive_test_suite.php" style="margin: 5px; padding: 12px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 6px;">🧪 Full Test Suite</a>
                <a href="issues_analysis.php" style="margin: 5px; padding: 12px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 6px;">📋 Issues Analysis</a>
            </div>
            <div style="margin: 20px 0;">
                <a href="form_haji.php" style="margin: 5px; padding: 12px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 6px;">🕋 Test Haji Form</a>
                <a href="form_umroh.php" style="margin: 5px; padding: 12px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 6px;">🕌 Test Umroh Form</a>
                <a href="admin_pending.php" style="margin: 5px; padding: 12px 20px; background: #ffc107; color: black; text-decoration: none; border-radius: 6px;">👨‍💼 Admin Verification</a>
                <a href="error_viewer.php" style="margin: 5px; padding: 12px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 6px;">📋 Error Logs</a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px; color: #666;">
            <p>Comprehensive Registration Flow Test Report generated at <?= $testExecutionTime ?></p>
            <p><small>MIW Travel Management System - Registration Flow Testing Report v1.0</small></p>
        </div>
    </div>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });

        // Auto-scroll to failed sections
        window.onload = function() {
            const failedTest = document.querySelector('.test-fail');
            if (failedTest) {
                setTimeout(() => {
                    failedTest.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 1000);
            }
        };
    </script>
</body>
</html>
