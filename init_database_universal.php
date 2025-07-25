<?php
// Universal Database Initialization Script
// Works with both MySQL (Railway/local) and PostgreSQL (Render)

// Detect environment and load appropriate config
if (file_exists('config.render.php') && ($_ENV['APP_ENV'] ?? '') === 'production') {
    require_once 'config.render.php';
    $dbType = 'postgresql';
} else {
    require_once 'config.php';
    $dbType = 'mysql';
}

function detectDatabaseType($pdo) {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    return $driver === 'pgsql' ? 'postgresql' : 'mysql';
}

function initializeDatabase($pdo, $dbType) {
    $success = true;
    $errors = [];
    
    try {
        echo "<h2>🗄️ Initializing {$dbType} Database...</h2>";
        
        if ($dbType === 'postgresql') {
            // PostgreSQL initialization
            $sql = file_get_contents('init_database_postgresql.sql');
        } else {
            // MySQL initialization
            $sql = "
                CREATE TABLE IF NOT EXISTS `paket` (
                    `pak_id` int(11) NOT NULL AUTO_INCREMENT,
                    `nama_paket` varchar(255) NOT NULL,
                    `jenis_paket` enum('Haji','Umroh') NOT NULL,
                    `tanggal_keberangkatan` date DEFAULT NULL,
                    `tanggal_kepulangan` date DEFAULT NULL,
                    `harga_quad` decimal(15,2) DEFAULT NULL,
                    `harga_triple` decimal(15,2) DEFAULT NULL,
                    `harga_double` decimal(15,2) DEFAULT NULL,
                    `deskripsi` text DEFAULT NULL,
                    `hotel_makkah` varchar(255) DEFAULT NULL,
                    `hotel_madinah` varchar(255) DEFAULT NULL,
                    `maskapai` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`pak_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `data_jamaah` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `pak_id` int(11) DEFAULT NULL,
                    `nama_lengkap` varchar(255) NOT NULL,
                    `jenis_kelamin` enum('Laki-laki','Perempuan') NOT NULL,
                    `tempat_lahir` varchar(255) DEFAULT NULL,
                    `tanggal_lahir` date DEFAULT NULL,
                    `ktp` varchar(20) DEFAULT NULL,
                    `alamat` text DEFAULT NULL,
                    `no_telepon` varchar(20) DEFAULT NULL,
                    `email` varchar(255) DEFAULT NULL,
                    `pekerjaan` varchar(255) DEFAULT NULL,
                    `pendidikan` varchar(255) DEFAULT NULL,
                    `golongan_darah` varchar(5) DEFAULT NULL,
                    `nama_kerabat` varchar(255) DEFAULT NULL,
                    `no_telepon_kerabat` varchar(20) DEFAULT NULL,
                    `hubungan_kerabat` varchar(100) DEFAULT NULL,
                    `riwayat_penyakit` text DEFAULT NULL,
                    `no_passport` varchar(50) DEFAULT NULL,
                    `tanggal_berakhir_passport` date DEFAULT NULL,
                    `asal_kota` varchar(255) DEFAULT NULL,
                    `ukuran_baju` varchar(10) DEFAULT NULL,
                    `tanggal_daftar` timestamp NULL DEFAULT current_timestamp(),
                    `status_pembayaran` enum('Belum Lunas','Lunas') DEFAULT 'Belum Lunas',
                    `metode_pembayaran` varchar(100) DEFAULT NULL,
                    `total_harga` decimal(15,2) DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `pak_id` (`pak_id`),
                    CONSTRAINT `data_jamaah_ibfk_1` FOREIGN KEY (`pak_id`) REFERENCES `paket` (`pak_id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `dokumen` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `jamaah_id` int(11) NOT NULL,
                    `jenis_dokumen` varchar(255) NOT NULL,
                    `nama_file` varchar(255) NOT NULL,
                    `file_path` varchar(500) NOT NULL,
                    `tanggal_upload` timestamp NULL DEFAULT current_timestamp(),
                    `status` enum('pending','approved','rejected') DEFAULT 'pending',
                    `keterangan` text DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `jamaah_id` (`jamaah_id`),
                    CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`jamaah_id`) REFERENCES `data_jamaah` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                CREATE TABLE IF NOT EXISTS `pembatalan` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `jamaah_id` int(11) NOT NULL,
                    `alasan` text NOT NULL,
                    `tanggal_pengajuan` timestamp NULL DEFAULT current_timestamp(),
                    `status` enum('pending','approved','rejected') DEFAULT 'pending',
                    `keterangan_admin` text DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT current_timestamp(),
                    `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                    PRIMARY KEY (`id`),
                    KEY `jamaah_id` (`jamaah_id`),
                    CONSTRAINT `pembatalan_ibfk_1` FOREIGN KEY (`jamaah_id`) REFERENCES `data_jamaah` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

                -- Insert sample data
                INSERT IGNORE INTO `paket` (`pak_id`, `nama_paket`, `jenis_paket`, `tanggal_keberangkatan`, `tanggal_kepulangan`, `harga_quad`, `harga_triple`, `harga_double`, `deskripsi`, `hotel_makkah`, `hotel_madinah`, `maskapai`) VALUES
                (1, 'Paket Haji Plus 2024', 'Haji', '2024-08-15', '2024-09-25', 65000000.00, 70000000.00, 75000000.00, 'Paket haji dengan fasilitas terbaik', 'Hotel Dar Al Hijra', 'Hotel Al Madinah Holiday', 'Garuda Indonesia'),
                (2, 'Paket Umroh Ramadhan', 'Umroh', '2024-04-01', '2024-04-15', 18000000.00, 22000000.00, 25000000.00, 'Paket umroh spesial bulan ramadhan', 'Hotel Makkah Clock Royal Tower', 'Hotel Anwar Al Madinah', 'Saudia Airlines'),
                (3, 'Paket Umroh Reguler', 'Umroh', '2024-06-01', '2024-06-11', 15000000.00, 18000000.00, 22000000.00, 'Paket umroh standar dengan pelayanan prima', 'Hotel Conrad Makkah', 'Hotel Pullman Zamzam', 'Emirates');
            ";
        }
        
        // Execute SQL statements
        if ($dbType === 'postgresql') {
            // For PostgreSQL, execute the entire file
            $pdo->exec($sql);
        } else {
            // For MySQL, split by semicolon and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
        }
        
        echo "<p class='success'>✅ Database tables created successfully!</p>";
        
        // Test the database connection
        if ($dbType === 'postgresql') {
            $result = $pdo->query("SELECT COUNT(*) as count FROM paket")->fetch();
        } else {
            $result = $pdo->query("SELECT COUNT(*) as count FROM paket")->fetch();
        }
        
        echo "<p class='success'>✅ Found {$result['count']} packages in database</p>";
        
    } catch (PDOException $e) {
        $success = false;
        $errors[] = "Database error: " . $e->getMessage();
        echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    return ['success' => $success, 'errors' => $errors];
}

// Check if tables already exist
function checkTables($pdo, $dbType) {
    try {
        if ($dbType === 'postgresql') {
            $result = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'paket'")->fetch();
        } else {
            $result = $pdo->query("SHOW TABLES LIKE 'paket'")->fetch();
        }
        return $result !== false && ($result['count'] > 0 || count($result) > 0);
    } catch (PDOException $e) {
        return false;
    }
}

// HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIW Database Initialization</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; border-left: 4px solid #17a2b8; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        h1 { color: #333; text-align: center; }
        h2 { color: #666; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 MIW Database Initialization</h1>
        
        <?php
        try {
            $dbType = detectDatabaseType($pdo);
            echo "<div class='info'>📊 Database Type: " . strtoupper($dbType) . "</div>";
            
            if (checkTables($pdo, $dbType)) {
                echo "<div class='success'>✅ Database is already initialized!</div>";
                echo "<p>Your MIW application is ready to use.</p>";
            } else {
                echo "<div class='info'>🔧 Initializing database for the first time...</div>";
                $result = initializeDatabase($pdo, $dbType);
                
                if ($result['success']) {
                    echo "<div class='success'>🎉 Database initialization completed successfully!</div>";
                    echo "<p>Your MIW application is now ready for customer registration.</p>";
                } else {
                    echo "<div class='error'>❌ Database initialization failed:</div>";
                    foreach ($result['errors'] as $error) {
                        echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<p>Please check your database configuration.</p>";
        }
        ?>
        
        <h2>📋 Next Steps</h2>
        <p>After successful initialization:</p>
        <ul>
            <li>✅ Test the registration forms</li>
            <li>✅ Check admin dashboard</li>
            <li>✅ Verify email functionality</li>
            <li>✅ Test document upload</li>
        </ul>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="form_haji.php" class="btn">🕋 Haji Registration</a>
            <a href="form_umroh.php" class="btn">🕌 Umroh Registration</a>
            <a href="admin_dashboard.php" class="btn">👨‍💼 Admin Dashboard</a>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <strong>Environment:</strong> <?php echo $_ENV['APP_ENV'] ?? 'development'; ?><br>
            <strong>Database:</strong> <?php echo strtoupper($dbType); ?><br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
