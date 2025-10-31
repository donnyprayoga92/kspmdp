<?php
include __DIR__ . '/config/db.php';
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Perhitungan Bunga Harian</title>
<style>
  :root {
    --bg: #f4f6fb;
    --card: #ffffff;
    --muted: #6b7280;
    --accent: #2563eb;
    --accent-light: #dbeafe;
    --border: #e5e7eb;
  }
  body {
    font-family: 'Segoe UI', Roboto, Inter, system-ui, Arial;
    background: var(--bg);
    color: #1f2937;
    margin: 0;
    
  }
  .container {
    max-width: 1080px;
    margin: 0 auto;
  }
  header h1 {
    margin: 0 0 8px;
    font-size: 24px;
    color: var(--accent);
  }
  header p {
    margin: 0 0 20px;
    color: var(--muted);
    font-size: 14px;
  }
  .card {
    background: var(--card);
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.05);
    margin-bottom: 20px;
    transition: transform .2s ease;
  }
  .card:hover {
    transform: translateY(-2px);
  }
  label {
    display: block;
    font-size: 14px;
    margin-bottom: 6px;
    font-weight: 500;
  }
  .row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
  }
  input[type="date"], input[type="number"], input[type="text"], select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    min-width: 0;
    background: #fff;
    transition: border .2s;
  }
  input:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 2px var(--accent-light);
  }
  table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
    font-size: 13px;
  }
  th, td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    text-align: left;
  }
  th {
    background: var(--accent-light);
    color: #1e3a8a;
    position: sticky;
    top: 0;
    font-weight: 600;
  }
  tr:nth-child(even) td {
    background: #f9fafb;
  }
  tr:hover td {
    background: #f3f4f6;
  }
  .btn {
    background: var(--accent);
    color: white;
    padding: 9px 14px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: background .2s;
  }
  .btn:hover {
    background: #1d4ed8;
  }
  .btn.ghost {
    background: transparent;
    color: var(--accent);
    border: 1px solid var(--accent);
  }
  .btn.ghost:hover {
    background: var(--accent-light);
  }
  .small {
    font-size: 13px;
    color: var(--muted);
  }
  .controls {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
  }
  .right { text-align: right; }
  .result-total { font-weight: 700; font-size: 15px; color: var(--accent); }
  .notice { font-size: 13px; color: var(--muted); margin-top: 10px; }
  @media(max-width: 760px) {
    .row { flex-direction: column; }
  }
  input[type="date"],
  input[type="month"],
  input[type="number"],
  input[type="text"],
  select {
    padding: 10px 12px;
    border: 1px solid var(--border);
    border-radius: 8px;
    font-size: 14px;
    min-width: 0;
    background: #fff;
    transition: border .2s;
  }
</style>
</head>
<body>
  <div class="container mt-4" style="flex:1; padding:32px">
    <header>
      <h1>Perhitungan Bunga Harian</h1>
      <p>Masukkan saldo harian, sistem akan menghitung bunga berdasarkan saldo efektif per periode dengan metode harian.</p>
    </header>

    <!-- Input Card -->
    <div class="card">
      <div class="row" style="align-items:flex-end">
        <div>
          <label for="month">Bulan/Tahun</label>
          <input id="month" type="month" value="2025-08" />
        </div>
        <div>
          <label for="rate">Suku Bunga p.a. (%)</label>
          <input id="rate" type="number" step="0.01" value="3.00" />
        </div>
        <div>
          <label for="threshold">Ambang Bunga (Rp)</label>
          <input id="threshold" type="number" step="1" value="100000" />
        </div>
        <div class="controls">
          <button id="addRow" class="btn ghost">+ Tambah Baris</button>
          <button id="compute" class="btn">Hitung</button>
        </div>
      </div>

      <div style="margin-top:16px">
        <label>Daftar Saldo</label>
        <table id="inputTable">
          <thead>
            <tr>
              <th style="width:150px">Tanggal</th>
              <th>Saldo (Rp)</th>
              <th style="width:100px">Aksi</th>
            </tr>
          </thead>
          <tbody id="inputBody"></tbody>
        </table>
        <div class="notice">Tips: Tambahkan baris saldo awal (mis. 1 Agustus) agar perhitungan valid.</div>
      </div>
    </div>

    <!-- Result Card -->
    <div id="resultCard" class="card" style="display:none">
      <h3 style="margin-top:0">Hasil Perhitungan</h3>
      <div id="summary" style="margin-bottom:12px;font-size:14px"></div>
      <div style="overflow:auto; max-height:420px; border:1px solid var(--border); border-radius:8px">
        <table id="resultTable">
          <thead>
            <tr>
              <th>Tgl Mulai</th>
              <th>Tgl Selesai</th>
              <th>Hari</th>
              <th class="right">Saldo</th>
              <th class="right">Dasar Bunga</th>
              <th class="right">Bunga/Hari</th>
              <th class="right">Bunga Periode</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:14px">
        <div class="small">Basis: 365 hari/tahun. Saldo ≤ ambang tidak dikenakan bunga.</div>
        <div class="controls">
          <button id="downloadCsv" class="btn">Unduh CSV</button>
          <button id="clear" class="btn ghost">Reset</button>
        </div>
      </div>
    </div>
  </div>

<script>
(function(){
  const addRowBtn = document.getElementById('addRow');
  const inputBody = document.getElementById('inputBody');
  const computeBtn = document.getElementById('compute');
  const resultCard = document.getElementById('resultCard');
  const resultTableBody = document.querySelector('#resultTable tbody');
  const summaryDiv = document.getElementById('summary');
  const downloadCsvBtn = document.getElementById('downloadCsv');
  const clearBtn = document.getElementById('clear');

  // Format ke Rupiah (tanpa "Rp")
  function formatRupiah(value) {
    const numberString = value.replace(/\D/g, "");
    return numberString ? Number(numberString).toLocaleString("id-ID") : "";
  }

  // Attach formatter ke semua input saldo
  function attachRupiahFormatter(el) {
    el.addEventListener("input", function(e) {
      e.target.value = formatRupiah(e.target.value);
    });
  }

  // Apply ke input yang sudah ada
  document.querySelectorAll(".dSaldo").forEach(attachRupiahFormatter);

  function makeRow(dateVal='', saldoVal='0'){
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="date" class="dTanggal" value="${dateVal}" /></td>
      <td><input type="text" class="dSaldo rupiah" value="${formatRupiah(saldoVal)}" /></td>
      <td><button class="del btn ghost">Hapus</button></td>
    `;
    inputBody.appendChild(tr);
    tr.querySelector('.del').addEventListener('click', ()=> tr.remove());
    attachRupiahFormatter(tr.querySelector(".dSaldo"));
  }

  addRowBtn.addEventListener('click', () => makeRow('', '0'));

  document.querySelectorAll('.del').forEach(btn => 
    btn.addEventListener('click', (e)=> e.target.closest('tr').remove())
  );

  function toDateISOInput(d){
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth()+1).padStart(2,'0');
    const dd = String(d.getDate()).padStart(2,'0');
    return `${yyyy}-${mm}-${dd}`;
  }

  function parseInputs(){
    const rows = [];
    const trs = inputBody.querySelectorAll('tr');
    trs.forEach(tr => {
      const d = tr.querySelector('.dTanggal').value;
      let s = tr.querySelector('.dSaldo').value.replace(/\D/g, "");
      if(!d) return;
      const dateObj = new Date(d + 'T00:00:00');
      const saldo = Number(s) || 0;
      rows.push({date: dateObj, saldo});
    });
    rows.sort((a,b)=> a.date - b.date);
    return rows;
  }

  function dateMinusOne(d){
    const nd = new Date(d);
    nd.setDate(nd.getDate() - 1);
    nd.setHours(0,0,0,0);
    return nd;
  }

  function formatRp(num){
    return Number(num).toLocaleString('id-ID', {minimumFractionDigits:2, maximumFractionDigits:2});
  }

  computeBtn.addEventListener('click', ()=>{
    resultTableBody.innerHTML = '';
    const entries = parseInputs();
    if(entries.length === 0){
      alert('Masukkan minimal 1 baris saldo dengan tanggal.');
      return;
    }

    const monthVal = document.getElementById('month').value;
    if(!monthVal){
      alert('Pilih bulan/tahun terlebih dahulu.');
      return;
    }
    const [y,m] = monthVal.split('-').map(Number);
    const monthStart = new Date(y, m-1, 1,0,0,0,0);
    const monthEnd = new Date(y, m, 0,0,0,0,0); // last day of month
    const annualRate = Number(document.getElementById('rate').value) || 0;
    const threshold = Number(document.getElementById('threshold').value) || 0;
    const dailyRate = annualRate/100/365;

    // Build segments: each entry applies from its date until day before next entry (or month end)
    // We consider effect only within the chosen month.
    const segments = [];
    for(let i=0;i<entries.length;i++){
      const start = entries[i].date;
      const bal = entries[i].saldo;
      const nextStart = (i+1 < entries.length) ? entries[i+1].date : null;
      // period end is day before nextStart, or monthEnd if none
      let periodEnd = nextStart ? dateMinusOne(nextStart) : monthEnd;
      // constrain to month window
      const effectiveStart = (start < monthStart) ? monthStart : start;
      if(effectiveStart > monthEnd) continue; // entirely after month
      if(periodEnd < monthStart) continue; // entirely before month
      if(periodEnd > monthEnd) periodEnd = monthEnd;
      if(periodEnd < effectiveStart) continue;
      const days = Math.round((periodEnd - effectiveStart) / (1000*60*60*24)) + 1;
      segments.push({start: effectiveStart, end: periodEnd, days, saldo: bal});
    }

    // Edge case: if first segment starts after monthStart, assume zero saldo before it (no bunga)
    // OR if user wants previous balance to carry, they should add a row at or before the month start.

    if(segments.length === 0){
      alert('Tidak ada saldo yang berlaku pada bulan tersebut. Pastikan ada baris dengan tanggal di atau sebelum awal bulan.');
      return;
    }

    let totalInterest = 0;
    let totalDays = 0;
    segments.forEach(seg => {
      const dasar = (seg.saldo > threshold) ? seg.saldo : 0;
      const bungaPerHari = dasar * dailyRate;
      const bungaPerPeriode = bungaPerHari * seg.days;
      totalInterest += bungaPerPeriode;
      totalDays += seg.days;

      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${toDateISOInput(seg.start)}</td>
        <td>${toDateISOInput(seg.end)}</td>
        <td>${seg.days}</td>
        <td class="right">${formatRp(seg.saldo)}</td>
        <td class="right">${formatRp(dasar)}</td>
        <td class="right">${formatRp(bungaPerHari)}</td>
        <td class="right">${formatRp(bungaPerPeriode)}</td>
      `;
      resultTableBody.appendChild(tr);
    });

    // footer summary row
    const trSum = document.createElement('tr');
    trSum.innerHTML = `
      <td colspan="2" class="right"><strong>Total</strong></td>
      <td><strong>${totalDays}</strong></td>
      <td></td>
      <td></td>
      <td></td>
      <td class="right result-total">${formatRp(totalInterest)}</td>
    `;
    resultTableBody.appendChild(trSum);

    const summaryHtml = `
      <div><strong>Periode:</strong> ${toDateISOInput(monthStart)} s.d. ${toDateISOInput(monthEnd)}</div>
      <div><strong>Suku Bunga p.a.:</strong> ${annualRate}% (basis 365) &nbsp; <strong>Ambang:</strong> Rp ${threshold.toLocaleString('id-ID')}</div>
      <div style="margin-top:6px"><strong>Total Bunga:</strong> Rp ${formatRp(totalInterest)}</div>
    `;
    summaryDiv.innerHTML = summaryHtml;
    resultCard.style.display = 'block';

    // store last computed data on resultCard dataset for CSV download
    resultCard.dataset.csv = JSON.stringify({month: monthVal, annualRate, threshold, segments, totalInterest, totalDays});
  });

  downloadCsvBtn.addEventListener('click', ()=>{
    if(!resultCard.dataset.csv){
      alert('Belum ada hasil perhitungan. Tekan "Hitung" terlebih dahulu.');
      return;
    }
    const data = JSON.parse(resultCard.dataset.csv);
    let csv = 'Tanggal Mulai,Tanggal Selesai,Jumlah Hari,Saldo Harian (Rp),Dasar Bunga (Rp),Bunga per Hari (Rp),Bunga Periode (Rp)\\n';
    data.segments.forEach(s=>{
      const row = [
        s.start.toISOString().slice(0,10),
        s.end.toISOString().slice(0,10),
        s.days,
        s.saldo,
        (s.saldo > data.threshold) ? s.saldo : 0,
        ((s.saldo > data.threshold) ? (s.saldo*data.annualRate/100/365) : 0).toFixed(6),
        ((s.saldo > data.threshold) ? (s.saldo*data.annualRate/100/365*s.days) : 0).toFixed(6)
      ];
      csv += row.join(',') + '\\n';
    });
    csv += `,,${data.totalDays},,,,${data.totalInterest.toFixed(6)}\\n`;
    const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Perhitungan_Bunga_Harian_${document.getElementById('month').value}.csv`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  });

  clearBtn.addEventListener('click', ()=>{
    resultTableBody.innerHTML = '';
    summaryDiv.innerHTML = '';
    resultCard.style.display = 'none';
    delete resultCard.dataset.csv;
  });

})();
</script>
</body>
</html>
