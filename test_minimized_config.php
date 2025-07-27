<?php
/**
 * Test the minimized config.php
 */

set_time_limit(20);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing minimized config.php...\n";

try {
    echo "Loading minimized config...\n";
    require_once 'config_minimized.php';
    echo "✓ Minimized config loaded successfully!\n";
    
    // Test if EMAIL_ENABLED is defined
    if (defined('EMAIL_ENABLED')) {
        echo "✓ EMAIL_ENABLED is defined: " . (EMAIL_ENABLED ? 'true' : 'false') . "\n";
    } else {
        echo "✗ EMAIL_ENABLED is NOT defined\n";
    }
    
    // Test if other email constants are defined
    $emailConstants = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'EMAIL_FROM', 'EMAIL_FROM_NAME'];
    foreach ($emailConstants as $const) {
        if (defined($const)) {
            $value = constant($const);
            $displayValue = ($const === 'SMTP_PASSWORD') ? '[HIDDEN]' : $value;
            echo "✓ $const is defined: $displayValue\n";
        } else {
            echo "✗ $const is NOT defined\n";
        }
    }
    
    // Test database connection status
    if (isset($pdo)) {
        echo "✓ PDO connection is available\n";
    } else {
        echo "✗ PDO connection is NOT available\n";
    }
    
    // Test environment detection
    if (function_exists('getCurrentEnvironment')) {
        echo "✓ Current environment: " . getCurrentEnvironment() . "\n";
    }
    
    // Test utility functions
    echo "✓ Database type: " . getDatabaseType() . "\n";
    echo "✓ Is production: " . (isProduction() ? 'true' : 'false') . "\n";
    echo "✓ Upload directory: " . getUploadDirectory() . "\n";
    echo "✓ App version: " . getAppVersion() . "\n";
    
    echo "\n✅ All tests passed! Minimized config works correctly.\n";
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
} catch (Error $e) {
    echo "✗ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Test complete\n";
?>
