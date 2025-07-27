<?php
/**
 * Simulate confirm_payment.php POST request to identify HTTP 500 cause
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Payment POST Simulation</title>
    <style>
        body { font-family: 'Consolas', monospace; margin: 20px; background: #f8f9fa; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        .header { background: #28a745; color: white; padding: 20px; margin: -30px -30px 30px; border-radius: 8px 8px 0 0; }
        .test-section { margin: 20px 0; padding: 15px; border-left: 4px solid #28a745; background: #f8f9fa; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #2d2d2d; color: #fff; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .form-section { background: #e9ecef; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🧪 Confirm Payment POST Simulation</h1>
            <p>Testing actual POST submission to confirm_payment.php</p>
        </div>

        <?php
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            echo "<div class='test-section'><h3>📥 POST Request Received</h3>";
            
            echo "<div class='info'>POST Data Received:</div>";
            echo "<pre>" . json_encode($_POST, JSON_PRETTY_PRINT) . "</pre>";
            
            echo "<div class='info'>FILES Data Received:</div>";
            echo "<pre>" . json_encode($_FILES, JSON_PRETTY_PRINT) . "</pre>";
            
            // Simulate the confirm_payment.php logic step by step
            echo "<h4>🔄 Simulating confirm_payment.php Logic</h4>";
            
            try {
                // Step 1: Load config
                echo "<div class='info'>Step 1: Loading config.php...</div>";
                require_once 'config.php';
                echo "<div class='success'>✅ Config loaded</div>";
                
                // Step 2: Load email functions
                echo "<div class='info'>Step 2: Loading email_functions.php...</div>";
                require_once 'email_functions.php';
                echo "<div class='success'>✅ Email functions loaded</div>";
                
                // Step 3: Check database connection
                echo "<div class='info'>Step 3: Checking database connection...</div>";
                if ($conn instanceof PDO) {
                    echo "<div class='success'>✅ Database connection active</div>";
                } else {
                    echo "<div class='error'>❌ Database connection failed</div>";
                    throw new Exception("Database connection not available");
                }
                
                // Step 4: Start transaction
                echo "<div class='info'>Step 4: Starting transaction...</div>";
                $conn->beginTransaction();
                echo "<div class='success'>✅ Transaction started</div>";
                
                // Step 5: Validate required fields
                echo "<div class='info'>Step 5: Validating required fields...</div>";
                $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
                $missingFields = [];
                
                foreach ($requiredFields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        $missingFields[] = $field;
                    }
                }
                
                if (!empty($missingFields)) {
                    echo "<div class='error'>❌ Missing fields: " . implode(', ', $missingFields) . "</div>";
                    throw new Exception("Missing required fields: " . implode(', ', $missingFields));
                } else {
                    echo "<div class='success'>✅ All required fields present</div>";
                }
                
                // Step 6: Validate file upload
                echo "<div class='info'>Step 6: Validating file upload...</div>";
                if (!isset($_FILES['payment_path'])) {
                    echo "<div class='error'>❌ No payment_path file in request</div>";
                    throw new Exception("Payment proof file is required");
                }
                
                $uploadError = $_FILES['payment_path']['error'];
                if ($uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE) {
                    $errorMessages = [
                        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                        UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
                    ];
                    
                    $errorMessage = $errorMessages[$uploadError] ?? "Unknown upload error ($uploadError)";
                    echo "<div class='error'>❌ Upload error: $errorMessage</div>";
                    throw new Exception("File upload error: $errorMessage");
                } else {
                    echo "<div class='success'>✅ File upload validation passed</div>";
                }
                
                // Step 7: Load upload handler
                echo "<div class='info'>Step 7: Loading upload handler...</div>";
                require_once 'upload_handler.php';
                echo "<div class='success'>✅ Upload handler loaded</div>";
                
                // Step 8: Create upload handler instance
                echo "<div class='info'>Step 8: Creating UploadHandler instance...</div>";
                $uploadHandler = new UploadHandler();
                echo "<div class='success'>✅ UploadHandler created</div>";
                
                // Step 9: Test filename generation
                echo "<div class='info'>Step 9: Testing filename generation...</div>";
                $customName = $uploadHandler->generateCustomFilename($_POST['nik'], 'payment', null);
                echo "<div class='success'>✅ Filename generated: $customName</div>";
                
                // Step 10: Test database update (but don't execute)
                echo "<div class='info'>Step 10: Testing database update query...</div>";
                $stmt = $conn->prepare("UPDATE data_jamaah SET 
                    transfer_account_name = :transfer_account_name,
                    payment_time = :payment_time,
                    payment_date = :payment_date,
                    payment_status = 'pending',
                    payment_path = :payment_path
                    WHERE nik = :nik");
                echo "<div class='success'>✅ Database update query prepared</div>";
                
                // Step 11: Test jamaah data fetch
                echo "<div class='info'>Step 11: Testing jamaah data fetch...</div>";
                $stmt = $conn->prepare("SELECT j.*, p.program_pilihan, p.tanggal_keberangkatan,
                    CASE j.type_room_pilihan
                        WHEN 'Quad' THEN p.base_price_quad
                        WHEN 'Triple' THEN p.base_price_triple
                        WHEN 'Double' THEN p.base_price_double
                    END as biaya_paket,
                    p.currency
                    FROM data_jamaah j
                    JOIN data_paket p ON j.pak_id = p.pak_id
                    WHERE j.nik = :nik");
                echo "<div class='success'>✅ Jamaah data query prepared</div>";
                
                // Rollback transaction since we're just testing
                $conn->rollBack();
                echo "<div class='success'>✅ Transaction rolled back (test mode)</div>";
                
                echo "<div class='success'><h4>🎉 All steps completed successfully!</h4></div>";
                echo "<div class='info'>The issue might be in the actual file upload or email sending process.</div>";
                
            } catch (Exception $e) {
                if ($conn && $conn->inTransaction()) {
                    $conn->rollBack();
                }
                echo "<div class='error'>❌ Error in step: " . $e->getMessage() . "</div>";
                echo "<div class='info'>Error file: " . $e->getFile() . "</div>";
                echo "<div class='info'>Error line: " . $e->getLine() . "</div>";
                echo "<div class='info'>Stack trace:</div>";
                echo "<pre>" . $e->getTraceAsString() . "</pre>";
            }
            
            echo "</div>";
        } else {
            // Show form to simulate POST request
            echo "<div class='test-section'><h3>📝 Simulate Payment Confirmation Form</h3>";
            echo "<div class='info'>This form simulates the data that would be sent to confirm_payment.php</div>";
            
            echo "<div class='form-section'>";
            echo "<form method='POST' enctype='multipart/form-data'>";
            echo "<table>";
            echo "<tr><td>NIK:</td><td><input type='text' name='nik' value='1234567890123456' required></td></tr>";
            echo "<tr><td>Nama:</td><td><input type='text' name='nama' value='Test User' required></td></tr>";
            echo "<tr><td>Transfer Account Name:</td><td><input type='text' name='transfer_account_name' value='Test Account' required></td></tr>";
            echo "<tr><td>Program Pilihan:</td><td><input type='text' name='program_pilihan' value='Test Program Umroh' required></td></tr>";
            echo "<tr><td>Type Room Pilihan:</td><td><select name='type_room_pilihan'><option>Quad</option><option>Triple</option><option>Double</option></select></td></tr>";
            echo "<tr><td>Payment Type:</td><td><input type='text' name='payment_type' value='DP'></td></tr>";
            echo "<tr><td>Payment Method:</td><td><input type='text' name='payment_method' value='Transfer Bank'></td></tr>";
            echo "<tr><td>Payment File:</td><td><input type='file' name='payment_path' accept='image/*,application/pdf'></td></tr>";
            echo "<tr><td colspan='2'><input type='submit' value='Test Simulation' style='padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 5px;'></td></tr>";
            echo "</table>";
            echo "</form>";
            echo "</div>";
            
            echo "<div class='warning'>⚠️ Note: This is a simulation that won't actually process the payment, but will test all the logic steps.</div>";
            
            echo "</div>";
        }
        
        ?>
        
    </div>
</body>
</html>
