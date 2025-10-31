<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cek Saldo Simpanan Wajib & Daftar Rekening</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .search-box { max-width: 400px; }
        table td, table th { vertical-align: middle; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="text-center mb-4">Cek Saldo Simpanan Wajib</h2>

    <!-- FORM PENCARIAN NO REK -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3" onsubmit="return validateForm()">
                
                <div class="col-md-6 d-flex align-items-end">
                    
                    <a href="?reset=1" class="btn btn-secondary me-2">Reset</a>
                    <a href="index.php" class="btn btn-danger">❌ Tutup</a>
                </div>
            </form>
        </div>
    </div>

    <?php
    // ================================
    // BAGIAN CEK SALDO
    // ================================
    if (!empty($_GET['norek'])) {
        $norek = $conn->real_escape_string($_GET['norek']);

        // Cari tabungan dan anggota
        $tab = $conn->query("
            SELECT tb.id, a.nama, a.alamat
            FROM tabungan tb
            JOIN anggota a ON tb.anggotaid = a.id
            WHERE tb.norekening='$norek'
        ")->fetch_assoc();

        if ($tab) {
            $tabunganid = $tab['id'];
            $nama = $tab['nama'];
            $alamat = $tab['alamat'];

            // Info Anggota
            echo "
            <div class='alert alert-info'>
                <strong>No Rekening:</strong> $norek <br>
                <strong>Nama Anggota:</strong> $nama <br>
                <strong>Alamat:</strong> $alamat
            </div>
            ";

            // Ringkasan Saldo
            $sql = "
                SELECT j.kode, j.nama, 
                       SUM(IFNULL(t.debet,0)) - SUM(IFNULL(t.kredit,0)) AS saldo
                FROM tabtransaksi t
                JOIN tabungan tb ON t.tabunganid = tb.id
                JOIN tabjenis j ON tb.jenisid = j.kode
                WHERE tb.id = '$tabunganid'
                GROUP BY j.kode, j.nama
            ";
            $q = $conn->query($sql);

            if ($q->num_rows > 0) {
                echo "<h4>Ringkasan Saldo</h4>";
                echo "<table class='table table-bordered table-striped table-sm'>
                        <thead class='table-primary'>
                            <tr>
                                <th>Kode Jenis</th>
                                <th>Nama Simpanan</th>
                                <th class='text-end'>Saldo</th>
                            </tr>
                        </thead>
                        <tbody>";
                $total = 0;
                while($r = $q->fetch_assoc()) {
                    $total += $r['saldo'];
                    echo "<tr>
                            <td>{$r['kode']}</td>
                            <td>{$r['nama']}</td>
                            <td class='text-end'>".number_format($r['saldo'],0,',','.')."</td>
                          </tr>";
                }
                echo "<tr class='fw-bold table-light'>
                        <td colspan='2'>TOTAL</td>
                        <td class='text-end'>".number_format($total,0,',','.')."</td>
                      </tr>";
                echo "</tbody></table>";
            }

            // Daftar Transaksi
            $sql2 = "
                SELECT tanggal, nobukti, keterangan, debet, kredit, saldo
                FROM tabtransaksi
                WHERE tabunganid='$tabunganid'
                ORDER BY tanggal ASC, id ASC
            ";
            $q2 = $conn->query($sql2);

            if ($q2->num_rows > 0) {
                echo "<h4>Daftar Transaksi</h4>";
                echo "<div class='table-responsive'>
                        <table class='table table-hover table-bordered table-sm'>
                            <thead class='table-secondary'>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>No Bukti</th>
                                    <th>Keterangan</th>
                                    <th class='text-end'>Debet</th>
                                    <th class='text-end'>Kredit</th>
                                    <th class='text-end'>Saldo</th>
                                </tr>
                            </thead>
                            <tbody>";
                while($r = $q2->fetch_assoc()) {
                    echo "<tr>
                            <td>{$r['tanggal']}</td>
                            <td>{$r['nobukti']}</td>
                            <td>{$r['keterangan']}</td>
                            <td class='text-end'>".number_format($r['debet'],0,',','.')."</td>
                            <td class='text-end'>".number_format($r['kredit'],0,',','.')."</td>
                            <td class='text-end'>".number_format($r['saldo'],0,',','.')."</td>
                          </tr>";
                }
                echo "</tbody></table></div>";
            } else {
                echo "<div class='alert alert-warning'>Tidak ada transaksi untuk rekening: <b>$norek</b></div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Nomor rekening tidak ditemukan: <b>$norek</b></div>";
        }
    }
    ?>

    <hr class="my-5">

    <!-- ================================= -->
    <!-- BAGIAN DAFTAR NOMOR REKENING -->
    <!-- ================================= -->
    <h2 class="text-center mb-4">Daftar Nomor Rekening Anggota</h2>

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <input type="text" id="searchNama" class="form-control search-box" placeholder="Cari berdasarkan nama anggota...">
        </div>
    </div>

    <?php
    if ($conn->connect_error) {
        die("<div class='alert alert-danger'>Koneksi database gagal: " . $conn->connect_error . "</div>");
    }

    $sql_list = "
        SELECT 
            CAST(tb.norekening AS CHAR) AS norekening,
            a.nama, 
            a.alamat, 
            j.nama AS jenis_simpanan
        FROM tabungan tb
        LEFT JOIN anggota a ON tb.anggotaid = a.id
        LEFT JOIN tabjenis j ON tb.jenisid = j.id
        WHERE CAST(tb.norekening AS CHAR) LIKE '02%'
        ORDER BY a.nama ASC;
    ";

    $result = $conn->query($sql_list);

    if (!$result) {
        die("<div class='alert alert-danger'>Query Error: " . $conn->error . "</div>");
    }

    if ($result->num_rows > 0) {
        echo "
        <div class='card shadow-sm'>
            <div class='card-body'>
                <div class='table-responsive'>
                    <table class='table table-striped table-hover table-bordered align-middle' id='tabelRekening'>
                        <thead class='table-primary text-center'>
                            <tr>
                                <th>No</th>
                                <th>Nomor Rekening</th>
                                <th>Nama Anggota</th>
                                <th>Alamat</th>
                                <th>Jenis Simpanan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
        ";

        $no = 1;
        while ($row = $result->fetch_assoc()) {
            $norek = htmlspecialchars($row['norekening']);
            $nama = htmlspecialchars($row['nama']);
            $alamat = htmlspecialchars($row['alamat']);
            $jenis = htmlspecialchars($row['jenis_simpanan']);

            echo "
                <tr>
                    <td class='text-center'>$no</td>
                    <td><strong>$norek</strong></td>
                    <td>$nama</td>
                    <td>$alamat</td>
                    <td>$jenis</td>
                    <td class='text-center'>
                        <a href='?norek=$norek' class='btn btn-sm btn-success'>💰 Cek Saldo</a>
                    </td>
                </tr>
            ";
            $no++;
        }

        echo "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>";
    } else {
        echo "<div class='alert alert-warning'>Belum ada nomor rekening yang diawali '02'.</div>";
    }
    ?>

</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Validasi & Filter -->
<script>
function validateForm() {
    const norek = document.getElementById('norek').value.trim();
    if (!/^02\d+$/.test(norek)) {
        alert('Nomor rekening harus diawali dengan 02.');
        return false;
    }
    return true;
}

document.getElementById('searchNama').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#tabelRekening tbody tr');
    rows.forEach(row => {
        const nama = row.cells[2].textContent.toLowerCase();
        row.style.display = nama.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
