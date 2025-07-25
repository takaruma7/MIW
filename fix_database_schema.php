<?php
// Quick Database Schema Fix for Heroku PostgreSQL
// This will fix the schema mismatch and table creation issues

// Force Heroku environment detection
require_once 'config.heroku.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Database Schema - MIW</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background-color: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; border-left: 4px solid #28a745; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 10px 0; }
        .info { color: #17a2b8; background: #d1ecf1; padding: 10px; border-radius: 5px; border-left: 4px solid #17a2b8; margin: 10px 0; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        h1 { color: #333; text-align: center; }
        .code { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Database Schema Fix</h1>
        
        <?php
        echo "<div class='info'>🗄️ Connecting to Heroku PostgreSQL database...</div>";
        
        try {
            // Create PostgreSQL tables with correct schema for your existing app
            $sql = "
                -- Create paket table (package information)
                CREATE TABLE IF NOT EXISTS paket (
                    pak_id SERIAL PRIMARY KEY,
                    nama_paket VARCHAR(255) NOT NULL,
                    jenis_paket VARCHAR(50) NOT NULL CHECK (jenis_paket IN ('Haji', 'Umroh')),
                    tanggal_keberangkatan DATE,
                    tanggal_kepulangan DATE,
                    harga_quad DECIMAL(15,2),
                    harga_triple DECIMAL(15,2),
                    harga_double DECIMAL(15,2),
                    deskripsi TEXT,
                    hotel_makkah VARCHAR(255),
                    hotel_madinah VARCHAR(255),
                    maskapai VARCHAR(255),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Create data_jamaah table (customer data)
                CREATE TABLE IF NOT EXISTS data_jamaah (
                    id SERIAL PRIMARY KEY,
                    pak_id INTEGER REFERENCES paket(pak_id) ON DELETE SET NULL,
                    nama_lengkap VARCHAR(255) NOT NULL,
                    jenis_kelamin VARCHAR(20) NOT NULL CHECK (jenis_kelamin IN ('Laki-laki', 'Perempuan')),
                    tempat_lahir VARCHAR(255),
                    tanggal_lahir DATE,
                    ktp VARCHAR(20),
                    alamat TEXT,
                    no_telepon VARCHAR(20),
                    email VARCHAR(255),
                    pekerjaan VARCHAR(255),
                    pendidikan VARCHAR(255),
                    golongan_darah VARCHAR(5),
                    nama_kerabat VARCHAR(255),
                    no_telepon_kerabat VARCHAR(20),
                    hubungan_kerabat VARCHAR(100),
                    riwayat_penyakit TEXT,
                    no_passport VARCHAR(50),
                    tanggal_berakhir_passport DATE,
                    asal_kota VARCHAR(255),
                    ukuran_baju VARCHAR(10),
                    tanggal_daftar TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status_pembayaran VARCHAR(50) DEFAULT 'Belum Lunas' CHECK (status_pembayaran IN ('Belum Lunas', 'Lunas')),
                    metode_pembayaran VARCHAR(100),
                    total_harga DECIMAL(15,2),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Create dokumen table (document management)
                CREATE TABLE IF NOT EXISTS dokumen (
                    id SERIAL PRIMARY KEY,
                    jamaah_id INTEGER REFERENCES data_jamaah(id) ON DELETE CASCADE,
                    jenis_dokumen VARCHAR(255) NOT NULL,
                    nama_file VARCHAR(255) NOT NULL,
                    file_path VARCHAR(500) NOT NULL,
                    tanggal_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
                    keterangan TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Create pembatalan table (cancellation management)
                CREATE TABLE IF NOT EXISTS pembatalan (
                    id SERIAL PRIMARY KEY,
                    jamaah_id INTEGER REFERENCES data_jamaah(id) ON DELETE CASCADE,
                    alasan TEXT NOT NULL,
                    tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    status VARCHAR(50) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
                    keterangan_admin TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                );

                -- Insert sample data
                INSERT INTO paket (nama_paket, jenis_paket, tanggal_keberangkatan, tanggal_kepulangan, harga_quad, harga_triple, harga_double, deskripsi, hotel_makkah, hotel_madinah, maskapai) 
                VALUES 
                    ('Paket Haji Plus 2024', 'Haji', '2024-08-15', '2024-09-25', 65000000.00, 70000000.00, 75000000.00, 'Paket haji dengan fasilitas terbaik', 'Hotel Dar Al Hijra', 'Hotel Al Madinah Holiday', 'Garuda Indonesia'),
                    ('Paket Umroh Ramadhan', 'Umroh', '2024-04-01', '2024-04-15', 18000000.00, 22000000.00, 25000000.00, 'Paket umroh spesial bulan ramadhan', 'Hotel Makkah Clock Royal Tower', 'Hotel Anwar Al Madinah', 'Saudia Airlines'),
                    ('Paket Umroh Reguler', 'Umroh', '2024-06-01', '2024-06-11', 15000000.00, 18000000.00, 22000000.00, 'Paket umroh standar dengan pelayanan prima', 'Hotel Conrad Makkah', 'Hotel Pullman Zamzam', 'Emirates')
                ON CONFLICT DO NOTHING;

                -- Create indexes for better performance
                CREATE INDEX IF NOT EXISTS idx_data_jamaah_pak_id ON data_jamaah(pak_id);
                CREATE INDEX IF NOT EXISTS idx_data_jamaah_email ON data_jamaah(email);
                CREATE INDEX IF NOT EXISTS idx_dokumen_jamaah_id ON dokumen(jamaah_id);
                CREATE INDEX IF NOT EXISTS idx_pembatalan_jamaah_id ON pembatalan(jamaah_id);
            ";
            
            // Execute the SQL
            $pdo->exec($sql);
            
            echo "<div class='success'>✅ Database schema created successfully!</div>";
            
            // Test the tables
            $result = $pdo->query("SELECT COUNT(*) as count FROM paket")->fetch();
            echo "<div class='success'>✅ Found {$result['count']} packages in database</div>";
            
            $result = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
            echo "<div class='success'>✅ Created tables: " . implode(', ', $result) . "</div>";
            
            echo "<div class='info'>🎉 Your database is now ready! You can test your application.</div>";
            
        } catch (PDOException $e) {
            echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<div class='info'>📋 This error might occur if tables already exist or there's a connection issue.</div>";
        }
        ?>
        
        <h2>📋 Next Steps</h2>
        <div style="text-align: center; margin-top: 30px;">
            <a href="form_haji.php" class="btn">🕋 Test Haji Registration</a>
            <a href="form_umroh.php" class="btn">🕌 Test Umroh Registration</a>
            <a href="admin_dashboard.php" class="btn">👨‍💼 Admin Dashboard</a>
            <a href="database_diagnostic.php" class="btn">🔍 Run Diagnostics</a>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-size: 12px; color: #666;">
            <strong>Environment:</strong> Heroku PostgreSQL<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
