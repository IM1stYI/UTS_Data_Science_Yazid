<?php
require_once __DIR__ . '/koneksi.php';
$pdo = koneksi_aman();

// Data awal berisi nilai ujian numerik dengan missing values
$data = [80, null, 75, "", 90, null, 65, 85, 100];

echo "=== 1. Data Awal ===\n";
echo "[" . implode(", ", array_map(function($v) { return $v === null ? 'null' : ($v === "" ? '""' : $v); }, $data)) . "]\n\n";

// ---------------------------------------------------------
// Tahap 1: Menghitung nilai rata-rata (mean) dari data valid
// ---------------------------------------------------------
$valid_values = [];
foreach ($data as $value) {
    if ($value !== null && $value !== "") {
        $valid_values[] = $value;
    }
}

$jumlah_valid = count($valid_values);
$total_nilai = array_sum($valid_values);
$mean = $jumlah_valid > 0 ? $total_nilai / $jumlah_valid : 0;

echo "=== 2. Perhitungan Rata-rata (Mean) ===\n";
echo "Total nilai valid : $total_nilai\n";
echo "Jumlah data valid : $jumlah_valid\n";
echo "Nilai Rata-rata   : $mean\n\n";

// ---------------------------------------------------------
// Tahap 2: Penanganan Missing Values (Imputasi)
// ---------------------------------------------------------
// Mengisi nilai yang kosong dengan pendekatan statistik (mean)
$imputed_data = [];
foreach ($data as $value) {
    if ($value === null || $value === "") {
        $imputed_data[] = $mean;
    } else {
        $imputed_data[] = $value;
    }
}

echo "=== 3. Hasil Imputasi Data ===\n";
echo "[" . implode(", ", $imputed_data) . "]\n\n";

// ---------------------------------------------------------
// Tahap 3: Transformasi Normalisasi Min-Max
// ---------------------------------------------------------
// Rumus Min-Max: (X - Min) / (Max - Min)
$min_val = min($imputed_data);
$max_val = max($imputed_data);
$range = $max_val - $min_val;

$normalized_data = [];
foreach ($imputed_data as $value) {
    if ($range == 0) {
        $normalized_data[] = 0; // Menghindari pembagian dengan nol
    } else {
        $norm = ($value - $min_val) / $range;
        // Membulatkan hasil ke 4 angka di belakang koma untuk kerapian
        $normalized_data[] = round($norm, 4); 
    }
}

echo "=== 4. Hasil Normalisasi Min-Max (Rentang 0 hingga 1) ===\n";
echo "Nilai Minimum : $min_val\n";
echo "Nilai Maksimum: $max_val\n";
echo "Data Normalisasi:\n";
echo "[" . implode(", ", $normalized_data) . "]\n";

// ---------------------------------------------------------
// Simpan ke database (tabel hasil_imputasi)
// ---------------------------------------------------------
if ($pdo) {
    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM hasil_imputasi');   // hasil terbaru menggantikan yang lama
    $st = $pdo->prepare('INSERT INTO hasil_imputasi (urutan, nilai_awal, nilai_imputasi, nilai_normalisasi) VALUES (?,?,?,?)');
    foreach ($data as $i => $awal) {
        $kosong = ($awal === null || $awal === "");
        $st->execute([$i + 1, $kosong ? null : $awal, $imputed_data[$i], $normalized_data[$i]]);
    }
    $pdo->commit();
    echo "\n[DB] " . count($data) . " baris hasil disimpan ke tabel hasil_imputasi\n";
}
