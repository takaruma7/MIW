<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing database connection...\n";

try {
    require_once 'config.php';
    
    echo "Config file loaded\n";
    
    if (isset($conn)) {
        echo "Database connection appears to be available\n";
        
        // Test a simple query
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Tables found: " . count($tables) . "\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
        
    } else {
        echo "ERROR: Database connection not available in config.php\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
