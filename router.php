<?php
/**
 * Router untuk server bawaan PHP (php -S ... router.php).
 * Server bawaan PHP mengabaikan .htaccess, jadi file sensitif diblokir di sini.
 */
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');

$diblokir = '#^/(data/|lib/|config.*\.php|koneksi\.php|router\.php|schema.*\.sql|README\.md|dataset_mahasiswa\.csv|bagian2-UTS[0-9]\.php|tampulan-bagian2-UTS\.html|start\.(bat|sh)|\.)#i';
if (preg_match($diblokir, $path) || str_contains($path, '..')) {
    http_response_code(403);
    exit('403 - Akses ditolak');
}
return false;   // selain itu: layani file/skrip apa adanya
