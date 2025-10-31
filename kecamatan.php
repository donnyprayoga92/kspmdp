<?php
session_start();
include __DIR__ . '/config/db.php';

// Handle Tambah/Edit/Hapus
if (isset($_POST['simpan'])) {
    $user = "admin"; // nanti bisa diganti dengan session login
    $jam = date("Y-m-d H:i:s");
    $aksi = $_POST['aksi'];
    $nama = trim($_POST['nama']);
    $kota_id = $_POST['kotaid'];

    if ($aksi == "tambah") {
        // Cek duplikasi: nama & kotaid sama
        $cek = mysqli_query($conn, "SELECT * FROM kecamatan WHERE nama='$nama' AND kotaid='$kota_id'");
        if (mysqli_num_rows($cek) > 0) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'message' => "Kecamatan <strong>$nama</strong> sudah ada di kabupaten/kota ini!"
            ];
            header("Location: kecamatan.php");
            exit;
        }

        $id = date("Ymd-His");
        $sql = "INSERT INTO kecamatan (id, nama, kotaid, user, jam) 
                VALUES ('$id', '$nama', '$kota_id', '$user', '$jam')";
    } else {
        // Edit data
        $id = $_POST['id'];
        // Cek duplikasi selain dirinya sendiri
        $cek = mysqli_query($conn, "SELECT * FROM kecamatan WHERE nama='$nama' AND kotaid='$kota_id' AND id <> '$id'");
        if (mysqli_num_rows($cek) > 0) {
            $_SESSION['alert'] = [
                'type' => 'warning',
                'message' => "Nama kecamatan <strong>$nama</strong> sudah digunakan di kabupaten/kota ini!"
            ];
            header("Location: kecamatan.php");
            exit;
        }

        $sql = "UPDATE kecamatan 
                SET nama='$nama', kotaid='$kota_id', user='$user', jam='$jam' 
                WHERE id='$id'";
    }

    if (mysqli_query($conn, $sql)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'message' => 'Data kecamatan berhasil disimpan.'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'danger',
            'message' => 'Terjadi kesalahan saat menyimpan data.'
        ];
    }

    header("Location: kecamatan.php");
    exit;
}

if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM kecamatan WHERE id='$id'");
    $_SESSION['alert'] = [
        'type' => 'success',
        'message' => 'Data kecamatan berhasil dihapus.'
    ];
    header("Location: kecamatan.php");
    exit;
}

// Ambil data kecamatan join kabupaten & propinsi
$result = mysqli_query($conn, "SELECT k.*, p.nama AS kabupaten, s.nama AS propinsi 
                               FROM kecamatan k 
                               JOIN kabupaten p ON k.kotaid = p.id 
                               JOIN propinsi s ON p.propinsiid = s.id
                               ORDER BY p.nama, k.nama ASC");

// Ambil data kabupaten untuk dropdown
$kabupaten = mysqli_query($conn, "SELECT * FROM kabupaten ORDER BY nama ASC");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Kecamatan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            font-size: 14px;
        }
        .main-content {
            margin-left: 220px;
            padding: 20px;
            min-height: 100vh;
        }
    </style>
</head>
<body>

<div class="main-content">
    <h3 class="mb-4">Data Kecamatan</h3>

    <!-- Notifikasi -->
    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?= $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
            <?= $_SESSION['alert']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <!-- Tombol Tambah -->
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalTambah">+ Tambah Kecamatan</button>

    <!-- Tabel Data -->
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Provinsi</th>
                        <th>Kabupaten/Kota</th>
                        <th>Nama Kecamatan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['propinsi']); ?></td>
                        <td><?= htmlspecialchars($row['kabupaten']); ?></td>
                        <td><?= htmlspecialchars($row['nama']); ?></td>
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
                                        <h5 class="modal-title">Edit Kecamatan</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="aksi" value="edit">
                                        <div class="mb-3">
                                            <label>ID</label>
                                            <input type="text" name="id" class="form-control" value="<?= $row['id']; ?>" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label>Kabupaten/Kota</label>
                                            <select name="kotaid" class="form-control" required>
                                                <?php
                                                $kabupatenEdit = mysqli_query($conn, "SELECT * FROM kabupaten ORDER BY nama ASC");
                                                while ($p = mysqli_fetch_assoc($kabupatenEdit)) {
                                                    $selected = ($p['id'] == $row['kotaid']) ? "selected" : "";
                                                    echo "<option value='{$p['id']}' $selected>{$p['nama']}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label>Nama Kecamatan</label>
                                            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($row['nama']); ?>" required>
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
                    <h5 class="modal-title">Tambah Kecamatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="mb-3">
                        <label>Kabupaten/Kota</label>
                        <select name="kotaid" class="form-control" required>
                            <option value="">-- Pilih Kabupaten/Kota --</option>
                            <?php while ($p = mysqli_fetch_assoc($kabupaten)) { ?>
                                <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nama']); ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Nama Kecamatan</label>
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
