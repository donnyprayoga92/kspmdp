<?php
include __DIR__ . '/config/db.php';
//include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; 

// Handle Tambah/Edit/Hapus
if (isset($_POST['simpan'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $user = "admin"; // bisa diganti sesuai session login
    $jam = date("Y-m-d H:i:s");

    if ($_POST['aksi'] == "tambah") {
        $sql = "INSERT INTO propinsi (id, nama, user, jam) VALUES ('$id', '$nama', '$user', '$jam')";
    } else {
        $sql = "UPDATE propinsi SET nama='$nama', user='$user', jam='$jam' WHERE id='$id'";
    }
    mysqli_query($conn, $sql);
    header("Location: propinsi.php");
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM propinsi WHERE id='$id'");
    header("Location: wilayah.php");
}

// Ambil data
$result = mysqli_query($conn, "SELECT * FROM propinsi WHERE nama ='KALIMANTAN BARAT'");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Propinsi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-size: 14px;
        }
        .main-content {
            margin-left: 220px; /* sesuai lebar sidebar.php */
            padding: 20px;
            min-height: 100vh;
        }
    </style>
</head>
<body>

    <!-- Konten Utama -->
    <div class="main-content">
        <h3 class="mb-4">Data Propinsi</h3>

        <!-- Tombol Tambah -->
        <!-- <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Propinsi</button> -->
        <!-- <button class="btn btn-primary mb-3" onclick="window.location.href='kabupaten.php'">+ Tambah Kabupaten</button> -->
        <button class="btn btn-primary mb-3" onclick="window.location.href='kecamatan.php'">+ Tambah Kecamatan</button>
        <button class="btn btn-primary mb-3" onclick="window.location.href='kelurahan.php'">+ Tambah Kelurahan</button>
        <!-- Tabel Data -->
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?= $row['id']; ?></td>
                            <td><?= $row['nama']; ?></td>
                            <td>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id']; ?>">Edit</button>
                                <a href="?hapus=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin hapus data ini?')">Delete</a>
                            </td>
                        </tr>

                        <!-- Modal Edit -->
                        <div class="modal fade" id="modalEdit<?= $row['id']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="post">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Propinsi</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="aksi" value="edit">
                                            <div class="mb-3">
                                                <label>ID</label>
                                                <input type="text" name="id" class="form-control" value="<?= $row['id']; ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label>Nama</label>
                                                <input type="text" name="nama" class="form-control" value="<?= $row['nama']; ?>" required>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal fade" id="modalTambah" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Propinsi</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="aksi" value="tambah">
                        <div class="mb-3">
                            <label>ID</label>
                            <input type="text" name="id" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Nama</label>
                            <input type="text" name="nama" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="simpan" class="btn btn-success">Simpan</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
