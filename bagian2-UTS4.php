<?php
/**
 * SOAL 4 - Kalkulator Statistik Deskriptif & Visualisasi Karakter ASCII
 * Menerima sekumpulan data numerik -> Mean, Median, Modus, Standar Deviasi,
 * dan ASCII Bar Chart frekuensi per rentang nilai.
 */

require_once __DIR__ . '/koneksi.php';
$pdo = koneksi_aman();

function hitung_mean(array $d): float
{
    return array_sum($d) / count($d);
}

function hitung_median(array $d): float
{
    sort($d);
    $n   = count($d);
    $mid = intdiv($n, 2);
    return ($n % 2 === 0) ? ($d[$mid - 1] + $d[$mid]) / 2 : $d[$mid];
}

/** Modus bisa lebih dari satu nilai (multimodal) */
function hitung_modus(array $d): array
{
    $freq = array_count_values(array_map('strval', $d));
    $maks = max($freq);
    if ($maks === 1) return [];               // semua nilai unik -> tidak ada modus
    $modus = [];
    foreach ($freq as $nilai => $f) {
        if ($f === $maks) $modus[] = $nilai + 0;
    }
    sort($modus);
    return $modus;
}

/** Standar deviasi; $sampel=true -> pembagi (n-1), false -> pembagi n */
function hitung_std(array $d, bool $sampel = true): float
{
    $n = count($d);
    if ($sampel && $n < 2) return 0.0;
    $mean = hitung_mean($d);
    $jk = 0.0;
    foreach ($d as $x) $jk += ($x - $mean) ** 2;
    return sqrt($jk / ($sampel ? $n - 1 : $n));
}

/** Frekuensi per rentang puluhan */
function hitung_frekuensi(array $d): array
{
    $bins = ['< 60' => 0, '60-69' => 0, '70-79' => 0, '80-89' => 0, '90-100' => 0];
    foreach ($d as $x) {
        if     ($x < 60)  $bins['< 60']++;
        elseif ($x < 70)  $bins['60-69']++;
        elseif ($x < 80)  $bins['70-79']++;
        elseif ($x < 90)  $bins['80-89']++;
        else              $bins['90-100']++;
    }
    return $bins;
}

function kalkulator_statistik(array $data, ?PDO $pdo = null): void
{
    // Validasi: hanya data numerik
    $data = array_values(array_filter($data, 'is_numeric'));
    if (count($data) === 0) {
        echo "Data kosong / tidak numerik.\n";
        return;
    }

    $modus = hitung_modus($data);

    echo "=== DATASET ===\n[" . implode(', ', $data) . "]\n\n";
    echo "=== STATISTIK DESKRIPTIF ===\n";
    printf("Jumlah data (n)           : %d\n", count($data));
    printf("Mean                      : %.2f\n", hitung_mean($data));
    printf("Median                    : %.2f\n", hitung_median($data));
    printf("Modus                     : %s\n", $modus ? implode(', ', $modus) : 'tidak ada');
    printf("Standar Deviasi (sampel)  : %.2f\n", hitung_std($data, true));
    printf("Standar Deviasi (populasi): %.2f\n\n", hitung_std($data, false));

    echo "=== ASCII BAR CHART (Frekuensi per Rentang Nilai) ===\n";
    foreach (hitung_frekuensi($data) as $rentang => $f) {
        if ($rentang === '< 60' && $f === 0) continue;   // sembunyikan jika kosong
        printf("%-7s | %-12s %d\n", $rentang, str_repeat('#', $f), $f);
    }

    // ---- Simpan ke database ----
    if ($pdo) {
        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO hasil_statistik (n, mean, median, modus, std_sampel, std_populasi) VALUES (?,?,?,?,?,?)')
            ->execute([
                count($data), hitung_mean($data), hitung_median($data),
                $modus ? implode(', ', $modus) : null,
                hitung_std($data, true), hitung_std($data, false),
            ]);
        $stat_id = (int)$pdo->lastInsertId();
        $st = $pdo->prepare('INSERT INTO hasil_frekuensi (stat_id, rentang, frekuensi) VALUES (?,?,?)');
        foreach (hitung_frekuensi($data) as $rentang => $f) $st->execute([$stat_id, $rentang, $f]);
        $pdo->commit();
        echo "\n[DB] Hasil disimpan (hasil_statistik.id = $stat_id)\n";
    }
}

// ---- Eksekusi ----
$data = [65, 72, 85, 92, 95, 88, 76, 60, 68, 74, 82, 89, 91, 100, 75, 85, 85, 90, 62, 78];
kalkulator_statistik($data, $pdo);
