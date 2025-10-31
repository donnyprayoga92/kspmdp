<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/sidebar.php'; 

// Handle Tambah/Edit/Hapus
if (isset($_POST['simpan'])) {
    $user = "admin"; // bisa diganti sesuai session login
    $jam = date("Y-m-d H:i:s");

    // Jika tambah → ID otomatis
    if ($_POST['aksi'] == "tambah") {
        $id = date("Ymd-His"); // format YYYYMMDD-HHMMSS
        $sandi = $_POST['sandi'];
        $nama = $_POST['nama'];
        $propinsi_id = $_POST['propinsiid'];

        $sql = "INSERT INTO kabupaten (id, sandi, nama, propinsiid, user, jam) 
                VALUES ('$id', '$sandi', '$nama', '$propinsi_id', '$user', '$jam')";
    } else {
        // Jika edit → ID dari form (readonly)
        $id = $_POST['id'];
        $sandi = $_POST['sandi'];
        $nama = $_POST['nama'];
        $propinsi_id = $_POST['propinsiid'];

        $sql = "UPDATE kabupaten 
                SET sandi='$sandi', nama='$nama', propinsiid='$propinsi_id', user='$user', jam='$jam' 
                WHERE id='$id'";
    }
    mysqli_query($conn, $sql);
    header("Location: kabupaten.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kabupaten WHERE id='$id'");
    header("Location: kabupaten.php");
    exit;
}

// Ambil data kabupaten join propinsi
$result = mysqli_query($conn, "SELECT k.*, p.nama AS propinsi 
                               FROM kabupaten k 
                               JOIN propinsi p ON k.propinsiid = p.id 
                               ORDER BY p.nama, k.sandi ASC");

// Ambil data propinsi untuk dropdown
$propinsi = mysqli_query($conn, "SELECT * FROM propinsi ORDER BY nama ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kabupaten</title>
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
        <h3 class="mb-4">Data Kabupaten</h3>

        <!-- Tombol Tambah -->
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Kabupaten</button>

        <!-- Tabel Data -->
        <div class="card">
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode</th>
                            <th>Propinsi</th>
                            <th>Nama Kabupaten</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr>
                            <td><?= $row['sandi']; ?></td>
                            <td><?= $row['propinsi']; ?></td>
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
                                            <h5 class="modal-title">Edit Kabupaten</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="aksi" value="edit">
                                            <div class="mb-3">
                                                <label>ID</label>
                                                <input type="text" name="id" class="form-control" value="<?= $row['id']; ?>" readonly>
                                            </div>
                                            <div class="mb-3">
                                                <label>Kode</label>
                                                <input type="text" name="sandi" class="form-control" value="<?= $row['sandi']; ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label>Propinsi</label>
                                                <select name="propinsiid" class="form-control" required>
                                                    <?php
                                                    $propinsiEdit = mysqli_query($conn, "SELECT * FROM propinsi ORDER BY nama ASC");
                                                    while ($p = mysqli_fetch_assoc($propinsiEdit)) {
                                                        $selected = ($p['id'] == $row['propinsiid']) ? "selected" : "";
                                                        echo "<option value='{$p['id']}' $selected>{$p['nama']}</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label>Nama Kabupaten</label>
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
                        <h5 class="modal-title">Tambah Kabupaten</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="aksi" value="tambah">
                        <div class="mb-3">
                            <label>Kode</label>
                            <input type="text" name="sandi" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Propinsi</label>
                            <select name="propinsiid" class="form-control" required>
                                <option value="">-- Pilih Propinsi --</option>
                                <?php while ($p = mysqli_fetch_assoc($propinsi)) { ?>
                                    <option value="<?= $p['id']; ?>"><?= $p['nama']; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Nama Kabupaten</label>
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
