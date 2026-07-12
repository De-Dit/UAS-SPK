<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
$conn = getConnection();

$kriteriaList = $conn->query("SELECT * FROM kriteria ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$dmList = $conn->query("SELECT * FROM dm ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$n = count($kriteriaList);

// Simpan matriks pairwise dari form (per DM)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_pairwise'])) {
    $dmId = (int)$_POST['dm_id'];
    $conn->query("DELETE FROM ahp_pairwise WHERE dm_id = $dmId");
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $ki = $kriteriaList[$i]['id'];
            $kj = $kriteriaList[$j]['id'];
            if ($i == $j) {
                $val = 1.0;
            } elseif ($i < $j) {
                $val = (float)$_POST["cell_{$i}_{$j}"];
            } else {
                // sel di bawah diagonal = kebalikan (reciprocal) dari sel di atas
                $val = 1 / (float)$_POST["cell_{$j}_{$i}"];
            }
            $stmt = $conn->prepare("INSERT INTO ahp_pairwise (dm_id, kriteria_i, kriteria_j, nilai) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiid', $dmId, $ki, $kj, $val);
            $stmt->execute();
        }
    }
    header('Location: ahp.php?dm=' . $dmId . '&saved=1');
    exit;
}

// Hitung & simpan bobot agregat (dipanggil setelah semua DM mengisi)
$aggregatedResult = null;
$individualResults = [];
if (isset($_GET['hitung'])) {
    $matrices = [];
    foreach ($dmList as $dm) {
        $mat = getPairwiseMatrix($conn, $dm['id'], $n);
        $matrices[] = $mat;
        $individualResults[$dm['id']] = computeAHPWeights($mat);
    }
    $aggMatrix = aggregateMatrices($matrices);
    $aggregatedResult = computeAHPWeights($aggMatrix);
    $aggregatedResult['matrix'] = $aggMatrix;

    // simpan bobot ke tabel kriteria
    foreach ($kriteriaList as $idx => $k) {
        $bobot = $aggregatedResult['weights'][$idx];
        $stmt = $conn->prepare("UPDATE kriteria SET bobot = ? WHERE id = ?");
        $stmt->bind_param('di', $bobot, $k['id']);
        $stmt->execute();
    }
}

$selectedDm = $_GET['dm'] ?? ($dmList[0]['id'] ?? null);
$existingMatrix = $selectedDm ? getPairwiseMatrix($conn, (int)$selectedDm, $n) : null;

require 'includes/header.php';
?>
<h1>Pembobotan Kriteria — AHP (Multi Pengambil Keputusan)</h1>
<p class="lead">Setiap pengambil keputusan (DM) mengisi matriks perbandingan berpasangan (skala Saaty 1–9)
antar kriteria. Sistem akan mengagregasi seluruh matriks menggunakan <em>Geometric Mean</em>
(Aggregation of Individual Judgments) sebelum menghitung bobot akhir dan rasio konsistensi (CR).</p>

<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success">Matriks perbandingan DM berhasil disimpan.</div>
<?php endif; ?>

<div class="tabs">
    <?php foreach ($dmList as $dm): ?>
        <a href="?dm=<?= $dm['id'] ?>" class="tab <?= $selectedDm == $dm['id'] ? 'tab-active' : '' ?>">
            <?= htmlspecialchars($dm['nama']) ?><br><small><?= htmlspecialchars($dm['jabatan']) ?></small>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($selectedDm): ?>
<form method="post">
    <input type="hidden" name="dm_id" value="<?= $selectedDm ?>">
    <div class="table-scroll">
    <table class="matrix-table">
        <thead>
            <tr>
                <th></th>
                <?php foreach ($kriteriaList as $k): ?><th><?= htmlspecialchars($k['kode']) ?></th><?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php for ($i = 0; $i < $n; $i++): ?>
            <tr>
                <th><?= htmlspecialchars($kriteriaList[$i]['kode']) ?></th>
                <?php for ($j = 0; $j < $n; $j++): ?>
                    <td>
                    <?php if ($i == $j): ?>
                        <span class="cell-fixed">1</span>
                    <?php elseif ($i < $j): ?>
                        <input type="number" step="0.0001" min="0.111" max="9" name="cell_<?= $i ?>_<?= $j ?>"
                               value="<?= $existingMatrix ? $existingMatrix[$i][$j] : 1 ?>" required>
                    <?php else: ?>
                        <span class="cell-derived">1/<?= htmlspecialchars($kriteriaList[$j]['kode']) ?></span>
                    <?php endif; ?>
                    </td>
                <?php endfor; ?>
            </tr>
        <?php endfor; ?>
        </tbody>
    </table>
    </div>
    <p class="hint">Isi hanya sel di atas diagonal (perbandingan kepentingan kriteria baris terhadap kolom, skala 1/9–9). Sel di bawah diagonal otomatis dihitung sebagai kebalikannya.</p>
    <button type="submit" name="simpan_pairwise" class="btn-primary">Simpan Matriks DM Ini</button>
</form>
<?php endif; ?>

<hr>
<form method="get">
    <button type="submit" name="hitung" value="1" class="btn-accent">Hitung &amp; Agregasi Bobot Semua DM</button>
</form>

<?php if ($aggregatedResult): ?>
<h2>Hasil Agregasi &amp; Bobot Akhir</h2>

<?php foreach ($individualResults as $dmId => $res): ?>
    <?php $dmName = array_values(array_filter($dmList, fn($d) => $d['id'] == $dmId))[0]['nama']; ?>
    <p><strong><?= htmlspecialchars($dmName) ?></strong> — CR = <?= number_format($res['CR'], 4) ?>
        <?= $res['consistent'] ? '<span class="badge badge-ok">Konsisten</span>' : '<span class="badge badge-danger">Tidak Konsisten, perlu revisi</span>' ?>
    </p>
<?php endforeach; ?>

<table class="data-table">
    <thead><tr><th>Kriteria</th><th>Bobot Agregat</th><th>Persentase</th></tr></thead>
    <tbody>
    <?php foreach ($kriteriaList as $idx => $k): ?>
        <tr>
            <td><?= htmlspecialchars($k['nama']) ?></td>
            <td><?= number_format($aggregatedResult['weights'][$idx], 4) ?></td>
            <td><?= number_format($aggregatedResult['weights'][$idx] * 100, 2) ?>%</td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<p>λmax = <?= number_format($aggregatedResult['lambdaMax'], 4) ?> ·
   CI = <?= number_format($aggregatedResult['CI'], 4) ?> ·
   CR = <?= number_format($aggregatedResult['CR'], 4) ?>
   <?= $aggregatedResult['consistent'] ? '<span class="badge badge-ok">Konsisten (CR ≤ 0.1)</span>' : '<span class="badge badge-danger">Tidak Konsisten</span>' ?>
</p>
<a href="hasil.php" class="btn-primary">Lanjut ke Perhitungan SAW &rarr;</a>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
