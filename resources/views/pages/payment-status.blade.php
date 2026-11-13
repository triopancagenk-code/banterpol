@extends('layouts.app')

@section('title', 'WiFi Banterpool - Konfirmasi Pengajuan Pemesanan')

@section('content')
<div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 relative">

  <!-- Tombol Kembali ke Beranda -->
  <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand font-bold text-sm mb-4 sm:mb-6 hover:underline">
    <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
  </a>

  <!-- Header Section -->
  <div class="mb-6 sm:mb-8">
    <div class="flex items-center gap-2 sm:gap-3 mb-1">
      <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
        <span>Menunggu Konfirmasi Admin</span>
      </span>
      <span class="text-xs text-gray-400">•</span>
      <span class="text-xs font-mono font-bold text-gray-500">{{ $customerData['order_number'] ?? 'ORD-BARU' }}</span>
    </div>
    <h1 class="text-2xl sm:text-3xl font-black text-black">Konfirmasi Pengajuan Pemesanan</h1>
    <p class="text-xs sm:text-sm text-gray-500 mt-1">Pengajuan Anda telah berhasil dikirim dan sedang dalam proses verifikasi oleh tim Admin Banterpool.</p>
  </div>

  <!-- Stepper Indicator Responsive -->
  <div class="w-full mb-8 sm:mb-10 text-xs">
    <div class="flex items-center justify-between w-full">
      <!-- Step 1: Pilih Paket -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs shrink-0">
          <i class="fa-solid fa-check text-[10px] sm:text-xs"></i>
        </div>
        <div class="leading-tight">
          <p class="font-bold text-black text-[10px] sm:text-xs whitespace-nowrap">Pilih Paket</p>
          <p class="text-[8px] sm:text-[10px] text-gray-400 leading-tight truncate max-w-[55px] sm:max-w-none">{{ $customerData['package_name'] ?? 'Paket WiFi' }}</p>
        </div>
      </div>
      <div class="flex-1 h-[2px] bg-brand mx-1 sm:mx-3 min-w-[6px]"></div>

      <!-- Step 2: Data Pelanggan -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs shrink-0">
          <i class="fa-solid fa-check text-[10px] sm:text-xs"></i>
        </div>
        <div class="leading-tight">
          <p class="font-bold text-black text-[10px] sm:text-xs whitespace-nowrap">Data Pelanggan</p>
          <p class="text-[8px] sm:text-[10px] text-gray-400 leading-tight">Lengkap</p>
        </div>
      </div>
      <div class="flex-1 h-[2px] bg-brand mx-1 sm:mx-3 min-w-[6px]"></div>

      <!-- Step 3: Konfirmasi Pesanan -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs shrink-0">
          <i class="fa-solid fa-check text-[10px] sm:text-xs"></i>
        </div>
        <div class="leading-tight">
          <p class="font-bold text-black text-[10px] sm:text-xs whitespace-nowrap">Konfirmasi</p>
          <p class="text-[8px] sm:text-[10px] text-gray-400 leading-tight">Pesanan</p>
        </div>
      </div>
      <div class="flex-1 h-[2px] bg-brand mx-1 sm:mx-3 min-w-[6px]"></div>

      <!-- Step 4: Status Pengajuan (Aktif) -->
      <div class="flex items-center gap-1 sm:gap-2 shrink-0">
        <div class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-[10px] sm:text-xs shrink-0 shadow-sm animate-pulse">
          <i class="fa-solid fa-clock text-[10px]"></i>
        </div>
        <div class="leading-tight">
          <p class="font-bold text-amber-600 text-[10px] sm:text-xs whitespace-nowrap">Status</p>
          <p class="text-[8px] sm:text-[10px] text-amber-500 font-semibold leading-tight">Menunggu</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Banner Pemberitahuan Bebas Biaya di Awal -->
  <div class="bg-gradient-to-r from-amber-50 via-orange-50 to-red-50 border border-amber-200/90 rounded-2xl p-4 sm:p-5 mb-6 sm:mb-8 shadow-xs">
    <div class="flex items-start gap-3.5">
      <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
        <i class="fa-solid fa-hand-holding-dollar"></i>
      </div>
      <div class="text-xs space-y-1">
        <h3 class="font-black text-slate-900 text-sm">Tidak Dikenakan Biaya Saat Ini (Tanpa Bayar di Awal)</h3>
        <p class="text-slate-600 leading-relaxed text-xs">
          Anda hanya mengajukan pesanan layanan internet. Pelanggan baru <strong class="text-slate-900">tidak langsung membayar biaya pemasangan</strong> sekarang. Pembayaran tagihan baru dilakukan setelah teknisi menyelesaikan pemasangan perangkat di rumah Anda dan koneksi internet sudah aktif.
        </p>
      </div>
    </div>
  </div>

  <!-- Grid Konten Utama -->
  <div class="w-full grid grid-cols-1 lg:grid-cols-12 gap-6 sm:gap-8 items-start">

    <!-- Sisi Kiri: Status Tracking & Rincian Pengajuan -->
    <div class="w-full lg:col-span-8 space-y-6">

      <!-- Card Status & Timeline Pelacakan Pengajuan -->
      <div class="border border-black rounded-2xl p-5 sm:p-7 bg-white space-y-6 shadow-sm">
        
        <!-- Header Status Pengajuan -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-gray-100 gap-3">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl shrink-0">
              <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h2 class="text-base sm:text-lg font-black text-black">Status: Menunggu Konfirmasi</h2>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">Admin sedang meninjau ketersediaan jalur fiber optik ke lokasi Anda.</p>
            </div>
          </div>
          <div class="text-left sm:text-right shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
              <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
              Dalam Antrean Admin
            </span>
          </div>
        </div>

        <!-- Timeline Pelacakan Pengajuan Real-Time -->
        <div>
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-400 mb-4">Tahapan Pengajuan Pesanan</h3>
          
          <div class="relative pl-6 sm:pl-8 space-y-6 before:content-[''] before:absolute before:left-3 before:top-2 before:bottom-2 before:w-[2px] before:bg-gray-200">
            
            <!-- Tahap 1: Pengajuan Diterima (Selesai) -->
            <div class="relative">
              <div class="absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold ring-4 ring-white">
                <i class="fa-solid fa-check text-[10px]"></i>
              </div>
              <div>
                <p class="text-xs font-black text-black">1. Pengajuan Pesanan Diterima</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Data formulir pemesanan telah tersimpan di sistem Banterpool.</p>
                <span class="inline-block mt-1 text-[10px] text-emerald-600 font-bold bg-emerald-50 px-2 py-0.5 rounded">
                  <i class="fa-regular fa-clock"></i> {{ now()->translatedFormat('d M Y, H:i') }} WIB
                </span>
              </div>
            </div>

            <!-- Tahap 2: Konfirmasi Admin (Sedang Berjalan) -->
            <div class="relative">
              <div class="absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs font-bold ring-4 ring-white animate-pulse">
                <i class="fa-solid fa-arrows-rotate text-[10px]"></i>
              </div>
              <div>
                <p class="text-xs font-black text-amber-700">2. Konfirmasi & Validasi Admin (Sedang Berjalan)</p>
                <p class="text-[11px] text-gray-600 mt-0.5">Admin Banterpool memverifikasi data dan menentukan titik ODP serta jadwal teknisi lapangan.</p>
                <span class="inline-block mt-1 text-[10px] text-amber-700 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                  <i class="fa-solid fa-phone-volume"></i> Admin akan menghubungi via WhatsApp / Telepon
                </span>
              </div>
            </div>

            <!-- Tahap 3: Jadwal & Pemasangan Teknisi (Menunggu) -->
            <div class="relative opacity-60">
              <div class="absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold ring-4 ring-white">
                <i class="fa-solid fa-wrench text-[10px]"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-700">3. Kedatangan & Instalasi Teknisi</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Penarikan kabel optik dan konfigurasi modem ONT di rumah Anda sesuai jadwal yang disetujui.</p>
              </div>
            </div>

            <!-- Tahap 4: Aktivasi Layanan Selesai (Menunggu) -->
            <div class="relative opacity-60">
              <div class="absolute -left-6 sm:-left-8 top-0.5 w-6 h-6 rounded-full bg-gray-200 text-gray-500 flex items-center justify-center text-xs font-bold ring-4 ring-white">
                <i class="fa-solid fa-wifi text-[10px]"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-gray-700">4. Layanan Aktif & Pembayaran Pertama</p>
                <p class="text-[11px] text-gray-500 mt-0.5">Koneksi internet siap digunakan dan bukti pembayaran diterbitkan setelah uji coba lancar.</p>
              </div>
            </div>

          </div>
        </div>

        <!-- Detail Data Pengajuan Box -->
        <div class="border border-gray-200 rounded-xl p-4 bg-gray-50/70 text-xs space-y-3">
          <div class="flex items-center justify-between pb-2 border-b border-gray-200">
            <span class="font-bold text-black text-xs uppercase tracking-wide">Detail Data Pengajuan</span>
            <span class="text-[11px] text-gray-400">Order ID Resmi</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Nomor Order</span>
            <div class="flex items-center gap-2">
              <span class="font-black text-brand text-xs font-mono">{{ $customerData['order_number'] ?? '-' }}</span>
              <button type="button" 
                      onclick="navigator.clipboard.writeText('{{ $customerData['order_number'] ?? '' }}'); alert('Nomor Order berhasil disalin!');"
                      class="text-gray-400 hover:text-black transition" title="Salin Nomor Order">
                <i class="fa-regular fa-copy"></i>
              </button>
            </div>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Waktu Pengajuan</span>
            <span class="font-bold text-black">{{ now()->translatedFormat('d F Y, H:i') }} WIB</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Metode Tagihan</span>
            <span class="font-bold text-black">Bayar Setelah Pemasangan Selesai</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-gray-500">Status Pembayaran Saat Ini</span>
            <span class="bg-gray-200 text-gray-700 px-2.5 py-0.5 rounded-full text-[10px] font-bold">
              Belum Ada Tagihan
            </span>
          </div>

          <div class="flex justify-between items-start pt-2 border-t border-gray-200">
            <span class="text-gray-500 shrink-0">Nama & Kontak</span>
            <div class="text-right">
              <p class="font-bold text-black">{{ $customerData['name'] ?? 'Pelanggan Baru' }}</p>
              <p class="text-[10px] text-gray-500">{{ $customerData['phone'] ?? '-' }} • {{ $customerData['email'] ?? '-' }}</p>
            </div>
          </div>

          <div class="flex justify-between items-start pt-1">
            <span class="text-gray-500 shrink-0">Alamat Pemasangan</span>
            <div class="text-right max-w-[240px] sm:max-w-xs">
              <p class="font-bold text-black text-[11px]">{{ $customerData['address'] ?? '-' }}</p>
              @if(!empty($customerData['latitude']) && !empty($customerData['longitude']))
                <a href="https://www.google.com/maps?q={{ $customerData['latitude'] }},{{ $customerData['longitude'] }}" target="_blank" class="text-brand hover:underline text-[10px] inline-flex items-center gap-1 font-semibold mt-0.5">
                  <i class="fa-solid fa-location-dot"></i> Buka Titik Maps
                </a>
              @endif
            </div>
          </div>

          <div class="flex justify-between items-center pt-1">
            <span class="text-gray-500">Jadwal Diajukan</span>
            <span class="font-bold text-black text-[11px]">
              @if(!empty($customerData['installation_date']))
                {{ \Carbon\Carbon::parse($customerData['installation_date'])->translatedFormat('l, d F Y') }}
              @else
                Besok
              @endif
              ({{ ($customerData['installation_time'] ?? '') == 'pagi' ? 'Pagi (08:00 - 12:00)' : 'Siang (13:00 - 16:00)' }})
            </span>
          </div>
        </div>

        <!-- Tombol Aksi Cek Status & WhatsApp -->
        <div class="pt-2 flex flex-col sm:flex-row gap-3">
          <!-- Tombol Lihat Status Pengajuan -->
          <a href="{{ route('order.detail', ['order_number' => $customerData['order_number'] ?? '']) }}" 
             class="flex-1 bg-brand hover:bg-brand-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition text-center shadow-sm flex items-center justify-center gap-2">
            <i class="fa-solid fa-clock-rotate-left"></i>
            <span>Pantau Status Pengajuan</span>
          </a>

          <!-- Tombol Hubungi WhatsApp Admin untuk Konfirmasi Cepat -->
          <a href="https://wa.me/628818679774?text={{ urlencode('Halo Admin BANTERPOOL, saya telah mengajukan pemesanan internet baru dengan Nomor Order ' . ($customerData['order_number'] ?? '') . ' atas nama ' . ($customerData['name'] ?? '') . '. Mohon konfirmasi dan pengecekan jalurnya ya.') }}" 
             target="_blank"
             class="flex-1 border border-emerald-600 bg-emerald-50 hover:bg-emerald-600 text-emerald-800 hover:text-white font-bold py-3 px-4 rounded-xl text-xs transition text-center flex items-center justify-center gap-2">
            <i class="fa-brands fa-whatsapp text-sm"></i>
            <span>Konfirmasi Cepat via WA</span>
          </a>

          <!-- Tombol Beranda -->
          <a href="{{ route('home') }}" 
             class="border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-3 px-4 rounded-xl text-xs transition text-center">
            Beranda
          </a>
        </div>

      </div>

    </div>

    <!-- Sisi Kanan: Ringkasan Pesanan & Layanan -->
    <div class="w-full lg:col-span-4 space-y-6">

      <div class="w-full border border-black rounded-2xl p-4 sm:p-6 bg-white space-y-6">
        <h3 class="text-base font-black text-black">Ringkasan Pengajuan</h3>

        <div class="space-y-4 text-xs">
          <div class="flex items-start justify-between">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-wifi text-brand text-lg"></i>
              <div>
                <p class="font-bold text-black">{{ $customerData['package_name'] ?? 'Paket 20 Mbps' }}</p>
                <p class="text-[10px] text-gray-400">Kecepatan {{ $customerData['package_speed'] ?? '20 Mbps' }}</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-black">Rp{{ $customerData['price'] ?? '110.000' }}</p>
              <p class="text-[10px] text-gray-400">/bulan</p>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-3 flex justify-between items-center text-sm">
            <span class="font-bold text-black">Estimasi Tagihan</span>
            <span class="font-black text-brand text-base">Rp{{ $customerData['total'] ?? '110.000' }} <span class="text-xs font-normal text-gray-500">/bulan</span></span>
          </div>

          <div class="bg-slate-100 rounded-xl p-2.5 text-[10px] text-slate-600 leading-tight">
            <i class="fa-solid fa-shield-check text-brand mr-1"></i> Biaya ini baru mulai dihitung dan ditagihkan setelah internet menyala normal di rumah Anda.
          </div>
        </div>

        <div class="space-y-3 text-xs pt-2">
          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-regular fa-clock"></i></div>
            <div>
              <p class="font-bold text-black">Pemasangan Cepat</p>
              <p class="text-[10px] text-gray-400">Tim admin segera menghubungi Anda</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-solid fa-rotate-left"></i></div>
            <div>
              <p class="font-bold text-black">Garansi 7 Hari</p>
              <p class="text-[10px] text-gray-400">Garansi kepuasan koneksi internet</p>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-black"><i class="fa-solid fa-headset"></i></div>
            <div>
              <p class="font-bold text-black">Dukungan Teknisi 24/7</p>
              <p class="text-[10px] text-gray-400">Bantuan teknis siap melayani Anda</p>
            </div>
          </div>
        </div>

        <hr class="border-gray-200">

        <div class="text-center pt-2">
          <p class="text-xs font-bold text-black">Ada Pertanyaan?</p>
          <p class="text-[10px] text-gray-400 mb-3">Tanyakan ke tim customer service kami</p>

          <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20ingin%20bertanya%20mengenai%20status%20pengajuan%20pemesanan%20saya." target="_blank" class="w-full border border-brand text-brand font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-brand hover:text-white transition">
            <i class="fa-solid fa-headset"></i> Hubungi Customer Service
          </a>
        </div>

      </div>

    </div>

  </div>

</div>
@endsection
