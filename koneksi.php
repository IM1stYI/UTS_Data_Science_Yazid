<?php
/** Helper konfigurasi & koneksi database (PDO bawaan PHP, tanpa composer). */

require_once __DIR__ . '/lib/skema.php';

function config(): array
{
    static $c = null;
    return $c ??= require __DIR__ . '/config.php';
}

function adalah_lokal(): bool
{
    $h = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
    return in_array($h, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
        || str_ends_with($h, '.test') || str_ends_with($h, '.local');
}

function driver(): string
{
    return config()['db']['driver'] ?? 'mysql';
}

/** Koneksi PDO. $tanpa_db=true dipakai setup.php untuk membuat database (MySQL saja). */
function koneksi(bool $tanpa_db = false): PDO
{
    $d   = config()['db'];
    $opt = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    if (driver() === 'sqlite') {
        $path = $d['sqlite_path'] ?? (__DIR__ . '/data/uts.sqlite');
        if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
        $pdo = new PDO('sqlite:' . $path, null, null, $opt);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
        if (!$tanpa_db) siapkan_otomatis($pdo);
        return $pdo;
    }
    $dsn = "mysql:host={$d['host']};port={$d['port']};" . ($tanpa_db ? '' : "dbname={$d['database']};") . "charset={$d['charset']}";
    $pdo = new PDO($dsn, $d['username'], $d['password'], $opt + [PDO::ATTR_EMULATE_PREPARES => false]);
    if (!$tanpa_db) siapkan_otomatis($pdo);
    return $pdo;
}

/** Nama tampilan database (untuk dashboard / log). */
function nama_db(): string
{
    $d = config()['db'];
    return driver() === 'sqlite' ? 'SQLite: ' . basename($d['sqlite_path'] ?? 'uts.sqlite') : $d['database'];
}

/** SQL "sisipkan baris dengan nilai default semua kolom" untuk kedua driver. */
function sql_insert_default(string $tabel): string
{
    return driver() === 'sqlite' ? "INSERT INTO $tabel DEFAULT VALUES" : "INSERT INTO $tabel () VALUES ()";
}

/** Koneksi tunggal untuk satu request (dipakai aplikasi web). */
function db(): PDO
{
    static $p = null;
    return $p ??= koneksi();
}

/** Untuk skrip soal: bila DB belum siap, skrip tetap jalan tanpa menyimpan. */
function koneksi_aman(): ?PDO
{
    $ext = driver() === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql';
    if (!extension_loaded($ext)) {
        echo "[DB] Ekstensi $ext belum aktif -> mode tanpa database.\n\n";
        return null;
    }
    try {
        $pdo = koneksi();
        echo "[DB] Terhubung ke '" . nama_db() . "'\n\n";
        return $pdo;
    } catch (PDOException $e) {
        echo "[DB] Tidak terhubung (" . $e->getMessage() . ")\n";
        echo "[DB] Jalankan setup.php terlebih dahulu. Skrip lanjut tanpa database.\n\n";
        return null;
    }
}
