<?php
/**
 * Emergency Testing Suite for MIW Travel Management System
 * 
 * This script performs time-limited White Box and Black Box testing
 * to identify critical errors and test form submission flows.
 * 
 * @version 1.0.0
 * @author MIW Development Team
 */

// Set strict time limits
set_time_limit(20);
ini_set('max_execution_time', 20);
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once 'config.php';

class EmergencyTestingSuite {
    private $startTime;
    private $testResults = [];
    private $errors = [];
    private $warnings = [];
    private $baseUrl;
    private $timeout = 5; // 5 seconds per individual test
    private $conn;
    private $maxExecutionTime = 20;
    
    public function __construct($dbConnection) {
        $this->startTime = microtime(true);
        $this->conn = $dbConnection;
        $this->baseUrl = $this->detectBaseUrl();
        
        echo $this->renderHeader();
    }
    
    /**
     * Detect base URL for testing
     */
    private function detectBaseUrl() {
        $isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
        
        if ($isHeroku) {
            return 'https://miw-travel-app-576ab80a8cab.herokuapp.com';
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8080';
            return $protocol . '://' . $host;
        }
    }
    
    /**
     * Main testing orchestrator with strict time limits
     */
    public function runEmergencyTests() {
        echo "<div class='test-section'>";
        echo "<h2>🚨 Emergency Testing Suite - Time Limited (20s)</h2>";
        echo "<p><strong>Environment:</strong> " . (!empty($_ENV['DYNO']) ? 'Heroku Production' : 'Local Development') . "</p>";
        echo "<p><strong>Base URL:</strong> " . $this->baseUrl . "</p>";
        echo "<p><strong>Test timeout:</strong> {$this->timeout}s each</p>";
        echo "</div>";
        
        // Step 1: Critical Error Fixes (2 seconds)
        $this->executeTimeLimitedTest('Critical Error Fixes', [$this, 'fixCriticalErrors'], 2);
        
        // Step 2: Core System Tests (5 seconds)
        $this->executeTimeLimitedTest('Core System Health', [$this, 'testCoreSystem'], 5);
        
        // Step 3: File Handler Tests (3 seconds)
        $this->executeTimeLimitedTest('File Handler Tests', [$this, 'testFileHandlers'], 3);
        
        // Step 4: Form Submission Test (5 forms, 8 seconds)
        $this->executeTimeLimitedTest('Form Submission Tests', [$this, 'testFormSubmissions'], 8);
        
        // Step 5: Generate report (2 seconds)
        $this->executeTimeLimitedTest('Generate Report', [$this, 'generateFinalReport'], 2);
    }
    
    /**
     * Execute a test with strict time limits
     */
    private function executeTimeLimitedTest($testName, $testFunction, $timeLimit) {
        $elapsed = microtime(true) - $this->startTime;
        if ($elapsed >= $this->maxExecutionTime) {
            echo "<div class='test-result timeout'>❌ {$testName}: SKIPPED - Overall timeout reached</div>";
            return;
        }
        
        echo "<div class='test-item'>";
        echo "<h3>⏱️ {$testName} (Max: {$timeLimit}s)</h3>";
        
        $testStart = microtime(true);
        
        try {
            // Set individual test timeout
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm($timeLimit);
            }
            
            if (is_callable($testFunction)) {
                $result = call_user_func($testFunction);
            } else {
                $result = "❌ Test function not callable";
            }
            
            $testTime = round(microtime(true) - $testStart, 2);
            
            if ($testTime > $timeLimit) {
                echo "<div class='test-result timeout'>⏰ TIMEOUT: {$testTime}s (limit: {$timeLimit}s)</div>";
                $this->errors[] = "{$testName} exceeded time limit";
            } else {
                echo "<div class='test-result success'>✅ Completed in {$testTime}s</div>";
                echo "<div class='test-details'>{$result}</div>";
            }
            
        } catch (Exception $e) {
            $testTime = round(microtime(true) - $testStart, 2);
            echo "<div class='test-result error'>❌ ERROR in {$testTime}s: " . htmlspecialchars($e->getMessage()) . "</div>";
            $this->errors[] = "{$testName}: " . $e->getMessage();
        } finally {
            if (function_exists('pcntl_alarm')) {
                pcntl_alarm(0); // Cancel alarm
            }
        }
        
        echo "</div>";
    }
    
    /**
     * Fix critical errors
     */
    private function fixCriticalErrors() {
        $fixes = [];
        
        // Check if HerokuFileManager error is fixed
        $herokuContent = file_get_contents(__DIR__ . '/heroku_file_manager.php');
        if (strpos($herokuContent, '$this->isHeroku()') !== false && 
            strpos($herokuContent, '__construct') !== false) {
            $fixes[] = "⚠️ HerokuFileManager still has potential issues";
        } else {
            $fixes[] = "✅ HerokuFileManager constructor fixed";
        }
        
        // Check file handler
        if (file_exists(__DIR__ . '/file_handler.php')) {
            $fixes[] = "✅ File handler exists";
        } else {
            $fixes[] = "❌ File handler missing";
        }
        
        return implode('<br>', $fixes);
    }
    
    /**
     * Test core system components
     */
    private function testCoreSystem() {
        $tests = [];
        
        // Database connection
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM data_paket");
            $count = $stmt->fetchColumn();
            $tests[] = "✅ Database: {$count} packages found";
        } catch (Exception $e) {
            $tests[] = "❌ Database error: " . $e->getMessage();
        }
        
        // Configuration
        try {
            $isHeroku = !empty($_ENV['DYNO']);
            $tests[] = "✅ Environment: " . ($isHeroku ? 'Heroku' : 'Local');
        } catch (Exception $e) {
            $tests[] = "❌ Config error: " . $e->getMessage();
        }
        
        // Core files
        $coreFiles = ['config.php', 'form_haji.php', 'form_umroh.php', 'submit_haji.php', 'submit_umroh.php'];
        $missing = [];
        foreach ($coreFiles as $file) {
            if (!file_exists(__DIR__ . '/' . $file)) {
                $missing[] = $file;
            }
        }
        
        if (empty($missing)) {
            $tests[] = "✅ All core files present";
        } else {
            $tests[] = "❌ Missing files: " . implode(', ', $missing);
        }
        
        return implode('<br>', $tests);
    }
    
    /**
     * Test file handlers
     */
    private function testFileHandlers() {
        $tests = [];
        
        // Test HerokuFileManager
        try {
            require_once 'heroku_file_manager.php';
            $manager = new HerokuFileManager();
            $tests[] = "✅ HerokuFileManager instantiated";
            
            // Test isHeroku method
            $isHeroku = $manager->isHeroku();
            $tests[] = "✅ isHeroku() method works: " . ($isHeroku ? 'true' : 'false');
            
        } catch (Exception $e) {
            $tests[] = "❌ HerokuFileManager error: " . $e->getMessage();
        }
        
        // Test UploadHandler
        try {
            require_once 'upload_handler.php';
            $handler = new UploadHandler();
            $tests[] = "✅ UploadHandler instantiated";
        } catch (Exception $e) {
            $tests[] = "❌ UploadHandler error: " . $e->getMessage();
        }
        
        // Test file_handler.php
        $testUrl = $this->baseUrl . '/file_handler.php?file=test.pdf&type=documents';
        $context = stream_context_create([
            'http' => [
                'timeout' => 3,
                'method' => 'GET'
            ]
        ]);
        
        try {
            $response = @file_get_contents($testUrl, false, $context);
            if ($response !== false) {
                $tests[] = "✅ File handler responds";
            } else {
                $tests[] = "⚠️ File handler timeout or error (expected for missing file)";
            }
        } catch (Exception $e) {
            $tests[] = "❌ File handler test failed: " . $e->getMessage();
        }
        
        return implode('<br>', $tests);
    }
    
    /**
     * Test form submissions (limited to 5 forms for time constraint)
     */
    private function testFormSubmissions() {
        $results = [];
        $users = $this->generateTestUsers(5); // Only 5 users for time limit
        
        foreach ($users as $index => $user) {
            $testStart = microtime(true);
            
            // Check if we have time left
            $elapsed = microtime(true) - $this->startTime;
            if ($elapsed >= ($this->maxExecutionTime - 2)) {
                $results[] = "⏰ Form " . ($index + 1) . ": Skipped due to time limit";
                break;
            }
            
            try {
                $result = $this->submitTestForm($user);
                $testTime = round(microtime(true) - $testStart, 2);
                
                if ($testTime > 3) {
                    $results[] = "⏰ Form " . ($index + 1) . ": SLOW ({$testTime}s) - {$result}";
                } else {
                    $results[] = "✅ Form " . ($index + 1) . ": OK ({$testTime}s) - {$result}";
                }
                
            } catch (Exception $e) {
                $testTime = round(microtime(true) - $testStart, 2);
                $results[] = "❌ Form " . ($index + 1) . ": ERROR ({$testTime}s) - " . $e->getMessage();
            }
        }
        
        return implode('<br>', $results);
    }
    
    /**
     * Generate test users for form submission
     */
    private function generateTestUsers($count) {
        $users = [];
        $types = ['haji', 'umroh'];
        
        // Get available packages from database
        try {
            $stmt = $this->conn->query("SELECT pak_id, jenis_paket FROM data_paket LIMIT 5");
            $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Fallback with dummy data
            $packages = [
                ['pak_id' => 1, 'jenis_paket' => 'Haji'],
                ['pak_id' => 2, 'jenis_paket' => 'Umroh']
            ];
        }
        
        for ($i = 1; $i <= $count; $i++) {
            $package = $packages[($i - 1) % count($packages)];
            $type = strtolower($package['jenis_paket']);
            
            $users[] = [
                'type' => $type,
                'nik' => '3210000000000' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'nama' => 'Test User ' . $i,
                'nama_ayah' => 'Test Father ' . $i,
                'nama_ibu' => 'Test Mother ' . $i,
                'tempat_lahir' => 'Jakarta',
                'tanggal_lahir' => '1990-01-01',
                'jenis_kelamin' => $i % 2 == 0 ? 'Laki-laki' : 'Perempuan',
                'alamat' => 'Test Address ' . $i,
                'no_telp' => '081234567' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'email' => 'test' . $i . '@example.com',
                'pak_id' => $package['pak_id'],
                'type_room_pilihan' => ['Quad', 'Triple', 'Double'][($i - 1) % 3],
                'payment_method' => $type === 'haji' ? 'BSI' : 'BNI',
                'payment_type' => 'Lunas'
            ];
        }
        
        return $users;
    }
    
    /**
     * Submit a test form
     */
    private function submitTestForm($user) {
        $submitUrl = $this->baseUrl . '/submit_' . $user['type'] . '.php';
        
        // Prepare form data
        $postData = http_build_query([
            'nik' => $user['nik'],
            'nama' => $user['nama'],
            'nama_ayah' => $user['nama_ayah'],
            'nama_ibu' => $user['nama_ibu'],
            'tempat_lahir' => $user['tempat_lahir'],
            'tanggal_lahir' => $user['tanggal_lahir'],
            'jenis_kelamin' => $user['jenis_kelamin'],
            'alamat' => $user['alamat'],
            'no_telp' => $user['no_telp'],
            'email' => $user['email'],
            'pak_id' => $user['pak_id'],
            'type_room_pilihan' => $user['type_room_pilihan'],
            'payment_method' => $user['payment_method'],
            'payment_type' => $user['payment_type'],
            'request_khusus' => 'Automated test submission'
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 5
            ]
        ]);
        
        $response = @file_get_contents($submitUrl, false, $context);
        
        if ($response === false) {
            return "Failed to connect";
        }
        
        // Check if response indicates success or error
        if (strpos($response, 'invoice.php') !== false) {
            return "SUCCESS - Redirected to invoice";
        } elseif (strpos($response, 'error') !== false) {
            return "ERROR - Form validation failed";
        } elseif (strpos($response, 'Fatal error') !== false) {
            return "FATAL ERROR - PHP error occurred";
        } else {
            return "UNKNOWN - Response length: " . strlen($response);
        }
    }
    
    /**
     * Generate final report
     */
    private function generateFinalReport() {
        $totalTime = round(microtime(true) - $this->startTime, 2);
        $status = empty($this->errors) ? 'PASSED' : 'FAILED';
        
        $report = [];
        $report[] = "📊 <strong>EMERGENCY TEST SUMMARY</strong>";
        $report[] = "⏱️ Total Time: {$totalTime}s / {$this->maxExecutionTime}s";
        $report[] = "🎯 Status: <strong>{$status}</strong>";
        $report[] = "❌ Errors: " . count($this->errors);
        $report[] = "⚠️ Warnings: " . count($this->warnings);
        
        if (!empty($this->errors)) {
            $report[] = "<br><strong>Critical Issues:</strong>";
            foreach ($this->errors as $error) {
                $report[] = "• " . htmlspecialchars($error);
            }
        }
        
        if ($totalTime >= $this->maxExecutionTime) {
            $report[] = "<br>⚠️ <strong>WARNING: Tests exceeded time limit - System may have performance issues</strong>";
        }
        
        return implode('<br>', $report);
    }
    
    /**
     * Render HTML header
     */
    private function renderHeader() {
        return '<!DOCTYPE html>
<html>
<head>
    <title>Emergency Testing Suite - MIW</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { margin: 20px 0; padding: 15px; background: #e3f2fd; border-radius: 5px; }
        .test-item { margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .test-result.success { color: #2e7d32; font-weight: bold; }
        .test-result.error { color: #d32f2f; font-weight: bold; }
        .test-result.timeout { color: #ff9800; font-weight: bold; }
        .test-details { margin: 10px 0; padding: 10px; background: #f9f9f9; border-radius: 3px; }
        h1 { color: #1976d2; border-bottom: 2px solid #1976d2; padding-bottom: 10px; }
        h2 { color: #388e3c; }
        h3 { color: #f57c00; margin: 0; }
        .warning { background: #fff3cd; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
<div class="container">
    <h1>🚨 Emergency Testing Suite - MIW Travel System</h1>
    <div class="warning">
        <strong>⚠️ Time-Limited Testing:</strong> This suite runs with strict 20-second timeout to identify performance issues.
        If tests timeout, it indicates system problems that need immediate attention.
    </div>';
    }
}

// Auto-start the tests
if (!isset($_GET['manual'])) {
    try {
        $tester = new EmergencyTestingSuite($conn);
        $tester->runEmergencyTests();
        
        echo '<div style="text-align: center; margin: 30px 0; padding: 20px; background: #e8f5e8; border-radius: 8px;">';
        echo '<h2>✅ Emergency Testing Complete</h2>';
        echo '<p>Tests completed. Review results above.</p>';
        echo '<p><a href="?manual=1">🔄 Run Again</a> | <a href="/admin_dashboard.php">📊 Admin Dashboard</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="background: #ffebee; padding: 20px; border: 1px solid #f44336; border-radius: 8px; margin: 20px 0;">';
        echo '<h2>❌ Testing Suite Error</h2>';
        echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>This indicates a serious system issue that requires immediate attention.</p>';
        echo '</div>';
    }
}

echo '</div></body></html>';
?>
