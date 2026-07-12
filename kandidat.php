<?php
require_once 'config/database.php';
$conn = getConnection();

$kriteriaList = $conn->query("SELECT * FROM kriteria ORDER BY kode")->fetch_all(MYSQLI_ASSOC);

// Tambah kandidat baru + nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah'])) {
    $kode = trim($_POST['kode']);
    $nama = trim($_POST['nama']);
    $stmt = $conn->prepare("INSERT INTO kandidat (kode, nama) VALUES (?, ?)");
    $stmt->bind_param('ss', $kode, $nama);
    $stmt->execute();
    $kandidatId = $conn->insert_id;

    foreach ($kriteriaList as $k) {
        $nilai = (float)($_POST['nilai_' . $k['id']] ?? 0);
        $stmt2 = $conn->prepare("INSERT INTO nilai_kandidat (kandidat_id, kriteria_id, nilai) VALUES (?, ?, ?)");
        $stmt2->bind_param('iid', $kandidatId, $k['id'], $nilai);
        $stmt2->execute();
    }
    header('Location: kandidat.php');
    exit;
}

if (isset($_GET['hapus'])) {
    $stmt = $conn->prepare("DELETE FROM kandidat WHERE id = ?");
    $stmt->bind_param('i', $_GET['hapus']);
    $stmt->execute();
    header('Location: kandidat.php');
    exit;
}

$kandidatList = $conn->query("SELECT * FROM kandidat ORDER BY kode")->fetch_all(MYSQLI_ASSOC);
$nilaiRes = $conn->query("SELECT * FROM nilai_kandidat");
$nilaiMap = []; // [kandidat_id][kriteria_id] = nilai
while ($n = $nilaiRes->fetch_assoc()) {
    $nilaiMap[$n['kandidat_id']][$n['kriteria_id']] = $n['nilai'];
}

require 'includes/header.php';
?>
<h1>Data Kandidat</h1>
<p class="lead">Data pelamar beserta nilai mentah (belum dinormalisasi) untuk tiap kriteria.</p>

<div class="table-scroll">
<table class="data-table">
    <thead>
        <tr>
            <th>Kode</th>
            <th>Nama</th>
            <?php foreach ($kriteriaList as $k): ?>
                <th><?= htmlspecialchars($k['kode']) ?><br><small><?= htmlspecialchars($k['nama']) ?></small></th>
            <?php endforeach; ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($kandidatList as $kd): ?>
        <tr>
            <td><?= htmlspecialchars($kd['kode']) ?></td>
            <td><?= htmlspecialchars($kd['nama']) ?></td>
            <?php foreach ($kriteriaList as $k): ?>
                <td><?= isset($nilaiMap[$kd['id']][$k['id']]) ? number_format($nilaiMap[$kd['id']][$k['id']], 0) : '-' ?></td>
            <?php endforeach; ?>
            <td><a href="?hapus=<?= $kd['id'] ?>" class="link-danger" onclick="return confirm('Hapus kandidat ini?')">Hapus</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2>Tambah Kandidat</h2>
<form method="post" class="form-grid">
    <label>Kode
        <input type="text" name="kode" placeholder="mis. K6" required maxlength="10">
    </label>
    <label>Nama
        <input type="text" name="nama" placeholder="Nama kandidat" required maxlength="100">
    </label>
    <?php foreach ($kriteriaList as $k): ?>
        <label><?= htmlspecialchars($k['nama']) ?> (<?= $k['tipe'] ?>)
            <input type="number" step="0.01" name="nilai_<?= $k['id'] ?>" required>
        </label>
    <?php endforeach; ?>
    <button type="submit" name="tambah" class="btn-primary">Tambah Kandidat</button>
</form>

<?php require 'includes/footer.php'; ?>
