<?php
require_once 'config.php';

// Get all packages
$stmt = $conn->prepare("SELECT * FROM data_paket ORDER BY tanggal_keberangkatan DESC");
$stmt->execute();
$packages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manifest Export - MIW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 admin-header d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-file-earmark-excel"></i> Manifest Export</h2>
            </div>
        </div>

        <?php include 'admin_nav.php'; ?>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Export Package Data</h5>
                        <p class="text-muted small">Export manifest data based on roomlist assignment</p>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="packageTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Jenis Paket</th>
                                        <th>Program</th>
                                        <th>Tanggal Keberangkatan</th>
                                        <th>Hotel Medinah</th>
                                        <th>Hotel Makkah</th>
                                        <th>Jumlah Jamaah</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($packages as $package): 
                                        // Count jamaah for this package
                                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM data_jamaah WHERE pak_id = ?");
                                        $stmt->execute([$package['pak_id']]);
                                        $jamaahCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($package['pak_id']) ?></td>
                                        <td><?= htmlspecialchars($package['jenis_paket']) ?></td>
                                        <td><?= htmlspecialchars($package['program_pilihan']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($package['tanggal_keberangkatan'])) ?></td>
                                        <td><?= htmlspecialchars($package['hotel_medinah']) ?></td>
                                        <td><?= htmlspecialchars($package['hotel_makkah']) ?></td>
                                        <td><?= $jamaahCount ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="admin_roomlist.php" class="btn btn-sm btn-secondary me-2" 
                                                        data-bs-toggle="tooltip"
                                                        title="Manage Roomlist">
                                                    <i class="bi bi-building"></i> Roomlist
                                                </a>
                                                <button class="btn btn-sm btn-primary export-manifest" 
                                                        data-pakid="<?= $package['pak_id'] ?>"
                                                        data-bs-toggle="tooltip"
                                                        title="Export Manifest">
                                                    <i class="bi bi-file-earmark-excel"></i> Export
                                                </button>
                                            </div>
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

        <!-- Export Status Modal -->
        <div class="modal fade" id="exportStatusModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Export Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div id="exportStatus" class="alert alert-info">
                            <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                            Processing export...
                        </div>
                        <div id="downloadLink" class="d-none text-center">
                            <p>Export completed successfully!</p>
                            <a href="#" class="btn btn-success" id="downloadBtn">
                                <i class="bi bi-download"></i> Download File
                            </a>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="manifest_scripts.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

            // Initialize DataTable
            $('#packageTable').DataTable({
                responsive: true,
                order: [[3, 'asc']], // Sort by departure date
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

            // Handle manifest export
            $(document).on('click', '.export-manifest', function() {
                const pakId = $(this).data('pakid');
                if (!pakId) {
                    alert('No package ID found for export');
                    return;
                }
                
                // Show export status modal
                const exportModal = new bootstrap.Modal(document.getElementById('exportStatusModal'));
                exportModal.show();
                
                // Reset modal content
                $('#exportStatus').removeClass('alert-danger').addClass('alert-info').html(`
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Processing export...
                `);
                $('#downloadLink').addClass('d-none');
                
                // Call the export function from manifest_scripts.js with modal feedback
                $.ajax({
                    url: 'export_manifest.php',
                    type: 'POST',
                    data: { pak_id: pakId, export_type: 'manifest' },
                    success: function(response) {
                        console.log('=== ADMIN MANIFEST EXPORT DEBUG ===');
                        console.log('Raw response:', response);
                        console.log('Response type:', typeof response);
                        console.log('Response success:', response.success);
                        console.log('Response data:', response.data);
                        
                        if (!response.success) {
                            console.error('Export failed:', response.message);
                            $('#exportStatus').removeClass('alert-info').addClass('alert-danger').html(
                                `<i class="bi bi-exclamation-triangle"></i> ${response.message || 'Error during export'}`
                            );
                            return;
                        }
                        
                        if (!response.data || !response.data.manifest || response.data.manifest.length === 0) {
                            console.error('No manifest data found');
                            console.log('Response data structure:', response.data);
                            $('#exportStatus').removeClass('alert-info').addClass('alert-danger').html(
                                `<i class="bi bi-exclamation-triangle"></i> No data found for export`
                            );
                            return;
                        }
                        
                        console.log('Manifest data found:', response.data.manifest.length, 'records');
                        console.log('Calling exportToExcel function...');
                        
                        // Use the simplified export function
                        if (window.exportToExcel) {
                            window.exportToExcel(pakId, 'manifest', response.data);
                            
                            // Update modal to show success
                            $('#exportStatus').removeClass('alert-info').addClass('alert-success').html(
                                `<i class="bi bi-check-circle"></i> Export completed successfully! (${response.data.manifest.length} records)`
                            );
                            $('#downloadLink').removeClass('d-none');
                        } else {
                            console.error('exportToExcel function not found on window object');
                            $('#exportStatus').removeClass('alert-info').addClass('alert-danger').html(
                                `<i class="bi bi-exclamation-triangle"></i> Export function not available`
                            );
                        }
                        
                        console.log('=== ADMIN MANIFEST EXPORT DEBUG END ===');
                    },
                    error: function(xhr, status, error) {
                        console.error('Export error:', error);
                        let message = 'Error generating Excel file';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            message = response.message || message;
                        } catch (e) {}
                        
                        $('#exportStatus').removeClass('alert-info').addClass('alert-danger').html(
                            `<i class="bi bi-exclamation-triangle"></i> ${message}`
                        );
                    }
                });
            });
        });
    </script>
</body>
</html>