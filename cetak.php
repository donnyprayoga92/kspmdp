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
    <title>Formulir Pendaftaran Anggota</title>
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
            font-size: 20px;
            font-weight: bold;
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

<div class="judul">FORMULIR PENDAFTARAN MENJADI ANGGOTA</div>

<div class="noanggota">
    <span class="noanggota-label right-align">No. Anggota:</span>
    <span class="noanggota-value right-align"><?= htmlspecialchars($data['noanggota']) ?></span>
</div>
<div class="form-group indent">Yang bertanda tangan di bawah ini :</div>

<div class="form-line"><div class="form-number">1.</div><div class="label">Nama lengkap</div><div class="value">: <?= $data['nama'] ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;L/P: <?= $gender ?></div></div>
<div class="form-line"><div class="form-number">2.</div><div class="label">Tempat dan tanggal lahir</div><div class="value">: <?= $data['tmplahir'] ?> / <?= date('d-m-Y', strtotime($data['tgllahir'])) ?></div></div>
<div class="form-line"><div class="form-number">3.</div><div class="label">Alamat lengkap</div><div class="value">: <?= $data['alamat'] ?></div></div>
<div class="form-line"><div class="form-number">4.</div><div class="label">Telepon</div><div class="value">: Hp : <?= $data['nohp'] ?></div></div>
<div class="form-line"><div class="form-number">5.</div><div class="label">Nomor identitas</div><div class="value">: <?= $jenisid ?> : <?= $data['noid'] ?></div></div>
<div class="form-line"><div class="form-number">6.</div><div class="label">Pendidikan terakhir</div><div class="value">: <?= $data['pendidikan'] ?? '................................................................' ?></div></div>
<div class="form-line"><div class="form-number">7.</div><div class="label">Pekerjaan</div><div class="value">: <?= $data['pekerjaan'] ?? '................................................................' ?></div></div>
<div class="form-line"><div class="form-number">8.</div><div class="label">Nama suami/istri</div><div class="value">: <?= $data['istri'] ?? '...............................................................................................' ?></div></div>
<div class="form-line"><div class="form-number">9.</div><div class="label">Nama Ibu Kandung</div><div class="value">: <?= $data['ibu'] ?></div></div>
<div class="form-line"><div class="form-number">10.</div><div class="label">Alamat surat</div><div class="value">: <?= $data['alamatsurat'] ?></div></div>


<p>Saya mengajukan permohonan untuk menjadi anggota Koperasi Simpan Pinjam Mitra Dana Persada.</p>
<p>Saya bersedia mentaati Anggaran Dasar, Anggaran Rumah Tangga, pola kebijakan pengurus serta peraturan lain yang berlaku pada Koperasi Simpan Pinjam Mitra Dana Persada dengan penuh tanggung jawab.</p>

<div class="catatan">
    Bersama Formulir Permohonan ini, saya sertakan :<br>
    1. Satu lembar fotokopy KTP<br>
    2. Penyetoran pertama anggota baru yang terdiri dari :<br>
    &nbsp;&nbsp;&nbsp;&nbsp;a. Simpanan Pokok &nbsp;&nbsp;&nbsp;: Rp. 100.000,-<br>
    &nbsp;&nbsp;&nbsp;&nbsp;b. Simpanan Wajib &nbsp;&nbsp;&nbsp;&nbsp;: Rp. 25.000,- (Setiap bulan) <br>
	&nbsp;&nbsp;&nbsp;&nbsp;c. biaya Administrasi &nbsp;: Rp. 25.000,- (Akan di debet dari Rekening Simpanan Wajib)
</div>

<p class="indent">Demikian permohonan ini saya buat dengan sebenar-benarnya, atas perhatiannya saya ucapkan terima kasih.</p>
<table class="ttd">
    <tr>
        <td>Pontianak, <?= $tanggal ?> <?= $bulan ?> <?= $tahun ?></td>
        <td></td>
    </tr>
    <tr>
        <td><b>Pemohon</b><br><br><br><br>( <?= $data['nama'] ?> )</td>
        <td><b>Pengurus</b><br><br><br><br>( ........................ )</td>
    </tr>
</table>

</body>
</html>
</body>
</html>
