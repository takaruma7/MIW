<?php
require_once 'config.php';

// Simulate the actual export request
$_POST['pak_id'] = '12';  // Use package 12 which has 3 jamaah
$_POST['export_type'] = 'manifest';

// Capture the export response
ob_start();
include 'export_manifest.php';
$response = ob_get_clean();

echo "=== EXPORT TEST RESPONSE ===\n";
echo "Raw response:\n";
echo $response . "\n\n";

// Try to decode JSON response
$data = json_decode($response, true);
if ($data) {
    echo "=== PARSED RESPONSE ===\n";
    echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
    
    if ($data['success']) {
        echo "Number of manifest records: " . count($data['data']['manifest']) . "\n";
        echo "Package name: " . $data['data']['package']['name'] . "\n";
        echo "Package type: " . $data['data']['package']['type'] . "\n";
        
        if (count($data['data']['manifest']) > 0) {
            echo "\nFirst manifest record:\n";
            $first = $data['data']['manifest'][0];
            foreach ($first as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
        }
    } else {
        echo "Error: " . $data['message'] . "\n";
    }
} else {
    echo "Failed to decode JSON response\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>
