<?php
/**
 * Production Error Log Viewer for MIW
 * 
 * This script helps view error logs from the deployed application
 * Access: /error_viewer.php (password protected)
 */

// Simple password protection
session_start();

if (!isset($_SESSION['error_viewer_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === 'MIW2025!') {
        $_SESSION['error_viewer_auth'] = true;
    } else {
        if (isset($_POST['password'])) {
            $error = "Invalid password";
        }
        ?>
        <!DOCTYPE html>
        <html><head><title>Error Log Viewer - Access</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            .form-group { margin: 15px 0; }
            input[type="password"], input[type="submit"] { width: 100%; padding: 10px; margin: 5px 0; }
            .error { color: red; margin: 10px 0; }
        </style></head>
        <body>
        <h2>🔒 Error Log Viewer Access</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required>
            </div>
            <input type="submit" value="Access Logs">
        </form>
        </body></html>
        <?php
        exit;
    }
}

// Logout handling
if (isset($_GET['logout'])) {
    unset($_SESSION['error_viewer_auth']);
    header('Location: error_viewer.php');
    exit;
}

// Error log viewer
?>
<!DOCTYPE html>
<html>
<head>
    <title>MIW Production Error Logs</title>
    <style>
        body { font-family: 'Consolas', 'Monaco', monospace; margin: 0; padding: 20px; background: #1e1e1e; color: #fff; }
        .header { background: #2d2d2d; padding: 20px; margin: -20px -20px 20px; border-bottom: 3px solid #007acc; }
        .container { max-width: 1200px; margin: 0 auto; }
        .log-section { margin: 20px 0; background: #2d2d2d; padding: 20px; border-radius: 8px; border-left: 4px solid #007acc; }
        .log-content { background: #1e1e1e; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; font-size: 12px; line-height: 1.4; white-space: pre-wrap; }
        .error-line { color: #ff6b6b; }
        .warning-line { color: #ffd93d; }
        .info-line { color: #6bcf7f; }
        .timestamp { color: #74c0fc; }
        .no-logs { color: #adb5bd; font-style: italic; }
        .refresh-btn { background: #007acc; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 10px 0; }
        .refresh-btn:hover { background: #005a9e; }
        .logout-btn { background: #dc3545; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; float: right; }
        .file-info { color: #adb5bd; font-size: 11px; margin-bottom: 10px; }
        .clear-logs-btn { background: #fd7e14; color: white; padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1>🔍 MIW Production Error Logs</h1>
            <p>Real-time error monitoring for deployed application</p>
            <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Logs</button>
            <button class="clear-logs-btn" onclick="clearLogs()">🗑️ Clear Old Logs</button>
            <a href="?logout=1"><button class="logout-btn">🚪 Logout</button></a>
        </div>
    </div>

    <div class="container">
        <?php
        
        // Function to format log lines
        function formatLogLine($line) {
            $line = htmlspecialchars($line);
            
            // Highlight timestamps
            $line = preg_replace('/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/', '<span class="timestamp">$1</span>', $line);
            
            // Highlight error types
            if (stripos($line, 'error') !== false || stripos($line, 'fatal') !== false) {
                return '<span class="error-line">' . $line . '</span>';
            } elseif (stripos($line, 'warning') !== false) {
                return '<span class="warning-line">' . $line . '</span>';
            } elseif (stripos($line, 'notice') !== false || stripos($line, 'info') !== false) {
                return '<span class="info-line">' . $line . '</span>';
            }
            
            return $line;
        }
        
        // Function to get log files
        function getLogFiles() {
            $logFiles = [];
            
            // Check various log locations
            $locations = [
                __DIR__ . '/error_logs/',
                __DIR__ . '/logs/',
                __DIR__ . '/temp/',
                '/tmp/',
                '/var/log/',
                ini_get('error_log') ? dirname(ini_get('error_log')) . '/' : null
            ];
            
            foreach ($locations as $location) {
                if (!$location || !is_dir($location)) continue;
                
                $files = glob($location . '*.{log,txt}', GLOB_BRACE);
                foreach ($files as $file) {
                    if (is_readable($file)) {
                        $logFiles[] = $file;
                    }
                }
            }
            
            // Also check for PHP error log
            $phpErrorLog = ini_get('error_log');
            if ($phpErrorLog && file_exists($phpErrorLog) && is_readable($phpErrorLog)) {
                $logFiles[] = $phpErrorLog;
            }
            
            return array_unique($logFiles);
        }
        
        // Function to tail log file
        function tailLog($file, $lines = 50) {
            if (!file_exists($file) || !is_readable($file)) {
                return ["File not accessible: $file"];
            }
            
            $content = file($file, FILE_IGNORE_NEW_LINES);
            if (!$content) {
                return ["File is empty or unreadable"];
            }
            
            return array_slice($content, -$lines);
        }
        
        // Get all log files
        $logFiles = getLogFiles();
        
        if (empty($logFiles)) {
            echo '<div class="log-section">';
            echo '<h3>❌ No Log Files Found</h3>';
            echo '<div class="log-content">';
            echo '<div class="no-logs">No accessible log files found in standard locations.</div>';
            echo '<div class="file-info">Checked locations:</div>';
            echo '<div class="file-info">• ' . __DIR__ . '/error_logs/</div>';
            echo '<div class="file-info">• ' . __DIR__ . '/logs/</div>';
            echo '<div class="file-info">• /tmp/</div>';
            echo '<div class="file-info">• PHP error_log: ' . (ini_get('error_log') ?: 'Not set') . '</div>';
            echo '</div>';
            echo '</div>';
        } else {
            foreach ($logFiles as $logFile) {
                echo '<div class="log-section">';
                echo '<h3>📄 ' . basename($logFile) . '</h3>';
                echo '<div class="file-info">Location: ' . $logFile . '</div>';
                echo '<div class="file-info">Size: ' . (file_exists($logFile) ? number_format(filesize($logFile)) . ' bytes' : 'Unknown') . '</div>';
                echo '<div class="file-info">Modified: ' . (file_exists($logFile) ? date('Y-m-d H:i:s', filemtime($logFile)) : 'Unknown') . '</div>';
                
                echo '<div class="log-content">';
                
                $lines = tailLog($logFile, 100);
                if (empty($lines) || (count($lines) == 1 && strpos($lines[0], 'empty') !== false)) {
                    echo '<div class="no-logs">No recent log entries</div>';
                } else {
                    foreach ($lines as $line) {
                        if (trim($line)) {
                            echo formatLogLine($line) . "\n";
                        }
                    }
                }
                
                echo '</div>';
                echo '</div>';
            }
        }
        
        // Show current error reporting settings
        echo '<div class="log-section">';
        echo '<h3>⚙️ PHP Error Configuration</h3>';
        echo '<div class="log-content">';
        echo 'Error Reporting: ' . error_reporting() . "\n";
        echo 'Display Errors: ' . (ini_get('display_errors') ? 'On' : 'Off') . "\n";
        echo 'Log Errors: ' . (ini_get('log_errors') ? 'On' : 'Off') . "\n";
        echo 'Error Log File: ' . (ini_get('error_log') ?: 'Not set') . "\n";
        echo 'Max Execution Time: ' . ini_get('max_execution_time') . " seconds\n";
        echo 'Memory Limit: ' . ini_get('memory_limit') . "\n";
        echo 'Upload Max Filesize: ' . ini_get('upload_max_filesize') . "\n";
        echo 'Environment: ' . (isset($_ENV['DYNO']) ? 'Heroku' : 'Local/Other') . "\n";
        echo '</div>';
        echo '</div>';
        
        // Show recent HTTP errors if available
        if (function_exists('apache_get_modules') || isset($_SERVER['SERVER_SOFTWARE'])) {
            echo '<div class="log-section">';
            echo '<h3>🌐 Server Information</h3>';
            echo '<div class="log-content">';
            echo 'Server Software: ' . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
            echo 'PHP Version: ' . PHP_VERSION . "\n";
            echo 'Server Name: ' . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "\n";
            echo 'Document Root: ' . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
            echo '</div>';
            echo '</div>';
        }
        ?>
    </div>

    <script>
        function clearLogs() {
            if (confirm('Are you sure you want to clear old log files? This cannot be undone.')) {
                fetch('error_viewer.php?action=clear_logs', {method: 'POST'})
                .then(() => location.reload())
                .catch(err => alert('Error clearing logs: ' + err));
            }
        }
        
        // Auto-refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>

<?php
// Handle log clearing
if (isset($_GET['action']) && $_GET['action'] === 'clear_logs') {
    $logFiles = getLogFiles();
    foreach ($logFiles as $logFile) {
        if (is_writable($logFile)) {
            file_put_contents($logFile, '');
        }
    }
    echo json_encode(['success' => true]);
    exit;
}
?>
