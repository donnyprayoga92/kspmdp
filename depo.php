<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// Inisialisasi variabel
$nominal = $suku_bunga = $jangka_waktu = 0;
$metode = '';
$bunga_kotor = $pajak = $bunga_bersih = $total_bunga_bersih = null;
$nominal_awal = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simpan nominal awal
    $nominal_awal = preg_replace('/[^0-9]/', '', $_POST['nominal']); 
    $nominal_awal = floatval($nominal_awal);

    $nominal = $nominal_awal; // buat perhitungan
    $suku_bunga = floatval($_POST['suku_bunga']);
    $jangka_waktu = intval($_POST['jangka_waktu']);
    $metode = $_POST['metode'] ?? '';

    if ($metode === "biasa") {
        // Hitung bunga per bulan (pokok tetap)
        $bunga_kotor = ($nominal * ($suku_bunga / 100)) / 12;
        $pajak = ($bunga_kotor > 240000) ? (0.10 * $bunga_kotor) : 0;
        $bunga_bersih = $bunga_kotor - $pajak;
        $total_bunga_bersih = $bunga_bersih * $jangka_waktu;
    } elseif ($metode === "rollover") {
        // Roll over: bunga ditambahkan ke pokok tiap bulan
        $pokok = $nominal;
        $total_bunga_bersih = 0;

        for ($i = 1; $i <= $jangka_waktu; $i++) {
            $bunga_kotor = ($pokok * ($suku_bunga / 100)) / 12;
            $pajak = ($bunga_kotor > 240000) ? (0.10 * $bunga_kotor) : 0;
            $bunga_bersih = $bunga_kotor - $pajak;
            $total_bunga_bersih += $bunga_bersih;
            $pokok += $bunga_bersih;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kalkulator Deposito</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f5f7fa;
        }
        .card {
            border-radius: 12px;
        }
        .result-card {
            background: linear-gradient(135deg, #0d6efd, #6610f2);
            color: white;
            border: none;
        }
        .list-group-item {
            background: transparent;
            border: none;
            color: white;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-lg">
                <div class="card-body p-4">
                    <h3 class="card-title text-center mb-4">💰 Kalkulator Deposito</h3>
                    <form method="post">
                        <div class="mb-3">
                            <label for="nominal" class="form-label">Nominal Deposito (Rp)</label>
                            <input type="text" class="form-control" id="nominal" name="nominal" required 
                                   value="<?= $nominal_awal ? number_format($nominal_awal, 0, ',', '.') : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="suku_bunga" class="form-label">Suku Bunga (%) per Tahun</label>
                            <input type="number" class="form-control" id="suku_bunga" name="suku_bunga" step="0.01" required 
                                   value="<?= $suku_bunga ?: '' ?>">
                        </div>
                        <div class="mb-3">
                            <label for="jangka_waktu" class="form-label">Jangka Waktu (bulan)</label>
                            <select class="form-select" id="jangka_waktu" name="jangka_waktu" required>
                                <option value="" disabled <?= $jangka_waktu ? '' : 'selected' ?>>-- Pilih --</option>
                                <option value="1" <?= $jangka_waktu == 1 ? 'selected' : '' ?>>1 Bulan</option>
                                <option value="3" <?= $jangka_waktu == 3 ? 'selected' : '' ?>>3 Bulan</option>
                                <option value="6" <?= $jangka_waktu == 6 ? 'selected' : '' ?>>6 Bulan</option>
                                <option value="12" <?= $jangka_waktu == 12 ? 'selected' : '' ?>>12 Bulan</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Metode Perhitungan</label>
                            <select class="form-select" name="metode" required>
                                <option value="" disabled <?= $metode ? '' : 'selected' ?>>-- Pilih --</option>
                                <option value="biasa" <?= $metode == "biasa" ? 'selected' : '' ?>>Biasa (pokok tetap)</option>
                                <option value="rollover" <?= $metode == "rollover" ? 'selected' : '' ?>>Roll Over (bunga berbunga)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Hitung</button>
                    </form>
                </div>
            </div>

            <?php if ($bunga_kotor !== null): ?>
                <div class="card shadow-lg result-card mt-4">
                    <div class="card-body">
                        <h5 class="text-center mb-3">📊 Hasil Perhitungan</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item bg-transparent text-white d-flex justify-content-between">
                                <span>Bunga Kotor (per bulan)</span>
                                <strong>Rp<?= number_format($bunga_kotor, 0, ',', '.') ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent text-white d-flex justify-content-between">
                                <span>Pajak Final (10%)</span>
                                <strong>Rp<?= number_format($pajak, 0, ',', '.') ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent text-white d-flex justify-content-between">
                                <span>Bunga Bersih (per bulan)</span>
                                <strong>Rp<?= number_format($bunga_bersih, 0, ',', '.') ?></strong>
                            </li>
                            <li class="list-group-item bg-transparent text-white d-flex justify-content-between">
                                <span>Total Bunga Bersih (<?= $jangka_waktu ?> bulan)</span>
                                <strong>Rp<?= number_format($total_bunga_bersih, 0, ',', '.') ?></strong>
                            </li>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Format input jadi Rupiah
const nominalInput = document.getElementById('nominal');
nominalInput.addEventListener('input', function() {
    let value = this.value.replace(/\D/g, '');
    this.value = new Intl.NumberFormat('id-ID').format(value);
});
</script>
</body>
</html>
