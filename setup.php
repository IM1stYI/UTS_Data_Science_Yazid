<?php
/**
 * SETUP DATABASE - jalankan SEKALI.
 *   Lokal   : http://localhost/Bagian2-UTS/setup.php     atau  php setup.php
 *   cPanel  : https://domain-anda.com/Bagian2-UTS/setup.php?key=KUNCI_DI_config.cpanel.php
 * Di cPanel, buat dulu database + user lewat "MySQL Databases" / "MySQL Database Wizard".
 */
$cli = (PHP_SAPI === 'cli');
require __DIR__ . '/koneksi.php';

// Pengaman: di hosting, setup hanya bisa dijalankan dengan kunci rahasia
if (!$cli && !adalah_lokal()) {
    $kunci = (string)(config()['app']['setup_key'] ?? '');
    if ($kunci === '' || $kunci === 'GANTI-KUNCI-RAHASIA' || !hash_equals($kunci, (string)($_GET['key'] ?? ''))) {
        http_response_code(403);
        exit('Akses ditolak. Isi setup_key di config.cpanel.php lalu buka setup.php?key=KUNCI_ANDA');
    }
}
if (!$cli) { header('Content-Type: text/html; charset=utf-8'); echo '<pre style="font-family:monospace;font-size:14px">'; }
function say(string $m): void { echo $m . "\n"; }

try {
    $d = config()['db'];

    // 1. Koneksi ke database; jika belum ada coba dibuat (di cPanel biasanya tidak diizinkan)
    try {
        $pdo = koneksi();
    } catch (PDOException $e) {
        if (driver() === 'sqlite' || (int)$e->getCode() !== 1049) throw $e;   // 1049 = database MySQL tidak ditemukan
        try {
            koneksi(true)->exec("CREATE DATABASE `{$d['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo = koneksi();
        } catch (PDOException $e2) {
            throw new RuntimeException("Database '{$d['database']}' belum ada dan tidak bisa dibuat otomatis. " .
                "Di cPanel buat lewat 'MySQL Databases', tambahkan user ke database (ALL PRIVILEGES), lalu cocokkan config.cpanel.php.");
        }
    }
    say("[OK] Terhubung ke database '" . nama_db() . "'");

    // 2-4. Tabel, akun dummy, data awal (logika di lib/skema.php; juga berjalan otomatis tanpa setup.php)
    siapkan_database($pdo, 'say');

    say("\nSETUP SELESAI." . ($cli ? '' : ' <a href="login.php">Lanjut ke halaman login &rarr;</a>'));
    if (!$cli && !adalah_lokal()) say("\nPENTING: setelah selesai, hapus setup.php atau ubah setup_key demi keamanan.");
} catch (Throwable $e) {
    say("[GAGAL] " . $e->getMessage());
    say("Periksa: MySQL aktif, serta nilai host/database/username/password di config.");
    exit(1);
}
