<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create the file_metadata table
require_once 'config.php';

try {
    // Create file_metadata table with MySQL/PostgreSQL compatibility
    $sql = "CREATE TABLE IF NOT EXISTS file_metadata (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        directory VARCHAR(50) NOT NULL,
        original_name VARCHAR(255),
        file_size INT,
        mime_type VARCHAR(100),
        upload_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_heroku TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY filename_directory_unique (filename, directory)
    )";
    
    $conn->exec($sql);
    echo "File metadata table created successfully!";
    
    // Create indexes for faster lookups - using MySQL syntax
    try {
        $conn->exec("CREATE INDEX idx_file_metadata_upload_time ON file_metadata(upload_time)");
        echo "Indexes created.";
    } catch (PDOException $indexError) {
        echo "Note: Index creation might have failed, but that's okay if they already exist.";
    }
    
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
?>
