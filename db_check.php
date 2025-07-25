<?php
require_once 'config.php';

echo "Database Connection Test\n";
echo "=======================\n";

try {
    // Test database connection
    if ($pdo instanceof PDO) {
        echo "✅ Database connection: OK\n";
        
        // Get database version
        $version = $pdo->query('SELECT version()')->fetchColumn();
        echo "Database: " . substr($version, 0, 50) . "...\n\n";
        
        // Check required tables
        $tables = ['data_jamaah', 'data_paket', 'data_manifest', 'data_pembatalan'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "✅ Table '$table': $count records\n";
            } catch (Exception $e) {
                echo "❌ Table '$table': ERROR - " . $e->getMessage() . "\n";
            }
        }
        
    } else {
        echo "❌ Database connection: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?>
