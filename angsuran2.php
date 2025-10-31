<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Simulasi Angsuran Kewajiban</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">

  <!-- Tombol Kembali -->
  <a href="index.php" class="btn btn-secondary mb-3">← Kembali</a>

  <!-- Card Form -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h2 class="text-center text-primary mb-4">Simulasi Angsuran Kewajiban Pokok dan Bunga</h2>

      <!-- Input Form -->
      <div class="mb-3">
        <label for="jumlahPinjaman" class="form-label fw-semibold">Jumlah Pinjaman</label>
        <input type="text" id="jumlahPinjaman" class="form-control" placeholder="Contoh: 10.000.000" oninput="formatInputRupiah(this)">
      </div>

      <div class="mb-3">
        <label for="tenor" class="form-label fw-semibold">Jangka Waktu (bulan)</label>
        <input type="number" id="tenor" class="form-control" placeholder="Contoh: 12">
      </div>

      <div class="mb-3">
        <label for="bungaTahunan" class="form-label fw-semibold">Suku Bunga per Tahun (%)</label>
        <input type="number" id="bungaTahunan" class="form-control" step="0.01" placeholder="Contoh: 12">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Simulasi Perbandingan Metode Angsuran</label>
        <div>
          <button onclick="hitungAngsuran()" class="btn btn-primary me-2">Bandingkan Metode</button>
          <button onclick="resetForm()" class="btn btn-danger">Reset</button>
        </div>
      </div>

      <!-- Output -->
      <div id="output"></div>
    </div>
  </div>

  <!-- Footer -->
  <div class="text-center text-muted small mt-4">
    &copy; 2025 Simulasi Angsuran KSP Mitra Dana Persada
  </div>
</div>

<script>
  function formatRupiah(angka) {
    return angka.toLocaleString("id-ID", { style: "currency", currency: "IDR" });
  }

  function parseRupiah(text) {
    return parseFloat(text.replace(/[^\d]/g, '')) || 0;
  }

  function formatInputRupiah(el) {
    const value = el.value.replace(/[^\d]/g, '');
    if (!value) { el.value = ''; return; }
    el.value = new Intl.NumberFormat('id-ID').format(value);
  }

  function hitungAngsuran() {
    const jumlahRaw = document.getElementById("jumlahPinjaman").value;
    const jumlah = parseRupiah(jumlahRaw);
    const tenor = parseInt(document.getElementById("tenor").value);
    const bungaTahunan = parseFloat(document.getElementById("bungaTahunan").value);

    if (!jumlah || isNaN(tenor) || isNaN(bungaTahunan)) {
      alert("Silakan isi semua input dengan benar.");
      return;
    }

    const bungaBulanan = bungaTahunan / 12 / 100;
    const metodeList = ["anuitas", "flat", "flatEfektif", "pokokTetap", "bungaMenurun"];
    const namaMetode = {
      anuitas: "Anuitas",
      flat: "Flat",
      flatEfektif: "Flat Efektif",
      pokokTetap: "Pokok Tetap",
      bungaMenurun: "Bunga Menurun"
    };

    let fullOutput = "";

    metodeList.forEach((metode) => {
      let sisa = jumlah;
      let totalPokok = 0, totalBunga = 0, totalAngsuran = 0;

      let output = `
        <h4 class="mt-4 text-secondary">${namaMetode[metode]}</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-sm align-middle">
            <thead class="table-dark">
              <tr>
                <th>Bulan</th>
                <th>Pokok</th>
                <th>Bunga</th>
                <th>Total Angsuran</th>
                <th>Sisa Pinjaman</th>
              </tr>
            </thead>
            <tbody>
      `;

      for (let i = 1; i <= tenor; i++) {
        let bunga = 0, pokok = 0, angsuran = 0;

        switch (metode) {
          case "anuitas":
            angsuran = jumlah * bungaBulanan / (1 - Math.pow(1 + bungaBulanan, -tenor));
            bunga = sisa * bungaBulanan;
            pokok = angsuran - bunga;
            break;
          case "flat":
            pokok = jumlah / tenor;
            bunga = jumlah * bungaBulanan;
            angsuran = pokok + bunga;
            break;
          case "flatEfektif":
            pokok = jumlah / tenor;
            bunga = sisa * bungaBulanan;
            angsuran = pokok + bunga;
            break;
          case "pokokTetap":
            pokok = jumlah / tenor;
            bunga = (i === 1) ? jumlah * bungaBulanan : 0;
            angsuran = pokok + bunga;
            break;
          case "bungaMenurun":
            bunga = sisa * bungaBulanan;
            pokok = jumlah / tenor;
            angsuran = pokok + bunga;
            break;
        }

        totalPokok += pokok;
        totalBunga += bunga;
        totalAngsuran += angsuran;
        sisa -= pokok;

        output += `
          <tr>
            <td>${i}</td>
            <td class="text-end">${formatRupiah(pokok)}</td>
            <td class="text-end">${formatRupiah(bunga)}</td>
            <td class="text-end">${formatRupiah(angsuran)}</td>
            <td class="text-end">${formatRupiah(Math.max(0, sisa))}</td>
          </tr>
        `;
      }

      output += `
            </tbody>
          </table>
        </div>
        <div class="alert alert-info">
          <strong>Total Pokok:</strong> ${formatRupiah(totalPokok)}<br>
          <strong>Total Bunga:</strong> ${formatRupiah(totalBunga)}<br>
          <strong>Total Angsuran:</strong> ${formatRupiah(totalAngsuran)}
        </div>
      `;

      fullOutput += output;
    });

    document.getElementById("output").innerHTML = fullOutput;
  }

  function resetForm() {
    document.getElementById("jumlahPinjaman").value = "";
    document.getElementById("tenor").value = "";
    document.getElementById("bungaTahunan").value = "";
    document.getElementById("output").innerHTML = "";
  }
</script>
</body>
</html>
