<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; 

if (!isset($_GET['id'])) {
    echo "<script>alert('ID anggota tidak ditemukan.'); window.location.href='anggota.php';</script>";
    exit;
}

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

if (!$data) {
    echo "<script>alert('Data anggota tidak ditemukan.'); window.location.href='anggota.php';</script>";
    exit;
}

$gender = $data['gender'] == '1' ? 'Laki-laki' : ($data['gender'] == '2' ? 'Perempuan' : '');
$jenisid = $data['jenisid'] == '1' ? 'KTP' : ($data['jenisid'] == '2' ? 'SIM' : '');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container my-4">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h4 class="mb-0">📋 Data Anggota</h4>
            <div>
                <a href="cetak.php?id=<?= $data['id'] ?>" class="btn btn-success btn-sm me-2">🖨 Cetak Form Pendaftaran</a>
                <a href="cetak_simpanan.php?id=<?= $data['id'] ?>" class="btn btn-success btn-sm">🖨 Cetak Form Simpanan</a>
                <a href="cetak_penutupan.php?id=<?= $data['id'] ?>" class="btn btn-success btn-sm">🖨 Cetak Form Penutupan</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-secondary">
                        <tr>
                            <th>No Anggota</th>
                            <th>Nama</th>
                            <th>Jenis Kelamin</th>
                            <th>Tempat / Tgl Lahir</th>
                            <th>Alamat</th>
                            <th>HP</th>
                            <th>Jenis / No Identitas</th>
                            <th>Pendidikan</th>
                            <th>Pekerjaan</th>
                            <th>Nama Suami/Istri</th>
                            <th>Ibu Kandung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?= $data['noanggota'] ?></td>
                            <td><?= $data['nama'] ?></td>
                            <td><?= $gender ?></td>
                            <td><?= $data['tmplahir'] ?> / <?= date('d-m-Y', strtotime($data['tgllahir'])) ?></td>
                            <td><?= $data['alamat'] ?></td>
                            <td><?= $data['nohp'] ?></td>
                            <td><?= $jenisid ?> : <?= $data['noid'] ?></td>
                            <td><?= $data['pendidikan'] ?? '-' ?></td>
                            <td><?= $data['pekerjaan'] ?? '-' ?></td>
                            <td><?= $data['istri'] ?? '-' ?></td>
                            <td><?= $data['ibu'] ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer text-end">
            <a href="anggota.php" class="btn btn-secondary">← Kembali</a>
            <a href="index.php" class="btn btn-dark">🏠 Dashboard</a>
        </div>
    </div>
</div>

</body>
</html>
