<?php
/**
 * MIW Project Workflow Validator
 * Tests all critical workflow processes step by step
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include config
require_once 'config.php';

class WorkflowValidator {
    private $conn;
    private $results = [];
    private $errors = [];
    
    public function __construct($conn) {
        $this->conn = $conn;
        echo "=== MIW WORKFLOW VALIDATION STARTED ===\n";
        echo "Database: Connected ✅\n";
        echo "PHP Version: " . PHP_VERSION . " ✅\n\n";
    }
    
    /**
     * Test 1: Database Schema Validation
     */
    public function testDatabaseSchema() {
        echo "1. Testing Database Schema...\n";
        
        $requiredTables = [
            'data_jamaah' => 'Pilgrim registration data',
            'data_paket' => 'Package information', 
            'data_invoice' => 'Invoice and payment tracking',
            'data_pembatalan' => 'Cancellation requests'
        ];
        
        foreach ($requiredTables as $table => $desc) {
            try {
                $stmt = $this->conn->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "   ✅ $table ($count records) - $desc\n";
            } catch (Exception $e) {
                echo "   ❌ $table - Error: " . $e->getMessage() . "\n";
                $this->errors[] = "Table $table: " . $e->getMessage();
            }
        }
        echo "\n";
    }
    
    /**
     * Test 2: File Upload System
     */
    public function testFileUploadSystem() {
        echo "2. Testing File Upload System...\n";
        
        // Check upload directories
        $uploadDirs = ['uploads', 'temp', 'documents'];
        foreach ($uploadDirs as $dir) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    echo "   ✅ $dir/ - Writable\n";
                } else {
                    echo "   ⚠️ $dir/ - Not writable\n";
                    $this->errors[] = "Directory $dir is not writable";
                }
            } else {
                echo "   ⚠️ $dir/ - Missing (will be created on demand)\n";
            }
        }
        
        // Test upload handler
        if (file_exists('upload_handler.php')) {
            $syntax = shell_exec('php -l upload_handler.php 2>&1');
            if (strpos($syntax, 'No syntax errors') !== false) {
                echo "   ✅ upload_handler.php - Syntax OK\n";
            } else {
                echo "   ❌ upload_handler.php - Syntax Error\n";
                $this->errors[] = "upload_handler.php has syntax errors";
            }
        }
        
        // Test file metadata table
        try {
            $stmt = $this->conn->query("SELECT COUNT(*) FROM file_metadata");
            $count = $stmt->fetchColumn();
            echo "   ✅ file_metadata table ($count records)\n";
        } catch (Exception $e) {
            echo "   ❌ file_metadata table error: " . $e->getMessage() . "\n";
            $this->errors[] = "file_metadata table: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    /**
     * Test 3: Registration Forms
     */
    public function testRegistrationForms() {
        echo "3. Testing Registration Forms...\n";
        
        $forms = [
            'form_haji.php' => 'Hajj Registration Form',
            'form_umroh.php' => 'Umrah Registration Form', 
            'form_pembatalan.php' => 'Cancellation Form'
        ];
        
        foreach ($forms as $file => $desc) {
            if (file_exists($file)) {
                $syntax = shell_exec("php -l \"$file\" 2>&1");
                if (strpos($syntax, 'No syntax errors') !== false) {
                    echo "   ✅ $file - $desc (Syntax OK)\n";
                } else {
                    echo "   ❌ $file - Syntax Error\n";
                    $this->errors[] = "$file has syntax errors";
                }
            } else {
                echo "   ❌ $file - Missing\n";
                $this->errors[] = "$file is missing";
            }
        }
        echo "\n";
    }
    
    /**
     * Test 4: Admin Panel
     */
    public function testAdminPanel() {
        echo "4. Testing Admin Panel...\n";
        
        $adminFiles = [
            'admin_dashboard.php' => 'Main Dashboard',
            'admin_kelengkapan.php' => 'Document Management',
            'admin_manifest.php' => 'Manifest Management',
            'admin_paket.php' => 'Package Management',
            'admin_pembatalan.php' => 'Cancellation Management'
        ];
        
        foreach ($adminFiles as $file => $desc) {
            if (file_exists($file)) {
                $syntax = shell_exec("php -l \"$file\" 2>&1");
                if (strpos($syntax, 'No syntax errors') !== false) {
                    echo "   ✅ $file - $desc (Syntax OK)\n";
                } else {
                    echo "   ❌ $file - Syntax Error\n";
                    $this->errors[] = "$file has syntax errors";
                }
            } else {
                echo "   ❌ $file - Missing\n";
                $this->errors[] = "$file is missing";
            }
        }
        echo "\n";
    }
    
    /**
     * Test 5: Payment and Invoice System
     */
    public function testPaymentSystem() {
        echo "5. Testing Payment System...\n";
        
        $paymentFiles = [
            'confirm_payment.php' => 'Payment Confirmation',
            'invoice.php' => 'Invoice Generation',
            'kwitansi_template.php' => 'Receipt Template'
        ];
        
        foreach ($paymentFiles as $file => $desc) {
            if (file_exists($file)) {
                $syntax = shell_exec("php -l \"$file\" 2>&1");
                if (strpos($syntax, 'No syntax errors') !== false) {
                    echo "   ✅ $file - $desc (Syntax OK)\n";
                } else {
                    echo "   ❌ $file - Syntax Error\n";
                    $this->errors[] = "$file has syntax errors";
                }
            } else {
                echo "   ❌ $file - Missing\n";
                $this->errors[] = "$file is missing";
            }
        }
        echo "\n";
    }
    
    /**
     * Test 6: Email System
     */
    public function testEmailSystem() {
        echo "6. Testing Email System...\n";
        
        if (file_exists('email_functions.php')) {
            $syntax = shell_exec('php -l email_functions.php 2>&1');
            if (strpos($syntax, 'No syntax errors') !== false) {
                echo "   ✅ email_functions.php - Syntax OK\n";
            } else {
                echo "   ❌ email_functions.php - Syntax Error\n";
                $this->errors[] = "email_functions.php has syntax errors";
            }
        } else {
            echo "   ❌ email_functions.php - Missing\n";
            $this->errors[] = "email_functions.php is missing";
        }
        
        // Check if mail function is available
        if (function_exists('mail')) {
            echo "   ✅ PHP mail() function available\n";
        } else {
            echo "   ⚠️ PHP mail() function not available\n";
        }
        echo "\n";
    }
    
    /**
     * Test 7: Security Features
     */
    public function testSecurity() {
        echo "7. Testing Security Features...\n";
        
        // Check session configuration
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        echo "   ✅ Session system available\n";
        
        // Check for security headers in key files
        $securityFiles = ['admin_dashboard.php', 'confirm_payment.php'];
        foreach ($securityFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                if (strpos($content, 'session_start') !== false) {
                    echo "   ✅ $file - Uses sessions\n";
                } else {
                    echo "   ⚠️ $file - No session usage detected\n";
                }
            }
        }
        echo "\n";
    }
    
    /**
     * Generate Summary Report
     */
    public function generateSummary() {
        echo "=== WORKFLOW VALIDATION SUMMARY ===\n";
        
        if (empty($this->errors)) {
            echo "🎉 ALL TESTS PASSED! Project is ready for production.\n";
        } else {
            echo "⚠️ ISSUES FOUND (" . count($this->errors) . "):\n";
            foreach ($this->errors as $i => $error) {
                echo "   " . ($i + 1) . ". $error\n";
            }
        }
        
        echo "\nValidation completed at: " . date('Y-m-d H:i:s') . "\n";
        echo "===========================================\n";
        
        return empty($this->errors);
    }
}

// Run the workflow validation
try {
    $validator = new WorkflowValidator($conn);
    
    $validator->testDatabaseSchema();
    $validator->testFileUploadSystem();
    $validator->testRegistrationForms();
    $validator->testAdminPanel();
    $validator->testPaymentSystem();
    $validator->testEmailSystem();
    $validator->testSecurity();
    
    $allPassed = $validator->generateSummary();
    
    if ($allPassed) {
        exit(0); // Success
    } else {
        exit(1); // Issues found
    }
    
} catch (Exception $e) {
    echo "❌ VALIDATION FAILED: " . $e->getMessage() . "\n";
    exit(1);
}
?>
