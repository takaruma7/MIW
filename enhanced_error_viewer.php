<?php
/**
 * Enhanced Error Log Viewer for MIW Travel Application
 * 
 * This is an upgraded version that provides multi-perspective error monitoring:
 * - Web console errors (if possible via JavaScript)
 * - Heroku Apache access and error logs
 * - Heroku Database (PostgreSQL) logs  
 * - Heroku PHP-FPM logs
 * - Application error logs
 * 
 * Features:
 * - Password protected access
 * - Real-time log aggregation
 * - Error filtering and search
 * - Log export functionality
 * - Auto-refresh capability
 * - Error categorization and highlighting
 */

session_start();

// Configuration
define('LOG_PASSWORD', 'MIW2024ErrorViewer!');
define('MAX_LOG_SIZE', 1024 * 1024); // 1MB max per log file
define('MAX_LINES_PER_LOG', 1000);
define('AUTO_REFRESH_INTERVAL', 30); // seconds

// Authentication
if (isset($_GET['logout'])) {
    unset($_SESSION['log_viewer_authenticated']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (!isset($_SESSION['log_viewer_authenticated'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === LOG_PASSWORD) {
            $_SESSION['log_viewer_authenticated'] = true;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $loginError = 'Invalid password';
        }
    }
    
    // Show login form
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>MIW Enhanced Error Log Viewer - Login</title>
        <style>
            body { font-family: 'Segoe UI', sans-serif; background: #1e1e1e; color: #fff; margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
            .login-container { background: #2d2d2d; padding: 40px; border-radius: 12px; box-shadow: 0 8px 32px rgba(0,0,0,0.3); max-width: 400px; width: 90%; }
            .login-header { text-align: center; margin-bottom: 30px; }
            .login-header h1 { color: #007acc; margin: 0 0 10px; }
            .login-header p { color: #adb5bd; margin: 0; }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 8px; color: #dee2e6; font-weight: 500; }
            .form-group input { width: 100%; padding: 12px; border: 1px solid #495057; border-radius: 6px; background: #343a40; color: #fff; font-size: 14px; box-sizing: border-box; }
            .form-group input:focus { outline: none; border-color: #007acc; box-shadow: 0 0 0 3px rgba(0,122,204,0.1); }
            .login-btn { width: 100%; padding: 12px; background: #007acc; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: 500; cursor: pointer; transition: background 0.2s; }
            .login-btn:hover { background: #005a9e; }
            .error { color: #ff6b6b; text-align: center; margin-top: 15px; }
            .security-note { background: #495057; padding: 15px; border-radius: 6px; margin-top: 20px; font-size: 12px; color: #adb5bd; }
        </style>
    </head>
    <body>
        <div class="login-container">
            <div class="login-header">
                <h1>🔍 Enhanced Error Log Viewer</h1>
                <p>Multi-perspective error monitoring for MIW Travel</p>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required autofocus>
                </div>
                <button type="submit" class="login-btn">🔓 Access Logs</button>
                <?php if (isset($loginError)): ?>
                    <div class="error">❌ <?= htmlspecialchars($loginError) ?></div>
                <?php endif; ?>
            </form>
            <div class="security-note">
                🔒 This tool provides access to sensitive error logs and system information. Unauthorized access is prohibited.
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'refresh_logs':
            echo json_encode(['success' => true, 'logs' => getAllLogs()]);
            break;
            
        case 'clear_logs':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $result = clearOldLogs();
                echo json_encode($result);
            }
            break;
            
        case 'export_logs':
            exportLogs();
            break;
            
        case 'get_log_stats':
            echo json_encode(getLogStatistics());
            break;
            
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
    exit;
}

/**
 * Get all available log sources and their content
 */
function getAllLogs() {
    $logs = [];
    
    // 1. Application Error Logs
    $logs['application'] = getApplicationLogs();
    
    // 2. PHP Error Logs (Heroku PHP-FPM)
    $logs['php'] = getPHPErrorLogs();
    
    // 3. Apache Access/Error Logs (Heroku)
    $logs['apache'] = getApacheLogs();
    
    // 4. Database Logs (PostgreSQL on Heroku)
    $logs['database'] = getDatabaseLogs();
    
    // 5. System Logs
    $logs['system'] = getSystemLogs();
    
    return $logs;
}

/**
 * Get application-specific error logs
 */
function getApplicationLogs() {
    $logs = [];
    $logDir = __DIR__ . '/error_logs';
    
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        
        foreach ($files as $file) {
            if (is_readable($file)) {
                $logs[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'content' => tailLog($file, MAX_LINES_PER_LOG),
                    'type' => 'application'
                ];
            }
        }
    }
    
    return $logs;
}

/**
 * Get PHP error logs from Heroku
 */
function getPHPErrorLogs() {
    $logs = [];
    $phpLogPaths = [
        '/tmp/heroku.php-fpm.www.*.log',
        '/app/vendor/heroku/heroku-buildpack-php/conf/php/php-fpm.conf.*.log',
        '/tmp/php_errors.log'
    ];
    
    foreach ($phpLogPaths as $pattern) {
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_readable($file) && filesize($file) > 0) {
                $logs[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'content' => tailLog($file, MAX_LINES_PER_LOG),
                    'type' => 'php'
                ];
            }
        }
    }
    
    return $logs;
}

/**
 * Get Apache access and error logs from Heroku
 */
function getApacheLogs() {
    $logs = [];
    $apacheLogPaths = [
        '/tmp/heroku.apache2_access.*.log',
        '/tmp/heroku.apache2_error.*.log',
        '/app/apache/logs/access.log',
        '/app/apache/logs/error.log'
    ];
    
    foreach ($apacheLogPaths as $pattern) {
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_readable($file) && filesize($file) > 0) {
                $logs[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'content' => tailLog($file, MAX_LINES_PER_LOG),
                    'type' => 'apache'
                ];
            }
        }
    }
    
    return $logs;
}

/**
 * Get database logs (PostgreSQL)
 */
function getDatabaseLogs() {
    $logs = [];
    
    // Try to get PostgreSQL logs if available
    $dbLogPaths = [
        '/tmp/postgresql.log',
        '/app/postgresql/logs/*.log',
        '/var/log/postgresql/*.log'
    ];
    
    foreach ($dbLogPaths as $pattern) {
        $files = glob($pattern);
        foreach ($files as $file) {
            if (is_readable($file) && filesize($file) > 0) {
                $logs[] = [
                    'name' => basename($file),
                    'path' => $file,
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                    'content' => tailLog($file, MAX_LINES_PER_LOG),
                    'type' => 'database'
                ];
            }
        }
    }
    
    // If no direct log files, try to get recent database errors from application logs
    if (empty($logs)) {
        $dbErrors = getRecentDatabaseErrors();
        if (!empty($dbErrors)) {
            $logs[] = [
                'name' => 'recent_db_errors.log',
                'path' => 'virtual',
                'size' => strlen(implode("\n", $dbErrors)),
                'modified' => time(),
                'content' => implode("\n", $dbErrors),
                'type' => 'database'
            ];
        }
    }
    
    return $logs;
}

/**
 * Get system logs
 */
function getSystemLogs() {
    $logs = [];
    $systemLogPaths = [
        '/tmp/system.log',
        '/var/log/syslog',
        '/var/log/messages'
    ];
    
    foreach ($systemLogPaths as $file) {
        if (is_readable($file) && filesize($file) > 0) {
            $logs[] = [
                'name' => basename($file),
                'path' => $file,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'content' => tailLog($file, 50), // Fewer lines for system logs
                'type' => 'system'
            ];
        }
    }
    
    return $logs;
}

/**
 * Get recent database errors from application logs
 */
function getRecentDatabaseErrors() {
    $errors = [];
    $logDir = __DIR__ . '/error_logs';
    
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        
        foreach ($files as $file) {
            if (is_readable($file)) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (stripos($line, 'database') !== false || 
                        stripos($line, 'postgresql') !== false ||
                        stripos($line, 'connection') !== false ||
                        stripos($line, 'pdo') !== false) {
                        $errors[] = $line;
                    }
                }
            }
        }
    }
    
    return array_slice(array_unique($errors), -100); // Last 100 unique DB errors
}

/**
 * Get last N lines from a log file
 */
function tailLog($file, $lines = 100) {
    if (!is_readable($file)) {
        return "Log file not accessible: $file";
    }
    
    $fileSize = filesize($file);
    if ($fileSize > MAX_LOG_SIZE) {
        return "Log file too large (" . formatBytes($fileSize) . "). Showing last " . formatBytes(MAX_LOG_SIZE) . ":\n\n" . 
               file_get_contents($file, false, null, -MAX_LOG_SIZE);
    }
    
    $content = file_get_contents($file);
    $allLines = explode("\n", $content);
    $tailLines = array_slice($allLines, -$lines);
    
    return implode("\n", $tailLines);
}

/**
 * Format log line with syntax highlighting
 */
function formatLogLine($line) {
    // Error patterns
    if (preg_match('/\b(FATAL|ERROR|CRITICAL|Exception|Fatal)\b/i', $line)) {
        return '<span class="error-line">' . htmlspecialchars($line) . '</span>';
    }
    
    // Warning patterns
    if (preg_match('/\b(WARNING|WARN|Notice)\b/i', $line)) {
        return '<span class="warning-line">' . htmlspecialchars($line) . '</span>';
    }
    
    // Info patterns
    if (preg_match('/\b(INFO|SUCCESS|OK)\b/i', $line)) {
        return '<span class="info-line">' . htmlspecialchars($line) . '</span>';
    }
    
    // Timestamp highlighting
    $line = preg_replace('/(\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/', '<span class="timestamp">$1</span>', htmlspecialchars($line));
    $line = preg_replace('/(\[\d{2}-[A-Za-z]{3}-\d{4} \d{2}:\d{2}:\d{2} UTC\])/', '<span class="timestamp">$1</span>', $line);
    
    return $line;
}

/**
 * Clear old log files
 */
function clearOldLogs() {
    $cleared = 0;
    $logDir = __DIR__ . '/error_logs';
    
    if (is_dir($logDir)) {
        $files = glob($logDir . '/*.log');
        $cutoff = time() - (7 * 24 * 60 * 60); // 7 days ago
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $cleared++;
                }
            }
        }
    }
    
    return ['success' => true, 'cleared' => $cleared];
}

/**
 * Export logs as downloadable file
 */
function exportLogs() {
    $logs = getAllLogs();
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "miw_error_logs_export_{$timestamp}.txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo "MIW Travel - Error Log Export\n";
    echo "Generated: " . date('Y-m-d H:i:s') . " UTC\n";
    echo str_repeat('=', 80) . "\n\n";
    
    foreach ($logs as $category => $categoryLogs) {
        if (empty($categoryLogs)) continue;
        
        echo strtoupper($category) . " LOGS\n";
        echo str_repeat('-', 40) . "\n";
        
        foreach ($categoryLogs as $log) {
            echo "File: {$log['name']}\n";
            echo "Path: {$log['path']}\n";
            echo "Size: " . formatBytes($log['size']) . "\n";
            echo "Modified: " . date('Y-m-d H:i:s', $log['modified']) . "\n";
            echo "Content:\n";
            echo $log['content'] . "\n";
            echo str_repeat('-', 80) . "\n\n";
        }
    }
    exit;
}

/**
 * Get log statistics
 */
function getLogStatistics() {
    $logs = getAllLogs();
    $stats = [
        'total_logs' => 0,
        'total_size' => 0,
        'categories' => [],
        'recent_errors' => 0,
        'last_error' => null
    ];
    
    foreach ($logs as $category => $categoryLogs) {
        $categoryCount = count($categoryLogs);
        $categorySize = 0;
        
        foreach ($categoryLogs as $log) {
            $categorySize += $log['size'];
            $stats['total_logs']++;
            $stats['total_size'] += $log['size'];
            
            // Count recent errors (last 24 hours)
            if ($log['modified'] > (time() - 86400)) {
                $errorCount = substr_count(strtolower($log['content']), 'error') + 
                             substr_count(strtolower($log['content']), 'fatal') +
                             substr_count(strtolower($log['content']), 'exception');
                $stats['recent_errors'] += $errorCount;
                
                // Get last error timestamp
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\].*(?:error|fatal|exception)/i', $log['content'], $matches)) {
                    $errorTime = strtotime($matches[1]);
                    if (!$stats['last_error'] || $errorTime > $stats['last_error']) {
                        $stats['last_error'] = $errorTime;
                    }
                }
            }
        }
        
        $stats['categories'][$category] = [
            'count' => $categoryCount,
            'size' => $categorySize
        ];
    }
    
    return $stats;
}

/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Get initial log data
$allLogs = getAllLogs();
$stats = getLogStatistics();
?>
<!DOCTYPE html>
<html>
<head>
    <title>MIW Enhanced Error Log Viewer</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', 'Monaco', 'Consolas', monospace; margin: 0; padding: 0; background: #0d1117; color: #c9d1d9; }
        
        .header { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); padding: 20px; border-bottom: 3px solid #0ea5e9; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .header h1 { margin: 0; color: #0ea5e9; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        .header-controls { display: flex; gap: 10px; flex-wrap: wrap; }
        
        .btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .btn-primary { background: #0ea5e9; color: white; }
        .btn-primary:hover { background: #0284c7; }
        .btn-secondary { background: #374151; color: #d1d5db; }
        .btn-secondary:hover { background: #4b5563; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-success { background: #059669; color: white; }
        .btn-success:hover { background: #047857; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #161b22; padding: 20px; border-radius: 8px; border: 1px solid #21262d; }
        .stat-card h3 { margin: 0 0 10px; font-size: 14px; color: #8b949e; text-transform: uppercase; }
        .stat-card .value { font-size: 24px; font-weight: bold; color: #0ea5e9; }
        .stat-card .label { font-size: 12px; color: #8b949e; margin-top: 5px; }
        
        .filters { background: #161b22; padding: 20px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #21262d; }
        .filters h3 { margin: 0 0 15px; color: #f0f6fc; }
        .filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: center; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-size: 12px; color: #8b949e; text-transform: uppercase; }
        .filter-group input, .filter-group select { padding: 8px 12px; border: 1px solid #30363d; border-radius: 6px; background: #0d1117; color: #c9d1d9; font-size: 14px; }
        
        .logs-container { display: grid; gap: 20px; }
        .log-category { background: #161b22; border-radius: 8px; border: 1px solid #21262d; overflow: hidden; }
        .log-category.hidden { display: none; }
        
        .category-header { background: #21262d; padding: 15px 20px; border-bottom: 1px solid #30363d; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .category-header:hover { background: #262c36; }
        .category-header h3 { margin: 0; display: flex; align-items: center; gap: 10px; }
        .category-header .badge { background: #374151; color: #d1d5db; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
        .category-header .toggle { color: #8b949e; font-size: 18px; transition: transform 0.2s; }
        .category-header.collapsed .toggle { transform: rotate(-90deg); }
        
        .category-content { display: block; }
        .category-content.collapsed { display: none; }
        
        .log-item { border-bottom: 1px solid #21262d; }
        .log-item:last-child { border-bottom: none; }
        
        .log-header { background: #0d1117; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
        .log-header:hover { background: #161b22; }
        .log-header .log-info h4 { margin: 0; color: #f0f6fc; font-size: 14px; }
        .log-header .log-info .meta { font-size: 12px; color: #8b949e; margin-top: 2px; }
        .log-header .log-controls { display: flex; gap: 8px; align-items: center; }
        .log-toggle { color: #8b949e; font-size: 16px; transition: transform 0.2s; }
        .log-toggle.expanded { transform: rotate(90deg); }
        
        .log-content { display: none; padding: 20px; background: #0d1117; font-family: 'Monaco', 'Consolas', monospace; font-size: 12px; line-height: 1.4; white-space: pre-wrap; max-height: 500px; overflow-y: auto; border: 1px solid #21262d; margin: 0 20px 20px; border-radius: 6px; }
        .log-content.expanded { display: block; }
        
        .error-line { color: #f85149; }
        .warning-line { color: #d29922; }
        .info-line { color: #56d364; }
        .timestamp { color: #79c0ff; }
        
        .search-highlight { background-color: #ffd33d; color: #000; padding: 1px 2px; border-radius: 2px; }
        
        .loading { text-align: center; padding: 40px; color: #8b949e; }
        .loading::after { content: ''; animation: spin 1s linear infinite; display: inline-block; width: 20px; height: 20px; border: 2px solid #8b949e; border-top: 2px solid #0ea5e9; border-radius: 50%; margin-left: 10px; }
        
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .no-logs { text-align: center; padding: 40px; color: #8b949e; font-style: italic; }
        
        .auto-refresh { position: fixed; bottom: 20px; right: 20px; background: #374151; color: #d1d5db; padding: 10px; border-radius: 6px; font-size: 12px; z-index: 1000; }
        
        @media (max-width: 768px) {
            .header-content { flex-direction: column; align-items: stretch; }
            .filter-row { flex-direction: column; align-items: stretch; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🔍 Enhanced Error Log Viewer</h1>
            <div class="header-controls">
                <button class="btn btn-primary" onclick="refreshLogs()">🔄 Refresh</button>
                <button class="btn btn-secondary" onclick="exportLogs()">📥 Export</button>
                <button class="btn btn-danger" onclick="clearOldLogs()">🗑️ Clear Old</button>
                <a href="?logout=1" class="btn btn-secondary">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Logs</h3>
                <div class="value" id="total-logs"><?= $stats['total_logs'] ?></div>
                <div class="label">log files</div>
            </div>
            <div class="stat-card">
                <h3>Total Size</h3>
                <div class="value" id="total-size"><?= formatBytes($stats['total_size']) ?></div>
                <div class="label">disk usage</div>
            </div>
            <div class="stat-card">
                <h3>Recent Errors</h3>
                <div class="value" id="recent-errors" style="color: <?= $stats['recent_errors'] > 0 ? '#f85149' : '#56d364' ?>"><?= $stats['recent_errors'] ?></div>
                <div class="label">last 24h</div>
            </div>
            <div class="stat-card">
                <h3>Last Error</h3>
                <div class="value" id="last-error" style="font-size: 14px;">
                    <?= $stats['last_error'] ? date('H:i:s', $stats['last_error']) : 'None' ?>
                </div>
                <div class="label"><?= $stats['last_error'] ? date('Y-m-d', $stats['last_error']) : 'today' ?></div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <h3>🔎 Search & Filter</h3>
            <div class="filter-row">
                <div class="filter-group">
                    <label>Search in logs</label>
                    <input type="text" id="search-input" placeholder="Enter search term..." onkeyup="searchLogs(this.value)">
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select id="category-filter" onchange="filterByCategory(this.value)">
                        <option value="">All Categories</option>
                        <option value="application">Application</option>
                        <option value="php">PHP</option>
                        <option value="apache">Apache</option>
                        <option value="database">Database</option>
                        <option value="system">System</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Level</label>
                    <select id="level-filter" onchange="filterByLevel(this.value)">
                        <option value="">All Levels</option>
                        <option value="error">Errors Only</option>
                        <option value="warning">Warnings Only</option>
                        <option value="info">Info Only</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Actions</label>
                    <button class="btn btn-secondary" onclick="toggleAllLogs()">Toggle All</button>
                </div>
            </div>
        </div>

        <!-- Logs -->
        <div class="logs-container" id="logs-container">
            <?php foreach ($allLogs as $category => $categoryLogs): ?>
                <?php if (empty($categoryLogs)) continue; ?>
                <div class="log-category" data-category="<?= $category ?>">
                    <div class="category-header" onclick="toggleCategory('<?= $category ?>')">
                        <h3>
                            <?php
                            $icons = [
                                'application' => '📱',
                                'php' => '🐘',
                                'apache' => '🌐',
                                'database' => '🗄️',
                                'system' => '⚙️'
                            ];
                            echo $icons[$category] ?? '📄';
                            ?>
                            <?= ucfirst($category) ?> Logs
                            <span class="badge"><?= count($categoryLogs) ?></span>
                        </h3>
                        <span class="toggle">▼</span>
                    </div>
                    <div class="category-content" id="category-<?= $category ?>">
                        <?php foreach ($categoryLogs as $index => $log): ?>
                            <div class="log-item">
                                <div class="log-header" onclick="toggleLog('<?= $category ?>-<?= $index ?>')">
                                    <div class="log-info">
                                        <h4><?= htmlspecialchars($log['name']) ?></h4>
                                        <div class="meta">
                                            📁 <?= htmlspecialchars($log['path']) ?> • 
                                            📏 <?= formatBytes($log['size']) ?> • 
                                            🕒 <?= date('Y-m-d H:i:s', $log['modified']) ?>
                                        </div>
                                    </div>
                                    <div class="log-controls">
                                        <span class="log-toggle" id="toggle-<?= $category ?>-<?= $index ?>">▶</span>
                                    </div>
                                </div>
                                <div class="log-content" id="content-<?= $category ?>-<?= $index ?>">
                                    <?php
                                    $lines = explode("\n", $log['content']);
                                    foreach ($lines as $line) {
                                        if (trim($line)) {
                                            echo formatLogLine($line) . "\n";
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($allLogs) || array_sum(array_map('count', $allLogs)) === 0): ?>
            <div class="no-logs">📭 No log files found or all log files are empty.</div>
        <?php endif; ?>
    </div>

    <div class="auto-refresh" id="auto-refresh">
        Auto-refresh in <span id="refresh-countdown"><?= AUTO_REFRESH_INTERVAL ?></span>s
    </div>

    <script>
        let autoRefreshEnabled = true;
        let refreshCountdown = <?= AUTO_REFRESH_INTERVAL ?>;
        let countdownInterval;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            startAutoRefresh();
        });

        function startAutoRefresh() {
            if (countdownInterval) clearInterval(countdownInterval);
            
            countdownInterval = setInterval(function() {
                refreshCountdown--;
                document.getElementById('refresh-countdown').textContent = refreshCountdown;
                
                if (refreshCountdown <= 0) {
                    if (autoRefreshEnabled) {
                        refreshLogs();
                    }
                    refreshCountdown = <?= AUTO_REFRESH_INTERVAL ?>;
                }
            }, 1000);
        }

        function refreshLogs() {
            document.getElementById('logs-container').innerHTML = '<div class="loading">Refreshing logs...</div>';
            
            fetch('?action=refresh_logs')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload(); // Simpler approach for now
                    }
                })
                .catch(error => {
                    console.error('Error refreshing logs:', error);
                    document.getElementById('logs-container').innerHTML = '<div class="no-logs">❌ Error refreshing logs</div>';
                });
        }

        function exportLogs() {
            window.open('?action=export_logs', '_blank');
        }

        function clearOldLogs() {
            if (confirm('Are you sure you want to clear old log files? This cannot be undone.')) {
                fetch('?action=clear_logs', {method: 'POST'})
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(`Cleared ${data.cleared} old log files.`);
                            refreshLogs();
                        }
                    })
                    .catch(error => {
                        console.error('Error clearing logs:', error);
                        alert('Error clearing logs');
                    });
            }
        }

        function toggleCategory(category) {
            const content = document.getElementById('category-' + category);
            const header = content.previousElementSibling;
            const toggle = header.querySelector('.toggle');
            
            if (content.style.display === 'none') {
                content.style.display = 'block';
                toggle.textContent = '▼';
                header.classList.remove('collapsed');
            } else {
                content.style.display = 'none';
                toggle.textContent = '▶';
                header.classList.add('collapsed');
            }
        }

        function toggleLog(logId) {
            const content = document.getElementById('content-' + logId);
            const toggle = document.getElementById('toggle-' + logId);
            
            if (content.classList.contains('expanded')) {
                content.classList.remove('expanded');
                toggle.textContent = '▶';
                toggle.classList.remove('expanded');
            } else {
                content.classList.add('expanded');
                toggle.textContent = '▼';
                toggle.classList.add('expanded');
            }
        }

        function toggleAllLogs() {
            const allCategories = document.querySelectorAll('.category-content');
            let allVisible = true;
            
            allCategories.forEach(category => {
                if (category.style.display === 'none') {
                    allVisible = false;
                }
            });
            
            allCategories.forEach(category => {
                const header = category.previousElementSibling;
                const toggle = header.querySelector('.toggle');
                
                if (allVisible) {
                    category.style.display = 'none';
                    toggle.textContent = '▶';
                    header.classList.add('collapsed');
                } else {
                    category.style.display = 'block';
                    toggle.textContent = '▼';
                    header.classList.remove('collapsed');
                }
            });
        }

        function searchLogs(searchTerm) {
            const logContents = document.querySelectorAll('.log-content');
            
            logContents.forEach(content => {
                const originalText = content.textContent;
                
                if (searchTerm.trim() === '') {
                    // Reset to original formatted content
                    content.innerHTML = content.getAttribute('data-original') || content.innerHTML;
                } else {
                    // Store original if not stored
                    if (!content.getAttribute('data-original')) {
                        content.setAttribute('data-original', content.innerHTML);
                    }
                    
                    // Highlight search term
                    const regex = new RegExp(`(${searchTerm})`, 'gi');
                    const highlighted = originalText.replace(regex, '<span class="search-highlight">$1</span>');
                    content.innerHTML = highlighted;
                }
            });
        }

        function filterByCategory(category) {
            const categories = document.querySelectorAll('.log-category');
            
            categories.forEach(cat => {
                if (category === '' || cat.dataset.category === category) {
                    cat.classList.remove('hidden');
                } else {
                    cat.classList.add('hidden');
                }
            });
        }

        function filterByLevel(level) {
            const logItems = document.querySelectorAll('.log-item');
            
            logItems.forEach(item => {
                const content = item.querySelector('.log-content');
                const text = content.textContent.toLowerCase();
                
                let show = true;
                if (level === 'error' && !text.includes('error') && !text.includes('fatal') && !text.includes('exception')) {
                    show = false;
                } else if (level === 'warning' && !text.includes('warning') && !text.includes('warn')) {
                    show = false;
                } else if (level === 'info' && !text.includes('info') && !text.includes('notice')) {
                    show = false;
                }
                
                item.style.display = show ? 'block' : 'none';
            });
        }

        // Click to toggle auto-refresh
        document.getElementById('auto-refresh').addEventListener('click', function() {
            autoRefreshEnabled = !autoRefreshEnabled;
            this.style.opacity = autoRefreshEnabled ? '1' : '0.5';
            this.title = autoRefreshEnabled ? 'Auto-refresh enabled (click to disable)' : 'Auto-refresh disabled (click to enable)';
        });
    </script>
</body>
</html>
