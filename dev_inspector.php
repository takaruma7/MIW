<?php
// Developer Inspector Tool - View deployed source code
// WARNING: Remove this file in production for security

// Check if this is running on Heroku or local
$isHeroku = isset($_ENV['DYNO']) || getenv('DYNO');
$environment = $isHeroku ? 'Heroku Production' : 'Local Development';

// Basic authentication for security
$valid_passwords = ['dev123', 'inspect2025'];
$password = $_GET['pwd'] ?? '';

if (!in_array($password, $valid_passwords)) {
    http_response_code(401);
    die('Access denied. Use: ?pwd=dev123');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Source Code Inspector - <?php echo $environment; ?></title>
    <style>
        body { font-family: 'Courier New', monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
        .header { background: #0066cc; color: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .section { margin: 20px 0; border: 1px solid #444; border-radius: 5px; }
        .section-header { background: #333; padding: 10px; cursor: pointer; }
        .section-content { padding: 15px; display: none; max-height: 500px; overflow-y: auto; }
        .file-content { background: #2d2d2d; padding: 10px; border-radius: 3px; white-space: pre-wrap; font-size: 12px; }
        .env-var { background: #0066cc; color: white; padding: 2px 5px; border-radius: 3px; margin: 2px; display: inline-block; }
        .warning { background: #ff6b35; color: white; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .active { display: block; }
        .file-list { columns: 3; column-gap: 20px; }
        .file-item { margin: 5px 0; padding: 5px; background: #333; border-radius: 3px; break-inside: avoid; }
        .toggle { color: #0066cc; text-decoration: none; }
    </style>
    <script>
        function toggleSection(id) {
            const content = document.getElementById(id);
            content.style.display = content.style.display === 'block' ? 'none' : 'block';
        }
    </script>
</head>
<body>
    <div class="header">
        <h1>🔍 Source Code Inspector</h1>
        <p>Environment: <strong><?php echo $environment; ?></strong> | Time: <?php echo date('Y-m-d H:i:s T'); ?></p>
    </div>

    <div class="warning">
        ⚠️ <strong>Security Warning:</strong> This inspector shows sensitive information. Remove this file before production deployment!
    </div>

    <!-- Environment Information -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('env-info')">
            <a href="#" class="toggle">📊 Environment Information</a>
        </div>
        <div id="env-info" class="section-content">
            <h3>Server Information</h3>
            <div class="file-content">
PHP Version: <?php echo PHP_VERSION; ?>

Server Software: <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>

Document Root: <?php echo $_SERVER['DOCUMENT_ROOT'] ?? getcwd(); ?>

Current Working Directory: <?php echo getcwd(); ?>

Request URI: <?php echo $_SERVER['REQUEST_URI'] ?? 'N/A'; ?>

Server Name: <?php echo $_SERVER['SERVER_NAME'] ?? 'N/A'; ?>

Is HTTPS: <?php echo isset($_SERVER['HTTPS']) ? 'Yes' : 'No'; ?>

User Agent: <?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'N/A'; ?>
            </div>

            <h3>Environment Variables</h3>
            <div>
                <?php
                $env_vars = ['APP_ENV', 'DATABASE_URL', 'DYNO', 'PORT', 'MAX_FILE_SIZE', 'MAX_EXECUTION_TIME', 'SECURE_HEADERS'];
                foreach ($env_vars as $var) {
                    $value = getenv($var) ?: $_ENV[$var] ?? 'Not Set';
                    if ($var === 'DATABASE_URL' && $value !== 'Not Set') {
                        $value = preg_replace('/:[^:@]*@/', ':***@', $value); // Hide password
                    }
                    echo "<span class='env-var'>$var: $value</span> ";
                }
                ?>
            </div>
        </div>
    </div>

    <!-- File System Structure -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('file-structure')">
            <a href="#" class="toggle">📁 File System Structure</a>
        </div>
        <div id="file-structure" class="section-content">
            <div class="file-list">
                <?php
                function scanDirectory($dir, $prefix = '') {
                    if (!is_dir($dir)) return;
                    
                    $files = scandir($dir);
                    foreach ($files as $file) {
                        if ($file === '.' || $file === '..') continue;
                        
                        $path = $dir . '/' . $file;
                        $relativePath = str_replace(getcwd() . '/', '', $path);
                        
                        if (is_dir($path)) {
                            echo "<div class='file-item'>📁 {$prefix}{$file}/</div>";
                            if (substr_count($relativePath, '/') < 3) { // Limit depth
                                scanDirectory($path, $prefix . '  ');
                            }
                        } else {
                            $size = number_format(filesize($path));
                            $modified = date('Y-m-d H:i', filemtime($path));
                            echo "<div class='file-item'>📄 {$prefix}{$file} <small>({$size} bytes, {$modified})</small></div>";
                        }
                    }
                }
                
                scanDirectory(getcwd());
                ?>
            </div>
        </div>
    </div>

    <!-- Key Configuration Files -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('config-files')">
            <a href="#" class="toggle">⚙️ Key Configuration Files</a>
        </div>
        <div id="config-files" class="section-content">
            <?php
            $key_files = [
                'config.php' => 'Database Configuration',
                'composer.json' => 'Dependencies',
                'heroku_file_manager.php' => 'Heroku File Manager',
                'file_handler.php' => 'File Handler',
                'admin_dashboard.php' => 'Admin Dashboard',
                'form_haji.php' => 'Haji Form',
                'form_umroh.php' => 'Umroh Form'
            ];
            
            foreach ($key_files as $file => $description) {
                if (file_exists($file)) {
                    echo "<h4>$description ($file)</h4>";
                    echo "<div class='file-content'>";
                    $content = file_get_contents($file);
                    
                    // Hide sensitive information
                    $content = preg_replace('/(\$.*password.*=\s*["\'])([^"\']+)(["\'])/', '$1***$3', $content);
                    $content = preg_replace('/(password["\']?\s*=>\s*["\'])([^"\']+)(["\'])/', '$1***$3', $content);
                    
                    echo htmlspecialchars(substr($content, 0, 2000));
                    if (strlen($content) > 2000) {
                        echo "\n\n... (truncated, " . strlen($content) . " total characters)";
                    }
                    echo "</div>";
                }
            }
            ?>
        </div>
    </div>

    <!-- Database Connection Test -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('db-test')">
            <a href="#" class="toggle">🗃️ Database Connection Test</a>
        </div>
        <div id="db-test" class="section-content">
            <div class="file-content">
<?php
try {
    require_once 'config.php';
    
    echo "Database Type: " . (strpos($db_config['host'], 'amazonaws.com') ? 'PostgreSQL (AWS RDS)' : 'MySQL/Other') . "\n";
    echo "Host: " . $db_config['host'] . "\n";
    echo "Database: " . $db_config['database'] . "\n";
    echo "Username: " . $db_config['username'] . "\n\n";
    
    $pdo = new PDO(
        "pgsql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['database']}",
        $db_config['username'],
        $db_config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Database connection successful!\n\n";
    
    // Get table list
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables found (" . count($tables) . "):\n";
    foreach ($tables as $table) {
        // Get row count for each table
        try {
            $count_stmt = $pdo->query("SELECT COUNT(*) FROM \"$table\"");
            $count = $count_stmt->fetchColumn();
            echo "  - $table ($count rows)\n";
        } catch (Exception $e) {
            echo "  - $table (error counting)\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database connection failed:\n";
    echo $e->getMessage();
}
?>
            </div>
        </div>
    </div>

    <!-- Recent Log Entries -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('logs')">
            <a href="#" class="toggle">📋 Recent Log Entries</a>
        </div>
        <div id="logs" class="section-content">
            <div class="file-content">
<?php
$log_dirs = ['logs', 'error_logs', 'temp'];
foreach ($log_dirs as $dir) {
    if (is_dir($dir)) {
        echo "=== $dir directory ===\n";
        $files = glob("$dir/*.{log,txt}", GLOB_BRACE);
        
        if (empty($files)) {
            echo "No log files found.\n\n";
            continue;
        }
        
        foreach (array_slice($files, -3) as $file) { // Last 3 files
            echo "\n--- " . basename($file) . " (last 500 chars) ---\n";
            $content = file_get_contents($file);
            echo substr($content, -500);
        }
        echo "\n\n";
    }
}

// Check for PHP error log
if (function_exists('ini_get')) {
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        echo "=== PHP Error Log (last 1000 chars) ===\n";
        $content = file_get_contents($error_log);
        echo substr($content, -1000);
    }
}
?>
            </div>
        </div>
    </div>

    <!-- Memory and Performance -->
    <div class="section">
        <div class="section-header" onclick="toggleSection('performance')">
            <a href="#" class="toggle">⚡ Memory and Performance</a>
        </div>
        <div id="performance" class="section-content">
            <div class="file-content">
Memory Usage: <?php echo number_format(memory_get_usage(true) / 1024 / 1024, 2); ?> MB
Peak Memory: <?php echo number_format(memory_get_peak_usage(true) / 1024 / 1024, 2); ?> MB
Memory Limit: <?php echo ini_get('memory_limit'); ?>

Execution Time Limit: <?php echo ini_get('max_execution_time'); ?> seconds
Upload Max File Size: <?php echo ini_get('upload_max_filesize'); ?>
Post Max Size: <?php echo ini_get('post_max_size'); ?>

Current Time: <?php echo date('Y-m-d H:i:s T'); ?>

Loaded Extensions (first 20):
<?php
$extensions = get_loaded_extensions();
foreach (array_slice($extensions, 0, 20) as $ext) {
    echo "  - $ext\n";
}
if (count($extensions) > 20) {
    echo "  ... and " . (count($extensions) - 20) . " more\n";
}
?>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px; text-align: center; color: #666;">
        <p>Inspector generated at <?php echo date('Y-m-d H:i:s T'); ?></p>
        <p>Remember to remove this file before production!</p>
    </div>

</body>
</html>
