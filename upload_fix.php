<?php
/**
 * Upload System Fix for Heroku
 * This script fixes issues with upload handling on the deployed system
 */

require_once 'config.php';

// Set error reporting for diagnostics
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Upload System Fix</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    code { background: #f5f5f5; padding: 2px 5px; border-radius: 3px; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow: auto; }
    h1, h2 { border-bottom: 1px solid #ddd; padding-bottom: 10px; }
    .section { margin: 30px 0; }
</style>";
echo "</head><body><div class='container'>";
echo "<h1>Upload System Fix</h1>";
echo "<p>This script fixes issues with the file upload system on Heroku deployment.</p>";

// 1. Check environment
echo "<div class='section'>";
echo "<h2>1. Environment Check</h2>";
$isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
echo "<p>Running on: <strong>" . ($isHeroku ? 'Heroku' : 'Local/Development') . "</strong></p>";
echo "<p>PHP Version: <strong>" . PHP_VERSION . "</strong></p>";
echo "</div>";

// 2. Create file_metadata table if needed
echo "<div class='section'>";
echo "<h2>2. File Metadata Table</h2>";

try {
    // Check if file_metadata table exists
    try {
        $stmt = $conn->prepare("SELECT 1 FROM file_metadata LIMIT 1");
        $stmt->execute();
        echo "<p class='success'>✓ file_metadata table exists</p>";
        $tableExists = true;
    } catch (PDOException $e) {
        echo "<p class='warning'>⚠ file_metadata table does not exist</p>";
        $tableExists = false;
    }
    
    // Create table if it doesn't exist
    if (!$tableExists) {
        $stmt = $conn->prepare("
            CREATE TABLE IF NOT EXISTS file_metadata (
                id SERIAL PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                directory VARCHAR(50) NOT NULL,
                original_name VARCHAR(255),
                file_size INTEGER,
                mime_type VARCHAR(100),
                upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                is_heroku BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(filename, directory)
            )
        ");
        $stmt->execute();
        echo "<p class='success'>✓ file_metadata table created successfully</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// 3. Check upload directories
echo "<div class='section'>";
echo "<h2>3. Upload Directories</h2>";

$uploadDirs = [
    ($isHeroku ? '/tmp/uploads' : __DIR__ . '/uploads'),
    ($isHeroku ? '/tmp/uploads/documents' : __DIR__ . '/uploads/documents'),
    ($isHeroku ? '/tmp/uploads/payments' : __DIR__ . '/uploads/payments'),
    ($isHeroku ? '/tmp/uploads/cancellations' : __DIR__ . '/uploads/cancellations')
];

foreach ($uploadDirs as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "<p class='success'>✓ Created directory: " . htmlspecialchars($dir) . "</p>";
        } else {
            echo "<p class='error'>✗ Failed to create directory: " . htmlspecialchars($dir) . "</p>";
        }
    } else {
        echo "<p>Directory exists: " . htmlspecialchars($dir) . "</p>";
        
        // Check if directory is writable
        if (is_writable($dir)) {
            echo "<p class='success'>✓ Directory is writable: " . htmlspecialchars($dir) . "</p>";
        } else {
            echo "<p class='error'>✗ Directory is not writable: " . htmlspecialchars($dir) . "</p>";
            
            // Try to make it writable
            if (chmod($dir, 0777)) {
                echo "<p class='success'>✓ Fixed permissions for: " . htmlspecialchars($dir) . "</p>";
            } else {
                echo "<p class='error'>✗ Could not fix permissions for: " . htmlspecialchars($dir) . "</p>";
            }
        }
    }
}

echo "</div>";

// 4. Fix upload_handler.php issues
echo "<div class='section'>";
echo "<h2>4. Upload Handler Check</h2>";

try {
    require_once 'upload_handler.php';
    $uploadHandler = new UploadHandler();
    
    // Test generating a filename
    $testFilename = $uploadHandler->generateCustomFilename('123456789012', 'test');
    echo "<p class='success'>✓ UploadHandler initialized successfully</p>";
    echo "<p>Test filename: " . htmlspecialchars($testFilename) . "</p>";
    
    // Test the file manager
    echo "<h3>File Manager Test</h3>";
    
    // Check if the file manager is working
    $fileManagerStats = $uploadHandler->getUploadStats();
    echo "<pre>" . print_r($fileManagerStats, true) . "</pre>";
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>This indicates there may be issues with upload_handler.php or heroku_file_manager.php</p>";
}

echo "</div>";

// 5. Test a direct file upload
echo "<div class='section'>";
echo "<h2>5. Test File Upload</h2>";

if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
    try {
        // Ensure upload handler is loaded
        if (!isset($uploadHandler)) {
            require_once 'upload_handler.php';
            $uploadHandler = new UploadHandler();
        }
        
        $customName = 'test_upload_' . time();
        $result = $uploadHandler->handleUpload($_FILES['test_file'], 'payments', $customName);
        
        if ($result) {
            echo "<p class='success'>✓ Test upload successful!</p>";
            echo "<pre>" . print_r($result, true) . "</pre>";
            
            // Record test in database
            try {
                $stmt = $conn->prepare("
                    INSERT INTO file_metadata 
                    (filename, directory, original_name, file_size, mime_type, is_heroku) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $customName . '.' . pathinfo($_FILES['test_file']['name'], PATHINFO_EXTENSION),
                    'payments',
                    $_FILES['test_file']['name'],
                    $_FILES['test_file']['size'],
                    $_FILES['test_file']['type'],
                    $isHeroku ? true : false
                ]);
                
                echo "<p class='success'>✓ Test file metadata recorded in database</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>⚠ Could not record file metadata: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            
        } else {
            echo "<p class='error'>✗ Test upload failed!</p>";
            echo "<p>Errors: " . implode(', ', $uploadHandler->getErrors()) . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ Exception during test upload: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Show the upload form
echo "<form method='post' enctype='multipart/form-data'>";
echo "<p>Upload a test file to verify the upload system:</p>";
echo "<input type='file' name='test_file'><br><br>";
echo "<button type='submit'>Test Upload</button>";
echo "</form>";

echo "</div>";

// 6. Create test record in data_jamaah for testing confirm_payment.php
echo "<div class='section'>";
echo "<h2>6. Test Jamaah Record</h2>";

try {
    // Check if test jamaah exists
    $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = '9999999999999999'");
    $stmt->execute();
    $testJamaah = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testJamaah) {
        echo "<p class='success'>✓ Test jamaah record exists</p>";
    } else {
        echo "<p>Creating test jamaah record...</p>";
        
        // First check if data_paket has any records
        $stmt = $conn->prepare("SELECT pak_id FROM data_paket LIMIT 1");
        $stmt->execute();
        $paket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$paket) {
            // Create a test paket
            $stmt = $conn->prepare("
                INSERT INTO data_paket (
                    pak_id, 
                    program_pilihan, 
                    tanggal_keberangkatan,
                    base_price_quad,
                    base_price_triple,
                    base_price_double,
                    currency,
                    created_at,
                    updated_at
                ) VALUES (
                    'PKG-TEST',
                    'Test Umroh Package',
                    '2025-12-31',
                    1000,
                    1200,
                    1500,
                    'USD',
                    NOW(),
                    NOW()
                )
            ");
            $stmt->execute();
            $paketId = 'PKG-TEST';
            echo "<p class='success'>✓ Created test package PKG-TEST</p>";
        } else {
            $paketId = $paket['pak_id'];
        }
        
        // Now create the test jamaah
        try {
            $stmt = $conn->prepare("
                INSERT INTO data_jamaah (
                    nik,
                    nama,
                    no_telp,
                    email,
                    pak_id,
                    type_room_pilihan,
                    created_at,
                    updated_at
                ) VALUES (
                    '9999999999999999',
                    'Test User',
                    '081234567890',
                    'test@example.com',
                    ?,
                    'Double',
                    NOW(),
                    NOW()
                )
            ");
            $stmt->execute([$paketId]);
            echo "<p class='success'>✓ Created test jamaah record with NIK 9999999999999999</p>";
            
        } catch (PDOException $e) {
            echo "<p class='error'>✗ Could not create test jamaah: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div>";

// 7. Test confirm_payment.php integration
echo "<div class='section'>";
echo "<h2>7. Test Payment Form</h2>";

echo "<p>Use this form to test the full payment process with the test jamaah:</p>";
echo "<form action='confirm_payment.php' method='post' enctype='multipart/form-data'>";
echo "<input type='hidden' name='nik' value='9999999999999999'>";
echo "<input type='hidden' name='nama' value='Test User'>";
echo "<input type='hidden' name='program_pilihan' value='Test Umroh Package'>";
echo "<input type='hidden' name='type_room_pilihan' value='Double'>";
echo "<input type='hidden' name='transfer_account_name' value='Test Account'>";
echo "<input type='hidden' name='payment_method' value='Transfer Bank'>";
echo "<input type='hidden' name='payment_type' value='Full Payment'>";

echo "<p><strong>Upload Payment Proof:</strong><br>";
echo "<input type='file' name='payment_path' required></p>";

echo "<p><button type='submit'>Test Full Payment Process</button></p>";
echo "</form>";

echo "</div>";

// 8. Recommendations
echo "<div class='section'>";
echo "<h2>8. Recommendations</h2>";

echo "<ul>";
echo "<li>After running this fix, test the payment form using the special test jamaah record.</li>";
echo "<li>Check error logs at <a href='error_viewer.php'>error_viewer.php</a> if issues persist.</li>";
echo "<li>Remember that on Heroku, files are stored in ephemeral storage and will be lost during dyno restarts.</li>";
echo "<li>For production, consider implementing cloud storage (AWS S3, Google Cloud Storage, etc).</li>";
echo "</ul>";

echo "</div>";

echo "</div></body></html>";
?>
