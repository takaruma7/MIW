<?php
/**
 * Enhanced Comprehensive Testing for MIW Travel Management System
 * Advanced testing suite with detailed error reporting and time limits
 */

set_time_limit(20); // 20 seconds max
ini_set('max_execution_time', 20);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<!DOCTYPE html><html><head><title>Enhanced Testing - MIW</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}</style></head><body>";
echo "<h1>🧪 Enhanced Comprehensive Testing Suite</h1>";

$startTime = microtime(true);

try {
    // Load configuration
    require_once 'config.php';
    echo "<p>✓ Config loaded successfully</p>";
    
    // Test 1: Database Connectivity
    echo "<h2>1. Database Connectivity Test</h2>";
    if (isset($conn) && $conn instanceof PDO) {
        echo "<p>✓ Database connection established</p>";
        
        // Test essential tables
        $tables = ['data_paket', 'data_jamaah', 'data_invoice'];
        foreach ($tables as $table) {
            try {
                $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                $result = $stmt->fetch();
                echo "<p>✓ Table $table: {$result['count']} records</p>";
            } catch (Exception $e) {
                echo "<p>✗ Table $table: Error - " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    } else {
        echo "<p>✗ Database connection failed</p>";
    }
    
    // Test 2: File System
    echo "<h2>2. File System Test</h2>";
    $criticalFiles = [
        'config.php', 'email_functions.php', 'upload_handler.php',
        'form_haji.php', 'form_umroh.php', 'admin_dashboard.php'
    ];
    
    foreach ($criticalFiles as $file) {
        if (file_exists($file)) {
            echo "<p>✓ $file exists</p>";
        } else {
            echo "<p>✗ $file missing</p>";
        }
    }
    
    // Test 3: Email Configuration
    echo "<h2>3. Email Configuration Test</h2>";
    $emailConstants = ['EMAIL_ENABLED', 'SMTP_HOST', 'SMTP_PORT'];
    foreach ($emailConstants as $constant) {
        if (defined($constant)) {
            echo "<p>✓ $constant is defined</p>";
        } else {
            echo "<p>✗ $constant is NOT defined</p>";
        }
    }
    
    // Test 4: Upload Directories
    echo "<h2>4. Upload Directory Test</h2>";
    $uploadDirs = ['uploads/documents', 'uploads/payments', 'uploads/cancellations'];
    foreach ($uploadDirs as $dir) {
        if (is_dir($dir)) {
            echo "<p>✓ Directory $dir exists</p>";
        } else {
            echo "<p>⚠ Directory $dir missing (will be created on upload)</p>";
        }
    }
    
    $executionTime = round(microtime(true) - $startTime, 3);
    echo "<div style='background:#e8f5e8;padding:15px;margin:20px 0;border-radius:5px;'>";
    echo "<h3>✅ Enhanced Testing Complete!</h3>";
    echo "<p>Execution time: {$executionTime} seconds</p>";
    echo "<p>All critical components tested within time limit.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;margin:20px 0;border-radius:5px;'>";
    echo "<h3>❌ Testing Error</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
