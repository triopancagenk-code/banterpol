<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Formulir Berlangganan Fiber Broadband - {{ $order->customer_name }} ({{ $order->order_number }})</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <style>
    @page {
      size: A4 portrait;
      margin: 8mm 10mm 8mm 10mm;
    }
    body {
      font-family: 'Arial', 'Inter', sans-serif;
      color: #000;
      background-color: #f1f5f9;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .form-checkbox {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 14px;
      height: 14px;
      border: 1.5px solid #000;
      font-size: 11px;
      font-weight: 900;
      line-height: 1;
      vertical-align: middle;
      margin-right: 5px;
      background-color: #fff;
    }
    .form-num-box {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 26px;
      height: 20px;
      border: 1.5px solid #000;
      font-size: 10px;
      font-weight: bold;
      background-color: #fff;
    }
    .underlined-value {
      border-bottom: 1px solid #000;
      display: inline-block;
      min-height: 18px;
    }
    .underlined-fill {
      border-bottom: 1px solid #000;
      width: 100%;
      display: block;
      min-height: 16px;
    }
    @media print {
      body {
        background-color: #fff !important;
        padding: 0 !important;
      }
      .no-print {
        display: none !important;
      }
      .printable-form {
        border: 2px solid #000 !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 14px 18px !important;
        width: 100% !important;
        max-width: 100% !important;
        border-radius: 0 !important;
      }
    }
  </style>
</head>
<body class="py-6 px-4">

  <!-- ============================================== -->
  <!-- TOP ACTION BAR (TIDAK DICETAK)                -->
  <!-- ============================================== -->
  <div class="max-w-4xl mx-auto mb-4 no-print flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex items-center gap-3">
      <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('admin.pelanggan') }}"
         class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali</span>
      </a>
      <span class="text-xs text-slate-500 font-medium">
        Order: <strong class="text-slate-800 font-mono">{{ $order->order_number }}</strong> ({{ $order->customer_name }})
      </span>
    </div>

    <div class="flex items-center gap-2">
      <button type="button" onclick="window.print()"
              class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-red-600 hover:bg-red-700 text-white font-bold text-xs shadow-sm transition cursor-pointer">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Formulir (Print / PDF)</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- DOKUMEN CETAK FORMULIR PENDAFTARAN             -->
  <!-- FORMAT RESMI PT. SAGA INFRASTRUKTUR MEDIASELARAS-->
  <!-- ============================================== -->
  <div class="printable-form max-w-4xl mx-auto bg-white border-2 border-black p-6 sm:p-8 text-black text-[10.5px] leading-snug shadow-md">
    
    <!-- 1. HEADER FORMULIR -->
    <div class="grid grid-cols-12 gap-2 items-center pb-2">
      <!-- Logo SIMS / Saga -->
      <div class="col-span-2 flex items-center justify-start">
        <img src="{{ asset('image/logo-saga.svg') }}" alt="Logo SIMS" class="h-16 w-auto object-contain">
      </div>

      <!-- Judul Tengah -->
      <div class="col-span-6 text-center">
        <h1 class="text-base sm:text-[17px] font-black tracking-wide uppercase text-black leading-tight">
          FORMULIR PENDAFTARAN
        </h1>
        <h2 class="text-sm sm:text-[15px] font-black tracking-wide uppercase text-black leading-tight">
          FIBER BROADBAND
        </h2>
        <h3 class="text-xs sm:text-[12px] font-bold tracking-wider uppercase text-black italic leading-tight">
          FIBER BROADBAND REGISTRATION FORM
        </h3>
      </div>

      <!-- Alamat Perusahaan Kanan -->
      <div class="col-span-4 text-left text-[9px] text-black leading-tight pl-2">
        <p class="font-black text-[9.5px]">PT. Saga Infrastruktur Mediaselaras</p>
        <p>Graha Mas Pemuda Rawamangun Blok AD 01</p>
        <p>Lantai 3 Jalan Raya Pemuda Pulau Gadung</p>
      </div>
    </div>

    <!-- Divider Garis Hitam -->
    <div class="border-t-2 border-black my-2"></div>

    <!-- 2. SECTION: DATA PELANGGAN -->
    <div class="border border-black bg-gray-200 py-0.5 text-center font-black text-[11px] uppercase tracking-wider text-black mb-2.5">
      DATA PELANGGAN - Customer Data
    </div>

    <!-- 3. FORMULIR FIELD BODY -->
    <div class="space-y-1.5 text-[10px]">

      <!-- Jenis Pendaftaran -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">Jenis pendaftaran - <span class="italic font-normal">Registration type</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 flex flex-wrap items-center gap-x-6 gap-y-1">
          <label class="inline-flex items-center">
            <span class="form-checkbox">✓</span>
            <span>Baru - <span class="italic text-gray-700">New</span></span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox"></span>
            <span>Perubahan jenis layanan - <span class="italic text-gray-700">Change service</span></span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox"></span>
            <span>Daftar Ulang - <span class="italic text-gray-700">Reactivate</span></span>
          </label>
        </div>
      </div>

      <!-- No Pelanggan -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">No. Pelanggan - <span class="italic font-normal">Customer ID</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 flex items-center">
          <span class="underlined-value font-bold font-mono px-1 min-w-[140px]">{{ $order->order_number }}</span>
          <span class="text-[8.5px] italic text-gray-600 ml-2">(khusus untuk perubahan jenis layanan/daftar ulang - change service/reactivation only)</span>
        </div>
      </div>

      <!-- Nama Pemilik Rekening -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">Nama pemilik rekening - <span class="italic font-normal">Account holder</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          <span class="underlined-value font-bold uppercase px-1 w-full">{{ $order->customer_name }}</span>
        </div>
      </div>

      <!-- Tanggal Lahir & Jenis Kelamin -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">Tanggal lahir - <span class="italic font-normal">Date of birth</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 grid grid-cols-12 gap-2 items-center">
          <div class="col-span-6">
            <span class="underlined-value w-full px-1 font-semibold">
              {{ $order->birth_date ? ($order->birth_place ? $order->birth_place . ', ' : '') . \Carbon\Carbon::parse($order->birth_date)->translatedFormat('d F Y') : ($order->birth_place ?? '') }}
            </span>
          </div>
          <div class="col-span-6 flex items-center justify-end gap-3 text-[9.5px]">
            <span class="font-semibold">Jenis Kelamin - <span class="italic font-normal">Sex</span></span>
            <label class="inline-flex items-center">
              <span class="form-checkbox"></span>
              <span>Pria - <span class="italic text-gray-700">Male</span></span>
            </label>
            <label class="inline-flex items-center">
              <span class="form-checkbox"></span>
              <span>Wanita - <span class="italic text-gray-700">Female</span></span>
            </label>
          </div>
        </div>
      </div>

      <!-- No Identitas -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">No Identitas - <span class="italic font-normal">Identity No (KTP/Passport/SIM)</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          <span class="underlined-value w-full px-1 font-mono font-bold">{{ $order->id_card_number ?? '' }}</span>
        </div>
      </div>

      <!-- Nomor NPWP -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">Nomor NPWP - <span class="italic font-normal">Tax registration number</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          <span class="underlined-value w-full px-1"></span>
        </div>
      </div>

      <!-- Kebangsaan -->
      <div class="grid grid-cols-12 gap-1 items-center">
        <div class="col-span-4 font-semibold">Kebangsaan - <span class="italic font-normal">Nationality</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          <span class="underlined-value px-1 min-w-[200px] font-semibold">Indonesia</span>
        </div>
      </div>

      <!-- Nomor untuk Dihubungi -->
      <div class="grid grid-cols-12 gap-1 items-start">
        <div class="col-span-4 font-semibold pt-0.5">Nomor untuk dihubungi - <span class="italic font-normal">Contact number</span></div>
        <div class="col-span-1 text-center font-bold pt-0.5">:</div>
        <div class="col-span-7 space-y-1">
          <!-- Baris 1: Rumah & Handphone -->
          <div class="grid grid-cols-12 gap-2 items-center">
            <div class="col-span-6 flex items-center">
              <span class="w-24 text-[9.5px]">Rumah - <span class="italic">Residence</span></span>
              <span class="underlined-value flex-1 px-1"></span>
            </div>
            <div class="col-span-6 flex items-center">
              <span class="w-20 text-[9.5px]">Handphone</span>
              <span class="underlined-value flex-1 px-1 font-bold">{{ $order->customer_phone }}</span>
            </div>
          </div>

          <!-- Baris 2: Kantor & Fax -->
          <div class="grid grid-cols-12 gap-2 items-center">
            <div class="col-span-6 flex items-center">
              <span class="w-24 text-[9.5px]">Kantor - <span class="italic">Office</span></span>
              <span class="underlined-value flex-1 px-1"></span>
            </div>
            <div class="col-span-6 flex items-center">
              <span class="w-20 text-[9.5px]">Fax</span>
              <span class="underlined-value flex-1 px-1"></span>
            </div>
          </div>

          <!-- Baris 3: E-mail for e-Billing Statement -->
          <div class="flex items-center">
            <span class="w-44 text-[9.5px] italic">E-mail for e-Billing Statement</span>
            <span class="underlined-value flex-1 px-1 font-semibold">{{ $order->customer_email ?: '-' }}</span>
          </div>
        </div>
      </div>

      <!-- Alamat Pemasangan -->
      <div class="grid grid-cols-12 gap-1 items-start pt-1">
        <div class="col-span-4 font-semibold">Alamat pemasangan - <span class="italic font-normal">Installation address</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 space-y-1">
          <div class="underlined-value w-full px-1 font-medium leading-relaxed">{{ $order->address }}</div>
          <div class="flex justify-end items-center text-[9.5px] pt-0.5">
            <span class="mr-2">Kode pos - <span class="italic">Zip Code</span></span>
            <span class="underlined-value w-24 text-center font-mono font-bold">53161</span>
          </div>
        </div>
      </div>

      <!-- Alamat Penagihan -->
      <div class="grid grid-cols-12 gap-1 items-start pt-1">
        <div class="col-span-4 font-semibold">Alamat penagihan - <span class="italic font-normal">Billing address</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 space-y-1">
          <label class="inline-flex items-center text-[9.5px]">
            <span class="form-checkbox">✓</span>
            <span>Sama dengan alamat pemasangan - <span class="italic text-gray-700">same as installation address</span></span>
          </label>
          <div class="underlined-value w-full px-1 font-medium leading-relaxed">{{ $order->address }}</div>
          <div class="flex justify-end items-center text-[9.5px] pt-0.5">
            <span class="mr-2">Kode pos - <span class="italic">Zip Code</span></span>
            <span class="underlined-value w-24 text-center font-mono font-bold">53161</span>
          </div>
        </div>
      </div>

      <!-- Jenis Layanan (Service Type) -->
      @php
        $speed = $order->speed ?? '';
        $packageName = $order->package_name ?? '';
        $is15 = str_contains($speed, '15') || str_contains($packageName, '15');
        $is20 = str_contains($speed, '20') || str_contains($packageName, '20') || (!$is15 && !str_contains($speed, '30') && !str_contains($speed, '50') && !str_contains($speed, '100') && !str_contains($speed, '200'));
        $is30 = str_contains($speed, '30') || str_contains($packageName, '30');
        $is50 = str_contains($speed, '50') || str_contains($packageName, '50');
        $is100 = str_contains($speed, '100') || str_contains($packageName, '100');
        $is200 = str_contains($speed, '200') || str_contains($packageName, '200');
      @endphp
      <div class="grid grid-cols-12 gap-1 items-start pt-1">
        <div class="col-span-4 font-semibold">Jenis Layanan - <span class="italic font-normal">Service Type</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7 grid grid-cols-3 gap-x-4 gap-y-1.5 font-bold">
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is15 ? '✓' : '' }}</span>
            <span>Up-To 15 Mbps</span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is30 ? '✓' : '' }}</span>
            <span>Up-To 30 Mbps</span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is100 ? '✓' : '' }}</span>
            <span>Up-To 100 Mbps</span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is20 ? '✓' : '' }}</span>
            <span>Up-To 20 Mbps</span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is50 ? '✓' : '' }}</span>
            <span>Up-To 50 Mbps</span>
          </label>
          <label class="inline-flex items-center">
            <span class="form-checkbox">{{ $is200 ? '✓' : '' }}</span>
            <span>Up-To 200 Mbps</span>
          </label>
        </div>
      </div>

      <!-- Layanan Tambahan (Additional Services) -->
      <div class="grid grid-cols-12 gap-1 items-start pt-1">
        <div class="col-span-4 font-semibold">Layanan Tambahan - <span class="italic font-normal">Additional Services</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          <p class="text-[8.5px] leading-tight text-gray-800 mb-1.5">
            Penggunaan layanan tambahan di atas akan dikenakan biaya tambahan. Untuk keterangan lebih lanjut: <span class="italic">For the use of additional services above, there will be an extra usage charge.</span>
          </p>
          <div class="grid grid-cols-3 gap-x-8 gap-y-1 w-44">
            <span class="form-num-box">1</span>
            <span class="form-num-box">3</span>
            <span class="form-num-box">5</span>
            <span class="form-num-box">2</span>
            <span class="form-num-box">4</span>
            <span class="form-num-box">6</span>
          </div>
        </div>
      </div>

      <!-- Username Email Utama -->
      <div class="grid grid-cols-12 gap-1 items-center pt-1">
        <div class="col-span-4 font-semibold">Username <span class="text-[9px] font-normal italic">(akan menjadi alamat e-mail utama - will become your default e-mail address)</span></div>
        <div class="col-span-1 text-center font-bold">:</div>
        <div class="col-span-7">
          @php
            $defaultUsername = $order->customer_email ?: (strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $order->customer_name)) . '@banterpool.net');
          @endphp
          <span class="underlined-value w-full px-1 font-mono font-bold">{{ $defaultUsername }}</span>
        </div>
      </div>

    </div>

    <!-- Divider Garis Hitam -->
    <div class="border-t border-black my-2.5"></div>

    <!-- 4. SYARAT DAN KETENTUAN (TERMS & CONDITIONS) -->
    <div class="text-[7.8px] leading-tight space-y-1.5 text-justify text-black mb-3">
      <p>
        <strong>SYARAT DAN KETENTUAN</strong> — Saya dengan ini menyatakan bahwa semua keterangan yang diisi adalah benar, serta menerima dan bersedia untuk terikat pada seluruh <strong>KETENTUAN BERLANGGANAN</strong> yang telah ditetapkan SIMS. Saya menyatakan ketersediaan untuk mengikuti ketentuan minimum berlangganan SIMS Fiber Broadband selama 12 bulan. Apabila saya berhenti berlangganan kurang dari 12 bulan, maka saya bersedia dikenakan biaya penalti Rp 500.000. Dengan ini saya memberikan wewenang kepada PT. Saga Infrastruktur Mediaselaras untuk melakukan penagihan untuk segala biaya layanan yang digunakan Pelanggan. SIMS berhak untuk menolak permohonan berlangganan ini tanpa memberikan alasan apa pun. Khusus untuk perubahan jenis layanan, e-mail selain username dianggap sebagai e-mail tambahan. Saya mengizinkan pengambilan perangkat yang terpasang oleh SIMS pada saat penutupan layanan.
      </p>
      <p class="italic">
        <strong>TERMS &amp; CONDITIONS</strong> — I, hereby confirm that the information here in given is true and agrees to be bound by SIMS's TERMS &amp; CONDITIONS. I agree to obey the rules for 12 months of minimum subscription. If I unsubscribe less than 12 months, I will be responsible for Penalty Fee Rp 500.000. I hereby authorize PT Saga Infrastruktur Mediaselaras for the payment of any services due to Customer. SIMS has the right to refuse this application without any explanation. For change service, other e-mail besides the username will be considered as additional upon my e-mail address. I grant SIMS to collect any previously installed equipments service terminates.
      </p>
    </div>

    <!-- 5. SIGNATURE & SIMS COMPLETION BOX -->
    <div class="grid grid-cols-12 gap-4 items-end pt-1">
      
      <!-- Kolom Kiri: Tanda Tangan Pelanggan -->
      <div class="col-span-5 text-center text-[10px] space-y-1">
        <div class="text-left font-semibold mb-6">
          Tanggal - <span class="italic font-normal">Date</span> : 
          <span class="font-bold underline ml-1">{{ $order->created_at ? $order->created_at->translatedFormat('d F Y') : date('d F Y') }}</span>
        </div>

        <div class="h-16"></div> <!-- Ruang tanda tangan -->

        <div class="border-t border-black pt-1 w-48 mx-auto font-bold uppercase text-[10.5px]">
          {{ $order->customer_name }}
        </div>
        <p class="text-[9px] italic text-gray-700">Tanda tangan - Signature</p>
      </div>

      <!-- Kolom Kanan: Kotak Khusus SIMS -->
      <div class="col-span-7">
        <div class="border border-black p-2 text-[9.5px]">
          <p class="font-bold mb-1.5 text-center">
            Bagian ini diisi oleh SIMS - <span class="italic font-normal">To be completed by SIMS</span>
          </p>

          <table class="w-full text-left">
            <tbody>
              <tr>
                <td class="py-0.5 pr-2 font-semibold w-56">Biaya Registrasi - <span class="italic font-normal">Setup Fee</span></td>
                <td class="py-0.5 font-bold w-3 text-center">:</td>
                <td class="py-0.5 pl-2 font-mono font-bold">
                  {{ $order->installation_fee > 0 ? 'Rp ' . number_format($order->installation_fee, 0, ',', '.') : 'Rp 0 (Gratis)' }}
                </td>
              </tr>
              <tr>
                <td class="py-0.5 pr-2 font-semibold">Biaya Bulanan - <span class="italic font-normal">Monthly Fee</span></td>
                <td class="py-0.5 font-bold text-center">:</td>
                <td class="py-0.5 pl-2 font-mono font-bold">
                  Rp {{ number_format($order->price ?? $order->total, 0, ',', '.') }}
                </td>
              </tr>
              <tr>
                <td class="py-0.5 pr-2 font-semibold">Biaya Tambahan - <span class="italic font-normal">Additional Fee</span></td>
                <td class="py-0.5 font-bold text-center">:</td>
                <td class="py-0.5 pl-2 font-mono font-bold">Rp 0</td>
              </tr>
              <tr>
                <td class="py-0.5 pr-2 font-semibold">
                  Harga belum termasuk PPN 11% - <span class="italic font-normal">Prices excludes VAT 11%</span>
                </td>
                <td class="py-0.5 font-bold text-center">:</td>
                <td class="py-0.5 pl-2 font-mono font-bold">
                  Rp {{ number_format(round(($order->price ?? $order->total) / 1.11), 0, ',', '.') }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

  </div>

</body>
</html>
