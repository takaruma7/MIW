<?php
require_once 'config.php';

// Get all jamaah records
$stmt = $conn->prepare("SELECT * FROM data_jamaah");
$stmt->execute();
$jamaahs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - MIW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="admin_styles.css">
    <style>
        .document-status {
            text-align: center;
            font-size: 1.2em;
        }
        .document-preview {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.5rem;
            background-color: #f8f9fa;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .file-actions {
            display: flex;
            gap: 0.25rem;
        }
        .upload-progress {
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 admin-header d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-file-earmark-text"></i> Document Management</h2>
            </div>
        </div>

        <?php include 'admin_nav.php'; ?>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <table class="table table-striped" id="documentTable">
                            <thead>
                                <tr>
                                    <th>NIK</th>
                                    <th>Nama</th>
                                    <th>Buku Kuning</th>
                                    <th>Foto</th>
                                    <th>Fotocopy KTP</th>
                                    <th>Fotocopy Ijazah</th>
                                    <th>Fotocopy KK</th>
                                    <th>Fotocopy Buku Nikah</th>
                                    <th>Fotocopy Akta Kelahiran</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jamaahs as $jamaah): ?>
                                <tr>
                                    <td><?= htmlspecialchars($jamaah['nik']) ?></td>
                                    <td><?= htmlspecialchars($jamaah['nama']) ?></td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['bk_kuning_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['foto_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['fc_ktp_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['fc_ijazah_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['fc_kk_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['fc_bk_nikah_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="document-status">
                                            <?php if ($jamaah['fc_akta_lahir_path']): ?>
                                                <span class="text-success">✓</span>
                                            <?php else: ?>
                                                <span class="text-danger">✕</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="openDocumentModal('<?= $jamaah['nik'] ?>', '<?= htmlspecialchars($jamaah['nama']) ?>')">
                                            Manage Documents
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

    <!-- Document Management Modal -->
    <?php include 'includes/file_preview_modal.php'; ?>
    <?php include 'document_modal.php'; // We'll create this file next ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/file_handlers.js"></script>
    <script src="js/document-management/document-management.js"></script>
    <script>
        $(document).ready(function() {
            $('#documentTable').DataTable({
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                    infoEmpty: "Tidak ada data yang ditampilkan",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Selanjutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        });

        // Global function to open document modal
        function openDocumentModal(nik, name) {
            if (typeof DocumentManager !== 'undefined' && DocumentManager.openDocumentModal) {
                DocumentManager.openDocumentModal(nik, name);
            } else {
                console.error('DocumentManager not available');
                alert('Document management system is not available. Please refresh the page.');
            }
        }
    </script>
</body>
</html>
