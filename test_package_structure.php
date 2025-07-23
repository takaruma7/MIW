<?php
require_once 'config.php';

// Check the HCN data structure
echo "Checking package data structure...\n";

try {
    $stmt = $conn->prepare("SELECT * FROM data_paket WHERE pak_id = 5");
    $stmt->execute();
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($package) {
        echo "Package HCN field:\n";
        echo $package['hcn'] . "\n\n";
        
        $hcn_data = json_decode($package['hcn'], true);
        echo "Parsed HCN data:\n";
        print_r($hcn_data);
        
        echo "\nIndividual HCN fields:\n";
        echo "Medinah HCN: " . (isset($hcn_data['medinah']) ? $hcn_data['medinah'] : 'N/A') . "\n";
        echo "Makkah HCN: " . (isset($hcn_data['makkah']) ? $hcn_data['makkah'] : 'N/A') . "\n";
        echo "Issue Date: " . (isset($hcn_data['issued_date']) ? $hcn_data['issued_date'] : 'N/A') . "\n";
        echo "Expiry Date: " . (isset($hcn_data['expiry_date']) ? $hcn_data['expiry_date'] : 'N/A') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
