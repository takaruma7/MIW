<?php
// submit_haji.php

require_once 'config.php';
require_once 'email_functions.php';

// Initialize response variables
$errors = [];
$success = false;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate required fields for Haji registration
        $requiredFields = [
            'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 
            'alamat', 'no_telp', 'email', 'nama_ayah',
            'pak_id', 'type_room_pilihan'
        ];
        
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $errors[] = "Field $field harus diisi.";
            }
        }
        
        // Validate NIK (16 digits)
        if (!preg_match('/^\d{16}$/', $_POST['nik'])) {
            $errors[] = "NIK harus 16 digit angka.";
        }
        
        // Validate email
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format email tidak valid.";
        }
        
        // Validate file uploads (but don't save to server)
        $requiredFiles = ['kk_path', 'ktp_path'];
        $uploadedFiles = [];
        
        foreach ($requiredFiles as $fileField) {
            if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "File $fileField harus diupload.";
                continue;
            }
            
            $file = $_FILES[$fileField];
            $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                $errors[] = "File $fileField harus berupa JPG, PNG, atau PDF.";
            }
            
            if ($file['size'] > $maxSize) {
                $errors[] = "Ukuran file $fileField tidak boleh melebihi 2MB.";
            }
            
            // Store file info for email attachment (don't move to server)
            $uploadedFiles[$fileField] = $file;
        }
        
        // If no errors, proceed with database insertion (without saving files)
        if (empty($errors)) {
            $conn->beginTransaction();
            
            // Prepare data for database
            $currentDateTime = date('Y-m-d H:i:s');
            $data = [
                'nik' => $_POST['nik'],
                'nama' => $_POST['nama'],
                'tempat_lahir' => $_POST['tempat_lahir'],
                'tanggal_lahir' => $_POST['tanggal_lahir'],
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'alamat' => $_POST['alamat'],
                'kode_pos' => $_POST['kode_pos'] ?? null,
                'email' => $_POST['email'],
                'no_telp' => $_POST['no_telp'],
                'tinggi_badan' => $_POST['tinggi_badan'] ?? null,
                'berat_badan' => $_POST['berat_badan'] ?? null,
                'nama_ayah' => $_POST['nama_ayah'],
                'umur' => $_POST['umur'],
                'kewarganegaraan' => $_POST['kewarganegaraan'],
                'desa_kelurahan' => $_POST['desa_kelurahan'],
                'kecamatan' => $_POST['kecamatan'],
                'kabupaten_kota' => $_POST['kabupaten_kota'],
                'provinsi' => $_POST['provinsi'],
                'pendidikan' => $_POST['pendidikan'],
                'pekerjaan' => $_POST['pekerjaan'],
                'golongan_darah' => $_POST['golongan_darah'],
                'status_perkawinan' => $_POST['status_perkawinan'],
                'ciri_rambut' => $_POST['ciri_rambut'] ?? null,
                'ciri_alis' => $_POST['ciri_alis'] ?? null,
                'ciri_hidung' => $_POST['ciri_hidung'] ?? null,
                'ciri_muka' => $_POST['ciri_muka'] ?? null,
                'emergency_nama' => $_POST['emergency_nama'] ?? null,
                'emergency_hp' => $_POST['emergency_hp'] ?? null,
                'nama_mahram' => $_POST['nama_mahram'] ?? null,
                'hubungan_mahram' => $_POST['hubungan_mahram'] ?? null,
                'nomor_mahram' => $_POST['nomor_mahram'] ?? null,
                'pengalaman_haji' => $_POST['pengalaman_haji'] ?? 'Belum',
                'marketing_nama' => $_POST['marketing_nama'] ?? null,
                'marketing_hp' => $_POST['marketing_hp'] ?? null,
                'marketing_type' => $_POST['marketing_type'] ?? null,
                'request_khusus' => $_POST['request_khusus'] ?? null,
                'pak_id' => $_POST['pak_id'],
                'type_room_pilihan' => $_POST['type_room_pilihan'],
                'created_at' => $currentDateTime,
                'updated_at' => $currentDateTime,
                // Set upload timestamps for files (even though we're not saving them to server)
                'kk_uploaded_at' => isset($_FILES['kk_path']) ? $currentDateTime : null,
                'ktp_uploaded_at' => isset($_FILES['ktp_path']) ? $currentDateTime : null,
                'paspor_uploaded_at' => isset($_FILES['paspor_path']) && $_FILES['paspor_path']['error'] === UPLOAD_ERR_OK ? $currentDateTime : null,
                'payment_type' => $_POST['payment_type'] ?? null,
                'payment_method' => $_POST['payment_method'] ?? null,
                // Note: payment_uploaded_at is not set here as it's for payment verification
            ];
            
            // Prepare SQL query (exclude file path fields)
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO data_jamaah ($columns) VALUES ($placeholders)";
            $stmt = $conn->prepare($sql);
            
            // Bind parameters
            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            // Execute query
            $stmt->execute();
            
            // Prepare email data
            $emailData = [
                'nik' => $_POST['nik'],
                'nama' => $_POST['nama'],
                'tanggal_lahir' => $_POST['tanggal_lahir'],
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'alamat' => $_POST['alamat'],
                'no_telp' => $_POST['no_telp'],
                'email' => $_POST['email'],
                'program_pilihan' => $_POST['program_pilihan'] ?? '',
                'type_room_pilihan' => $_POST['type_room_pilihan'] ?? '',
                'harga_paket' => $_POST['harga_paket'] ?? 0,
                'currency' => $_POST['currency'] ?? 'USD'
            ];

            // Send emails with attachments (files are not saved to server)
            $emailSent = sendRegistrationEmail($emailData, $uploadedFiles, 'Haji');

            if (!$emailSent) {
                error_log("Failed to send registration email for NIK: " . $data['nik']);
            }
            
            $conn->commit();
            $success = true;
        }
    } catch (PDOException $e) {
        $conn->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
        error_log("Database error: " . $e->getMessage());
    } catch (Exception $e) {
        $conn->rollBack();
        $errors[] = $e->getMessage();
        error_log("Error: " . $e->getMessage());
    }
}

if ($success) {
    // Get package details for pricing
    $paketId = $_POST['pak_id'];
    $stmt = $conn->prepare("SELECT program_pilihan, tanggal_keberangkatan, 
                           base_price_quad, base_price_triple, base_price_double, currency 
                           FROM data_paket WHERE pak_id = ?");
    $stmt->execute([$paketId]);
    $package = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate total based on room type
    $roomType = $_POST['type_room_pilihan'];
    $paymentTotal = 0;
    switch($roomType) {
        case 'Quad': $paymentTotal = $package['base_price_quad']; break;
        case 'Triple': $paymentTotal = $package['base_price_triple']; break;
        case 'Double': $paymentTotal = $package['base_price_double']; break;
    }

    // Prepare invoice parameters
    $invoiceParams = [
        'nama' => $_POST['nama'],
        'no_telp' => $_POST['no_telp'],
        'alamat' => $_POST['alamat'],
        'email' => $_POST['email'] ?? '',
        'nik' => $_POST['nik'],
        'program_pilihan' => $package['program_pilihan'],
        'tanggal_keberangkatan' => $package['tanggal_keberangkatan'],
        'type_room_pilihan' => $_POST['type_room_pilihan'],
        'payment_method' => $_POST['payment_method'],
        'payment_type' => $_POST['payment_type'],
        'currency' => $package['currency'],
        'payment_total' => $paymentTotal,
        'request_khusus' => $_POST['request_khusus'] ?? '',
        'pak_id' => $_POST['pak_id']
    ];

    $queryString = http_build_query($invoiceParams);
    header("Location: invoice.php?" . $queryString);
} else {
    $errorMessages = implode("\\n", $errors);
    header("Location: invoice.php?errors=" . urlencode($errorMessages));
}
exit();