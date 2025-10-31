<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

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

<!-- Styling tambahan -->
<style>
    /* Pastikan jarak konten dari sidebar */
    .main-content {
        margin-left: 240px; /* sesuaikan dengan lebar sidebar kamu */
        padding: 30px;
        background-color: #f8f9fa;
        min-height: 100vh;
    }

    /* Buat konten di tengah area kanan */
    .content-wrapper {
        max-width: 1100px;
        margin: auto;
    }

    /* Sedikit efek hover pada tabel */
    .table-hover tbody tr:hover {
        background-color: #f1faff;
        transition: 0.2s;
    }
    table {font-size: 12px !important;}
    .table thead { background:#2563eb; color:#fff; font-size: 12px;}
</style>

<!-- Main Container -->
<div class="main-content">
    <div class="content-wrapper">
        <div class="card border-0 shadow-lg">
            <div class="card-header text-white d-flex justify-content-between align-items-center"
                style="background: linear-gradient(90deg, #007bff, #00bfff);">
                <h4 class="mb-0"><i class="bi bi-cash-coin me-2"></i> Data Pinjaman Anggota</h4>
            </div>

            <div class="card-body bg-light">
                <!-- Tabs -->
                

                <div class="tab-content" id="pinjamanTabContent">
                    <!-- DATA PINJAMAN -->
                    <div class="tab-pane fade show active" id="data" role="tabpanel">
                        <div class="card-body table-responsive p-0">
                            <table class="table table-striped table-hover align-middle mb-0">
                                <thead class="bg-primary text-white">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Anggota</th>
                                        <th>No HP</th>
                                        <th>Tanggal</th>
                                        <th>Plafon</th>
                                        <th>Metode</th>
                                        <th>Bunga (%)</th>
                                        <th>Jangka (Bln)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="table-light">
                                    <?php
                                    $no = 1;
                                    while ($row = $result->fetch_assoc()) {
                                        $badgeColor = $row['metode'] === 'Anuitas' ? 'bg-success' : 'bg-warning text-dark';
                                        echo "<tr>
                                                <td>{$no}</td>
                                                <td class='text-start ps-3 fw-semibold'>{$row['nama_anggota']}</td>
                                                <td>{$row['no_telp']}</td>
                                                <td>{$row['tanggal']}</td>
                                                <td class='fw-bold text-primary'>Rp " . number_format($row['plafon'], 0, ',', '.') . "</td>
                                                <td><span class='badge {$badgeColor} px-3 py-2'>{$row['metode']}</span></td>
                                                <td>{$row['bunga']}</td>
                                                <td>{$row['jangkawaktu']}</td>
                                                <td>
                                                    <button class='btn btn-sm btn-outline-primary rounded-pill px-3' 
                                                        onclick=\"showSchedule('{$row['id']}', '{$row['nama_anggota']}', {$row['plafon']}, {$row['bunga']}, {$row['jangkawaktu']}, '{$row['metode']}')\">
                                                        <i class='bi bi-calendar-week'></i> Jadwal
                                                    </button>
                                                </td>
                                            </tr>";
                                        $no++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- REPAYMENT SCHEDULE -->
                    <div class="tab-pane fade" id="repayment" role="tabpanel">
                        <div id="scheduleContent" class="mt-4"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showSchedule(id, nama, plafon, bunga, bulan, metode) {
    const container = document.getElementById("scheduleContent");
    const interest = bunga / 100 / 12;
    let sisa = plafon;
    let totalPokok = 0, totalBunga = 0, totalAngsuran = 0;

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
                <div class="text-center mb-4">
                    <h5 class="fw-bold mb-1 text-primary">KOPERASI SIMPAN PINJAM</h5>
                    <p class="mb-1">Jadwal Pembayaran Pinjaman Anggota</p>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th>Nama Anggota</th><td>: ${nama}</td></tr>
                            <tr><th>Metode</th><td>: ${metode}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm table-borderless">
                            <tr><th>Plafon</th><td>: Rp ${plafon.toLocaleString('id-ID')}</td></tr>
                            <tr><th>Bunga</th><td>: ${bunga}%</td></tr>
                            <tr><th>Jangka Waktu</th><td>: ${bulan} bulan</td></tr>
                        </table>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle text-center shadow-sm">
                        <thead class="table-primary text-dark">
                            <tr>
                                <th>Bulan ke-</th>
                                <th>Pokok</th>
                                <th>Bunga</th>
                                <th>Total Angsuran</th>
                                <th>Sisa Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody>
    `;

    // === Perhitungan Anuitas ===
    if (metode === 'Anuitas') {
        const angsuranTetap = plafon * (interest / (1 - Math.pow(1 + interest, -bulan)));

        for (let i = 1; i <= bulan; i++) {
            const bungaBulan = sisa * interest;
            let pokok = angsuranTetap - bungaBulan;
            if (i === bulan) pokok = sisa;
            sisa -= pokok;
            if (sisa < 1e-6) sisa = 0;

            totalPokok += pokok;
            totalBunga += bungaBulan;
            totalAngsuran += (pokok + bungaBulan);

            html += `<tr>
                        <td>${i}</td>
                        <td>Rp ${Math.round(pokok).toLocaleString('id-ID')}</td>
                        <td>Rp ${Math.round(bungaBulan).toLocaleString('id-ID')}</td>
                        <td class="fw-bold text-primary">Rp ${Math.round(pokok + bungaBulan).toLocaleString('id-ID')}</td>
                        <td>Rp ${Math.round(sisa).toLocaleString('id-ID')}</td>
                    </tr>`;
        }
    }

    // === Perhitungan Flat ===
    else if (metode === 'Flat') {
        const pokokTetap = plafon / bulan;
        const bungaTetap = plafon * interest;

        for (let i = 1; i <= bulan; i++) {
            let pokok = pokokTetap;
            if (i === bulan) pokok = sisa;
            const total = pokok + bungaTetap;
            sisa -= pokok;
            if (sisa < 1e-6) sisa = 0;

            totalPokok += pokok;
            totalBunga += bungaTetap;
            totalAngsuran += total;

            html += `<tr>
                        <td>${i}</td>
                        <td>Rp ${Math.round(pokok).toLocaleString('id-ID')}</td>
                        <td>Rp ${Math.round(bungaTetap).toLocaleString('id-ID')}</td>
                        <td class="fw-bold text-success">Rp ${Math.round(total).toLocaleString('id-ID')}</td>
                        <td>Rp ${Math.round(sisa).toLocaleString('id-ID')}</td>
                    </tr>`;
        }
    }

    // === Total Keseluruhan ===
    html += `
                        </tbody>
                        <tfoot class="table-info fw-bold text-dark">
                            <tr>
                                <td>Total</td>
                                <td>Rp ${Math.round(totalPokok).toLocaleString('id-ID')}</td>
                                <td>Rp ${Math.round(totalBunga).toLocaleString('id-ID')}</td>
                                <td>Rp ${Math.round(totalAngsuran).toLocaleString('id-ID')}</td>
                                <td>-</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    `;

    container.innerHTML = html;
    new bootstrap.Tab(document.querySelector('#repayment-tab')).show();
}

// === Fungsi Cetak ===
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

            <br><br><br>
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
