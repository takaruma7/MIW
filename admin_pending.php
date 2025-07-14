<?php
require_once 'config.php';
require_once 'email_functions.php';

// Ensure error logs directory exists
if (!file_exists(__DIR__ . '/error_logs')) {
    mkdir(__DIR__ . '/error_logs', 0755, true);
}

// Process payment verification/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->beginTransaction();

        if (!isset($_POST['nik'])) {
            throw new Exception('NIK is required');
        }

        $nik = $_POST['nik'];
        $currentDateTime = new DateTime();

        // Get jamaah and package details
        $stmt = $conn->prepare("
            SELECT j.*, p.currency, p.program_pilihan,
                   CASE j.type_room_pilihan
                       WHEN 'Quad' THEN p.base_price_quad
                       WHEN 'Triple' THEN p.base_price_triple
                       WHEN 'Double' THEN p.base_price_double
                   END as package_price
            FROM data_jamaah j
            JOIN data_paket p ON j.pak_id = p.pak_id
            WHERE j.nik = ?
        ");
        $stmt->execute([$nik]);
        $jamaah = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$jamaah) {
            throw new Exception('Jamaah not found');
        }

        if (isset($_POST['verify_payment'])) {
            if (!isset($_POST['paid_amount']) || empty($_POST['paid_amount'])) {
                throw new Exception('Payment amount is required');
            }

            $paymentTotal = floatval($_POST['paid_amount']);
            $packagePrice = floatval($jamaah['package_price']);
            $paymentRemaining = $packagePrice - $paymentTotal;

            // Generate invoice_id (YYYY + 4-digit sequence)
            $year = date('Y');
            $stmt = $conn->prepare("
                SELECT COALESCE(MAX(SUBSTRING(invoice_id, 5)), 0) as last_sequence 
                FROM data_invoice 
                WHERE invoice_id LIKE ?
            ");
            $stmt->execute([$year . '%']);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $sequence = str_pad(intval($result['last_sequence']) + 1, 4, '0', STR_PAD_LEFT);
            $invoiceId = $year . $sequence;

            // Insert into data_invoice
            $stmt = $conn->prepare("
                INSERT INTO data_invoice (
                    invoice_id, pak_id, nik, nama, alamat, no_telp,
                    keterangan, payment_type, program_pilihan, type_room_pilihan,
                    harga_paket, payment_amount, total_uang_masuk, sisa_pembayaran
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $invoiceId,
                $jamaah['pak_id'],
                $nik,
                $jamaah['nama'],
                $jamaah['alamat'],
                $jamaah['no_telp'],
                'Pembayaran ' . ($paymentRemaining > 0 ? 'DP' : 'LUNAS'),
                $paymentRemaining > 0 ? 'DP' : 'LUNAS',
                $jamaah['program_pilihan'],
                $jamaah['type_room_pilihan'],
                $packagePrice,
                $paymentTotal,
                $paymentTotal,
                $paymentRemaining
            ]);

            // Update jamaah record
            $stmt = $conn->prepare("
                UPDATE data_jamaah 
                SET payment_status = 'verified',
                    payment_total = ?,
                    payment_remaining = ?,
                    payment_verified_at = ?,
                    payment_rejected_at = NULL,
                    payment_verified_by = 'Admin'
                WHERE nik = ?
            ");
            $stmt->execute([
                $paymentTotal,
                $paymentRemaining,
                $currentDateTime->format('Y-m-d H:i:s'),
                $nik
            ]);

            // Generate kwitansi
            $kwitansiData = [
                'invoice_id' => $invoiceId,
                'nama' => $jamaah['nama'],
                'nik' => $jamaah['nik'],
                'alamat' => $jamaah['alamat'],
                'no_telp' => $jamaah['no_telp'],
                'program_pilihan' => $jamaah['program_pilihan'],
                'type_room_pilihan' => $jamaah['type_room_pilihan'],
                'package_price' => $packagePrice,
                'payment_type' => $paymentRemaining > 0 ? 'DP' : 'LUNAS',
                'payment_method' => $jamaah['payment_method'],
                'payment_total' => $paymentTotal,
                'currency' => $jamaah['currency'],
                'keterangan' => 'Pembayaran ' . ($paymentRemaining > 0 ? 'DP' : 'LUNAS')
            ];

            // Generate PDF kwitansi
            ob_start();
            $temp_file = tempnam(sys_get_temp_dir(), 'kwitansi_');
            require 'kwitansi_template.php';
            $html = ob_get_clean();

            require_once 'vendor/tecnickcom/tcpdf/tcpdf.php';
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('MIW Travel');
            $pdf->SetTitle('Kwitansi Pembayaran - ' . $jamaah['nama']);
            $pdf->SetMargins(10, 10, 10);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($temp_file, 'F');

            // Send verification email with kwitansi attachment
            $emailData = [
                'nama' => $jamaah['nama'],
                'nik' => $jamaah['nik'],
                'program_pilihan' => $jamaah['program_pilihan'],
                'payment_total' => $paymentTotal,
                'payment_remaining' => $paymentRemaining,
                'currency' => $jamaah['currency'],
                'payment_status' => 'verified',
                'payment_date' => $currentDateTime->format('Y-m-d'),
                'payment_time' => $currentDateTime->format('H:i:s'),
                'email' => $jamaah['email']
            ];

            $attachments = [
                'kwitansi' => [
                    'tmp_name' => $temp_file,
                    'name' => 'Kwitansi_' . $invoiceId . '.pdf',
                    'error' => UPLOAD_ERR_OK
                ]
            ];

            if (!sendPaymentVerificationEmail($emailData, $attachments)) {
                unlink($temp_file);
                throw new Exception('Failed to send verification email');
            }
            
            unlink($temp_file); // Clean up temporary file

        } elseif (isset($_POST['reject_payment'])) {
            // Update jamaah record for rejection
            $stmt = $conn->prepare("
                UPDATE data_jamaah 
                SET payment_status = 'rejected',
                    payment_total = NULL,
                    payment_remaining = NULL,
                    payment_verified_at = NULL,
                    payment_rejected_at = ?,
                    payment_verified_by = 'Admin'
                WHERE nik = ?
            ");
            $stmt->execute([
                $currentDateTime->format('Y-m-d H:i:s'),
                $nik
            ]);

            // Send rejection email
            $emailData = [
                'nama' => $jamaah['nama'],
                'nik' => $jamaah['nik'],
                'program_pilihan' => $jamaah['program_pilihan'],
                'payment_status' => 'rejected',
                'payment_date' => $jamaah['payment_date'],
                'payment_time' => $jamaah['payment_time']
            ];

            if (!sendPaymentRejectionEmail($emailData)) {
                throw new Exception('Failed to send rejection email');
            }
        }

        $conn->commit();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;

    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Payment processing error: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Fetch pending registrations
try {
    $stmt = $conn->prepare("
        SELECT 
            j.*,
            p.currency, 
            p.program_pilihan,
            CASE j.type_room_pilihan
                WHEN 'Quad' THEN p.base_price_quad
                WHEN 'Triple' THEN p.base_price_triple
                WHEN 'Double' THEN p.base_price_double
            END as package_price,
            DATE(j.created_at) as tanggal_daftar
        FROM data_jamaah j
        JOIN data_paket p ON j.pak_id = p.pak_id
        WHERE j.payment_status = 'pending'
        ORDER BY j.created_at DESC
    ");
    $stmt->execute();
    $pendingRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching pending registrations: " . $e->getMessage());
    $pendingRegistrations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Registrations - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="admin_styles.css" rel="stylesheet">
    <style>
        .modal-xl {
            max-width: 95% !important;
        }
        .table-container {
            margin-bottom: 40px;
        }
        .table-title {
            background-color: #f6b127;
            color: #000;
            padding: 10px;
            margin-top: 20px;
            border-radius: 5px 5px 0 0;
        }
        .action-btns {
            white-space: nowrap;
        }
        .nav-tabs .nav-link.active {
            background-color: #f6b127;
            color: #000;
            font-weight: bold;
        }
        .document-link {
            margin-right: 5px;
        }
        .time-remaining {
            font-weight: bold;
        }
        .time-remaining.critical {
            color: #dc3545;
        }
        .sortable:hover {
            cursor: pointer;
            text-decoration: underline;
        }
        .btn-verify {
            margin-right: 5px;
        }
        .modal-document-link {
            display: block;
            margin-bottom: 10px;
        }
        .modal-footer .actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 bg-dark text-white p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>MIW Travel Admin Dashboard</h2>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <ul class="nav nav-tabs">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_pending.php">Pending Registrations</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_crud.php">Full Database CRUD</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_manifest.php">Manifests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_paket.php">Paket Management</a>
                    </li>
                </ul>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['message'] ?></div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>

                <div class="table-container">
                    <div class="table-title">Pending Registrations & Payments</div>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" id="pendingTable">
                            <thead class="table-dark">
                                <tr>
                                    <th class="sortable" data-sort="created_at">Tanggal Daftar</th>
                                    <th class="sortable" data-sort="nama">Nama</th>
                                    <th class="sortable" data-sort="nik">NIK</th>
                                    <th class="sortable" data-sort="email">Email</th>
                                    <th class="sortable" data-sort="no_telp">No. Telp</th>
                                    <th class="sortable" data-sort="data_paket">Paket</th>
                                    <th class="sortable" data-sort="biaya_paket">Biaya</th>
                                    <th class="sortable" data-sort="payment_method">Bank Transfer</th>
                                    <th class="sortable" data-sort="transfer_account_name">a.n. Transfer</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($pendingRegistrations as $row): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><?= htmlspecialchars($row['nama']) ?></td>
                                <td><?= htmlspecialchars($row['nik']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['no_telp']) ?></td>
                                <td><?= htmlspecialchars($row['program_pilihan']) ?></td>
                                <td><?= $row['currency'] ?> <?= number_format($row['package_price'], 2) ?></td>
                                <td><?= htmlspecialchars($row['payment_method'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($row['transfer_account_name'] ?? 'N/A') ?></td>
                                <td class="action-btns">
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#registrationModal<?= $row['nik'] ?>">
                                        <i class="bi bi-info-circle"></i> Display Info
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for each registration -->
    <!-- In admin_pending.php, replace the modal section with: -->
<?php foreach ($pendingRegistrations as $row): ?>
    <div class="modal fade" id="registrationModal<?= $row['nik'] ?>" tabindex="-1" aria-labelledby="registrationModalLabel<?= $row['nik'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registrationModalLabel<?= $row['nik'] ?>">
                        Registration Details - <?= htmlspecialchars($row['nama']) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h5>Personal Information</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $personalFields = [
                                    'nik' => 'NIK',
                                    'nama' => 'Nama',
                                    'tempat_lahir' => 'Tempat Lahir',
                                    'tanggal_lahir' => 'Tanggal Lahir',
                                    'jenis_kelamin' => 'Jenis Kelamin',
                                    'umur' => 'Umur',
                                    'email' => 'Email',
                                    'no_telp' => 'No. Telp',
                                    'alamat' => 'Alamat',
                                    'kode_pos' => 'Kode Pos',
                                    'desa_kelurahan' => 'Desa/Kelurahan',
                                    'kecamatan' => 'Kecamatan',
                                    'kabupaten_kota' => 'Kabupaten/Kota',
                                    'provinsi' => 'Provinsi',
                                    'kewarganegaraan' => 'Kewarganegaraan',
                                    'pendidikan' => 'Pendidikan',
                                    'pekerjaan' => 'Pekerjaan',
                                    'golongan_darah' => 'Golongan Darah',
                                    'status_perkawinan' => 'Status Perkawinan',
                                    'tinggi_badan' => 'Tinggi Badan (cm)',
                                    'berat_badan' => 'Berat Badan (kg)'
                                ];
                                foreach ($personalFields as $field => $label):
                                    $value = $row[$field] ?? null;
                                    if ($field === 'tanggal_lahir' && $value) {
                                        $value = date('d/m/Y', strtotime($value));
                                    }
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $value !== null ? htmlspecialchars($value) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <h5 class="mt-4">Package & Payment</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $packageFields = [
                                    'program_pilihan' => 'Program',
                                    'type_room_pilihan' => 'Tipe Kamar',
                                    'request_khusus' => 'Request Khusus',
                                    'payment_method' => 'Metode Pembayaran',
                                    'payment_type' => 'Jenis Pembayaran',
                                    'payment_date' => 'Tanggal Pembayaran',
                                    'payment_time' => 'Waktu Pembayaran',
                                    'transfer_account_name' => 'Atas Nama Transfer',
                                    'payment_status' => 'Status Pembayaran',
                                    'payment_total' => 'Total Pembayaran',
                                    'payment_remaining' => 'Sisa Pembayaran'
                                ];
                                foreach ($packageFields as $field => $label):
                                    $value = $row[$field] ?? null;
                                    if ($field === 'payment_date' && $value) {
                                        $value = date('d/m/Y', strtotime($value));
                                    }
                                    if (in_array($field, ['payment_total', 'payment_remaining']) && $value !== null) {
                                        $value = $row['currency'] . ' ' . number_format($value, 2);
                                    }
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $value !== null ? htmlspecialchars($value) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <th width="40%">Total Package Price</th>
                                    <td><?= $row['currency'] ?> <?= number_format($row['package_price'], 2) ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <h5>Family Information</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $familyFields = [
                                    'nama_ayah' => 'Nama Ayah',
                                    'nama_ibu' => 'Nama Ibu',
                                    'emergency_nama' => 'Nama Kontak Darurat',
                                    'emergency_hp' => 'No. HP Kontak Darurat',
                                    'nama_mahram' => 'Nama Mahram',
                                    'hubungan_mahram' => 'Hubungan Mahram',
                                    'nomor_mahram' => 'Nomor Mahram'
                                ];
                                foreach ($familyFields as $field => $label):
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $row[$field] !== null ? htmlspecialchars($row[$field]) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        <div class="col-md-3">
                            <h5>Physical & Documents</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $physicalFields = [
                                    'ciri_rambut' => 'Ciri Rambut',
                                    'ciri_alis' => 'Ciri Alis',
                                    'ciri_hidung' => 'Ciri Hidung',
                                    'ciri_muka' => 'Ciri Muka'
                                ];
                                foreach ($physicalFields as $field => $label):
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $row[$field] !== null ? htmlspecialchars($row[$field]) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <h5 class="mt-4">Vaccination Details</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $documentFields = [
                                    'nama_paspor' => 'Nama Paspor',
                                    'no_paspor' => 'Nomor Paspor',
                                    'tempat_pembuatan_paspor' => 'Tempat Pembuatan Paspor',
                                    'tanggal_pengeluaran_paspor' => 'Tanggal Pengeluaran Paspor',
                                    'tanggal_habis_berlaku' => 'Tanggal Habis Berlaku',
                                    'nama_sertifikat_covid' => 'Nama Sertifikat COVID',
                                    'jenis_vaksin_1' => 'Vaksin 1',
                                    'jenis_vaksin_2' => 'Vaksin 2',
                                    'jenis_vaksin_3' => 'Vaksin 3',
                                    'tanggal_vaksin_1' => 'Tanggal Vaksin 1',
                                    'tanggal_vaksin_2' => 'Tanggal Vaksin 2',
                                    'tanggal_vaksin_3' => 'Tanggal Vaksin 3',
                                    'pengalaman_haji' => 'Pengalaman Haji'
                                ];
                                foreach ($documentFields as $field => $label):
                                    $value = $row[$field] ?? null;
                                    if (strpos($field, 'tanggal_') === 0 && $value) {
                                        $value = date('d/m/Y', strtotime($value));
                                    }
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $value !== null ? htmlspecialchars($value) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <h5 class="mt-4">Documents & Status</h5>
                            <table class="table table-bordered table-sm">
                                <?php
                                $uploadFields = [
                                    'bk_kuning' => 'Buku Kuning',
                                    'foto' => 'Foto',
                                    'fc_ktp_uploaded_at' => 'Fotokopi KTP',
                                    'fc_ijazah_uploaded_at' => 'Fotokopi Ijazah',
                                    'fc_kk_uploaded_at' => 'Fotokopi KK',
                                    'fc_bk_nikah_uploaded_at' => 'Fotokopi Buku Nikah',
                                    'fc_akta_lahir_uploaded_at' => 'Fotokopi Akta Lahir'
                                ];
                                foreach ($uploadFields as $field => $label):
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $row[$field] !== null ? htmlspecialchars($row[$field]) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <h5 class="mt-4">Marketing Information</h5>
                            <table class="table table-bordered">
                                <?php
                                $marketingFields = [
                                    'marketing_nama' => 'Nama Marketing',
                                    'marketing_hp' => 'HP Marketing',
                                    'marketing_type' => 'Tipe Marketing'
                                ];
                                foreach ($marketingFields as $field => $label):
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $row[$field] !== null ? htmlspecialchars($row[$field]) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>

                            <h5 class="mt-4">Document Upload Status</h5>
                            <table class="table table-bordered">
                                <?php
                                $uploadFields = [
                                    'bk_kuning' => 'Buku Kuning',
                                    'foto' => 'Foto',
                                    'fc_ktp_uploaded_at' => 'Fotokopi KTP',
                                    'fc_ijazah_uploaded_at' => 'Fotokopi Ijazah',
                                    'fc_kk_uploaded_at' => 'Fotokopi KK',
                                    'fc_bk_nikah_uploaded_at' => 'Fotokopi Buku Nikah',
                                    'fc_akta_lahir_uploaded_at' => 'Fotokopi Akta Lahir'
                                ];
                                foreach ($uploadFields as $field => $label):
                                ?>
                                    <tr>
                                        <th width="40%"><?= $label ?></th>
                                        <td><?= $row[$field] !== null ? htmlspecialchars($row[$field]) : '-' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <form method="post" class="mt-4">
                        <input type="hidden" name="nik" value="<?= $row['nik'] ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="payment_amount<?= $row['nik'] ?>" class="form-label">
                                        Jumlah Pembayaran (<?= $row['currency'] ?>)
                                    </label>
                                    <input type="number" class="form-control" 
                                           id="payment_amount<?= $row['nik'] ?>" 
                                           name="paid_amount" 
                                           step="0.01"
                                           min="0">
                                    <div class="invalid-feedback">
                                        Jumlah pembayaran tidak valid
                                    </div>
                                </div>
                            </div>                        <div class="col-md-6">
                            <div class="d-flex justify-content-end">
                                <button type="submit" name="verify_payment" class="btn btn-success me-2">
                                    <i class="bi bi-check-circle"></i> Verify Payment
                                </button>
                                <button type="submit" name="reject_payment" class="btn btn-danger me-2">
                                    <i class="bi bi-x-circle"></i> Reject Payment
                                </button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    Close
                                </button>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
    $(document).ready(function() {
        // Initialize DataTable
        $('#pendingTable').DataTable({
            "order": [[0, "desc"]], // Default sort by registration date descending
            "language": {
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data per halaman",
                "zeroRecords": "Tidak ada data yang ditemukan",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });

        // Payment validation
        $('.modal').on('show.bs.modal', function() {
            const modal = $(this);
            const paymentInput = modal.find('input[name="paid_amount"]');
            const verifyBtn = modal.find('button[name="verify_payment"]');
            
            try {
                const priceCell = modal.find('tr:contains("Total Package Price") td').text().trim();
                const currency = priceCell.split(' ')[0];
                const packagePrice = parseFloat(priceCell.split(' ')[1].replace(/,/g, ''));
                
                // Set minimum payment based on currency
                const minPayment = currency === 'USD' ? 5000 : 5000000;
                paymentInput.attr('min', minPayment);
                
                // Validate payment amount on input
                paymentInput.on('input', function() {
                    const value = parseFloat($(this).val()) || 0;
                    if (value < minPayment || value > packagePrice) {
                        $(this).addClass('is-invalid');
                        verifyBtn.prop('disabled', true);
                    } else {
                        $(this).removeClass('is-invalid');
                        verifyBtn.prop('disabled', false);
                    }
                });
            } catch (e) {
                console.error('Error initializing payment validation:', e);
            }
        });
    });
    </script>
</body>
</html>