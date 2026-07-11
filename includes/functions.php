<?php
/**
 * Fungsi inti perhitungan AHP (pembobotan kriteria, multi-DM/GDSS)
 * dan SAW (perankingan alternatif/kandidat).
 */

// Random Index (Saaty) untuk uji konsistensi AHP
const RI_TABLE = [1 => 0, 2 => 0, 3 => 0.58, 4 => 0.9, 5 => 1.12, 6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49];

/**
 * Ambil matriks pairwise satu DM dari DB dalam bentuk array 2D (n x n)
 */
function getPairwiseMatrix(mysqli $conn, int $dmId, int $n): array
{
    $matrix = array_fill(0, $n, array_fill(0, $n, 1.0));
    $stmt = $conn->prepare("SELECT kriteria_i, kriteria_j, nilai FROM ahp_pairwise WHERE dm_id = ?");
    $stmt->bind_param('i', $dmId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // kriteria_i/j di DB adalah id kriteria (1..n) -> index array (0..n-1)
        $i = (int)$row['kriteria_i'] - 1;
        $j = (int)$row['kriteria_j'] - 1;
        $matrix[$i][$j] = (float)$row['nilai'];
    }
    return $matrix;
}

/**
 * Agregasi matriks pairwise dari beberapa DM menggunakan Geometric Mean
 * (Aggregation of Individual Judgments - AIJ), lazim dipakai pada GDSS-AHP.
 */
function aggregateMatrices(array $matrices): array
{
    $n = count($matrices[0]);
    $m = count($matrices);
    $agg = array_fill(0, $n, array_fill(0, $n, 0.0));
    for ($i = 0; $i < $n; $i++) {
        for ($j = 0; $j < $n; $j++) {
            $product = 1.0;
            foreach ($matrices as $mat) {
                $product *= $mat[$i][$j];
            }
            $agg[$i][$j] = $product ** (1 / $m);
        }
    }
    return $agg;
}

/**
 * Hitung bobot AHP (eigenvector approximation: normalisasi kolom lalu rata-rata baris)
 * beserta Consistency Ratio (CR).
 *
 * @return array{weights: array, lambdaMax: float, CI: float, CR: float, consistent: bool}
 */
function computeAHPWeights(array $matrix): array
{
    $n = count($matrix);

    // 1. Jumlah tiap kolom
    $colSum = array_fill(0, $n, 0.0);
    for ($j = 0; $j < $n; $j++) {
        for ($i = 0; $i < $n; $i++) {
            $colSum[$j] += $matrix[$i][$j];
        }
    }

    // 2. Normalisasi matriks & rata-rata tiap baris -> bobot
    $weights = array_fill(0, $n, 0.0);
    for ($i = 0; $i < $n; $i++) {
        $rowSum = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $rowSum += $matrix[$i][$j] / $colSum[$j];
        }
        $weights[$i] = $rowSum / $n;
    }

    // 3. Hitung lambda max
    $lambdaEach = [];
    for ($i = 0; $i < $n; $i++) {
        $weightedSum = 0.0;
        for ($j = 0; $j < $n; $j++) {
            $weightedSum += $matrix[$i][$j] * $weights[$j];
        }
        $lambdaEach[] = $weightedSum / $weights[$i];
    }
    $lambdaMax = array_sum($lambdaEach) / $n;

    // 4. Consistency Index & Consistency Ratio
    $CI = ($lambdaMax - $n) / ($n - 1);
    $RI = RI_TABLE[$n] ?? 1.49;
    $CR = $RI > 0 ? $CI / $RI : 0;

    return [
        'weights'    => $weights,
        'lambdaMax'  => $lambdaMax,
        'CI'         => $CI,
        'CR'         => $CR,
        'consistent' => $CR <= 0.1,
    ];
}

/**
 * Hitung SAW (Simple Additive Weighting):
 * - Normalisasi: benefit -> x/max, cost -> min/x
 * - Nilai preferensi Vi = sum(w_j * r_ij)
 *
 * @param array $decisionMatrix [kandidat][kriteria] = nilai mentah
 * @param array $weights bobot kriteria hasil AHP (urutan sama dengan kolom decisionMatrix)
 * @param array $types 'benefit' atau 'cost' per kriteria (urutan sama)
 * @return array{normalized: array, scores: array}
 */
function computeSAW(array $decisionMatrix, array $weights, array $types): array
{
    $altCount = count($decisionMatrix);
    $critCount = count($weights);

    // cari max/min tiap kolom
    $colMax = array_fill(0, $critCount, -INF);
    $colMin = array_fill(0, $critCount, INF);
    foreach ($decisionMatrix as $row) {
        for ($j = 0; $j < $critCount; $j++) {
            $colMax[$j] = max($colMax[$j], $row[$j]);
            $colMin[$j] = min($colMin[$j], $row[$j]);
        }
    }

    $normalized = [];
    $scores = [];
    foreach ($decisionMatrix as $idx => $row) {
        $normRow = [];
        $v = 0.0;
        for ($j = 0; $j < $critCount; $j++) {
            if ($types[$j] === 'benefit') {
                $r = $colMax[$j] == 0 ? 0 : $row[$j] / $colMax[$j];
            } else {
                $r = $row[$j] == 0 ? 0 : $colMin[$j] / $row[$j];
            }
            $normRow[$j] = $r;
            $v += $weights[$j] * $r;
        }
        $normalized[$idx] = $normRow;
        $scores[$idx] = $v;
    }

    return ['normalized' => $normalized, 'scores' => $scores];
}

/**
 * Urutkan skor SAW menjadi ranking (1 = terbaik)
 */
function rankScores(array $scores): array
{
    $ranked = $scores;
    arsort($ranked); // urutkan skor tertinggi ke terendah, key tetap
    $rank = 1;
    $result = [];
    foreach ($ranked as $idx => $score) {
        $result[$idx] = ['score' => $score, 'rank' => $rank];
        $rank++;
    }
    return $result;
}
