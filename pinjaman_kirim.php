<?php
// pinjaman_reminder.php

include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// === Reset otomatis setiap bulan ===
$hari = date("d"); 
if ($hari == "01") { // hanya jalan di tanggal 01
    $conn->query("UPDATE pinjaman SET sms = 0");
}

$bulan_indo = [
    "01"=>"Januari","02"=>"Februari","03"=>"Maret","04"=>"April",
    "05"=>"Mei","06"=>"Juni","07"=>"Juli","08"=>"Agustus",
    "09"=>"September","10"=>"Oktober","11"=>"November","12"=>"Desember"
];

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

$tanggal_hari_ini = date("Y-m-d");

// --- Query pinjaman ---
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8" />
<title>Reminder Pinjaman</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { background:#f4f6fb;}
  table {font-size: 12px !important;}
  button {font-size: 10px !important;}
  .table thead { background:#2563eb; color:#fff; font-size: 12px;}
  .badge-day { font-size:0.85rem; }
  .btn-wa { background:#25D366; color:#fff; border:none; padding:4px 10px; border-radius:6px; }
  .btn-wa:hover { background:#1ebe57; color:#fff; }
</style>
</head>
<body>
    
<div class="container my-4">
    <button class="btn btn-primary mb-3" onclick="window.location.href='pinjamanedit.php'">📜 Edit Angsuran ke</button>
    <button class="btn btn-primary mb-3" onclick="window.location.href='pinjaman_cek.php'">📜 Cek Angsuran</button>
  <h2 class="text-center text-primary mb-4">📋 Reminder Pinjaman Anggota</h2>

  <div class="text-end mb-3">
    <button id="autoSendAll" class="btn btn-success btn-sm">
      🚀 Kirim Otomatis ke Semua yang Jatuh Tempo Hari Ini
    </button>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      Daftar Pinjaman Jatuh Tempo
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
            <th>Kirim</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
<?php 
$data_auto = []; // untuk JS auto send
if ($result && $result->num_rows > 0):
  while ($row = $result->fetch_assoc()): 
    $jatuhTempo = hitungJatuhTempo($row['tanggal'], $row['rekeningkoran']);
    $hariTempo = $jatuhTempo->format("l"); 
    $hari_indo = ["Sunday"=>"Minggu","Monday"=>"Senin","Tuesday"=>"Selasa","Wednesday"=>"Rabu","Thursday"=>"Kamis","Friday"=>"Jumat","Saturday"=>"Sabtu"];
    $hariTempoIndo = $hari_indo[$hariTempo] ?? $hariTempo;

    $tglReminder = clone $jatuhTempo;
    $tglReminder->modify("-3 day");
    $tglReminderStr = $tglReminder->format("Y-m-d");

    $bulan_rem = $bulan_indo[$tglReminder->format("m")];
    $reminder_text = $tglReminder->format("d") . " " . $bulan_rem . " " . $tglReminder->format("Y");

    $nohp = normalize_phone($row['no_telp']);

    $pesan = "Salam sejahtera untuk Bapak/Ibu {$row['nama_anggota']},\n" 
           . "Anggota Koperasi KSP Mitra Dana Persada\n\n"
           . "Dengan hormat kami sampaikan pengingat bahwa angsuran pinjaman ke-{$row['rekeningkoran']} "
           . "sebesar *Rp " . number_format($row['angsuran'],0,",",".") . "* "
           . "untuk bulan " . $bulan_indo[$jatuhTempo->format("m")] . " " . $jatuhTempo->format("Y") . " akan jatuh tempo pada tanggal "
           . $jatuhTempo->format("d") . " " . $bulan_indo[$jatuhTempo->format("m")] . " " . $jatuhTempo->format("Y") . ".\n\n"
           . "Mohon kesediaannya untuk melakukan pembayaran tepat waktu agar kewajiban pinjaman tetap lancar.\n\n"
           . "Untuk pembayaran dapat melalui VA Bank Kalbar:\n"
           . "No. VA : 0168010000000002\n"
           . "Nama VA : KSP MDP\n\n"
           . "*_Dimohon untuk menyampaikan bukti transfer setelah melakukan pembayaran melalui VA._*\n\n"
           . "Atas perhatian dan kerja samanya, kami ucapkan terima kasih.\n\n"
           . "Salam hangat,\nKSP Mitra Dana Persada";

    $pesanWA = urlencode($pesan);

    // Kumpulkan data untuk auto send jika reminder = hari ini
    if ($tglReminderStr == $tanggal_hari_ini && $row['sms'] == 0) {
        $data_auto[] = [
            'id' => $row['id'],
            'nohp' => $nohp,
            'pesan' => $pesan
        ];
    }
?>
        <tr>
            <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
            <td><?= htmlspecialchars($nohp) ?></td>
            <td class="text-center"><?= htmlspecialchars($row['nopinjaman']) ?></td>
            <td><?= date("d-M-Y", strtotime($row['tanggal'])) ?></td>
            <td class="text-end">Rp <?= number_format($row['plafon'],0,",",".") ?></td>
            <td class="text-end">Rp <?= number_format($row['angsuran'],0,",",".") ?></td>
            <td class="text-center"><?= $row['jangkawaktu'] ?> bln</td>
            <td class="text-center"><?= $row['rekeningkoran'] ?></td>
            <td class="text-center fw-bold"><?= $jatuhTempo->format("d-M-Y") ?></td>
            <td class="text-center"><span class="badge bg-info badge-day"><?= $hariTempoIndo ?></span></td>
            <td class="text-center"><?= $reminder_text ?></td>
            <td class="text-center">
                <button 
                    class="btn-wa btn-sm btn-kirim" 
                    data-id="<?= $row['id'] ?>" 
                    data-nohp="<?= $nohp ?>" 
                    data-pesan="<?= htmlspecialchars($pesan) ?>"
                >
                    Kirim
                </button>
            </td>
            <td class="text-center">
                <span id="status-<?= $row['id'] ?>" class="badge <?= $row['sms']==1 ? 'bg-success' : 'bg-secondary' ?>">
                    <?= $row['sms'] == 1 ? 'Terkirim' : 'Belum' ?>
                </span>
            </td>
        </tr>
<?php endwhile; ?>
<?php else: ?>
          <tr><td colspan="13" class="text-center py-4">Tidak ada data</td></tr>
<?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// --- Fungsi Kirim Manual ---
$(document).on("click", ".btn-kirim", function(e){
    e.preventDefault();
    let btn = $(this);
    let id = btn.data("id");
    let nohp = btn.data("nohp");
    let pesan = btn.data("pesan");

    $.post("update_sms.php", {id: id}, function(res){
        if(res.trim() === "ok"){
            $("#status-"+id).removeClass("bg-secondary").addClass("bg-success").text("Terkirim");
            const url = "https://api.whatsapp.com/send?phone=" + nohp + "&text=" + encodeURIComponent(pesan);
            window.open(url, "_blank");
        } else {
            alert("Gagal update SMS!");
        }
    });
});

// --- Kirim Otomatis Semua ---
const autoList = <?php echo json_encode($data_auto); ?>;

$("#autoSendAll").click(async function(){
    if(autoList.length === 0){
        alert("Tidak ada anggota yang jatuh tempo hari ini./pesan sudah terkirim");
        return;
    }

    if(!confirm("Kirim pesan otomatis ke " + autoList.length + " anggota?")) return;

    for(let i=0; i<autoList.length; i++){
        const data = autoList[i];
        $("#status-"+data.id).text("Mengirim...");
        await new Promise(resolve => {
            $.post("update_sms.php", {id: data.id}, function(res){
                if(res.trim() === "ok"){
                    $("#status-"+data.id).removeClass("bg-secondary").addClass("bg-success").text("Terkirim");
                    const url = "https://api.whatsapp.com/send?phone=" + data.nohp + "&text=" + encodeURIComponent(data.pesan);
                    window.open(url, "_blank");
                }
                resolve();
            });
        });

        // jeda 1–2 detik
        const delay = 1000 + Math.random()*1000;
        await new Promise(r => setTimeout(r, delay));
    }

    alert("Selesai kirim semua pesan!");
});
</script>
</body>
</html>
<?php $conn->close(); ?>
