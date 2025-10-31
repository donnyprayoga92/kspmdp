<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; 
date_default_timezone_set("Asia/Jakarta");

$accountidArr = $_POST['accountid'];   // Bisa multiple
$debetArr     = $_POST['debet'];
$kreditArr    = $_POST['kredit'];
$keterangan   = $_POST['keterangan'];
$tanggal      = date("Y-m-d");
$jam          = date("Y-m-d H:i:s");

// Buat ID jurnal otomatis
$jurnalid = "JU." . date("ymdHis");

// Insert header jurnal
$conn->query("INSERT INTO accjurnal (id, tanggal, keterangan, posted, kantorid, user, jam) 
              VALUES ('$jurnalid','$tanggal','$keterangan',1,1,'admin','$jam')");

// Loop detail inputan
for ($i = 0; $i < count($accountidArr); $i++) {
    $acc = $accountidArr[$i];
    $debet = cleanRupiah($debetArr[$i]);
    $kredit = cleanRupiah($kreditArr[$i]);

    // Jika yang dipilih rekening tabungan anggota
    if (strpos($acc, "tab_") === 0) {
        $tabunganid = str_replace("tab_", "", $acc);

        // Ambil norekening anggota
        $q = $conn->query("SELECT norekening FROM tabungan WHERE id='$tabunganid'");
        $d = $q->fetch_assoc();
        $norekening = $d['norekening'];

        // Insert detail jurnal (otomatis pakai akun COA 510-01)
        $conn->query("INSERT INTO accjurnaldetail 
            (id, accountid, keterangan, debet, kredit, cek) 
            VALUES 
            ('$jurnalid','510-01','$keterangan',$debet,$kredit,0)");

        // -------------------- UPDATE TABUNGAN VIA TABTRANSAKSI --------------------

        // Ambil saldo terakhir
        $qSaldo = $conn->query("SELECT saldo FROM tabtransaksi 
                                WHERE tabunganid='$tabunganid' 
                                ORDER BY tanggal DESC, jam DESC, id DESC LIMIT 1");
        if ($qSaldo->num_rows > 0) {
            $last = $qSaldo->fetch_assoc();
            $saldo_awal = $last['saldo'];
        } else {
            $saldo_awal = 0;
        }

        // Hitung saldo baru
        $saldo_baru = $saldo_awal + $kredit - $debet;

        // Buat ID transaksi otomatis
        $tabtransid = "TL" . date("ymdHis") . $i;

        // Insert ke tabtransaksi
        $conn->query("INSERT INTO tabtransaksi 
            (id, tanggal, tabunganid, kodeid, jurnalid, nobukti, debet, kredit, saldo, keterangan, tipe, cetak, nobaris, bukti, kantorid, user, jam) 
            VALUES 
            ('$tabtransid','$tanggal','$tabunganid','99','$jurnalid','$jurnalid',$debet,$kredit,$saldo_baru,'$keterangan',1,0,0,1,1,'admin','$jam')");

    } else if (strpos($acc, "coa_") === 0) {
        // Jika yang dipilih COA
        $coa_id = str_replace("coa_", "", $acc);
        $conn->query("INSERT INTO accjurnaldetail 
            (id, accountid, keterangan, debet, kredit, cek) 
            VALUES 
            ('$jurnalid','$coa_id','$keterangan',$debet,$kredit,0)");
    }
}

// Fungsi hapus format rupiah
function cleanRupiah($value) {
    $value = str_replace('.', '', $value);  // hapus titik ribuan
    $value = str_replace(',', '.', $value); // ubah koma jadi titik (jika ada desimal)
    return (float)$value;
}

echo "✅ Jurnal berhasil disimpan dengan ID: $jurnalid <br>
      <a href='pemindahbukuan_list.php'>Lihat Daftar</a>";
?>
