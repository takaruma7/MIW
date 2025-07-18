<?php
require_once 'config.php'; // Database connection

// Get all packages
$packages = $conn->query("SELECT * FROM data_paket ORDER BY jenis_paket, program_pilihan")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Manifest Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 admin-header">
                <h2>MIW Travel Admin Dashboard</h2>
            </div>
        </div>

        <?php include 'admin_nav.php'; ?>

        <div class="row mt-3">
            <div class="col-12">
                <ul class="nav nav-tabs" id="manifestTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="manifest-tab" data-bs-toggle="tab" data-bs-target="#manifest" type="button" role="tab">Manifest</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="kelengkapan-tab" data-bs-toggle="tab" data-bs-target="#kelengkapan" type="button" role="tab">Kelengkapan</button>
                    </li>
                </ul>
                
                <div class="tab-content" id="manifestTabsContent">
                    <div class="tab-pane fade show active" id="manifest" role="tabpanel">
                        <?php include 'tab_manifest.php'; ?>
                    </div>
                    <div class="tab-pane fade active" id="kelengkapan" role="tabpanel">
                        <?php include 'tab_kelengkapan.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery must be loaded first -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap Bundle includes Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- DataTables with Bootstrap 5 -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- XLSX for Excel export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- Custom scripts for manifest functionality -->
    <script src="manifest_scripts.js"></script>
</body>
</html>