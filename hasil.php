<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$conn = getConnection();

$kriteriaList = $conn->query("SELECT * FROM kriteria ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$kandidatList = $conn->query("SELECT * FROM kandidat ORDER BY id")->fetch_all(MYSQLI_ASSOC);

$weights = array_column($kriteriaList, 'bobot');
$types = array_column($kriteriaList, 'tipe');

if (in_array(null, $weights, true)) {
    require 'includes/header.php';
    echo '<div class="alert alert-warning">Bobot kriteria belum dihitung. Silakan lengkapi <a href="ahp.php">Pembobotan AHP</a> terlebih dahulu.</div>';
    require 'includes/footer.php';
    exit;
}

$weights = array_map('floatval', $weights);

// susun decision matrix [kandidat][kriteria]
$nilaiRes = $conn->query("SELECT * FROM nilai_kandidat");
$nilaiMap = [];
while ($n = $nilaiRes->fetch_assoc()) {
    $nilaiMap[$n['kandidat_id']][$n['kriteria_id']] = (float)$n['nilai'];
}

$decisionMatrix = [];
foreach ($kandidatList as $kd) {
    $row = [];
    foreach ($kriteriaList as $k) {
        $row[] = $nilaiMap[$kd['id']][$k['id']] ?? 0;
    }
    $decisionMatrix[] = $row;
}

$sawResult = computeSAW($decisionMatrix, $weights, $types);
$ranking = rankScores($sawResult['scores']);

// simpan ke hasil_akhir (audit trail)
$conn->query("DELETE FROM hasil_akhir");
foreach ($ranking as $idx => $r) {
    $kandidatId = $kandidatList[$idx]['id'];
    $stmt = $conn->prepare("INSERT INTO hasil_akhir (kandidat_id, nilai_preferensi, ranking) VALUES (?, ?, ?)");
    $stmt->bind_param('idi', $kandidatId, $r['score'], $r['rank']);
    $stmt->execute();
}

require 'includes/header.php';
?>
<h1>Hasil Perhitungan &amp; Ranking Akhir</h1>
<p class="lead">Perankingan kandidat dihitung menggunakan metode SAW dengan bobot kriteria hasil
agregasi AHP dari seluruh pengambil keputusan.</p>

<h2>1. Bobot Kriteria yang Digunakan</h2>
<table class="data-table">
    <thead><tr><th>Kriteria</th><th>Jenis</th><th>Bobot</th></tr></thead>
    <tbody>
    <?php foreach ($kriteriaList as $idx => $k): ?>
        <tr>
            <td><?= htmlspecialchars($k['nama']) ?></td>
            <td><?= $k['tipe'] ?></td>
            <td><?= number_format($weights[$idx], 4) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<h2>2. Matriks Ternormalisasi (SAW)</h2>
<div class="table-scroll">
<table class="data-table">
    <thead>
        <tr><th>Kandidat</th><?php foreach ($kriteriaList as $k): ?><th><?= htmlspecialchars($k['kode']) ?></th><?php endforeach; ?></tr>
    </thead>
    <tbody>
    <?php foreach ($kandidatList as $idx => $kd): ?>
        <tr>
            <td><?= htmlspecialchars($kd['nama']) ?></td>
            <?php foreach ($sawResult['normalized'][$idx] as $val): ?>
                <td><?= number_format($val, 4) ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<h2>3. Ranking Akhir</h2>
<table class="data-table ranking-table">
    <thead><tr><th>Peringkat</th><th>Kandidat</th><th>Nilai Preferensi (V)</th></tr></thead>
    <tbody>
    <?php foreach ($ranking as $idx => $r): ?>
        <tr class="<?= $r['rank'] == 1 ? 'row-winner' : '' ?>">
            <td><?= $r['rank'] ?><?= $r['rank'] == 1 ? ' 🏆' : '' ?></td>
            <td><?= htmlspecialchars($kandidatList[$idx]['nama']) ?></td>
            <td><?= number_format($r['score'], 5) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="alert alert-info">
    <strong>Rekomendasi Sistem:</strong>
    <?php $winnerIdx = array_key_first($ranking); ?>
    <?= htmlspecialchars($kandidatList[$winnerIdx]['nama']) ?> direkomendasikan sebagai kandidat
    terbaik untuk posisi <?= htmlspecialchars($kandidatList[$winnerIdx]['posisi_dilamar'] ?? '') ?>
    dengan nilai preferensi tertinggi (<?= number_format($ranking[$winnerIdx]['score'], 5) ?>).
</div>

<?php require 'includes/footer.php'; ?>
