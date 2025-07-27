<?php
set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Simple config test...\n";

try {
    require_once 'config_clean.php';
    echo "SUCCESS: Config loaded\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
