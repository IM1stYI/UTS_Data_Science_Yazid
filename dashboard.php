<?php
require __DIR__ . '/lib/auth.php';
sesi_mulai();
$user = wajib_login();
$info = require __DIR__ . '/lib/soal_info.php';
$app  = [
    'user' => $user,
    'csrf' => csrf_token(),
    'soal' => array_map(fn($s) => ['judul' => $s['judul'], 'deskripsi' => $s['deskripsi'], 'poin' => $s['poin'], 'file' => $s['file']], $info),
];
$json = json_encode($app, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?><!DOCTYPE html>
<html lang="id"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard – UTS Data Science</title>
<link rel="stylesheet" href="assets/app.css">
</head><body>
<header class="top">
  <button id="menuBtn" class="btn ghost only-mobile" aria-label="Menu">☰</button>
  <div class="brand sm"><span class="logo">DS</span><strong>UTS Data Science</strong></div>
  <div class="spacer"></div>
  <div class="who"><strong><?= htmlspecialchars($user['nama']) ?></strong><span class="badge r-<?= $user['role'] ?>"><?= $user['role'] ?></span></div>
  <form method="post" action="logout.php"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>"><button class="btn ghost">Keluar</button></form>
</header>
<div class="shell">
  <nav id="side" class="side"></nav>
  <main id="view" class="main"></main>
</div>
<script>window.APP = <?= $json ?>;</script>
<script src="assets/app.js"></script>
</body></html>
