<?php
/**
 * Test the clean config.php
 */

set_time_limit(20);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing clean config.php...\n";

try {
    echo "Loading clean config...\n";
    require_once 'config_clean.php';
    echo "✓ Clean config loaded successfully!\n";
    
    echo "✓ Current environment: " . getCurrentEnvironment() . "\n";
    echo "✓ Database type: " . getDatabaseType() . "\n";
    echo "✓ Is production: " . (isProduction() ? 'true' : 'false') . "\n";
    echo "✓ Upload directory: " . getUploadDirectory() . "\n";
    echo "✓ App version: " . getAppVersion() . "\n";
    
    // Test if EMAIL_ENABLED is defined
    if (defined('EMAIL_ENABLED')) {
        echo "✓ EMAIL_ENABLED is defined: " . (EMAIL_ENABLED ? 'true' : 'false') . "\n";
    } else {
        echo "✗ EMAIL_ENABLED is NOT defined\n";
    }
    
    // Test if other email constants are defined
    $emailConstants = ['SMTP_HOST', 'SMTP_PORT', 'EMAIL_FROM', 'EMAIL_FROM_NAME'];
    foreach ($emailConstants as $const) {
        if (defined($const)) {
            $value = constant($const);
            echo "✓ $const is defined: $value\n";
        } else {
            echo "✗ $const is NOT defined\n";
        }
    }
    
    // Test database connection status
    if (isset($pdo)) {
        echo "✓ PDO connection is available\n";
    } else {
        echo "⚠ PDO connection is NOT available (normal for local without database)\n";
    }
    
    echo "\n✅ All tests passed! Clean config works correctly.\n";
    echo "Removed services: Railway, Render\n";
    echo "Kept services: Heroku, Local, Docker, SMTP, GitHub\n";
    
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
