<?php
// Database configuration for verification
$host = 'localhost';
$dbname = 'data_miw';
$username = 'root';
$password = '';

echo "=== FINAL VERIFICATION TEST ===\n\n";

// Test 1: Database connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Database connection successful\n";
} catch (PDOException $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit;
}

// Test 2: Check available packages
echo "\n=== AVAILABLE PACKAGES ===\n";
try {
    $stmt = $pdo->query("SELECT p.pak_id, p.pak_nama, p.pak_kategori, COUNT(j.jamaah_id) as jamaah_count 
                         FROM data_paket p
                         LEFT JOIN data_jamaah j ON p.pak_id = j.pak_id 
                         GROUP BY p.pak_id, p.pak_nama, p.pak_kategori 
                         ORDER BY p.pak_id");
    $packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($packages as $pkg) {
        echo "Package {$pkg['pak_id']}: {$pkg['pak_nama']} ({$pkg['pak_kategori']}) - {$pkg['jamaah_count']} jamaah\n";
    }
} catch (Exception $e) {
    echo "✗ Error checking packages: " . $e->getMessage() . "\n";
}

// Test 3: Test export for package with most jamaah
echo "\n=== TESTING EXPORT FOR PACKAGE WITH DATA ===\n";
try {
    // Find package with most jamaah
    $stmt = $pdo->query("SELECT pak_id, COUNT(*) as count 
                         FROM data_jamaah 
                         GROUP BY pak_id 
                         ORDER BY count DESC 
                         LIMIT 1");
    $bestPackage = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bestPackage) {
        $pakId = $bestPackage['pak_id'];
        echo "Testing export for package {$pakId} with {$bestPackage['count']} jamaah...\n";
        
        // Simulate the exact same query as export_manifest.php
        $manifestQuery = "
            SELECT 
                ROW_NUMBER() OVER (ORDER BY jamaah_nama) as 'No',
                CASE 
                    WHEN jamaah_sex = 'L' THEN 'MR' 
                    WHEN jamaah_sex = 'P' THEN 'MRS' 
                    ELSE jamaah_sex 
                END as 'Sex',
                UPPER(jamaah_nama) as 'Name of Passport',
                COALESCE(jamaah_marketing, 'TBD') as 'Marketing',
                UPPER(COALESCE(jamaah_father, 'TBD')) as 'Nama Ayah',
                DATE_FORMAT(STR_TO_DATE(jamaah_birth_date, '%Y-%m-%d'), '%d/%m/%Y') as 'Birth: Date',
                UPPER(COALESCE(jamaah_birth_place, 'TBD')) as 'Birth: City',
                COALESCE(jamaah_passport_no, 'TBD') as 'Passport: No.Passport',
                UPPER(COALESCE(jamaah_passport_office, 'TBD')) as 'Passport: Issuing Office',
                DATE_FORMAT(STR_TO_DATE(jamaah_passport_issue, '%Y-%m-%d'), '%d/%m/%Y') as 'Passport: Date of Issue',
                DATE_FORMAT(STR_TO_DATE(jamaah_passport_expire, '%Y-%m-%d'), '%d/%m/%Y') as 'Passport: Date of Expiry',
                UPPER(COALESCE(jamaah_relation, 'TBD')) as 'Relation',
                YEAR(CURDATE()) - YEAR(jamaah_birth_date) as 'Age',
                COALESCE(jamaah_branch, 'TBD') as 'Cabang',
                COALESCE(jamaah_room_type, 'TBD') as 'Roomlist',
                COALESCE(jamaah_nik, 'TBD') as 'NIK',
                COALESCE(jamaah_address, 'TBD') as 'Alamat',
                COALESCE(jamaah_notes, 'TBD') as 'Keterangan'
            FROM data_jamaah 
            WHERE pak_id = ? 
            ORDER BY jamaah_nama
        ";
        
        $stmt = $pdo->prepare($manifestQuery);
        $stmt->execute([$pakId]);
        $manifestData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "✓ Manifest query executed successfully\n";
        echo "✓ Retrieved " . count($manifestData) . " jamaah records\n";
        
        if (count($manifestData) > 0) {
            echo "\nSample jamaah data:\n";
            $sample = $manifestData[0];
            foreach ($sample as $key => $value) {
                echo "  {$key}: {$value}\n";
            }
        }
        
        // Test package data retrieval
        $packageQuery = "
            SELECT 
                pak_id,
                pak_nama as name,
                pak_kategori as type,
                DATE_FORMAT(pak_berangkat, '%d/%m/%Y') as departure_date,
                COALESCE(pak_hotel_madinah, 'TBD') as hotel_medinah,
                COALESCE(pak_hotel_makkah, 'TBD') as hotel_makkah,
                COALESCE(pak_hotel_madinah_hcn, 'TBD') as hotel_medinah_hcn,
                COALESCE(pak_hotel_makkah_hcn, 'TBD') as hotel_makkah_hcn,
                DATE_FORMAT(pak_hcn_issue, '%d/%m/%Y') as hcn_issue_date,
                DATE_FORMAT(pak_hcn_expire, '%d/%m/%Y') as hcn_expiry_date
            FROM data_paket 
            WHERE pak_id = ?
        ";
        
        $stmt = $pdo->prepare($packageQuery);
        $stmt->execute([$pakId]);
        $packageData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "\n✓ Package query executed successfully\n";
        echo "Package data:\n";
        foreach ($packageData as $key => $value) {
            echo "  {$key}: {$value}\n";
        }
        
    } else {
        echo "✗ No packages with jamaah found\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error testing export: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "The export system should work correctly.\n";
echo "Try using the comprehensive_test.html file to test the full export workflow.\n";
?>
