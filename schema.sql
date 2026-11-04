-- Skema database UTS Data Science (MySQL / MariaDB)
-- Otomatis dijalankan oleh setup.php. Jika diimpor manual lewat phpMyAdmin,
-- PILIH DATABASE-nya dulu (di cPanel: akuncpanel_uts) lalu klik Import.

-- ===== Autentikasi =====
CREATE TABLE IF NOT EXISTS pengguna (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    username       VARCHAR(50)  NOT NULL UNIQUE,
    nama           VARCHAR(100) NOT NULL,
    role           ENUM('admin','dosen','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
    password_hash  VARCHAR(255) NOT NULL,
    is_demo        TINYINT(1)   NOT NULL DEFAULT 0,
    terakhir_login DATETIME NULL
);

-- ===== Soal 1: Data Pipeline =====
CREATE TABLE IF NOT EXISTS transaksi_mentah (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    payload    TEXT NOT NULL,                      -- baris mentah (JSON), struktur bisa rusak
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS pipeline_run (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    dijalankan_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS pipeline_tahap (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    run_id  INT NOT NULL,
    tahap   VARCHAR(20) NOT NULL,                  -- ingestion / valid / diproses / siap_analisis
    status  VARCHAR(10) NOT NULL,
    jumlah  INT NOT NULL,
    FOREIGN KEY (run_id) REFERENCES pipeline_run(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS transaksi_bersih (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    run_id    INT NOT NULL,
    kode      VARCHAR(20) NOT NULL,
    amount    DECIMAL(15,2) NOT NULL,
    status    VARCHAR(10) NOT NULL,
    tanggal   DATE NOT NULL,
    FOREIGN KEY (run_id) REFERENCES pipeline_run(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS transaksi_ditolak (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    run_id  INT NOT NULL,
    baris   INT NOT NULL,
    kode    VARCHAR(20),
    alasan  VARCHAR(255) NOT NULL,
    FOREIGN KEY (run_id) REFERENCES pipeline_run(id) ON DELETE CASCADE
);

-- ===== Soal 2: EDA CSV =====
CREATE TABLE IF NOT EXISTS mahasiswa (
    nim         VARCHAR(20) PRIMARY KEY,
    nama        VARCHAR(100) NOT NULL,
    nilai_tugas INT,
    nilai_ujian INT
);

-- ===== Soal 3: Imputasi & Normalisasi =====
CREATE TABLE IF NOT EXISTS hasil_imputasi (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    urutan            INT NOT NULL,
    nilai_awal        DECIMAL(10,2) NULL,          -- NULL = missing value
    nilai_imputasi    DECIMAL(10,2) NOT NULL,
    nilai_normalisasi DECIMAL(8,4) NOT NULL
);

-- ===== Soal 4: Statistik Deskriptif =====
CREATE TABLE IF NOT EXISTS hasil_statistik (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    dijalankan_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    n               INT NOT NULL,
    mean            DECIMAL(10,2),
    median          DECIMAL(10,2),
    modus           VARCHAR(50),
    std_sampel      DECIMAL(10,2),
    std_populasi    DECIMAL(10,2)
);
CREATE TABLE IF NOT EXISTS hasil_frekuensi (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    stat_id   INT NOT NULL,
    rentang   VARCHAR(10) NOT NULL,
    frekuensi INT NOT NULL,
    FOREIGN KEY (stat_id) REFERENCES hasil_statistik(id) ON DELETE CASCADE
);

-- ===== Soal 5: Train-Test Split =====
CREATE TABLE IF NOT EXISTS dataset_sampel (
    id      INT PRIMARY KEY,
    fitur_x INT NOT NULL,
    fitur_y INT NOT NULL,
    label   VARCHAR(10) NOT NULL
);
CREATE TABLE IF NOT EXISTS split_run (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    dijalankan_pada TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    test_size       DECIMAL(3,2) NOT NULL,
    jumlah_train    INT NOT NULL,
    jumlah_test     INT NOT NULL
);
CREATE TABLE IF NOT EXISTS split_detail (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    split_id  INT NOT NULL,
    sampel_id INT NOT NULL,
    subset    ENUM('train','test') NOT NULL,
    UNIQUE KEY uq_split_sampel (split_id, sampel_id),   -- mencegah duplikasi antar subset
    FOREIGN KEY (split_id) REFERENCES split_run(id) ON DELETE CASCADE
);
