-- PostgreSQL Database Schema for MIW Application
-- Exact conversion from MySQL data_miw database to match PHP application expectations

-- Create enum types for PostgreSQL
DO $$ 
BEGIN
    -- Gender enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'gender_enum') THEN
        CREATE TYPE gender_enum AS ENUM ('Laki-laki', 'Perempuan');
    END IF;
    
    -- Payment status enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_status_enum') THEN
        CREATE TYPE payment_status_enum AS ENUM ('pending', 'verified', 'rejected');
    END IF;
    
    -- Travel type enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'travel_type_enum') THEN
        CREATE TYPE travel_type_enum AS ENUM ('Haji', 'Umroh');
    END IF;
    
    -- Room type enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'room_type_enum') THEN
        CREATE TYPE room_type_enum AS ENUM ('Quad', 'Triple', 'Double');
    END IF;
    
    -- Payment type enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_type_enum') THEN
        CREATE TYPE payment_type_enum AS ENUM ('DP', 'Pelunasan');
    END IF;
    
    -- Payment method enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_method_enum') THEN
        CREATE TYPE payment_method_enum AS ENUM ('BSI', 'BNI', 'Mandiri');
    END IF;
    
    -- Education enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'education_enum') THEN
        CREATE TYPE education_enum AS ENUM ('SD', 'SLTP', 'SLTA', 'D1/D2/D3/SM', 'S1', 'S2', 'S3');
    END IF;
    
    -- Occupation enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'occupation_enum') THEN
        CREATE TYPE occupation_enum AS ENUM ('Pegawai Negeri Sipil', 'TNI/POLRI', 'Dagang', 'Tani/Nelayan', 'Swasta', 'Ibu Rumah Tangga', 'Pelajar/Mahasiswa', 'BUMN/BUMD', 'Pensiunan');
    END IF;
    
    -- Blood type enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'blood_type_enum') THEN
        CREATE TYPE blood_type_enum AS ENUM ('A', 'B', 'AB', 'O');
    END IF;
    
    -- Marital status enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'marital_status_enum') THEN
        CREATE TYPE marital_status_enum AS ENUM ('Belum Menikah', 'Menikah', 'Janda/Duda');
    END IF;
    
    -- Mahram relation enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'mahram_relation_enum') THEN
        CREATE TYPE mahram_relation_enum AS ENUM ('Orang Tua', 'Anak', 'Suami/Istri', 'Mertua', 'Saudara Kandung');
    END IF;
    
    -- Hajj experience enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'hajj_experience_enum') THEN
        CREATE TYPE hajj_experience_enum AS ENUM ('Pernah', 'Belum');
    END IF;
    
    -- Nationality enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'nationality_enum') THEN
        CREATE TYPE nationality_enum AS ENUM ('Indonesia', 'Asing');
    END IF;
    
    -- Currency enum
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'currency_enum') THEN
        CREATE TYPE currency_enum AS ENUM ('IDR', 'USD');
    END IF;
    
END $$;

-- Drop existing tables if they exist
DROP TABLE IF EXISTS data_invoice CASCADE;
DROP TABLE IF EXISTS data_jamaah CASCADE;
DROP TABLE IF EXISTS data_paket CASCADE;
DROP TABLE IF EXISTS data_pembatalan CASCADE;

-- 1. data_paket Table (EXACT match to MySQL structure)
CREATE TABLE data_paket (
    pak_id SERIAL PRIMARY KEY,
    jenis_paket travel_type_enum NOT NULL,
    currency currency_enum NOT NULL,
    program_pilihan VARCHAR(255),
    tanggal_keberangkatan DATE,
    base_price_quad DECIMAL(15,2),
    base_price_triple DECIMAL(15,2),
    base_price_double DECIMAL(15,2),
    hotel_medinah VARCHAR(100),
    hotel_makkah VARCHAR(100),
    additional_hotels VARCHAR(100),
    hotel_medinah_rooms TEXT,
    hotel_makkah_rooms TEXT,
    additional_hotels_rooms TEXT,
    room_numbers TEXT,
    hcn TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. data_jamaah Table (EXACT match to MySQL structure)
CREATE TABLE data_jamaah (
    nik VARCHAR(16) PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    tempat_lahir VARCHAR(50),
    tanggal_lahir DATE,
    jenis_kelamin gender_enum,
    alamat TEXT,
    kode_pos VARCHAR(10),
    email VARCHAR(50),
    no_telp VARCHAR(20),
    tinggi_badan INTEGER,
    berat_badan INTEGER,
    nama_ayah VARCHAR(100),
    nama_ibu VARCHAR(100),
    umur INTEGER,
    kewarganegaraan nationality_enum,
    desa_kelurahan VARCHAR(100),
    kecamatan VARCHAR(100),
    kabupaten_kota VARCHAR(100),
    provinsi VARCHAR(100),
    pendidikan education_enum,
    pekerjaan occupation_enum,
    golongan_darah blood_type_enum,
    status_perkawinan marital_status_enum,
    ciri_rambut VARCHAR(50),
    ciri_alis VARCHAR(50),
    ciri_hidung VARCHAR(50),
    ciri_muka VARCHAR(50),
    emergency_nama VARCHAR(255),
    emergency_hp VARCHAR(20),
    nama_mahram VARCHAR(100),
    hubungan_mahram mahram_relation_enum,
    nomor_mahram VARCHAR(20),
    nama_paspor VARCHAR(255),
    no_paspor VARCHAR(50),
    tempat_pembuatan_paspor VARCHAR(255),
    tanggal_pengeluaran_paspor DATE,
    tanggal_habis_berlaku DATE,
    nama_sertifikat_covid VARCHAR(255),
    jenis_vaksin_1 VARCHAR(100),
    jenis_vaksin_2 VARCHAR(100),
    jenis_vaksin_3 VARCHAR(100),
    tanggal_vaksin_1 DATE,
    tanggal_vaksin_2 DATE,
    tanggal_vaksin_3 DATE,
    pengalaman_haji hajj_experience_enum,
    marketing_nama VARCHAR(100),
    marketing_hp VARCHAR(20),
    marketing_type VARCHAR(20),
    kk_path VARCHAR(255),
    ktp_path VARCHAR(255),
    paspor_path VARCHAR(255),
    transfer_account_name VARCHAR(100),
    payment_time TIME,
    payment_date DATE,
    payment_type payment_type_enum,
    payment_method payment_method_enum,
    payment_status payment_status_enum,
    payment_path VARCHAR(255),
    payment_total DECIMAL(15,2),
    payment_remaining DECIMAL(15,2),
    payment_verified_at TIMESTAMP,
    payment_rejected_at TIMESTAMP,
    payment_verified_by VARCHAR(100),
    bk_kuning_path VARCHAR(255),
    foto_path VARCHAR(255),
    fc_ktp_path VARCHAR(255),
    fc_ijazah_path VARCHAR(255),
    fc_kk_path VARCHAR(255),
    fc_bk_nikah_path VARCHAR(255),
    fc_akta_lahir_path VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pak_id INTEGER,
    type_room_pilihan room_type_enum,
    request_khusus TEXT,
    room_prefix VARCHAR(5),
    medinah_room_number VARCHAR(5),
    mekkah_room_number VARCHAR(5),
    room_relation VARCHAR(30),
    
    -- Foreign key constraint
    CONSTRAINT fk_data_jamaah_pak_id FOREIGN KEY (pak_id) REFERENCES data_paket(pak_id)
);

-- 3. data_invoice Table (EXACT match to MySQL structure)
CREATE TABLE data_invoice (
    invoice_id VARCHAR(8) PRIMARY KEY,
    pak_id INTEGER,
    nik VARCHAR(16),
    nama VARCHAR(100),
    alamat TEXT,
    no_telp VARCHAR(20),
    keterangan TEXT,
    payment_type payment_type_enum,
    program_pilihan VARCHAR(255),
    type_room_pilihan room_type_enum,
    harga_paket DECIMAL(15,2),
    payment_amount DECIMAL(15,2),
    diskon INTEGER,
    total_uang_masuk DECIMAL(15,2),
    sisa_pembayaran DECIMAL(15,2)
);

-- 4. data_pembatalan Table (EXACT match to MySQL structure)
CREATE TABLE data_pembatalan (
    pembatalan_id SERIAL PRIMARY KEY,
    nik VARCHAR(16) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    no_telp VARCHAR(20),
    email VARCHAR(50),
    alasan TEXT,
    kwitansi_path VARCHAR(255),
    proof_path VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance (matching MySQL structure)
CREATE INDEX idx_data_jamaah_pak_id ON data_jamaah(pak_id);
CREATE INDEX idx_data_jamaah_payment_status ON data_jamaah(payment_status);
CREATE INDEX idx_data_invoice_nik ON data_invoice(nik);
CREATE INDEX idx_data_pembatalan_nik ON data_pembatalan(nik);

-- Create triggers for automatic timestamp updates
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_data_paket_updated_at 
    BEFORE UPDATE ON data_paket 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_data_jamaah_updated_at 
    BEFORE UPDATE ON data_jamaah 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_data_pembatalan_updated_at 
    BEFORE UPDATE ON data_pembatalan 
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert sample data from your MySQL database
INSERT INTO data_paket (
    pak_id, jenis_paket, currency, program_pilihan, tanggal_keberangkatan, 
    base_price_quad, base_price_triple, base_price_double, hotel_medinah, 
    hotel_makkah, additional_hotels, hotel_medinah_rooms, hotel_makkah_rooms, 
    additional_hotels_rooms, room_numbers, hcn
) VALUES 
(5, 'Umroh', 'IDR', 'UMRAH AGUSTUS SAFAR MUBARAK GOLD', '2025-08-10', 
 32500000.00, 34000000.00, 36500000.00, ' Al Anshor Golden Tulip', 
 'Hilton Convention', '[]', 
 '{"quad":["101","102","103","104","105"],"triple":["305","307"],"double":["606","609"]}', 
 '{"quad":["204","207","205","208"],"triple":["609","702"],"double":["606","600"]}', 
 '[]', 'Q1,Q2,Q3,Q4,T1,T2,D1,D2', 
 '{"medinah":"Medinah-Testing-1","makkah":"Makkah-Testing-1","additional":[],"issued_date":"2025-12-31","expiry_date":"2025-12-31"}'),

(6, 'Umroh', 'IDR', 'UMRAH AGUSTUS SAFAR MUBARAK PLATINUM', '2025-08-27', 
 40500000.00, 42000000.00, 46500000.00, 'Hotel Madinah Al Haram', 
 'Hotel Mekah Jumeirah', '[]', 
 '{"quad":["101","102","103","104","105"],"triple":["209","301"],"double":["606","609"]}', 
 '{"quad":["101","102","103","104","105"],"triple":["305","307"],"double":["606","600"]}', 
 '[]', 'Q1,Q2,Q3,Q4,Q5,T1,T2,D1,D2', 
 '{"medinah":"MAD-TESTING-1","makkah":"MAK-TESTING-2","additional":[],"issued_date":"2025-12-31","expiry_date":"2025-12-31"}'),

(7, 'Umroh', 'IDR', 'UMROH SEPTEMBER MAULID 1447H REGULER ', '2025-09-03', 
 32000000.00, 33000000.00, 35000000.00, 'Ansar Golden Tulip', 
 'Al Shohada', '[]', 
 '{"quad":["101","102","103","104","105"],"triple":["303","304","205","206","207"],"double":["408","409","501"]}', 
 '{"quad":["101","102","103","104","105"],"triple":["303","304","205","206","207"],"double":["408","409","501"]}', 
 '[]', 'Q1,Q2,Q3,Q4,Q5,T1,T2,T3,T4,T5,D1,D2,D3', 
 '{"medinah":"MAD-TESTING-2","makkah":"MAK-TESTING-2","additional":[],"issued_date":"2025-12-31","expiry_date":"2025-12-31"}'),

(12, 'Haji', 'USD', 'Haji Signature 2026', '2026-06-12', 
 16000.00, 18500.00, 21000.00, 'Hotel Medinah Suites', 
 'Hilton Convention', '[]', 
 '{"quad":["101","102","103","104","105"],"triple":["305","307"],"double":["701"]}', 
 '{"quad":["204","207","205","208"],"triple":["305","307"],"double":["606","609"]}', 
 '[]', 'Q1,Q2,Q3,Q4,T1,T2,D1', 
 '{"medinah":"MAD-3209674-3245-325643","makkah":"MAK-32525-43654-324","additional":[],"issued_date":"2026-07-12","expiry_date":"2026-07-23"}');

-- Reset sequence to continue from the highest pak_id
SELECT setval('data_paket_pak_id_seq', COALESCE((SELECT MAX(pak_id) FROM data_paket), 1), true);

-- Insert some sample jamaah data (matching the structure from your MySQL backup)
INSERT INTO data_jamaah (
    nik, nama, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, kode_pos, 
    email, no_telp, tinggi_badan, berat_badan, nama_ayah, umur, kewarganegaraan, 
    desa_kelurahan, kecamatan, kabupaten_kota, provinsi, pendidikan, pekerjaan, 
    golongan_darah, status_perkawinan, ciri_rambut, ciri_alis, ciri_hidung, 
    ciri_muka, nama_mahram, hubungan_mahram, nomor_mahram, pengalaman_haji, 
    marketing_nama, marketing_hp, marketing_type, kk_path, ktp_path, 
    transfer_account_name, payment_time, payment_date, payment_type, 
    payment_method, payment_status, payment_path, payment_total, 
    payment_remaining, payment_verified_at, payment_verified_by, 
    created_at, updated_at, pak_id, type_room_pilihan, request_khusus
) VALUES 
('3273272102010001', 'Rudolf Mitscher', 'Sumatra', '2025-12-31', 'Laki-laki', 
 'Bandung', '42569', 'winstonarma7@gmail.com', '082126389444', 100, 30, 
 'Ayaha', -1, 'Indonesia', 'Cisaranten Kidul', 'Gedebage', 'Kota Bandung', 
 'Jawa Barat', 'SLTP', 'Pegawai Negeri Sipil', 'A', 'Belum Menikah', 
 'ikal', 'Test', 'Hitam', 'Panjang', 'Drake Andresson', 'Suami/Istri', 
 '3273272102010001', 'Pernah', 'Rudolf Mitscher', '082126389444', 'mandiri', 
 '/MIW/uploads/documents/12_3273272102010001_kk_20250722102940.pdf', 
 '/MIW/uploads/documents/12_3273272102010001_ktp_20250722102940.png', 
 'Yunus', '10:29:48', '2025-07-22', 'DP', 'BSI', 'verified', 
 '/MIW/uploads/payments/3273272102010001_payment_20250722102948.png', 
 5000.00, 16000.00, '2025-07-22 03:30:07', 'Admin', 
 '2025-07-22 03:29:40', '2025-07-22 03:30:07', 12, 'Double', 'Testing'),

('3273272102010002', 'Yusuf Hendra', 'Jakarta', '2025-12-31', 'Laki-laki', 
 'Testing', '42569', 'winstonarma7@gmail.com', '081221030301', 100, 30, 
 'Test', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 
 NULL, NULL, NULL, NULL, 'Drake Andresson', NULL, NULL, NULL, 
 'Yusuf Hendra', '081221030301', 'mandiri', 
 '/MIW/uploads/documents/6_3273272102010002_kk_20250721200849.png', 
 '/MIW/uploads/documents/6_3273272102010002_ktp_20250721200849.pdf', 
 'Kevin', '20:08:58', '2025-07-21', 'DP', 'BNI', 'verified', 
 '/MIW/uploads/payments/3273272102010002_payment_20250721200858.png', 
 5000000.00, 37000000.00, '2025-07-21 13:09:22', 'Admin', 
 '2025-07-21 13:08:49', '2025-07-21 13:09:22', 6, 'Triple', 'Testing');

-- Insert corresponding invoice data
INSERT INTO data_invoice (
    invoice_id, pak_id, nik, nama, alamat, no_telp, keterangan, 
    payment_type, program_pilihan, type_room_pilihan, harga_paket, 
    payment_amount, total_uang_masuk, sisa_pembayaran
) VALUES 
('20250004', 12, '3273272102010001', 'Rudolf Mitscher', 'Bandung', 
 '082126389444', 'Pembayaran DP', 'DP', 'Haji Signature 2026', 'Double', 
 21000.00, 5000.00, 5000.00, 16000.00),

('20250002', 6, '3273272102010002', 'Yusuf Hendra', 'Testing', 
 '081221030301', 'Pembayaran DP', 'DP', 'UMRAH AGUSTUS SAFAR MUBARAK PLATINUM', 
 'Triple', 42000000.00, 5000000.00, 5000000.00, 37000000.00);
