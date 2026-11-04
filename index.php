<?php
require __DIR__ . '/lib/auth.php';
sesi_mulai();
header('Location: ' . (pengguna_aktif() ? 'dashboard.php' : 'login.php'));
