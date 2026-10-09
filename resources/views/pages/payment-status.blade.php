@extends('layouts.app')

@section('title', 'WiFi Banterpool - Status Pembayaran')

@section('content')
@php
  $isSuccess = request()->get('status') !== 'failed';
@endphp

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  <!-- Header Status Section -->
  <div class="flex items-center gap-3 mb-2">
    @if($isSuccess)
      <div class="w-8 h-8 rounded-full bg-green-600 text-white flex items-center justify-center font-bold text-lg">
        <i class="fa-solid fa-check"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-black">Pembayaran Berhasil!</h1>
    @else
      <div class="w-8 h-8 rounded-full bg-brand text-white flex items-center justify-center font-bold text-lg">
        <i class="fa-solid fa-xmark"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl font-black text-black">Pembayaran Di Tolak!</h1>
    @endif
  </div>
  <p class="text-xs sm:text-sm text-gray-500 mb-8 ml-11">
    {{ $isSuccess ? 'Terima Kasih, pembayaran Anda telah berhasil diproses' : 'Maaf, pembayaran Anda tidak dapat diproses' }}
  </p>

  <!-- Stepper Indicator -->
  <div class="flex items-center justify-between max-w-2xl mb-8 text-xs">
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold"><i class="fa-solid fa-check text-xs"></i></div>
      <div><p class="font-bold text-black">Pilih Paket</p><p class="text-[10px] text-gray-400">{{ $customerData['package_name'] ?? request('package_name', 'Paket 20 Mbps') }}</p></div>
    </div>
    <div class="flex-1 h-[2px] bg-brand mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold"><i class="fa-solid fa-check text-xs"></i></div>
      <div><p class="font-bold text-black">Data Pelanggan</p><p class="text-[10px] text-gray-400">Lengkap</p></div>
    </div>
    <div class="flex-1 h-[2px] bg-brand mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold"><i class="fa-solid fa-check text-xs"></i></div>
      <div><p class="font-bold text-black">Konfirmasi</p><p class="text-[10px] text-gray-400">Pembayaran</p></div>
    </div>
    <div class="flex-1 h-[2px] bg-brand mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs">4</div>
      <div>
        <p class="font-bold text-black">{{ $isSuccess ? 'Selesai' : 'Pembayaran Ditolak' }}</p>
        <p class="text-[10px] text-gray-400">Terhubung</p>
      </div>
    </div>
  </div>

  <!-- Grid Konten Utama -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Sisi Kiri: Card Status Pembayaran -->
    <div class="lg:col-span-8 space-y-4">

      <div class="border border-black rounded-2xl p-8 bg-white text-center space-y-6">

        <!-- Icon Banner Besar -->
        <div class="flex flex-col items-center justify-center">
          @if($isSuccess)
            <div class="w-24 h-24 rounded-full bg-[#108a3e] text-white flex items-center justify-center text-5xl mb-4 shadow-sm">
              <i class="fa-solid fa-check"></i>
            </div>
            <h2 class="text-xl font-black text-black">Pembayaran Berhasil!</h2>
            <p class="text-xs text-gray-500 mt-1 font-medium">Layanan Internet Anda Akan Segera Aktif.</p>
          @else
            <div class="w-24 h-24 rounded-full bg-[#cc0000] text-white flex items-center justify-center text-5xl mb-4 shadow-sm">
              <i class="fa-solid fa-xmark"></i>
            </div>
            <h2 class="text-xl font-black text-black">Pembayaran Di Tolak!</h2>
            <p class="text-xs text-gray-500 mt-1 font-medium">Silakan lakukan pemesanan ulang atau hubungi dukungan kami.</p>
          @endif
        </div>

        <!-- Banner Estimasi Aktivasi -->
        @if($isSuccess)
          <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-4 flex items-center justify-center gap-3 text-left">
            <i class="fa-regular fa-clock text-emerald-700 text-xl"></i>
            <div>
              <p class="font-bold text-xs text-black">Estimasi aktivasi: 5 – 10 Menit</p>
              <p class="text-[10px] text-gray-500">Anda akan mendapatkan notifikasi setelah layanan aktif</p>
            </div>
          </div>
        @endif

        <!-- Detail Transaksi Box -->
        <div class="border border-black rounded-xl p-5 text-left text-xs space-y-3">
          <h3 class="font-bold text-black text-sm mb-4">Detail Transaksi</h3>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">No. Order</span>
            <div class="flex items-center gap-2">
              <span class="font-black text-brand text-xs">{{ $customerData['order_number'] ?? 'ORD-28052024-000123' }}</span>
              <i class="fa-regular fa-copy text-gray-400 cursor-pointer hover:text-black" onclick="navigator.clipboard.writeText('{{ $customerData['order_number'] ?? '' }}')"></i>
            </div>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Tanggal & Waktu</span>
            <span class="font-bold text-black">{{ now()->translatedFormat('d M Y, H:i') }} WIB</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Metode Pembayaran</span>
            <span class="font-bold text-black">{{ request()->get('payment_method', 'BRI Virtual Account') }}</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Status Pembayaran</span>
            @if($isSuccess)
              <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-extrabold flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Berhasil
              </span>
            @else
              <span class="bg-red-600 text-white px-3 py-1 rounded-lg text-[10px] font-extrabold">
                Di Tolak!!
              </span>
            @endif
          </div>
        </div>

        <!-- Tombol Aksi Dinamis Sesuai Status Pembayaran -->
        <div class="pt-2">
          @if($isSuccess)
            <!-- Tampilan Kiri: Kembali | Kanan: Lihat Detail Pemesanan dengan Meneruskan Query -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <a href="{{ route('home') }}" class="w-full border border-gray-300 bg-white text-black hover:bg-gray-100 py-3 rounded-xl font-bold text-xs transition text-center flex items-center justify-center gap-2 shadow-sm">
                <i class="fa-solid fa-arrow-left text-black"></i> Kembali
              </a>
              <a href="{{ route('order.detail', array_merge(request()->query(), ['order_number' => $customerData['order_number'] ?? ''])) }}" class="w-full bg-brand hover:bg-brand-700 text-white py-3 rounded-xl font-bold text-xs transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-file-invoice"></i> Lihat Detail Pemesanan
              </a>
            </div>
          @else
            <!-- Tampilan Jika Pembayaran Ditolak -->
            <a href="{{ route('home') }}" class="w-full block bg-brand hover:bg-brand-700 text-white py-3 rounded-xl font-bold text-xs transition text-center">
              Kembali ke Halaman Utama
            </a>
          @endif
        </div>

      </div>

      <!-- Banner Email Struk -->
      @if($isSuccess)
        <div class="border border-black rounded-2xl p-4 bg-white flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
          <div class="flex items-center gap-3">
            <div class="w-10 h-8 rounded-lg bg-gray-600 text-white flex items-center justify-center text-lg">
              <i class="fa-regular fa-envelope"></i>
            </div>
            <div>
              <p class="font-bold text-black">Struk pembayaran telah dikirim!</p>
              <p class="text-[10px] text-gray-500">Kami telah mengirimkan bukti pembayaran ke email Anda ({{ $customerData['email'] }})</p>
            </div>
          </div>

          <button type="button" class="w-full sm:w-auto border border-brand text-brand hover:bg-brand hover:text-white px-6 py-2.5 rounded-xl font-bold text-xs transition">
            Kirim Ulang Email
          </button>
        </div>
      @endif

    </div>

    <!-- Sisi Kanan: Ringkasan Pesanan & Support -->
    <div class="lg:col-span-4 space-y-6">

      <div class="border border-black rounded-2xl p-6 bg-white space-y-6">
        <h3 class="text-base font-black text-black">Ringkasan Pesanan</h3>

        <div class="space-y-4 text-xs">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-wifi text-brand text-lg"></i>
              <div>
                <p class="font-bold text-black">{{ $customerData['package_name'] ?? request('package_name', 'Paket 20 Mbps') }}</p>
                <p class="text-[10px] text-gray-400">Internet cepat & stabil</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-black">Rp{{ $customerData['price'] ?? request('package_price', '110.000') }}</p>
              <p class="text-[10px] text-gray-400">/bulan</p>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-3 flex justify-between items-center text-sm">
            <span class="font-bold text-black">Total Pembayaran</span>
            <span class="font-black text-brand text-base">Rp{{ $customerData['total'] ?? request('package_price', '110.000') }} <span class="text-xs font-normal text-gray-500">/bulan</span></span>
          </div>
        </div>

        <!-- Banner Keamanan -->
        <div class="bg-red-50 rounded-xl p-3 flex items-start gap-2 text-xs text-gray-700">
          <i class="fa-solid fa-circle-check text-brand mt-0.5"></i>
          <div>
            <p class="font-bold text-black">Aman & Terpercaya</p>
            <p class="text-[10px] text-gray-500 leading-tight">Data anda aman bersama kami. Pembayaran terenkripsi dengan sistem keamanan terbaik</p>
          </div>
        </div>

        <!-- List Keunggulan -->
        <div class="space-y-3 text-xs pt-2">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-regular fa-clock"></i></div>
            <div>
              <p class="font-bold text-black">Pemasangan Cepat</p>
              <p class="text-[10px] text-gray-400">Team kami segera menghubungi anda</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-solid fa-rotate-left"></i></div>
            <div>
              <p class="font-bold text-black">Garansi 7 hari</p>
              <p class="text-[10px] text-gray-400">Garansi uang kembali jika tidak puas</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-solid fa-headset"></i></div>
            <div>
              <p class="font-bold text-black">Support 24/7</p>
              <p class="text-[10px] text-gray-400">Kami siap untuk membantu kapan saja</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200">

        <!-- Butuh Bantuan -->
        <div class="text-center pt-2">
          <p class="text-xs font-bold text-black">Butuh Bantuan?</p>
          <p class="text-[10px] text-gray-400 mb-3">Hubungi tim kami untuk lebih lanjut</p>

          <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20butuh%20bantuan%20terkait%20status%20pembayaran%20WiFi." target="_blank" class="w-full border border-brand text-brand font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-brand hover:text-white transition">
            <i class="fa-solid fa-headset"></i> Hubungi Kami
          </a>
        </div>

      </div>

    </div>

  </div>

</div>
@endsection
