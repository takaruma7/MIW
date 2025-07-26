<?php
/**
 * Simple Project Health Check
 */
echo "=== MIW PROJECT HEALTH CHECK ===\n";

// Test 1: PHP Version
echo "PHP Version: " . PHP_VERSION . "\n";

// Test 2: Config file
echo "\nTesting config.php...\n";
try {
    if (file_exists('config.php')) {
        echo "✅ config.php exists\n";
        
        // Test include without execution
        $configContent = file_get_contents('config.php');
        if (strpos($configContent, '$conn') !== false) {
            echo "✅ Database connection variable found\n";
        } else {
            echo "❌ Database connection variable not found\n";
        }
        
        if (strpos($configContent, 'PDO') !== false) {
            echo "✅ PDO reference found\n";
        } else {
            echo "❌ PDO reference not found\n";
        }
    } else {
        echo "❌ config.php not found\n";
    }
} catch (Exception $e) {
    echo "❌ Config test error: " . $e->getMessage() . "\n";
}

// Test 3: Critical files
echo "\nTesting critical files...\n";
$criticalFiles = [
    'upload_handler.php',
    'confirm_payment.php', 
    'admin_dashboard.php',
    'form_haji.php',
    'form_umroh.php'
];

foreach ($criticalFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file\n";
    } else {
        echo "❌ $file MISSING\n";
    }
}

// Test 4: Directories
echo "\nTesting directories...\n";
$directories = ['uploads', 'error_logs', 'temp'];

foreach ($directories as $dir) {
    if (is_dir($dir)) {
        echo "✅ $dir/ exists\n";
    } else {
        echo "⚠️ $dir/ missing (will be created on demand)\n";
    }
}

// Test 5: Extensions
echo "\nTesting PHP extensions...\n";
$extensions = ['pdo', 'mbstring', 'fileinfo', 'gd'];

foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ $ext extension loaded\n";
    } else {
        echo "❌ $ext extension MISSING\n";
    }
}

echo "\n=== HEALTH CHECK COMPLETE ===\n";
echo "Run this from browser for detailed HTML report.\n";
?>
