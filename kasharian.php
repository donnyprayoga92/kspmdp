<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$tanggal = isset($_GET['tanggal']) ? $_GET['tanggal'] : date('Y-m-d');

// Kas Awal
$kasAwalQuery = mysqli_query($conn, "SELECT nominal, TIME(jam) AS jam FROM kasawal WHERE tanggal = '$tanggal'");
$kasAwal = mysqli_fetch_assoc($kasAwalQuery);
$kasAwalNominal = $kasAwal ? $kasAwal['nominal'] : 0;
$kasAwalJam = $kasAwal ? $kasAwal['jam'] : '';

// Query Tabungan
$tabunganQuery = mysqli_query($conn, "
    SELECT 
        t.nobukti,
        CONCAT(t.keterangan, ' - ', tb.norekening, ' - ', ag.nama) AS uraian,
        t.kredit AS debet,
        t.debet AS kredit,
        TIME(t.jam) AS jam
    FROM tabtransaksi t
    JOIN tabungan tb ON t.tabunganid = tb.id
    JOIN anggota ag ON tb.anggotaid = ag.id
    WHERE t.tanggal = '$tanggal' AND t.user = 'tl1' 
      AND NOT(t.kodeid = 'ADM20250819-103655' OR t.kodeid = 'ADM20250825-081119')
");

// Query Kas Keluar
$kasKeluarQuery = mysqli_query($conn, "
    SELECT keterangan AS uraian, nominal AS kredit, TIME(jam) AS jam
    FROM kaskeluar
    WHERE tanggal = '$tanggal' AND user = 'tl1'
");

// Query Kas Masuk
$kasmasukQuery = mysqli_query($conn, "
    SELECT keterangan AS uraian, nominal AS debet, TIME(jam) AS jam
    FROM kasmasuk
    WHERE tanggal = '$tanggal' AND user = 'tl1'
");

// Ambil total kas masuk
$kasMasukQueryTotal = mysqli_query($conn, "
    SELECT SUM(nominal) AS nominal FROM kasmasuk 
    WHERE tanggal = '$tanggal' AND user = 'tl1'
");
$kasMasuk = mysqli_fetch_assoc($kasMasukQueryTotal);
$kasMasukNominal = $kasMasuk ? $kasMasuk['nominal'] : 0;

// Cek apakah tanggal ini akhir bulan
$isAkhirBulan = (date('Y-m-d', strtotime($tanggal)) === date('Y-m-t', strtotime($tanggal)));


// Query Pendapatan
$pendapatanQuery = mysqli_query($conn, "
    SELECT 
        j.id AS nomor_jurnal,
        j.keterangan AS uraian,
        jd.kredit AS debet,
        jd.debet AS kredit,
        TIME(j.jam) AS jam
    FROM accjurnaldetail jd
    JOIN accjurnal j ON jd.id = j.id
    WHERE jd.accountid IN ('940-03', '910-01') 
      AND j.tanggal = '$tanggal'
      " . ($isAkhirBulan ? "AND jd.accountid NOT IN ('940-03', '910-01')" : "AND jd.accountid IN ('940-03', '910-01')") . " 
      
");

// Inisialisasi Saldo Awal
$saldo = $kasAwalNominal;
$total_debet = $kasAwalNominal;
$total_kredit = 0;

// Subtotal
$subtotal_tabungan_debet = 0;
$subtotal_tabungan_kredit = 0;
$subtotal_kaskeluar = 0;
$subtotal_pendapatan_debet = 0;
$subtotal_pendapatan_kredit = 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Transaksi Kas Harian</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f8f9fa; font-size:13px; }
    .table thead { background:#2563eb; color:white; }
    .subtotal { background:#e9ecef; font-weight:bold; }
    .grandtotal { background:#cfe2ff; font-weight:bold; }
    @media print {
      .no-print { display:none; }
    }
  </style>
</head>
<body>
<div class="container my-4">
  <div class="text-center mb-4">
    <h4 class="fw-bold text-primary">KSP Mitra Dana Persada</h4>
    <h5>Laporan Transaksi Kas Harian</h5>
    <p class="text-muted">Tanggal: <?= date('d-m-Y', strtotime($tanggal)) ?></p>
  </div>

  <!-- Form Pilih Tanggal -->
  <form method="GET" class="row g-2 justify-content-center no-print mb-3">
    <div class="col-auto">
      <input type="date" class="form-control" id="tanggal" name="tanggal" value="<?= $tanggal ?>">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Tampilkan</button>
      <button type="button" onclick="window.print()" class="btn btn-outline-secondary">🖨 Cetak</button>
    </div>
  </form>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table class="table table-bordered table-hover align-middle">
        <thead class="text-center">
          <tr>
            <th>Jam</th>
            <th>No. Bukti</th>
            <th>Uraian</th>
            <th class="text-end">Debet</th>
            <th class="text-end">Kredit</th>
            <th class="text-end">Saldo</th>
          </tr>
        </thead>
        <tbody>
          <!-- Kas Awal -->
          <tr>
            <td><?= $kasAwalJam ?></td>
            <td></td>
            <td><span class="fw-bold">Saldo Awal Kas</span></td>
            <td class="text-end"><?= number_format($kasAwalNominal,0,',','.') ?></td>
            <td class="text-end">0</td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>

          <!-- Kas Masuk -->
          <?php 
          $subtotal_kasmasuk = 0;
          while ($row = mysqli_fetch_assoc($kasmasukQuery)):
            $subtotal_kasmasuk += $row['debet'];
            $saldo += $row['debet'];
            $total_debet += $row['debet'];
          ?>
          <tr>
            <td><?= $row['jam'] ?></td>
            <td></td>
            <td><?= $row['uraian'] ?></td>
            <td class="text-end"><?= number_format($row['debet'],0,',','.') ?></td>
            <td class="text-end">0</td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>
          <?php endwhile; ?>
          <tr class="subtotal">
            <td colspan="3" class="text-end">Subtotal Kas Masuk</td>
            <td class="text-end"><?= number_format($subtotal_kasmasuk,0,',','.') ?></td>
            <td class="text-end">0</td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>

          <!-- Tabungan -->
          <?php while ($row = mysqli_fetch_assoc($tabunganQuery)):
            $debet = $row['debet'];
            $kredit = $row['kredit'];
            $subtotal_tabungan_debet += $debet;
            $subtotal_tabungan_kredit += $kredit;
            $saldo += $debet - $kredit;
            $total_debet += $debet;
            $total_kredit += $kredit;
          ?>
          <tr>
            <td><?= $row['jam'] ?></td>
            <td><?= $row['nobukti'] ?></td>
            <td><?= $row['uraian'] ?></td>
            <td class="text-end"><?= number_format($debet,0,',','.') ?></td>
            <td class="text-end"><?= number_format($kredit,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>
          <?php endwhile; ?>
          <tr class="subtotal">
            <td colspan="3" class="text-end">Subtotal Transaksi Kas Teller</td>
            <td class="text-end"><?= number_format($subtotal_tabungan_debet + $kasAwalNominal,0,',','.') ?></td>
            <td class="text-end"><?= number_format($subtotal_tabungan_kredit,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>

          <!-- Kas Keluar -->
          <?php while ($row = mysqli_fetch_assoc($kasKeluarQuery)):
            $subtotal_kaskeluar += $row['kredit'];
            $saldo -= $row['kredit'];
            $total_kredit += $row['kredit'];
          ?>
          <tr>
            <td><?= $row['jam'] ?></td>
            <td></td>
            <td><?= $row['uraian'] ?></td>
            <td class="text-end">0</td>
            <td class="text-end"><?= number_format($row['kredit'],0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>
          <?php endwhile; ?>
          <tr class="subtotal">
            <td colspan="3" class="text-end">Subtotal Kas Keluar Teller</td>
            <td class="text-end">0</td>
            <td class="text-end"><?= number_format($subtotal_kaskeluar,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>

          <!-- Pendapatan -->
          <?php while ($row = mysqli_fetch_assoc($pendapatanQuery)):
            $debet = $row['debet'];
            $kredit = $row['kredit'];
            $subtotal_pendapatan_debet += $debet;
            $subtotal_pendapatan_kredit += $kredit;
            $saldo += $debet - $kredit;
            $total_debet += $debet;
          ?>
          <tr>
            <td><?= $row['jam'] ?></td>
            <td><?= $row['nomor_jurnal'] ?></td>
            <td><?= $row['uraian'] ?></td>
            <td class="text-end"><?= number_format($debet,0,',','.') ?></td>
            <td class="text-end"><?= number_format($kredit,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>
          <?php endwhile; ?>
          <tr class="subtotal">
            <td colspan="3" class="text-end">Subtotal Pendapatan BO</td>
            <td class="text-end"><?= number_format($subtotal_pendapatan_debet,0,',','.') ?></td>
            <td class="text-end"><?= number_format($subtotal_pendapatan_kredit,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>

          <!-- GRAND TOTAL -->
          <tr class="grandtotal">
            <td colspan="3" class="text-end">GRAND TOTAL</td>
            <td class="text-end"><?= number_format($total_debet,0,',','.') ?></td>
            <td class="text-end"><?= number_format($total_kredit,0,',','.') ?></td>
            <td class="text-end"><?= number_format($saldo,0,',','.') ?></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tanda tangan -->
  <div class="row mt-5 text-center" style="font-size:12px;">
    <div class="col-6">
      <p>Mengetahui,</p><br><br><br><br>
      <p>(Pengurus)</p>
    </div>
    <div class="col-6">
      <p>Pontianak, <?= date('d-m-Y') ?><br>Dibuat oleh,</p><br><br><br><br>
      <p>(Teller)</p>
    </div>
  </div>
</div>
</body>
</html>
