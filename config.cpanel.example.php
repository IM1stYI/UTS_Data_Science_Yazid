<?php
/**
 * KONFIGURASI cPANEL (hosting).
 * Cara pakai: di File Manager cPanel, SALIN file ini menjadi  config.cpanel.php
 * lalu isi nilai di bawah. Jangan upload config.cpanel.php ke repositori publik.
 *
 * Di cPanel nama database & user SELALU diawali nama akun cPanel, contoh:
 *   database : akuncpanel_uts
 *   username : akuncpanel_utsuser
 */
return [
    'db' => [
        'host' => 'localhost', 'port' => 3306,
        'database' => 'akuncpanel_uts',
        'username' => 'akuncpanel_utsuser',
        'password' => 'GANTI_PASSWORD_DB',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        'demo_login'   => true,                    // set false jika tidak ingin akun dummy aktif di internet
        'setup_key'    => 'GANTI-KUNCI-RAHASIA',   // dipakai: setup.php?key=KUNCI
        'session_name' => 'uts_ds',
    ],
];
