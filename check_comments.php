<?php
/**
 * Check for unterminated comments in config.php
 */

set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Checking for comment termination issues...\n";

$configContent = file_get_contents('config.php');
$lines = explode("\n", $configContent);

$inMultiLineComment = false;
$commentStartLine = 0;

for ($i = 0; $i < count($lines); $i++) {
    $line = trim($lines[$i]);
    $lineNum = $i + 1;
    
    // Check for comment start
    if (strpos($line, '/**') !== false || strpos($line, '/*') !== false) {
        if ($inMultiLineComment) {
            echo "WARNING: Comment start found while already in comment at line $lineNum\n";
        }
        $inMultiLineComment = true;
        $commentStartLine = $lineNum;
        echo "Comment started at line $lineNum: $line\n";
    }
    
    // Check for comment end
    if (strpos($line, '*/') !== false) {
        if (!$inMultiLineComment) {
            echo "WARNING: Comment end found without start at line $lineNum\n";
        } else {
            echo "Comment ended at line $lineNum (started at line $commentStartLine)\n";
        }
        $inMultiLineComment = false;
    }
    
    // Check if we're at line 50 and still in comment
    if ($lineNum == 50 && $inMultiLineComment) {
        echo "ISSUE: Still in comment at line 50!\n";
        echo "Comment started at line $commentStartLine\n";
        echo "Line 50: $line\n";
        break;
    }
}

if ($inMultiLineComment) {
    echo "ERROR: Unterminated comment starting at line $commentStartLine\n";
    echo "Last few lines:\n";
    for ($i = max(0, count($lines) - 5); $i < count($lines); $i++) {
        echo "  " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
    }
}

echo "Comment check complete\n";
?>
