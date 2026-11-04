<?php
/**
 * Konfigurasi LOKAL / OFFLINE.
 * Default: SQLite -> database berupa 1 file (data/uts.sqlite), TANPA perlu XAMPP/MySQL.
 * Ingin tetap memakai MySQL (XAMPP/Laragon/MAMP)? Ubah 'driver' menjadi 'mysql'.
 */
return [
    'db' => [
        'driver'   => 'sqlite',                       // 'sqlite' (offline) atau 'mysql'
        'sqlite_path' => __DIR__ . '/data/uts.sqlite',
        // --- hanya dipakai bila driver = mysql (MAMP: port 8889, password "root") ---
        'host' => '127.0.0.1', 'port' => 3306,
        'database' => 'uts_datascience', 'username' => 'root', 'password' => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'demo_login'   => true,      // tombol "Masuk sebagai akun dummy"
        'setup_key'    => '',        // tidak wajib di localhost
        'session_name' => 'uts_ds',
        'auto_setup'   => true,      // tabel + akun dummy dibuat otomatis
    ],
];
