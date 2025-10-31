<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
require_once __DIR__ . '/phpqrcode/qrlib.php';

// =======================
// Function Generate QR
// =======================
function generateQRCodeWithLogo($text, $namaAnggota, $logoPath = 'phpqrcode/logo1.png') {
    $tempDir = 'qrcodes/';
    if (!file_exists($tempDir)) mkdir($tempDir, 0777, true);

    $cleanName = preg_replace('/[^A-Za-z0-9]/', '_', $namaAnggota);
    $hash = substr(md5($text), 0, 8);
    $filename = "{$cleanName}_{$hash}.png";
    $filePath = $tempDir . $filename;

    foreach (glob("{$tempDir}{$cleanName}_*.png") as $oldFile) {
        if ($oldFile !== $filePath) unlink($oldFile);
    }

    QRcode::png($text, $filePath, QR_ECLEVEL_H, 6);

    if (file_exists($logoPath)) {
        $QR = imagecreatefrompng($filePath);
        $logo = imagecreatefrompng($logoPath);

        $QR_width = imagesx($QR);
        $QR_height = imagesy($QR);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);

        $logo_qr_width = (int)($QR_width / 2);
        $scale = $logo_width / $logo_qr_width;
        $logo_qr_height = (int)($logo_height / $scale);
        $from_width = (int)(($QR_width - $logo_qr_width) / 2);
        $from_height = (int)(($QR_height - $logo_qr_height) / 2);

        $finalQR = imagecreatetruecolor($QR_width, $QR_height);
        imagesavealpha($finalQR, true);
        $transparent = imagecolorallocatealpha($finalQR, 0, 0, 0, 127);
        imagefill($finalQR, 0, 0, $transparent);

        imagecopy($finalQR, $QR, 0, 0, 0, 0, $QR_width, $QR_height);
        imagealphablending($finalQR, true);
        imagecopyresampled(
            $finalQR, $logo,
            $from_width, $from_height,
            0, 0,
            $logo_qr_width, $logo_qr_height,
            $logo_width, $logo_height
        );

        imagepng($finalQR, $filePath);
        imagedestroy($finalQR);
    }

    return $filePath;
}

// =======================
// Query Data Anggota
// =======================
$anggota_result = $conn->query("SELECT id, noanggota, nama, foto FROM anggota ORDER BY noanggota ASC");

$anggotaid   = $_GET['anggotaid'] ?? '';
$lihat_kartu = isset($_GET['lihat_kartu']);
$anggota     = null;
$simpanan    = [];
$qr_file     = '';

if ($anggotaid) {
    $stmt = $conn->prepare("SELECT noanggota, nama, alamat FROM anggota WHERE id = ?");
    $stmt->bind_param("s", $anggotaid);
    $stmt->execute();
    $anggota = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        SELECT s.norekening, j.nama AS nama_jenis 
        FROM tabungan s 
        LEFT JOIN tabjenis j ON s.jenisid = j.id 
        WHERE s.anggotaid = ?
    ");
    $stmt->bind_param("s", $anggotaid);
    $stmt->execute();
    $simpanan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $qr_content = "No Anggota: {$anggota['noanggota']}\n";
    $qr_content .= "Nama: {$anggota['nama']}\n";
    $qr_content .= "Alamat: {$anggota['alamat']}\nSimpanan:\n";
    foreach ($simpanan as $s) {
        $qr_content .= "- {$s['norekening']} ({$s['nama_jenis']})\n";
    }

    $qr_file = generateQRCodeWithLogo($qr_content, $anggota['nama']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kartu & QR Anggota</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- CSS untuk kartu -->
    <style>
        .qr { margin-top: 20px; }
        .card { width: 600px; height: 360px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.2); background-color: white;}
        .front, .back {position: absolute; width: 100%; height: 100%; background-size: cover; background-position: center; border-radius: 10px;}
        .front { background-image: url('img/KTADEPAN.png'); z-index: 1; }
        .back  { background-image: url('img/KTA BELAKANG.png'); z-index: 1; }
        .qr img { position: absolute; top: 150px; right: 30px; width: 120px; z-index: 2; }
        .data {
            position: absolute;
            top: 156px;
            left: 205px;
            z-index: 2;
            font-size: 16px;
            color: #001133;
            font-weight: bold;

            max-width: 250px;        /* batas kanan */
            word-wrap: break-word;   /* agar kata panjang bisa terpotong jika perlu */
            line-height: 1.5;
            }
        
    </style>
</head>
<body class="bg-light">

<div class="container py-4">
    <h2 class="mb-4 text-center">Kartu Anggota dan QR Code</h2>

    <!-- Form Pencarian -->
    <form method="get" class="row g-2 mb-4">
        <div class="col-sm-6 col-md-4">
            <input type="text" name="cari_nama"
                   class="form-control"
                   value="<?= htmlspecialchars($_GET['cari_nama'] ?? '') ?>"
                   placeholder="Masukkan nama anggota">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary">Cari</button>
            <a href="<?= strtok($_SERVER["REQUEST_URI"], '?') ?>" class="btn btn-outline-secondary">Reset</a>
            <a href="index.php" class="btn btn-outline-dark">← Kembali</a>
        </div>
    </form>

    <?php
    $cari_nama = $_GET['cari_nama'] ?? '';
    $daftar = [];

    if ($cari_nama) {
        $anggota_result->data_seek(0);
        while ($row = $anggota_result->fetch_assoc()) {
            if (stripos($row['nama'], $cari_nama) !== false) $daftar[] = $row;
        }
        echo "<p class='text-muted'><strong>Hasil:</strong> " . count($daftar) . " anggota ditemukan untuk '<em>" . htmlspecialchars($cari_nama) . "</em>'</p>";
    } else {
        $anggota_result->data_seek(0);
        while ($row = $anggota_result->fetch_assoc()) $daftar[] = $row;
    }
    ?>

    <!-- Daftar Anggota -->
    <?php if (empty($anggotaid)): ?>
    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-5 row-cols-lg-6 g-2">
        <?php foreach ($daftar as $row): ?>
            <div class="col">
                <div class="album-card border rounded shadow-sm">

                    <!-- Foto Anggota -->
                    <?php if (!empty($row['foto'])): ?>
                        <img src="data:image/jpeg;base64,<?= base64_encode($row['foto']) ?>" 
                            alt="Foto <?= htmlspecialchars($row['nama']) ?>" 
                            class="w-100"
                            style="height: 100px; object-fit: cover;">
                    <?php else: ?>
                        <img src="img/LOGO KSP.jpg" 
                            alt="Foto Default" 
                            class="w-100"
                            style="height: 100px; object-fit: cover;">
                    <?php endif; ?>

                    <!-- Overlay muncul saat hover -->
                    <div class="fw-semibold text-center px-2" style="font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;">
                        <?= htmlspecialchars($row['nama']) ?>
                    </div>
                    <div class="small text-center" style="font-size: 11px;">
                        <?= htmlspecialchars($row['noanggota']) ?>
                    </div>
                    <div class="album-overlay d-flex flex-column justify-content-center align-items-center text-white text-center p-1">
                        <div class="mt-1 d-flex gap-1">
                            <a href="?anggotaid=<?= $row['id'] ?>&lihat_kartu=1" 
                            class="btn btn-sm btn-light py-0 px-2" 
                            style="font-size: 11px;">Kartu</a>
                            <a href="?anggotaid=<?= $row['id'] ?>" 
                            class="btn btn-sm btn-outline-light py-0 px-2" 
                            style="font-size: 11px;">Detail</a>
                        </div>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <style>
    .album-card {
        position: relative;
        overflow: hidden;
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .album-card img {
        transition: transform 0.3s ease;
    }

    /* Efek zoom saat hover */
    .album-card:hover img {
        transform: scale(1.1);
    }

    /* Overlay */
    .album-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .album-card:hover .album-overlay {
        opacity: 1;
    }
    </style>
    <?php endif; ?>




    <!-- Detail Anggota -->
    <?php if ($anggota && !$lihat_kartu): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h4 class="card-title">Informasi Anggota</h4>
                <p><strong>No Anggota:</strong> <?= $anggota['noanggota'] ?></p>
                <p><strong>Nama:</strong> <?= $anggota['nama'] ?></p>
                <p><strong>Alamat:</strong> <?= $anggota['alamat'] ?></p>
            </div>
        </div>

        <h5>Daftar Simpanan</h5>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr><th>No Rekening</th><th>Jenis Simpanan</th></tr>
            </thead>
            <tbody>
            <?php foreach ($simpanan as $s): ?>
                <tr><td><?= $s['norekening'] ?></td><td><?= $s['nama_jenis'] ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <div class="mt-4">
            <h5>QR Code</h5>
            <img src="<?= $qr_file ?>" alt="QR Code" class="img-thumbnail" style="max-width:200px"><br>
            <small class="text-muted">File: <code><?= basename($qr_file) ?></code></small>
        </div>

    <?php elseif ($anggota && $lihat_kartu): ?>
        
        <div id="kartuArea" style="width: 100%; padding: 10px; background: #fff; display: flex; justify-content: center; gap: 40px; flex-wrap: wrap;">
        <div class="card">
            <div class="front"></div>
            <div class="data">
                <?= htmlspecialchars($anggota['noanggota']) ?><br>
                <span id="namaAnggota"><?= htmlspecialchars($anggota['nama']) ?></span><br>
                <?= htmlspecialchars($anggota['alamat']) ?>
            </div>
            <div class="qr">
                <img src="<?= htmlspecialchars($qr_file) ?>" alt="QR Code">
            </div>
        </div>

            <!-- Kartu Belakang -->
            <div class="card">
                <div class="back"></div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 mt-4">
            <button class="btn btn-success" onclick="downloadAsPNG()">Cetak PNG</button>
            <button class="btn btn-danger" onclick="downloadAsPDF()">Cetak PDF</button>
        </div>
    <?php endif; ?>
</div>


<script>
function getNamaAnggota() {
    const namaEl = document.getElementById('namaAnggota');
    return namaEl ? namaEl.textContent.trim().replace(/\s+/g, '_') : 'anggota';
}

function downloadAsPNG() {
    const element = document.getElementById('kartuArea');
    const namaAnggota = getNamaAnggota();

    const scale = 3; // meningkatkan kualitas 3x lipat
    html2canvas(element, {
        useCORS: true,
        backgroundColor: "#ffffff",
        scale: scale,
        allowTaint: false
    }).then(canvas => {
        const link = document.createElement('a');
        link.download = 'kartu-anggota-' + namaAnggota + '.png';
        link.href = canvas.toDataURL('image/png');
        link.click();
    });
}

async function downloadAsPDF() {
    const element = document.getElementById('kartuArea');
    const namaAnggota = getNamaAnggota();

    const scale = 3; // kualitas tinggi
    const canvas = await html2canvas(element, {
        useCORS: true,
        backgroundColor: "#ffffff",
        scale: scale,
        allowTaint: false
    });

    const imgData = canvas.toDataURL('image/png');

    const { jsPDF } = window.jspdf;

    // Ukuran asli (mm) standar ID Card horizontal (ID-1): 85.6mm x 53.98mm
    const dpi = 300;
    const mmToPt = mm => (mm * dpi) / 25.4 * 0.75; // 1 pt = 1/72 inchi
    const width = mmToPt(85.6);
    const height = mmToPt(53.98);

    const pdf = new jsPDF({
        orientation: 'landscape',
        unit: 'pt',
        format: [width, height]
    });

    pdf.addImage(imgData, 'PNG', 0, 0, width, height);
    pdf.save('kartu-anggota-' + namaAnggota + '.pdf');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>