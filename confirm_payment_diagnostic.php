<?php
/**
 * Confirm Payment Diagnostic Script
 * Reproduces and analyzes the confirm_payment.php error
 */

error_reporting(E_ALL);
set_time_limit(20); // 20 seconds max
ini_set('max_execution_time', 20);
ini_set('display_errors', 1); // Show all errors for debugging
ini_set('log_errors', 1);

echo "🔍 Confirm Payment Error Diagnostic\n";
echo "====================================\n\n";

try {
    // Step 1: Check if required files exist
    echo "1. Checking required files...\n";
    $requiredFiles = ['config.php', 'email_functions.php', 'confirm_payment.php'];
    foreach ($requiredFiles as $file) {
        if (file_exists($file)) {
            echo "   ✅ {$file} exists\n";
        } else {
            echo "   ❌ {$file} missing\n";
        }
    }

    // Step 2: Load configuration
    echo "\n2. Loading configuration...\n";
    require_once 'config.php';
    echo "   ✅ Config loaded\n";

    // Step 3: Check email configuration
    echo "\n3. Checking email configuration...\n";
    $emailConstants = [
        'EMAIL_ENABLED', 'SMTP_HOST', 'SMTP_USERNAME', 'SMTP_PASSWORD', 
        'SMTP_SECURE', 'SMTP_PORT', 'EMAIL_FROM', 'EMAIL_FROM_NAME', 'ADMIN_EMAIL'
    ];
    
    foreach ($emailConstants as $constant) {
        if (defined($constant)) {
            $value = constant($constant);
            if (in_array($constant, ['SMTP_PASSWORD'])) {
                $value = str_repeat('*', strlen($value));
            } elseif (in_array($constant, ['SMTP_USERNAME', 'EMAIL_FROM', 'ADMIN_EMAIL'])) {
                $value = !empty($value) ? substr($value, 0, 3) . '***' . substr($value, -3) : 'EMPTY';
            }
            echo "   ✅ {$constant}: {$value}\n";
        } else {
            echo "   ❌ {$constant}: NOT DEFINED\n";
        }
    }

    // Step 4: Load email functions
    echo "\n4. Loading email functions...\n";
    require_once 'email_functions.php';
    echo "   ✅ Email functions loaded\n";

    // Step 5: Check if sendPaymentConfirmationEmail function exists
    echo "\n5. Checking email function...\n";
    if (function_exists('sendPaymentConfirmationEmail')) {
        echo "   ✅ sendPaymentConfirmationEmail function exists\n";
    } else {
        echo "   ❌ sendPaymentConfirmationEmail function NOT found\n";
    }

    // Step 6: Test email function with sample data
    echo "\n6. Testing email function with sample data...\n";
    $testPaymentData = [
        'nama' => 'Test User',
        'no_telp' => '081234567890',
        'email' => 'test@example.com',
        'program_pilihan' => 'Haji Test',
        'tanggal_keberangkatan' => '2026-01-01',
        'biaya_paket' => 50000000,
        'type_room_pilihan' => 'Quad',
        'transfer_account_name' => 'Test Transfer',
        'payment_time' => date('H:i:s'),
        'payment_date' => date('Y-m-d'),
        'payment_type' => 'DP',
        'payment_method' => 'BSI',
        'currency' => 'IDR'
    ];

    try {
        $emailResult = sendPaymentConfirmationEmail($testPaymentData, [], 'Haji');
        if ($emailResult['success']) {
            echo "   ✅ Email function test: SUCCESS - {$emailResult['message']}\n";
        } else {
            echo "   ⚠️  Email function test: FAILED - {$emailResult['message']}\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Email function test: ERROR - " . $e->getMessage() . "\n";
        echo "   📍 Error on line: " . $e->getLine() . "\n";
        echo "   📁 Error in file: " . $e->getFile() . "\n";
    }

    // Step 7: Check PHPMailer
    echo "\n7. Checking PHPMailer...\n";
    try {
        $mail = new PHPMailer(true);
        echo "   ✅ PHPMailer instantiated successfully\n";
    } catch (Exception $e) {
        echo "   ❌ PHPMailer error: " . $e->getMessage() . "\n";
    }

    // Step 8: Check database connection
    echo "\n8. Checking database connection...\n";
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) FROM data_jamaah");
            $count = $stmt->fetchColumn();
            echo "   ✅ Database connected: {$count} jamaah records\n";
        } catch (Exception $e) {
            echo "   ❌ Database query error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ❌ Database connection not available\n";
    }

} catch (Exception $e) {
    echo "\n❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "📍 Error on line: " . $e->getLine() . "\n";
    echo "📁 Error in file: " . $e->getFile() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n📊 DIAGNOSTIC COMPLETE\n";
echo "======================\n";
echo "If you see any ❌ errors above, those are the issues causing confirm_payment.php to fail.\n";
echo "Most likely causes:\n";
echo "1. Missing or undefined email constants\n";
echo "2. PHPMailer configuration issues\n";
echo "3. SMTP authentication problems\n";
echo "4. Missing email_functions.php functions\n";
?>
