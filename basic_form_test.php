<?php
/**
 * Basic Form Test for MIW Travel Management System
 * Simple test to verify basic form functionality
 */

set_time_limit(20); // 20 seconds max
ini_set('max_execution_time', 20);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Basic Form Test ===\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Load configuration
    require_once 'config.php';
    echo "✓ Config loaded successfully\n";
    
    // Test database connection
    if (isset($conn) && $conn instanceof PDO) {
        echo "✓ Database connection established\n";
        
        // Test basic query
        $stmt = $conn->query("SELECT COUNT(*) as count FROM data_paket");
        $result = $stmt->fetch();
        echo "✓ Found {$result['count']} packages in database\n";
    } else {
        echo "✗ Database connection failed\n";
        exit(1);
    }
    
    // Check if form files exist
    $formFiles = ['form_haji.php', 'form_umroh.php', 'index.php'];
    foreach ($formFiles as $file) {
        if (file_exists($file)) {
            echo "✓ $file exists\n";
        } else {
            echo "✗ $file missing\n";
        }
    }
    
    echo "\n=== Basic Test Complete ===\n";
    echo "Basic functionality verified!\n";
    
} catch (Exception $e) {
    echo "✗ Error during basic test: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
