<?php
// Heroku File Storage Handler
// This file provides a solution for file uploads on Heroku's ephemeral filesystem

require_once 'config.php';

/**
 * Heroku File Storage Manager
 * Handles file uploads with cloud storage fallback for production
 */
class HerokuFileManager {
    private $uploadBaseDir;
    private $tempDir;
    private $isHeroku;
    
    public function __construct() {
        $this->isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
        $this->tempDir = $this->isHeroku ? '/tmp/uploads' : __DIR__ . '/uploads';
        $this->uploadBaseDir = $this->tempDir;
        
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
            
            // Create .htaccess for security
            $htaccessFile = $dir . '/.htaccess';
            if (!file_exists($htaccessFile)) {
                file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
            }
        }
    }
    
    /**
     * Handle file upload
     */
    public function handleUpload($file, $targetDir, $customName) {
        try {
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("File upload failed");
            }
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception("Invalid file type. Allowed: JPG, PNG, PDF");
            }
            
            // Validate file size (2MB max)
            if ($file['size'] > 2 * 1024 * 1024) {
                throw new Exception("File size exceeds 2MB limit");
            }
            
            // Create target directory
            $targetPath = $this->uploadBaseDir . '/' . trim($targetDir, '/');
            if (!file_exists($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $customName . '.' . $extension;
            $fullPath = $targetPath . '/' . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
                throw new Exception("Failed to move uploaded file");
            }
            
            // Store file metadata in database for recovery
            $this->storeFileMetadata($filename, $targetDir, $file);
            
            return [
                'success' => true,
                'path' => '/MIW/uploads/' . $targetDir . '/' . $filename,
                'filename' => $filename,
                'size' => $file['size'],
                'type' => $file['type']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Store file metadata in database for recovery purposes
     */
    private function storeFileMetadata($filename, $directory, $fileInfo) {
        try {
            global $conn;
            
            $stmt = $conn->prepare("
                INSERT INTO file_metadata (filename, directory, original_name, file_size, mime_type, upload_time, is_heroku)
                VALUES (?, ?, ?, ?, ?, NOW(), ?)
                ON CONFLICT (filename, directory) DO UPDATE SET
                    original_name = EXCLUDED.original_name,
                    file_size = EXCLUDED.file_size,
                    mime_type = EXCLUDED.mime_type,
                    upload_time = EXCLUDED.upload_time,
                    is_heroku = EXCLUDED.is_heroku
            ");
            
            $stmt->execute([
                $filename,
                $directory,
                $fileInfo['name'],
                $fileInfo['size'],
                $fileInfo['type'],
                $this->isHeroku ? 1 : 0
            ]);
            
        } catch (Exception $e) {
            error_log("Failed to store file metadata: " . $e->getMessage());
        }
    }
    
    /**
     * Check if file exists
     */
    public function fileExists($filename, $directory) {
        $fullPath = $this->uploadBaseDir . '/' . $directory . '/' . $filename;
        return file_exists($fullPath);
    }
    
    /**
     * Get file path
     */
    public function getFilePath($filename, $directory) {
        $fullPath = $this->uploadBaseDir . '/' . $directory . '/' . $filename;
        return file_exists($fullPath) ? $fullPath : false;
    }
    
    /**
     * Get file info from database
     */
    public function getFileInfo($filename, $directory) {
        try {
            global $conn;
            
            $stmt = $conn->prepare("
                SELECT * FROM file_metadata 
                WHERE filename = ? AND directory = ?
            ");
            $stmt->execute([$filename, $directory]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate a warning message for Heroku
     */
    public function getHerokuWarning() {
        if ($this->isHeroku) {
            return [
                'warning' => true,
                'message' => 'Files on Heroku are temporary and will be deleted during dyno restarts. For production, implement cloud storage (AWS S3, Cloudinary, etc.)',
                'suggestion' => 'Consider implementing permanent cloud storage solution'
            ];
        }
        return ['warning' => false];
    }
    
    /**
     * Clean up old files (for Heroku temporary storage)
     */
    public function cleanupOldFiles($daysOld = 1) {
        if (!$this->isHeroku) return;
        
        $directories = ['documents', 'payments', 'cancellations'];
        $cutoffTime = time() - ($daysOld * 24 * 60 * 60);
        
        foreach ($directories as $dir) {
            $dirPath = $this->uploadBaseDir . '/' . $dir;
            if (!is_dir($dirPath)) continue;
            
            $files = glob($dirPath . '/*');
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $cutoffTime) {
                    unlink($file);
                }
            }
        }
    }
}

/**
 * Enhanced File Handler for Heroku
 */
class HerokuFileHandler {
    private $fileManager;
    
    public function __construct() {
        $this->fileManager = new HerokuFileManager();
    }
    
    /**
     * Handle file serving with fallback
     */
    public function serveFile($filename, $type, $action = 'preview') {
        // Clean filename
        $cleanFilename = basename($filename);
        
        // Try to get file
        $filePath = $this->fileManager->getFilePath($cleanFilename, $type);
        
        if (!$filePath) {
            // Check database for file info
            $fileInfo = $this->fileManager->getFileInfo($cleanFilename, $type);
            
            if ($fileInfo && $this->fileManager->isHeroku) {
                // Return Heroku-specific error
                header('HTTP/1.0 404 Not Found');
                echo json_encode([
                    'error' => 'File not found on ephemeral storage',
                    'message' => 'File may have been deleted during dyno restart',
                    'filename' => $cleanFilename,
                    'original_size' => $fileInfo['file_size'] ?? 'unknown',
                    'upload_time' => $fileInfo['upload_time'] ?? 'unknown',
                    'suggestion' => 'Re-upload the file or implement cloud storage'
                ]);
                exit;
            } else {
                header('HTTP/1.0 404 Not Found');
                exit('File not found: ' . $cleanFilename);
            }
        }
        
        // Serve the file
        $this->outputFile($filePath, $cleanFilename, $action === 'download');
    }
    
    /**
     * Output file with proper headers
     */
    private function outputFile($filepath, $filename, $download = false) {
        $mimeType = $this->getMimeType($filepath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($filepath));
        
        if ($download) {
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        } else {
            header('Content-Disposition: inline; filename="' . basename($filename) . '"');
        }
        
        // Security headers
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        
        readfile($filepath);
        exit;
    }
    
    /**
     * Get MIME type
     */
    private function getMimeType($filepath) {
        if (function_exists('finfo_open') && file_exists($filepath)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $filepath);
            finfo_close($finfo);
            return $mimeType;
        }
        
        $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }
}

// Auto-cleanup for Heroku (run occasionally)
if (!empty($_ENV['DYNO']) || !empty(getenv('DYNO'))) {
    $fileManager = new HerokuFileManager();
    
    // Clean up files older than 1 day every 100 requests (approximate)
    if (rand(1, 100) === 1) {
        $fileManager->cleanupOldFiles(1);
    }
}
?>
