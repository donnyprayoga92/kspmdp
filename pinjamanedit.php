<?php
// pinjaman_reminder.php

include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// === Reset otomatis setiap bulan ===
$hari = date("d"); 
if ($hari == "01") { 
    $conn->query("UPDATE pinjaman SET sms = 0");
}

$bulan_indo = [
    "01"=>"Januari","02"=>"Februari","03"=>"Maret","04"=>"April",
    "05"=>"Mei","06"=>"Juni","07"=>"Juli","08"=>"Agustus",
    "09"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"
];

$sql = "
SELECT 
    p.id,
    a.nama AS nama_anggota,
    a.nohp AS no_telp,
    p.nopinjaman,
    p.tanggal,
    p.plafon,
    p.nangsuran AS angsuran,
    p.jangkawaktu,
    p.rekeningkoran,
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

function hitungJatuhTempo($tanggal, $pembayaran) {
    $date = new DateTime($tanggal);
    $date->modify("+{$pembayaran} month");
    return $date;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Update Angsuran Ke</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f4f6fb;}
  table {font-size: 12px !important;}
  button {font-size: 10px !important;}
  .table thead { background:#2563eb; color:#fff; font-size: 12px;}
  .badge-day { font-size:0.85rem; }
  .btn-save { background:#2563eb; color:#fff; border:none; padding:4px 10px; border-radius:6px; }
  .btn-save:hover { background:#1d4ed8; color:#fff; }
  input.form-control-sm { height:28px; font-size:12px; text-align:center; }
</style>
</head>
<body>
<div class="container my-4">
  <h2 class="text-center text-primary mb-4">📋 Update Angsuran Ke</h2>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      Daftar Pinjaman Anggota
    </div>
    <div class="card-body table-responsive p-0">
      <table class="table table-striped table-hover align-middle mb-0">
        <thead>
          <tr class="text-center">
            <th>Nama Anggota</th>
            <th>No. HP</th>
            <th>No. Pinjaman</th>
            <th>Tanggal Pinjaman</th>
            <th>Plafon</th>
            <th>Angsuran</th>
            <th>Jangka Waktu</th>
            <th>Pembayaran ke-</th>
            <th>Tgl Jatuh Tempo</th>
            <th>Hari</th>
            <th>Reminder -3</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
<?php if ($result && $result->num_rows > 0): ?>
<?php while ($row = $result->fetch_assoc()): 
    $jatuhTempo = hitungJatuhTempo($row['tanggal'], $row['rekeningkoran']);
    $hariTempo = $jatuhTempo->format("l"); 
    $hari_indo = ["Sunday"=>"Minggu","Monday"=>"Senin","Tuesday"=>"Selasa","Wednesday"=>"Rabu","Thursday"=>"Kamis","Friday"=>"Jumat","Saturday"=>"Sabtu"];
    $hariTempoIndo = $hari_indo[$hariTempo] ?? $hariTempo;

    $tglReminder = clone $jatuhTempo;
    $tglReminder->modify("-3 day");
    $bulan_rem = $bulan_indo[$tglReminder->format("m")];
    $reminder_text = $tglReminder->format("d") . " " . $bulan_rem . " " . $tglReminder->format("Y");
?>
        <tr>
            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
            <td><?= htmlspecialchars(normalize_phone($row['no_telp'])) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['nopinjaman']) ?></td>
            <td><?= date("d-M-Y", strtotime($row['tanggal'])) ?></td>
            <td class="text-end">Rp <?= number_format($row['plafon'],0,",",".") ?></td>
            <td class="text-end">Rp <?= number_format($row['angsuran'],0,",",".") ?></td>
            <td class="text-center"><?= $row['jangkawaktu'] ?> bln</td>
            <td class="text-center">
                <input type="number" 
                       class="form-control form-control-sm text-center input-angsuran" 
                       id="angsuran-<?= $row['id'] ?>" 
                       value="<?= $row['rekeningkoran'] ?>" 
                       min="0" style="width:70px;">
            </td>
            <td class="text-center fw-bold"><?= $jatuhTempo->format("d-M-Y") ?></td>
            <td class="text-center"><span class="badge bg-info badge-day"><?= $hariTempoIndo ?></span></td>
            <td class="text-center"><?= $reminder_text ?></td>
            <td class="text-center">
                <button class="btn-save btn-sm btn-simpan" data-id="<?= $row['id'] ?>">💾 Simpan</button>
            </td>
        </tr>
<?php endwhile; ?>
<?php else: ?>
          <tr><td colspan="12" class="text-center py-4">Tidak ada data</td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on("click", ".btn-simpan", function(e){
    e.preventDefault();
    let id = $(this).data("id");
    let angsuran = $("#angsuran-" + id).val();

    $.post("update_angsuran.php", { id: id, rekeningkoran: angsuran }, function(res){
        if(res.trim() === "ok"){
            alert("✅ Data berhasil diperbarui!");
        } else {
            alert("❌ Gagal menyimpan perubahan!");
        }
    });
});
</script>
</body>
</html>
<?php $conn->close(); ?>
