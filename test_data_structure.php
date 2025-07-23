<?php
require_once 'config.php';

// Test the actual database structure and data
echo "=== DATABASE STRUCTURE & DATA TEST ===\n\n";

try {
    // Test 1: Check table structures
    echo "1. CHECKING TABLE STRUCTURES:\n";
    
    // Check data_paket structure
    $stmt = $conn->query("DESCRIBE data_paket");
    $paketColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "data_paket columns: " . implode(', ', $paketColumns) . "\n\n";
    
    // Check data_jamaah structure  
    $stmt = $conn->query("DESCRIBE data_jamaah");
    $jamaahColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "data_jamaah columns: " . implode(', ', $jamaahColumns) . "\n\n";
    
    // Test 2: Check available packages with jamaah count
    echo "2. AVAILABLE PACKAGES WITH JAMAAH:\n";
    $stmt = $conn->query("
        SELECT p.pak_id, p.program_pilihan, p.jenis_paket, p.tanggal_keberangkatan, 
               COUNT(j.nik) as jamaah_count
        FROM data_paket p 
        LEFT JOIN data_jamaah j ON p.pak_id = j.pak_id 
        GROUP BY p.pak_id 
        ORDER BY jamaah_count DESC
    ");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($packages as $pkg) {
        echo "Package {$pkg['pak_id']}: {$pkg['program_pilihan']} ({$pkg['jenis_paket']}) - {$pkg['jamaah_count']} jamaah\n";
    }
    
    // Test 3: Get a package with jamaah and test the export query
    $packageWithJamaah = null;
    foreach ($packages as $pkg) {
        if ($pkg['jamaah_count'] > 0) {
            $packageWithJamaah = $pkg;
            break;
        }
    }
    
    if ($packageWithJamaah) {
        echo "\n3. TESTING EXPORT QUERY FOR PACKAGE {$packageWithJamaah['pak_id']}:\n";
        
        // Test the actual export query
        $stmt = $conn->prepare("
            SELECT j.*
            FROM data_jamaah j
            WHERE j.pak_id = ?
            ORDER BY j.nama
        ");
        $stmt->execute([$packageWithJamaah['pak_id']]);
        $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Found " . count($jamaahs) . " jamaah records\n";
        
        if (count($jamaahs) > 0) {
            echo "\nFirst jamaah sample:\n";
            $sample = $jamaahs[0];
            
            // Show the fields that are used in export
            $exportFields = [
                'nik', 'nama', 'jenis_kelamin', 'nama_paspor', 'marketing_nama', 
                'nama_ayah', 'tanggal_lahir', 'tempat_lahir', 'no_paspor', 
                'tempat_pembuatan_paspor', 'tanggal_pengeluaran_paspor', 'tanggal_habis_berlaku',
                'room_relation', 'hubungan_mahram', 'umur', 'type_room_pilihan', 'alamat', 'request_khusus',
                'room_prefix', 'medinah_room_number', 'mekkah_room_number'
            ];
            
            foreach ($exportFields as $field) {
                $value = $sample[$field] ?? 'NULL';
                echo "  {$field}: {$value}\n";
            }
            
            // Test the manifest format processing
            echo "\n4. TESTING MANIFEST FORMAT PROCESSING:\n";
            
            $age = $sample['umur'] ?? '';
            if (empty($age) && !empty($sample['tanggal_lahir'])) {
                $age = date_diff(date_create($sample['tanggal_lahir']), date_create('today'))->y;
            }
            
            $marketingName = $sample['marketing_nama'] ?? '';
            if ($marketingName === $sample['nama']) {
                $marketingName = 'Eli Rahmalia';
            }
            
            // Format as manifest data
            $manifestRecord = [
                'No' => 1,
                'Sex' => $sample['jenis_kelamin'] === 'Laki-laki' ? 'MR' : 'MRS',
                'Name of Passport' => strtoupper($sample['nama_paspor'] ?? $sample['nama']),
                'Marketing' => strtoupper($marketingName),
                'Nama Ayah' => strtoupper($sample['nama_ayah'] ?? ''),
                'Birth: Date' => $sample['tanggal_lahir'] ? date('d/m/Y', strtotime($sample['tanggal_lahir'])) : '',
                'Birth: City' => strtoupper($sample['tempat_lahir'] ?? ''),
                'Passport: No.Passport' => strtoupper($sample['no_paspor'] ?? ''),
                'Passport: Issuing Office' => strtoupper($sample['tempat_pembuatan_paspor'] ?? ''),
                'Passport: Date of Issue' => $sample['tanggal_pengeluaran_paspor'] ? date('d/m/Y', strtotime($sample['tanggal_pengeluaran_paspor'])) : '',
                'Passport: Date of Expiry' => $sample['tanggal_habis_berlaku'] ? date('d/m/Y', strtotime($sample['tanggal_habis_berlaku'])) : '',
                'Relation' => strtoupper($sample['room_relation'] ?? $sample['hubungan_mahram'] ?? ''),
                'Age' => $age,
                'Cabang' => 'Bandung',
                'Roomlist' => $sample['type_room_pilihan'] ?? '',
                'NIK' => $sample['nik'],
                'Alamat' => strtoupper($sample['alamat'] ?? ''),
                'Keterangan' => $sample['request_khusus'] ?? ''
            ];
            
            echo "Processed manifest record:\n";
            foreach ($manifestRecord as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
        }
        
        // Test package data processing
        echo "\n5. TESTING PACKAGE DATA PROCESSING:\n";
        $stmt = $conn->prepare("SELECT * FROM data_paket WHERE pak_id = ?");
        $stmt->execute([$packageWithJamaah['pak_id']]);
        $packageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $processedPackage = [
            'name' => $packageData['program_pilihan'],
            'type' => $packageData['jenis_paket'],
            'departure_date' => date('d/m/Y', strtotime($packageData['tanggal_keberangkatan'])),
            'hotel_medinah' => $packageData['hotel_medinah'],
            'hotel_medinah_hcn' => json_decode($packageData['hcn'], true)['medinah'] ?? '',
            'hotel_makkah' => $packageData['hotel_makkah'],
            'hotel_makkah_hcn' => json_decode($packageData['hcn'], true)['makkah'] ?? '',
            'hcn_issue_date' => json_decode($packageData['hcn'], true)['issued_date'] ?? '',
            'hcn_expiry_date' => json_decode($packageData['hcn'], true)['expiry_date'] ?? ''
        ];
        
        echo "Processed package data:\n";
        foreach ($processedPackage as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        
    } else {
        echo "\nNo packages with jamaah found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
