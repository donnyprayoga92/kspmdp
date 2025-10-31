<?php
// simpanan_wajib.php (FINAL FIX)

// --- Koneksi DB ---
include __DIR__ . '/config/db.php';

// --- Bulan Indonesia ---
$bulan_indo = [
    "01" => "Januari","02" => "Februari","03" => "Maret","04" => "April",
    "05" => "Mei","06" => "Juni","07" => "Juli","08" => "Agustus",
    "09" => "September","10" => "Oktober","11" => "November","12" => "Desember"
];

// --- Ambil filter bulan/tahun ---
$bulan_pilih = isset($_GET['bulan']) ? $_GET['bulan'] : date("m");
$tahun_pilih = isset($_GET['tahun']) ? $_GET['tahun'] : date("Y");
$nama_bulan = (isset($bulan_indo[sprintf("%02d",$bulan_pilih)]) ? $bulan_indo[sprintf("%02d",$bulan_pilih)] : date("F")) . " " . $tahun_pilih;

// --- Filter tanggal gabung ---
$tgl_awal = isset($_GET['tgl_awal']) && $_GET['tgl_awal'] !== '' ? $_GET['tgl_awal'] : '';
$tgl_akhir = isset($_GET['tgl_akhir']) && $_GET['tgl_akhir'] !== '' ? $_GET['tgl_akhir'] : '';

// --- Filter saldo terakhir ---
$saldo_min = isset($_GET['saldo_min']) && $_GET['saldo_min'] !== '' ? (int) $_GET['saldo_min'] : '';
$saldo_max = isset($_GET['saldo_max']) && $_GET['saldo_max'] !== '' ? (int) $_GET['saldo_max'] : '';


// --- Template pesan default ---
$pesan_raw_default = "Salam sejahtera untuk Bapak/Ibu {{nama}}
Anggota Koperasi KSP Mitra Dana Persada,

Kami mengingatkan dengan hormat bahwa pembayaran Simpanan Wajib bulan *{{bulan}}* sebesar *Rp 25.000,-* sudah dapat dilakukan.
Mohon kesediaannya untuk melakukan setoran sesuai ketentuan, agar kewajiban anggota tetap terpenuhi dan koperasi kita semakin berkembang.

Atas perhatian dan kerja samanya, kami ucapkan terima kasih.

Salam hangat,
KSP Mitra Dana Persada";

// --- Ambil template pesan ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pesan_raw'])) {
    $pesan_raw = trim($_POST['pesan_raw']);
    if ($pesan_raw === '') $pesan_raw = $pesan_raw_default;
} else {
    $pesan_raw = $pesan_raw_default;
}

// --- Query utama ---
$sql = "
SELECT 
    a.noanggota AS anggota_id,
    a.nama AS nama_anggota,
    a.nohp AS no_telp,
    a.tanggal AS tgl_gabung,
    j.nama AS nama_simpanan,
    t.saldo AS saldo_terakhir,
    a.biayaandroid AS autodebet,
    u.norekening,
    u.sms
FROM tabtransaksi t
JOIN tabungan u ON t.tabunganid = u.id
JOIN anggota a ON u.anggotaid = a.id
JOIN tabjenis j ON u.jenisid = j.id
WHERE j.nama = 'SIMPANAN WAJIB' AND u.aktif = '1' 
  AND t.id = (
      SELECT t2.id 
      FROM tabtransaksi t2
      WHERE t2.tabunganid = t.tabunganid
      ORDER BY t2.tanggal DESC, t2.jurnalid DESC
      LIMIT 1
  )
";


// filter tanggal gabung
if ($tgl_awal !== '' && $tgl_akhir !== '') {
    $sql .= " AND a.tanggal BETWEEN '".$conn->real_escape_string($tgl_awal)."' AND '".$conn->real_escape_string($tgl_akhir)."' ";
} elseif ($tgl_awal !== '') {
    $sql .= " AND a.tanggal >= '".$conn->real_escape_string($tgl_awal)."' ";
} elseif ($tgl_akhir !== '') {
    $sql .= " AND a.tanggal <= '".$conn->real_escape_string($tgl_akhir)."' ";
}

// filter saldo terakhir
if ($saldo_min !== '' && $saldo_max !== '') {
    $sql .= " AND t.saldo BETWEEN $saldo_min AND $saldo_max ";
} elseif ($saldo_min !== '') {
    $sql .= " AND t.saldo >= $saldo_min ";
} elseif ($saldo_max !== '') {
    $sql .= " AND t.saldo <= $saldo_max ";
}


$sql .= " ORDER BY a.noanggota ";
$result = $conn->query($sql);

// --- Helper fungsi ---
function normalize_phone($raw) {
    $digits = preg_replace('/\D+/', '', (string)$raw);
    if ($digits === '') return '';
    if (strpos($digits, '0') === 0) return '62' . substr($digits, 1);
    if (strpos($digits, '62') === 0) return $digits;
    if (strpos($digits, '8') === 0) return '62' . $digits;
    return $digits;
}
function bulanAkhir($startDate, $jumlah_bulan, $bulan_indo) {
    if ($jumlah_bulan <= 0) return "-";
    $date = new DateTime($startDate);
    $date->modify("+" . ($jumlah_bulan - 1) . " month");
    $m = $date->format("m");
    $y = $date->format("Y");
    return (isset($bulan_indo[$m]) ? $bulan_indo[$m] : $date->format("F")) . " " . $y;
}
$tanggal_awal_default = "2025-08-01";


// --- Export Excel ---
if (isset($_POST['export_excel'])) {
    $filename = "simpanan_wajib_" . strtolower($nama_bulan) . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    // --- Export Excel ---
    echo "No Anggota\tNama Anggota\tNo HP\ttgl Gabung\tSaldo Terakhir\tJumlah Bulan Setor\tBulan Akhir\tautodebet\n";
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $jumlah_bulan = (int) floor(($row['saldo_terakhir'] ?? 0) / 25000);
            $bulan_akhir = bulanAkhir($tanggal_awal_default, $jumlah_bulan, $bulan_indo);
            $nohp = normalize_phone($row['no_telp']);
            $autodebet_text = ($row['autodebet'] == 1 ? "Ya" : "Tidak");

            echo $row['anggota_id'] . "\t" .
                $row['nama_anggota'] . "\t" .
                $nohp . "\t" .
                $row['tgl_gabung'] . "\t" .
                $row['saldo_terakhir'] . "\t" .
                $jumlah_bulan . "\t" .
                $bulan_akhir . "\t".
                $autodebet_text . "\n";
        }
    }
    exit;
}

// --- Setelah cek Excel, baru load header & sidebar ---
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <title>Daftar Simpanan Wajib</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f4f6fb; font-size: 14px; }
    .table thead { background:#2563eb; color:#fff; font-size: 14px; }
    .btn-wa { background:#25D366; color:#fff; border-color:#25D366; font-size: 10px;}
    .btn-wa:hover { background:#1ebe57; color:#fff; }
    .status-text { font-weight:600; min-width:100px; display:inline-block; }
    .status-success { color:#198754; }
    .status-fail { color:#dc3545; }
  </style>
</head>
<body>
<div class="container my-4">
  <h2 class="text-center text-primary mb-4">📋 Daftar Simpanan Wajib Anggota</h2>

  <!-- Filter bulan/tahun & tanggal gabung -->
  <form method="get" class="row g-2 mb-3 align-items-end">
    <div class="col-md-2">
      <label class="form-label">Bulan</label>
      <select name="bulan" class="form-select">
        <?php foreach ($bulan_indo as $k=>$v): 
            $sel = ($k == sprintf("%02d",$bulan_pilih)) ? 'selected' : '';
        ?>
          <option value="<?= $k ?>" <?= $sel ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Tahun</label>
      <select name="tahun" class="form-select">
        <?php for ($y = date("Y")-2; $y <= date("Y")+2; $y++): 
            $sel = ($y == $tahun_pilih) ? 'selected' : '';
        ?>
          <option value="<?= $y ?>" <?= $sel ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Tgl Gabung Awal</label>
      <input type="date" name="tgl_awal" class="form-control" value="<?= htmlspecialchars($tgl_awal) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Tgl Gabung Akhir</label>
      <input type="date" name="tgl_akhir" class="form-control" value="<?= htmlspecialchars($tgl_akhir) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Saldo Min</label>
      <input type="number" name="saldo_min" class="form-control" value="<?= htmlspecialchars($saldo_min) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Saldo Max</label>
      <input type="number" name="saldo_max" class="form-control" value="<?= htmlspecialchars($saldo_max) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label">Preview Pesan</label>
      <div class="form-control bg-white small" style="min-height:58px;">
        Placeholder <code>{{nama}}</code>, <code>{{bulan}}</code> → contoh isi: <strong><?= htmlspecialchars($nama_bulan) ?></strong>
      </div>
    </div>
    <div class="col-md-1">
      <button class="btn btn-primary w-100">Terapkan</button>
    </div>
  </form>

  <!-- Edit pesan -->
  <div class="card mb-3">
    <div class="card-header bg-primary text-white">✍️ Ubah Pesan Template</div>
    <div class="card-body">
      <form method="post" action="?bulan=<?= urlencode($bulan_pilih) ?>&tahun=<?= urlencode($tahun_pilih) ?>">
        <textarea name="pesan_raw" class="form-control mb-2" rows="6"><?= htmlspecialchars($pesan_raw) ?></textarea>
        <button type="submit" class="btn btn-success">💾 Simpan Template</button>
      </form>
    </div>
  </div>

  <!-- Tombol aksi -->
  <div class="mb-2 d-flex gap-2 align-items-center flex-wrap">
    <!--<button type="button" class="btn btn-success" id="sendSelected">📤 Kirim WA Massal</button> -->
    <form method="post" class="m-0 p-0">
      <input type="hidden" name="export_excel" value="1">
      <button type="submit" class="btn btn-outline-success">📊 Export ke Excel</button>
    </form>
  </div>



  <!-- Tabel -->
  <div class="card">
    <div class="card-body table-responsive p-0">
      <table class="table table-bordered align-middle mb-0">
        <thead>
          <tr>
            <!--<th><input type="checkbox" id="checkAll"></th>-->
            <th>No</th>
            <th>No. Anggota</th>
            <th>Nama</th>
            <th>No. HP</th>
            <th>Tgl Gabung</th>
            <th>Saldo Terakhir</th>
            <th>Jlh Bln Setor</th>
            <!--<th>Bulan Akhir</th>-->
            <th>Autodebet</th>
            <th>Aksi</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
<?php $no = 1; // inisialisasi nomor urut ?>
<?php if ($result && $result->num_rows > 0): ?>
<?php while ($row = $result->fetch_assoc()):
    $jumlah_bulan = (int) floor(($row['saldo_terakhir'] ?? 0) / 25000);
    $bulan_akhir = bulanAkhir($tanggal_awal_default, $jumlah_bulan, $bulan_indo);
    $nohp = normalize_phone($row['no_telp']);
    $pesan_final = str_replace(["{{nama}}", "{{bulan}}"], [$row['nama_anggota'], $nama_bulan], $pesan_raw);
    $wa_url = "https://wa.me/{$nohp}?text=" . rawurlencode($pesan_final);
?>
          <tr>
            <!--<td class="text-center"><input type="checkbox" class="checkRow" data-wa="<?= htmlspecialchars($wa_url) ?>"></td>-->
            <td class="text-center"><?= $no++ ?></td>
            <td><?= htmlspecialchars($row['anggota_id']) ?></td>
            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
            <td><?= htmlspecialchars($nohp) ?></td>
            <td><?= htmlspecialchars($row['tgl_gabung']) ?></td>
            <td>Rp <?= number_format($row['saldo_terakhir'] ?? 0,0,',','.') ?></td>
            <td class="text-center"><?= $jumlah_bulan ?> bulan</td>
            <!--<td class="text-center"><?= htmlspecialchars($bulan_akhir) ?></td>-->
            <td><?= ($row['autodebet'] == 1 ? "Ya" : "Tidak") ?></td>
            <td class="text-center">
                <button type="button" 
                    class="btn btn-sm btn-wa btn-send" 
                    data-id="<?= $row['anggota_id'] ?>" 
                    data-norek="<?= htmlspecialchars($row['norekening']) ?>"
                    data-wa="<?= htmlspecialchars($wa_url) ?>">
                  Kirim WA
                </button>
            </td>
            <td class="text-center">
                <span id="status-<?= $row['anggota_id'] ?>" 
                      class="badge <?= ($row['sms']==1 ? 'bg-success' : 'bg-secondary') ?>">
                    <?= ($row['sms']==1 ? 'Terkirim' : 'Belum') ?>
                </span>
            </td>
            
          </tr>
<?php endwhile; ?>
<?php else: ?>
          <tr><td colspan="9" class="text-center py-4">Tidak ada data</td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function(){
  $("#checkAll").on('change', function(){ $(".checkRow").prop('checked', this.checked); });

  $('body').on('click', '.btn-send', function(e){
    e.preventDefault();
    let btn   = $(this);
    let wa    = btn.data('wa');
    let id    = btn.data('id');
    let norek = btn.data('norek');
    $.post("update_sms1.php", {id: id, norek: norek}, function(res){
      if(res.trim() === "ok"){
        $("#status-"+id).removeClass("bg-secondary").addClass("bg-success").text("Terkirim");
        window.open(wa, "_blank");
      } else {
        alert("❌ Gagal update SMS!");
      }
    });
  });

  $('#sendSelected').on('click', function(){
    let $sel = $('.checkRow:checked');
    if (!$sel.length) { alert('Pilih anggota dahulu'); return; }
    $sel.each(function(){
      let tr    = $(this).closest('tr');
      let btn   = tr.find('.btn-send');
      btn.trigger('click'); // reuse event
    });
  });
});
</script>

</body>
</html>
<?php $conn->close(); ?>
