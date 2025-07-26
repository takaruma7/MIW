<?php
/**
 * Test script for payment form submission and upload handling
 * Used to diagnose confirm_payment.php issues with detailed logging
 */

require_once 'config.php';

// Initialize error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

// Error logging function
function logError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/error_logs/payment_form_test_' . date('Y-m-d') . '.log';
    
    $logEntry = "[{$timestamp}] TEST: {$message}\n";
    
    if (!empty($context)) {
        $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    
    $logEntry .= str_repeat('-', 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Start HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment Form Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { border: 1px solid #ddd; padding: 20px; margin: 20px 0; border-radius: 5px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        form { margin: 20px 0; }
        label { display: block; margin: 10px 0 5px; }
        input[type="text"], input[type="file"] { width: 100%; padding: 8px; margin-bottom: 10px; }
        input[type="submit"] { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        .test-result { margin: 20px 0; padding: 15px; background: #f5f5f5; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Payment Form Test</h1>
        
        <div class="section">
            <h2>1. Server Environment</h2>
            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            <p><strong>Environment:</strong> <?= isset($_ENV['DYNO']) ? 'Heroku' : 'Local/Other' ?></p>
            <p><strong>Upload Max Filesize:</strong> <?= ini_get('upload_max_filesize') ?></p>
            <p><strong>Post Max Size:</strong> <?= ini_get('post_max_size') ?></p>
        </div>
        
        <div class="section">
            <h2>2. File Metadata Table Check</h2>
            <?php
            $hasFileMetadataTable = false;
            
            try {
                $stmt = $conn->prepare("SELECT 1 FROM file_metadata LIMIT 1");
                $stmt->execute();
                $hasFileMetadataTable = true;
                echo '<p class="success">✓ file_metadata table exists</p>';
            } catch (PDOException $e) {
                echo '<p class="error">✗ file_metadata table does not exist</p>';
                echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                
                // Try to create the table
                echo '<p>Attempting to create file_metadata table...</p>';
                try {
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
                    $hasFileMetadataTable = true;
                    echo '<p class="success">✓ file_metadata table created successfully</p>';
                } catch (PDOException $e2) {
                    echo '<p class="error">✗ Failed to create file_metadata table</p>';
                    echo '<p>Error: ' . htmlspecialchars($e2->getMessage()) . '</p>';
                }
            }
            ?>
        </div>
        
        <div class="section">
            <h2>3. Upload Handler Check</h2>
            <?php
            $uploadHandlerWorking = false;
            
            try {
                require_once 'upload_handler.php';
                $uploadHandler = new UploadHandler();
                
                // Test generating a custom filename
                $testFilename = $uploadHandler->generateCustomFilename('123456789012', 'test');
                
                echo '<p class="success">✓ UploadHandler class initialized successfully</p>';
                echo '<p>Test filename generated: ' . htmlspecialchars($testFilename) . '</p>';
                
                $uploadHandlerWorking = true;
                
            } catch (Exception $e) {
                echo '<p class="error">✗ Error initializing UploadHandler</p>';
                echo '<p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>4. Test Upload Form</h2>
            <p>This form simulates the payment confirmation form submission:</p>
            
            <form action="confirm_payment.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="test_mode" value="1">
                
                <label for="nik">NIK:</label>
                <input type="text" id="nik" name="nik" value="1234567890123456" required>
                
                <label for="nama">Name:</label>
                <input type="text" id="nama" name="nama" value="Test User" required>
                
                <label for="transfer_account_name">Transfer Account Name:</label>
                <input type="text" id="transfer_account_name" name="transfer_account_name" value="Test Account" required>
                
                <label for="program_pilihan">Program Pilihan:</label>
                <input type="text" id="program_pilihan" name="program_pilihan" value="Umroh Test Package" required>
                
                <label for="type_room_pilihan">Room Type:</label>
                <input type="text" id="type_room_pilihan" name="type_room_pilihan" value="Double" required>
                
                <label for="payment_method">Payment Method:</label>
                <input type="text" id="payment_method" name="payment_method" value="Transfer" required>
                
                <label for="payment_type">Payment Type:</label>
                <input type="text" id="payment_type" name="payment_type" value="Full" required>
                
                <label for="payment_path">Payment Proof:</label>
                <input type="file" id="payment_path" name="payment_path" required>
                
                <p>
                    <input type="submit" value="Test Submit to confirm_payment.php">
                </p>
            </form>
        </div>
        
        <div class="section">
            <h2>5. Manual Upload Test</h2>
            <p>Test the upload handler directly:</p>
            
            <?php
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_upload'])) {
                logError('Manual upload test started', [
                    'post_data' => $_POST,
                    'files' => array_keys($_FILES)
                ]);
                
                if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
                    try {
                        // Ensure we have the UploadHandler
                        if (!class_exists('UploadHandler')) {
                            require_once 'upload_handler.php';
                        }
                        
                        $uploadHandler = new UploadHandler();
                        $customName = $uploadHandler->generateCustomFilename('TEST', 'manual');
                        
                        echo '<div class="test-result">';
                        echo '<h3>Upload Test Results</h3>';
                        
                        $targetDir = 'payments';
                        $result = $uploadHandler->handleUpload($_FILES['test_file'], $targetDir, $customName);
                        
                        if ($result) {
                            echo '<p class="success">✓ File uploaded successfully</p>';
                            echo '<pre>' . print_r($result, true) . '</pre>';
                            
                            logError('Manual upload success', ['result' => $result]);
                        } else {
                            echo '<p class="error">✗ Upload failed</p>';
                            echo '<p>Errors: ' . implode(', ', $uploadHandler->getErrors()) . '</p>';
                            
                            logError('Manual upload failed', ['errors' => $uploadHandler->getErrors()]);
                        }
                        
                        echo '</div>';
                        
                    } catch (Exception $e) {
                        echo '<div class="test-result">';
                        echo '<p class="error">✗ Exception during upload: ' . htmlspecialchars($e->getMessage()) . '</p>';
                        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                        echo '</div>';
                        
                        logError('Exception during upload', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    $errorCode = $_FILES['test_file']['error'] ?? 'No file';
                    echo '<div class="test-result">';
                    echo '<p class="error">✗ No valid file uploaded (Error code: ' . $errorCode . ')</p>';
                    echo '</div>';
                    
                    logError('No valid file uploaded', ['error_code' => $errorCode]);
                }
            }
            ?>
            
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="test_upload" value="1">
                
                <label for="test_file">Test File:</label>
                <input type="file" id="test_file" name="test_file" required>
                
                <p>
                    <input type="submit" value="Test Direct Upload">
                </p>
            </form>
        </div>
        
        <div class="section">
            <h2>6. Database Connection Test</h2>
            <?php
            try {
                $stmt = $conn->prepare("SELECT 1");
                $stmt->execute();
                echo '<p class="success">✓ Database connection working</p>';
                
                // Test creating a record in file_metadata
                if ($hasFileMetadataTable) {
                    $testFilename = 'test_' . time() . '.jpg';
                    
                    $stmt = $conn->prepare("
                        INSERT INTO file_metadata (filename, directory, original_name, file_size, mime_type)
                        VALUES (?, ?, ?, ?, ?)
                        ON CONFLICT (filename, directory) DO NOTHING
                    ");
                    
                    $result = $stmt->execute([
                        $testFilename,
                        'test',
                        'original_test.jpg',
                        1024,
                        'image/jpeg'
                    ]);
                    
                    if ($result) {
                        echo '<p class="success">✓ Test record created in file_metadata table</p>';
                    } else {
                        echo '<p class="warning">⚠ Could not create test record, but no error thrown</p>';
                    }
                }
                
            } catch (PDOException $e) {
                echo '<p class="error">✗ Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            ?>
        </div>
        
        <div class="section">
            <h2>7. Recommendations</h2>
            <?php if (!$hasFileMetadataTable): ?>
                <p class="warning">⚠ You need to create the file_metadata table. Run comprehensive_fix.php first.</p>
            <?php endif; ?>
            
            <?php if (!$uploadHandlerWorking): ?>
                <p class="warning">⚠ There are issues with the upload handler. Check upload_handler.php and heroku_file_manager.php.</p>
            <?php endif; ?>
            
            <p>Check the error logs at error_logs/payment_form_test_*.log for detailed information.</p>
            <p>Use the <a href="error_viewer.php" target="_blank">error_viewer.php</a> to view all error logs.</p>
            
            <p><strong>If all tests pass but confirm_payment.php still fails:</strong></p>
            <ul>
                <li>Check that config.php has correct database connection settings</li>
                <li>Verify email_functions.php is working properly</li>
                <li>Make sure there are data_jamaah and data_paket records that match the submitted NIK</li>
                <li>Check permissions on upload directories</li>
                <li>Verify that the comprehensive_fix.php script has been run on production</li>
            </ul>
        </div>
    </div>
</body>
</html>
