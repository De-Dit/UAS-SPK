-- =========================================================
-- SPK Seleksi Karyawan (AHP-SAW) - Skema & Data Studi Kasus
-- Studi kasus: UD Bali Trip Rental (Rental Motor & Jasa Tur)
-- =========================================================

CREATE DATABASE IF NOT EXISTS spk_seleksi_karyawan;
USE spk_seleksi_karyawan;

-- Tabel pengambil keputusan (Decision Maker)
CREATE TABLE dm (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    jabatan VARCHAR(100) NOT NULL
);

-- Tabel kriteria
CREATE TABLE kriteria (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    tipe ENUM('benefit','cost') NOT NULL,
    bobot DECIMAL(6,4) DEFAULT NULL
);

-- Tabel kandidat (alternatif)
CREATE TABLE kandidat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode VARCHAR(10) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    posisi_dilamar VARCHAR(100) DEFAULT 'Staff Operasional & Front Office'
);

-- Nilai tiap kandidat pada tiap kriteria (data mentah, belum dinormalisasi)
CREATE TABLE nilai_kandidat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kandidat_id INT NOT NULL,
    kriteria_id INT NOT NULL,
    nilai DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (kandidat_id) REFERENCES kandidat(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_id) REFERENCES kriteria(id) ON DELETE CASCADE
);

-- Matriks perbandingan berpasangan AHP per DM (disimpan per sel i,j)
CREATE TABLE ahp_pairwise (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dm_id INT NOT NULL,
    kriteria_i INT NOT NULL,
    kriteria_j INT NOT NULL,
    nilai DECIMAL(10,6) NOT NULL,
    FOREIGN KEY (dm_id) REFERENCES dm(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_i) REFERENCES kriteria(id) ON DELETE CASCADE,
    FOREIGN KEY (kriteria_j) REFERENCES kriteria(id) ON DELETE CASCADE
);

-- Hasil akhir SAW (disimpan agar terdokumentasi / audit trail)
CREATE TABLE hasil_akhir (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kandidat_id INT NOT NULL,
    nilai_preferensi DECIMAL(10,6) NOT NULL,
    ranking INT NOT NULL,
    dihitung_pada DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kandidat_id) REFERENCES kandidat(id) ON DELETE CASCADE
);

-- =========================================================
-- SEED DATA - sesuai studi kasus pada laporan (Bab 3.5)
-- =========================================================

INSERT INTO dm (nama, jabatan) VALUES
('I Wayan Sudarma', 'Pemilik / Owner'),
('Ni Made Ayu Kartika', 'Manajer Operasional');

INSERT INTO kriteria (kode, nama, tipe) VALUES
('C1', 'Pengalaman Kerja (tahun)', 'benefit'),
('C2', 'Nilai Wawancara', 'benefit'),
('C3', 'Kemampuan Bahasa Asing', 'benefit'),
('C4', 'Fleksibilitas Jadwal Kerja', 'benefit'),
('C5', 'Ekspektasi Gaji (Rp/bulan)', 'cost');

INSERT INTO kandidat (kode, nama) VALUES
('K1', 'Kandidat A'),
('K2', 'Kandidat B'),
('K3', 'Kandidat C'),
('K4', 'Kandidat D'),
('K5', 'Kandidat E');

-- Nilai mentah kandidat (kriteria_id mengikuti urutan insert C1..C5 = id 1..5)
INSERT INTO nilai_kandidat (kandidat_id, kriteria_id, nilai) VALUES
(1,1,3),(1,2,85),(1,3,4),(1,4,4),(1,5,4000000),
(2,1,5),(2,2,78),(2,3,3),(2,4,5),(2,5,4500000),
(3,1,2),(3,2,90),(3,3,5),(3,4,3),(3,5,5000000),
(4,1,4),(4,2,82),(4,3,3),(4,4,4),(4,5,3800000),
(5,1,1),(5,2,75),(5,3,2),(5,4,5),(5,5,3500000);

-- Matriks pairwise AHP DM1 (Owner) -- kriteria_i dibanding kriteria_j
INSERT INTO ahp_pairwise (dm_id, kriteria_i, kriteria_j, nilai) VALUES
(1,1,1,1),      (1,1,2,0.5),   (1,1,3,3),     (1,1,4,5),     (1,1,5,5),
(1,2,1,2),      (1,2,2,1),     (1,2,3,4),     (1,2,4,6),     (1,2,5,6),
(1,3,1,0.3333), (1,3,2,0.25),  (1,3,3,1),     (1,3,4,2),     (1,3,5,2),
(1,4,1,0.2),    (1,4,2,0.1667),(1,4,3,0.5),   (1,4,4,1),     (1,4,5,1),
(1,5,1,0.2),    (1,5,2,0.1667),(1,5,3,0.5),   (1,5,4,1),     (1,5,5,1);

-- Matriks pairwise AHP DM2 (Manajer Operasional)
INSERT INTO ahp_pairwise (dm_id, kriteria_i, kriteria_j, nilai) VALUES
(2,1,1,1),      (2,1,2,0.3333),(2,1,3,2),     (2,1,4,3),     (2,1,5,4),
(2,2,1,3),      (2,2,2,1),     (2,2,3,3),     (2,2,4,4),     (2,2,5,5),
(2,3,1,0.5),    (2,3,2,0.3333),(2,3,3,1),     (2,3,4,1),     (2,3,5,2),
(2,4,1,0.3333), (2,4,2,0.25),  (2,4,3,1),     (2,4,4,1),     (2,4,5,2),
(2,5,1,0.25),   (2,5,2,0.2),   (2,5,3,0.5),   (2,5,4,0.5),   (2,5,5,1);
