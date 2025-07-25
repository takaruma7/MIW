<?php
// Heroku-compatible file handler with enhanced error handling
require_once 'config.php';
require_once 'heroku_file_manager.php';

// Validate request parameters
if (!isset($_GET['file']) || !isset($_GET['type'])) {
    header('HTTP/1.0 400 Bad Request');
    exit('Missing required parameters: file and type');
}

$filename = urldecode($_GET['file']);
$type = $_GET['type'];
$action = isset($_GET['action']) ? $_GET['action'] : 'preview';

// Validate file type
$validTypes = ['documents', 'payments', 'cancellations'];
if (!in_array($type, $validTypes)) {
    header('HTTP/1.0 400 Bad Request');
    exit('Invalid file type');
}

// Use the enhanced Heroku file handler
$fileHandler = new HerokuFileHandler();
$fileHandler->serveFile($filename, $type, $action);
?>
