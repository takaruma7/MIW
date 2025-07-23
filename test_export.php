<?php
require_once 'config.php';

// Simple test to check data structure
echo "Testing export functionality...\n";

try {
    // Get a package
    $stmt = $conn->prepare("SELECT * FROM data_paket LIMIT 1");
    $stmt->execute();
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($package) {
        echo "Found package: " . $package['program_pilihan'] . "\n";
        echo "Package ID: " . $package['pak_id'] . "\n";
        
        // Get jamaah for this package
        $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE pak_id = ? LIMIT 3");
        $stmt->execute([$package['pak_id']]);
        $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($jamaahs) . " jamaah for this package\n";
        
        if (count($jamaahs) > 0) {
            echo "Sample jamaah data:\n";
            $jamaah = $jamaahs[0];
            echo "Name: " . ($jamaah['nama'] ?? 'N/A') . "\n";
            echo "NIK: " . ($jamaah['nik'] ?? 'N/A') . "\n";
            echo "Gender: " . ($jamaah['jenis_kelamin'] ?? 'N/A') . "\n";
            echo "Passport Name: " . ($jamaah['nama_paspor'] ?? 'N/A') . "\n";
            echo "Marketing: " . ($jamaah['marketing_nama'] ?? 'N/A') . "\n";
            echo "Father Name: " . ($jamaah['nama_ayah'] ?? 'N/A') . "\n";
            echo "Birth Date: " . ($jamaah['tanggal_lahir'] ?? 'N/A') . "\n";
            echo "Birth Place: " . ($jamaah['tempat_lahir'] ?? 'N/A') . "\n";
            echo "Passport No: " . ($jamaah['no_paspor'] ?? 'N/A') . "\n";
            echo "Room Type: " . ($jamaah['type_room_pilihan'] ?? 'N/A') . "\n";
            echo "Room Prefix: " . ($jamaah['room_prefix'] ?? 'N/A') . "\n";
            echo "Medinah Room: " . ($jamaah['medinah_room_number'] ?? 'N/A') . "\n";
            echo "Makkah Room: " . ($jamaah['mekkah_room_number'] ?? 'N/A') . "\n";
            echo "Room Relation: " . ($jamaah['room_relation'] ?? 'N/A') . "\n";
        }
    } else {
        echo "No packages found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
