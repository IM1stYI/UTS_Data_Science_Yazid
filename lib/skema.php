<?php
/** Pembuatan tabel + data awal (idempoten). Dipakai otomatis oleh koneksi() dan oleh setup.php. */

function siapkan_database(PDO $pdo, ?callable $say = null): void
{
    $say ??= static function (string $m): void {};
    $sqlite = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite');

    // 1. Tabel
    $file = __DIR__ . '/../' . ($sqlite ? 'schema.sqlite.sql' : 'schema.sql');
    $sql  = preg_replace('/^\s*--.*$/m', '', file_get_contents($file));
    $n = 0;
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if (preg_match('/^(CREATE DATABASE|USE)\b/i', $stmt)) continue;
        $pdo->exec($stmt);
        $n++;
    }
    $say("[OK] $n perintah skema dijalankan");

    // 2. Akun dummy
    $dummy = [
        ['admin',     'Administrator (Dummy)',  'admin',     'admin123'],
        ['dosen',     'Dosen Pengampu (Dummy)', 'dosen',     'dosen123'],
        ['mahasiswa', 'Mahasiswa (Dummy)',      'mahasiswa', 'mahasiswa123'],
    ];
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pengguna WHERE username = ?');
    $ins = $pdo->prepare('INSERT INTO pengguna (username, nama, role, password_hash, is_demo) VALUES (?,?,?,?,1)');
    foreach ($dummy as [$u, $nama, $role, $pw]) {
        $cek->execute([$u]);
        if ((int)$cek->fetchColumn() === 0) { $ins->execute([$u, $nama, $role, password_hash($pw, PASSWORD_DEFAULT)]); $say("[OK] Akun dummy '$u' dibuat"); }
        else $say("[--] Akun '$u' sudah ada, dilewati");
    }

    // 3. Data transaksi mentah (Soal 1)
    if ((int)$pdo->query('SELECT COUNT(*) FROM transaksi_mentah')->fetchColumn() === 0) {
        $mentah = [
            ["id" => "TX001", "amount" => 50000,  "status" => "success", "date" => "2026-10-21"],
            ["id" => "TX002", "amount" => -10000, "status" => "success", "date" => "2026-10-21"],
            ["id" => "TX003", "amount" => 150000, "status" => "pending", "date" => "2026-10-21"],
            ["id" => "TX004", "amount" => null,   "status" => "success", "date" => "2026-10-21"],
            ["id" => "TX005", "amount" => 75000,  "status" => "success", "date" => "2026-10-22"],
            ["id" => "TX006", "amount" => 20000,  "status" => "success"],
            ["id" => "TX007", "amount" => "abc",  "status" => "failed",  "date" => "2026-10-22"],
            ["id" => "",      "amount" => 30000,  "status" => "success", "date" => "2026-10-23"],
        ];
        $st = $pdo->prepare('INSERT INTO transaksi_mentah (payload) VALUES (?)');
        foreach ($mentah as $r) $st->execute([json_encode($r)]);
        $say("[OK] " . count($mentah) . " transaksi mentah dimasukkan");
    } else $say("[--] transaksi_mentah sudah terisi, dilewati");

    // 4. Dataset 50 sampel (Soal 5)
    if ((int)$pdo->query('SELECT COUNT(*) FROM dataset_sampel')->fetchColumn() === 0) {
        mt_srand(2026);
        $st = $pdo->prepare('INSERT INTO dataset_sampel (id, fitur_x, fitur_y, label) VALUES (?,?,?,?)');
        for ($i = 1; $i <= 50; $i++) $st->execute([$i, mt_rand(10, 100), mt_rand(100, 500), $i % 2 == 0 ? 'Positif' : 'Negatif']);
        $say("[OK] 50 data sampel dimasukkan");
    } else $say("[--] dataset_sampel sudah terisi, dilewati");
}

/** Dipanggil dari koneksi(): bila tabel 'pengguna' belum ada, siapkan semuanya (sekali saja). */
function siapkan_otomatis(PDO $pdo): void
{
    static $sudah = false;
    if ($sudah) return;
    $sudah = true;
    if (!(config()['app']['auto_setup'] ?? true)) return;
    try {
        $pdo->query('SELECT 1 FROM pengguna LIMIT 1');
        $pdo->query('SELECT 1 FROM split_detail LIMIT 1');   // tabel terakhir di skema
        return;                                              // sudah lengkap
    } catch (PDOException $e) {
        siapkan_database($pdo);
    }
}
