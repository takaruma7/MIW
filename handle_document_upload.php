<?php
// handle_document_upload.php - Handles document upload and retrieval for the document management system

require_once 'config.php';
require_once 'upload_handler.php';
require_once 'email_functions.php';

// Set proper content type for JSON responses
header('Content-Type: application/json');

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => [],
    'uploaded' => []
];

try {
    // Check if nik is provided (required for all operations)
    if (empty($_POST['nik']) && empty($_GET['nik'])) {
        throw new Exception('NIK is required');
    }
    
    // Document upload handling
    if (isset($_POST['action']) && $_POST['action'] == 'upload') {
        $nik = $_POST['nik'];
        
        // Validate jamaah exists
        $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
        $stmt->execute([$nik]);
        $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jamaah) {
            throw new Exception('Jamaah with NIK ' . $nik . ' not found');
        }
        
        // Initialize upload handler
        $uploadHandler = new UploadHandler();
        $uploadedFiles = [];
        $uploadedCount = 0;
        $uploadErrors = [];
        
        // Define document types and their corresponding DB fields
        $documentTypes = [
            'bk_kuning' => 'bk_kuning_path',
            'foto' => 'foto_path',
            'fc_ktp' => 'fc_ktp_path',
            'fc_ijazah' => 'fc_ijazah_path',
            'fc_kk' => 'fc_kk_path',
            'fc_bk_nikah' => 'fc_bk_nikah_path',
            'fc_akta_lahir' => 'fc_akta_lahir_path'
        ];
        
        // Process each document type
        foreach ($documentTypes as $docType => $dbField) {
            if (!isset($_FILES[$docType]) || $_FILES[$docType]['error'] === UPLOAD_ERR_NO_FILE) {
                continue; // Skip if no file uploaded for this document type
            }
            
            if ($_FILES[$docType]['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = '';
                switch ($_FILES[$docType]['error']) {
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $errorMessage = 'File too large';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $errorMessage = 'File only partially uploaded';
                        break;
                    default:
                        $errorMessage = 'Upload failed with error code ' . $_FILES[$docType]['error'];
                }
                $uploadErrors[$docType] = $errorMessage;
                continue; // Skip files with upload errors
            }
            
            // Generate custom filename
            $customName = $uploadHandler->generateCustomFilename($nik, $docType);
            
            // Handle upload
            $uploadResult = $uploadHandler->handleUpload($_FILES[$docType], 'documents', $customName);
            
            if (!$uploadResult) {
                $uploadErrors[$docType] = implode(', ', $uploadHandler->getErrors());
                continue; // Skip if upload failed
            }
            
            // Store path in database (use current timestamp as value)
            $currentDateTime = date('Y-m-d H:i:s');
            $updateSql = "UPDATE data_jamaah SET $dbField = ? WHERE nik = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->execute([$currentDateTime, $nik]);
            
            // Add to uploaded files array
            $uploadedFiles[$docType] = $_FILES[$docType];
            $response['uploaded'][$docType] = $uploadResult['path'];
            $uploadedCount++;
        }
        
        // Add upload errors to response
        if (!empty($uploadErrors)) {
            $response['errors'] = $uploadErrors;
        }
        
        // Send email notification if files were uploaded
        if ($uploadedCount > 0) {
            sendDocumentUploadEmail($jamaah, $uploadedFiles);
        }
        
        // Return success response
        $response['success'] = true;
        $response['message'] = $uploadedCount > 0 ? 
            'Successfully uploaded ' . $uploadedCount . ' document(s)' : 
            'No documents were uploaded';
    }
    
    // Document retrieval handling
    else if (isset($_GET['action']) && $_GET['action'] == 'get_documents') {
        $nik = $_GET['nik'];
        
        // Validate jamaah exists
        $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
        $stmt->execute([$nik]);
        $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$jamaah) {
            throw new Exception('Jamaah with NIK ' . $nik . ' not found');
        }
        
        // Map document types to their paths
        $documentPaths = [];
        $documentTypes = [
            'bk_kuning' => 'bk_kuning_path',
            'foto' => 'foto_path',
            'fc_ktp' => 'fc_ktp_path',
            'fc_ijazah' => 'fc_ijazah_path',
            'fc_kk' => 'fc_kk_path',
            'fc_bk_nikah' => 'fc_bk_nikah_path',
            'fc_akta_lahir' => 'fc_akta_lahir_path'
        ];
        
        // Generate relative file paths
        foreach ($documentTypes as $docType => $dbField) {
            if (!empty($jamaah[$dbField])) {
                // Format expected path based on your file naming convention
                $formattedDate = date('YmdHis', strtotime($jamaah[$dbField]));
                
                // Check for various extensions - try pdf, jpg, jpeg, png
                $extensions = ['pdf', 'jpg', 'jpeg', 'png'];
                $found = false;
                
                foreach ($extensions as $extension) {
                    $path = "/MIW/uploads/documents/{$nik}_{$docType}_{$formattedDate}.{$extension}";
                    $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
                    
                    if (file_exists($fullPath)) {
                        $documentPaths[$docType] = $path;
                        $found = true;
                        break;
                    }
                }
                
                // If file not found with any extension, use default extension
                if (!$found) {
                    $documentPaths[$docType] = "/MIW/uploads/documents/{$nik}_{$docType}_{$formattedDate}.pdf";
                }
            }
        }
        
        // Return document paths
        $response['success'] = true;
        $response['data'] = $documentPaths;
    }
    
    // Invalid action
    else {
        throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Document upload error: " . $e->getMessage());
}

// Output JSON response
echo json_encode($response);
