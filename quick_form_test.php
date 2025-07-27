<?php
/**
 * Quick Form Submission Test
 * Tests actual form submission with timeout monitoring
 */

set_time_limit(30);
ini_set('max_execution_time', 30);

require_once 'config.php';

echo '<!DOCTYPE html>
<html>
<head>
    <title>Quick Form Test - MIW</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-box { background: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #e8f5e8; border-left: 4px solid #4caf50; }
        .error { background: #ffebee; border-left: 4px solid #f44336; }
        .warning { background: #fff3cd; border-left: 4px solid #ff9800; }
        .timing { font-family: monospace; color: #666; }
    </style>
</head>
<body>';

echo '<h1>🧪 Quick Form Submission Test</h1>';

function testFormSubmission() {
    global $conn;
    
    $startTime = microtime(true);
    
    echo '<div class="test-box">';
    echo '<h2>Testing Form Submission Flow</h2>';
    
    // Test 1: Check if form pages load
    $forms = ['form_haji.php', 'form_umroh.php'];
    foreach ($forms as $form) {
        $testStart = microtime(true);
        
        if (file_exists($form)) {
            // Test if form loads without errors
            ob_start();
            $error = null;
            try {
                include $form;
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            $output = ob_get_clean();
            
            $testTime = round(microtime(true) - $testStart, 3);
            
            if ($error) {
                echo "<div class='error'>❌ {$form}: Error - {$error} <span class='timing'>({$testTime}s)</span></div>";
            } elseif ($testTime > 2) {
                echo "<div class='warning'>⏰ {$form}: SLOW - {$testTime}s</div>";
            } else {
                echo "<div class='success'>✅ {$form}: OK <span class='timing'>({$testTime}s)</span></div>";
            }
        } else {
            echo "<div class='error'>❌ {$form}: File not found</div>";
        }
    }
    
    // Test 2: Check database connectivity and package data
    $testStart = microtime(true);
    try {
        $stmt = $conn->query("SELECT pak_id, nama_paket, jenis_paket, harga_paket FROM data_paket LIMIT 3");
        $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $testTime = round(microtime(true) - $testStart, 3);
        
        if ($testTime > 1) {
            echo "<div class='warning'>⏰ Database query SLOW: {$testTime}s</div>";
        } else {
            echo "<div class='success'>✅ Database OK: " . count($packages) . " packages found <span class='timing'>({$testTime}s)</span></div>";
        }
        
        foreach ($packages as $pkg) {
            echo "<div style='margin-left: 20px; font-size: 0.9em;'>• {$pkg['nama_paket']} ({$pkg['jenis_paket']}) - Rp " . number_format($pkg['harga_paket']) . "</div>";
        }
        
    } catch (Exception $e) {
        $testTime = round(microtime(true) - $testStart, 3);
        echo "<div class='error'>❌ Database Error: " . $e->getMessage() . " <span class='timing'>({$testTime}s)</span></div>";
    }
    
    // Test 3: Simulate form data submission
    echo '<h3>🎯 Simulating Form Submission</h3>';
    
    $testData = [
        'nik' => '3210123456789001',
        'nama' => 'Emergency Test User',
        'nama_ayah' => 'Test Father',
        'nama_ibu' => 'Test Mother',
        'tempat_lahir' => 'Jakarta',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'alamat' => 'Jl. Test No. 123',
        'no_telp' => '081234567890',
        'email' => 'test@example.com',
        'pak_id' => isset($packages[0]) ? $packages[0]['pak_id'] : 1,
        'type_room_pilihan' => 'Quad',
        'payment_method' => 'BSI',
        'payment_type' => 'Lunas'
    ];
    
    // Test insertion into data_jemaah
    $testStart = microtime(true);
    try {
        $sql = "INSERT INTO data_jemaah (
            nik, nama, nama_ayah, nama_ibu, tempat_lahir, tanggal_lahir,
            jenis_kelamin, alamat, no_telp, email, pak_id, type_room_pilihan,
            payment_method, payment_type, status, request_khusus
        ) VALUES (
            :nik, :nama, :nama_ayah, :nama_ibu, :tempat_lahir, :tanggal_lahir,
            :jenis_kelamin, :alamat, :no_telp, :email, :pak_id, :type_room_pilihan,
            :payment_method, :payment_type, 'pending', 'Emergency test - safe to delete'
        )";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($testData);
        
        $newId = $conn->lastInsertId();
        $testTime = round(microtime(true) - $testStart, 3);
        
        if ($testTime > 2) {
            echo "<div class='warning'>⏰ Database INSERT SLOW: {$testTime}s</div>";
        } else {
            echo "<div class='success'>✅ Form submission simulated successfully - ID: {$newId} <span class='timing'>({$testTime}s)</span></div>";
        }
        
        // Cleanup test data
        $conn->prepare("DELETE FROM data_jemaah WHERE jem_id = ?")->execute([$newId]);
        echo "<div style='margin-left: 20px; font-size: 0.9em; color: #666;'>🧹 Test data cleaned up</div>";
        
    } catch (Exception $e) {
        $testTime = round(microtime(true) - $testStart, 3);
        echo "<div class='error'>❌ Form submission simulation failed: " . $e->getMessage() . " <span class='timing'>({$testTime}s)</span></div>";
    }
    
    $totalTime = round(microtime(true) - $startTime, 3);
    echo '</div>';
    
    echo "<div class='test-box'>";
    echo "<h2>📊 Test Summary</h2>";
    echo "<div><strong>Total execution time:</strong> {$totalTime}s</div>";
    
    if ($totalTime > 10) {
        echo "<div class='error'><strong>⚠️ WARNING:</strong> Total time exceeds 10 seconds - Performance issues detected!</div>";
    } elseif ($totalTime > 5) {
        echo "<div class='warning'><strong>⚠️ CAUTION:</strong> Execution time is borderline - Monitor performance</div>";
    } else {
        echo "<div class='success'><strong>✅ GOOD:</strong> Execution time within acceptable limits</div>";
    }
    echo "</div>";
}

// Run the test
testFormSubmission();

echo '<div style="text-align: center; margin: 20px 0;">';
echo '<p><a href="emergency_testing_suite.php">🚨 Run Full Emergency Suite</a> | ';
echo '<a href="admin_dashboard.php">📊 Admin Dashboard</a> | ';
echo '<a href="?refresh=1">🔄 Refresh Test</a></p>';
echo '</div>';

echo '</body></html>';
?>
