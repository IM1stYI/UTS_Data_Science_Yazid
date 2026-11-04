<?php
/**
 * SOAL 1 - Simulasi Siklus Hidup Data Sains (Data Pipeline Sederhana)
 * PHP Native, berbasis array asosiatif.
 *
 * Tahapan pipeline:
 *   1. INGESTION    : data mentah transaksi diterima
 *   2. VALID        : validasi struktur & isi setiap baris data
 *   3. DIPROSES     : transaksi valid disaring & dibersihkan (trim, cast tipe)
 *   4. SIAP ANALISIS: dataset final siap dipakai untuk analisis
 */

require_once __DIR__ . '/koneksi.php';
$pdo = koneksi_aman();

// ---- Data mentah transaksi penjualan (cadangan jika database tidak tersedia) ----
$raw_transactions = [
    ["id" => "TX001", "amount" => 50000,  "status" => "success", "date" => "2026-10-21"],
    ["id" => "TX002", "amount" => -10000, "status" => "success", "date" => "2026-10-21"], // amount negatif
    ["id" => "TX003", "amount" => 150000, "status" => "pending", "date" => "2026-10-21"],
    ["id" => "TX004", "amount" => null,   "status" => "success", "date" => "2026-10-21"], // amount kosong
    ["id" => "TX005", "amount" => 75000,  "status" => "success", "date" => "2026-10-22"],
    ["id" => "TX006", "amount" => 20000,  "status" => "success"],                          // kolom date hilang
    ["id" => "TX007", "amount" => "abc",  "status" => "failed",  "date" => "2026-10-22"], // amount bukan angka
    ["id" => "",      "amount" => 30000,  "status" => "success", "date" => "2026-10-23"], // id kosong
];

/**
 * Tahap VALID: validasi struktur baris.
 * Mengembalikan array alasan kesalahan (kosong = baris valid).
 */
function validate_row(array $row): array
{
    $errors = [];
    $required = ['id', 'amount', 'status', 'date'];

    // a. Validasi struktur: semua kunci wajib harus ada
    foreach ($required as $key) {
        if (!array_key_exists($key, $row)) {
            $errors[] = "kolom '$key' tidak ada";
        }
    }
    if ($errors) {
        return $errors; // struktur rusak, hentikan pengecekan isi
    }

    // b. Validasi isi
    if (trim((string)$row['id']) === '') {
        $errors[] = "id kosong";
    }
    if ($row['amount'] === null || $row['amount'] === '' || !is_numeric($row['amount'])) {
        $errors[] = "amount kosong / bukan angka";
    } elseif ($row['amount'] <= 0) {
        $errors[] = "amount harus > 0";
    }
    if (!in_array($row['status'], ['success', 'pending', 'failed'], true)) {
        $errors[] = "status tidak dikenal";
    }
    $d = DateTime::createFromFormat('Y-m-d', (string)$row['date']);
    if (!$d || $d->format('Y-m-d') !== $row['date']) {
        $errors[] = "format date tidak valid (Y-m-d)";
    }
    return $errors;
}

/**
 * Pipeline utama: menerima data mentah, mengembalikan laporan tiap tahapan.
 */
function run_pipeline(array $raw): array
{
    $report = [
        'tahapan' => [
            'ingestion'     => ['status' => 'selesai', 'jumlah' => count($raw)],
            'valid'         => ['status' => 'menunggu', 'jumlah' => 0],
            'diproses'      => ['status' => 'menunggu', 'jumlah' => 0],
            'siap_analisis' => ['status' => 'menunggu', 'jumlah' => 0],
        ],
        'ditolak'       => [],   // baris tidak valid beserta alasannya
        'data_final'    => [],
    ];

    // ---- Tahap 2: VALID (validasi struktur & penyaringan) ----
    $valid_rows = [];
    foreach ($raw as $i => $row) {
        $errors = validate_row($row);
        if (empty($errors)) {
            $valid_rows[] = $row;
        } else {
            $report['ditolak'][] = [
                'baris'  => $i + 1,
                'id'     => $row['id'] ?? '(tanpa id)',
                'alasan' => implode('; ', $errors),
            ];
        }
    }
    $report['tahapan']['valid'] = ['status' => 'selesai', 'jumlah' => count($valid_rows)];

    // ---- Tahap 3: DIPROSES (pembersihan & penyeragaman tipe data) ----
    $processed = [];
    foreach ($valid_rows as $row) {
        $processed[] = [
            'id'     => strtoupper(trim($row['id'])),
            'amount' => (float)$row['amount'],
            'status' => $row['status'],
            'date'   => $row['date'],
        ];
    }
    $report['tahapan']['diproses'] = ['status' => 'selesai', 'jumlah' => count($processed)];

    // ---- Tahap 4: SIAP ANALISIS ----
    $report['data_final'] = $processed;
    $report['tahapan']['siap_analisis'] = [
        'status' => count($processed) > 0 ? 'selesai' : 'kosong',
        'jumlah' => count($processed),
    ];

    return $report;
}

/** Memuat data mentah dari tabel transaksi_mentah (payload JSON). */
function muat_mentah(PDO $pdo): array
{
    $rows = [];
    foreach ($pdo->query('SELECT payload FROM transaksi_mentah ORDER BY id') as $r) {
        $rows[] = json_decode($r['payload'], true);
    }
    return $rows;
}

/** Menyimpan hasil pipeline (run, status tahapan, data bersih, data ditolak). */
function simpan_hasil(PDO $pdo, array $hasil): int
{
    $pdo->beginTransaction();
    try {
        $pdo->exec(sql_insert_default('pipeline_run'));
        $run = (int)$pdo->lastInsertId();

        $st = $pdo->prepare('INSERT INTO pipeline_tahap (run_id, tahap, status, jumlah) VALUES (?,?,?,?)');
        foreach ($hasil['tahapan'] as $nama => $i) $st->execute([$run, $nama, $i['status'], $i['jumlah']]);

        $st = $pdo->prepare('INSERT INTO transaksi_bersih (run_id, kode, amount, status, tanggal) VALUES (?,?,?,?,?)');
        foreach ($hasil['data_final'] as $r) $st->execute([$run, $r['id'], $r['amount'], $r['status'], $r['date']]);

        $st = $pdo->prepare('INSERT INTO transaksi_ditolak (run_id, baris, kode, alasan) VALUES (?,?,?,?)');
        foreach ($hasil['ditolak'] as $d) $st->execute([$run, $d['baris'], $d['id'], $d['alasan']]);

        $pdo->commit();
        return $run;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ---- Eksekusi & laporan ----
if ($pdo) {
    $dari_db = muat_mentah($pdo);
    if ($dari_db) {
        $raw_transactions = $dari_db;
        echo "[DB] " . count($dari_db) . " transaksi mentah dimuat dari tabel transaksi_mentah\n\n";
    }
}
$hasil = run_pipeline($raw_transactions);

echo "=== LAPORAN STATUS TAHAPAN PIPELINE ===\n";
foreach ($hasil['tahapan'] as $nama => $info) {
    printf("%-15s : %-8s (%d baris)\n", strtoupper($nama), $info['status'], $info['jumlah']);
}

echo "\n=== TRANSAKSI DITOLAK ===\n";
foreach ($hasil['ditolak'] as $d) {
    printf("Baris %d [%s] -> %s\n", $d['baris'], $d['id'], $d['alasan']);
}

echo "\n=== DATA SIAP ANALISIS ===\n";
printf("%-8s %-12s %-10s %-12s\n", 'ID', 'Amount', 'Status', 'Tanggal');
foreach ($hasil['data_final'] as $r) {
    printf("%-8s %-12s %-10s %-12s\n", $r['id'], number_format($r['amount'], 0, ',', '.'), $r['status'], $r['date']);
}

if ($pdo) {
    $run_id = simpan_hasil($pdo, $hasil);
    echo "\n[DB] Hasil pipeline disimpan (pipeline_run.id = $run_id)\n";
}
