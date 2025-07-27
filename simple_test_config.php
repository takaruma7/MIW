<?php
/**
 * Simple test for minimized config
 */

set_time_limit(10);
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Simple test starting...\n";

try {
    require_once 'config_minimized.php';
    echo "SUCCESS: Config loaded\n";
    echo "Environment: " . getCurrentEnvironment() . "\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
