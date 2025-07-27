<?php
echo "Testing step by step...\n";

// Test 1: Basic PHP execution
echo "1. PHP is working\n";

// Test 2: Basic require
try {
    echo "2. About to require config_minimized.php...\n";
    require_once 'config_minimized.php';
    echo "3. Config loaded successfully!\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Type: " . get_class($e) . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "Done\n";
?>
