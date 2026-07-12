# SPK Seleksi Karyawan — AHP-SAW (GDSS)

Aplikasi Sistem Pendukung Keputusan Kelompok untuk seleksi karyawan baru,
studi kasus **UD Bali Trip Rental**. Kombinasi metode **AHP** (pembobotan
kriteria dari beberapa pengambil keputusan) dan **SAW** (perankingan kandidat).

## Cara Menjalankan (XAMPP / Laragon)

1. Salin folder `spk-app` ke `htdocs/` (XAMPP) atau `www/` (Laragon).
2. Buka phpMyAdmin, buat database baru lalu import `database/schema.sql`
   (skema sekaligus data studi kasus 5 kandidat, 5 kriteria, 2 DM sudah termasuk).
3. Sesuaikan kredensial di `config/database.php` jika berbeda dari default XAMPP.
4. Akses `http://localhost/spk-app/` di browser.

## Alur Penggunaan

1. **Kriteria** — tinjau daftar kriteria & jenis atribut (benefit/cost).
2. **Kandidat** — tinjau/tambah data kandidat dan nilai mentahnya.
3. **Pembobotan AHP** — untuk tiap DM, isi matriks perbandingan berpasangan,
   simpan, lalu klik "Hitung & Agregasi Bobot Semua DM" untuk mendapatkan
   bobot akhir + uji konsistensi (CR).
4. **Hasil & Ranking** — sistem otomatis menjalankan SAW dan menampilkan
   ranking akhir kandidat.

## Struktur Folder

```
spk-app/
├── config/database.php      # koneksi database
├── database/schema.sql      # skema + data studi kasus (seed)
├── includes/
│   ├── functions.php        # logika inti AHP & SAW
│   ├── header.php / footer.php
├── assets/css/style.css
├── index.php                # dashboard
├── kriteria.php             # CRUD kriteria
├── kandidat.php             # CRUD kandidat & nilai
├── ahp.php                  # input pairwise + hitung bobot
└── hasil.php                # perhitungan SAW + ranking akhir
```

## Verifikasi terhadap Perhitungan Manual

Data seed pada `schema.sql` sudah disesuaikan dengan contoh perhitungan
manual pada laporan (Bab 3.5). Hasil akhir yang seharusnya muncul:

| Peringkat | Kandidat | Nilai Preferensi (V) |
|---|---|---|
| 1 | Kandidat B | 0.87584 |
| 2 | Kandidat D | 0.83374 |
| 3 | Kandidat A | 0.81575 |
| 4 | Kandidat C | 0.78224 |
| 5 | Kandidat E | 0.63245 |

Bobot kriteria hasil agregasi AHP (CR = 0.0125, konsisten):

| Kriteria | Bobot |
|---|---|
| C1 Pengalaman Kerja | 0.2730 |
| C2 Nilai Wawancara | 0.4528 |
| C3 Kemampuan Bahasa Asing | 0.1228 |
| C4 Fleksibilitas Jadwal | 0.0854 |
| C5 Ekspektasi Gaji | 0.0660 |
