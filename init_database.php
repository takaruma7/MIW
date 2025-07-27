<?php
// Database initialization script for Railway deployment
// This will automatically create tables when the application first runs

set_time_limit(20); // 20 seconds max
ini_set('max_execution_time', 20);

require_once 'config.php';

function initializeDatabase($pdo) {
    $tables = [
        'paket' => "
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'data_jamaah' => "
            CREATE TABLE IF NOT EXISTS `data_jamaah` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `pak_id` int(11) DEFAULT NULL,
                `nama_lengkap` varchar(255) NOT NULL,
                `alamat` text DEFAULT NULL,
                `no_telp` varchar(20) DEFAULT NULL,
                `nik` varchar(16) DEFAULT NULL,
                `tempat_lahir` varchar(100) DEFAULT NULL,
                `tanggal_lahir` date DEFAULT NULL,
                `gender` enum('Laki-laki','Perempuan') DEFAULT NULL,
                `pekerjaan` varchar(100) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'manifest' => "
            CREATE TABLE IF NOT EXISTS `manifest` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `pak_id` int(11) DEFAULT NULL,
                `nik` varchar(16) DEFAULT NULL,
                `nama_lengkap` varchar(255) DEFAULT NULL,
                `tempat_lahir` varchar(100) DEFAULT NULL,
                `tanggal_lahir` date DEFAULT NULL,
                `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT NULL,
                `alamat` text DEFAULT NULL,
                `no_telp` varchar(20) DEFAULT NULL,
                `no_passport` varchar(50) DEFAULT NULL,
                `kloter` varchar(50) DEFAULT NULL,
                `hotel_makkah` varchar(255) DEFAULT NULL,
                `hotel_madinah` varchar(255) DEFAULT NULL,
                `created_at` timestamp NULL DEFAULT current_timestamp(),
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'data_invoice' => "
            CREATE TABLE IF NOT EXISTS `data_invoice` (
                `invoice_id` varchar(8) NOT NULL,
                `pak_id` int(11) DEFAULT NULL,
                `nik` varchar(16) DEFAULT NULL,
                `nama` varchar(100) DEFAULT NULL,
                `no_telp` varchar(20) DEFAULT NULL,
                `payment_type` enum('DP','Pelunasan') DEFAULT NULL,
                `harga_paket` decimal(15,2) DEFAULT NULL,
                `payment_amount` decimal(15,2) DEFAULT NULL,
                `total_uang_masuk` decimal(15,2) DEFAULT NULL,
                PRIMARY KEY (`invoice_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ",
        
        'pembatalan' => "
            CREATE TABLE IF NOT EXISTS `pembatalan` (
                `pembatalan_id` int(11) NOT NULL AUTO_INCREMENT,
                `pak_id` int(11) DEFAULT NULL,
                `nik` varchar(16) DEFAULT NULL,
                `nama_lengkap` varchar(255) DEFAULT NULL,
                `alasan_pembatalan` text DEFAULT NULL,
                `tanggal_pembatalan` timestamp NULL DEFAULT current_timestamp(),
                `status_pembatalan` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
                PRIMARY KEY (`pembatalan_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        "
    ];
    
    try {
        // Create tables
        foreach ($tables as $tableName => $sql) {
            $pdo->exec($sql);
            echo "✓ Table '$tableName' created successfully<br>";
        }
        
        // Insert sample data if paket table is empty
        $stmt = $pdo->query("SELECT COUNT(*) FROM paket");
        $count = $stmt->fetchColumn();
        
        if ($count == 0) {
            $sampleData = "
                INSERT INTO `paket` (`nama_paket`, `jenis_paket`, `tanggal_keberangkatan`, `tanggal_kepulangan`, `harga_quad`, `harga_triple`, `harga_double`, `deskripsi`, `hotel_makkah`, `hotel_madinah`, `maskapai`) VALUES
                ('Umroh Ekonomi 2025', 'Umroh', '2025-09-15', '2025-09-25', 25000000.00, 27000000.00, 30000000.00, 'Paket umroh ekonomis dengan fasilitas lengkap', 'Hotel Al Kiswah', 'Hotel Al Madinah', 'Garuda Indonesia'),
                ('Haji Regular 2025', 'Haji', '2025-08-10', '2025-09-20', 45000000.00, 50000000.00, 55000000.00, 'Paket haji regular dengan pelayanan terbaik', 'Hotel Makkah Towers', 'Hotel Madinah Hilton', 'Saudia Airlines'),
                ('Umroh Plus Turki', 'Umroh', '2025-10-01', '2025-10-15', 35000000.00, 38000000.00, 42000000.00, 'Paket umroh plus wisata Turki', 'Hotel Zamzam', 'Hotel Crown Plaza', 'Turkish Airlines')
            ";
            $pdo->exec($sampleData);
            echo "✓ Sample travel packages inserted<br>";
        }
        
        return true;
    } catch (PDOException $e) {
        echo "❌ Database initialization error: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Auto-initialize database if this file is accessed directly
if (basename($_SERVER['PHP_SELF']) == 'init_database.php') {
    echo "<h2>🚀 MIW Database Initialization</h2>";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . $_ENV['DB_HOST'] . ";port=" . $_ENV['DB_PORT'] . ";dbname=" . $_ENV['DB_NAME'],
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        echo "✓ Connected to Railway MySQL<br><br>";
        
        if (initializeDatabase($pdo)) {
            echo "<br>🎉 <strong>Database initialization completed successfully!</strong><br>";
            echo "<a href='index.php'>← Back to MIW Application</a>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage();
    }
}
?>
