<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// Logika filter
$filter = $_GET['filter'] ?? 'today';
$tanggal_tertentu = $_GET['tanggal'] ?? null;

$judul = "Ulang Tahun Hari Ini";
$filter_sql = "DATE_FORMAT(tgllahir, '%m-%d') = DATE_FORMAT(CURDATE(), '%m-%d')";

if ($filter == '7hari') {
    $judul = "Ulang Tahun 7 Hari ke Depan";
    $filter_sql = "DATE_FORMAT(tgllahir, '%m-%d') 
                   BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
                   AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 7 DAY), '%m-%d')";
} elseif ($filter == 'tanggal' && $tanggal_tertentu) {
    $tanggal_format = date('m-d', strtotime($tanggal_tertentu));
    $judul = "Ulang Tahun pada Tanggal " . date('d M Y', strtotime($tanggal_tertentu));
    $filter_sql = "DATE_FORMAT(tgllahir, '%m-%d') = '$tanggal_format'";
}

// Query
$sql = "SELECT noanggota, nama, tgllahir 
        FROM anggota 
        WHERE $filter_sql AND aktif ='1'
        ORDER BY MONTH(tgllahir), DAY(tgllahir)";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Ulang Tahun Anggota</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container py-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="mb-4 text-primary">🎉 <?= $judul ?> (<?= date('d M Y') ?>)</h2>

            <!-- Filter Form -->
            <form method="get" class="row g-3 align-items-center mb-4">
                <div class="col-auto">
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="today" <?= $filter == 'today' ? 'selected' : '' ?>>Hari Ini</option>
                        <option value="7hari" <?= $filter == '7hari' ? 'selected' : '' ?>>7 Hari Ke Depan</option>
                        <option value="tanggal" <?= $filter == 'tanggal' ? 'selected' : '' ?>>Tanggal Tertentu</option>
                    </select>
                </div>

                <?php if ($filter == 'tanggal'): ?>
                    <div class="col-auto">
                        <input type="date" 
                               name="tanggal" 
                               class="form-control" 
                               value="<?= htmlspecialchars($tanggal_tertentu) ?>" 
                               onchange="this.form.submit()">
                    </div>
                <?php endif; ?>

                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Tampilkan</button>
                </div>
            </form>

            <!-- Tabel Data -->
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>No Anggota</th>
                                <th>Nama</th>
                                <th>Tanggal Lahir</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['noanggota']) ?></td>
                                    <td><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><?= date("d M Y", strtotime($row['tgllahir'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">Tidak ada data ulang tahun sesuai filter.</div>
            <?php endif; ?>

            <a href="index.php" class="btn btn-secondary mt-3">← Kembali</a>
        </div>
    </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
