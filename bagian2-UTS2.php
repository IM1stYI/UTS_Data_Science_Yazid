<?php
/**
 * SOAL 2 - Pengambilan & Eksplorasi Awal Dataset (EDA Ekstraksi CSV)
 * Menggunakan fopen() dan fgetcsv().
 */
require_once __DIR__ . '/koneksi.php';
$pdo = koneksi_aman();
$filename = __DIR__ . '/dataset_mahasiswa.csv';

/** Deteksi tipe data satu nilai: integer / float / string / kosong */
function detect_type(string $v): string
{
    $v = trim($v);
    if ($v === '')                          return 'kosong';
    if (preg_match('/^-?\d+$/', $v))        return 'integer';
    if (is_numeric($v))                     return 'float';
    return 'string';
}

if (!file_exists($filename) || ($handle = fopen($filename, 'r')) === false) {
    die("Gagal membuka file dataset_mahasiswa.csv\n");
}

// Baris pertama = nama kolom (hapus BOM jika ada)
$kolom = fgetcsv($handle, 1000, ',', '"', '');
$kolom[0] = preg_replace('/^\xEF\xBB\xBF/', '', $kolom[0]);

$total_baris = 0;
$head        = [];
$tipe        = array_fill_keys($kolom, []);   // kumpulan tipe per kolom
$semua_baris = [];                            // untuk disimpan ke database

while (($row = fgetcsv($handle, 1000, ',', '"', '')) !== false) {
    if ($row === [null]) continue;            // lewati baris kosong
    $total_baris++;
    $semua_baris[] = $row;

    if ($total_baris <= 5) {
        $head[] = $row;                       // 5 baris pertama
    }
    foreach ($kolom as $i => $nama) {
        $tipe[$nama][detect_type($row[$i] ?? '')] = true;
    }
}
fclose($handle);

// ---- Tampilkan hasil ----
echo "=== EDA DATASET: dataset_mahasiswa.csv ===\n\n";

echo "1. Jumlah total baris/rekaman data : $total_baris\n\n";

echo "2. Daftar nama kolom (total " . count($kolom) . "):\n";
foreach ($kolom as $i => $nama) {
    echo "   " . ($i + 1) . ". $nama\n";
}

echo "\n3. Pratinjau 5 baris pertama (head):\n";
echo "   " . implode(' | ', array_map(fn($k) => str_pad($k, 12), $kolom)) . "\n";
echo "   " . str_repeat('-', 12 * count($kolom) + 3 * (count($kolom) - 1)) . "\n";
foreach ($head as $row) {
    echo "   " . implode(' | ', array_map(fn($v) => str_pad($v, 12), $row)) . "\n";
}

echo "\n4. Pengecekan tipe data tiap kolom:\n";
foreach ($tipe as $nama => $daftar) {
    $jenis = array_keys($daftar);
    // Jika campuran integer & float -> anggap float
    if (count(array_diff($jenis, ['integer', 'float'])) === 0 && count($jenis) > 1) {
        $jenis = ['float'];
    }
    printf("   %-14s : %s\n", $nama, implode(', ', $jenis));
}

// ---- Simpan ke database (tabel mahasiswa) - upsert berdasarkan NIM ----
if ($pdo) {
    $upsert = driver() === 'sqlite'
        ? 'ON CONFLICT(nim) DO UPDATE SET nama = excluded.nama, nilai_tugas = excluded.nilai_tugas, nilai_ujian = excluded.nilai_ujian'
        : 'ON DUPLICATE KEY UPDATE nama = VALUES(nama), nilai_tugas = VALUES(nilai_tugas), nilai_ujian = VALUES(nilai_ujian)';
    $st = $pdo->prepare('INSERT INTO mahasiswa (nim, nama, nilai_tugas, nilai_ujian) VALUES (?,?,?,?) ' . $upsert);
    foreach ($semua_baris as $r) {
        $st->execute([$r[0], $r[1], $r[2] === '' ? null : $r[2], $r[3] === '' ? null : $r[3]]);
    }
    $total_db = $pdo->query('SELECT COUNT(*) FROM mahasiswa')->fetchColumn();
    echo "\n[DB] " . count($semua_baris) . " rekaman CSV disimpan ke tabel mahasiswa (total di DB: $total_db)\n";
}
