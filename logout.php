<?php
require __DIR__ . '/lib/auth.php';
sesi_mulai();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_cek($_POST['csrf'] ?? '')) keluar_sesi();
header('Location: login.php');
