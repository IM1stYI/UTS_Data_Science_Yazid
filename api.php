<?php
/** API JSON untuk dashboard. Semua aksi wajib login + header X-CSRF-Token. */
require __DIR__ . '/lib/auth.php';
ini_set('display_errors', '0');
sesi_mulai();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function keluar($d, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    exit;
}

$user = pengguna_aktif();
if (!$user) keluar(['error' => 'Belum login'], 401);
if (!csrf_cek($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) keluar(['error' => 'Token keamanan tidak valid, muat ulang halaman'], 419);

$info  = require __DIR__ . '/lib/soal_info.php';
$aksi  = $_GET['action'] ?? '';
$soal  = (int)($_GET['soal'] ?? 0);
$body  = json_decode(file_get_contents('php://input') ?: '[]', true) ?: [];
$pdo   = null;
try { $pdo = db(); } catch (Throwable $e) {}

try {
    switch ($aksi) {
        case 'status':
            $tabel = ['pengguna', 'transaksi_mentah', 'mahasiswa', 'dataset_sampel', 'pipeline_run', 'hasil_imputasi', 'hasil_statistik', 'split_run'];
            $jml = [];
            if ($pdo) foreach ($tabel as $t) $jml[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
            keluar(['db' => (bool)$pdo, 'database' => nama_db(), 'tabel' => $jml, 'php' => PHP_VERSION]);

        case 'source':     // kode jawaban (hanya file whitelist)
            if ($soal < 1 || $soal > 5) keluar(['error' => 'Soal tidak valid'], 400);
            keluar(['file' => $info[$soal - 1]['file'], 'kode' => file_get_contents(__DIR__ . '/' . $info[$soal - 1]['file'])]);

        case 'script':     // jalankan skrip asli bagian2-UTS{n}.php
            if ($soal < 1 || $soal > 5) keluar(['error' => 'Soal tidak valid'], 400);
            $path = __DIR__ . '/' . $info[$soal - 1]['file'];
            ob_start();
            (function ($f) { include $f; })($path);
            keluar(['output' => ob_get_clean()]);

        case 'csv':
            keluar(['csv' => file_get_contents(__DIR__ . '/dataset_mahasiswa.csv')]);

        case 'run':
            require __DIR__ . '/lib/analisis.php';
            switch ($soal) {
                case 1:
                    if (!is_array($body['rows'] ?? null)) keluar(['error' => 'Data transaksi tidak valid'], 400);
                    keluar(lib_pipeline(array_slice($body['rows'], 0, 500)));
                case 2:
                    $csv = (string)($body['csv'] ?? '');
                    if (strlen($csv) > 100000) keluar(['error' => 'CSV terlalu besar (maks 100 KB)'], 400);
                    $h = fopen('php://temp', 'r+'); fwrite($h, $csv); rewind($h);
                    $r = lib_eda($h); fclose($h);
                    keluar($r);
                case 3:
                    $p = lib_parse_nilai((string)($body['teks'] ?? ''));
                    keluar(isset($p['error']) ? $p : lib_imputasi($p['data']));
                case 4:
                    keluar(lib_statistik((string)($body['teks'] ?? '')));
                case 5:
                    $ts = max(0.05, min(0.95, (float)($body['test_size'] ?? 0.2)));
                    $seed = ($body['seed'] ?? '') === '' ? null : (int)$body['seed'];
                    keluar(lib_split(lib_dataset_sampel($pdo), $ts, (bool)($body['shuffle'] ?? true), $seed));
            }
            keluar(['error' => 'Soal tidak valid'], 400);

        case 'riwayat':
            if (!in_array($user['role'], ['admin', 'dosen'], true)) keluar(['error' => 'Khusus admin/dosen'], 403);
            if (!$pdo) keluar(['error' => 'Database belum siap'], 503);
            keluar([
                'pipeline' => $pdo->query('SELECT r.id, r.dijalankan_pada, (SELECT jumlah FROM pipeline_tahap WHERE run_id=r.id AND tahap=\'ingestion\') AS diterima, (SELECT jumlah FROM pipeline_tahap WHERE run_id=r.id AND tahap=\'siap_analisis\') AS siap FROM pipeline_run r ORDER BY r.id DESC LIMIT 5')->fetchAll(),
                'statistik' => $pdo->query('SELECT id, dijalankan_pada, n, mean, median, modus, std_sampel FROM hasil_statistik ORDER BY id DESC LIMIT 5')->fetchAll(),
                'split' => $pdo->query('SELECT id, dijalankan_pada, test_size, jumlah_train, jumlah_test FROM split_run ORDER BY id DESC LIMIT 5')->fetchAll(),
                'mahasiswa' => $pdo->query('SELECT nim, nama, nilai_tugas, nilai_ujian FROM mahasiswa ORDER BY nim')->fetchAll(),
                'pengguna' => $pdo->query('SELECT username, nama, role, terakhir_login FROM pengguna ORDER BY id')->fetchAll(),
            ]);
    }
    keluar(['error' => 'Aksi tidak dikenal'], 400);
} catch (Throwable $e) {
    keluar(['error' => 'Kesalahan server: ' . $e->getMessage()], 500);
}
