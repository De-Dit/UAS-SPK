<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$conn = getConnection();

// Tambah kriteria baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $kode = trim($_POST['kode']);
    $nama = trim($_POST['nama']);
    $tipe = $_POST['tipe'];
    $stmt = $conn->prepare("INSERT INTO kriteria (kode, nama, tipe) VALUES (?, ?, ?)");
    $stmt->bind_param('sss', $kode, $nama, $tipe);
    $stmt->execute();
    header('Location: kriteria.php');
    exit;
}

// Hapus kriteria
if (isset($_GET['hapus'])) {
    $stmt = $conn->prepare("DELETE FROM kriteria WHERE id = ?");
    $stmt->bind_param('i', $_GET['hapus']);
    $stmt->execute();
    header('Location: kriteria.php');
    exit;
}

$kriteria = $conn->query("SELECT * FROM kriteria ORDER BY kode");

require 'includes/header.php';
?>
<h1>Kriteria Penilaian</h1>
<p class="lead">Daftar kriteria yang digunakan untuk menilai kandidat. Jenis atribut menentukan arah
normalisasi pada perhitungan SAW: <em>benefit</em> (semakin besar semakin baik) atau
<em>cost</em> (semakin kecil semakin baik).</p>

<table class="data-table">
    <thead>
        <tr><th>Kode</th><th>Nama Kriteria</th><th>Jenis Atribut</th><th>Bobot (hasil AHP)</th><th></th></tr>
    </thead>
    <tbody>
    <?php while ($row = $kriteria->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['kode']) ?></td>
            <td><?= htmlspecialchars($row['nama']) ?></td>
            <td><span class="badge badge-<?= $row['tipe'] ?>"><?= $row['tipe'] === 'benefit' ? 'Benefit' : 'Cost' ?></span></td>
            <td><?= $row['bobot'] !== null ? number_format($row['bobot'], 4) : '<span class="muted">belum dihitung</span>' ?></td>
            <td><a href="?hapus=<?= $row['id'] ?>" class="link-danger" onclick="return confirm('Hapus kriteria ini?')">Hapus</a></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<h2>Tambah Kriteria</h2>
<form method="post" class="form-inline">
    <input type="text" name="kode" placeholder="Kode (mis. C6)" required maxlength="10">
    <input type="text" name="nama" placeholder="Nama kriteria" required maxlength="100">
    <select name="tipe" required>
        <option value="benefit">Benefit</option>
        <option value="cost">Cost</option>
    </select>
    <button type="submit" name="tambah" class="btn-primary">Tambah</button>
</form>

<?php require 'includes/footer.php'; ?>
