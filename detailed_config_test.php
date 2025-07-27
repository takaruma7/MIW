<?php
/**
 * Detailed config.php test - line by line
 */

set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Detailed config test...\n";

try {
    $configContent = file_get_contents('config.php');
    $configLines = explode("\n", $configContent);
    
    echo "Total lines: " . count($configLines) . "\n";
    
    // Test line by line starting from 45
    for ($testLines = 45; $testLines <= 55; $testLines++) {
        echo "\nTesting first $testLines lines...\n";
        
        $testConfig = implode("\n", array_slice($configLines, 0, $testLines));
        $testFile = "test_line_$testLines.php";
        file_put_contents($testFile, $testConfig);
        
        // Show the last few lines being added
        echo "Last 3 lines being tested:\n";
        for ($i = max(0, $testLines - 3); $i < $testLines; $i++) {
            echo "  " . ($i + 1) . ": " . trim($configLines[$i]) . "\n";
        }
        
        // Test the chunk
        ob_start();
        $error = null;
        
        try {
            $output = shell_exec("php -l $testFile 2>&1");
            if (strpos($output, 'No syntax errors') !== false) {
                echo "  SYNTAX OK for $testLines lines\n";
            } else {
                echo "  SYNTAX ERROR for $testLines lines: $output\n";
                unlink($testFile);
                break;
            }
            
            // Now try to include it
            include $testFile;
            $includeOutput = ob_get_clean();
            echo "  INCLUDE OK for $testLines lines\n";
            if (!empty($includeOutput)) {
                echo "  Include output: " . substr($includeOutput, 0, 50) . "...\n";
            }
            
        } catch (Exception $e) {
            ob_get_clean();
            echo "  INCLUDE FAILED for $testLines lines: " . $e->getMessage() . "\n";
            echo "  Error line: " . $e->getLine() . "\n";
            unlink($testFile);
            break;
        } catch (ParseError $e) {
            ob_get_clean();
            echo "  PARSE ERROR for $testLines lines: " . $e->getMessage() . "\n";
            echo "  Error line: " . $e->getLine() . "\n";
            unlink($testFile);
            break;
        }
        
        unlink($testFile);
    }
    
} catch (Exception $e) {
    echo "Detailed test failed: " . $e->getMessage() . "\n";
}

echo "\nDetailed test complete\n";
?>
