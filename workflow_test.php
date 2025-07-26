<?php
/**
 * MIW Travel Management System - Workflow Test
 * 
 * This file tests all critical workflows to ensure the system is running reliably.
 * 
 * @version 1.0.0
 */

require_once 'config.php';
require_once 'session_manager.php';

// Set content type for JSON response
header('Content-Type: application/json');

$tests = [];
$overallStatus = true;

/**
 * Test 1: Database Connection
 */
try {
    $stmt = $conn->prepare("SELECT 1");
    $stmt->execute();
    $tests['database_connection'] = [
        'status' => 'pass',
        'message' => 'Database connection successful',
        'environment' => getCurrentEnvironment(),
        'database_type' => getDatabaseType()
    ];
} catch (Exception $e) {
    $tests['database_connection'] = [
        'status' => 'fail',
        'message' => 'Database connection failed: ' . $e->getMessage()
    ];
    $overallStatus = false;
}

/**
 * Test 2: Essential Tables Exist
 */
try {
    $requiredTables = ['data_jamaah', 'data_paket', 'data_pembatalan'];
    $existingTables = [];
    
    foreach ($requiredTables as $table) {
        try {
            if (getDatabaseType() === 'postgresql') {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?");
            } else {
                $stmt = $conn->prepare("SHOW TABLES LIKE ?");
            }
            $stmt->execute([$table]);
            $result = $stmt->fetch();
            
            if ($result && (isset($result['count']) ? $result['count'] > 0 : count($result) > 0)) {
                $existingTables[] = $table;
            }
        } catch (Exception $e) {
            // Table doesn't exist
        }
    }
    
    if (count($existingTables) === count($requiredTables)) {
        $tests['tables_exist'] = [
            'status' => 'pass',
            'message' => 'All required tables exist',
            'existing_tables' => $existingTables
        ];
    } else {
        $missingTables = array_diff($requiredTables, $existingTables);
        $tests['tables_exist'] = [
            'status' => 'fail',
            'message' => 'Missing tables: ' . implode(', ', $missingTables),
            'existing_tables' => $existingTables,
            'missing_tables' => $missingTables
        ];
        $overallStatus = false;
    }
} catch (Exception $e) {
    $tests['tables_exist'] = [
        'status' => 'fail',
        'message' => 'Error checking tables: ' . $e->getMessage()
    ];
    $overallStatus = false;
}

/**
 * Test 3: Upload Handler Functionality
 */
try {
    require_once 'upload_handler.php';
    $uploadHandler = new UploadHandler();
    
    // Test filename generation
    $testFilename = $uploadHandler->generateCustomFilename('1234567890123456', 'test', 'PAK001');
    
    if (strpos($testFilename, '1234567890123456') !== false && strpos($testFilename, 'test') !== false) {
        $tests['upload_handler'] = [
            'status' => 'pass',
            'message' => 'Upload handler working correctly',
            'test_filename' => $testFilename
        ];
    } else {
        $tests['upload_handler'] = [
            'status' => 'fail',
            'message' => 'Upload handler filename generation failed'
        ];
        $overallStatus = false;
    }
} catch (Exception $e) {
    $tests['upload_handler'] = [
        'status' => 'fail',
        'message' => 'Upload handler error: ' . $e->getMessage()
    ];
    $overallStatus = false;
}

/**
 * Test 4: Email Configuration
 */
try {
    $emailConfig = [
        'SMTP_HOST' => defined('SMTP_HOST') ? SMTP_HOST : 'not_defined',
        'SMTP_PORT' => defined('SMTP_PORT') ? SMTP_PORT : 'not_defined',
        'SMTP_USERNAME' => defined('SMTP_USERNAME') ? (empty(SMTP_USERNAME) ? 'empty' : 'configured') : 'not_defined',
        'SMTP_PASSWORD' => defined('SMTP_PASSWORD') ? (empty(SMTP_PASSWORD) ? 'empty' : 'configured') : 'not_defined'
    ];
    
    $configurationOk = defined('SMTP_HOST') && defined('SMTP_PORT') && defined('SMTP_USERNAME') && defined('SMTP_PASSWORD');
    
    $tests['email_configuration'] = [
        'status' => $configurationOk ? 'pass' : 'warning',
        'message' => $configurationOk ? 'Email configuration complete' : 'Email configuration incomplete',
        'configuration' => $emailConfig
    ];
} catch (Exception $e) {
    $tests['email_configuration'] = [
        'status' => 'fail',
        'message' => 'Email configuration error: ' . $e->getMessage()
    ];
}

/**
 * Test 5: Session Functionality
 */
try {
    $_SESSION['test_key'] = 'test_value';
    
    if (isset($_SESSION['test_key']) && $_SESSION['test_key'] === 'test_value') {
        unset($_SESSION['test_key']);
        $tests['session_functionality'] = [
            'status' => 'pass',
            'message' => 'Session functionality working',
            'session_id' => session_id()
        ];
    } else {
        $tests['session_functionality'] = [
            'status' => 'fail',
            'message' => 'Session functionality not working'
        ];
        $overallStatus = false;
    }
} catch (Exception $e) {
    $tests['session_functionality'] = [
        'status' => 'fail',
        'message' => 'Session error: ' . $e->getMessage()
    ];
    $overallStatus = false;
}

/**
 * Test 6: File System Access
 */
try {
    $uploadDir = getUploadDirectory();
    ensureUploadDirectory();
    
    if (is_dir($uploadDir) && is_writable($uploadDir)) {
        $tests['file_system'] = [
            'status' => 'pass',
            'message' => 'File system access working',
            'upload_directory' => $uploadDir,
            'writable' => true
        ];
    } else {
        $tests['file_system'] = [
            'status' => 'warning',
            'message' => 'File system access limited (expected on Heroku)',
            'upload_directory' => $uploadDir,
            'writable' => is_writable($uploadDir)
        ];
    }
} catch (Exception $e) {
    $tests['file_system'] = [
        'status' => 'fail',
        'message' => 'File system error: ' . $e->getMessage()
    ];
}

/**
 * Test 7: Data Package Integrity
 */
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM data_paket");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && $result['count'] > 0) {
        $tests['package_data'] = [
            'status' => 'pass',
            'message' => 'Package data available',
            'package_count' => $result['count']
        ];
    } else {
        $tests['package_data'] = [
            'status' => 'warning',
            'message' => 'No package data found',
            'package_count' => 0
        ];
    }
} catch (Exception $e) {
    $tests['package_data'] = [
        'status' => 'fail',
        'message' => 'Package data error: ' . $e->getMessage()
    ];
}

/**
 * Test 8: Critical Files Exist
 */
$criticalFiles = [
    'config.php',
    'upload_handler.php',
    'heroku_file_manager.php',
    'email_functions.php',
    'form_haji.php',
    'form_umroh.php',
    'admin_dashboard.php'
];

$missingFiles = [];
foreach ($criticalFiles as $file) {
    if (!file_exists(__DIR__ . '/' . $file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $tests['critical_files'] = [
        'status' => 'pass',
        'message' => 'All critical files present',
        'files_checked' => count($criticalFiles)
    ];
} else {
    $tests['critical_files'] = [
        'status' => 'fail',
        'message' => 'Missing critical files: ' . implode(', ', $missingFiles),
        'missing_files' => $missingFiles
    ];
    $overallStatus = false;
}

// Compile final result
$result = [
    'overall_status' => $overallStatus ? 'pass' : 'fail',
    'test_count' => count($tests),
    'passed_tests' => count(array_filter($tests, function($test) { return $test['status'] === 'pass'; })),
    'failed_tests' => count(array_filter($tests, function($test) { return $test['status'] === 'fail'; })),
    'warning_tests' => count(array_filter($tests, function($test) { return $test['status'] === 'warning'; })),
    'environment' => getCurrentEnvironment(),
    'database_type' => getDatabaseType(),
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => $tests
];

// Output JSON response
echo json_encode($result, JSON_PRETTY_PRINT);
?>
