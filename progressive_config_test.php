<?php
/**
 * Progressive config.php test
 */

set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Progressive config test...\n";

try {
    $configContent = file_get_contents('config.php');
    $configLines = explode("\n", $configContent);
    
    // Test progressively larger chunks
    $chunkSizes = [50, 100, 150, 200, 250, 300, count($configLines)];
    
    foreach ($chunkSizes as $size) {
        echo "Testing first $size lines...\n";
        
        $testConfig = implode("\n", array_slice($configLines, 0, $size));
        $testFile = "test_config_$size.php";
        file_put_contents($testFile, $testConfig);
        
        // Test the chunk
        ob_start();
        $error = null;
        
        try {
            include $testFile;
            $output = ob_get_clean();
            echo "  SUCCESS: $size lines executed\n";
            if (!empty($output)) {
                echo "  Output: " . substr($output, 0, 100) . "...\n";
            }
        } catch (Exception $e) {
            ob_get_clean();
            echo "  FAILED at $size lines: " . $e->getMessage() . "\n";
            echo "  Line around " . $e->getLine() . "\n";
            
            // Show the problematic area
            $errorLine = $e->getLine();
            $startLine = max(0, $errorLine - 5);
            $endLine = min($size, $errorLine + 5);
            
            echo "  Context around error:\n";
            for ($i = $startLine; $i < $endLine; $i++) {
                $marker = ($i == $errorLine - 1) ? " >> " : "    ";
                echo $marker . ($i + 1) . ": " . trim($configLines[$i]) . "\n";
            }
            
            unlink($testFile);
            break;
        } catch (ParseError $e) {
            ob_get_clean();
            echo "  PARSE ERROR at $size lines: " . $e->getMessage() . "\n";
            unlink($testFile);
            break;
        }
        
        unlink($testFile);
    }
    
} catch (Exception $e) {
    echo "Progressive test failed: " . $e->getMessage() . "\n";
}

echo "Progressive test complete\n";
?>
