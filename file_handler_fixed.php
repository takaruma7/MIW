<?php
// Heroku-compatible file handler with cloud storage fallback
require_once 'config.php';

// Function to get file MIME type
function getMimeType($filepath) {
    if (function_exists('finfo_open') && file_exists($filepath)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        return $mimeType;
    }
    
    // Fallback based on file extension
    $extension = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}

// Function to output file for preview/download
function outputFile($filepath, $filename, $download = false) {
    if (!file_exists($filepath)) {
        header('HTTP/1.0 404 Not Found');
        exit('File not found. This is expected on Heroku due to ephemeral filesystem. Please implement cloud storage for production use.');
    }

    $mimeType = getMimeType($filepath);
    
    // Set headers
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($filepath));
    
    if ($download) {
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    } else {
        header('Content-Disposition: inline; filename="' . basename($filename) . '"');
    }
    
    // Security headers for Heroku
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    
    // Output file
    readfile($filepath);
    exit;
}

// Validate and process request
if (isset($_GET['file']) && isset($_GET['type'])) {
    $file = urldecode($_GET['file']);
    $type = $_GET['type'];
    $action = isset($_GET['action']) ? $_GET['action'] : 'preview';
    
    // Map type to subdirectory
    $validTypes = [
        'documents' => 'documents',
        'payments' => 'payments',
        'cancellations' => 'cancellations'
    ];
    
    if (!isset($validTypes[$type])) {
        header('HTTP/1.0 400 Bad Request');
        exit('Invalid file type.');
    }
    
    // Clean and validate the file path
    $cleanFile = str_replace('..', '', $file); // Remove potential directory traversal
    $cleanFile = basename($cleanFile); // Only get the filename
    
    // Setup paths for Heroku environment
    $uploadsBaseDirs = [
        __DIR__ . '/uploads/',
        '/tmp/uploads/',
        '/app/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/'
    ];
    
    // Try multiple upload directory locations
    $filepath = null;
    foreach ($uploadsBaseDirs as $baseDir) {
        $typePath = $baseDir . $validTypes[$type] . '/';
        $testPath = $typePath . $cleanFile;
        
        if (file_exists($testPath)) {
            $filepath = $testPath;
            break;
        }
    }
    
    if (!$filepath) {
        // Check if running on Heroku
        $isHeroku = !empty($_ENV['DYNO']) || !empty(getenv('DYNO'));
        
        if ($isHeroku) {
            header('HTTP/1.0 404 Not Found');
            echo json_encode([
                'error' => 'File not found',
                'message' => 'Files on Heroku are temporary and deleted during dyno restarts. For production use, please implement cloud storage (AWS S3, Cloudinary, etc.)',
                'filename' => $cleanFile,
                'suggestion' => 'Consider implementing cloud storage for permanent file storage'
            ]);
        } else {
            header('HTTP/1.0 404 Not Found');
            exit('File not found: ' . $cleanFile);
        }
        exit;
    }
    
    // Final security check
    $realPath = realpath($filepath);
    if ($realPath === false) {
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied - invalid file path.');
    }
    
    // Check if file is within allowed directory
    $allowedPaths = false;
    foreach ($uploadsBaseDirs as $baseDir) {
        $realBaseDir = realpath($baseDir);
        if ($realBaseDir && strpos($realPath, $realBaseDir) === 0) {
            $allowedPaths = true;
            break;
        }
    }
    
    if (!$allowedPaths) {
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied - file outside allowed directories.');
    }
    
    outputFile($filepath, basename($file), $action === 'download');
} else {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing required parameters: file and type.');
}
?>
