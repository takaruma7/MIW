<?php
/**
 * Upload Handler for MIW Travel Management System
 * 
 * This class provides a unified interface for file uploads across different environments.
 * It uses HerokuFileManager for Heroku deployments and regular file handling for local development.
 * 
 * @version 2.0.0
 * @author MIW Development Team
 */

require_once 'config.php';
require_once 'heroku_file_manager.php';

class UploadHandler {
    private $fileManager;
    private $errors = [];
    private $isHeroku;
    private $uploadBaseDir;
    
    public function __construct() {
        $this->isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
        $this->fileManager = new HerokuFileManager();
        $this->uploadBaseDir = $this->isHeroku ? '/tmp/uploads' : __DIR__ . '/uploads';
        
        // Ensure upload directories exist
        $this->ensureDirectoriesExist();
    }
    
    /**
     * Ensure all necessary directories exist
     */
    private function ensureDirectoriesExist() {
        $directories = [
            $this->uploadBaseDir,
            $this->uploadBaseDir . '/documents',
            $this->uploadBaseDir . '/payments',
            $this->uploadBaseDir . '/cancellations'
        ];
        
        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Handle file upload
     * 
     * @param array $file The $_FILES array element
     * @param string $targetDir Target directory (documents, payments, cancellations)
     * @param string $customName Custom filename without extension
     * @return array|false Upload result or false on failure
     */
    public function handleUpload($file, $targetDir, $customName) {
        try {
            // Clear previous errors
            $this->errors = [];
            
            // Validate file upload
            if (!$file || !is_array($file)) {
                $this->errors[] = "Invalid file data";
                return false;
            }
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $this->errors[] = $this->getUploadError($file['error']);
                return false;
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'image/jpg'];
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            finfo_close($fileInfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $this->errors[] = "Invalid file type. Allowed: JPG, PNG, PDF. Detected: " . $mimeType;
                return false;
            }
            
            // Validate file size (2MB max)
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->errors[] = "File size exceeds 2MB limit. Current size: " . round($file['size'] / 1024 / 1024, 2) . "MB";
                return false;
            }
            
            // Use HerokuFileManager for actual upload
            $result = $this->fileManager->handleUpload($file, $targetDir, $customName);
            
            if (!$result['success']) {
                $this->errors[] = $result['error'];
                return false;
            }
            
            return $result;
            
        } catch (Exception $e) {
            $this->errors[] = "Upload failed: " . $e->getMessage();
            error_log("UploadHandler Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generate custom filename for uploaded files
     * 
     * @param string $nik NIK of the person
     * @param string $documentType Type of document
     * @param string|null $pakId Package ID (optional)
     * @return string Custom filename without extension
     */
    public function generateCustomFilename($nik, $documentType, $pakId = null) {
        $timestamp = date('YmdHis');
        
        if ($pakId) {
            return "{$nik}_{$documentType}_{$pakId}_{$timestamp}";
        } else {
            return "{$nik}_{$documentType}_{$timestamp}";
        }
    }
    
    /**
     * Get upload errors
     * 
     * @return array Array of error messages
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if there are any errors
     * 
     * @return bool True if there are errors
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Get human-readable upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadError($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return "File too large (exceeds server limit)";
            case UPLOAD_ERR_FORM_SIZE:
                return "File too large (exceeds form limit)";
            case UPLOAD_ERR_PARTIAL:
                return "File only partially uploaded";
            case UPLOAD_ERR_NO_FILE:
                return "No file uploaded";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "Missing temporary folder";
            case UPLOAD_ERR_CANT_WRITE:
                return "Failed to write file to disk";
            case UPLOAD_ERR_EXTENSION:
                return "File upload stopped by extension";
            default:
                return "Unknown upload error (code: $errorCode)";
        }
    }
    
    /**
     * Validate file extension
     * 
     * @param string $filename Filename to validate
     * @return bool True if extension is allowed
     */
    public function isAllowedExtension($filename) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $allowedExtensions);
    }
    
    /**
     * Get file info if it exists
     * 
     * @param string $filename Filename
     * @param string $directory Directory
     * @return array|false File info or false if not found
     */
    public function getFileInfo($filename, $directory) {
        return $this->fileManager->getFileInfo($filename, $directory);
    }
    
    /**
     * Check if file exists
     * 
     * @param string $filename Filename
     * @param string $directory Directory
     * @return bool True if file exists
     */
    public function fileExists($filename, $directory) {
        return $this->fileManager->fileExists($filename, $directory);
    }
    
    /**
     * Get file path
     * 
     * @param string $filename Filename
     * @param string $directory Directory
     * @return string|false File path or false if not found
     */
    public function getFilePath($filename, $directory) {
        return $this->fileManager->getFilePath($filename, $directory);
    }
    
    /**
     * Delete a file
     * 
     * @param string $filename Filename
     * @param string $directory Directory
     * @return bool True if deleted successfully
     */
    public function deleteFile($filename, $directory) {
        try {
            $filePath = $this->fileManager->getFilePath($filename, $directory);
            if ($filePath && file_exists($filePath)) {
                return unlink($filePath);
            }
            return false;
        } catch (Exception $e) {
            error_log("Error deleting file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get Heroku warning if applicable
     * 
     * @return array Warning info
     */
    public function getHerokuWarning() {
        return $this->fileManager->getHerokuWarning();
    }
    
    /**
     * Clean up old files (for Heroku)
     * 
     * @param int $daysOld Number of days old files to clean
     */
    public function cleanupOldFiles($daysOld = 1) {
        $this->fileManager->cleanupOldFiles($daysOld);
    }
    
    /**
     * Get upload statistics
     * 
     * @return array Upload statistics
     */
    public function getUploadStats() {
        return [
            'is_heroku' => $this->isHeroku,
            'upload_base_dir' => $this->uploadBaseDir,
            'max_file_size' => '2MB',
            'allowed_types' => ['JPG', 'PNG', 'PDF'],
            'environment' => $this->isHeroku ? 'Heroku' : 'Local'
        ];
    }
}
?>
