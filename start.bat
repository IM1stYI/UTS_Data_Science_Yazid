@echo off
chcp 65001 >nul
title UTS Data Science - localhost offline
cd /d "%~dp0"
set PORT=8000

set PHP=
where php >nul 2>nul && set PHP=php
if not defined PHP if exist "%~dp0php\php.exe"        set PHP=%~dp0php\php.exe
if not defined PHP if exist "C:\xampp\php\php.exe"    set PHP=C:\xampp\php\php.exe
if not defined PHP for /d %%D in ("C:\laragon\bin\php\php*") do if exist "%%D\php.exe" set PHP=%%D\php.exe
if not defined PHP (
  echo [!] PHP tidak ditemukan.
  echo     Unduh PHP 8.1+ dari https://windows.php.net/download  ^(zip, Thread Safe^)
  echo     lalu ekstrak ke folder "php" di samping start.bat, atau pasang XAMPP.
  pause & exit /b 1
)

"%PHP%" -m | findstr /i "pdo_sqlite" >nul || (
  echo [!] Ekstensi pdo_sqlite belum aktif. Buka php.ini lalu hapus tanda ; pada:
  echo       extension=pdo_sqlite
  echo       extension=sqlite3
  pause & exit /b 1
)

echo Menyiapkan database (aman dijalankan berulang)...
"%PHP%" setup.php
echo.
echo Server berjalan di  http://localhost:%PORT%/   ^(tutup jendela ini untuk berhenti^)
start "" http://localhost:%PORT%/
"%PHP%" -S localhost:%PORT% router.php
pause
