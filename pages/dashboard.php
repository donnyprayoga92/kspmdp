<?php
include 'config/db.php';

// === Tentukan tanggal akhir bulan berjalan ===
$akhir_bulan = date('Y-m-t'); // contoh hasil: 2025-10-31

// === FUNGSI HELPER: ambil total nominal dengan fallback ===
function getTotalNominal($conn, $accountid, $akhir_bulan) {
    // Cek apakah data untuk tanggal akhir bulan ada
    $cek = $conn->query("SELECT COUNT(*) AS jml FROM accneraca WHERE accountid='$accountid' AND tanggal='$akhir_bulan'");
    $row = $cek->fetch_assoc();

    if ($row['jml'] > 0) {
        // Jika ada data, ambil nominal tanggal akhir bulan
        $sql = "SELECT COALESCE(SUM(nominal),0) AS total 
                FROM accneraca 
                WHERE accountid='$accountid' 
                AND tanggal='$akhir_bulan'";
    } else {
        // Jika tidak ada data, fallback ke tanggal terakhir yang tersedia
        $sql = "SELECT COALESCE(SUM(nominal),0) AS total 
                FROM accneraca 
                WHERE accountid='$accountid' 
                AND tanggal = (SELECT MAX(tanggal) FROM accneraca WHERE accountid='$accountid')";
    }

    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'] ?? 0;
}

// === Ambil Data Dasar ===
$total_anggota = $conn->query("SELECT COUNT(*) AS total FROM anggota")->fetch_assoc()['total'];
$jumlah_anggota = $conn->query("SELECT COUNT(*) AS total FROM anggota WHERE aktif='1'")->fetch_assoc()['total'];

// === Ambil Data Finansial dengan fallback ===
$jumlah_pinjaman = getTotalNominal($conn, '150-02', $akhir_bulan);
$jumlah_tabungan = getTotalNominal($conn, '510-01', $akhir_bulan);

?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Koperasi Mitra Dana Persada</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
  <link rel="icon" href="/img/LOGO KSP.jpg">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-gray-100 min-h-screen font-sans flex">
  <!-- Main Content -->
  <div class="ml-64 flex-1">
    <header class="bg-white p-4 shadow-sm flex justify-between items-center">
      <h2 class="text-xl font-semibold text-gray-700">Dashboard</h2>
      <span class="text-sm text-gray-500"><?= date('d M Y'); ?></span>
    </header>

    <main class="p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card Jumlah Anggota -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Jumlah Anggota</h3>
          <p class="text-3xl font-bold text-blue-600">
            <?= number_format($total_anggota); ?> /
            <?= number_format($jumlah_anggota); ?>
          </p>
        </div>

        <!-- Card Simpanan -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Total Simpanan</h3>
          <p class="text-3xl font-bold text-green-600">
            <?= number_format($jumlah_tabungan, 0, ',', '.'); ?>
          </p>
        </div>

        <!-- Card Pinjaman -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-md transition">
          <h3 class="text-lg font-semibold text-gray-800 mb-2">Outstanding Pinjaman</h3>
          <p class="text-3xl font-bold text-red-600">
            <?= number_format($jumlah_pinjaman, 0, ',', '.'); ?>
          </p>
        </div>
      </div>
    </main>

    <!-- Image Carousel -->
    <div class="px-6 pb-6">
      <h3 class="text-lg font-semibold text-gray-700 mb-3">Galeri Kegiatan</h3>
      <div class="overflow-x-auto whitespace-nowrap space-x-4 flex">
        <?php
          $imageDir = 'image/';
          $images = glob($imageDir . "*.{jpg,jpeg,png,gif,webp}", GLOB_BRACE);

          if (!empty($images)) {
            foreach ($images as $img) {
              echo '<img src="'.$img.'" class="h-64 max-w-xs rounded-lg shadow-md inline-block cursor-pointer hover:scale-105 transition-transform object-cover" onclick="openModal(this.src)" alt="Gambar">';
            }
          } else {
            echo "<p class='text-gray-500'>Tidak ada gambar di folder <code>image/</code>.</p>";
          }
        ?>
      </div>
    </div>

    <!-- Modal Viewer -->
    <div id="modal" class="fixed inset-0 bg-black bg-opacity-70 hidden items-center justify-center z-50" onclick="closeModal(event)">
      <img id="modalImage" src="" class="max-h-[90vh] max-w-[90vw] rounded-lg shadow-lg border-4 border-white" alt="Preview Gambar">
    </div>
  </div>

  <!-- JS Modal -->
  <script>
    function openModal(src) {
      document.getElementById("modalImage").src = src;
      document.getElementById("modal").classList.remove("hidden");
      document.getElementById("modal").classList.add("flex");
    }

    function closeModal(e) {
      // Tutup jika klik di luar gambar
      if (e.target.id === "modal") {
        document.getElementById("modal").classList.remove("flex");
        document.getElementById("modal").classList.add("hidden");
        document.getElementById("modalImage").src = "";
      }
    }
  </script>
</body>
</html>
