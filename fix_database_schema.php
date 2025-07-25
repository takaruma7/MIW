<?php
// Quick Database Schema Fix for Heroku PostgreSQL
// This will fix the schema mismatch and table creation issues

// Force Heroku environment detection
require_once 'config.heroku.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Schema - MIW</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; border-left: 4px solid #17a2b8; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        h1 { color: #333; text-align: center; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Schema Fix</h1>
        
        <?php
        echo "<div class='info'>🗄️ Connecting to Heroku PostgreSQL database...</div>";
        
        try {
            // Read and execute the exact PostgreSQL schema that matches MySQL data_miw
            $sqlFile = __DIR__ . '/init_database_postgresql_exact_miw.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("PostgreSQL exact MIW schema file not found: $sqlFile");
            }
            
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                throw new Exception("Failed to read PostgreSQL schema file");
            }
            
            echo "<div class='info'>📂 Reading exact MIW schema from: $sqlFile</div>";
            echo "<div class='info'>📦 Schema size: " . number_format(strlen($sql)) . " bytes</div>";
            
            // Execute the SQL
            $pdo->exec($sql);
            
            echo "<div class='success'>✅ Database schema created successfully!</div>";
            echo "<div class='info'>📋 Schema source: MySQL data_miw backup (exact match)</div>";
            
            // Test the tables
            $result = $pdo->query("SELECT COUNT(*) as count FROM data_paket")->fetch();
            echo "<div class='success'>✅ Found {$result['count']} packages in database</div>";
            
            $result = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
            echo "<div class='success'>✅ Created tables: " . implode(', ', $result) . "</div>";
            
            // Check if data_pembatalan table exists and is properly structured
            $pembatalanCheck = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = 'data_pembatalan'")->fetchColumn();
            if ($pembatalanCheck > 0) {
                echo "<div class='success'>✅ data_pembatalan table created successfully</div>";
                
                // Test admin_pembatalan.php compatibility
                try {
                    $testQuery = $pdo->prepare("SELECT COUNT(*) FROM data_pembatalan p JOIN data_jamaah j ON p.nik = j.nik");
                    $testQuery->execute();
                    echo "<div class='success'>✅ admin_pembatalan.php JOIN query compatibility verified</div>";
                } catch (Exception $e) {
                    echo "<div class='warning'>⚠️ admin_pembatalan.php JOIN test: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
            
            echo "<div class='info'>🎉 Your database is now ready! You can test your application.</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>📋 This error might occur if tables already exist or there's a connection issue.</div>";
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
        ?>
        
        <h2>📋 Next Steps</h2>
        <div style="text-align: center; margin-top: 30px;">
            <a href="form_haji.php" class="btn">🕋 Test Haji Registration</a>
            <a href="form_umroh.php" class="btn">🕌 Test Umroh Registration</a>
            <a href="admin_dashboard.php" class="btn">👨‍💼 Admin Dashboard</a>
            <a href="database_diagnostic.php" class="btn">🔍 Run Diagnostics</a>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <strong>Environment:</strong> Heroku PostgreSQL<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
