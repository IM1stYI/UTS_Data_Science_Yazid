#!/usr/bin/env bash
# Jalankan: ./start.sh   (macOS / Linux)
cd "$(dirname "$0")" || exit 1
PORT="${PORT:-8000}"
command -v php >/dev/null || { echo "[!] PHP belum terpasang (macOS: brew install php | Ubuntu: sudo apt install php-cli php-sqlite3)"; exit 1; }
php -m | grep -qi pdo_sqlite || { echo "[!] Ekstensi pdo_sqlite belum aktif (Ubuntu: sudo apt install php-sqlite3)"; exit 1; }
php setup.php
echo; echo "Server berjalan di http://localhost:$PORT/  (Ctrl+C untuk berhenti)"
(command -v xdg-open >/dev/null && xdg-open "http://localhost:$PORT/" || command -v open >/dev/null && open "http://localhost:$PORT/") >/dev/null 2>&1 &
exec php -S "localhost:$PORT" router.php
