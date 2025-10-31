<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// ==== UPDATE HANDLER ====
if (isset($_POST['update'])) {
    $table = $_POST['table'];
    $id    = $_POST['id'];
    $kolom = $_POST['kolom'];
    $nilai = $_POST['nilai'];

    $sql = "UPDATE $table SET $kolom = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nilai, $id);
    $stmt->execute();

    echo "<div class='alert alert-success'>Data $table berhasil diperbarui!</div>";
}

// ==== QUERY DATA ====
$angsuran = $conn->query("SELECT * FROM pinjangsurandebetdetail LIMIT 50");
$jurnal   = $conn->query("SELECT * FROM pinjtransaksi ORDER BY tanggal DESC LIMIT 50");
$detail   = $conn->query("SELECT * FROM pinjtransaksidetail LIMIT 50");
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
    /* Judul dan tab menu */
    h2, h4, .nav-link {
        font-size: 16px !important;
        font-weight: bold;
    }

    /* Konten tabel + form input */
    table, td, th, input, select, button {
        font-size: 12px !important;
    }

    /* Padding tabel agar lebih compact */
    table.table td, 
    table.table th {
        padding: 4px 6px !important;
        vertical-align: middle;
    }

    /* Efek striped row lebih halus */
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9 !important; /* abu-abu sangat muda */
    }
    .table-striped tbody tr:nth-of-type(even) {
        background-color: #ffffff !important; /* putih bersih */
    }
</style>


<div class="container mt-4">
    <h2>Data Transaksi</h2>
    <!-- TAB MENU -->
    <ul class="nav nav-tabs" id="myTab" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#angsuran">Angsuran</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#jurnal">Pokok</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#detail">Bunga</button></li>
    </ul>

    <div class="tab-content mt-3">
        <!-- =================== ANGSURAN =================== -->
        <div class="tab-pane fade show active" id="angsuran">
            <h4>Data Angsuran</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>No Angsur</th><th>Pinjaman ID</th><th>Simpanan ID</th>
                        <th>Nominal</th><th>Pembulatan</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $angsuran->fetch_assoc()) { ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?php echo $row['id']; ?>
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="table" value="pinjangsurandebetdetail">
                            </td>
                            <td><?php echo $row['noangsur']; ?></td>
                            <td><?php echo $row['pinjamanid']; ?></td>
                            <td><?php echo $row['simpananid']; ?></td>
                            <td><input type="text" name="nilai" value="<?php echo $row['nominal']; ?>"></td>
                            <td><input type="text" name="nilai2" value="<?php echo $row['pembulatan']; ?>"></td>
                            <td>
                                <select name="kolom">
                                    <option value="nominal">Nominal</option>
                                    <option value="pembulatan">Pembulatan</option>
                                </select>
                                <button type="submit" name="update" class="btn btn-sm btn-primary">Simpan</button>
                            </td>
                        </form>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- =================== POKOK =================== -->
        <div class="tab-pane fade" id="jurnal">
            <h4>Data Pokok</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Tanggal</th><th>No Bukti</th><th>Pinjaman ID</th>
                        <th>Jurnal ID</th><th>Debet</th><th>Kredit</th><th>Keterangan</th>
                        <th>User</th><th>Jam</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $jurnal->fetch_assoc()) { ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?php echo $row['id']; ?>
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="table" value="pinjtransaksi">
                            </td>
                            <td><?php echo $row['tanggal']; ?></td>
                            <td><?php echo $row['nobukti']; ?></td>
                            <td><?php echo $row['pinjamanid']; ?></td>
                            <td><?php echo $row['jurnalid']; ?></td>
                            <td><input type="text" name="nilai" value="<?php echo $row['debet']; ?>"></td>
                            <td><input type="text" name="nilai2" value="<?php echo $row['kredit']; ?>"></td>
                            <td><?php echo $row['keterangan']; ?></td>
                            <td><?php echo $row['user']; ?></td>
                            <td><?php echo $row['jam']; ?></td>
                            <td>
                                <select name="kolom">
                                    <option value="debet">Debet</option>
                                    <option value="kredit">Kredit</option>
                                </select>
                                <button type="submit" name="update" class="btn btn-sm btn-primary">Simpan</button>
                            </td>
                        </form>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>

        <!-- =================== BUNGA =================== -->
        <div class="tab-pane fade" id="detail">
            <h4>Data Bunga</h4>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th><th>Nama</th><th>Nominal</th><th>Nilai</th><th>Persen</th><th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $detail->fetch_assoc()) { ?>
                    <tr>
                        <form method="post">
                            <td>
                                <?php echo $row['id']; ?>
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <input type="hidden" name="table" value="pinjtransaksidetail">
                            </td>
                            <td><?php echo $row['nama']; ?></td>
                            <td><input type="text" name="nilai" value="<?php echo $row['nominal']; ?>"></td>
                            <td><?php echo $row['nilai']; ?></td>
                            <td><?php echo $row['persen']; ?></td>
                            <td>
                                <input type="hidden" name="kolom" value="nominal">
                                <button type="submit" name="update" class="btn btn-sm btn-primary">Simpan</button>
                            </td>
                        </form>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
