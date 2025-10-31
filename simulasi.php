<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
// ==========================
// Helper Functions
// ==========================
function formatRupiah($angka) {
    return 'Rp' . number_format((float)$angka, 0, ',', '.');
}
function parseRupiah($str) {
    $str = trim((string)$str);
    if ($str === '') return 0.0;
    // Remove anything that's not digit
    $clean = preg_replace('/[^0-9]/', '', $str);
    if ($clean === '' || $clean === null) return 0.0;
    return (float)$clean;
}
function percent($str, $default = 0) {
    if ($str === null || $str === '') return (float)$default;
    $str = str_replace(',', '.', (string)$str);
    return (float)$str;
}

// ==========================
// Core Finance Functions
// ==========================
function kewajibanBulanan($pinjaman, $bungaTahunan, $bulan, $metode) {
    $pinjaman = (float)$pinjaman; $bungaTahunan = (float)$bungaTahunan; $bulan = (int)$bulan;
    if ($pinjaman <= 0 || $bulan <= 0 || $bungaTahunan < 0) return 0.0;
    $i = $bungaTahunan / 100.0 / 12.0;
    switch ($metode) {
        case 'Anuitas':
            if ($i == 0) return $pinjaman / $bulan;
            return $pinjaman * $i / (1 - pow(1 + $i, -$bulan));
        case 'Flat':
            return ($pinjaman / $bulan) + ($pinjaman * ($bungaTahunan / 100.0) / 12.0);
        case 'Efektif':
            $sisa = $pinjaman; $total = 0.0;
            for ($m=0; $m<$bulan; $m++) {
                $bungaBulan = $sisa * $i;
                $pokok = $pinjaman / $bulan;
                $total += $pokok + $bungaBulan;
                $sisa -= $pokok;
            }
            return $total / $bulan;
        default:
            return 0.0;
    }
}
function pinjamanDariAngsuran($angsuran, $bungaTahunan, $bulan, $metode) {
    $angsuran = (float)$angsuran; $bungaTahunan = (float)$bungaTahunan; $bulan = (int)$bulan;
    if ($angsuran <= 0 || $bulan <= 0) return 0.0;
    $i = $bungaTahunan / 100.0 / 12.0;
    if ($metode === 'Anuitas') {
        if ($i == 0) return $angsuran * $bulan;
        return $angsuran / ($i / (1 - pow(1 + $i, -$bulan)));
    } else { // Flat (aproksimasi umum)
        return ($angsuran * $bulan) / (1 + ($bungaTahunan / 100.0) * ($bulan / 12.0));
    }
}

// ==========================
// Handle POST
// ==========================
$tab = $_POST['tab'] ?? 'simulasi';

$simulasi = [
    'nominal' => '', 'tenor' => '', 'bunga' => '', 'penghasilan' => '', 'metode' => 'Anuitas',
    'hasil' => ''
];
$analisa = [
    'pendapatan' => '', 'biaya' => '', 'risikoPersen' => '30', 'savingPersen' => '80',
    'pinjaman' => '', 'bulan' => '', 'bunga' => '', 'metode' => 'Anuitas',
    // hasil
    'risikoUsaha' => 0, 'pendapatanBersih' => 0, 'savingNominal' => 0,
    'kewajibanBulanan' => 0, 'rasioAngsuran' => 0, 'statusKonsumtif' => '', 'statusProduktif' => '',
    'rrc' => 0, 'statusRRC' => '', 'angsMaxKonsum' => 0, 'angsMaxProd' => 0,
    'pinjMaxAnuitas' => 0, 'pinjMaxFlat' => 0
];

if (isset($_POST['hitung_simulasi'])) {
    $tab = 'simulasi';
    $simulasi['nominal'] = $_POST['nominal'] ?? '';
    $simulasi['tenor'] = $_POST['tenor'] ?? '';
    $simulasi['bunga'] = $_POST['bunga'] ?? '';
    $simulasi['penghasilan'] = $_POST['penghasilan'] ?? '';
    $simulasi['metode'] = $_POST['metode'] ?? 'Anuitas';

    $nominal = parseRupiah($simulasi['nominal']);
    $tenor = (int)($simulasi['tenor']);
    $bunga = percent($simulasi['bunga']);
    $penghasilan = parseRupiah($simulasi['penghasilan']);

    if ($nominal > 0 && $tenor > 0 && $bunga > 0) {
        $i = $bunga / 100.0 / 12.0;
        $angs = kewajibanBulanan($nominal, $bunga, $tenor, $simulasi['metode']);
        // total bunga
        switch ($simulasi['metode']) {
            case 'Flat':
                $bungaBulanan = $nominal * ($bunga/100.0) / 12.0;
                $totalBunga = $bungaBulanan * $tenor;
                break;
            case 'Anuitas':
                $totalBunga = ($angs * $tenor) - $nominal;
                break;
            case 'Efektif':
                $sisa = $nominal; $total = 0.0;
                for ($m=0; $m<$tenor; $m++) { $total += ($nominal/$tenor) + ($sisa*$i); $sisa -= ($nominal/$tenor);} 
                $totalBunga = $total - $nominal;
                break;
            default: $totalBunga = 0.0; 
        }
        $totalBayar = $nominal + $totalBunga;
        $rasio = $penghasilan > 0 ? ($angs / $penghasilan) * 100.0 : 0.0;
        $status = ($penghasilan > 0 && $rasio > 30) ? '⚠️ Tidak layak (rasio > 30%)' : '✅ Layak';

        $simulasi['hasil'] =
            '<b>📌 Metode:</b> ' . htmlspecialchars($simulasi['metode']) . '<br>' .
            '<b>💰 Angsuran / bulan:</b> ' . formatRupiah($angs) . '<br>' .
            '<b>💸 Total bayar:</b> ' . formatRupiah($totalBayar) . '<br>' .
            '<b>📊 Total bunga:</b> ' . formatRupiah($totalBunga) . '<br>' .
            '<b>📈 Rasio terhadap penghasilan:</b> ' . number_format($rasio,1,',','.') . '%<br>' .
            '<b>Status:</b> ' . $status;
    } else {
        $simulasi['hasil'] = '⚠️ Mohon isi semua field dengan benar.';
    }
}

if (isset($_POST['hitung_analisa'])) {
    $tab = 'analisa';
    // Ambil input mentah (untuk refill form)
    foreach (['pendapatan','biaya','pinjaman'] as $k) { $analisa[$k] = $_POST[$k] ?? ''; }
    $analisa['risikoPersen'] = $_POST['risikoPersen'] ?? '30';
    $analisa['savingPersen'] = $_POST['savingPersen'] ?? '80';
    $analisa['bulan'] = $_POST['bulan'] ?? '';
    $analisa['bunga'] = $_POST['bunga'] ?? '';
    $analisa['metode'] = $_POST['metodeAnalisa'] ?? 'Anuitas';

    // Parsing ke angka
    $pendapatan = parseRupiah($analisa['pendapatan']);
    $biaya = parseRupiah($analisa['biaya']);
    $risikoPersen = min(30.0, max(0.0, percent($analisa['risikoPersen'], 30)));
    $savingPersen = max(0.0, percent($analisa['savingPersen'], 80));
    $pinjaman = parseRupiah($analisa['pinjaman']);
    $bulan = (int)$analisa['bulan'];
    $bunga = percent($analisa['bunga']);

    // 7: Risiko usaha
    $risikoUsaha = $pendapatan * ($risikoPersen/100.0);
    // 5: Pendapatan bersih
    $pendapatanBersih = max(0.0, $pendapatan - $biaya - $risikoUsaha);
    // 9: Saving nominal
    $savingNominal = $pendapatanBersih * ($savingPersen/100.0);
    // 12: Kewajiban / bulan
    $kewajibanBulanan = kewajibanBulanan($pinjaman, $bunga, $bulan, $analisa['metode']);
    // 13: Rasio
    $rasioAngsuran = $pendapatanBersih > 0 ? ($kewajibanBulanan / $pendapatanBersih) * 100.0 : 0.0;
    $statusKonsumtif = ($rasioAngsuran > 30) ? 'Tidak layak' : 'Layak';
    if ($rasioAngsuran <= 30) { $statusProduktif = 'Aman'; }
    elseif ($rasioAngsuran <= 50) { $statusProduktif = 'Dipertimbangkan, cek laba usaha'; }
    else { $statusProduktif = 'Tidak layak'; }
    // 14: RRC
    $rrc = ($kewajibanBulanan > 0) ? (($savingNominal - $kewajibanBulanan) / $kewajibanBulanan) * 100.0 : 0.0;
    if ($rrc < 70) $statusRRC = 'Risiko';
    elseif ($rrc < 100) $statusRRC = 'Cukup aman';
    else $statusRRC = 'Aman';
    // 15: Angsuran maksimum
    $angsMaxKonsum = $pendapatanBersih * 0.30;
    $angsMaxProd = $pendapatanBersih * 0.45;
    // 16: Pinjaman maksimum dari angsuran maksimum (pakai produktif)
    $pinjMaxAnuitas = pinjamanDariAngsuran($angsMaxProd, $bunga, $bulan, 'Anuitas');
    $pinjMaxFlat = pinjamanDariAngsuran($angsMaxProd, $bunga, $bulan, 'Flat');

    // Simpan hasil ke array (untuk tampilan)
    $analisa['risikoUsaha'] = $risikoUsaha;
    $analisa['pendapatanBersih'] = $pendapatanBersih;
    $analisa['savingNominal'] = $savingNominal;
    $analisa['kewajibanBulanan'] = $kewajibanBulanan;
    $analisa['rasioAngsuran'] = $rasioAngsuran;
    $analisa['statusKonsumtif'] = $statusKonsumtif;
    $analisa['statusProduktif'] = $statusProduktif;
    $analisa['rrc'] = $rrc;
    $analisa['statusRRC'] = $statusRRC;
    $analisa['angsMaxKonsum'] = $angsMaxKonsum;
    $analisa['angsMaxProd'] = $angsMaxProd;
    $analisa['pinjMaxAnuitas'] = $pinjMaxAnuitas;
    $analisa['pinjMaxFlat'] = $pinjMaxFlat;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Simulasi & Analisa Pinjaman (PHP)</title>
<style>
    :root { --teal: #0f766e; --muted:#f4f7f8; --card:#ffffff; --text:#111827; }
    *{ box-sizing: border-box; }
    body{ font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; background:var(--muted); color:var(--text); margin:0; }
    .container{ max-width:1080px; margin: 24px auto; padding: 0 16px; }
    .tabs{ display:flex; gap:8px; margin-bottom:16px; }
    .tab{ padding:10px 14px; border-radius:999px; border:1px solid #cbd5e1; background:#fff; cursor:pointer; font-weight:600; }
    .tab.active{ background: var(--teal); color:#fff; border-color:var(--teal); }
    .grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap:12px; }
    .col-12{ grid-column: span 12; }
    .col-6{ grid-column: span 12; }
    .card{ background:var(--card); border-radius:16px; padding:16px; box-shadow: 0 6px 20px rgba(0,0,0,.06); }
    label{ display:block; font-size:14px; margin-bottom:6px; color:#334155; }
    input, select{ width:100%; padding:10px 12px; border-radius:10px; border:1px solid #cbd5e1; background:#fff; }
    .row{ display:flex; gap:8px; }
    .btn{ border:none; padding:10px 14px; border-radius:10px; font-weight:700; cursor:pointer; }
    .btn-primary{ background:var(--teal); color:#fff; }
    .btn-ghost{ background:#e2e8f0; }
    .muted{ color:#64748b; font-size:12px; }
    .result{ background:#ecfeff; border:1px solid #22d3ee; padding:16px; border-radius:12px; }
    .tile{ display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px dashed #e2e8f0; }
    .tile:last-child{ border-bottom:none; }
    .badge{ font-size:12px; padding:4px 8px; border-radius:999px; }
    .red{ background:#fee2e2; color:#991b1b; }
    .orange{ background:#ffedd5; color:#9a3412; }
    .blue{ background:#dbeafe; color:#1e3a8a; }
    @media(min-width:900px){ .col-6{ grid-column: span 6; } }
</style>
</head>
<body>
<div class="container">
    <h1 style="margin:6px 0 18px 0;">Simulasi & Analisa Pinjaman</h1>
    <div class="tabs">
        <button class="tab btn btn-outline-primary" data-tab="hitung" onclick="switchTab('hitung')">Penghasilan</button>    
        <button class="tab btn btn-outline-primary active" data-tab="simulasi" onclick="switchTab('simulasi')">Kewajiban/bulan</button>
        <button class="tab btn btn-outline-primary" data-tab="analisa" onclick="switchTab('analisa')">Analisa</button>
        
    </div>

    <!-- SIMULASI -->
    <section id="tab-simulasi" style="display: <?php echo $tab==='simulasi'?'block':'none'; ?>;">
        <form method="post" class="grid">
            <input type="hidden" name="tab" value="simulasi" />
            <div class="col-6 card">
                <h3>Input Simulasi</h3>
                <div>
                    <label>Nominal Pinjaman (Rp)</label>
                    <input class="rupiah" type="text" name="nominal" value="<?php echo htmlspecialchars($simulasi['nominal']); ?>" required />
                </div>
                <div>
                    <label>Jangka Waktu (bulan)</label>
                    <input type="number" name="tenor" min="1" value="<?php echo htmlspecialchars($simulasi['tenor']); ?>" required />
                </div>
                <div>
                    <label>Bunga per Tahun (%)</label>
                    <input type="text" inputmode="decimal" name="bunga" value="<?php echo htmlspecialchars($simulasi['bunga']); ?>" required />
                </div>
                <div>
                    <label>Penghasilan per Bulan (opsional)</label>
                    <input class="rupiah" type="text" name="penghasilan" value="<?php echo htmlspecialchars($simulasi['penghasilan']); ?>" />
                    <div class="muted">Diisi untuk menghitung rasio cicilan terhadap penghasilan (batas wajar 30%).</div>
                </div>
                <div>
                    <label>Metode</label>
                    <select name="metode">
                        <?php foreach(['Anuitas','Efektif','Flat'] as $m): ?>
                            <option value="<?php echo $m; ?>" <?php echo $simulasi['metode']===$m?'selected':''; ?>><?php echo $m; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit" name="hitung_simulasi">Hitung</button>
                    <button class="btn btn-ghost" type="reset" onclick="resetRupiah(this.form)">Reset</button>
                </div>
            </div>
            <div class="col-6 card">
                <h3>Hasil</h3>
                <?php if (!empty($simulasi['hasil'])): ?>
                    <div class="result"><?php echo $simulasi['hasil']; ?></div>
                <?php else: ?>
                    <div class="muted">Isi form lalu klik <b>Hitung</b> untuk melihat hasil.</div>
                <?php endif; ?>
                <hr />
                <details>
                    <summary><b>Keterangan Pendapatan</b> (klik untuk buka)</summary>
                    <div class="muted">• Pendapatan kotor/bulan • Modal yang diperlukan • Penjualan per hari / minggu / bulan</div>
                </details>
                <details style="margin-top:8px;">
                    <summary><b>Keterangan Biaya</b> (klik untuk buka)</summary>
                    <div class="muted">Biaya: listrik, air, telp, internet, tenaga kerja, pinjaman tempat lain, makan minum</div>
                </details>
            </div>
        </form>
    </section>

    <!-- ANALISA -->
    <section id="tab-analisa" style="display: <?php echo $tab==='analisa'?'block':'none'; ?>;">
        <form method="post" class="grid">
            <input type="hidden" name="tab" value="analisa" />
            <div class="col-6 card">
                <h3>Input Analisa</h3>
                <div>
                    <label>Pendapatan (Rp)</label>
                    <input class="rupiah" type="text" name="pendapatan" value="<?php echo htmlspecialchars($analisa['pendapatan']); ?>" />
                </div>
                <div>
                    <label>Biaya Operasional/Hidup (Rp)</label>
                    <input class="rupiah" type="text" name="biaya" value="<?php echo htmlspecialchars($analisa['biaya']); ?>" />
                </div>
                <div>
                    <label>Persentase Risiko Usaha (%) (maks 30%)</label>
                    <input type="number" name="risikoPersen" min="0" max="30" value="<?php echo htmlspecialchars($analisa['risikoPersen']); ?>" />
                </div>
                <div>
                    <label>Persentase Saving (%) (default 80%)</label>
                    <input type="number" name="savingPersen" min="0" value="<?php echo htmlspecialchars($analisa['savingPersen']); ?>" />
                </div>
                <div>
                    <label>Nilai Pengajuan Pinjaman (Rp)</label>
                    <input class="rupiah" type="text" name="pinjaman" value="<?php echo htmlspecialchars($analisa['pinjaman']); ?>" />
                </div>
                <div>
                    <label>Jangka Waktu (bulan)</label>
                    <input type="number" name="bulan" min="1" value="<?php echo htmlspecialchars($analisa['bulan']); ?>" />
                </div>
                <div>
                    <label>Bunga Pinjaman per Tahun (%)</label>
                    <input type="text" inputmode="decimal" name="bunga" value="<?php echo htmlspecialchars($analisa['bunga']); ?>" />
                </div>
                <div>
                    <label>Metode</label>
                    <select name="metodeAnalisa">
                        <?php foreach(['Anuitas','Flat','Efektif'] as $m): ?>
                            <option value="<?php echo $m; ?>" <?php echo $analisa['metode']===$m?'selected':''; ?>><?php echo $m; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-primary" type="submit" name="hitung_analisa">Hitung</button>
                    <button class="btn btn-ghost" type="reset" onclick="resetRupiah(this.form)">Reset</button>
                </div>
            </div>

            <div class="col-6 card">
                <h3>Hasil Analisa</h3>
                <div class="tile"><span>Risiko Usaha (Rp)</span><strong><?php echo formatRupiah($analisa['risikoUsaha']); ?></strong></div>
                <div class="tile"><span>Pendapatan Bersih = Pendapatan - Biaya - Risiko</span><strong><?php echo formatRupiah($analisa['pendapatanBersih']); ?></strong></div>
                <div class="tile"><span>Saving (Rp) = % x Pendapatan Bersih</span><strong><?php echo formatRupiah($analisa['savingNominal']); ?></strong></div>
                <div class="tile"><span>Kewajiban/Bulan (<?php echo htmlspecialchars($analisa['metode']); ?>)</span><strong><?php echo formatRupiah($analisa['kewajibanBulanan']); ?></strong></div>
                <div class="tile">
                    <span>Rasio Angsuran</span>
                    <strong><?php echo number_format($analisa['rasioAngsuran'],1,',','.'); ?>%</strong>
                </div>
                <div class="tile">
                    <span>Konsumtif</span>
                    <span class="badge <?php echo ($analisa['statusKonsumtif']==='Tidak layak')?'red':'blue'; ?>"><?php echo $analisa['statusKonsumtif']; ?></span>
                </div>
                <div class="tile">
                    <span>Produktif</span>
                    <?php
                        $cls = 'blue';
                        if (stripos($analisa['statusProduktif'],'Dipertimbangkan')!==false) $cls='orange';
                        if (stripos($analisa['statusProduktif'],'Tidak layak')!==false) $cls='red';
                    ?>
                    <span class="badge <?php echo $cls; ?>"><?php echo $analisa['statusProduktif']; ?></span>
                </div>
                <div class="tile"><span>RRC</span><strong><?php echo number_format($analisa['rrc'],1,',','.'); ?>%</strong></div>
                <div class="tile">
                    <span>Status RRC</span>
                    <span class="badge <?php echo ($analisa['statusRRC']==='Risiko')?'red':(($analisa['statusRRC']==='Cukup aman')?'orange':'blue'); ?>"><?php echo $analisa['statusRRC']; ?></span>
                </div>
                <div class="tile"><span>Angsuran Maks Konsumtif (30%)</span><strong><?php echo formatRupiah($analisa['angsMaxKonsum']); ?></strong></div>
                <div class="tile"><span>Angsuran Maks Produktif (45%)</span><strong><?php echo formatRupiah($analisa['angsMaxProd']); ?></strong></div>
                <div class="tile"><span>Pinjaman Maks Produktif (Anuitas)</span><strong><?php echo formatRupiah($analisa['pinjMaxAnuitas']); ?></strong></div>
                <div class="tile"><span>Pinjaman Maks Produktif (Flat)</span><strong><?php echo formatRupiah($analisa['pinjMaxFlat']); ?></strong></div>
                <hr />
                <details>
                    <summary><b>Keterangan Pendapatan</b> (klik untuk buka)</summary>
                    <div class="muted">• Pendapatan kotor/bulan • Modal yang diperlukan • Penjualan per hari / minggu / bulan</div>
                </details>
                <details style="margin-top:8px;">
                    <summary><b>Keterangan Biaya</b> (klik untuk buka)</summary>
                    <div class="muted">Biaya: listrik, air, telp, internet, tenaga kerja, pinjaman tempat lain, makan minum</div>
                </details>
            </div>
        </form>
    </section>

    <!-- hitung -->
    <section id="tab-hitung" style="display: <?php echo $tab==='hitung'?'block':'none'; ?>;">
        <form method="post" class="grid" onsubmit="resetRupiah(this)">
            <input type="hidden" name="tab" value="hitung" />
            <div class="col-6 card">
            <div class="card shadow-lg">
                <div class="card-header bg-primary text-white text-center">
                <h4>💰 Kalkulator Pinjaman Maksimal (Flat & Anuitas)</h4>
                </div>
                <div class="card-body">
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                    <label class="form-label">Penghasilan Bersih per Bulan (Rp)</label>
                    <input class="rupiah form-control" type="text" name="penghasilan"
                        value="<?php echo htmlspecialchars($_POST['penghasilan'] ?? ''); ?>" required />
                    </div>
                    <div class="col-md-6">
                    <label class="form-label">Rasio Maksimal Angsuran (%)</label>
                    <input type="number" name="rasio" class="form-control" required placeholder="contoh: 30"
                        value="<?= $_POST['rasio'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                    <label class="form-label">Bunga per Tahun (%)</label>
                    <input type="number" name="bunga" step="0.01" class="form-control" required placeholder="contoh: 12"
                        value="<?= $_POST['bunga'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                    <label class="form-label">Lama Pinjaman (bulan)</label>
                    <input type="number" name="tenor" class="form-control" required placeholder="contoh: 24"
                        value="<?= $_POST['tenor'] ?? '' ?>">
                    </div>
                    <div class="col-12 text-center">
                    <button type="submit" class="btn btn-success px-4 mt-3">Hitung</button>
                    </div>
                </form>
                <hr>

                <?php
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    // Ambil & bersihkan input rupiah
                    $penghasilan_input = $_POST['penghasilan'] ?? '0';
                    $penghasilan = floatval(preg_replace('/[^0-9]/', '', $penghasilan_input));
                    $rasio = floatval($_POST['rasio']) / 100;
                    $bunga_tahun = floatval($_POST['bunga']) / 100;
                    $tenor = intval($_POST['tenor']);

                    // Hitung kemampuan bayar
                    $angsuran_maks = $penghasilan * $rasio;

                    // Bunga per bulan
                    $bunga_bulan = $bunga_tahun / 12;

                    // Metode Flat
                    $pinjaman_flat = $angsuran_maks / ((1 / $tenor) + $bunga_bulan);

                    // Metode Anuitas
                    $pinjaman_anuitas = $angsuran_maks / (($bunga_bulan * pow(1 + $bunga_bulan, $tenor)) / (pow(1 + $bunga_bulan, $tenor) - 1));
                ?>
                <div class="row mt-4">
                    <div class="col-md-6">
                    <div class="card border-info shadow-sm">
                        <div class="card-header bg-info text-white text-center fw-bold">Metode Flat</div>
                        <div class="card-body text-center">
                        <p>Pinjaman Maksimum:</p>
                        <h3 class="text-success fw-bold">
                            Rp <?= number_format($pinjaman_flat, 0, ',', '.') ?>
                        </h3>
                        <p class="text-muted">Bunga flat <?= $_POST['bunga'] ?>% per tahun selama <?= $tenor ?> bulan</p>
                        </div>
                    </div>
                    </div>

                    <div class="col-md-6">
                    <div class="card border-primary shadow-sm">
                        <div class="card-header bg-primary text-white text-center fw-bold">Metode Anuitas</div>
                        <div class="card-body text-center">
                        <p>Pinjaman Maksimum:</p>
                        <h3 class="text-success fw-bold">
                            Rp <?= number_format($pinjaman_anuitas, 0, ',', '.') ?>
                        </h3>
                        <p class="text-muted">Bunga anuitas <?= $_POST['bunga'] ?>% per tahun selama <?= $tenor ?> bulan</p>
                        </div>
                    </div>
                    </div>
                </div>

          <div class="alert alert-secondary mt-4">
            <h6>💡 Catatan:</h6>
            <ul>
              <li>Hasil ini sebagai estimasi maksimum pinjaman yang dapat diberikan berdasarkan kemampuan membayar.</li>
            </ul>
          </div>
          <?php } ?>
        </div>
      </div>
    </div>

                <!-- Tambahkan sedikit styling tambahan -->
                <style>
                body {
                    background-color: #f8f9fa;
                }
                .card {
                    border-radius: 15px;
                }
                h4 {
                    font-weight: 600;
                }
                </style>
    <p class="muted" style="margin-top:16px; text-align:center;">Created by donnyprayoga</p>
</div>

<script>
// Tab switch (no reload needed)
function switchTab(name) {
    // 1️⃣ Sembunyikan semua konten tab
    document.querySelectorAll('[id^="tab-"]').forEach(tab => {
        tab.style.display = 'none';
    });

    // 2️⃣ Tampilkan tab yang diklik
    const target = document.getElementById('tab-' + name);
    if (target) target.style.display = 'block';

    // 3️⃣ Hapus class 'active' dari semua tombol tab
    document.querySelectorAll('.tab').forEach(btn => {
        btn.classList.remove('active');
    });

    // 4️⃣ Tambahkan class 'active' ke tab yang diklik
    const activeTab = document.querySelector(`.tab[data-tab="${name}"]`);
    if (activeTab) activeTab.classList.add('active');

    // 5️⃣ Simpan tab terakhir di localStorage (opsional)
    localStorage.setItem('activeTab', name);
}

// 6️⃣ Saat halaman dimuat, tampilkan tab terakhir yang disimpan
document.addEventListener('DOMContentLoaded', function() {
    const lastTab = localStorage.getItem('activeTab') || 'hitung';
    switchTab(lastTab);
});

// Format Rupiah saat mengetik (client-side)
function onlyDigits(s){ return s.replace(/[^0-9]/g,''); }
function toRupiah(n){
    if(!n) return '';
    return 'Rp' + Number(n).toLocaleString('id-ID');
}
function bindRupiahInputs(){
    document.querySelectorAll('input.rupiah').forEach(function(inp){
        inp.addEventListener('input', function(e){
            var pos = inp.selectionStart;
            var clean = onlyDigits(inp.value);
            inp.value = toRupiah(clean);
            // caret to end (simple approach)
            inp.setSelectionRange(inp.value.length, inp.value.length);
        });
        // On focus, if empty show 'Rp'
        inp.addEventListener('focus', function(){ if(inp.value==='') inp.value='Rp'; });
        inp.addEventListener('blur', function(){ if(inp.value==='Rp') inp.value=''; });
    });
}
function resetRupiah(form){
    setTimeout(function(){ bindRupiahInputs(); }, 0);
}
bindRupiahInputs();
</script>
</body>
</html>
