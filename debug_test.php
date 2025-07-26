<?php
/**
 * Simple Workflow Test - Debug Version
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SIMPLE WORKFLOW TEST DEBUG ===\n";

// Test 1: Basic PHP
echo "✅ PHP " . PHP_VERSION . " is working\n";

// Test 2: Config file test
echo "\nTesting config inclusion...\n";
try {
    if (file_exists('config.php')) {
        echo "✅ config.php file exists\n";
        
        // Read first few lines to check syntax
        $configLines = file('config.php', FILE_IGNORE_NEW_LINES);
        echo "✅ Config file readable, " . count($configLines) . " lines\n";
        
        // Check for common patterns
        $hasDbConfig = false;
        foreach ($configLines as $line) {
            if (strpos($line, 'PDO') !== false || strpos($line, '$conn') !== false) {
                $hasDbConfig = true;
                break;
            }
        }
        
        if ($hasDbConfig) {
            echo "✅ Database config patterns found\n";
        } else {
            echo "⚠️ No database config patterns found\n";
        }
        
        // Try to include without connecting
        echo "Attempting to include config...\n";
        ob_start();
        $error = null;
        
        try {
            include_once 'config.php';
            echo "✅ Config included successfully\n";
            
            if (isset($conn)) {
                echo "✅ \$conn variable available\n";
                
                if ($conn instanceof PDO) {
                    echo "✅ \$conn is PDO instance\n";
                    
                    // Test simple query
                    $stmt = $conn->query("SELECT 1 as test");
                    if ($stmt) {
                        $result = $stmt->fetchColumn();
                        if ($result == 1) {
                            echo "✅ Database connection working\n";
                        } else {
                            echo "❌ Database query failed\n";
                        }
                    } else {
                        echo "❌ Query preparation failed\n";
                    }
                } else {
                    echo "❌ \$conn is not PDO instance: " . gettype($conn) . "\n";
                }
            } else {
                echo "❌ \$conn variable not set after config include\n";
            }
        } catch (Exception $e) {
            echo "❌ Config include error: " . $e->getMessage() . "\n";
        } catch (Error $e) {
            echo "❌ Config include fatal error: " . $e->getMessage() . "\n";
        }
        
        $output = ob_get_clean();
        echo $output;
        
    } else {
        echo "❌ config.php file not found\n";
    }
} catch (Exception $e) {
    echo "❌ Config test error: " . $e->getMessage() . "\n";
}

// Test 3: Test critical files syntax
echo "\nTesting file syntax...\n";
$criticalFiles = [
    'upload_handler.php',
    'confirm_payment.php',
    'admin_dashboard.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        $output = shell_exec("php -l \"$file\" 2>&1");
        if (strpos($output, 'No syntax errors') !== false) {
            echo "✅ $file syntax OK\n";
        } else {
            echo "❌ $file syntax error: $output\n";
        }
    } else {
        echo "❌ $file not found\n";
    }
}

echo "\n=== DEBUG TEST COMPLETE ===\n";
?>
