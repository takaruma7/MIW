<?php
/**
 * Minimal config.php test
 */

set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting minimal config test...\n";

// Check if we can start output buffering
echo "Testing output buffering...\n";
ob_start();
echo "Buffer test\n";
$bufferContent = ob_get_clean();
echo "Buffer worked: " . $bufferContent;

// Test if we can load a simple PHP file first
echo "Testing simple PHP include...\n";
$testPhpContent = '<?php echo "Simple PHP file loaded successfully\n"; ?>';
file_put_contents('test_simple.php', $testPhpContent);

try {
    include 'test_simple.php';
    unlink('test_simple.php');
    echo "Simple include test passed\n";
} catch (Exception $e) {
    echo "Simple include failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Now test just the beginning of config.php to see where it fails
echo "Testing config.php loading step by step...\n";

try {
    // Read config.php and check it line by line
    $configContent = file_get_contents('config.php');
    $configLines = explode("\n", $configContent);
    
    echo "Config.php has " . count($configLines) . " lines\n";
    echo "First 5 lines:\n";
    for ($i = 0; $i < min(5, count($configLines)); $i++) {
        echo "  " . ($i + 1) . ": " . trim($configLines[$i]) . "\n";
    }
    
    // Try to execute just the first part
    echo "Testing minimal config execution...\n";
    
    // Create a minimal version with just the beginning
    $minimalConfig = implode("\n", array_slice($configLines, 0, 20));
    file_put_contents('test_config_minimal.php', $minimalConfig);
    
    ob_start();
    include 'test_config_minimal.php';
    $minimalOutput = ob_get_clean();
    
    echo "Minimal config output: " . $minimalOutput . "\n";
    unlink('test_config_minimal.php');
    
} catch (Exception $e) {
    echo "Config loading test failed: " . $e->getMessage() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "File: " . $e->getFile() . "\n";
}

echo "Minimal test complete\n";
?>
