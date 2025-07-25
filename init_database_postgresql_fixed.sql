-- PostgreSQL Database Schema for MIW Application
-- Tables named to match PHP application expectations

-- Create enum types for PostgreSQL
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gender_enum') THEN
        CREATE TYPE gender_enum AS ENUM ('Laki-laki', 'Perempuan');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_status_enum') THEN
        CREATE TYPE payment_status_enum AS ENUM ('pending', 'verified', 'rejected');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'travel_type_enum') THEN
        CREATE TYPE travel_type_enum AS ENUM ('Haji', 'Umroh');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'room_type_enum') THEN
        CREATE TYPE room_type_enum AS ENUM ('Quad', 'Triple', 'Double');
    END IF;
END $$;

-- 1. data_paket Table (matches PHP expectations)
CREATE TABLE IF NOT EXISTS data_paket (
    pak_id SERIAL PRIMARY KEY,
    program_pilihan VARCHAR(255) NOT NULL,
    jenis_paket travel_type_enum NOT NULL,
    tanggal_keberangkatan DATE NOT NULL,
    tanggal_kepulangan DATE,
    base_price_quad DECIMAL(15,2),
    base_price_triple DECIMAL(15,2),
    base_price_double DECIMAL(15,2),
    deskripsi TEXT,
    hotel_makkah VARCHAR(255),
    hotel_madinah VARCHAR(255),
    maskapai VARCHAR(255),
    currency VARCHAR(3) DEFAULT 'IDR',
    room_numbers TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. data_jamaah Table (main participant table)
CREATE TABLE IF NOT EXISTS data_jamaah (
    id SERIAL PRIMARY KEY,
    pak_id INTEGER REFERENCES data_paket(pak_id) ON DELETE CASCADE,
    nik VARCHAR(16) UNIQUE NOT NULL,
    nama VARCHAR(255) NOT NULL,
    jenis_kelamin gender_enum NOT NULL,
    tempat_lahir VARCHAR(255),
    tanggal_lahir DATE,
    umur INTEGER,
    alamat TEXT,
    kode_pos VARCHAR(10),
    no_telp VARCHAR(20),
    email VARCHAR(255),
    pekerjaan VARCHAR(255),
    pendidikan VARCHAR(255),
    golongan_darah VARCHAR(5),
    kewarganegaraan VARCHAR(100) DEFAULT 'Indonesia',
    
    -- Family information
    nama_ayah VARCHAR(255),
    nama_ibu VARCHAR(255),
    status_perkawinan VARCHAR(50),
    nama_mahram VARCHAR(255),
    hubungan_mahram VARCHAR(100),
    no_telp_mahram VARCHAR(20),
    
    -- Physical information
    tinggi_badan INTEGER,
    berat_badan INTEGER,
    
    -- Passport information
    nama_passport VARCHAR(255),
    no_passport VARCHAR(50),
    tanggal_berakhir_passport DATE,
    asal_kota VARCHAR(255),
    
    -- Package selection
    type_room_pilihan room_type_enum,
    ukuran_baju VARCHAR(10),
    
    -- Payment information
    payment_type VARCHAR(50),
    payment_method VARCHAR(100),
    payment_status payment_status_enum DEFAULT 'pending',
    payment_total DECIMAL(15,2),
    payment_remaining DECIMAL(15,2),
    payment_date DATE,
    payment_time TIME,
    payment_path VARCHAR(500),
    payment_verified_at TIMESTAMP,
    payment_rejected_at TIMESTAMP,
    payment_verified_by VARCHAR(100),
    transfer_account_name VARCHAR(255),
    
    -- Document paths (for file uploads)
    kk_path VARCHAR(500),
    ktp_path VARCHAR(500),
    paspor_path VARCHAR(500),
    bk_kuning_path VARCHAR(500),
    foto_path VARCHAR(500),
    fc_ktp_path VARCHAR(500),
    fc_ijazah_path VARCHAR(500),
    fc_kk_path VARCHAR(500),
    fc_bk_nikah_path VARCHAR(500),
    fc_akta_lahir_path VARCHAR(500),
    
    -- Room assignment (for manifest)
    room_prefix VARCHAR(10),
    room_relation VARCHAR(100),
    medinah_room_number VARCHAR(10),
    mekkah_room_number VARCHAR(10),
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. data_invoice Table (for payment tracking)
CREATE TABLE IF NOT EXISTS data_invoice (
    invoice_id VARCHAR(8) PRIMARY KEY,
    pak_id INTEGER REFERENCES data_paket(pak_id),
    nik VARCHAR(16) REFERENCES data_jamaah(nik),
    nama VARCHAR(100),
    no_telp VARCHAR(20),
    payment_type VARCHAR(50),
    harga_paket DECIMAL(15,2),
    payment_amount DECIMAL(15,2),
    total_uang_masuk DECIMAL(15,2),
    sisa_pembayaran DECIMAL(15,2),
    diskon DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. data_pembatalan Table (for cancellations)
CREATE TABLE IF NOT EXISTS data_pembatalan (
    id SERIAL PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    nama VARCHAR(255) NOT NULL,
    no_telp VARCHAR(20),
    email VARCHAR(255),
    alasan TEXT NOT NULL,
    kwitansi_path VARCHAR(500),
    proof_path VARCHAR(500),
    tanggal_pengajuan TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'pending',
    keterangan_admin TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. data_manifest Table (for departure manifest - optional, most data in data_jamaah)
CREATE TABLE IF NOT EXISTS data_manifest (
    id SERIAL PRIMARY KEY,
    pak_id INTEGER REFERENCES data_paket(pak_id),
    nik VARCHAR(16) REFERENCES data_jamaah(nik),
    nama VARCHAR(255),
    room_type VARCHAR(50),
    room_number VARCHAR(10),
    seat_number VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(pak_id, nik)
);

-- Insert sample data for data_paket
INSERT INTO data_paket (
    program_pilihan, jenis_paket, tanggal_keberangkatan, tanggal_kepulangan,
    base_price_quad, base_price_triple, base_price_double,
    deskripsi, hotel_makkah, hotel_madinah, maskapai, currency, room_numbers
) VALUES 
(
    'Paket Haji Plus 2024', 'Haji', '2024-08-15', '2024-09-25',
    65000000.00, 70000000.00, 75000000.00,
    'Paket haji dengan fasilitas terbaik',
    'Hotel Dar Al Hijra', 'Hotel Al Madinah Holiday', 'Garuda Indonesia',
    'IDR', 'Q1,Q2,Q3,T1,T2,D1,D2'
),
(
    'Paket Umroh Ramadhan', 'Umroh', '2024-04-01', '2024-04-15',
    18000000.00, 22000000.00, 25000000.00,
    'Paket umroh spesial bulan ramadhan',
    'Hotel Makkah Clock Royal Tower', 'Hotel Anwar Al Madinah', 'Saudia Airlines',
    'IDR', 'Q1,Q2,Q3,T1,T2,D1,D2'
),
(
    'Paket Umroh Reguler', 'Umroh', '2024-06-01', '2024-06-11',
    15000000.00, 18000000.00, 22000000.00,
    'Paket umroh standar dengan pelayanan prima',
    'Hotel Conrad Makkah', 'Hotel Pullman Zamzam', 'Emirates',
    'IDR', 'Q1,Q2,Q3,T1,T2,D1,D2'
)
ON CONFLICT DO NOTHING;

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_data_jamaah_pak_id ON data_jamaah(pak_id);
CREATE INDEX IF NOT EXISTS idx_data_jamaah_nik ON data_jamaah(nik);
CREATE INDEX IF NOT EXISTS idx_data_jamaah_payment_status ON data_jamaah(payment_status);
CREATE INDEX IF NOT EXISTS idx_data_invoice_nik ON data_invoice(nik);
CREATE INDEX IF NOT EXISTS idx_data_pembatalan_nik ON data_pembatalan(nik);
CREATE INDEX IF NOT EXISTS idx_data_manifest_pak_id ON data_manifest(pak_id);
