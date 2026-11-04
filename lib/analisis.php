<?php
/**
 * Logika jawaban Soal 1-5 dalam bentuk fungsi murni (PHP native) untuk dashboard interaktif.
 * Prefix lib_ agar tidak bentrok dengan fungsi di skrip asli bagian2-UTS*.php.
 */

// ---------- Soal 1: Pipeline ----------
function lib_validasi_baris($row): array
{
    $e = [];
    if (!is_array($row)) return ['baris bukan array'];
    foreach (['id', 'amount', 'status', 'date'] as $k) if (!array_key_exists($k, $row)) $e[] = "kolom '$k' tidak ada";
    if ($e) return $e;
    if (trim((string)$row['id']) === '') $e[] = 'id kosong';
    if ($row['amount'] === null || $row['amount'] === '' || !is_numeric($row['amount'])) $e[] = 'amount kosong / bukan angka';
    elseif ($row['amount'] <= 0) $e[] = 'amount harus > 0';
    if (!in_array($row['status'], ['success', 'pending', 'failed'], true)) $e[] = 'status tidak dikenal';
    $d = DateTime::createFromFormat('Y-m-d', (string)$row['date']);
    if (!$d || $d->format('Y-m-d') !== $row['date']) $e[] = 'format date tidak valid (Y-m-d)';
    return $e;
}

function lib_pipeline(array $raw): array
{
    $valid = []; $ditolak = [];
    foreach (array_values($raw) as $i => $row) {
        $err = lib_validasi_baris($row);
        if (!$err) $valid[] = $row;
        else $ditolak[] = ['baris' => $i + 1, 'id' => is_array($row) ? (string)($row['id'] ?? '(tanpa id)') : '-', 'alasan' => implode('; ', $err)];
    }
    $final = array_map(fn($r) => [
        'id' => strtoupper(trim((string)$r['id'])), 'amount' => (float)$r['amount'], 'status' => $r['status'], 'date' => $r['date'],
    ], $valid);
    return [
        'tahapan' => [
            ['nama' => 'ingestion',     'status' => 'selesai', 'jumlah' => count($raw)],
            ['nama' => 'valid',         'status' => 'selesai', 'jumlah' => count($valid)],
            ['nama' => 'diproses',      'status' => 'selesai', 'jumlah' => count($final)],
            ['nama' => 'siap_analisis', 'status' => $final ? 'selesai' : 'kosong', 'jumlah' => count($final)],
        ],
        'ditolak' => $ditolak, 'final' => $final,
    ];
}

// ---------- Soal 2: EDA CSV (fopen/fgetcsv) ----------
function lib_tipe(string $v): string
{
    $v = trim($v);
    if ($v === '') return 'kosong';
    if (preg_match('/^-?\d+$/', $v)) return 'integer';
    return is_numeric($v) ? 'float' : 'string';
}

function lib_eda($h): array
{
    $kolom = fgetcsv($h, 0, ',', '"', '');
    if (!$kolom || $kolom === [null]) return ['error' => 'CSV kosong atau header tidak terbaca'];
    $kolom[0] = preg_replace('/^\xEF\xBB\xBF/', '', $kolom[0]);
    $kolom = array_map('trim', $kolom);
    $total = 0; $head = []; $tipe = array_fill_keys($kolom, []);
    while (($r = fgetcsv($h, 0, ',', '"', '')) !== false && $total < 5000) {
        if ($r === [null]) continue;
        $total++;
        if ($total <= 5) $head[] = $r;
        foreach ($kolom as $i => $k) $tipe[$k][lib_tipe($r[$i] ?? '')] = true;
    }
    $out = [];
    foreach ($tipe as $k => $set) {
        $j = array_keys($set);
        if (count($j) > 1 && !array_diff($j, ['integer', 'float'])) $j = ['float'];
        $out[] = ['kolom' => $k, 'tipe' => $j ? implode(', ', $j) : '-'];
    }
    return ['total' => $total, 'kolom' => $kolom, 'head' => $head, 'tipe' => $out];
}

// ---------- Soal 3: Imputasi + Min-Max ----------
function lib_parse_nilai(string $teks): array
{
    $data = [];
    foreach (explode(',', str_replace(["\n", ';'], ',', $teks)) as $tok) {
        $t = trim($tok);
        if ($t === '' || strtolower($t) === 'null' || $t === '""' || $t === "''") { $data[] = null; continue; }
        if (!is_numeric($t)) return ['error' => "Nilai '$t' bukan angka. Gunakan angka, null, atau \"\"."];
        $data[] = $t + 0;
    }
    if (count($data) > 1000) return ['error' => 'Maksimal 1000 data.'];
    return ['data' => $data];
}

function lib_imputasi(array $data): array
{
    $valid = array_values(array_filter($data, fn($v) => $v !== null && $v !== ''));
    if (!$valid) return ['error' => 'Tidak ada nilai valid untuk dihitung rata-ratanya.'];
    $mean = array_sum($valid) / count($valid);
    $imp  = array_map(fn($v) => ($v === null || $v === '') ? $mean : $v, $data);
    $min = min($imp); $max = max($imp); $rng = $max - $min;
    $rows = [];
    foreach ($data as $i => $v) {
        $rows[] = ['no' => $i + 1, 'awal' => $v, 'imputasi' => $imp[$i], 'norm' => $rng == 0 ? 0 : round(($imp[$i] - $min) / $rng, 4)];
    }
    return ['mean' => $mean, 'min' => $min, 'max' => $max, 'valid' => count($valid), 'kosong' => count($data) - count($valid), 'rows' => $rows];
}

// ---------- Soal 4: Statistik deskriptif ----------
function lib_statistik(string $teks): array
{
    $d = [];
    foreach (preg_split('/[\s,;]+/', trim($teks), -1, PREG_SPLIT_NO_EMPTY) as $t) {
        if (!is_numeric($t)) return ['error' => "'$t' bukan angka."];
        $d[] = $t + 0;
    }
    if (!$d) return ['error' => 'Masukkan minimal satu angka.'];
    if (count($d) > 10000) return ['error' => 'Maksimal 10000 data.'];

    $n = count($d); $mean = array_sum($d) / $n;
    $s = $d; sort($s); $m = intdiv($n, 2);
    $median = $n % 2 ? $s[$m] : ($s[$m - 1] + $s[$m]) / 2;
    $freq = array_count_values(array_map('strval', $d)); $mx = max($freq);
    $modus = [];
    if ($mx > 1) { foreach ($freq as $v => $f) if ($f === $mx) $modus[] = $v + 0; sort($modus); }
    $jk = 0.0; foreach ($d as $x) $jk += ($x - $mean) ** 2;

    $bins = ['< 60' => 0, '60-69' => 0, '70-79' => 0, '80-89' => 0, '90-100' => 0, '> 100' => 0];
    foreach ($d as $x) {
        if ($x < 60) $bins['< 60']++; elseif ($x < 70) $bins['60-69']++; elseif ($x < 80) $bins['70-79']++;
        elseif ($x < 90) $bins['80-89']++; elseif ($x <= 100) $bins['90-100']++; else $bins['> 100']++;
    }
    $ascii = '';
    foreach ($bins as $r => $f) { if (in_array($r, ['< 60', '> 100'], true) && $f === 0) continue; $ascii .= sprintf("%-7s | %-20s %d\n", $r, str_repeat('#', $f), $f); }
    return [
        'data' => $d, 'n' => $n, 'mean' => $mean, 'median' => $median, 'modus' => $modus, 'modus_freq' => $mx,
        'std_sampel' => $n > 1 ? sqrt($jk / ($n - 1)) : 0, 'std_populasi' => sqrt($jk / $n),
        'bins' => $bins, 'ascii' => $ascii,
    ];
}

// ---------- Soal 5: Train-test split ----------
function lib_dataset_sampel(?PDO $pdo): array
{
    if ($pdo) {
        try { $r = $pdo->query('SELECT id, fitur_x, fitur_y, label FROM dataset_sampel ORDER BY id')->fetchAll(); if ($r) return $r; } catch (Throwable $e) {}
    }
    mt_srand(2026); $r = [];
    for ($i = 1; $i <= 50; $i++) $r[] = ['id' => $i, 'fitur_x' => mt_rand(10, 100), 'fitur_y' => mt_rand(100, 500), 'label' => $i % 2 ? 'Negatif' : 'Positif'];
    return $r;
}

function lib_split(array $data, float $test_size = 0.2, bool $shuffle = true, ?int $seed = null): array
{
    if ($seed !== null) mt_srand($seed);
    $n = count($data);
    $k = max(1, min($n - 1, (int)round($n * $test_size)));
    $idx = range(0, $n - 1);
    if ($shuffle) for ($i = $n - 1; $i > 0; $i--) { $j = mt_rand(0, $i); [$idx[$i], $idx[$j]] = [$idx[$j], $idx[$i]]; }   // Fisher-Yates
    $te = array_slice($idx, 0, $k); $tr = array_slice($idx, $k);
    $train = array_map(fn($i) => $data[$i], $tr); $test = array_map(fn($i) => $data[$i], $te);
    $idTr = array_column($train, 'id'); $idTe = array_column($test, 'id');
    return [
        'total' => $n, 'jumlah_train' => count($train), 'jumlah_test' => count($test),
        'id_train' => $idTr, 'id_test' => $idTe, 'train' => $train, 'test' => $test,
        'irisan' => array_values(array_intersect($idTr, $idTe)), 'lengkap' => count($train) + count($test) === $n,
    ];
}
