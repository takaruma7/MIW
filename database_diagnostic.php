<?php
// Database Diagnostic Tool for Heroku Deployment
// This will help identify the exact database connection and schema issues

// Detect environment and load appropriate config
if (isset($_ENV['DATABASE_URL']) || file_exists('config.heroku.php')) {
    // Heroku deployment detected
    require_once 'config.heroku.php';
    $dbType = 'postgresql';
} elseif (file_exists('config.render.php') && ($_ENV['APP_ENV'] ?? '') === 'production') {
    // Render deployment detected
    require_once 'config.render.php';
    $dbType = 'postgresql';
} else {
    // Local or Railway deployment
    require_once 'config.php';
    $dbType = 'mysql';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; border-left: 4px solid #17a2b8; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; }
        h1 { color: #333; text-align: center; }
        h2 { color: #666; border-bottom: 2px solid #007bff; padding-bottom: 10px; margin-top: 30px; }
        .section { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 MIW Database Diagnostic Tool</h1>
        
        <div class="section">
            <h2>📊 Environment Information</h2>
            <div class="info">
                <strong>Detected Environment:</strong> <?php echo strtoupper($dbType); ?><br>
                <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?><br>
                <strong>App Environment:</strong> <?php echo $_ENV['APP_ENV'] ?? 'Not set'; ?>
            </div>
        </div>

        <div class="section">
            <h2>🔗 Database Connection Test</h2>
            <?php
            try {
                if (isset($pdo)) {
                    echo "<div class='success'>✅ Database connection successful!</div>";
                    
                    // Get database info
                    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
                    $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                    
                    echo "<div class='info'>";
                    echo "<strong>Database Driver:</strong> " . strtoupper($driver) . "<br>";
                    echo "<strong>Database Version:</strong> " . $version . "<br>";
                    echo "</div>";
                    
                } else {
                    echo "<div class='error'>❌ Database connection object not found!</div>";
                }
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
        </div>

        <div class="section">
            <h2>🗄️ Database Schema Analysis</h2>
            <?php
            if (isset($pdo)) {
                try {
                    if ($dbType === 'postgresql') {
                        // Check PostgreSQL tables
                        $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (empty($tables)) {
                            echo "<div class='warning'>⚠️ No tables found in database. Database needs initialization.</div>";
                        } else {
                            echo "<div class='success'>✅ Found " . count($tables) . " tables in database:</div>";
                            echo "<div class='code'>";
                            foreach ($tables as $table) {
                                echo "• " . htmlspecialchars($table) . "<br>";
                            }
                            echo "</div>";
                        }
                        
                        // Check if expected tables exist
                        $expectedTables = ['data_jamaah', 'paket', 'dokumen', 'pembatalan'];
                        $missingTables = [];
                        foreach ($expectedTables as $table) {
                            if (!in_array($table, $tables)) {
                                $missingTables[] = $table;
                            }
                        }
                        
                        if (!empty($missingTables)) {
                            echo "<div class='error'>❌ Missing required tables: " . implode(', ', $missingTables) . "</div>";
                        } else {
                            echo "<div class='success'>✅ All required tables are present!</div>";
                        }
                        
                    } else {
                        // Check MySQL tables
                        $stmt = $pdo->query("SHOW TABLES");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (empty($tables)) {
                            echo "<div class='warning'>⚠️ No tables found in database. Database needs initialization.</div>";
                        } else {
                            echo "<div class='success'>✅ Found " . count($tables) . " tables in database:</div>";
                            echo "<div class='code'>";
                            foreach ($tables as $table) {
                                echo "• " . htmlspecialchars($table) . "<br>";
                            }
                            echo "</div>";
                        }
                    }
                    
                } catch (PDOException $e) {
                    echo "<div class='error'>❌ Failed to analyze database schema: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>🔧 Configuration Analysis</h2>
            <div class="code">
                <strong>Environment Variables:</strong><br>
                DATABASE_URL: <?php echo isset($_ENV['DATABASE_URL']) ? 'Set (' . substr($_ENV['DATABASE_URL'], 0, 30) . '...)' : 'Not set'; ?><br>
                APP_ENV: <?php echo $_ENV['APP_ENV'] ?? 'Not set'; ?><br>
                SMTP_HOST: <?php echo $_ENV['SMTP_HOST'] ?? 'Not set'; ?><br>
                SMTP_USERNAME: <?php echo $_ENV['SMTP_USERNAME'] ?? 'Not set'; ?><br>
                <br>
                <strong>File Existence Check:</strong><br>
                config.heroku.php: <?php echo file_exists('config.heroku.php') ? '✅ Exists' : '❌ Missing'; ?><br>
                config.render.php: <?php echo file_exists('config.render.php') ? '✅ Exists' : '❌ Missing'; ?><br>
                init_database_postgresql.sql: <?php echo file_exists('init_database_postgresql.sql') ? '✅ Exists' : '❌ Missing'; ?><br>
                init_database_postgresql_fixed.sql: <?php echo file_exists('init_database_postgresql_fixed.sql') ? '✅ Exists' : '❌ Missing'; ?><br>
            </div>
        </div>

        <div class="section">
            <h2>🚨 Specific Issues Found</h2>
            <?php
            $issues = [];
            
            // Check if init file reference is correct
            if ($dbType === 'postgresql' && !file_exists('init_database_postgresql_fixed.sql')) {
                $issues[] = "Missing PostgreSQL initialization file: init_database_postgresql_fixed.sql";
            }
            
            // Check database connection variables
            if (!isset($_ENV['DATABASE_URL']) && $dbType === 'postgresql') {
                $issues[] = "DATABASE_URL environment variable not set for PostgreSQL";
            }
            
            // Check if tables exist but wrong schema
            if (isset($pdo) && $dbType === 'postgresql') {
                try {
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'data_jamaah'");
                    $result = $stmt->fetch();
                    if ($result['count'] == 0) {
                        $issues[] = "Main application table 'data_jamaah' not found in PostgreSQL database";
                    }
                } catch (Exception $e) {
                    $issues[] = "Cannot check table existence: " . $e->getMessage();
                }
            }
            
            if (empty($issues)) {
                echo "<div class='success'>✅ No critical issues detected!</div>";
            } else {
                foreach ($issues as $issue) {
                    echo "<div class='error'>❌ " . htmlspecialchars($issue) . "</div>";
                }
            }
            ?>
        </div>

        <div class="section">
            <h2>💡 Recommended Actions</h2>
            <div class="info">
                <strong>Next Steps:</strong><br>
                <?php if ($dbType === 'postgresql'): ?>
                1. Click <a href="fix_database_schema.php" style="color: #007bff;">Fix Database Schema</a> to automatically fix PostgreSQL schema<br>
                2. Or manually visit <a href="init_database_universal.php" style="color: #007bff;">Database Initialization</a><br>
                <?php else: ?>
                1. Visit <a href="init_database_universal.php" style="color: #007bff;">Database Initialization</a><br>
                <?php endif; ?>
                3. Test your application forms: <a href="form_haji.php" style="color: #007bff;">Haji</a> | <a href="form_umroh.php" style="color: #007bff;">Umroh</a><br>
                4. Check admin dashboard: <a href="admin_dashboard.php" style="color: #007bff;">Admin Panel</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666; text-align: center;">
            Generated at <?php echo date('Y-m-d H:i:s T'); ?> | 
            Environment: <?php echo strtoupper($dbType); ?> | 
            <a href="?" style="color: #007bff;">Refresh Diagnostics</a>
        </div>
    </div>
</body>
</html>
