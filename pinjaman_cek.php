<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// Query gabungan untuk menghitung jumlah pembayaran angsuran per pinjaman
$sql = "
    SELECT 
        p.id AS pinjamanid,
        p.tanggal,
        p.nopinjaman,
        a.nama AS nama_anggota,
        COUNT(d.id) AS jumlah_angsuran,
        IFNULL(SUM(d.nominal), 0) AS total_nominal
    FROM pinjaman p
    LEFT JOIN anggota a ON p.anggotaid = a.id
    LEFT JOIN pinjangsurandebetdetail d ON p.id = d.pinjamanid
    GROUP BY p.id, p.tanggal, p.nopinjaman, a.nama
    ORDER BY p.tanggal ASC
";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Angsuran per Pinjaman</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6fa;
            margin: 0;
            display: flex;
        }

        header {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 10px 0;
            text-align: center;
            position: fixed;
            top: 0;
            z-index: 1000;
        }

        .sidebar {
            width: 200px;
            background-color: #2c3e50;
            padding-top: 70px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            display: block;
            padding: 10px 15px;
            color: white;
            text-decoration: none;
            transition: 0.3s;
        }

        .sidebar ul li a:hover {
            background-color: #34495e;
        }

        .content {
            margin-left: 220px;
            padding: 90px 20px 20px 20px;
            width: calc(100% - 220px);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        th, td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:hover {
            background-color: #f2f2f2;
        }

        h2 {
            color: #333;
        }

        .filter {
            margin-bottom: 15px;
        }

        .filter input {
            padding: 8px;
            width: 250px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        button {
            padding: 8px 12px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>

<?php
// header dan sidebar sudah di-include di atas
?>

<main class="content">
    <h2>📊 Rekap Pembayaran Angsuran Berdasarkan Pinjaman</h2>

    <div class="filter">
        <form method="GET">
            <input type="text" name="cari" placeholder="Cari nama anggota atau No. Pinjaman..." value="<?= isset($_GET['cari']) ? htmlspecialchars($_GET['cari']) : '' ?>">
            <button type="submit">Cari</button>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pinjaman</th>
                <th>No. Pinjaman</th>
                <th>Nama Anggota</th>
                <th>Jumlah Pembayaran</th>
                <th>Total Angsuran (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $cari = isset($_GET['cari']) ? $_GET['cari'] : '';

            if (!empty($cari)) {
                $sql = "
                    SELECT 
                        p.id AS pinjamanid,
                        p.tanggal,
                        p.nopinjaman,
                        a.nama AS nama_anggota,
                        COUNT(d.id) AS jumlah_angsuran,
                        IFNULL(SUM(d.nominal), 0) AS total_nominal
                    FROM pinjaman p
                    LEFT JOIN anggota a ON p.anggotaid = a.id
                    LEFT JOIN pinjangsurandebetdetail d ON p.id = d.pinjamanid
                    WHERE a.nama LIKE '%" . $conn->real_escape_string($cari) . "%' 
                       OR p.nopinjaman LIKE '%" . $conn->real_escape_string($cari) . "%'
                    GROUP BY p.id, p.tanggal, p.nopinjaman, a.nama
                    ORDER BY p.tanggal ASC
                ";
                $result = $conn->query($sql);
            }

            while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['nopinjaman']) ?></td>
                    <td><?= htmlspecialchars($row['nama_anggota']) ?></td>
                    <td style="text-align:center;"><?= $row['jumlah_angsuran'] ?></td>
                    <td style="text-align:right;"><?= number_format($row['total_nominal'], 2, ',', '.') ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</main>

</body>
</html>
