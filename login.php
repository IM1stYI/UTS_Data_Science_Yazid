<?php
require __DIR__ . '/lib/auth.php';
sesi_mulai();
if (pengguna_aktif()) { header('Location: dashboard.php'); exit; }

$err  = '';
$demo = !empty(config()['app']['demo_login']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_cek($_POST['csrf'] ?? '')) {
        $err = 'Sesi kedaluwarsa, silakan coba lagi.';
    } else {
        $h = (($_POST['aksi'] ?? '') === 'dummy')
            ? login_masuk((string)($_POST['username'] ?? ''), null, true)
            : login_masuk(trim((string)($_POST['username'] ?? '')), (string)($_POST['password'] ?? ''));
        if ($h === true) { header('Location: dashboard.php'); exit; }
        $err = $h;
    }
}
$csrf = csrf_token();
$akun = [
    ['admin',     'Admin',     'Akses penuh + riwayat database',  'admin123'],
    ['dosen',     'Dosen',     'Lihat semua jawaban + riwayat',   'dosen123'],
    ['mahasiswa', 'Mahasiswa', 'Lihat & coba semua jawaban',      'mahasiswa123'],
];
?><!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login – UTS Data Science</title>
<link rel="stylesheet" href="assets/app.css">
</head><body class="auth">
<main class="login-box">
  <div class="brand"><span class="logo">DS</span><div><h1>UTS Data Science</h1><p class="muted">PHP Native · Politeknik META Industri</p></div></div>

  <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <form method="post" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= $csrf ?>">
    <label>Username<input name="username" required autofocus></label>
    <label>Password<input name="password" type="password" required></label>
    <button class="btn primary block" type="submit">Masuk</button>
  </form>

  <?php if ($demo): ?>
  <div class="divider"><span>atau masuk cepat dengan akun dummy</span></div>
  <div class="demo-list">
    <?php foreach ($akun as [$u, $label, $ket, $pw]): ?>
      <form method="post" class="demo-item">
        <input type="hidden" name="csrf" value="<?= $csrf ?>">
        <input type="hidden" name="aksi" value="dummy">
        <input type="hidden" name="username" value="<?= $u ?>">
        <button class="demo-btn" type="submit">
          <strong><?= $label ?></strong><small><?= $ket ?></small>
          <code><?= $u ?> / <?= $pw ?></code>
        </button>
      </form>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</main>
</body></html>
