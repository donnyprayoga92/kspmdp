<?php
include __DIR__ . '/config/db.php';

// Array untuk nama hari dan bulan dalam bahasa Indonesia
$hariIndo = [
    'Sunday' => 'Minggu',
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu'
];

$bulanIndo = [
    '01' => 'Januari',
    '02' => 'Februari',
    '03' => 'Maret',
    '04' => 'April',
    '05' => 'Mei',
    '06' => 'Juni',
    '07' => 'Juli',
    '08' => 'Agustus',
    '09' => 'September',
    '10' => 'Oktober',
    '11' => 'November',
    '12' => 'Desember'
];

$hari = $hariIndo[date('l')];
$tanggal = date('d');
$bulan = $bulanIndo[date('m')];
$tahun = date('Y');

$id = $_GET['id'];
$query = "
    SELECT 
        a.*, 
        p.nama AS pendidikan,
        k.nama AS pekerjaan
    FROM anggota a
    LEFT JOIN pendidikan p ON a.pendidikanid = p.id
    LEFT JOIN pekerjaan k ON a.pekerjaanid = k.id
    WHERE a.id = '$id'
";
$data = $conn->query($query)->fetch_assoc();
$gender = $data['gender'] == '1' ? 'Laki-laki' : ($data['gender'] == '2' ? 'Perempuan' : '');
$jenisid = $data['jenisid'] == '1' ? 'KTP' : ($data['jenisid'] == '2' ? 'SIM' : '');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Formulir Pembukaan Simpanan</title>
    <style>
        body {
            font-family: Arial;
            font-size: 14px;
            margin: 40px;
        }
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .header img {
            width: 300px;
            margin-right: 15px;
        }
        .judul {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            text-decoration: underline;
            margin: 10px 0;
        }
        .form-group {
            margin-bottom: 8px;
        }
        .label {
            display: inline-block;
            width: 260px;
        }
        .indent {
            text-indent: 1em;
        }
        .catatan {
            font-size: 14px;
            margin-top: 20px;
        }
        .ttd {
            width: 100%;
            margin-top: 5px;
        }
        .ttd td {
            vertical-align: top;
            text-align: center;
            height: 30px;
        }
        .right-align {
            text-align: right;
            font-weight: bold;
        }
        @media print {
            button {
                display: none;
            }
        }
		.form-line {
			display: flex;
			margin-bottom: 8px;
		}
		.form-number {
			width: 30px;
		}
		.label {
			width: 260px;
		}
		.value {
			flex: 1;
		}
        .noanggota {
            text-align: right;
            margin-bottom: 10px;
        }
        .noanggota-label {
            font-size: 14px;
            font-weight: normal;
        }
        .noanggota-value {
            font-size: 14px;
            font-weight: bold;
        }
        input.noborder {
            border: none;
            border-bottom: 1px solid black;
            background: transparent;
            width: 70%;
            font-size: 14px;
        }
        @media print {
            input.noborder {
                border: none;
                outline: none;
            }
        }
        

    </style>
</head>
<body>
<button onclick="window.history.back()" style="margin-top: 30px; padding: 8px 16px; font-size: 16px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
    ← Kembali
</button>
<button onclick="window.print()" style="margin-top: 30px; padding: 8px 16px; font-size: 16px; background-color: #6c757d; color: white; border: none; border-radius: 4px; cursor: pointer;">
    🖨 Cetak
</button>

<div class="header">
    <img src="img/logo.jpg" alt="Logo Koperasi">
</div>

<div class="judul">FORMULIR PEMBUKAAN SIMPANAN</div>
<!--
<div class="noanggota">
    <span class="noanggota-label right-align">No. Anggota:</span>
    <span class="noanggota-value right-align"><?= htmlspecialchars($data['noanggota']) ?></span>
</div> 
-->
<!--
<div class="form-group indent">Yang bertanda tangan di bawah ini :</div>
-->
<br>
<br>
<div class="form-line"><div class="form-number">1.</div><div class="label">Nama lengkap</div><div class="value">: <?= $data['nama'] ?></div></div>
<div class="form-line"><div class="form-number">2.</div><div class="label">No. Anggota</div><div class="value">: <?= $data['noanggota'] ?></div></div>
<div class="form-line"><div class="form-number">3.</div><div class="label">Tempat dan tanggal lahir</div><div class="value">: <?= $data['tmplahir'] ?> / <?= date('d-m-Y', strtotime($data['tgllahir'])) ?></div></div>
<div class="form-line"><div class="form-number">4.</div><div class="label">Alamat lengkap</div><div class="value">: <?= $data['alamat'] ?></div></div>
<div class="form-line"><div class="form-number">5.</div><div class="label">Telepon</div><div class="value">: Hp : <?= $data['nohp'] ?></div></div>
<div class="form-line"><div class="form-number">6.</div><div class="label">Nomor identitas</div><div class="value">: <?= $jenisid ?> : <?= $data['noid'] ?></div></div>
<div class="form-line"><div class="form-number">7.</div><div class="label">Jenis Simpanan</div><div class="value">: Tabungan emdipi</div></div>
<div class="form-line">
    <div class="form-number">8.</div>
    <div class="label">Sumber Dana</div>
    <div class="value">: <input type="text" name="sumberdana" class="noborder"></div>
</div>
<div class="catatan">
Dengan menandatangani formulir pembukaan rekening simpanan ini saya menyatakan setuju<br> 
untuk tunduk dan terikat pada syarat dan ketentuan yang berlaku pada Koperasi Simpan Pinjam <br>
Mitra Dana Persada.<br>
   
</div>
<br>
<br>
<table class="ttd">
    <tr>
        <td>Pontianak, <?= $tanggal ?> <?= $bulan ?> <?= $tahun ?></td>
        <td></td>
    </tr>
    <!--
    <tr>
        <td><b>Pemohon</b><br><br><br><br>( <?= $data['nama'] ?> )</td>
        <td><b>Pengurus</b><br><br><br><br>( ........................ )</td>
    </tr>
    -->
</table>
<!-- Tabel tanda tangan tiga kolom -->
<table border="1" cellspacing="0" cellpadding="10" style="width: 400px; text-align: center; table-layout: fixed; margin-left: auto;">
    <tr>
        <th>Pemohon</th>
        <th>CS</th>
        <th>Pengurus</th>
    </tr>
    <tr style="height: 80px;">
        <td></td>
        <td></td>
        <td></td>
    </tr>
</table>
<div style="font-size: 10px; text-align: center; margin-top: 4px;">Tanda tangan sesuai KTP</div>

<br><br><br>

<!-- Spesimen tanda tangan -->
<div style="font-size: 20px; font-weight: bold; margin-left: 100px;">SPESIMEN</div>
<br>
<div style="border: 1px solid black; width: 300px; height: 200px;">
    <div style="height: 50%; border-bottom: 1px solid black; padding: 5px;">Tanda tangan</div>
    <div style="height: 50%; padding: 5px;">Tanda tangan</div>
</div>

</body>
</html>


</body>
</html>
