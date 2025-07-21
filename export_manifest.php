<?php
require_once 'config.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

if (!isset($_POST['pak_id']) || !isset($_POST['export_type'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters: pak_id or export_type',
        'error' => true
    ]);
    exit;
}

$pakId = trim($_POST['pak_id']);
$exportType = trim($_POST['export_type']);

if (empty($pakId) || empty($exportType)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters: pak_id or export_type cannot be empty',
        'error' => true
    ]);
    exit;
}

try {
    // Validate connection
    if (!$conn instanceof PDO) {
        throw new Exception("Database connection error");
    }

    // Get package details
    $stmt = $conn->prepare("SELECT * FROM data_paket WHERE pak_id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare package query");
    }
    
    $stmt->execute([$pakId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$package) {
        throw new Exception("Package not found: " . $pakId);
    }
    
    // Get jamaah for this package
    $stmt = $conn->prepare("
        SELECT j.*, 
               m.room_prefix, 
               m.medinah_number, 
               m.mekkah_number, 
               m.relation,
               j.hubungan_mahram
        FROM data_jamaah j
        LEFT JOIN data_manifest m ON j.nik = m.nik AND j.pak_id = m.pak_id
        WHERE j.pak_id = ?
        ORDER BY j.nama
    ");
    $stmt->execute([$pakId]);
    $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $exportData = [];
    
    if ($exportType === 'manifest') {
        // Prepare manifest export data
        $counter = 1;
        foreach ($jamaahs as $jamaah) {
            $exportData[] = [
                'No' => $counter++,
                'Sex' => $jamaah['jenis_kelamin'] === 'Laki-laki' ? 'Mr' : 'Mrs',
                'Name of Passport' => $jamaah['nama_paspor'] ?? $jamaah['nama'],
                'Marketing' => $jamaah['marketing_nama'] ?? 'Eli Rahmalia',
                'Nama Ayah' => $jamaah['nama_ayah'] ?? '',
                'Birth Date' => $jamaah['tanggal_lahir'] ? date('d/m/Y', strtotime($jamaah['tanggal_lahir'])) : '',
                'Birth City' => $jamaah['tempat_lahir'] ?? '',
                'Passport No' => $jamaah['no_paspor'] ?? '',
                'Issuing Office' => $jamaah['tempat_pembuatan_paspor'] ?? 'Bandung',
                'Date of Issue' => $jamaah['tanggal_pengeluaran_paspor'] ? date('d/m/Y', strtotime($jamaah['tanggal_pengeluaran_paspor'])) : '',
                'Date of Expiry' => $jamaah['tanggal_habis_berlaku'] ? date('d/m/Y', strtotime($jamaah['tanggal_habis_berlaku'])) : '-',
                'Relation' => $jamaah['relation'] ?? $jamaah['hubungan_mahram'] ?? '-',
                'Age' => $jamaah['umur'] ?? ($jamaah['tanggal_lahir'] ? date_diff(date_create($jamaah['tanggal_lahir']), date_create('today'))->y : ''),
                'Cabang' => 'Bandung',
                'Roomlist' => $jamaah['room_prefix'] ?? '',
                'NIK' => $jamaah['nik'],
                'Alamat' => $jamaah['alamat'] ?? '',
                'Keterangan' => $jamaah['request_khusus'] ?? ''
            ];
        }
    } elseif ($exportType === 'kelengkapan') {
        // Prepare kelengkapan export data
        $counter = 1;
        foreach ($jamaahs as $jamaah) {
            $exportData[] = [
                'No' => $counter++,
                'Gender' => $jamaah['jenis_kelamin'] === 'Laki-laki' ? 'Mr' : 'Mrs',
                'Nama' => $jamaah['nama'],
                'Passport' => $jamaah['paspor_path'] ? '✓' : '',
                'Buku Kuning' => $jamaah['bk_kuning'] ? '✓' : '',
                'Foto' => $jamaah['foto'] ? '✓' : '',
                'Fotocopy KTP' => $jamaah['fc_ktp_path'] ? '✓' : '',
                'Fotocopy Ijazah' => $jamaah['fc_ijazah_path'] ? '✓' : '',
                'Fotocopy Kartu Keluarga' => $jamaah['fc_kk_path'] ? '✓' : '',
                'Fotocopy Buku Nikah' => $jamaah['fc_bk_nikah_path'] ? '✓' : '',
                'Fotocopy Akta Kelahiran' => $jamaah['fc_akta_lahir_path'] ? '✓' : '',
                'Vaksin' => $jamaah['tanggal_vaksin_1'] ? '✓' : ''
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => $exportData,
        'type' => $exportType
    ]);
    
} catch (Exception $e) {
    error_log("Export manifest error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => true
    ]);
}