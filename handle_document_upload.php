<?php
require_once 'config.php';
require_once 'email_functions.php';

header('Content-Type: application/json');

try {
    if (!isset($_POST['nik'])) {
        throw new Exception('NIK is required');
    }

    // Get jamaah data
    $stmt = $conn->prepare("SELECT * FROM data_jamaah WHERE nik = ?");
    $stmt->execute([$_POST['nik']]);
    $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$jamaah) {
        throw new Exception('Jamaah not found');
    }

    // Check if all required files are present
    $requiredFiles = [
        'bk_kuning',
        'foto',
        'fc_ktp',
        'fc_ijazah',
        'fc_kk',
        'fc_bk_nikah',
        'fc_akta_lahir'
    ];

    $files = [];
    foreach ($requiredFiles as $file) {
        if (!isset($_FILES[$file]) || $_FILES[$file]['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('All documents must be uploaded');
        }
        $files[$file] = $_FILES[$file];
    }

    // Send email with attachments
    if (!sendDocumentUploadEmail($jamaah, $files)) {
        throw new Exception('Failed to send email');
    }

    // Update document timestamps
    $currentTime = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("
        UPDATE data_jamaah SET
            bk_kuning = ?,
            foto = ?,
            fc_ktp_uploaded_at = ?,
            fc_ijazah_uploaded_at = ?,
            fc_kk_uploaded_at = ?,
            fc_bk_nikah_uploaded_at = ?,
            fc_akta_lahir_uploaded_at = ?
        WHERE nik = ?
    ");

    $stmt->execute([
        $currentTime,
        $currentTime,
        $currentTime,
        $currentTime,
        $currentTime,
        $currentTime,
        $currentTime,
        $_POST['nik']
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Documents uploaded successfully',
        'timestamps' => [
            'bk_kuning' => $currentTime,
            'foto' => $currentTime,
            'fc_ktp_uploaded_at' => $currentTime,
            'fc_ijazah_uploaded_at' => $currentTime,
            'fc_kk_uploaded_at' => $currentTime,
            'fc_bk_nikah_uploaded_at' => $currentTime,
            'fc_akta_lahir_uploaded_at' => $currentTime
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
