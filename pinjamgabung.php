<?php
// gabungan_pinjaman_full.php
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

function normalize_phone($raw) {
    $digits = preg_replace('/\\D+/', '', (string)$raw);
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
$tanggal_hari_ini = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Gabungan Modul Pinjaman</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body { background: #f8f9fa; }
.table thead { background:#2563eb; color:white; font-size:11px; }
.nav-tabs .nav-link { font-weight:500; }
.badge-day { font-size:0.85rem; }
.btn-wa { background:#25D366; color:#fff; border:none; padding:4px 10px; border-radius:6px; }
.btn-wa:hover { background:#1ebe57; color:#fff; }
.btn-save { background:#2563eb; color:#fff; border:none; padding:4px 10px; border-radius:6px; }
.btn-save:hover { background:#1d4ed8; color:#fff; }
main.container-fluid {
    display: flex;
    justify-content: center;
  }
.content-wrapper {
    width: 90%;
    max-width: 1400px;
    margin-top: 30px;
  }
.tab-container {
    margin-top: 30px;
  }
.tab-content {
    border: 1px solid #dee2e6;
    border-top: 10px;
    background: #fff;
    padding: 15px;
    border-radius: 0 0 8px 8px;
  }
input.form-control-sm { height:28px; font-size:12px; text-align:center; }
</style>
</head>

<body>
<main class="container-fluid">
  <div class="content-wrapper">
    <h3 class="mb-3"> 📊 Modul Pinjaman</h3>
     
  <?php
  $activeTab = isset($_GET['tab']) ? $_GET['tab'] : '';
  ?>
  <!-- Tabs -->
  <ul class="nav nav-tabs" id="pinjamanTabs" role="tablist">
    <li class="nav-item">
      <button class="nav-link <?= ($activeTab==='' || $activeTab=='reminder') ? 'active' : '' ?>"
        data-bs-toggle="tab" data-bs-target="#reminder" type="button">Reminder Pinjaman</button>
    </li>
    <li class="nav-item">
      <button class="nav-link <?= ($activeTab=='update') ? 'active' : '' ?>"
        data-bs-toggle="tab" data-bs-target="#update" type="button">Update Angsuran ke-</button>
    </li>
    <li class="nav-item">
      <button class="nav-link <?= ($activeTab=='rekap') ? 'active' : '' ?>"
        data-bs-toggle="tab" data-bs-target="#rekap" type="button">Rekap Angsuran</button>
    </li>
  </ul>

  <div class="tab-content" id="pinjamanTabsContent">

    <!-- TAB 1: REMINDER -->
    <div class="tab-pane fade <?= ($activeTab==='' || $activeTab=='reminder') ? 'show active' : '' ?>" id="reminder">
      <?php
      $sql = "SELECT p.id, a.nama AS nama_anggota, a.nohp AS no_telp, p.nopinjaman, p.tanggal, 
               p.plafon, p.nangsuran AS angsuran, p.jangkawaktu, p.rekeningkoran, p.sms
        FROM pinjaman p
        JOIN anggota a ON p.anggotaid = a.id";
        $result = $conn->query($sql);

        $data = [];
        $data_auto = [];
        if ($result && $result->num_rows > 0) {
          while($r = $result->fetch_assoc()) {
              $jt = hitungJatuhTempo($r['tanggal'], $r['rekeningkoran']);
              $tglReminder = clone $jt; 
              $tglReminder->modify("-3 day");
              $r['tglReminder'] = $tglReminder->format('Y-m-d');
              $r['jatuhTempo'] = $jt->format('Y-m-d');

              if ($jt->format('Y-m-d') == $tanggal_hari_ini && !$r['sms']) {
                $r['nohp'] = normalize_phone($r['no_telp']);
                $r['pesan'] = "Salam sejahtera untuk Bapak/Ibu {$r['nama_anggota']},\nAnggota KSP Mitra Dana Persada...";
                $data_auto[] = $r;
              }

              $data[] = $r;
          }
        }

        usort($data, function($a, $b) {
            return strtotime($a['tglReminder']) - strtotime($b['tglReminder']);
        });
      ?>
      <h4 class="mb-3">📋 Reminder Pinjaman</h4>
      <button id="autoSendAll" class="btn btn-success btn-sm mb-2">🚀 Kirim Otomatis ke Semua yang Jatuh Tempo Hari Ini</button>

      <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
          <thead class="text-center">
            <tr>
              <th>No</th><th>Nama</th><th>No. HP</th><th>No. Pinjaman</th><th>Tanggal</th>
              <th>Plafon</th><th>Angsuran</th><th>Waktu</th><th>Ke-</th>
              <th>Jatuh Tempo</th><th>Hari</th><th>Reminder -3</th><th>Kirim</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php 
          $no=1; 
          if (!empty($data)): 
            foreach($data as $r):
              $jt = new DateTime($r['jatuhTempo']);
              $hari_indo = ["Sunday"=>"Minggu","Monday"=>"Senin","Tuesday"=>"Selasa",
                            "Wednesday"=>"Rabu","Thursday"=>"Kamis","Friday"=>"Jumat","Saturday"=>"Sabtu"];
              $hariTempo = $hari_indo[$jt->format("l")] ?? $jt->format("l");
              $tglReminder = new DateTime($r['tglReminder']);
              $reminder_text = $tglReminder->format("d") . " " . $bulan_indo[$tglReminder->format("m")];
              $nohp = normalize_phone($r['no_telp']);
              $pesan = "Salam sejahtera untuk Bapak/Ibu {$r['nama_anggota']},\nAnggota KSP Mitra Dana Persada...";
          ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
              <td><?= $nohp ?></td>
              <td><?= htmlspecialchars($r['nopinjaman']) ?></td>
              <td><?= date('d-M-Y', strtotime($r['tanggal'])) ?></td>
              <td class="text-end">Rp <?= number_format($r['plafon'],0,',','.') ?></td>
              <td class="text-end">Rp <?= number_format($r['angsuran'],0,',','.') ?></td>
              <td class="text-center"><?= $r['jangkawaktu'] ?> bln</td>
              <td class="text-center"><?= $r['rekeningkoran'] ?></td>
              <td><?= $jt->format('d-M-Y') ?></td>
              <td class="text-center"><?= $hariTempo ?></td>
              <td class="text-center"><?= $reminder_text ?></td>
              <td class="text-center">
                <button class="btn-wa btn-sm btn-kirim" 
                        data-id="<?= $r['id'] ?>" 
                        data-nohp="<?= $nohp ?>" 
                        data-pesan="<?= htmlspecialchars($pesan) ?>">Kirim</button>
              </td>
              <td class="text-center">
                <span id="status-<?= $r['id'] ?>" class="badge <?= $r['sms']?'bg-success':'bg-secondary' ?>">
                  <?= $r['sms']?'Terkirim':'Belum' ?>
                </span>
              </td>
            </tr>
          <?php 
            endforeach; 
          else: 
          ?>
            <tr><td colspan="13" class="text-center">Tidak ada data</td></tr>
          <?php endif; ?>
          </tbody>
      </table>
    </div>
    </div>

    <!-- TAB 2: UPDATE ANGSURAN -->
    <div class="tab-pane fade <?= ($activeTab=='update') ? 'show active' : '' ?>" id="update">
      <h4 class="mb-3">📋 Update Angsuran Ke</h4>
      <?php
      $sql_update = "SELECT p.id,a.nama AS nama_anggota,a.nohp AS no_telp,p.nopinjaman,
                            p.tanggal,p.plafon,p.nangsuran AS angsuran,p.jangkawaktu,p.rekeningkoran
                     FROM pinjaman p
                     JOIN anggota a ON p.anggotaid=a.id
                     ORDER BY p.tanggal ASC";
      $res_update = $conn->query($sql_update);
      ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
          <thead class="text-center">
            <tr>
              <th>Nama Anggota</th><th>No. HP</th><th>No. Pinjaman</th><th>Tanggal Pinjaman</th>
              <th>Plafon</th><th>Angsuran</th><th>Jangka Waktu</th>
              <th>Pembayaran ke-</th><th>Tgl Jatuh Tempo</th><th>Hari</th><th>Reminder -3</th><th>Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($res_update && $res_update->num_rows>0): 
            while($row=$res_update->fetch_assoc()):
              $jt=hitungJatuhTempo($row['tanggal'],$row['rekeningkoran']);
              $hari_indo=["Sunday"=>"Minggu","Monday"=>"Senin","Tuesday"=>"Selasa","Wednesday"=>"Rabu","Thursday"=>"Kamis","Friday"=>"Jumat","Saturday"=>"Sabtu"];
              $hariTempoIndo=$hari_indo[$jt->format("l")]??$jt->format("l");
              $tglReminder=clone $jt; $tglReminder->modify("-3 day");
              $bulan_rem=$bulan_indo[$tglReminder->format("m")];
              $reminder_text=$tglReminder->format("d")." ".$bulan_rem." ".$tglReminder->format("Y");
          ?>
            <tr>
              <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
              <td><?= htmlspecialchars(normalize_phone($row['no_telp'])) ?></td>
              <td class="text-center"><?= htmlspecialchars($row['nopinjaman']) ?></td>
              <td><?= date("d-M-Y",strtotime($row['tanggal'])) ?></td>
              <td class="text-end">Rp <?= number_format($row['plafon'],0,",",".") ?></td>
              <td class="text-end">Rp <?= number_format($row['angsuran'],0,",",".") ?></td>
              <td class="text-center"><?= $row['jangkawaktu'] ?> bln</td>
              <td class="text-center">
                <input type="number" class="form-control form-control-sm text-center" 
                       id="angsuran-<?= $row['id'] ?>" value="<?= $row['rekeningkoran'] ?>" 
                       min="0" style="width:70px;">
              </td>
              <td class="text-center fw-bold"><?= $jt->format("d-M-Y") ?></td>
              <td class="text-center"><span class="badge bg-info badge-day"><?= $hariTempoIndo ?></span></td>
              <td class="text-center"><?= $reminder_text ?></td>
              <td class="text-center">
                <button class="btn-save btn-sm btn-simpan" data-id="<?= $row['id'] ?>">💾 Simpan</button>
              </td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="12" class="text-center py-4">Tidak ada data</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- TAB 3: REKAP ANGSURAN -->
    <div class="tab-pane fade <?= ($activeTab=='rekap') ? 'show active' : '' ?>" id="rekap">
      <h4 class="mb-3">📊 Rekap Pembayaran Angsuran</h4>
      <form method="GET" class="mb-2">
        <input type="hidden" name="tab" value="rekap">
        <input type="text" name="cari" placeholder="Cari nama atau No. Pinjaman..." 
              value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
        <button type="submit" class="btn btn-primary btn-sm">Cari</button>
      </form>

      <?php
      $cari = isset($_GET['cari']) ? $conn->real_escape_string($_GET['cari']) : '';
      $sql3 = "
        SELECT p.id, p.tanggal, p.nopinjaman, a.nama AS nama_anggota,
              COUNT(d.id) AS jumlah_angsuran, IFNULL(SUM(d.nominal),0) AS total_nominal
        FROM pinjaman p
        LEFT JOIN anggota a ON p.anggotaid=a.id
        LEFT JOIN pinjangsurandebetdetail d ON p.id=d.pinjamanid
        WHERE a.nama LIKE '%$cari%' OR p.nopinjaman LIKE '%$cari%'
        GROUP BY p.id,p.tanggal,p.nopinjaman,a.nama
        ORDER BY p.tanggal ASC";
      $res3 = $conn->query($sql3);
      ?>

      <div class="table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>No</th><th>Tanggal</th><th>No. Pinjaman</th><th>Nama Anggota</th>
              <th>Jumlah Pembayaran</th><th>Total Angsuran (Rp)</th>
            </tr>
          </thead>
          <tbody>
          <?php 
          $no=1; 
          if($res3 && $res3->num_rows>0): 
            while($r=$res3->fetch_assoc()): ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= htmlspecialchars($r['tanggal']) ?></td>
              <td><?= htmlspecialchars($r['nopinjaman']) ?></td>
              <td><?= htmlspecialchars($r['nama_anggota']) ?></td>
              <td class="text-center"><?= $r['jumlah_angsuran'] ?></td>
              <td class="text-end"><?= number_format($r['total_nominal'],2,',','.') ?></td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="6" class="text-center">Tidak ada data</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Kirim manual ---
$(document).on('click', '.btn-kirim', function(e){
  e.preventDefault();
  let id=$(this).data('id'), nohp=$(this).data('nohp'), pesan=$(this).data('pesan');
  $.post('update_sms.php',{id:id},function(r){
    if(r.trim()==='ok'){
      $('#status-'+id).removeClass('bg-secondary').addClass('bg-success').text('Terkirim');
      window.open('https://api.whatsapp.com/send?phone='+nohp+'&text='+encodeURIComponent(pesan),'_blank');
    }
  });
});
// --- Auto send ---
const autoList = <?php echo json_encode($data_auto); ?>;
$('#autoSendAll').click(async ()=>{
  if(autoList.length===0){alert('Tidak ada yang jatuh tempo hari ini.');return;}
  if(!confirm('Kirim ke '+autoList.length+' anggota?')) return;
  for(let d of autoList){
    $('#status-'+d.id).text('Mengirim...');
    await new Promise(res=>{
      $.post('update_sms.php',{id:d.id},()=>res());
      const url='https://api.whatsapp.com/send?phone='+d.nohp+'&text='+encodeURIComponent(d.pesan);
      window.open(url,'_blank');
    });
    await new Promise(r=>setTimeout(r,1000+Math.random()*1000));
  }
  alert('Selesai kirim semua.');
});
// --- Simpan update angsuran ---
$(document).on('click','.btn-simpan',function(){
  let id=$(this).data('id');
  let val=$('#angsuran-'+id).val();
  $.post('update_angsuran.php',{id:id,rekeningkoran:val},r=>{
    if(r.trim()==='ok') alert('✅ Data disimpan!');
    else alert('❌ Gagal!');
  });
});
// --- Tab persistence ---
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tab => {
  tab.addEventListener('shown.bs.tab', e => {
    localStorage.setItem('activeTab', e.target.getAttribute('data-bs-target'));
  });
});
document.addEventListener('DOMContentLoaded', ()=>{
  const urlParams=new URLSearchParams(window.location.search);
  const tabParam=urlParams.get('tab');
  let activeTarget=tabParam ? '#'+tabParam : localStorage.getItem('activeTab');
  if(activeTarget){
    const tabTrigger=document.querySelector(`button[data-bs-toggle="tab"][data-bs-target="${activeTarget}"]`);
    if(tabTrigger){
      const tab=new bootstrap.Tab(tabTrigger); tab.show();
    }
  }
});
</script>
</body>
</html>
<?php $conn->close(); ?>
