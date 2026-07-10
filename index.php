<?php
require_once 'config/database.php';
$conn = getConnection();

$jmlKriteria = $conn->query("SELECT COUNT(*) c FROM kriteria")->fetch_assoc()['c'];
$jmlKandidat = $conn->query("SELECT COUNT(*) c FROM kandidat")->fetch_assoc()['c'];
$jmlDM = $conn->query("SELECT COUNT(*) c FROM dm")->fetch_assoc()['c'];
$sudahDihitung = $conn->query("SELECT COUNT(*) c FROM hasil_akhir")->fetch_assoc()['c'] > 0;

require 'includes/header.php';
?>
<h1>Dashboard</h1>
<p class="lead">Sistem Pendukung Keputusan Kelompok (Group Decision Support System) untuk membantu proses
seleksi karyawan baru pada UD Bali Trip Rental secara objektif dan terdokumentasi, menggunakan
kombinasi metode <strong>AHP</strong> (pembobotan kriteria dari beberapa pengambil keputusan) dan
<strong>SAW</strong> (perankingan kandidat).</p>

<div class="card-grid">
    <div class="stat-card">
        <span class="stat-number"><?= $jmlDM ?></span>
        <span class="stat-label">Pengambil Keputusan (DM)</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $jmlKriteria ?></span>
        <span class="stat-label">Kriteria Penilaian</span>
    </div>
    <div class="stat-card">
        <span class="stat-number"><?= $jmlKandidat ?></span>
        <span class="stat-label">Kandidat Pelamar</span>
    </div>
    <div class="stat-card <?= $sudahDihitung ? 'stat-done' : 'stat-pending' ?>">
        <span class="stat-number"><?= $sudahDihitung ? '✓' : '—' ?></span>
        <span class="stat-label"><?= $sudahDihitung ? 'Perhitungan Selesai' : 'Belum Dihitung' ?></span>
    </div>
</div>

<div class="steps">
    <h2>Alur Penggunaan Sistem</h2>
    <ol>
        <li><strong>Kriteria</strong> — tinjau/atur kriteria penilaian dan jenis atributnya (benefit/cost).</li>
        <li><strong>Kandidat</strong> — kelola data kandidat pelamar dan nilai mentahnya per kriteria.</li>
        <li><strong>Pembobotan AHP</strong> — masukkan matriks perbandingan berpasangan dari tiap DM, sistem
            mengagregasi &amp; menghitung bobot otomatis beserta uji konsistensi (CR).</li>
        <li><strong>Hasil &amp; Ranking</strong> — sistem menjalankan SAW menggunakan bobot AHP dan menampilkan
            perankingan akhir kandidat.</li>
    </ol>
</div>

<?php require 'includes/footer.php'; ?>
