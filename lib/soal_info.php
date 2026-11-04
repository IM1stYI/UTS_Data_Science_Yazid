<?php
/** Teks soal (dari lembar UTS) + nama file jawaban. */
return [
    ['judul' => 'Simulasi Siklus Hidup Data Sains (Data Pipeline Sederhana)', 'file' => 'bagian2-UTS1.php',
     'deskripsi' => 'Skrip PHP native berbasis array asosiatif: menerima data mentah transaksi penjualan, memvalidasi struktur baris, menyaring transaksi valid, dan menghasilkan laporan status tahapan pipeline.',
     'poin' => ['Validasi struktur baris data', 'Penyaringan transaksi yang valid', 'Laporan status: valid → diproses → siap analisis']],
    ['judul' => 'Pengambilan & Eksplorasi Awal Dataset (EDA Ekstraksi CSV)', 'file' => 'bagian2-UTS2.php',
     'deskripsi' => 'Membaca dataset_mahasiswa.csv (nim, nama, nilai_tugas, nilai_ujian) dengan fopen() dan fgetcsv().',
     'poin' => ['Jumlah total baris/rekaman', 'Daftar nama kolom', 'Pratinjau 5 baris pertama (head)', 'Pengecekan tipe data tiap kolom']],
    ['judul' => 'Pembersihan Data, Penanganan Missing Value & Transformasi', 'file' => 'bagian2-UTS3.php',
     'deskripsi' => 'Array nilai ujian [80, null, 75, "", 90, null, 65, 85, 100] dibersihkan dan ditransformasi.',
     'poin' => ['Mean dari nilai valid', 'Imputasi null / "" dengan mean', 'Normalisasi Min-Max ke rentang 0–1']],
    ['judul' => 'Kalkulator Statistik Deskriptif & Visualisasi Karakter ASCII', 'file' => 'bagian2-UTS4.php',
     'deskripsi' => 'Menerima sekumpulan data numerik lalu menghitung statistik deskriptif dan menampilkan ASCII bar chart.',
     'poin' => ['Mean, Median, Modus', 'Standar deviasi', 'ASCII bar chart frekuensi per rentang (60-69, 70-79, 80-89, 90-100)']],
    ['judul' => 'Pembagian Data Latih dan Data Uji (Train-Test Split Algorithm)', 'file' => 'bagian2-UTS5.php',
     'deskripsi' => 'Fungsi train_test_split($data, $test_size = 0.2, $shuffle = true) pada dataset 2 dimensi berisi 50 sampel.',
     'poin' => ['80% data latih, 20% data uji', 'Pengacakan indeks (Fisher-Yates) tanpa duplikasi antar subset', 'Tampilkan jumlah baris tiap himpunan']],
];
