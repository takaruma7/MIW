<?php
/**
 * Direct config.php test
 */

set_time_limit(20);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Direct config test started...\n";

try {
    echo "Loading config.php...\n";
    require_once 'config.php';
    echo "Config.php loaded successfully!\n";
    
    // Test if EMAIL_ENABLED is defined
    if (defined('EMAIL_ENABLED')) {
        echo "EMAIL_ENABLED is defined: " . (EMAIL_ENABLED ? 'true' : 'false') . "\n";
    } else {
        echo "EMAIL_ENABLED is NOT defined\n";
    }
    
    // Test if other email constants are defined
    $emailConstants = ['EMAIL_HOST', 'EMAIL_PORT', 'EMAIL_USERNAME', 'EMAIL_PASSWORD', 'EMAIL_FROM', 'EMAIL_FROM_NAME'];
    foreach ($emailConstants as $const) {
        if (defined($const)) {
            $value = constant($const);
            $displayValue = ($const === 'EMAIL_PASSWORD') ? '[HIDDEN]' : $value;
            echo "$const is defined: $displayValue\n";
        } else {
            echo "$const is NOT defined\n";
        }
    }
    
    // Test database connection status
    if (isset($pdo)) {
        echo "PDO connection is available\n";
    } else {
        echo "PDO connection is NOT available\n";
    }
    
    // Test environment detection
    if (function_exists('getCurrentEnvironment')) {
        echo "Current environment: " . getCurrentEnvironment() . "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR loading config.php: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR loading config.php: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Direct test complete\n";
?>
