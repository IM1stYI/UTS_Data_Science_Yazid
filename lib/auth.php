<?php
/** Autentikasi berbasis session + CSRF (PHP bawaan). */
require_once __DIR__ . '/../koneksi.php';

function sesi_mulai(): void
{
    if (session_status() !== PHP_SESSION_NONE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
          || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name(config()['app']['session_name'] ?? 'uts_ds');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'secure' => $https, 'samesite' => 'Lax']);
    session_start();
}

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function csrf_cek(?string $t): bool
{
    return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function pengguna_aktif(): ?array
{
    return $_SESSION['user'] ?? null;
}

function wajib_login(): array
{
    $u = pengguna_aktif();
    if (!$u) { header('Location: login.php'); exit; }
    return $u;
}

/** @return true|string  true bila berhasil, string = pesan galat */
function login_masuk(string $username, ?string $password, bool $dummy = false)
{
    if (time() < ($_SESSION['kunci_sampai'] ?? 0)) {
        return 'Terlalu banyak percobaan. Coba lagi beberapa detik lagi.';
    }
    try {
        $st = db()->prepare('SELECT * FROM pengguna WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
    } catch (Throwable $e) {
        return 'Database belum siap. Jalankan setup.php terlebih dahulu.';
    }

    $ok = false;
    if ($u) {
        $ok = $dummy
            ? (!empty(config()['app']['demo_login']) && (int)$u['is_demo'] === 1)
            : password_verify((string)$password, $u['password_hash']);
    }
    if (!$ok) {
        $_SESSION['gagal'] = ($_SESSION['gagal'] ?? 0) + 1;
        if ($_SESSION['gagal'] >= 5) { $_SESSION['kunci_sampai'] = time() + 30; $_SESSION['gagal'] = 0; }
        return $dummy ? 'Login dummy dinonaktifkan atau akun belum dibuat (jalankan setup.php).' : 'Username atau password salah.';
    }

    session_regenerate_id(true);
    $_SESSION['user'] = ['id' => (int)$u['id'], 'username' => $u['username'], 'nama' => $u['nama'], 'role' => $u['role']];
    $_SESSION['gagal'] = 0;
    db()->prepare('UPDATE pengguna SET terakhir_login = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), $u['id']]);
    return true;
}

function keluar_sesi(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
