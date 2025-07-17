<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="row mt-3">
    <div class="col-12">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'admin_pending.php' ? 'active' : '' ?>" href="admin_pending.php">
                    Pending Registrations
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'admin_paket.php' ? 'active' : '' ?>" href="admin_paket.php">
                    Package Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'admin_pembatalan.php' ? 'active' : '' ?>" href="admin_pembatalan.php">
                    Cancellation Request
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $current_page === 'admin_manifest.php' ? 'active' : '' ?>" href="admin_manifest.php">
                    Manifest Control Panel
                </a>
            </li>
        </ul>
    </div>
</div>
