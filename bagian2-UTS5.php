<?php
/**
 * SOAL 5 - Pembagian Data Latih dan Data Uji (Train-Test Split Algorithm)
 * Pengacakan memakai algoritma Fisher-Yates pada ARRAY INDEKS (manual),
 * sehingga tidak ada duplikasi data antar subset.
 */

require_once __DIR__ . '/koneksi.php';
$pdo = koneksi_aman();

// ---- Dataset array 2 dimensi, 50 sampel ----
$dataset = [];
if ($pdo) {   // dimuat dari tabel dataset_sampel (diisi oleh setup.php)
    $dataset = $pdo->query('SELECT id, fitur_x, fitur_y, label FROM dataset_sampel ORDER BY id')->fetchAll();
    if ($dataset) echo "[DB] " . count($dataset) . " sampel dimuat dari tabel dataset_sampel\n\n";
}
if (!$dataset) {   // cadangan jika database tidak tersedia
    for ($i = 1; $i <= 50; $i++) {
        $dataset[] = [
            'id'      => $i,
            'fitur_x' => mt_rand(10, 100),
            'fitur_y' => mt_rand(100, 500),
            'label'   => ($i % 2 == 0) ? 'Positif' : 'Negatif',
        ];
    }
}

/** Fisher-Yates shuffle pada array indeks */
function acak_indeks(array $indeks): array
{
    for ($i = count($indeks) - 1; $i > 0; $i--) {
        $j = mt_rand(0, $i);
        [$indeks[$i], $indeks[$j]] = [$indeks[$j], $indeks[$i]];
    }
    return $indeks;
}

/**
 * @param array $data      dataset 2 dimensi
 * @param float $test_size proporsi data uji (default 0.2 -> 20%)
 * @param bool  $shuffle   acak indeks sebelum dibagi
 * @return array ['train' => [...], 'test' => [...]]
 */
function train_test_split(array $data, float $test_size = 0.2, bool $shuffle = true): array
{
    $n = count($data);
    $jumlah_test = (int) round($n * $test_size);

    $indeks = range(0, $n - 1);
    if ($shuffle) {
        $indeks = acak_indeks($indeks);
    }

    // Satu daftar indeks dipotong dua -> tidak mungkin ada duplikasi
    $idx_test  = array_slice($indeks, 0, $jumlah_test);
    $idx_train = array_slice($indeks, $jumlah_test);

    $train = [];
    $test  = [];
    foreach ($idx_train as $i) $train[] = $data[$i];
    foreach ($idx_test  as $i) $test[]  = $data[$i];

    return ['train' => $train, 'test' => $test];
}

// ---- Eksekusi ----
$hasil = train_test_split($dataset, 0.2, true);
$train = $hasil['train'];
$test  = $hasil['test'];

echo "=== HASIL TRAIN-TEST SPLIT ===\n";
printf("Total data      : %d baris\n", count($dataset));
printf("Data Latih (80%%): %d baris\n", count($train));
printf("Data Uji   (20%%): %d baris\n", count($test));

// ---- Verifikasi tidak ada duplikasi / data hilang ----
$id_train = array_column($train, 'id');
$id_test  = array_column($test, 'id');
$irisan   = array_intersect($id_train, $id_test);
$lengkap  = (count($id_train) + count($id_test)) === count($dataset);

echo "\n=== VERIFIKASI ===\n";
echo "Irisan train & test : " . (count($irisan) === 0 ? "kosong (tidak ada duplikasi)" : "ADA DUPLIKASI!") . "\n";
echo "Total train + test  : " . (count($id_train) + count($id_test)) . " (" . ($lengkap ? "lengkap" : "tidak lengkap") . ")\n";

echo "\nID Data Uji   : " . implode(', ', $id_test) . "\n";

// ---- Simpan hasil split ke database ----
if ($pdo) {
    $pdo->beginTransaction();
    $pdo->prepare('INSERT INTO split_run (test_size, jumlah_train, jumlah_test) VALUES (?,?,?)')
        ->execute([0.2, count($train), count($test)]);
    $split_id = (int)$pdo->lastInsertId();
    $st = $pdo->prepare('INSERT INTO split_detail (split_id, sampel_id, subset) VALUES (?,?,?)');
    foreach ($train as $r) $st->execute([$split_id, $r['id'], 'train']);
    foreach ($test  as $r) $st->execute([$split_id, $r['id'], 'test']);
    $pdo->commit();
    echo "\n[DB] Hasil split disimpan (split_run.id = $split_id)\n";
}
