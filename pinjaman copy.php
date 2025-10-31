<?php
// pinjaman_reminder.php

include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$hari = date("d");
if ($hari == "01") {
    $conn->query("UPDATE pinjaman SET sms = 0");
}

$sql = "
SELECT 
    p.id,
    a.nama AS nama_anggota,
    a.nohp AS no_telp,
    p.nopinjaman,
    p.tanggal,
    p.plafon,
    p.angsuran,
    p.nangsuran AS kewajiban,
    p.jangkawaktu,
    p.bunga,
    p.sms
FROM pinjaman p
JOIN anggota a ON p.anggotaid = a.id
ORDER BY p.tanggal ASC
";
$result = $conn->query($sql);

function normalize_phone($raw) {
    $digits = preg_replace('/\D+/', '', (string)$raw);
    if ($digits === '') return '';
    if (strpos($digits, '0') === 0) return '62' . substr($digits, 1);
    if (strpos($digits, '62') === 0) return $digits;
    if (strpos($digits, '8') === 0) return '62' . $digits;
    return $digits;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Reminder Pinjaman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<style>
  body { background:#f4f6fb; }
  table { font-size: 12px !important; }
  button { font-size: 11px !important; }
  .table thead { background:#2563eb; color:#fff; font-size: 12px; }
  .btn-print { background:#10b981; color:#fff; border:none; padding:6px 12px; border-radius:6px; }
  .btn-print:hover { background:#0d946b; color:#fff; }
  @media print {
  body * { visibility: hidden !important; }
  #printPreview, #printPreview * { visibility: visible !important; }
  #printPreview {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    padding: 25px;
    background: white;
    font-size: 12px;
    color: #000;
  }

  h4, h5 {
    margin: 0;
    text-align: center;
  }

  hr {
    border: 1px solid #000;
    margin: 8px 0 16px 0;
  }

  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 8px;
  }

  th, td {
    border: 1px solid #000;
    padding: 6px 8px;
    font-size: 12px;
  }

  th {
    background: #e8e8e8;
    text-align: center;
  }

  td.text-right {
    text-align: right;
  }

  td.text-center {
    text-align: center;
  }

  td.text-left {
    text-align: left;
  }
}
</style>
</head>
<body>
<div class="container my-4">
  <h2 class="text-center text-primary mb-4">📋 Reminder Pinjaman Anggota</h2>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">Daftar Pinjaman Jatuh Tempo</div>
    <div class="card-body table-responsive p-0">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead>
          <tr class="text-center">
            <th>No</th>
            <th>Nama Anggota</th>
            <th>No. HP</th>
            <th>No. Pinjaman</th>
            <th>Tanggal Pinjaman</th>
            <th>Plafon</th>
            <th>Bunga/tahun</th>
            <th>Metode</th>
            <th>Jangka Waktu</th>
            <th>Kewajiban</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
<?php if ($result && $result->num_rows > 0): ?>
<?php $no = 1; while ($row = $result->fetch_assoc()): $nohp = normalize_phone($row['no_telp']); ?>
<tr class="text-center">
  <td><?= $no++ ?></td>
  <td class="text-start"><?= htmlspecialchars($row['nama_anggota']) ?></td>
  <td><?= htmlspecialchars($nohp) ?></td>
  <td><?= htmlspecialchars($row['nopinjaman']) ?></td>
  <td><?= date("d-M-Y", strtotime($row['tanggal'])) ?></td>
  <td class="text-end">Rp <?= number_format($row['plafon'],0,",",".") ?></td>
  <td><?= $row['bunga'] ?>%</td>
  <td><?= ucfirst($row['angsuran']) ?></td>
  <td><?= $row['jangkawaktu'] ?> bln</td>
  <td class="text-end text-success"><b>Rp <?= number_format($row['kewajiban'],0,",",".") ?></b></td>
  <td>
      <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
          onclick="showSchedule(
              '<?= htmlspecialchars($row['nama_anggota'], ENT_QUOTES) ?>',
              '<?= htmlspecialchars($row['nopinjaman'], ENT_QUOTES) ?>',
              <?= $row['plafon'] ?>,
              <?= $row['bunga'] ?>,
              <?= $row['jangkawaktu'] ?>,
              '<?= htmlspecialchars($row['angsuran'], ENT_QUOTES) ?>',
              <?= $row['kewajiban'] ?>,
              '<?= $row['tanggal'] ?>'
          )">
          🗓️ Jadwal
      </button>
  </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="11" class="text-center py-4">Tidak ada data</td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Jadwal Angsuran -->
<div class="modal fade" id="scheduleModal" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Jadwal Angsuran Pinjaman</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="printArea">
        <div id="scheduleContent"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-print" onclick="printSchedule()">🖨️ Print</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
// === Hitung Jadwal Angsuran dengan metode Anuitas & Flat ===
function showSchedule(nama, noPinjaman, plafon, bunga, bulan, metode, kewajiban, startDate) {
    const modal = new bootstrap.Modal(document.getElementById('scheduleModal'));
    const interest = (bunga / 100) / 12; // bunga per tahun → bulanan
    const angsuran_bulan = Math.ceil(kewajiban);
    let sisa = plafon;
    let totalPokok = 0, totalBunga = 0, totalAngsuran = 0;

    let html = `
    <div id="printPreview">
      <h4><b>KOPERASI SIMPAN PINJAM MURNI DAMAI PUTRA</b></h4>
      <p style="text-align:center;margin:0;">Jl. Contoh No. 123, Jakarta</p>
      <p style="text-align:center;margin:0;">Telp: (021) 555-1234</p>
      <hr>
      <h5><b>JADWAL ANGSURAN PINJAMAN</b></h5>

      <table style="width:60%; margin-bottom:10px;">
        <tr><th style="width:35%;">Nama Anggota</th><td>${nama}</td></tr>
        <tr><th>No. Pinjaman</th><td>${noPinjaman}</td></tr>
        <tr><th>Tanggal Mulai</th><td>${startDate}</td></tr>
        <tr><th>Plafon</th><td>Rp ${plafon.toLocaleString('id-ID')}</td></tr>
        <tr><th>Metode</th><td>${metode.toUpperCase()}</td></tr>
        <tr><th>Bunga</th><td>${bunga}% per tahun (${(interest*100).toFixed(3)}% per bulan)</td></tr>
        <tr><th>Jangka Waktu</th><td>${bulan} bulan</td></tr>
        <tr><th>Angsuran/bulan</th><td><b>Rp ${angsuran_bulan.toLocaleString('id-ID')}</b></td></tr>
      </table>

      <table>
        <thead>
          <tr>
            <th style="width:7%;">Bulan</th>
            <th style="width:17%;">Tanggal Jatuh Tempo</th>
            <th style="width:19%;">Pokok</th>
            <th style="width:19%;">Bunga</th>
            <th style="width:19%;">Total Angsuran</th>
            <th style="width:19%;">Sisa Saldo</th>
          </tr>
        </thead>
        <tbody>`;

    for (let i = 1; i <= bulan; i++) {
        let bungaBulan = sisa * interest;
        let pokok;

        if (metode.toLowerCase() === "anuitas") {
            pokok = angsuran_bulan - bungaBulan;
        } else {
            pokok = plafon / bulan;
            bungaBulan = plafon * interest;
        }

        if (pokok > sisa) pokok = sisa;
        sisa -= pokok;
        if (sisa < 1e-6) sisa = 0;

        totalPokok += pokok;
        totalBunga += bungaBulan;
        totalAngsuran += angsuran_bulan;

        const dueDate = new Date(startDate);
        dueDate.setMonth(dueDate.getMonth() + i);
        const tanggalFormat = dueDate.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });

        html += `
          <tr>
              <td class="text-center">${i}</td>
              <td class="text-center">${tanggalFormat}</td>
              <td class="text-right">Rp ${Math.round(pokok).toLocaleString('id-ID')}</td>
              <td class="text-right">Rp ${Math.round(bungaBulan).toLocaleString('id-ID')}</td>
              <td class="text-right fw-bold">Rp ${Math.round(angsuran_bulan).toLocaleString('id-ID')}</td>
              <td class="text-right">Rp ${Math.round(sisa).toLocaleString('id-ID')}</td>
          </tr>`;
    }

    html += `
        </tbody>
        <tfoot>
          <tr style="font-weight:bold;">
            <td colspan="2" class="text-center">TOTAL</td>
            <td class="text-right">Rp ${Math.round(totalPokok).toLocaleString('id-ID')}</td>
            <td class="text-right">Rp ${Math.round(totalBunga).toLocaleString('id-ID')}</td>
            <td class="text-right">Rp ${Math.round(totalAngsuran).toLocaleString('id-ID')}</td>
            <td class="text-right">-</td>
          </tr>
        </tfoot>
      </table>

      <div style="margin-top:50px;text-align:right;">
        <p>Dicetak pada: ${new Date().toLocaleDateString('id-ID')}</p>
        <p><b>Petugas Koperasi</b></p><br><br>
        <p>__________________________</p>
      </div>
    </div>`;

    document.getElementById('scheduleContent').innerHTML = html;
    modal.show();
}

function printSchedule() {
    const printContents = document.querySelector('#scheduleModal .modal-body').innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}
</script>
</body>
</html>
<?php $conn->close(); ?>
