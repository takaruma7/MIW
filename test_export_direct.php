<?php
require_once 'config.php';

// Simulate the export request
$_POST['pak_id'] = '5';
$_POST['export_type'] = 'manifest';

// Include and run the export script
ob_start();
include 'export_manifest.php';
$output = ob_get_clean();

echo "Export output:\n";
echo $output;
?>
