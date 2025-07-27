<?php
/**
 * Troubleshooting script for verify_email_fix.php
 * Tests each dependency individually with time limits
 */

set_time_limit(20);
ini_set('max_execution_time', 20);

echo "=== TROUBLESHOOTING verify_email_fix.php ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Basic PHP functionality
echo "1. Testing basic PHP functionality...\n";
try {
    $testVar = "test";
    echo "   ✓ PHP variables working\n";
    
    if (function_exists('date')) {
        echo "   ✓ Date function available\n";
    } else {
        echo "   ✗ Date function not available\n";
    }
    
    if (function_exists('set_time_limit')) {
        echo "   ✓ set_time_limit function available\n";
    } else {
        echo "   ✗ set_time_limit function not available\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Basic PHP test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: File system access
echo "\n2. Testing file system access...\n";
try {
    $currentDir = __DIR__;
    echo "   ✓ Current directory: $currentDir\n";
    
    if (file_exists('config.php')) {
        echo "   ✓ config.php exists\n";
        $configSize = filesize('config.php');
        echo "   ✓ config.php size: $configSize bytes\n";
    } else {
        echo "   ✗ config.php NOT found\n";
        // List available files
        $files = glob('*.php');
        echo "   Available PHP files: " . implode(', ', $files) . "\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "   ✗ File system test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Try to include config.php with error handling
echo "\n3. Testing config.php inclusion...\n";
try {
    // Enable all error reporting for this test
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Capture any output or errors
    ob_start();
    $errorBefore = error_get_last();
    
    echo "   Attempting to include config.php...\n";
    include_once 'config.php';
    echo "   Include completed...\n";
    
    $output = ob_get_clean();
    $errorAfter = error_get_last();
    
    if ($output) {
        echo "   Output from config.php: " . trim($output) . "\n";
    }
    
    if ($errorAfter && $errorAfter !== $errorBefore) {
        echo "   ✗ Error loading config.php: " . $errorAfter['message'] . "\n";
        echo "   ✗ Error in file: " . $errorAfter['file'] . " on line " . $errorAfter['line'] . "\n";
        exit(1);
    }
    
    echo "   ✓ config.php included successfully\n";
    
} catch (ParseError $e) {
    echo "   ✗ Parse error in config.php: " . $e->getMessage() . "\n";
    echo "   ✗ Error on line: " . $e->getLine() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "   ✗ Exception loading config.php: " . $e->getMessage() . "\n";
    echo "   ✗ Error on line: " . $e->getLine() . "\n";
    exit(1);
}

// Test 4: Check if constants are defined
echo "\n4. Testing constants definition...\n";
try {
    // Check EMAIL_ENABLED
    if (defined('EMAIL_ENABLED')) {
        echo "   ✓ EMAIL_ENABLED defined: " . (EMAIL_ENABLED ? 'true' : 'false') . "\n";
    } else {
        echo "   ✗ EMAIL_ENABLED NOT defined\n";
    }
    
    // Check other email constants
    $emailConstants = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'EMAIL_FROM', 'ADMIN_EMAIL'];
    foreach ($emailConstants as $constant) {
        if (defined($constant)) {
            $value = constant($constant);
            if ($constant === 'SMTP_USERNAME' && !empty($value)) {
                $value = substr($value, 0, 3) . '***';
            }
            echo "   ✓ $constant defined: $value\n";
        } else {
            echo "   ✗ $constant NOT defined\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Constants test failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check global variables
echo "\n5. Testing global variables...\n";
try {
    global $pdo, $conn, $db_config;
    
    if (isset($pdo)) {
        echo "   ✓ \$pdo variable exists: " . get_class($pdo) . "\n";
    } else {
        echo "   ✗ \$pdo variable not set\n";
    }
    
    if (isset($conn)) {
        echo "   ✓ \$conn variable exists: " . get_class($conn) . "\n";
    } else {
        echo "   ✗ \$conn variable not set\n";
    }
    
    if (isset($db_config)) {
        echo "   ✓ \$db_config variable exists\n";
        if (is_array($db_config)) {
            echo "   ✓ \$db_config is array with " . count($db_config) . " elements\n";
        }
    } else {
        echo "   ✗ \$db_config variable not set\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Global variables test failed: " . $e->getMessage() . "\n";
}

// Test 6: Try to load email_functions.php
echo "\n6. Testing email_functions.php...\n";
try {
    if (file_exists('email_functions.php')) {
        echo "   ✓ email_functions.php exists\n";
        
        ob_start();
        $errorBefore = error_get_last();
        
        include_once 'email_functions.php';
        
        $output = ob_get_clean();
        $errorAfter = error_get_last();
        
        if ($output) {
            echo "   Output from email_functions.php: " . trim($output) . "\n";
        }
        
        if ($errorAfter && $errorAfter !== $errorBefore) {
            echo "   ✗ Error loading email_functions.php: " . $errorAfter['message'] . "\n";
        } else {
            echo "   ✓ email_functions.php loaded successfully\n";
            
            if (function_exists('sendPaymentConfirmationEmail')) {
                echo "   ✓ sendPaymentConfirmationEmail function exists\n";
            } else {
                echo "   ✗ sendPaymentConfirmationEmail function not found\n";
            }
        }
        
    } else {
        echo "   ✗ email_functions.php NOT found\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ email_functions.php test failed: " . $e->getMessage() . "\n";
}

echo "\n=== TROUBLESHOOTING COMPLETE ===\n";
echo "If all tests passed, the original verify_email_fix.php should work.\n";
echo "If any test failed, that's the issue that needs to be fixed first.\n";
?>
