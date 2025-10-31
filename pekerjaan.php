<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
date_default_timezone_set("Asia/Jakarta");

// === Tambah Data ===
if (isset($_POST['tambah'])) {
    $id   = date("Ymd-His"); // format yyyymmdd-hhmmss
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $usr  = mysqli_real_escape_string($conn, $_POST['user']);
    $jam  = date("Y-m-d H:i:s"); // otomatis timestamp sekarang

    mysqli_query($conn, "INSERT INTO pekerjaan (id, nama, user, jam) VALUES ('$id','$nama','$usr','$jam')");
    echo "<script>window.location.href='pekerjaan.php';</script>";
    exit;
}

// === Update Data ===
if (isset($_POST['update'])) {
    $id   = $_POST['id'];
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $usr  = mysqli_real_escape_string($conn, $_POST['user']);
    // jam tidak diubah otomatis saat update, hanya nama & user
    mysqli_query($conn, "UPDATE pekerjaan SET nama='$nama', user='$usr' WHERE id='$id'");
    echo "<script>window.location.href='pekerjaan.php';</script>";
    exit;
}

// === Hapus Data ===
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pekerjaan WHERE id='$id'");
    echo "<script>window.location.href='pekerjaan.php';</script>";
    exit;
}

// === Ambil Data Edit (jika ada) ===
$edit = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM pekerjaan WHERE id='$id'");
    $edit = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>CRUD Pekerjaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4">Pekerjaan</h2>

    <!-- Form Input -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header">
            <?= $edit ? 'Edit Data Pekerjaan' : 'Tambah Data Pekerjaan'; ?>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <?php if ($edit) { ?>
                    <input type="hidden" name="id" value="<?= $edit['id']; ?>">
                <?php } ?>
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="nama" class="form-control" value="<?= $edit['nama'] ?? ''; ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">User</label>
                    <input type="text" name="user" class="form-control" value="<?= $edit['user'] ?? ''; ?>" required>
                </div>
                <div class="col-12">
                    <?php if ($edit) { ?>
                        <button type="submit" name="update" class="btn btn-warning">Update</button>
                        <a href="pekerjaan.php" class="btn btn-secondary">Batal</a>
                    <?php } else { ?>
                        <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Data -->
    <div class="card shadow-sm">
        <div class="card-header">
            Daftar Pekerjaan
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>User</th>
                        <th>Jam</th>
                        <th width="150">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = mysqli_query($conn, "SELECT * FROM pekerjaan ORDER BY id DESC");
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>
                                    <td>{$row['id']}</td>
                                    <td>{$row['nama']}</td>
                                    <td>{$row['user']}</td>
                                    <td>{$row['jam']}</td>
                                    <td>
                                        <a href='?edit={$row['id']}' class='btn btn-sm btn-warning'>Edit</a>
                                        <a href='?hapus={$row['id']}' onclick=\"return confirm('Yakin hapus?');\" class='btn btn-sm btn-danger'>Hapus</a>
                                    </td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>Belum ada data</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
