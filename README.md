# UTS Data Science – PHP Native (Login + Dashboard Interaktif + Database)

Tanpa composer / CDN / internet. Butuh PHP 8.1+ (ekstensi `pdo_mysql`) dan MySQL/MariaDB.

## Akun dummy (dibuat otomatis oleh setup.php)
| Username | Password | Peran | Akses |
|---|---|---|---|
| admin | admin123 | admin | Semua soal + Riwayat Database |
| dosen | dosen123 | dosen | Semua soal + Riwayat Database |
| mahasiswa | mahasiswa123 | mahasiswa | Semua soal |

Di halaman login ada tombol **akun dummy**: sekali klik langsung masuk.

---
## 0. MODE OFFLINE (paling mudah, TANPA XAMPP/MySQL)
Database memakai **SQLite** (satu file `data/uts.sqlite`), jadi cukup PHP 8.1+ dengan ekstensi `pdo_sqlite`.
- **Windows**: klik dua kali `start.bat` (mencari PHP di PATH, folder `php\`, XAMPP, atau Laragon).
- **macOS / Linux**: `./start.sh`
- Manual: `php setup.php` lalu `php -S localhost:8000 router.php`, buka http://localhost:8000/
`router.php` menggantikan `.htaccess` (memblokir config, data, dan skrip jawaban) saat memakai server bawaan PHP.
Ingin kembali ke MySQL? Ubah `'driver' => 'mysql'` di `config.local.php`.
Reset data: hapus `data/uts.sqlite` lalu jalankan `start` lagi.

---
## A. Menjalankan di LOKAL (XAMPP / Laragon)
1. Start **Apache** dan **MySQL**.
2. Salin folder ke `C:\xampp\htdocs\Bagian2-UTS`.
3. Buka `http://localhost/Bagian2-UTS/setup.php` (sekali saja).
4. Buka `http://localhost/Bagian2-UTS/` → login.

Konfigurasi lokal ada di `config.local.php` (default root / tanpa password; MAMP: port 8889, password `root`).
Tanpa Apache: `php setup.php` lalu `php -S localhost:8000` (catatan: server bawaan PHP mengabaikan `.htaccess`).

## B. Menjalankan di cPANEL (tanpa setup.php)
1. Pastikan database & user MySQL sudah ada dan user punya ALL PRIVILEGES (kredensial ada di `config.cpanel.php`).
2. Upload seluruh isi folder ke `public_html/UTS/` (File Manager -> Upload zip lalu Extract, atau FTP).
3. Pilih **PHP 8.1+** dan aktifkan `pdo_mysql` (cPanel -> Select PHP Version).
4. Buka `https://domain-anda.com/UTS/` -> **tabel & akun dummy dibuat otomatis** saat halaman pertama dibuka. Selesai.
Kredensial dapat diganti lewat environment variable `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` atau langsung di `config.cpanel.php`.
Opsional: `setup.php?key=KUNCI` (key = `setup_key` di config.cpanel.php) bila ingin melihat log pembuatan tabel. Hapus setelah dipakai.
Jika `auto_setup` diset `false`, tabel tidak dibuat otomatis (gunakan setup.php atau impor `schema.sql` via phpMyAdmin).
Lokal (localhost) tetap memakai SQLite (`config.local.php`); domain hosting memakai `config.cpanel.php`.

## Struktur
- `login.php`, `logout.php`, `dashboard.php`, `api.php`, `index.php` – aplikasi web
- `assets/` – CSS & JS (tanpa library eksternal)
- `lib/` – autentikasi, logika jawaban, teks soal
- `bagian2-UTS1..5.php` – **skrip jawaban asli** (bisa juga dijalankan CLI: `php bagian2-UTS1.php`)
- `schema.sql`, `setup.php`, `config*.php`, `koneksi.php`, `.htaccess`

## Keamanan yang sudah diterapkan
Password di-hash (`password_hash`), query memakai prepared statement, token CSRF pada form & API,
session cookie `HttpOnly` + `SameSite` (+ `Secure` bila HTTPS), pembatasan percobaan login,
`setup.php` terkunci di hosting, file konfigurasi/skema/skrip jawaban diblokir dari akses langsung via `.htaccess`.

## Troubleshooting
- `Database belum siap` → jalankan setup.php; cek isi config.
- `Access denied for user` → user belum ditambahkan ke database di cPanel, atau password salah.
- `could not find driver` → aktifkan `pdo_mysql` di Select PHP Version.
- Halaman 403 pada file `.php` jawaban → memang disengaja; jalankan lewat dashboard setelah login.
