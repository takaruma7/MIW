<?php
// email_functions.php

require_once 'config.php';
require_once 'vendor/autoload.php'; // For PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Build HTML email template with styling
 */
function buildEmailTemplate($title, $content) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #f6b127; color: #000; padding: 15px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background-color: #f6b127; color: #000; text-align: left; padding: 10px; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        .footer { margin-top: 20px; font-size: 0.9em; color: #666; text-align: center; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>$title</h2>
            <p>MIW Travel</p>
        </div>
        <div class='content'>
            $content
        </div>
        <div class='footer'>
            <p>Email ini dikirim secara otomatis. Mohon tidak membalas email ini.</p>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * Build registration details table for admin email
 */
function buildRegistrationDetails($registrationData, $registrationType) {
    $currencySymbol = ($registrationData['currency'] ?? 'IDR') === 'USD' ? '$' : 'Rp';
    $amount = isset($registrationData['harga_paket']) ? number_format($registrationData['harga_paket'], 0, ',', '.') : '0';
    
    $content = "<p>Berikut detail pendaftaran baru yang perlu diproses:</p>";
    
    $content .= "<h3>Informasi Jamaah</h3>
    <table>
        <tr><th width='30%'>Nama Lengkap</th><td>" . htmlspecialchars($registrationData['nama']) . "</td></tr>
        <tr><th>NIK</th><td>" . htmlspecialchars($registrationData['nik']) . "</td></tr>
        <tr><th>Tanggal Lahir</th><td>" . htmlspecialchars($registrationData['tanggal_lahir']) . "</td></tr>
        <tr><th>Jenis Kelamin</th><td>" . htmlspecialchars($registrationData['jenis_kelamin']) . "</td></tr>
        <tr><th>Alamat</th><td>" . htmlspecialchars($registrationData['alamat']) . "</td></tr>
        <tr><th>No. Telepon</th><td>" . htmlspecialchars($registrationData['no_telp']) . "</td></tr>
        <tr><th>Email</th><td>" . htmlspecialchars($registrationData['email']) . "</td></tr>
    </table>";
    
    if ($registrationType === 'Umroh' || $registrationType === 'Haji') {
        $content .= "<h3>Detail Paket</h3>
        <table>
            <tr><th width='30%'>Program</th><td>" . htmlspecialchars($registrationData['program_pilihan'] ?? '') . "</td></tr>
            <tr><th>Tipe Kamar</th><td>" . htmlspecialchars($registrationData['type_room_pilihan'] ?? '') . "</td></tr>
            <tr><th>Biaya</th><td>$currencySymbol $amount</td></tr>
        </table>";
    }
    
    $content .= "<p>Dokumen pendukung terlampir dalam email ini.</p>";
    
    return $content;
}

/**
 * Build confirmation content for registrant
 */
function buildConfirmationContent($registrationData, $registrationType) {
    $content = "<p>Halo " . htmlspecialchars($registrationData['nama']) . ",</p>";
    $content .= "<p>Terima kasih telah mendaftar $registrationType dengan detail berikut:</p>";
    
    $content .= "<table>
        <tr><th width='30%'>NIK</th><td>" . htmlspecialchars($registrationData['nik']) . "</td></tr>
        <tr><th>Nama Lengkap</th><td>" . htmlspecialchars($registrationData['nama']) . "</td></tr>";
    
    if (isset($registrationData['program_pilihan'])) {
        $content .= "<tr><th>Program</th><td>" . htmlspecialchars($registrationData['program_pilihan']) . "</td></tr>";
    }
    
    $content .= "</table>";
    
    $content .= "<p>Tim kami akan segera menghubungi Anda untuk proses selanjutnya.</p>";
    $content .= "<p>Terima kasih atas kepercayaan Anda menggunakan layanan MIW Travel.</p>";
    
    return $content;
}

/**
 * Build payment confirmation details for admin email
 */
function buildPaymentConfirmationDetails($paymentData) {
    $content = "<p>Berikut detail konfirmasi pembayaran yang perlu diverifikasi:</p>";
    
    $content .= "<h3>Informasi Pembayaran</h3>
    <table>
        <tr><th width='30%'>Nama Lengkap</th><td>" . htmlspecialchars($paymentData['nama']) . "</td></tr>
        <tr><th>NIK</th><td>" . htmlspecialchars($paymentData['nik']) . "</td></tr>
        <tr><th>Program</th><td>" . htmlspecialchars($paymentData['program_pilihan']) . "</td></tr>
        <tr><th>Tipe Kamar</th><td>" . htmlspecialchars($paymentData['type_room_pilihan']) . "</td></tr>
        <tr><th>Atas Nama Transfer</th><td>" . htmlspecialchars($paymentData['transfer_account_name']) . "</td></tr>
        <tr><th>Jenis Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_type']) . "</td></tr>
        <tr><th>Metode Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_method']) . "</td></tr>
        <tr><th>Tanggal Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_date']) . "</td></tr>
        <tr><th>Waktu Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_time']) . "</td></tr>
    </table>";
    
    $content .= "<p>Bukti pembayaran terlampir dalam email ini.</p>";
    
    return $content;
}

/**
 * Build payment verification email content
 */
function buildPaymentVerificationContent($paymentData) {
    $currencySymbol = $paymentData['currency'] === 'USD' ? '$' : 'Rp';
    $content = "<p>Halo " . htmlspecialchars($paymentData['nama']) . ",</p>";
    $content .= "<p>Pembayaran Anda untuk program " . htmlspecialchars($paymentData['program_pilihan']) . " telah berhasil diverifikasi.</p>";
    
    $content .= "<h3>Detail Pembayaran</h3>";
    $content .= "<table>
        <tr><th width='30%'>NIK</th><td>" . htmlspecialchars($paymentData['nik']) . "</td></tr>
        <tr><th>Jumlah Pembayaran</th><td>" . $currencySymbol . ' ' . number_format($paymentData['payment_total'], 2) . "</td></tr>";
    
    if ($paymentData['payment_remaining'] > 0) {
        $content .= "<tr><th>Sisa Pembayaran</th><td>" . $currencySymbol . ' ' . number_format($paymentData['payment_remaining'], 2) . "</td></tr>";
    }
    
    $content .= "</table>";
    
    if ($paymentData['payment_remaining'] > 0) {
        $content .= "<p>Mohon segera melunasi sisa pembayaran untuk memastikan slot perjalanan Anda.</p>";
    }
    
    $content .= "<p>Terima kasih atas kepercayaan Anda menggunakan layanan MIW Travel.</p>";
    
    return $content;
}

/**
 * Build payment rejection email content
 */
function buildPaymentRejectionContent($paymentData) {
    $content = "<p>Halo " . htmlspecialchars($paymentData['nama']) . ",</p>";
    $content .= "<p>Mohon maaf, pembayaran Anda untuk program " . htmlspecialchars($paymentData['program_pilihan']) . " belum dapat diverifikasi.</p>";
    
    $content .= "<h3>Detail Pembayaran</h3>";
    $content .= "<table>
        <tr><th width='30%'>NIK</th><td>" . htmlspecialchars($paymentData['nik']) . "</td></tr>
        <tr><th>Tanggal Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_date']) . "</td></tr>
        <tr><th>Waktu Pembayaran</th><td>" . htmlspecialchars($paymentData['payment_time']) . "</td></tr>
    </table>";
    
    $content .= "<p>Silakan hubungi customer service kami untuk informasi lebih lanjut dan panduan pembayaran ulang.</p>";
    $content .= "<p>Terima kasih atas pengertian Anda.</p>";
    
    return $content;
}

/**
 * Send registration email to admin with attachments
 */
function sendRegistrationEmail($registrationData, $files, $registrationType = 'Umroh') {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled in config");
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress(ADMIN_EMAIL);
        
        // Add reply-to if needed
        if (!empty($registrationData['email'])) {
            $mail->addReplyTo($registrationData['email'], $registrationData['nama']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Pendaftaran $registrationType Baru - " . $registrationData['nama'];
        
        // Build email content
        $emailContent = buildRegistrationDetails($registrationData, $registrationType);
        $mail->Body = buildEmailTemplate("Notifikasi Pendaftaran $registrationType", $emailContent);
        $mail->AltBody = strip_tags($emailContent);

        // Attachments
        foreach ($files as $fileType => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment(
                    $file['tmp_name'],
                    $file['name']
                );
            }
        }

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send confirmation email to registrant
 */
function sendConfirmationEmail($registrationData, $registrationType = 'Umroh') {
    if (!EMAIL_ENABLED || empty($registrationData['email'])) {
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($registrationData['email'], $registrationData['nama']);
        $mail->addReplyTo(EMAIL_FROM, EMAIL_FROM_NAME);

        // Content
        $mail->isHTML(true);
        $mail->Subject = EMAIL_SUBJECT;
        
        // Build email content
        $emailContent = buildConfirmationContent($registrationData, $registrationType);
        $mail->Body = buildEmailTemplate("Konfirmasi Pendaftaran $registrationType", $emailContent);
        $mail->AltBody = strip_tags($emailContent);

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Confirmation email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send payment confirmation email to admin with payment proof
 */
function sendPaymentConfirmationEmail($paymentData, $files, $registrationType = 'Umroh') {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled in config");
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress(ADMIN_EMAIL);
        
        // Add reply-to if needed
        if (!empty($paymentData['email'])) {
            $mail->addReplyTo($paymentData['email'], $paymentData['nama']);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = "Konfirmasi Pembayaran $registrationType - " . $paymentData['nama'];
        
        // Build email content
        $emailContent = buildPaymentConfirmationDetails($paymentData);
        $mail->Body = buildEmailTemplate("Konfirmasi Pembayaran $registrationType", $emailContent);
        $mail->AltBody = strip_tags($emailContent);

        // Attachments
        foreach ($files as $fileType => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment(
                    $file['tmp_name'],
                    $file['name']
                );
            }
        }

        // Send email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payment confirmation email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send payment verification email to user
 */
function sendPaymentVerificationEmail($paymentData, $files = []) {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled in config");
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($paymentData['email']);
        $mail->addBCC(ADMIN_EMAIL);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Konfirmasi Verifikasi Pembayaran - MIW Travel';
        
        $content = buildPaymentVerificationContent($paymentData);
        $mail->Body = buildEmailTemplate('Verifikasi Pembayaran', $content);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send payment rejection email to user
 */
function sendPaymentRejectionEmail($paymentData) {
    if (!EMAIL_ENABLED) {
        error_log("Email sending is disabled in config");
        return false;
    }

    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
        $mail->addAddress($paymentData['email']);
        $mail->addBCC(ADMIN_EMAIL);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Pembayaran Tidak Dapat Diverifikasi - MIW Travel';
        
        $content = buildPaymentRejectionContent($paymentData);
        $mail->Body = buildEmailTemplate('Pembayaran Tidak Dapat Diverifikasi', $content);

        return $mail->send();

    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}