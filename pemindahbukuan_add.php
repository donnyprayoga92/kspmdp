<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Jurnal Umum</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <style>
        .table-jurnal th { 
            text-align: center; 
            background: #f8f9fa; 
        }
        .table-jurnal input, 
        .table-jurnal select { 
            font-size: 14px; 
        }
        .total-box { 
            background: #fff8dc; 
            font-weight: bold; 
            text-align: right; 
        }
        /* Kecilkan font untuk COA & Rekening */
        .select2-results__option, 
        .select2-selection__rendered { 
            font-size: 13px !important; 
            font-family: monospace, sans-serif;
        }
        .select2-container--default .select2-selection--single {
            height: 38px; 
            padding: 6px 8px;
        }
    </style>
</head>

<div class="container my-4">
    <h2 class="text-center mb-4">📘 Input Jurnal Umum</h2>
    

    <form method="post" action="simpan_jurnal.php">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">No. Jurnal</label>
                <input type="text" name="no_jurnal" value="JU<?php echo date('ymdHis'); ?>" class="form-control" readonly>
            </div>
            <div class="col-md-6">
                <label class="form-label">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" required>
            </div>
        </div>

        <div class="mb-3">
            <div class="form-check form-check-inline">
                <input type="checkbox" id="chkCOA" class="form-check-input" checked>
                <label for="chkCOA" class="form-check-label">Tampilkan COA</label>
            </div>
            <div class="form-check form-check-inline">
                <input type="checkbox" id="chkRek" class="form-check-input" checked>
                <label for="chkRek" class="form-check-label">Tampilkan Rekening</label>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-jurnal align-middle" id="jurnalTable">
                <thead class="table-light">
                    <tr>
                        <th style="width: 25%">No. Account / Rekening</th>
                        <th style="width: 20%">Nama Account / Anggota</th>
                        <th style="width: 20%">Keterangan</th>
                        <th style="width: 12%">Debet</th>
                        <th style="width: 12%">Kredit</th>
                        <th style="width: 8%">Aksi</th>
                    </tr>
                </thead>
                <tbody id="jurnalBody">
                    <tr>
                        <td>
                            <select name="accountid[]" class="form-select account select2" style="width:250px" required>
                                <option value="">-- Pilih Akun / Rekening --</option>
                                <optgroup label="Chart of Account (COA)" id="optCOA">
                                    <?php
                                    $coa = $conn->query("SELECT id, nama FROM account ORDER BY id ASC");
                                    while($c = $coa->fetch_assoc()){
                                        echo "<option value='coa_{$c['id']}' data-nama='{$c['nama']}'>{$c['id']} - {$c['nama']}</option>";
                                    }
                                    ?>
                                </optgroup>
                                <optgroup label="Rekening Tabungan Anggota" id="optRek">
                                    <?php
                                    $rek = $conn->query("SELECT t.id, t.norekening, a.nama 
                                                        FROM tabungan t 
                                                        JOIN anggota a ON t.anggotaid=a.id
                                                        WHERE t.norekening LIKE '05%'
                                                        ORDER BY t.norekening ASC");
                                    while($r = $rek->fetch_assoc()){
                                        echo "<option value='tab_{$r['id']}' data-nama='{$r['nama']}'>{$r['norekening']} - {$r['nama']}</option>";
                                    }
                                    ?>
                                </optgroup>
                            </select>
                        </td>
                        <td><input type="text" name="nama_account[]" class="form-control nama_account" readonly></td>
                        <td><input type="text" name="ket_detail[]" class="form-control"></td>
                        <td><input type="text" name="debet[]" class="form-control debet text-end" value="0" oninput="formatRupiah(this)" onkeyup="hitungTotal()"></td>
                        <td><input type="text" name="kredit[]" class="form-control kredit text-end" value="0" oninput="formatRupiah(this)" onkeyup="hitungTotal()"></td>
                        <td class="text-center">
                            <button type="button" onclick="hapusBaris(this)" class="btn btn-sm btn-danger">✖</button>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Total</td>
                        <td><input type="text" id="totalDebet" class="form-control total-box" readonly></td>
                        <td><input type="text" id="totalKredit" class="form-control total-box" readonly></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-end fw-bold">Balance</td>
                        <td colspan="2"><input type="text" id="balance" class="form-control total-box" readonly></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-success btn-sm mb-3" onclick="tambahBaris()">+ Tambah Akun/Rekening</button>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">💾 Simpan</button>
            <button type="reset" class="btn btn-warning">🧹 Hapus</button>
            <a href="index.php" class="btn btn-secondary">❌ Tutup</a>
        </div>
    </form>

<script>
/* === script tetap sama seperti sebelumnya === */
function tambahBaris() {
    let row = document.querySelector("#jurnalBody tr").cloneNode(true);
    row.querySelectorAll("input").forEach(input => { input.value = ""; });
    row.querySelector(".account").value = "";
    document.getElementById("jurnalBody").appendChild(row);
    $(row).find('.select2').select2({
        tags: true,
        placeholder: "-- Pilih Akun / Rekening --",
        allowClear: true
    });
    bindAccountDropdown();
}

function hapusBaris(btn) {
    let row = btn.closest("tr");
    let tbody = document.getElementById("jurnalBody");
    if (tbody.rows.length > 1) {
        row.remove();
    } else {
        alert("Minimal 1 baris harus ada!");
    }
    hitungTotal();
}
function getNumber(el) {
    return parseInt(el.value.replace(/\./g, "").replace(/,/g, "")) || 0;
}
function hitungTotal() {
    let totalDebet = 0;
    let totalKredit = 0;
    document.querySelectorAll(".debet").forEach(el => totalDebet += getNumber(el));
    document.querySelectorAll(".kredit").forEach(el => totalKredit += getNumber(el));
    document.getElementById("totalDebet").value = totalDebet.toLocaleString("id-ID");
    document.getElementById("totalKredit").value = totalKredit.toLocaleString("id-ID");
    let balance = totalDebet - totalKredit;
    document.getElementById("balance").value = balance.toLocaleString("id-ID");
}
function formatRupiah(el) {
    let numberString = el.value.replace(/[^,\d]/g, "");
    if (numberString === "") { el.value = ""; return; }
    let split = numberString.split(",");
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);
    if (ribuan) {
        let separator = sisa ? "." : "";
        rupiah += separator + ribuan.join(".");
    }
    rupiah = split[1] !== undefined ? rupiah + "," + split[1] : rupiah;
    el.value = rupiah;
}
function bindAccountDropdown() {
    document.querySelectorAll(".account").forEach(select => {
        $(select).on("change", function(){
            let nama = this.options[this.selectedIndex]?.getAttribute("data-nama");
            if(!nama) nama = $(this).val();
            this.closest("tr").querySelector(".nama_account").value = nama || "";
        });
    });
}
$(document).ready(function(){
    $('.select2').select2({
        tags: true,
        placeholder: "-- Pilih Akun / Rekening --",
        allowClear: true
    });
    bindAccountDropdown();
});
document.addEventListener("DOMContentLoaded", function(){
    const chkCOA = document.getElementById("chkCOA");
    const chkRek = document.getElementById("chkRek");
    const optCOA = document.getElementById("optCOA");
    const optRek = document.getElementById("optRek");
    function toggleOptgroup() {
        optCOA.style.display = chkCOA.checked ? "block" : "none";
        optRek.style.display = chkRek.checked ? "block" : "none";
    }
    chkCOA.addEventListener("change", toggleOptgroup);
    chkRek.addEventListener("change", toggleOptgroup);
    toggleOptgroup();
});
</script>

</body>
</html>
