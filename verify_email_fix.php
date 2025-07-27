<?php
/**
 * Quick verification script for EMAIL_ENABLED constant fix
 * This script checks if the EMAIL_ENABLED constant is properly defined
 */

echo "=== EMAIL_ENABLED Constant Verification ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Set time limits to prevent hanging
    set_time_limit(20); // 20 seconds max
    ini_set('max_execution_time', 20);
    
    echo "⏱️ Time limit set to 20 seconds\n";
    echo "🌍 Environment: " . ($_ENV['DYNO'] ?? 'Local') . "\n";
    
    // Load configuration
    require_once 'config.php';
    
    echo "✓ Config loaded successfully\n";
    
    // Check if EMAIL_ENABLED is defined
    if (defined('EMAIL_ENABLED')) {
        echo "✓ EMAIL_ENABLED constant is defined\n";
        echo "  Value: " . (EMAIL_ENABLED ? 'true' : 'false') . "\n";
    } else {
        echo "✗ EMAIL_ENABLED constant is NOT defined\n";
        exit(1);
    }
    
    // Check other email constants
    $emailConstants = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD', 'EMAIL_FROM', 'EMAIL_FROM_NAME', 'ADMIN_EMAIL'];
    
    echo "\nOther email constants:\n";
    foreach ($emailConstants as $constant) {
        if (defined($constant)) {
            $value = constant($constant);
            // Hide sensitive data
            if (in_array($constant, ['SMTP_PASSWORD', 'SMTP_USERNAME']) && !empty($value)) {
                $value = str_repeat('*', strlen($value));
            }
            echo "  ✓ $constant: $value\n";
        } else {
            echo "  ✗ $constant: NOT DEFINED\n";
        }
    }
    
    // Test email_functions.php loading
    echo "\nTesting email_functions.php:\n";
    try {
        // Check if we're still within time limit
        if (time() - $_SERVER['REQUEST_TIME'] > 15) {
            throw new Exception("Time limit approaching, stopping email functions test");
        }
        
        require_once 'email_functions.php';
        echo "✓ email_functions.php loaded successfully\n";
        
        // Test if the function that was causing the error exists
        if (function_exists('sendPaymentConfirmationEmail')) {
            echo "✓ sendPaymentConfirmationEmail function exists\n";
        } else {
            echo "✗ sendPaymentConfirmationEmail function not found\n";
        }
        
    } catch (Exception $e) {
        echo "✗ Error loading email_functions.php: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== Verification Complete ===\n";
    echo "The EMAIL_ENABLED constant issue has been resolved!\n";
    
} catch (Exception $e) {
    echo "✗ Error during verification: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
