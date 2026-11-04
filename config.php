<?php
/**
 * Pemilih konfigurasi otomatis:
 *  - Diakses lewat localhost / 127.0.0.1 / *.test / *.local  -> config.local.php
 *  - Selain itu, jika config.cpanel.php ada                  -> config.cpanel.php
 *  - Selain itu                                                -> config.local.php
 */
$host   = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$lokal  = in_array($host, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
          || str_ends_with($host, '.test') || str_ends_with($host, '.local');

$cpanel = __DIR__ . '/config.cpanel.php';
return require (!$lokal && is_file($cpanel)) ? $cpanel : __DIR__ . '/config.local.php';
