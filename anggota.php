<?php 
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cari Anggota</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <h2 class="mb-4">🔍 Cari Anggota</h2>

    <!-- Form Pencarian -->
    <form method="get" class="row g-2 mb-4">
        <div class="col-sm-6 col-md-4">
            <input type="text" name="nama" class="form-control" 
                   placeholder="Masukkan nama anggota..." 
                   value="<?= isset($_GET['nama']) ? htmlspecialchars($_GET['nama']) : '' ?>" required>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-secondary">Reset</a>
        </div>
    </form>

    <?php
    $nama = '';
    $whereClause = '1';

    if (isset($_GET['nama'])) {
        $nama = $conn->real_escape_string($_GET['nama']);
        $whereClause = "nama LIKE '%$nama%'";
        echo "<div class='alert alert-info'>Hasil pencarian untuk: <b>" . htmlspecialchars($nama) . "</b></div>";
    }

    $sql = "SELECT * FROM anggota WHERE $whereClause ORDER BY noanggota ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $anggota = [];
        while ($row = $result->fetch_assoc()) {
            $anggota[] = $row;
        }

        $jumlah_per_kolom = 10;
        $total = count($anggota);
        $kolom_total = ceil($total / $jumlah_per_kolom);

        echo "<div class='row'>";
        for ($k = 0; $k < $kolom_total; $k++) {
            echo "<div class='col-md-3 mb-3'><ul class='list-group'>";
            for ($i = $k * $jumlah_per_kolom; $i < min(($k + 1) * $jumlah_per_kolom, $total); $i++) {
                $noanggota = htmlspecialchars($anggota[$i]['noanggota'], ENT_QUOTES, 'UTF-8');
                $nama_anggota = htmlspecialchars($anggota[$i]['nama'], ENT_QUOTES, 'UTF-8');
                $id = $anggota[$i]['id'];
                $aktif = $anggota[$i]['aktif'];

                // 🔥 Tambahkan class jika anggota tidak aktif
                $liClass = $aktif == 0 ? "list-group-item text-danger fw-bold" : "list-group-item";
                $linkClass = $aktif == 0 ? "text-danger" : "";

                echo "<li class='$liClass'>
                        <a href='detail.php?id=$id' class='text-decoration-none $linkClass'>
                            <strong>$noanggota</strong> - $nama_anggota
                        </a>";

                // Tambahkan badge jika non aktif
                if ($aktif == 0) {
                    echo " <span class='badge bg-danger ms-2'>Non Aktif</span>";
                }

                echo "</li>";
            }
            echo "</ul></div>";
        }
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>Tidak ada data anggota ditemukan.</div>";
    }
    ?>

    <!-- Tombol kembali -->
    <a href="index.php" class="btn btn-outline-secondary mt-4">← Kembali</a>
</div>

<!-- Bootstrap JS (opsional, untuk dropdown dll) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
