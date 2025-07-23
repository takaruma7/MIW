<?php
require_once 'config.php';

// Set proper headers for JSON response
header('Content-Type: application/json');

if (!isset($_POST['pak_id']) || !isset($_POST['export_type'])) {http_response_code(400);
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
    
    // Get jamaah data directly from data_jamaah table
    $jamaahs = [];
    $stmt = $conn->prepare("
        SELECT j.*
        FROM data_jamaah j
        WHERE j.pak_id = ?
        ORDER BY j.nama
    ");
    $stmt->execute([$pakId]);
    $jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $manifestData = [];
    $roomListsData = [
        'medinah' => [],
        'makkah' => []
    ];
    
    if ($exportType === 'manifest') {
        // Get room configurations
        $medinahRooms = json_decode($package['hotel_medinah_rooms'], true) ?: [];
        $makkahRooms = json_decode($package['hotel_makkah_rooms'], true) ?: [];
        // Prepare manifest export data
        $counter = 1;
        foreach ($jamaahs as $jamaah) {
            // Calculate age if needed
            $age = $jamaah['umur'] ?? '';
            if (empty($age) && !empty($jamaah['tanggal_lahir'])) {
                $age = date_diff(date_create($jamaah['tanggal_lahir']), date_create('today'))->y;
            }
            
            // Determine marketing name
            $marketingName = $jamaah['marketing_nama'] ?? '';
            if ($marketingName === $jamaah['nama']) {
                $marketingName = 'Eli Rahmalia';
            }
            
            // Strictly format data according to manifest template
            $manifestData[] = [
                'No' => $counter++,
                'Sex' => $jamaah['jenis_kelamin'] === 'Laki-laki' ? 'MR' : 'MRS',
                'Name of Passport' => strtoupper($jamaah['nama_paspor'] ?? $jamaah['nama']),
                'Marketing' => strtoupper($marketingName),
                'Nama Ayah' => strtoupper($jamaah['nama_ayah'] ?? ''),
                'Birth: Date' => $jamaah['tanggal_lahir'] ? date('d/m/Y', strtotime($jamaah['tanggal_lahir'])) : '',
                'Birth: City' => strtoupper($jamaah['tempat_lahir'] ?? ''),
                'Passport: No.Passport' => strtoupper($jamaah['no_paspor'] ?? ''),
                'Passport: Issuing Office' => strtoupper($jamaah['tempat_pembuatan_paspor'] ?? ''),
                'Passport: Date of Issue' => $jamaah['tanggal_pengeluaran_paspor'] ? date('d/m/Y', strtotime($jamaah['tanggal_pengeluaran_paspor'])) : '',
                'Passport: Date of Expiry' => $jamaah['tanggal_habis_berlaku'] ? date('d/m/Y', strtotime($jamaah['tanggal_habis_berlaku'])) : '',
                'Relation' => strtoupper($jamaah['room_relation'] ?? $jamaah['hubungan_mahram'] ?? ''),
                'Age' => $age,
                'Cabang' => 'Bandung',
                'Roomlist' => $jamaah['type_room_pilihan'] ?? '',
                'NIK' => $jamaah['nik'],
                'Alamat' => strtoupper($jamaah['alamat'] ?? ''),
                'Keterangan' => $jamaah['request_khusus'] ?? ''
            ];
            
            // Add to room lists data
            $roomPrefix = $jamaah['room_prefix'] ?? '';
            if (!empty($roomPrefix)) {
                $roomType = substr($roomPrefix, 0, 1); // Q, T, or D
                $roomNumber = $jamaah['medinah_room_number'] ?? '';
                $makkahNumber = $jamaah['mekkah_room_number'] ?? '';
                
                // Add to Medinah rooms
                if (!empty($roomNumber)) {
                    if (!isset($roomListsData['medinah'][$roomNumber])) {
                        $roomListsData['medinah'][$roomNumber] = [
                            'Room Number' => $roomNumber,
                            'Type' => $roomType === 'Q' ? 'Quad' : ($roomType === 'T' ? 'Triple' : 'Double'),
                            'Occupants' => []
                        ];
                    }
                    $roomListsData['medinah'][$roomNumber]['Occupants'][] = $jamaah['nama'];
                }
                
                // Add to Makkah rooms
                if (!empty($makkahNumber)) {
                    if (!isset($roomListsData['makkah'][$makkahNumber])) {
                        $roomListsData['makkah'][$makkahNumber] = [
                            'Room Number' => $makkahNumber,
                            'Type' => $roomType === 'Q' ? 'Quad' : ($roomType === 'T' ? 'Triple' : 'Double'),
                            'Occupants' => []
                        ];
                    }
                    $roomListsData['makkah'][$makkahNumber]['Occupants'][] = $jamaah['nama'];
                }
            }
        }

    }
    
    // Convert room lists to flat arrays for export
    $medinahRoomList = [];
    $makkahRoomList = [];

    foreach ($roomListsData['medinah'] as $room) {
        $medinahRoomList[] = [
            'Room Number' => $room['Room Number'],
            'Type' => $room['Type'],
            'Occupants' => implode(', ', $room['Occupants'])
        ];
    }

    foreach ($roomListsData['makkah'] as $room) {
        $makkahRoomList[] = [
            'Room Number' => $room['Room Number'],
            'Type' => $room['Type'],
            'Occupants' => implode(', ', $room['Occupants'])
        ];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'data' => [
            'manifest' => $manifestData,
            'roomLists' => [
                'medinah' => $medinahRoomList,
                'makkah' => $makkahRoomList
            ],
            'package' => [
                'name' => $package['program_pilihan'],
                'type' => $package['jenis_paket'],
                'departure_date' => date('d/m/Y', strtotime($package['tanggal_keberangkatan'])),
                'hotel_medinah' => $package['hotel_medinah'],
                'hotel_medinah_hcn' => json_decode($package['hcn'], true)['medinah'] ?? '',
                'hotel_makkah' => $package['hotel_makkah'],
                'hotel_makkah_hcn' => json_decode($package['hcn'], true)['makkah'] ?? '',
                'hcn_issue_date' => json_decode($package['hcn'], true)['issued_date'] ?? '',
                'hcn_expiry_date' => json_decode($package['hcn'], true)['expiry_date'] ?? ''
            ]
        ],
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