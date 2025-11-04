<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// === Query utama daftar pinjaman ===
$query = "
SELECT 
    p.id,
    a.nama AS nama_anggota,
    a.nohp AS no_telp,
    p.nopinjaman,
    p.tanggal,
    p.plafon,
    p.nangsuran AS angsuran_bulan,
    p.jangkawaktu,
    p.angsuranke,
    p.bunga,
    p.angsuran AS metode
FROM pinjaman p
JOIN anggota a ON p.anggotaid = a.id
ORDER BY p.tanggal ASC
";
$result = $conn->query($query);
?>

<style>
.main-content { margin-left: 240px; padding: 30px; background-color: #f8f9fa; min-height: 100vh; }
.content-wrapper { max-width: 1100px; margin: auto; }
.table-hover tbody tr:hover { background-color: #f1faff; transition: 0.2s; }
table { font-size: 12px !important; }
.table thead { background:#2563eb; color:#fff; font-size: 12px;}
.status-paid { color: green; font-weight: 600; }
.status-unpaid { color: red; font-weight: 600; }
.info-table {
    border-collapse: collapse;
    font-size: 13px;
    width: 100%;
    margin-bottom: 10px;
}
.info-table th {
    width: 150px;                /* kontrol lebar label */
    text-align: left;
    font-weight: 600;
    padding: 2px 4px 2px 0;
    vertical-align: top;
}
.info-table td {
    text-align: left;
    padding: 2px 0;
    white-space: nowrap;
}
.info-table .colon {
    display: inline-block;
    width: 10px;                /* posisi titik dua rapat tapi sejajar */
    text-align: center;
}

/* === Untuk mode cetak === */
@media print {
    .info-table th {
        width: 150px !important;
        padding: 2px 4px 2px 0 !important;
    }
    .info-table td {
        padding: 2px 0 !important;
    }
    .info-table .colon {
        width: 10px !important;
    }
}
</style>

<div class="main-content">
<div class="content-wrapper">
<div class="card border-0 shadow-lg">
    <div class="card-header text-white d-flex justify-content-between align-items-center"
        style="background: linear-gradient(90deg, #c2d60eff, #00bfff);">
        <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Data Pinjaman Anggota</h4>
    </div>

    <div class="card-body bg-light">
        <div class="card-body table-responsive p-0">
            <table class="table table-striped table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Anggota</th>
                        <th>No HP</th>
                        <th>Tanggal</th>
                        <th>Plafon</th>
                        <th>Bunga (%)</th>
                        <th>Jangka (Bln)</th>
                        <th>Kewajiban</th>
                        <th>Metode</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="table-light">
                    <?php
                    $no = 1;
                    while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td class="text-start ps-3 fw-semibold"><?= htmlspecialchars($row['nama_anggota']) ?></td>
                            <td><?= $row['no_telp'] ?></td>
                            <td><?= $row['tanggal'] ?></td>
                            <td class="fw-bold text-primary">Rp <?= number_format($row['plafon'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= $row['bunga'] ?></td>
                            <td class="text-center"><?= $row['jangkawaktu'] ?></td>
                            <td class="fw-bold text-primary">Rp <?= number_format($row['angsuran_bulan'], 0, ',', '.') ?></td>
                            <td class="text-center"><?= ucfirst($row['metode']) ?></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    onclick="showSchedule(
                                        '<?= $row['id'] ?>',
                                        '<?= htmlspecialchars($row['nama_anggota'], ENT_QUOTES) ?>',
                                        <?= $row['plafon'] ?>,
                                        <?= $row['bunga'] ?>,
                                        <?= $row['jangkawaktu'] ?>,
                                        '<?= htmlspecialchars($row['metode'], ENT_QUOTES) ?>',
                                        <?= $row['angsuran_bulan'] ?>,
                                        '<?= $row['tanggal'] ?>'
                                    )">
                                    <i class="bi bi-calendar-week"></i> Jadwal
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="scheduleContent" class="mt-4"></div>
</div>
</div>
</div>

<script>
function showSchedule(id, nama, plafon, bunga, bulan, metode, angsuran_bulan, tanggalMulai) {
    const container = document.getElementById("scheduleContent");
    const interest = bunga / 12 / 100;
    let sisa = plafon;

    const startDate = new Date(tanggalMulai);
    let html = `
    <div class="card shadow-lg border-0 mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center"
            style="background: linear-gradient(90deg, #007bff, #00bfff);">
            <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i> Jadwal Angsuran - ${nama}</h5>
            <button class="btn btn-light btn-sm text-primary fw-semibold shadow-sm" onclick="printSchedule()">
                <i class="bi bi-printer"></i> Cetak
            </button>
        </div>
        <div class="card-body bg-white" id="printArea">
            <div class="row mb-3">
                <div class="col-md-6">
                    <table class="table table-sm table-borderless info-table">
                        <tr><th>Nama Anggota</th><td>:&nbsp; ${nama}</td></tr>
                        <tr><th>Plafon</th><td>:&nbsp; Rp ${plafon.toLocaleString('id-ID')}</td></tr>
                        <tr><th>Jangka Waktu</th><td>:&nbsp; ${bulan} bulan</td></tr>
                        <tr><th>Kewajiban/Bulan</th><td>:&nbsp; Rp ${angsuran_bulan.toLocaleString('id-ID')}</td></tr>
                        <tr><th>Tanggal Mulai</th><td>:&nbsp; ${new Date(tanggalMulai).toLocaleDateString('id-ID')}</td></tr>
                    </table>
                </div>
            </div>
            <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle text-center shadow-sm">
                <thead class="table-primary text-dark">
                    <tr>
                        <th>Bulan ke-</th>
                        <th>Jatuh Tempo</th>
                        <th>Total Angsuran</th>
                        <th>Sisa Pinjaman</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tbodySchedule"></tbody>
            </table>
            </div>
        </div>
    </div>`;
    container.innerHTML = html;

    // --- Ambil data status pembayaran via PHP ---
    fetch(`status_pembayaran.php?pinjamanid=${encodeURIComponent(id)}`)
    .then(res => res.json())
    .then(paidData => {
        const tbody = document.getElementById("tbodySchedule");
        let rows = '';
        let totalPokok = 0, totalBunga = 0, totalAngsuran = 0;

        for (let i = 1; i <= bulan; i++) {
            let bungaBulan = sisa * interest;
            let pokok = (metode.toLowerCase() === "anuitas") ? angsuran_bulan - bungaBulan : plafon / bulan;
            if (metode.toLowerCase() === "flat") bungaBulan = plafon * interest;

            if (pokok > sisa) pokok = sisa;
            sisa -= pokok;
            if (sisa < 1e-6) sisa = 0;

            totalPokok += pokok;
            totalBunga += bungaBulan;
            totalAngsuran += angsuran_bulan;

            const dueDate = new Date(startDate);
            dueDate.setMonth(dueDate.getMonth() + i);

            const status = paidData.includes(i)
                ? '<span class="status-paid">✅ Sudah Terbayar</span>'
                : '<span class="status-unpaid">❌ Belum</span>';

            rows += `
            <tr>
                <td>${i}</td>
                <td>${dueDate.toLocaleDateString('id-ID')}</td>
                <td class="fw-bold text-primary">Rp ${Math.round(angsuran_bulan).toLocaleString('id-ID')}</td>
                <td>Rp ${Math.round(sisa).toLocaleString('id-ID')}</td>
                <td>${status}</td>
            </tr>`;
        }
        tbody.innerHTML = rows;
    })
    .catch(err => {
        console.error('Error memuat status pembayaran:', err);
    });
}
function printSchedule() {
    const printContent = document.getElementById('printArea').innerHTML;

    // Buka jendela baru untuk print preview
    const printWindow = window.open('', '_blank');
    printWindow.document.open();
    printWindow.document.write(`
        <!DOCTYPE html>
        <html lang="id">
        <head>
            <meta charset="UTF-8">
            <title>Cetak Jadwal Angsuran</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <style>
                body {
                    font-family: 'Segoe UI', sans-serif;
                    margin: 40px;
                    color: #000;
                }
                .kop {
                    text-align: center;
                    border-bottom: 3px solid #000;
                    padding-bottom: 10px;
                    margin-bottom: 25px;
                }
                .kop h4 {
                    margin: 0;
                    font-weight: 700;
                    color: #003399;
                }
                .kop p {
                    margin: 0;
                    font-size: 13px;
                }
                .judul {
                    text-align: center;
                    margin: 20px 0;
                    font-weight: 600;
                    text-transform: uppercase;
                    color: #000;
                }
                /* === Bagian Informasi Anggota === */
                .info-section {
                    margin-bottom: 20px;
                    font-size: 14px;
                }
                .info-section table {
                    width: 100%;
                }
                .info-section th {
                    width: 140px;
                    text-align: left;
                    font-weight: 600;
                    padding: 4px 8px;
                    vertical-align: top;
                }
                .info-section td {
                    text-align: left;
                    padding: 4px 8px;
                }
                table {
                    font-size: 13px;
                }
                th {
                    background-color: #e6f0ff !important;
                    text-align: center;
                    vertical-align: middle;
                }
                tfoot tr td {
                    font-weight: 700;
                    background-color: #f3f6fa !important;
                }
                @media print {
                    @page {
                        size: A4 portrait;
                        margin: 20mm;
                    }
                    body {
                        margin: 0;
                    }
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <h5 class="judul">Jadwal Pembayaran Pinjaman Anggota</h5>
            <!-- Ambil hanya bagian informasi utama -->
            <div class="info-section">
                <!-- Ambil data dari printContent -->
                ${(() => {
                    // Ekstrak bagian informasi anggota dari printArea
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(printContent, 'text/html');
                    const infoTables = doc.querySelectorAll('.table-borderless');
                    let infoHTML = '';
                    infoTables.forEach(tbl => {
                        infoHTML += tbl.outerHTML;
                    });
                    return infoHTML;
                })()}
            </div>

            <!-- Ambil bagian tabel jadwal -->
            ${(() => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(printContent, 'text/html');
                const scheduleTable = doc.querySelector('.table.table-bordered');
                return scheduleTable ? scheduleTable.outerHTML : '';
            })()}

            <br>
            <div class="text-center no-print mt-4">
                <button class="btn btn-primary" onclick="window.print()">🖨️ Cetak</button>
            </div>
        </body>
        </html>
    `);
    printWindow.document.close();
}

</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
