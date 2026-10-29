<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice {{ $invoiceNumber }} — {{ $targetCompany }}</title>
  
  <!-- Google Fonts: Poppins & Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&family=Dancing+Script:wght@700&display=swap" rel="stylesheet">
  
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: #f1f5f9;
      color: #0f172a;
      -webkit-font-smoothing: antialiased;
      padding: 24px 16px;
    }

    /* ── Action Toolbar (No Print) ── */
    .toolbar-container {
      max-width: 820px;
      margin: 0 auto 16px auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      border-radius: 10px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.15s ease;
      border: none;
      outline: none;
    }
    .btn-back {
      background: #ffffff;
      color: #334155;
      border: 1px solid #cbd5e1;
      box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }
    .btn-back:hover {
      background: #f8fafc;
      color: #0f172a;
      border-color: #94a3b8;
    }
    .btn-print {
      background: #c40000;
      color: #ffffff;
      box-shadow: 0 4px 14px -2px rgba(196, 0, 0, 0.4);
    }
    .btn-print:hover {
      background: #a80000;
    }
    .select-pill {
      background: #ffffff;
      border: 1px solid #cbd5e1;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 700;
      color: #334155;
      outline: none;
      cursor: pointer;
    }

    /* ── A4 Sheet Paper ── */
    .invoice-sheet {
      width: 100%;
      max-width: 820px;
      min-height: 1100px;
      background: #ffffff;
      margin: 0 auto;
      padding: 48px 56px 40px 56px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04);
      border-radius: 4px;
      position: relative;
      box-sizing: border-box;
    }

    /* ── Header ── */
    .invoice-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 24px;
    }

    .brand-logo-area {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-name {
      color: #c40000;
      font-weight: 900;
      font-size: 26px;
      letter-spacing: -0.5px;
      line-height: 1;
    }

    .brand-tagline {
      font-size: 9px;
      font-weight: 700;
      color: #1e293b;
      letter-spacing: 0.04em;
      margin-top: 3px;
      text-transform: uppercase;
    }

    .brand-meta {
      margin-top: 10px;
      font-size: 11px;
      color: #1e293b;
      line-height: 1.45;
    }

    .brand-meta p {
      margin-bottom: 2px;
    }

    .brand-meta .contact-item {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #1e293b;
      font-weight: 500;
      margin-top: 3px;
    }

    .brand-meta .contact-item i {
      font-size: 11px;
      color: #1e293b;
    }

    .invoice-title-area {
      text-align: right;
    }

    .invoice-badge-title {
      color: #c40000;
      font-size: 32px;
      font-weight: 900;
      letter-spacing: 0.02em;
      line-height: 1;
      margin-bottom: 12px;
    }

    .meta-table {
      margin-left: auto;
      font-size: 11.5px;
      color: #0f172a;
    }

    .meta-table tr td {
      padding: 2.5px 0;
      vertical-align: middle;
    }

    .meta-table .val {
      text-align: right;
      padding-right: 8px;
      font-weight: 500;
    }

    .meta-table .sep {
      width: 14px;
      text-align: center;
      font-weight: 600;
    }

    .meta-table .lbl {
      text-align: right;
      font-weight: 600;
      color: #0f172a;
    }

    /* ── To / Bill To Section ── */
    .bill-to-section {
      margin-bottom: 22px;
    }

    .to-row {
      display: flex;
      align-items: baseline;
      gap: 12px;
      margin-bottom: 6px;
    }

    .to-label {
      font-size: 14px;
      font-weight: 700;
      color: #0f172a;
    }

    .to-company {
      font-size: 26px;
      font-weight: 900;
      color: #000000;
      letter-spacing: -0.01em;
      line-height: 1;
    }

    .to-details-table {
      font-size: 11.5px;
      color: #0f172a;
    }

    .to-details-table tr td {
      padding: 1.5px 0;
      vertical-align: top;
    }

    .to-details-table .lbl {
      width: 82px;
      font-weight: 600;
      color: #0f172a;
    }

    .to-details-table .sep {
      width: 14px;
      font-weight: 600;
    }

    .to-details-table .val {
      font-weight: 500;
    }

    /* ── Items Table ── */
    .invoice-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6px;
      font-size: 12px;
    }

    .invoice-table thead tr th {
      background-color: #c40000;
      color: #ffffff;
      font-weight: 800;
      text-transform: capitalize;
      padding: 9px 8px;
      font-size: 12.5px;
      border: 1px solid #c40000;
      letter-spacing: 0.01em;
    }

    .invoice-table tbody tr td {
      padding: 8px 10px;
      border: 1px solid #cbd5e1;
      color: #0f172a;
      font-size: 12px;
      line-height: 1.35;
    }

    .invoice-table .sec-title-row td {
      border: none;
      padding: 14px 2px 6px 2px;
      font-weight: 800;
      font-size: 13px;
      color: #000000;
    }

    .invoice-table .col-no { width: 50px; text-align: center; }
    .invoice-table .col-item { width: auto; text-align: left; }
    .invoice-table .col-vol { width: 62px; text-align: center; }
    .invoice-table .col-sat { width: 78px; text-align: center; }
    .invoice-table .col-price { width: 130px; text-align: right; }
    .invoice-table .col-total { width: 140px; text-align: right; }

    .invoice-table .empty-row td {
      height: 28px;
      padding: 0 10px;
    }

    /* ── Footer Summary & Payment ── */
    .invoice-footer-grid {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-top: 24px;
      padding-top: 6px;
      gap: 30px;
    }

    .payment-method-box {
      font-size: 12px;
      color: #0f172a;
      line-height: 1.5;
    }

    .payment-method-box h4 {
      font-size: 13px;
      font-weight: 800;
      color: #000000;
      margin-bottom: 8px;
    }

    .payment-table {
      font-size: 12px;
    }
    .payment-table tr td {
      padding: 2px 0;
    }
    .payment-table .lbl {
      font-weight: 600;
      color: #0f172a;
      width: 120px;
    }
    .payment-table .sep {
      width: 14px;
      font-weight: 600;
    }
    .payment-table .val {
      font-weight: 500;
    }

    /* ── Totals Box ── */
    .totals-box {
      width: 280px;
      font-size: 12.5px;
    }

    .totals-table {
      width: 100%;
      border-collapse: collapse;
    }

    .totals-table tr td {
      padding: 3.5px 0;
    }

    .totals-table .lbl {
      text-align: left;
      font-weight: 600;
      color: #0f172a;
    }

    .totals-table .val {
      text-align: right;
      font-weight: 600;
      color: #0f172a;
    }

    .totals-table tr.grand-total td {
      padding-top: 8px;
      font-weight: 900;
      font-size: 14px;
      color: #000000;
    }

    /* ── Sign Section ── */
    .sign-section {
      margin-top: 32px;
      display: flex;
      justify-content: flex-end;
      text-align: center;
    }

    .sign-box {
      width: 220px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .sign-date {
      font-size: 11.5px;
      color: #0f172a;
      font-weight: 500;
      margin-bottom: 4px;
    }

    .sign-title {
      font-size: 12px;
      font-weight: 600;
      color: #0f172a;
      margin-bottom: 6px;
    }

    .sign-signature-space {
      height: 62px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 2px 0;
    }

    .sign-img {
      height: 58px;
      max-height: 58px;
      max-width: 145px;
      object-fit: contain;
      transform: rotate(-2deg);
      user-select: none;
      -webkit-user-drag: none;
    }

    .sign-script {
      font-family: 'Dancing Script', cursive;
      font-size: 34px;
      font-weight: 700;
      color: #1e3a8a;
      transform: rotate(-4deg);
      user-select: none;
      letter-spacing: 1px;
    }

    .sign-name {
      font-size: 13px;
      font-weight: 900;
      color: #000000;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      border-bottom: 1.5px solid transparent;
      padding-top: 4px;
    }

    /* ── Print Setup ── */
    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .toolbar-container {
        display: none !important;
      }
      .invoice-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 10mm 15mm 10mm 15mm !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        min-height: auto !important;
      }
      @page {
        size: A4 portrait;
        margin: 10mm;
      }
      .invoice-table thead tr th {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        background-color: #c40000 !important;
        color: #ffffff !important;
      }
    }
  </style>
</head>
<body>

  <!-- Top Action Toolbar (Tidak Dicetak) -->
  <div class="toolbar-container no-print">
    <div class="flex items-center gap-2">
      <a href="{{ route('daily-report.index') }}" class="btn btn-back">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali ke Daily Report</span>
      </a>
    </div>

    <!-- Switcher Opsi Perusahaan Cepat -->
    <form method="GET" action="{{ route('daily-report.invoice') }}" class="flex items-center gap-2" id="quickSwitchForm">
      <!-- Switcher Log Pekerjaan Teknis -->
      @if(isset($recentReports) && $recentReports->count() > 0)
        <select name="report_id" onchange="this.form.submit()" class="select-pill" style="max-width: 280px;" title="Ganti Log Pekerjaan Teknis">
          @foreach($recentReports as $rp)
            <option value="{{ $rp->id }}" {{ (string)($report?->id ?? '') === (string)$rp->id ? 'selected' : '' }}>
              Log: {{ $rp->report_date ? $rp->report_date->format('d/m/Y') : '' }} - {{ $rp->customer_name }}
            </option>
          @endforeach
        </select>
      @elseif(request('report_id'))
        <input type="hidden" name="report_id" value="{{ request('report_id') }}">
      @endif

      <!-- Pilihan Penerbit -->
      <select name="issuer_company" onchange="this.form.submit()" class="select-pill" title="Pilihan Perusahaan Penerbit Invoice">
        <option value="Three Five" {{ ($selectedIssuerKey ?? '') === 'Three Five' ? 'selected' : '' }}>Penerbit: Three Five</option>
        <option value="BANTERPOL" {{ ($selectedIssuerKey ?? '') === 'BANTERPOL' ? 'selected' : '' }}>Penerbit: Banterpool</option>
      </select>

      <!-- Pilihan Tujuan / Mitra -->
      <select name="target_company" onchange="this.form.submit()" class="select-pill" title="Pilihan Perusahaan Tujuan (To: ...)">
        @foreach($allPartners as $p)
          <option value="{{ $p }}" {{ ($targetCompany ?? '') === $p ? 'selected' : '' }}>To: {{ $p }}</option>
        @endforeach
      </select>

      <!-- Tombol Print -->
      <button type="button" onclick="window.print()" class="btn btn-print">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Invoice (PDF)</span>
      </button>
    </form>
  </div>

  <!-- ======================================================== -->
  <!-- LEMBAR INVOICE A4 (MENGIKUTI TEMPLATE INVOICE.PDF)        -->
  <!-- ======================================================== -->
  <div class="invoice-sheet" id="invoice-sheet">
    
    <!-- 1. Header: Logo & Alamat Perusahaan (Kiri) vs Judul & Faktur (Kanan) -->
    <div class="invoice-header">
      
      <!-- Kiri: Brand & Kontak -->
      <div>
        <div class="brand-logo-area">
          @if(($issuer['logo_type'] ?? '') === 'banterpol')
            <img src="{{ asset('image/logo-banterpol.png') }}" alt="Banterpool" style="height: 42px; object-fit: contain;">
            <div>
              <div class="brand-name">BANTER<span style="color: #0f172a;">POOL</span></div>
              <div class="brand-tagline">{{ $issuer['tagline'] }}</div>
            </div>
          @else
            <!-- Three Five Shield Icon Logo (Persis Template) -->
            <svg style="width: 44px; height: 44px; shrink: 0;" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <polygon points="12,14 88,14 74,48 50,90 26,48" fill="#c40000" />
              <polygon points="20,18 80,18 68,44 50,78 32,44" fill="#a00000" />
              <rect x="24" y="24" width="52" height="7" fill="#ffffff" />
              <rect x="44" y="24" width="12" height="42" fill="#ffffff" />
              <rect x="34" y="38" width="32" height="5" fill="#ffffff" />
            </svg>
            <div>
              <div class="brand-name">{{ $issuer['name'] }}</div>
              <div class="brand-tagline">{{ $issuer['tagline'] }}</div>
            </div>
          @endif
        </div>

        <div class="brand-meta">
          <p>{{ $issuer['address'] }}</p>
          <div class="contact-item">
            <i class="fa-solid fa-phone"></i>
            <span>{{ $issuer['phone'] }}</span>
          </div>
          <div class="contact-item">
            <i class="fa-regular fa-envelope"></i>
            <span>{{ $issuer['email'] }}</span>
          </div>
        </div>
      </div>

      <!-- Kanan: Judul Invoice & Nomer Faktur -->
      <div class="invoice-title-area">
        <h1 class="invoice-badge-title">INVOICE</h1>
        <table class="meta-table">
          <tr>
            <td class="val">{{ $invoiceNumber }}</td>
            <td class="sep">:</td>
            <td class="lbl">Nomer Faktur</td>
          </tr>
          <tr>
            <td class="val">{{ $invoiceDate }}</td>
            <td class="sep">:</td>
            <td class="lbl">Tanggal</td>
          </tr>
          <tr>
            <td class="val">{{ $dueDate }}</td>
            <td class="sep">:</td>
            <td class="lbl">Tanggal Jatuh Tempo</td>
          </tr>
        </table>
      </div>

    </div>

    <!-- 2. Penerima (To : ...) -->
    <div class="bill-to-section">
      <div class="to-row">
        <span class="to-label">To :</span>
        <span class="to-company">{{ $targetCompany }}</span>
      </div>
      <table class="to-details-table">
        <tr>
          <td class="lbl">Alamat</td>
          <td class="sep">:</td>
          <td class="val">{{ $targetAddress }}</td>
        </tr>
        <tr>
          <td class="lbl">Nomor Telp</td>
          <td class="sep">:</td>
          <td class="val">{{ $targetPhone }}</td>
        </tr>
        <tr>
          <td class="lbl">Mail</td>
          <td class="sep">:</td>
          <td class="val">{{ $targetEmail }}</td>
        </tr>
      </table>
    </div>

    <!-- 3. Tabel Item Material & Jasa -->
    <table class="invoice-table">
      <thead>
        <tr>
          <th class="col-no">No</th>
          <th class="col-item">Item</th>
          <th class="col-vol">Vol</th>
          <th class="col-sat">Sat</th>
          <th class="col-price">Price</th>
          <th class="col-total">Total</th>
        </tr>
      </thead>
      <tbody>

        <!-- Section A: Material -->
        <tr class="sec-title-row">
          <td colspan="6">A. Material</td>
        </tr>
        @foreach($materials as $m)
          <tr>
            <td class="col-no">{{ $m['no'] }}</td>
            <td class="col-item">{{ $m['name'] }}</td>
            <td class="col-vol">{{ $m['vol'] }}</td>
            <td class="col-sat">{{ $m['sat'] }}</td>
            <td class="col-price">{{ number_format($m['price'], 0, ',', ',') }}</td>
            <td class="col-total">{{ number_format($m['total'], 0, ',', ',') }}</td>
          </tr>
        @endforeach

        <!-- Baris Kosong Dekoratif A (Mengikuti Template) -->
        <tr class="empty-row">
          <td class="col-no"></td>
          <td class="col-item"></td>
          <td class="col-vol"></td>
          <td class="col-sat"></td>
          <td class="col-price"></td>
          <td class="col-total">0</td>
        </tr>

        <!-- Section B: Jasa -->
        <tr class="sec-title-row">
          <td colspan="6">B. Jasa</td>
        </tr>
        @foreach($services as $s)
          <tr>
            <td class="col-no">{{ $s['no'] }}</td>
            <td class="col-item">{{ $s['name'] }}</td>
            <td class="col-vol">{{ $s['vol'] }}</td>
            <td class="col-sat">{{ $s['sat'] }}</td>
            <td class="col-price">{{ number_format($s['price'], 0, ',', ',') }}</td>
            <td class="col-total">{{ number_format($s['total'], 0, ',', ',') }}</td>
          </tr>
        @endforeach

        <!-- Baris Kosong Dekoratif B (Mengikuti Template) -->
        <tr class="empty-row">
          <td class="col-no"></td>
          <td class="col-item"></td>
          <td class="col-vol"></td>
          <td class="col-sat"></td>
          <td class="col-price"></td>
          <td class="col-total"></td>
        </tr>
        <tr class="empty-row">
          <td class="col-no"></td>
          <td class="col-item"></td>
          <td class="col-vol"></td>
          <td class="col-sat"></td>
          <td class="col-price"></td>
          <td class="col-total"></td>
        </tr>

      </tbody>
    </table>

    <!-- 4. Footer: Metode Pembayaran & Ringkasan Total -->
    <div class="invoice-footer-grid">
      
      <!-- Kiri: Metode Pembayaran -->
      <div class="payment-method-box">
        <h4>Metode Pembayaran</h4>
        <table class="payment-table">
          <tr>
            <td class="lbl">Atas Nama</td>
            <td class="sep">:</td>
            <td class="val">{{ $issuer['account_name'] }}</td>
          </tr>
          <tr>
            <td class="lbl">Nama Bank</td>
            <td class="sep">:</td>
            <td class="val">{{ $issuer['bank_name'] }}</td>
          </tr>
          <tr>
            <td class="lbl">Nomor Rekening</td>
            <td class="sep">:</td>
            <td class="val font-mono" style="font-weight: 700;">{{ $issuer['account_number'] }}</td>
          </tr>
        </table>
      </div>

      <!-- Kanan: Rincian Total -->
      <div class="totals-box">
        <table class="totals-table">
          <tr>
            <td class="lbl">Total</td>
            <td class="val">{{ number_format($subtotal, 0, ',', ',') }}</td>
          </tr>
          <tr>
            <td class="lbl">PPN {{ $ppnPercent }}%</td>
            <td class="val">{{ number_format($ppnAmount, 0, ',', ',') }}</td>
          </tr>
          <tr>
            <td class="lbl">Diskon (%)</td>
            <td class="val">{{ $discountPercent }}%</td>
          </tr>
          <tr class="grand-total">
            <td class="lbl">Total</td>
            <td class="val">{{ number_format($grandTotal, 0, ',', ',') }}</td>
          </tr>
        </table>
      </div>

    </div>

    <!-- 5. Tanda Tangan & Tanggal -->
    <div class="sign-section">
      <div class="sign-box">
        <div class="sign-date">{{ $signatureDate }}</div>
        <div class="sign-title">{{ $issuer['signer_title'] }}</div>
        <div class="sign-signature-space">
          @php
            $ttdPath = public_path('template/ttd_warohman.png');
            $ttdSrc = file_exists($ttdPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($ttdPath)) : asset('template/ttd_warohman.png');
          @endphp
          <img src="{{ $ttdSrc }}" alt="Tanda Tangan {{ $issuer['signer_name'] }}" class="sign-img">
        </div>
        <div class="sign-name">{{ $issuer['signer_name'] }}</div>
      </div>
    </div>

  </div>

</body>
</html>

