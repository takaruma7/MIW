<?php
// Directory Cleanup Script for MIW Project
// This script identifies and removes unnecessary, unused, and non-working files

// Ensure this script is run from the correct directory
if (!file_exists('config.php')) {
    die("Please run this script from the MIW project root directory.");
}

echo "<!DOCTYPE html><html><head><title>MIW Cleanup</title></head><body>";
echo "<h1>MIW Project Directory Cleanup</h1>";

// Files to definitely keep (core system files)
$coreFiles = [
    // Configuration files
    'config.php', 'config.heroku.php', 'config.production.php', 'composer.json', 'composer.lock',
    
    // Main application files
    'admin_dashboard.php', 'admin_kelengkapan.php', 'admin_manifest.php', 'admin_nav.php',
    'admin_paket.php', 'admin_pembatalan.php', 'admin_pending.php', 'admin_roomlist.php',
    'form_haji.php', 'form_pembatalan.php', 'form_umroh.php', 'invoice.php',
    'manifest_haji.php', 'manifest_umroh.php', 'closing_page.php', 'closing_page_pembatalan.php',
    
    // Core functionality files
    'submit_haji.php', 'submit_pembatalan.php', 'submit_umroh.php', 'confirm_payment.php',
    'handle_document_upload.php', 'upload_handler.php', 'file_handler.php',
    'email_functions.php', 'paket_functions.php', 'terbilang.php',
    'export_manifest.php', 'update_manifest.php', 'get_package.php', 'get_pembatalan_details.php',
    'verify_cancellation.php', 'kwitansi_template.php',
    
    // Enhanced system files (new)
    'heroku_file_manager.php', 'comprehensive_fix.php', 'database_diagnostic.php', 'fix_database_schema.php',
    
    // Tab and modal files
    'tab_kelengkapan.php', 'tab_manifest.php', 'document_modal.php',
    
    // CSS and styling
    'styles.css', 'admin_styles.css', 'invoice_styles.css',
    
    // Database files
    'init_database_postgresql_complete_miw.sql', 'add_file_metadata_table.sql',
    
    // Heroku deployment files
    'Procfile', 'render.yaml', 'Dockerfile',
    
    // Assets/images
    'miw_logo.png', 'himpuh_icon.jpg', 'iata_icon.png', 'ig_icon.jpg', 'kan_icon.jpg',
    'wa_icon.png', 'website_icon.png',
    
    // JavaScript files
    'manifest_scripts.js', 'paket_scripts.js', 'roomlist_scripts.js',
    
    // Template files
    'manifest_template.xlsx'
];

// Directories to keep
$coreDirectories = [
    'uploads', 'includes', 'js', 'public', 'vendor', 'temp', 'logs', 'error_logs',
    'backup_sql', 'documents'
];

// Files that are likely unnecessary or outdated
$suspiciousFiles = [];
$unnecessaryFiles = [];

// Scan current directory
$allFiles = scandir('.');

echo "<h2>Analyzing Directory Contents</h2>";

foreach ($allFiles as $file) {
    if ($file === '.' || $file === '..') continue;
    
    if (is_file($file)) {
        // Check if it's a core file
        if (in_array($file, $coreFiles)) {
            continue; // Keep core files
        }
        
        // Check for specific patterns that indicate unnecessary files
        $fileInfo = pathinfo($file);
        $extension = strtolower($fileInfo['extension'] ?? '');
        $basename = $fileInfo['filename'];
        
        // Files to remove
        if (
            // Backup files
            strpos($file, '.bak') !== false ||
            strpos($file, '.backup') !== false ||
            strpos($file, '~') !== false ||
            
            // Temporary files
            strpos($file, '.tmp') !== false ||
            strpos($file, '.temp') !== false ||
            
            // IDE/Editor files
            strpos($file, '.swp') !== false ||
            strpos($file, '.swo') !== false ||
            $file === '.DS_Store' ||
            $file === 'Thumbs.db' ||
            
            // Version control conflicts
            strpos($file, '.orig') !== false ||
            strpos($file, '.BACKUP') !== false ||
            strpos($file, '.BASE') !== false ||
            strpos($file, '.LOCAL') !== false ||
            strpos($file, '.REMOTE') !== false ||
            
            // Old/unused config files (keep only the ones we need)
            ($file === 'config.render.php') || // We're using Heroku, not Render
            
            // Duplicate or test files
            strpos($file, '_test') !== false ||
            strpos($file, '_old') !== false ||
            strpos($file, '_backup') !== false ||
            strpos($file, 'test_') !== false ||
            
            // Windows batch files (might be local development only)
            $extension === 'bat' ||
            
            // Apache config (not needed on Heroku)
            $file === 'apache2.conf' ||
            
            // Old SQL files that are superseded
            ($file === 'init_database_postgresql.sql') ||
            ($file === 'check_and_add_document_fields.sql') ||
            ($file === 'add_document_timestamp_fields.sql') ||
            ($file === 'create_manifest_table.sql')
        ) {
            $unnecessaryFiles[] = $file;
        } else {
            // Files that might be unnecessary but need review
            $suspiciousFiles[] = $file;
        }
    }
}

// Display findings
echo "<h3>Files Analysis</h3>";

if (!empty($unnecessaryFiles)) {
    echo "<h4>Files Recommended for Deletion:</h4>";
    echo "<ul>";
    foreach ($unnecessaryFiles as $file) {
        $reason = '';
        if (strpos($file, '.bak') !== false || strpos($file, '.backup') !== false) $reason = 'Backup file';
        elseif (strpos($file, '.tmp') !== false || strpos($file, '.temp') !== false) $reason = 'Temporary file';
        elseif (strpos($file, '.swp') !== false || $file === '.DS_Store') $reason = 'IDE/System file';
        elseif ($file === 'config.render.php') $reason = 'Unused deployment config';
        elseif ($file === 'apache2.conf') $reason = 'Not needed on Heroku';
        elseif (pathinfo($file, PATHINFO_EXTENSION) === 'bat') $reason = 'Windows batch file';
        elseif (strpos($file, '_old') !== false || strpos($file, '_backup') !== false) $reason = 'Old/backup version';
        else $reason = 'Superseded or unused';
        
        echo "<li><strong>$file</strong> - $reason</li>";
    }
    echo "</ul>";
} else {
    echo "<p>✅ No obvious unnecessary files found!</p>";
}

if (!empty($suspiciousFiles)) {
    echo "<h4>Files That Need Review:</h4>";
    echo "<ul>";
    foreach ($suspiciousFiles as $file) {
        $size = filesize($file);
        $modified = date('Y-m-d H:i:s', filemtime($file));
        echo "<li><strong>$file</strong> - Size: " . number_format($size) . " bytes, Modified: $modified</li>";
    }
    echo "</ul>";
}

// Check for empty directories
echo "<h3>Directory Analysis</h3>";
$allDirs = array_filter(scandir('.'), function($item) {
    return is_dir($item) && $item !== '.' && $item !== '..';
});

$emptyDirs = [];
foreach ($allDirs as $dir) {
    $contents = scandir($dir);
    $hasFiles = false;
    foreach ($contents as $item) {
        if ($item !== '.' && $item !== '..' && $item !== '.htaccess') {
            $hasFiles = true;
            break;
        }
    }
    if (!$hasFiles) {
        $emptyDirs[] = $dir;
    }
}

if (!empty($emptyDirs)) {
    echo "<h4>Empty Directories (may be safe to remove):</h4>";
    echo "<ul>";
    foreach ($emptyDirs as $dir) {
        echo "<li>$dir/</li>";
    }
    echo "</ul>";
}

// Safety check and automatic cleanup option
echo "<h2>Cleanup Actions</h2>";

if (!empty($unnecessaryFiles)) {
    echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>⚠️ Cleanup Recommendation</h4>";
    echo "<p>The following files have been identified as safe to remove:</p>";
    echo "<ul>";
    foreach ($unnecessaryFiles as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
    echo "<p><strong>To automatically remove these files, uncomment the cleanup code below and refresh this page.</strong></p>";
    echo "</div>";
    
    // Automatic cleanup (commented out for safety)
    /*
    foreach ($unnecessaryFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
            echo "<p>✅ Deleted: $file</p>";
        }
    }
    */
    
} else {
    echo "<div style='background: #d4edda; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
    echo "<h4>✅ Directory is Clean</h4>";
    echo "<p>No unnecessary files were found. Your project directory is well-maintained!</p>";
    echo "</div>";
}

// Final summary
echo "<h2>Summary</h2>";
echo "<p><strong>Total files analyzed:</strong> " . count($allFiles) . "</p>";
echo "<p><strong>Core files protected:</strong> " . count($coreFiles) . "</p>";
echo "<p><strong>Files recommended for deletion:</strong> " . count($unnecessaryFiles) . "</p>";
echo "<p><strong>Files needing review:</strong> " . count($suspiciousFiles) . "</p>";

echo "<div style='background: #e2e3e5; padding: 15px; border: 1px solid #d6d8db; border-radius: 5px; margin: 15px 0;'>";
echo "<h4>🔧 Manual Cleanup Instructions</h4>";
echo "<p>To clean up the identified files:</p>";
echo "<ol>";
echo "<li>Review the files listed above</li>";
echo "<li>Manually delete files you're confident are unnecessary</li>";
echo "<li>Keep backups of any files you're unsure about</li>";
echo "<li>Test your application after cleanup</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>
