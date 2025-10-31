<!-- sidebar.php -->
<?php
// Wajib ada session_start agar $_SESSION bisa dipakai
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$id = $_SESSION['id'] ?? '';
?>

<div class="sidebar">
    <div class="sidebar-header">KSP Panel</div>
    <ul class="sidebar-menu">
        <li><a href="index.php">🏠 Dashboard</a></li>

        <?php if ($id === 'donny'): ?>
            <!-- Semua menu muncul untuk donny -->
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">👥 Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="kartu.php">💳 Kartu Anggota</a></li>
                    <li><a href="anggota.php">📜 Cetak Form Anggota</a></li>
                    <li><a href="ulangtahun.php">🎉 Ulang Tahun Anggota</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#" class="submenu-toggle">📊 Pinjaman ▾</a>
                <ul class="submenu">
                    <li><a href="pinjamgabung.php">💴 Pinjaman</a></li>
                    <li><a href="pinjaman_rs.php">📑 Repayment Schedule</a></li>
                    <li><a href="injek.php">💴 Bypass Angsuran</a></li>
                    <li><a href="simulasi.php">📠 Hitung Simulasi</a></li>
                    <li><a href="angsuran2.php">📑 Perbandingan Angsuran</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#" class="submenu-toggle">📒 Jurnal PB ▾</a>
                <ul class="submenu">
                    <li><a href="pemindahbukuan_add.php">➕ Input PB</a></li>
                    <li><a href="pemindahbukuan_list.php">📑 Daftar PB</a></li>
                    <li><a href="kasharian.php">💴 Kas Harian Teller</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#" class="submenu-toggle">💴 Simpanan Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="simpanan.php">📑 Cek Saldo Simpanan</a></li>
                    <li><a href="bunga_harian.php">📠 Hitung Bunga harian</a></li>
                    <li><a href="simpanan_wajib.php">💴 Simpanan Wajib</a></li>
                        <ul><a href="simpananwajibsaldo.php">📑 Cek Saldo</a></ul>
                        <ul><a href="simpanan_wajib1.php">💴 Simp.Wajib All </a></ul>
                        
                    <li><a href="depo.php">📠 Hitung Depo</a></li>
                </ul>
            </li>

            <li class="has-submenu">
                <a href="#" class="submenu-toggle">💴 Tools ▾</a>
                <ul class="submenu">
                    <li><a href="wilayah.php">📑 Tambah Wilayah</a></li>
                    <li><a href="pekerjaan.php">📑 Pekerjaan</a></li>
                    <li><a href="pendidikan.php">📑 Pendidikan</a></li>
                </ul>
            </li>

        <?php elseif (in_array($id, ['CS01', 'BO01'])): ?>
            <!-- CS01 hanya bisa lihat Anggota -->
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">👥 Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="kartu.php">💳 Kartu Anggota</a></li>
                    <li><a href="anggota.php">📜 Cetak Form Anggota</a></li>
                    <li><a href="ulangtahun.php">🎉 Ulang Tahun Anggota</a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">📊 Pinjaman ▾</a>
                <ul class="submenu">
                    <li><a href="pinjaman.php">💴 Pinjaman</a></li>
                    <li><a href="simulasi.php">📠 Hitung Simulasi</a></li>
                    <li><a href="angsuran2.php">📑 Perbandingan Angsuran</a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">💴 Simpanan Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="simpanan_wajib.php">💴 Simpanan Wajib</a></li>
                        <ul><a href="simpananwajibsaldo.php">📑 Cek Saldo</a></ul>
                    <li><a href="bunga_harian.php">📠 Hitung Bunga harian</a></li>
                </ul>
            </li>
        <?php elseif ($id === 'CMO'): ?>
            <!-- CS01 hanya bisa lihat Anggota -->
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">👥 Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="anggota.php">📜 Cetak Form Anggota</a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">📊 Pinjaman ▾</a>
                <ul class="submenu">
                    <li><a href="pinjaman.php">💴 Pinjaman</a></li>
                    <li><a href="pinjaman_rs.php">📑 Repayment Schedule</a></li>
                    <li><a href="simulasi.php">📠 Hitung Simulasi</a></li>
                    <li><a href="angsuran2.php">📑 Perbandingan Angsuran</a></li>
                </ul>
            </li>
            <li class="has-submenu">
                <a href="#" class="submenu-toggle">💴 Simpanan Anggota ▾</a>
                <ul class="submenu">
                    <li><a href="bunga_harian.php">📠 Hitung Bunga harian</a></li>
                </ul>
            </li>
        <?php endif; ?>

        <li><a href="logout.php">🚪 Logout</a></li>
    </ul>
</div>


<script>
document.querySelectorAll(".submenu-toggle").forEach(function(el){
    el.addEventListener("click", function(e){
        e.preventDefault();
        let parent = this.parentElement;
        parent.classList.toggle("open");
    });
});
</script>

<style>
/* === Sidebar Style === */
body {
    margin: 0;
    font-family: Arial, sans-serif;
    font-size: 14px;
}
.sidebar {
    width: 220px;
    height: 100vh;
    background: #1c2534;
    color: #fff;
    padding: 20px 0;
    position: fixed;
}
.sidebar-header {
    font-size: 18px;
    font-weight: bold;
    text-align: center;
    margin-bottom: 20px;
}
.sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar-menu li {
    margin-bottom: 5px;
}
.sidebar-menu a {
    display: block;
    padding: 10px 20px;
    color: #ddd;
    text-decoration: none;
    transition: 0.3s;
}
.sidebar-menu a:hover {
    background: #2d3b52;
    color: #fff;
}
.has-submenu > a {
    cursor: pointer;
}
.submenu {
    display: none;
    list-style: none;
    padding-left: 20px;
}
.submenu li a {
    font-size: 14px;
    padding: 8px 20px;
}
.has-submenu.open .submenu {
    display: block;
}
.has-submenu.open > a {
    background: #2d3b52;
    color: #fff;
}
</style>
