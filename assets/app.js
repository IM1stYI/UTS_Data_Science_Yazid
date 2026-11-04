(() => {
'use strict';
const A = window.APP, view = document.getElementById('view'), side = document.getElementById('side');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const fmt = (n, d = 2) => Number(n).toLocaleString('id-ID', {maximumFractionDigits: d});
const $ = (s, e = document) => e.querySelector(s);

async function api(action, params = {}, body = null) {
  const opt = {headers: {'X-CSRF-Token': A.csrf}};
  if (body) { opt.method = 'POST'; opt.headers['Content-Type'] = 'application/json'; opt.body = JSON.stringify(body); }
  const r = await fetch('api.php?' + new URLSearchParams({action, ...params}), opt);
  const j = await r.json().catch(() => ({error: 'Respons server tidak valid'}));
  if (!r.ok || j.error) throw new Error(j.error || 'HTTP ' + r.status);
  return j;
}
const table = (head, rows) => `<div class="tw"><table><thead><tr>${head.map(h => `<th>${esc(h)}</th>`).join('')}</tr></thead><tbody>${rows.map(r => `<tr>${r.map(c => `<td>${c}</td>`).join('')}</tr>`).join('')}</tbody></table></div>`;
const stat = (l, v, c = 'pri') => `<div class="card stat ${c}"><div class="l">${esc(l)}</div><div class="v">${v}</div></div>`;
async function guard(box, fn) {
  box.innerHTML = '<p class="muted">Memproses…</p>';
  try { box.innerHTML = await fn(); } catch (e) { box.innerHTML = `<div class="alert err">${esc(e.message)}</div>`; }
}

/* ---------- navigasi ---------- */
function renderNav(cur) {
  const link = (h, t) => `<a href="#${h}" class="${cur === h ? 'on' : ''}">${t}</a>`;
  side.innerHTML = link('beranda', '🏠 Beranda') + '<small>Soal Praktikum</small>' +
    A.soal.map((s, i) => link('soal' + (i + 1), `${i + 1}. ${esc(s.judul.split(/[(&,]/)[0].trim())}`)).join('') +
    (['admin', 'dosen'].includes(A.user.role) ? '<small>Admin</small>' + link('riwayat', '🗄 Riwayat Database') : '');
  side.classList.remove('open');
}
function route() {
  const h = location.hash.slice(1) || 'beranda';
  renderNav(h); window.scrollTo(0, 0);
  if (h === 'beranda') return pageHome();
  if (h === 'riwayat') return pageRiwayat();
  const m = h.match(/^soal([1-5])$/);
  m ? pageSoal(+m[1]) : pageHome();
}
window.addEventListener('hashchange', route);
document.getElementById('menuBtn').onclick = () => side.classList.toggle('open');

/* ---------- beranda ---------- */
async function pageHome() {
  view.innerHTML = `<div class="stack">
    <div><h2>Halo, ${esc(A.user.nama)} 👋</h2><p class="muted">Dashboard jawaban UTS Data Science – PHP Native. Pilih soal untuk melihat penjelasan, mencoba secara interaktif, membaca kode jawaban, dan menjalankan skrip aslinya.</p></div>
    <div id="st" class="cards"></div>
    <div class="soal-grid">${A.soal.map((s, i) => `<a href="#soal${i + 1}"><div class="card"><span class="badge">Soal ${i + 1}</span><h3>${esc(s.judul)}</h3><p class="muted">${esc(s.deskripsi)}</p></div></a>`).join('')}</div></div>`;
  try {
    const s = await api('status');
    $('#st').innerHTML = stat('Database', s.db ? 'Terhubung' : 'Putus', s.db ? 'ok' : 'bad') + stat('Nama DB', esc(s.database)) + stat('Versi PHP', esc(s.php)) + stat('Peran', esc(A.user.role), 'warn');
  } catch (e) { $('#st').innerHTML = `<div class="alert err">${esc(e.message)}</div>`; }
}

/* ---------- halaman soal ---------- */
function pageSoal(n) {
  const s = A.soal[n - 1];
  view.innerHTML = `<h2>Soal ${n}: ${esc(s.judul)}</h2><p class="muted">${esc(s.deskripsi)}</p>
    <ul class="poin">${s.poin.map(p => `<li>${esc(p)}</li>`).join('')}</ul>
    <div class="tabs"><button data-t="coba">▶ Coba Interaktif</button><button data-t="kode">{ } Kode Jawaban</button><button data-t="out">▣ Output Skrip Asli</button></div><div id="tb"></div>`;
  const tabs = view.querySelectorAll('.tabs button');
  const show = t => { tabs.forEach(b => b.classList.toggle('on', b.dataset.t === t)); const tb = $('#tb'); ({coba: UI[n], kode: tabKode, out: tabOut}[t])(tb, n); };
  tabs.forEach(b => b.onclick = () => show(b.dataset.t));
  show('coba');
}
function tabKode(el, n) {
  guard(el, async () => {
    const r = await api('source', {soal: n});
    setTimeout(() => $('#cp').onclick = () => navigator.clipboard.writeText(r.kode).then(() => { $('#cp').textContent = 'Tersalin ✓'; }), 0);
    return `<div class="row-f" style="margin-bottom:.5rem"><span class="muted">File: <code>${esc(r.file)}</code></span><button id="cp" class="btn sm" style="margin-left:auto">Salin kode</button></div><pre class="code">${esc(r.kode)}</pre>`;
  });
}
function tabOut(el, n) {
  el.innerHTML = `<p class="muted">Menjalankan skrip <code>${esc(A.soal[n - 1].file)}</code> di server (sama persis dengan hasil CLI; hasilnya juga tersimpan ke database).</p><button id="go" class="btn primary">Jalankan Skrip</button><div id="o" style="margin-top:.8rem"></div>`;
  $('#go').onclick = () => guard($('#o'), async () => `<pre class="code">${esc((await api('script', {soal: n})).output)}</pre>`);
}

/* ---------- UI interaktif tiap soal ---------- */
const UI = {};

// Soal 1
UI[1] = el => {
  let rows = [
    {id:'TX001',amount:50000,status:'success',date:'2026-10-21'},{id:'TX002',amount:-10000,status:'success',date:'2026-10-21'},
    {id:'TX003',amount:150000,status:'pending',date:'2026-10-21'},{id:'TX004',amount:null,status:'success',date:'2026-10-21'},
    {id:'TX005',amount:75000,status:'success',date:'2026-10-22'},{id:'TX006',amount:20000,status:'success'},
    {id:'TX007',amount:'abc',status:'failed',date:'2026-10-22'},{id:'',amount:30000,status:'success',date:'2026-10-23'}];
  const draw = () => {
    $('#rw').innerHTML = table(['#','id','amount','status','date',''], rows.map((r, i) => [i + 1, esc(r.id), r.amount === null ? '<i class="muted">null</i>' : esc(r.amount), esc(r.status ?? ''), 'date' in r ? esc(r.date) : '<i class="muted">(tidak ada)</i>', `<button class="btn sm" data-del="${i}">✕</button>`]));
    el.querySelectorAll('[data-del]').forEach(b => b.onclick = () => { rows.splice(+b.dataset.del, 1); draw(); });
  };
  el.innerHTML = `<div class="stack"><div class="card"><h3>Data mentah transaksi</h3><div id="rw"></div>
    <div class="row-f" style="margin-top:.6rem"><div><label>id</label><input id="f-id" placeholder="TX009"></div><div><label>amount</label><input id="f-am" placeholder="25000"></div>
    <div><label>status</label><select id="f-st"><option>success</option><option>pending</option><option>failed</option><option value="">(kosong)</option></select></div>
    <div><label>date</label><input id="f-dt" type="date"></div><button id="add" class="btn">+ Tambah</button></div>
    <p class="muted" style="font-size:.8rem">Kosongkan <b>date</b> untuk mensimulasikan kolom yang hilang (gagal validasi struktur).</p>
    <div class="row-f"><button id="run" class="btn primary">Jalankan Pipeline</button><button id="rs" class="btn">Reset contoh</button></div></div><div id="res"></div></div>`;
  draw();
  $('#add').onclick = () => {
    const am = $('#f-am').value.trim(), r = {id: $('#f-id').value, amount: am === '' ? null : (isNaN(am) ? am : +am), status: $('#f-st').value};
    if ($('#f-dt').value) r.date = $('#f-dt').value; rows.push(r); draw();
  };
  $('#rs').onclick = () => UI[1](el);
  $('#run').onclick = () => guard($('#res'), async () => {
    const r = await api('run', {soal: 1}, {rows});
    return `<div class="card stack"><h3>Laporan status tahapan pipeline</h3><div class="stages">${r.tahapan.map(t => `<div class="stage ${t.status}"><div class="muted" style="font-size:.75rem;text-transform:uppercase">${esc(t.nama.replace('_', ' '))}</div><div class="n">${t.jumlah}</div><div class="s">${t.status}</div></div>`).join('')}</div>
      <h3>Transaksi ditolak (${r.ditolak.length})</h3>${r.ditolak.length ? table(['Baris','ID','Alasan'], r.ditolak.map(d => [d.baris, esc(d.id), `<span class="chip bad">${esc(d.alasan)}</span>`])) : '<p class="muted">Tidak ada.</p>'}
      <h3>Data siap analisis (${r.final.length})</h3>${r.final.length ? table(['ID','Amount','Status','Tanggal'], r.final.map(f => [esc(f.id), fmt(f.amount, 0), esc(f.status), esc(f.date)])) : '<p class="muted">Kosong.</p>'}</div>`;
  });
};

// Soal 2
UI[2] = async el => {
  el.innerHTML = `<div class="card stack"><div class="row-f"><h3 style="flex:1">Isi CSV (dapat diedit)</h3><button id="rs" class="btn sm">Muat dataset_mahasiswa.csv</button></div><textarea id="csv" spellcheck="false" style="min-height:160px"></textarea><div><button id="run" class="btn primary">Eksplorasi Dataset</button></div></div><div id="res" style="margin-top:1rem"></div>`;
  const load = async () => { $('#csv').value = (await api('csv')).csv; };
  $('#rs').onclick = load; await load();
  $('#run').onclick = () => guard($('#res'), async () => {
    const r = await api('run', {soal: 2}, {csv: $('#csv').value});
    return `<div class="stack"><div class="cards">${stat('Total baris', r.total)}${stat('Jumlah kolom', r.kolom.length, 'ok')}</div>
      <div class="card"><h3>Nama kolom</h3><div class="chips">${r.kolom.map(k => `<span class="chip">${esc(k)}</span>`).join('')}</div></div>
      <div class="card"><h3>Pratinjau 5 baris pertama (head)</h3>${table(r.kolom, r.head.map(x => x.map(esc)))}</div>
      <div class="card"><h3>Tipe data tiap kolom</h3>${table(['Kolom','Tipe'], r.tipe.map(t => [esc(t.kolom), `<span class="chip ${t.tipe === 'string' ? 'warn' : 'ok'}">${esc(t.tipe)}</span>`]))}</div></div>`;
  });
};

// Soal 3
UI[3] = el => {
  el.innerHTML = `<div class="card stack"><label style="margin-top:0">Array nilai (pisahkan dengan koma; kosong = <code>null</code> atau <code>""</code>)</label><textarea id="t">80, null, 75, "", 90, null, 65, 85, 100</textarea><div><button id="run" class="btn primary">Bersihkan & Normalisasi</button></div></div><div id="res" style="margin-top:1rem"></div>`;
  $('#run').onclick = () => guard($('#res'), async () => {
    const r = await api('run', {soal: 3}, {teks: $('#t').value});
    return `<div class="stack"><div class="cards">${stat('Mean (data valid)', fmt(r.mean, 4))}${stat('Data valid', r.valid, 'ok')}${stat('Missing diimputasi', r.kosong, 'bad')}${stat('Min / Max', r.min + ' / ' + r.max, 'warn')}</div>
      <div class="card"><h3>Langkah demi langkah</h3>${table(['#','Data awal','Setelah imputasi','Min-Max (0–1)',''], r.rows.map(x => [x.no, x.awal === null ? '<span class="chip bad">kosong</span>' : x.awal, fmt(x.imputasi, 4), `<b>${x.norm}</b>`,
        `<div class="bars" style="width:140px"><div class="bar"><div class="fill" style="width:${x.norm * 100}%"></div></div></div>`]))}</div></div>`;
  });
  $('#run').click();
};

// Soal 4
UI[4] = el => {
  el.innerHTML = `<div class="card stack"><label style="margin-top:0">Data numerik (pisahkan dengan koma / spasi / baris baru)</label><textarea id="t">65, 72, 85, 92, 95, 88, 76, 60, 68, 74, 82, 89, 91, 100, 75, 85, 85, 90, 62, 78</textarea><div class="row-f"><button id="run" class="btn primary">Hitung Statistik</button><button id="rnd" class="btn">Data acak</button></div></div><div id="res" style="margin-top:1rem"></div>`;
  $('#rnd').onclick = () => { $('#t').value = Array.from({length: 25}, () => 55 + Math.floor(Math.random() * 46)).join(', '); $('#run').click(); };
  $('#run').onclick = () => guard($('#res'), async () => {
    const r = await api('run', {soal: 4}, {teks: $('#t').value}), mx = Math.max(...Object.values(r.bins), 1);
    return `<div class="stack"><div class="cards">${stat('Mean', fmt(r.mean))}${stat('Median', fmt(r.median), 'ok')}${stat('Modus', r.modus.length ? r.modus.join(', ') : '–', 'warn')}${stat('Std Deviasi (n−1)', fmt(r.std_sampel), 'bad')}</div>
      <p class="muted">n = ${r.n} · std populasi = ${fmt(r.std_populasi)}${r.modus.length ? ` · modus muncul ${r.modus_freq}×` : ' · semua nilai unik'}</p>
      <div class="two"><div class="card"><h3>Diagram batang</h3><div class="bars">${Object.entries(r.bins).filter(([k, v]) => v || !['< 60', '> 100'].includes(k)).map(([k, v]) => `<div class="row"><span class="lbl">${esc(k)}</span><div class="bar"><div class="fill" style="width:${v / mx * 100}%"></div></div><span class="val">${v}</span></div>`).join('')}</div></div>
      <div class="card"><h3>ASCII Bar Chart</h3><pre class="code">${esc(r.ascii)}</pre></div></div></div>`;
  });
  $('#run').click();
};

// Soal 5
UI[5] = el => {
  el.innerHTML = `<div class="card stack"><div class="row-f"><div><label>Ukuran data uji: <b id="tv">20%</b></label><input id="ts" type="range" min="5" max="50" step="5" value="20"></div>
    <div><label>Seed (opsional, untuk hasil yang bisa diulang)</label><input id="sd" type="number" placeholder="mis. 42"></div><div><label><input id="sh" type="checkbox" checked> Acak indeks (shuffle)</label></div><button id="run" class="btn primary">Split Data</button></div></div><div id="res" style="margin-top:1rem"></div>`;
  $('#ts').oninput = () => $('#tv').textContent = $('#ts').value + '%';
  $('#run').onclick = () => guard($('#res'), async () => {
    const r = await api('run', {soal: 5}, {test_size: $('#ts').value / 100, shuffle: $('#sh').checked, seed: $('#sd').value});
    const set = new Set(r.id_test), pt = r.jumlah_train / r.total * 100, ok = !r.irisan.length && r.lengkap;
    const cols = ['id','fitur_x','fitur_y','label'];
    return `<div class="stack"><div class="cards">${stat('Total data', r.total)}${stat('Data latih', r.jumlah_train, 'ok')}${stat('Data uji', r.jumlah_test, 'warn')}${stat('Duplikasi', r.irisan.length, ok ? 'ok' : 'bad')}</div>
      <div class="split"><div style="width:${pt}%;background:#059669">Latih ${fmt(pt, 0)}%</div><div style="width:${100 - pt}%;background:#7c3aed">Uji ${fmt(100 - pt, 0)}%</div></div>
      <div class="alert ${ok ? 'ok' : 'err'}">${ok ? '✓ Terverifikasi: irisan train & test kosong, dan train + test = total data.' : '✗ Ada masalah pada pembagian data.'}</div>
      <div class="card"><h3>Posisi tiap sampel (hijau = latih, ungu = uji)</h3><div class="grid50">${Array.from({length: r.total}, (_, i) => `<span class="${set.has(i + 1) ? 'te' : 'tr'}">${i + 1}</span>`).join('')}</div></div>
      <div class="two"><div class="card"><h3>Data latih (5 teratas)</h3>${table(cols, r.train.slice(0, 5).map(x => cols.map(c => esc(x[c]))))}</div><div class="card"><h3>Data uji (5 teratas)</h3>${table(cols, r.test.slice(0, 5).map(x => cols.map(c => esc(x[c]))))}</div></div></div>`;
  });
  $('#run').click();
};

/* ---------- riwayat database ---------- */
async function pageRiwayat() {
  view.innerHTML = '<h2>Riwayat Database</h2><div id="rv" class="stack"><p class="muted">Memuat…</p></div>';
  guard($('#rv'), async () => {
    const r = await api('riwayat'), k = (t, h, rows) => `<div class="card"><h3>${t}</h3>${rows.length ? table(h, rows) : '<p class="muted">Belum ada data. Jalankan skrip asli pada tab “Output Skrip Asli”.</p>'}</div>`;
    return k('Pipeline terakhir (Soal 1)', ['Run','Waktu','Diterima','Siap analisis'], r.pipeline.map(x => [x.id, x.dijalankan_pada, x.diterima, x.siap])) +
      k('Statistik terakhir (Soal 4)', ['ID','Waktu','n','Mean','Median','Modus','Std'], r.statistik.map(x => [x.id, x.dijalankan_pada, x.n, x.mean, x.median, esc(x.modus), x.std_sampel])) +
      k('Split terakhir (Soal 5)', ['ID','Waktu','Test size','Train','Test'], r.split.map(x => [x.id, x.dijalankan_pada, x.test_size, x.jumlah_train, x.jumlah_test])) +
      k('Tabel mahasiswa (Soal 2)', ['NIM','Nama','Tugas','Ujian'], r.mahasiswa.map(x => [esc(x.nim), esc(x.nama), x.nilai_tugas, x.nilai_ujian])) +
      k('Pengguna', ['Username','Nama','Role','Login terakhir'], r.pengguna.map(x => [esc(x.username), esc(x.nama), `<span class="badge r-${x.role}">${x.role}</span>`, x.terakhir_login ?? '–']));
  });
}

route();
})();
