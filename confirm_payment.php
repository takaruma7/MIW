<?php
require_once 'config.php';
require_once 'email_functions.php';

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->beginTransaction();

        // Validate required fields
        $requiredFields = ['nik', 'transfer_account_name', 'nama', 'program_pilihan'];
        foreach ($requiredFields as $field) {
            if (!isset($_POST[$field]) || empty($_POST[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate file upload
        if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Payment proof file is required");
        }

        // Get current timestamp for payment records
        $currentDateTime = new DateTime();
        $currentDate = $currentDateTime->format('Y-m-d');
        $currentTime = $currentDateTime->format('H:i:s');

        // Validate file upload
        $file = $_FILES['payment_proof'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error uploading payment proof");
        }

        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            throw new Exception("Invalid file type. Allowed types: JPG, PNG, PDF");
        }

        // Validate file size (2MB limit)
        $maxSize = 2 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds 2MB limit");
        }

        // Update jamaah record with payment details
        $stmt = $conn->prepare("UPDATE data_jamaah SET 
            transfer_account_name = :transfer_account_name,
            payment_time = :payment_time,
            payment_date = :payment_date,
            payment_status = 'pending',
            payment_uploaded_at = :payment_uploaded_at
            WHERE nik = :nik");

        $stmt->execute([
            'transfer_account_name' => $_POST['transfer_account_name'],
            'payment_time' => $currentTime,
            'payment_date' => $currentDate,
            'payment_uploaded_at' => $currentDateTime->format('Y-m-d H:i:s'),
            'nik' => $_POST['nik']
        ]);

        // Prepare email data
        $paymentData = [
            'nama' => $_POST['nama'],
            'nik' => $_POST['nik'],
            'program_pilihan' => $_POST['program_pilihan'],
            'transfer_account_name' => $_POST['transfer_account_name'],
            'payment_time' => $currentTime,
            'payment_date' => $currentDate,
            'type_room_pilihan' => $_POST['type_room_pilihan'] ?? '',
            'payment_type' => $_POST['payment_type'] ?? '',
            'payment_method' => $_POST['payment_method'] ?? ''
        ];

        // Prepare email attachment with payment proof
        $emailFiles = [
            'payment_proof' => $_FILES['payment_proof']
        ];

        // Determine registration type from program_pilihan
        $registrationType = (stripos($_POST['program_pilihan'], 'haji') !== false) ? 'Haji' : 'Umroh';

        // Send email
        $emailSent = sendPaymentConfirmationEmail($paymentData, $emailFiles, $registrationType);

        if (!$emailSent) {
            throw new Exception("Failed to send payment confirmation email");
        }

        // Commit transaction
        $conn->commit();

        // Set success session data for closing_page.php
        $_SESSION['payment_success'] = [
            'status' => true,
            'timestamp' => time(),
            'message' => 'Payment confirmation submitted successfully'
        ];

        // Redirect to closing page
        header('Location: closing_page.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Payment confirmation error: " . $e->getMessage());
        
        // Store error in session and redirect back to invoice
        $_SESSION['payment_error'] = $e->getMessage();
        header('Location: invoice.php');
        exit();
    }
}

// If we get here without POST data, redirect to homepage
header('Location: https://hajikhusus.web.id');
exit();
