<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pemindah Bukuan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.3/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.3/themes/base/jquery-ui.css">
    <style>
        body { background:#f4f6fb;}
        table {font-size: 12px !important;}
        .table thead { background:#2563eb; color:#fff; font-size: 12px;}
        .badge-day { font-size:0.85rem; }
        .btn-wa { background:#25D366; color:#fff; border:none; padding:4px 10px; border-radius:6px; }
        .btn-wa:hover { background:#1ebe57; color:#fff; }
    </style>
</head>
<body class="bg-light">

<div class="container mt-4">
    <h2 class="text-center mb-4">📑 Daftar Pemindah Bukuan</h2>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 justify-content-center">
                <div class="col-md-3">
                    <label class="form-label">Dari</label>
                    <input type="date" name="dari" class="form-control" value="<?= $_GET['dari'] ?? '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sampai</label>
                    <input type="date" name="sampai" class="form-control" value="<?= $_GET['sampai'] ?? '' ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cari Nomor Rekening / Nama</label>
                    <input type="text" id="searchAnggota" name="no_rekening" class="form-control" 
                           placeholder="Ketik nomor rekening atau nama" value="<?= $_GET['no_rekening'] ?? '' ?>">
                </div>
                <div class="col-md-12 text-center mt-3">
                    <button type="submit" class="btn btn-primary">🔍 Filter</button>
                    <a href="?" class="btn btn-warning">🔁 Reset</a>
                    <a href="index.php" class="btn btn-secondary">❌ Tutup</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>Tanggal</th>
                            <th>ID Jurnal</th>
                            <th>Keterangan</th>
                            <th>Akun</th>
                            <th>Debet</th>
                            <th>Kredit</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // filter
                    $where = "WHERE 1=1";
                    if (!empty($_GET['dari']) && !empty($_GET['sampai'])) {
                        $dari = $conn->real_escape_string($_GET['dari']);
                        $sampai = $conn->real_escape_string($_GET['sampai']);
                        $where .= " AND j.tanggal BETWEEN '$dari' AND '$sampai'";
                    }
                    if (!empty($_GET['no_rekening'])) {
                        $rek = $conn->real_escape_string($_GET['no_rekening']);
                        $where .= " AND j.keterangan LIKE '%$rek%'";
                    }

                    // ambil jurnal + detail (nama akun dari accheader.nama)
                    $q = $conn->query("
                        SELECT j.id, j.tanggal, j.keterangan, 
                            jd.debet, jd.kredit, jd.accountid, 
                            a.nama AS nama_akun
                        FROM accjurnal j
                        JOIN accjurnaldetail jd ON j.id = jd.id
                        LEFT JOIN account a ON jd.accountid = a.id
                        $where
                        ORDER BY j.tanggal DESC, j.id ASC
                    ");

                    if ($q && $q->num_rows > 0) {
                        $last_jurnal = null;
                        $per_debet = 0;
                        $per_kredit = 0;

                        while($r = $q->fetch_assoc()){
                            // normalisasi angka
                            $debet = isset($r['debet']) ? floatval($r['debet']) : 0;
                            $kredit = isset($r['kredit']) ? floatval($r['kredit']) : 0;
                            $akun_tampil = !empty($r['nama_akun']) ? $r['nama_akun'] : $r['accountid'];

                            // jika jurnal baru, tampilkan tanggal, id, keterangan
                            if ($r['id'] !== $last_jurnal) {
                                // jika bukan jurnal pertama, tampilkan subtotal jurnal sebelumnya
                                if ($last_jurnal !== null) {
                                    echo "<tr class='table-secondary'>
                                            <td></td>
                                            <td style='font-weight:600'>Subtotal</td>
                                            <td></td>
                                            <td></td>
                                            <td class='text-end' style='font-weight:600'>".number_format($per_debet,0,',','.')."</td>
                                            <td class='text-end' style='font-weight:600'>".number_format($per_kredit,0,',','.')."</td>
                                        </tr>";
                                    // reset per jurnal totals
                                    $per_debet = 0;
                                    $per_kredit = 0;
                                }

                                // tampilkan baris pertama jurnal
                                echo "<tr>
                                        <td>{$r['tanggal']}</td>
                                        <td>{$r['id']}</td>
                                        <td>".htmlspecialchars($r['keterangan'])."</td>
                                        <td>".htmlspecialchars($akun_tampil)."</td>
                                        <td class='text-end'>".($debet ? number_format($debet,0,',','.') : '')."</td>
                                        <td class='text-end'>".($kredit ? number_format($kredit,0,',','.') : '')."</td>
                                    </tr>";
                            } else {
                                // baris detail berikutnya untuk jurnal yang sama (kosongkan kolom tanggal/id/keterangan)
                                echo "<tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td>".htmlspecialchars($akun_tampil)."</td>
                                        <td class='text-end'>".($debet ? number_format($debet,0,',','.') : '')."</td>
                                        <td class='text-end'>".($kredit ? number_format($kredit,0,',','.') : '')."</td>
                                    </tr>";
                            }

                            // akumulasi subtotal jurnal
                            $per_debet += $debet;
                            $per_kredit += $kredit;
                            $last_jurnal = $r['id'];
                        }

                        // tampilkan subtotal untuk jurnal terakhir
                        if ($last_jurnal !== null) {
                            echo "<tr class='table-secondary'>
                                    <td></td>
                                    <td style='font-weight:600'>Subtotal</td>
                                    <td></td>
                                    <td></td>
                                    <td class='text-end' style='font-weight:600'>".number_format($per_debet,0,',','.')."</td>
                                    <td class='text-end' style='font-weight:600'>".number_format($per_kredit,0,',','.')."</td>
                                </tr>";
                        }

                    } else {
                        echo "<tr><td colspan='6' class='text-center text-muted'>Tidak ada data</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $("#searchAnggota").autocomplete({
        source: "search_anggota.php",
        minLength: 2
    });
});
</script>

</body>
</html>
