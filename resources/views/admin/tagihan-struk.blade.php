<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nota Elektronik - {{ $bill->bill_number }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
    }
    @media print {
      body {
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
      }
      .no-print {
        display: none !important;
      }
      #printable-struk {
        box-shadow: none !important;
        border: none !important;
        max-width: 100% !important;
        width: 100% !important;
        padding: 10px !important;
      }
    }
  </style>
</head>
<body class="bg-slate-100 min-h-screen py-6 px-4 flex flex-col items-center justify-center text-slate-800 antialiased">

  <!-- Top Action Bar (No Print) -->
  <div class="no-print w-full max-w-sm mb-4 flex items-center justify-between gap-2">
    <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('admin.tagihan', ['status' => 'rekap']) }}"
       class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-2xs transition">
      <i class="fa-solid fa-arrow-left"></i>
      <span>Kembali</span>
    </a>

    <div class="flex items-center gap-2">
      <button type="button" onclick="window.print()"
              class="inline-flex items-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs transition cursor-pointer">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Struk</span>
      </button>

      <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $bill->customer_phone) }}?text={{ urlencode('*BANTERPOOL* \n\nNOTA ELEKTRONIK\n' . url('/struk/' . $bill->bill_number) . '\n\nYth. Bapak / Ibu\n' . $bill->customer_name . '\n' . $bill->customer_phone . '\nnull\nArea : ' . ($bill->address ?? '-') . '\n\n*PEMBAYARAN*\nTanggal Bayar :' . $paidDate . ' | ' . $paidTime . '\n\nTagihan Bulan : \n- ' . $monthLabel . '\nJenis Paket : ' . $bill->package_name . '\nHarga Paket : Rp. ' . number_format($bill->total, 0, ',', '.') . '\n*Sudah Termasuk Pajak\nDiskon  : Rp. 0\nTotal Biaya : Rp. ' . number_format($bill->total, 0, ',', '.') . '\nKet : *LUNAS* \nAdmin By : ' . $adminBy . '\n\nterimakasih telah menggunakan layanan internet dari BANTERPOOL\n\nSUPPORT BY :\nAdmin BANTERPOOL') }}"
         target="_blank"
         class="inline-flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl shadow-xs transition">
        <i class="fa-brands fa-whatsapp text-sm"></i>
        <span>Kirim WA</span>
      </a>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- CARD STRUK / NOTA ELEKTRONIK (FOTO 2 REPLICA)  -->
  <!-- ============================================== -->
  <div id="printable-struk"
       class="w-full max-w-sm bg-white rounded-3xl p-6 sm:p-7 shadow-xl border border-slate-200/80 transition-all">

    <!-- Header Brand -->
    <div class="flex items-center gap-3 pb-3">
      <div class="w-10 h-10 rounded-2xl bg-gradient-to-tr from-sky-600 to-indigo-600 flex items-center justify-center text-white shadow-xs">
        <i class="fa-solid fa-wifi text-lg"></i>
      </div>
      <div>
        <h1 class="text-xl font-extrabold text-slate-900 tracking-tight leading-tight">Banterpool</h1>
        <p class="text-[11px] text-slate-400 font-medium">Layanan Internet Cepat & Stabil</p>
      </div>
    </div>

    <!-- Total Pembayaran Block -->
    <div class="bg-slate-50 border border-slate-100 rounded-2xl p-4 mt-2 mb-5">
      <span class="block text-xs text-slate-500 font-medium">Total Pembayaran</span>
      <span class="block text-sm font-extrabold text-sky-600 mt-0.5">{{ $bill->package_name }}</span>
      <span class="block text-3xl font-black text-sky-600 tracking-tight mt-1">Rp. {{ number_format($bill->total, 0, ',', '.') }}</span>
    </div>

    <!-- Rincian Heading -->
    <h2 class="text-sm font-bold text-slate-900 mb-2">Rincian</h2>

    <!-- Rincian List -->
    <div class="divide-y divide-slate-100 text-xs">

      <!-- Nama Kepada -->
      <div class="py-2.5 flex items-start justify-between gap-3">
        <span class="text-slate-500 shrink-0">Nama Kepada</span>
        <div class="text-right">
          <div class="font-bold text-slate-900">{{ $bill->customer_name }}</div>
          <div class="flex items-center justify-end gap-1.5 mt-1">
            <span class="bg-sky-500 text-white font-extrabold text-[10px] px-2 py-0.5 rounded-full tracking-wide">
              {{ $customerCode }}
            </span>
            <span class="font-mono text-slate-600 text-[11px]">{{ $bill->customer_phone }}</span>
          </div>
        </div>
      </div>

      <!-- Area -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Area</span>
        <span class="font-bold text-slate-900 uppercase text-right">{{ $bill->address ?: 'AREA BANYUMAS' }}</span>
      </div>

      <!-- Harga Paket -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Harga Paket</span>
        <span class="font-bold text-slate-900">Rp. {{ number_format($bill->total, 0, ',', '.') }}</span>
      </div>

      <!-- Biaya Tambahan -->
      <div class="py-2.5 flex items-start justify-between gap-3">
        <span class="text-slate-500">Biaya Tambahan</span>
        <div class="text-right text-slate-700 font-medium space-y-0.5">
          <div>- Rp. 0</div>
          <div>- Rp. 0</div>
        </div>
      </div>

      <!-- PPN -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">PPN</span>
        <span class="font-bold text-slate-900">Rp. 0</span>
      </div>

      <!-- Diskon -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Diskon</span>
        <span class="font-bold text-slate-900">Rp. 0</span>
      </div>

      <!-- Bulan -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Bulan</span>
        <span class="font-bold text-slate-900">{{ $monthLabel }}</span>
      </div>

      <!-- Tanggal -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Tanggal</span>
        <span class="font-bold text-slate-900">{{ $paidDate }}</span>
      </div>

      <!-- Keterangan -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Keterangan</span>
        <span class="font-black text-slate-900 tracking-widest uppercase">L U N A S</span>
      </div>

      <!-- Admin By -->
      <div class="py-2.5 flex items-center justify-between gap-3">
        <span class="text-slate-500">Admin By</span>
        <span class="font-bold text-slate-900">{{ $adminBy }}</span>
      </div>

    </div>

    <!-- Footer Struk -->
    <div class="mt-7 pt-5 border-t border-slate-100 text-center">
      <p class="text-[11px] text-slate-400 font-normal leading-relaxed">
        terimakasih telah menggunakan layanan internet dari BANTERPOOL
      </p>
      <p class="text-[11px] text-slate-400 mt-3 font-medium">Support By :</p>
      <p class="text-xs font-bold text-slate-700 mt-0.5">Admin BANTERPOOL</p>
    </div>

  </div>

  <p class="no-print text-center text-[11px] text-slate-400 mt-4">
    Nota Elektronik Resmi Banterpool • Dokumen Sah Bukti Pelunasan
  </p>

</body>
</html>

