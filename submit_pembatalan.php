<?php
require_once 'config.php';
require_once 'email_functions.php';


function insertCancellationData($inputData) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO data_pembatalan 
            (nik, nama, no_telp, email, alasan, kwitansi_uploaded_at, proof_uploaded_at) 
            VALUES 
            (:nik, :nama, :no_telp, :email, :alasan, NOW(), NOW())
        ");

        return $stmt->execute([
            ':nik' => $inputData['nik'],
            ':nama' => $inputData['nama'],
            ':no_telp' => $inputData['no_telp'],
            ':email' => $inputData['email'],
            ':alasan' => $inputData['alasan']
        ]);
        
    } catch(PDOException $e) {
        error_log("Database error in insertCancellationData: " . $e->getMessage());
        return false;
    }
}

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'errors' => [],
    'input' => []
];

// Collect form data
$inputData = [
    'nik' => trim($_POST['nik'] ?? ''),
    'nama' => trim($_POST['nama'] ?? ''),
    'no_telp' => trim($_POST['no_telp'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'alasan' => trim($_POST['alasan'] ?? '')
];

// Validate required fields
if (empty($inputData['nik'])) {
    $response['errors'][] = 'NIK harus diisi';
}
if (empty($inputData['nama'])) {
    $response['errors'][] = 'Nama lengkap harus diisi';
}
if (empty($inputData['no_telp'])) {
    $response['errors'][] = 'Nomor telepon harus diisi';
}
if (empty($inputData['email'])) {
    $response['errors'][] = 'Email harus diisi';
} elseif (!filter_var($inputData['email'], FILTER_VALIDATE_EMAIL)) {
    $response['errors'][] = 'Format email tidak valid';
}

// Validate file uploads
$files = [];
if (empty($_FILES['kwitansi_uploaded_at']['name'])) {
    $response['errors'][] = 'Kwitansi pembayaran harus diupload';
} else {
    $files[] = $_FILES['kwitansi_uploaded_at'];
}

if (empty($_FILES['proof_uploaded_at']['name'])) {
    $response['errors'][] = 'Bukti pembayaran harus diupload';
} else {
    $files[] = $_FILES['proof_uploaded_at'];
}

// Check file types and sizes
foreach ($files as $file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        $response['errors'][] = "File {$file['name']} harus berupa JPG, PNG, atau PDF";
    }

    if ($file['size'] > 2 * 1024 * 1024) {
        $response['errors'][] = "Ukuran file {$file['name']} tidak boleh melebihi 2MB";
    }
}

// If there are errors, redirect back
if (!empty($response['errors'])) {
    $response['input'] = $inputData;
    $encodedErrors = urlencode(implode("\n", $response['errors']));
    $encodedInput = urlencode(json_encode($inputData));
    header("Location: form_pembatalan.php?errors={$encodedErrors}&input={$encodedInput}");
    exit;
}

try {
    // Insert to database
    $dbSuccess = insertCancellationData($inputData);
    
    if (!$dbSuccess) {
        throw new Exception('Gagal menyimpan data pembatalan');
    }

    // Send email notification
    $emailSuccess = sendCancellationEmail($inputData, $files);
    
    if (!$emailSuccess) {
        error_log("Email failed but data was saved");
    }

    // Redirect to success page
    $_SESSION['cancellation_success'] = [
        'status' => true,
        'timestamp' => time()
    ];
    header('Location: closing_page_pembatalan.php');
    exit;

} catch (Exception $e) {
    error_log("Processing error: " . $e->getMessage());
    $response['errors'][] = 'Terjadi kesalahan sistem. Silakan coba lagi.';
    $encodedErrors = urlencode(implode("\n", $response['errors']));
    $encodedInput = urlencode(json_encode($inputData));
    header("Location: form_pembatalan.php?errors={$encodedErrors}&input={$encodedInput}");
    exit;
}