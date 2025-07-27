<?php
require_once 'config.php';
require_once 'email_functions.php';

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

// Enhanced error logging function
function logDetailedError($message, $context = []) {
    $timestamp = date('Y-m-d H:i:s');
    $logFile = __DIR__ . '/error_logs/confirm_payment_' . date('Y-m-d') . '.log';
    
    $logEntry = "[{$timestamp}] CONFIRM_PAYMENT ERROR: {$message}\n";
    
    if (!empty($context)) {
        $logEntry .= "[{$timestamp}] CONTEXT: " . json_encode($context, JSON_PRETTY_PRINT) . "\n";
    }
    
    $logEntry .= "[{$timestamp}] SERVER INFO: " . json_encode([
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
        'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        'USER_AGENT' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'POST_DATA' => !empty($_POST) ? array_keys($_POST) : 'None',
        'FILES_DATA' => !empty($_FILES) ? array_keys($_FILES) : 'None',
        'MEMORY_USAGE' => memory_get_usage(true),
        'PEAK_MEMORY' => memory_get_peak_usage(true)
    ], JSON_PRETTY_PRINT) . "\n";
    
    $logEntry .= str_repeat('-', 80) . "\n";
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    error_log($message); // Also log to PHP error log
}

// Log script start
logDetailedError("Script started", [
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
    'post_fields' => !empty($_POST) ? array_keys($_POST) : [],
    'files' => !empty($_FILES) ? array_keys($_FILES) : []
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    logDetailedError("POST request received", ['post_data' => $_POST, 'files' => array_keys($_FILES)]);
    
    try {
        // Start transaction
        $conn->beginTransaction();
        logDetailedError("Database transaction started");

        // Validate required fields
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        logDetailedError("Required fields validation passed");

        // Validate file upload with detailed error reporting
        if (!isset($_FILES['payment_path'])) {
            logDetailedError("File upload validation failed - No file in request", [
                'files_available' => array_keys($_FILES),
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ]);
            throw new Exception("Payment proof file is required - no file uploaded");
        }
        
        $uploadError = $_FILES['payment_path']['error'];
        if ($uploadError !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize (' . ini_get('upload_max_filesize') . ')',
                UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
            ];
            
            $errorMessage = $errorMessages[$uploadError] ?? "Unknown upload error ($uploadError)";
            logDetailedError("File upload validation failed", [
                'upload_error_code' => $uploadError,
                'upload_error_message' => $errorMessage,
                'file_size' => $_FILES['payment_path']['size'] ?? 'Unknown',
                'file_name' => $_FILES['payment_path']['name'] ?? 'Unknown',
                'file_type' => $_FILES['payment_path']['type'] ?? 'Unknown'
            ]);
            throw new Exception("Payment proof file upload error: $errorMessage");
        }
        logDetailedError("File upload validation passed", [
            'file_name' => $_FILES['payment_path']['name'],
            'file_size' => $_FILES['payment_path']['size'],
            'file_type' => $_FILES['payment_path']['type']
        ]);

        // Get current timestamp for payment records
        $currentDateTime = new DateTime();
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');

        // Validate file upload
        $file = $_FILES['payment_path'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed types: JPG, PNG, PDF");
        }

        // Validate file size (2MB limit)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds 2MB limit");
        }

        // Handle file upload with enhanced error handling
        require_once 'upload_handler.php';
        logDetailedError("Upload handler loaded");
        
        try {
            $uploadHandler = new UploadHandler();
            logDetailedError("UploadHandler instantiated successfully");
        } catch (Exception $e) {
            logDetailedError("Failed to create UploadHandler", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new Exception("Upload system error: " . $e->getMessage());
        }
        
        try {
            $customName = $uploadHandler->generateCustomFilename($_POST['nik'], 'payment', null);
            logDetailedError("Custom filename generated", ['filename' => $customName]);
        } catch (Exception $e) {
            logDetailedError("Failed to generate filename", [
                'error' => $e->getMessage(),
                'nik' => $_POST['nik']
            ]);
            throw new Exception("Filename generation error: " . $e->getMessage());
        }
        
        try {
            $uploadResult = $uploadHandler->handleUpload($_FILES['payment_path'], 'payments', $customName);
            
            if (!$uploadResult) {
                $errors = $uploadHandler->getErrors();
                logDetailedError("Upload failed", ['errors' => $errors]);
                throw new Exception('Failed to upload payment proof: ' . implode(', ', $errors));
            }
            logDetailedError("File upload successful", ['result' => $uploadResult]);
        } catch (Exception $e) {
            logDetailedError("Upload handler exception", [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'upload_handler_errors' => $uploadHandler->getErrors()
            ]);
            throw new Exception("File upload failed: " . $e->getMessage());
        }

        // Update jamaah record with payment details
        logDetailedError("Updating jamaah record", [
            'nik' => $_POST['nik'],
            'payment_path' => $uploadResult['path']
        ]);
        
        try {
            $stmt = $conn->prepare("UPDATE data_jamaah SET 
                transfer_account_name = :transfer_account_name,
                payment_time = :payment_time,
                payment_date = :payment_date,
                payment_status = 'pending',
                payment_path = :payment_path
                WHERE nik = :nik");

            $updateResult = $stmt->execute([
                'transfer_account_name' => $_POST['transfer_account_name'],
                'payment_time' => $currentTime,
                'payment_date' => $currentDate,
                'payment_path' => $uploadResult['path'],
                'nik' => $_POST['nik']
            ]);
            
            if (!$updateResult) {
                logDetailedError("Database update failed", [
                    'error_info' => $stmt->errorInfo(),
                    'sql_state' => $stmt->errorCode()
                ]);
                throw new Exception("Failed to update payment information: " . implode(', ', $stmt->errorInfo()));
            }
            
            $affectedRows = $stmt->rowCount();
            if ($affectedRows === 0) {
                logDetailedError("No rows affected by update", ['nik' => $_POST['nik']]);
                throw new Exception("No jamaah record found with NIK: " . $_POST['nik']);
            }
            
            logDetailedError("Database update successful", ['affected_rows' => $affectedRows]);
        } catch (PDOException $e) {
            logDetailedError("PDO Exception during update", [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw new Exception("Database error: " . $e->getMessage());
        }

        // Fetch package details for email
        try {
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
            $stmt->execute(['nik' => $_POST['nik']]);
            $jamaahData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$jamaahData) {
                logDetailedError("Jamaah data not found", ['nik' => $_POST['nik']]);
                throw new Exception("Jamaah record not found for NIK: " . $_POST['nik']);
            }
            
            logDetailedError("Jamaah data fetched successfully", [
                'program' => $jamaahData['program_pilihan'],
                'currency' => $jamaahData['currency']
            ]);
        } catch (PDOException $e) {
            logDetailedError("PDO Exception during jamaah data fetch", [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw new Exception("Database error while fetching jamaah data: " . $e->getMessage());
        }
        
        // Prepare email data with complete details
        $paymentData = [
            'nama' => $_POST['nama'],
            'nik' => $_POST['nik'],
            'no_telp' => $jamaahData['no_telp'],
            'email' => $jamaahData['email'],
            'program_pilihan' => $jamaahData['program_pilihan'],
            'tanggal_keberangkatan' => $jamaahData['tanggal_keberangkatan'],
            'biaya_paket' => $jamaahData['biaya_paket'],
            'type_room_pilihan' => $_POST['type_room_pilihan'],
            'transfer_account_name' => $_POST['transfer_account_name'],
            'payment_time' => $currentTime,
            'payment_date' => $currentDate,
            'payment_type' => $_POST['payment_type'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? '',
            'currency' => $jamaahData['currency']
        ];

        // Determine registration type from program_pilihan
        $registrationType = (stripos($_POST['program_pilihan'], 'haji') !== false) ? 'Haji' : 'Umroh';

        // Send email notification without attachments
        $emailResult = sendPaymentConfirmationEmail($paymentData, [], $registrationType);

        // Log email result but don't stop transaction
        if (!$emailResult['success']) {
            error_log("Payment confirmation email issue: " . $emailResult['message']);
        }

        // Commit transaction
        $conn->commit();

        // Set success session data for closing_page.php
        $_SESSION['payment_success'] = [
            'status' => true,
            'timestamp' => time(),
            'message' => 'Payment confirmation submitted successfully',
            'email_status' => $emailResult['success'] ? $emailResult['message'] : null
        ];

        // Redirect to closing page
        header('Location: closing_page.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        logDetailedError("Exception caught in confirm_payment.php", [
            'error_message' => $e->getMessage(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'stack_trace' => $e->getTraceAsString()
        ]);
        
        // Store error in session and redirect back to invoice
        $_SESSION['payment_error'] = $e->getMessage();
        header('Location: invoice.php');
        exit();
    }
} else {
    logDetailedError("Non-POST request received", [
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
        'query_string' => $_SERVER['QUERY_STRING'] ?? 'None'
    ]);
}

// If we get here without POST data, redirect to homepage
header('Location: https://hajikhusus.web.id');
exit();
