@extends('layouts.app')

@section('title', 'WiFi Banterpool - Status & Detail Pemesanan')

@section('content')
<div class="w-full max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6 relative">

  <!-- Bar Atas: Tombol Kembali & Form Lacak Order Lain -->
  <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 sm:mb-6">
    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-brand font-bold text-sm hover:underline">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
    </a>

    <!-- Form Pencarian / Lacak Nomor Order Lain -->
    <form action="{{ route('order.detail') }}" method="GET" class="flex items-center gap-2 w-full sm:w-auto">
      <div class="relative w-full sm:w-64">
        <input type="text" 
               name="order_number" 
               value="{{ request('order_number') }}" 
               placeholder="Cari No. Order (ORD-...)" 
               class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded-xl focus:ring-brand focus:border-brand font-mono">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-gray-400 text-xs"></i>
      </div>
      <button type="submit" class="shrink-0 bg-slate-900 hover:bg-black text-white px-3 py-1.5 rounded-xl text-xs font-bold transition">
        Lacak
      </button>
    </form>
  </div>

  @if($hasOrder ?? false)
    @php
      $status = $customerData['status'] ?? 'Menunggu Konfirmasi';
      $isMenunggu = in_array($status, ['Menunggu Konfirmasi', 'Menunggu', 'Pending']);
      $isJadwal = in_array($status, ['Jadwal Teknisi', 'Dijadwalkan']);
      $isProses = in_array($status, ['Sedang Dipasang', 'Proses', 'Pemasangan']);
      $isSelesai = in_array($status, ['Selesai', 'Aktif']);
      $isKendala = in_array($status, ['Kendala Lapangan', 'Kendala']);
      $isBatal = in_array($status, ['Dibatalkan', 'Batal']);
    @endphp

    <!-- Card Utama Detail & Status Pemesanan -->
    <div class="border border-black rounded-3xl p-5 sm:p-8 bg-white space-y-6 sm:space-y-8 shadow-sm">

      <!-- Header Status Pemesanan -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-gray-200">
        <div>
          <div class="flex flex-wrap items-center gap-2 mb-2">
            @if($isMenunggu)
              <span class="bg-amber-100 text-amber-800 border border-amber-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Menunggu Konfirmasi Admin</span>
              </span>
            @elseif($isJadwal)
              <span class="bg-blue-100 text-blue-800 border border-blue-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-calendar-check text-blue-600"></i>
                <span>Jadwal Teknisi Ditentukan</span>
              </span>
            @elseif($isProses)
              <span class="bg-purple-100 text-purple-800 border border-purple-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-purple-600 animate-ping"></span>
                <span>Sedang Dalam Pemasangan</span>
              </span>
            @elseif($isSelesai)
              <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span>Layanan Aktif / Selesai</span>
              </span>
            @elseif($isKendala)
              <span class="bg-orange-100 text-orange-800 border border-orange-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-triangle-exclamation text-orange-600"></i>
                <span>Kendala Lapangan</span>
              </span>
            @elseif($isBatal)
              <span class="bg-red-100 text-red-800 border border-red-200 px-3 py-1 rounded-full text-[11px] font-extrabold inline-flex items-center gap-1.5 shadow-xs">
                <i class="fa-solid fa-xmark text-red-600"></i>
                <span>Pesanan Dibatalkan</span>
              </span>
            @endif

            <span class="text-xs text-gray-400">•</span>
            <span class="text-xs font-mono font-bold text-gray-500">ID: {{ $customerData['order_number'] ?? '-' }}</span>
          </div>

          <h1 class="text-2xl sm:text-3xl font-black text-black">Status Pengajuan Pemesanan</h1>
          <p class="text-xs text-gray-500 mt-1">
            Diajukan pada {{ !empty($customerData['created_at']) ? \Carbon\Carbon::parse($customerData['created_at'])->translatedFormat('l, d F Y, H:i') : now()->translatedFormat('d F Y') }} WIB
          </p>
        </div>

        <div class="flex items-center gap-2 self-stretch sm:self-auto">
          <button type="button" onclick="window.print()" class="flex-1 sm:flex-initial border border-gray-300 hover:border-black text-gray-700 hover:text-black bg-white px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 shadow-xs">
            <i class="fa-solid fa-print"></i>
            <span>Cetak Bukti</span>
          </button>
        </div>
      </div>

      <!-- Banner Status Khusus & Informasi Admin -->
      @if($isMenunggu)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 text-xs text-amber-900 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
            <i class="fa-solid fa-hourglass-half"></i>
          </div>
          <div class="space-y-1">
            <h4 class="font-black text-black text-sm">Pesanan Sedang Menunggu Konfirmasi Admin</h4>
            <p class="text-gray-600 leading-relaxed">
              Pengajuan pesanan Anda telah tersimpan dengan aman. Tim Admin Banterpool saat ini sedang memeriksa ketersediaan port ODP dan jalur kabel ke alamat Anda. Kami akan segera menghubungi Anda melalui WhatsApp atau telepon untuk konfirmasi jadwal teknisi.
            </p>
            <p class="text-[11px] font-bold text-amber-800 pt-1">
              <i class="fa-solid fa-circle-info mr-1"></i> Anda tidak perlu melakukan pembayaran sekarang. Tagihan dibayar setelah teknisi selesai memasang dan internet siap dipakai.
            </p>
          </div>
        </div>
      @elseif($isJadwal)
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 text-xs text-blue-900 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <div class="space-y-1">
            <h4 class="font-black text-black text-sm">Jadwal Teknisi Telah Dikonfirmasi</h4>
            <p class="text-gray-600 leading-relaxed">
              Pesanan telah diverifikasi oleh Admin. Tim teknisi Banterpool dijadwalkan hadir ke lokasi pemasangan sesuai tanggal dan sesi waktu yang telah ditentukan.
            </p>
          </div>
        </div>
      @elseif($isProses)
        <div class="bg-purple-50 border border-purple-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 text-xs text-purple-900 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
            <i class="fa-solid fa-truck-fast"></i>
          </div>
          <div class="space-y-1">
            <h4 class="font-black text-black text-sm">Teknisi Sedang Dalam Proses Instalasi</h4>
            <p class="text-gray-600 leading-relaxed">
              Petugas teknisi sedang melakukan penarikan kabel optik, penyambungan port ODP, dan konfigurasi modem WiFi di rumah Anda.
            </p>
          </div>
        </div>
      @elseif($isSelesai)
        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 text-xs text-emerald-900 shadow-xs">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-lg shrink-0 mt-0.5">
            <i class="fa-solid fa-circle-check"></i>
          </div>
          <div class="space-y-1">
            <h4 class="font-black text-black text-sm">Layanan Telah Aktif & Selesai Dipasang!</h4>
            <p class="text-gray-600 leading-relaxed">
              Pemasangan fiber optik telah berhasil diselesaikan dan koneksi internet Anda telah aktif dengan kecepatan normal. Selamat menikmati internet super kencang dari Banterpool!
            </p>
          </div>
        </div>
      @endif

      <!-- Timeline Tracking Alur Pemesanan 4 Langkah -->
      <div class="border border-gray-200 rounded-2xl p-4 sm:p-6 bg-gray-50/50">
        <h3 class="text-xs font-black text-black uppercase tracking-wider mb-5">Progres Pengajuan Layanan</h3>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 relative">
          <!-- Step 1: Pengajuan -->
          <div class="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
            <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
              <i class="fa-solid fa-check"></i>
            </div>
            <div>
              <p class="text-xs font-bold text-black">1. Pengajuan Dikirim</p>
              <p class="text-[10px] text-gray-500">Formulir diterima sistem</p>
            </div>
          </div>

          <!-- Step 2: Konfirmasi Admin -->
          <div class="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
            @if($isMenunggu)
              <div class="w-8 h-8 rounded-full bg-amber-500 text-white flex items-center justify-center font-bold text-xs shrink-0 animate-pulse shadow-xs">
                <i class="fa-solid fa-clock"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-amber-700">2. Konfirmasi Admin</p>
                <p class="text-[10px] text-amber-600 font-semibold">Sedang Diproses</p>
              </div>
            @elseif($isJadwal || $isProses || $isSelesai)
              <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                <i class="fa-solid fa-check"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-black">2. Terkonfirmasi</p>
                <p class="text-[10px] text-gray-500">Admin menyetujui</p>
              </div>
            @else
              <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center font-bold text-xs shrink-0">
                2
              </div>
              <div>
                <p class="text-xs font-bold text-gray-400">2. Konfirmasi Admin</p>
                <p class="text-[10px] text-gray-400">Menunggu</p>
              </div>
            @endif
          </div>

          <!-- Step 3: Pemasangan Teknisi -->
          <div class="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
            @if($isJadwal || $isProses)
              <div class="w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs shrink-0 animate-pulse shadow-xs">
                <i class="fa-solid fa-wrench"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-blue-700">3. Pemasangan Teknisi</p>
                <p class="text-[10px] text-blue-600 font-semibold">{{ $isProses ? 'Sedang Dipasang' : 'Terjadwal' }}</p>
              </div>
            @elseif($isSelesai)
              <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                <i class="fa-solid fa-check"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-black">3. Pemasangan Selesai</p>
                <p class="text-[10px] text-gray-500">Perangkat terpasang</p>
              </div>
            @else
              <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center font-bold text-xs shrink-0">
                3
              </div>
              <div>
                <p class="text-xs font-bold text-gray-400">3. Pemasangan Teknisi</p>
                <p class="text-[10px] text-gray-400">Menunggu jadwal</p>
              </div>
            @endif
          </div>

          <!-- Step 4: Layanan Aktif -->
          <div class="flex sm:flex-col items-center sm:items-start gap-3 sm:gap-2">
            @if($isSelesai)
              <div class="w-8 h-8 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-xs">
                <i class="fa-solid fa-wifi"></i>
              </div>
              <div>
                <p class="text-xs font-bold text-emerald-700">4. Layanan Aktif</p>
                <p class="text-[10px] text-emerald-600 font-semibold">Siap Digunakan</p>
              </div>
            @else
              <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center font-bold text-xs shrink-0">
                4
              </div>
              <div>
                <p class="text-xs font-bold text-gray-400">4. Layanan Aktif</p>
                <p class="text-[10px] text-gray-400">Tahap akhir</p>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Info Paket Internet -->
      <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Paket Langganan Dipilih</h3>
        <div class="border border-black rounded-2xl p-4 sm:p-5 bg-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-red-50 flex items-center justify-center text-brand text-2xl shrink-0">
              <i class="fa-solid fa-wifi"></i>
            </div>
            <div>
              <h4 class="font-black text-black text-base">{{ $customerData['package_name'] ?? 'Paket 20 Mbps' }}</h4>
              <p class="text-xs text-gray-500">Kecepatan {{ $customerData['speed'] ?? '20 Mbps' }} simetris • Unlimited tanpa FUP</p>
            </div>
          </div>
          <div class="text-left sm:text-right">
            <p class="font-black text-brand text-lg">Rp{{ $customerData['price'] ?? '110.000' }} <span class="text-xs text-gray-500 font-normal">/bulan</span></p>
            <p class="text-[11px] text-gray-500 font-medium">Bebas biaya pemasangan kabel standar</p>
          </div>
        </div>
      </div>

      <!-- Grid Detail Informasi Pelanggan & Jadwal -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- Box Data Pelanggan -->
        <div>
          <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Pelanggan</h3>
          <div class="border border-gray-200 rounded-2xl p-4 sm:p-5 space-y-3.5 text-xs bg-gray-50/70">
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Nama Pelanggan</p>
              <p class="font-bold text-black flex items-center gap-2">
                <i class="fa-regular fa-user text-brand"></i> {{ $customerData['name'] }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Nomor Telepon / WhatsApp</p>
              <p class="font-bold text-black flex items-center gap-2">
                <i class="fa-solid fa-phone text-brand"></i> {{ $customerData['phone'] }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Email</p>
              <p class="font-bold text-black flex items-center gap-2">
                <i class="fa-regular fa-envelope text-brand"></i> {{ $customerData['email'] }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">No. KTP (NIK)</p>
              <p class="font-bold text-black font-mono flex items-center gap-2">
                <i class="fa-regular fa-id-card text-brand font-sans"></i> {{ $customerData['id_card_number'] ?? '-' }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Tempat, Tanggal Lahir</p>
              <p class="font-bold text-black flex items-center gap-2">
                <i class="fa-regular fa-calendar text-brand"></i>
                {{ $customerData['birth_place'] ?? '' }}{{ (!empty($customerData['birth_place']) && !empty($customerData['birth_date'])) ? ', ' : '' }}{{ !empty($customerData['birth_date']) ? \Carbon\Carbon::parse($customerData['birth_date'])->translatedFormat('d F Y') : '-' }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Alamat Pemasangan</p>
              <p class="font-bold text-black leading-relaxed flex items-start gap-2">
                <i class="fa-solid fa-location-dot text-brand mt-0.5 shrink-0"></i>
                <span>{{ $customerData['address'] }}</span>
              </p>
              @if(!empty($customerData['latitude']) && !empty($customerData['longitude']))
                <div class="mt-2 flex flex-wrap items-center gap-2 text-[10px]">
                  <span class="inline-flex items-center gap-1 bg-red-50 text-brand px-2 py-0.5 rounded border border-red-200">
                    <i class="fa-solid fa-crosshairs text-[9px]"></i> Titik GPS: {{ $customerData['latitude'] }}, {{ $customerData['longitude'] }}
                  </span>
                  <a href="https://www.google.com/maps?q={{ $customerData['latitude'] }},{{ $customerData['longitude'] }}" target="_blank" class="text-brand font-bold hover:underline inline-flex items-center gap-1">
                    <span>Buka Maps</span>
                    <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                  </a>
                </div>
              @endif
            </div>
          </div>
        </div>

        <!-- Box Jadwal & Teknisi -->
        <div>
          <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Jadwal & Teknisi</h3>
          <div class="border border-gray-200 rounded-2xl p-4 sm:p-5 space-y-3.5 text-xs bg-gray-50/70">
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Nomor Order</p>
              <p class="font-black text-brand font-mono">{{ $customerData['order_number'] }}</p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Jadwal Pemasangan yang Diajukan</p>
              <p class="font-bold text-black flex items-center gap-2">
                <i class="fa-regular fa-clock text-brand"></i>
                @if(!empty($customerData['installation_date']))
                  {{ \Carbon\Carbon::parse($customerData['installation_date'])->translatedFormat('l, d F Y') }}
                @else
                  Menyesuaikan konfirmasi admin
                @endif
                <span class="text-gray-500 font-normal">
                  ({{ ($customerData['installation_time'] ?? '') == 'pagi' ? 'Pagi 08:00 - 12:00 WIB' : 'Siang 13:00 - 16:00 WIB' }})
                </span>
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Teknisi Lapangan</p>
              @if(!empty($customerData['technician']))
                <p class="font-bold text-black flex items-center gap-2">
                  <i class="fa-solid fa-user-gear text-brand"></i> {{ $customerData['technician'] }}
                </p>
              @else
                <p class="text-gray-500 italic flex items-center gap-2">
                  <i class="fa-solid fa-user-clock text-gray-400"></i> Menunggu penugasan teknisi oleh Admin
                </p>
              @endif
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Metode Pembayaran</p>
              <p class="font-bold text-black">
                {{ $customerData['payment_method'] ?? 'Bayar Setelah Pemasangan (Konfirmasi Admin)' }}
              </p>
            </div>
            <div>
              <p class="text-gray-400 text-[10px] mb-0.5">Status Pembayaran</p>
              @if($isSelesai && ($customerData['payment_status'] ?? '') === 'Lunas')
                <span class="bg-emerald-100 text-emerald-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                  <i class="fa-solid fa-check"></i> Sudah Lunas
                </span>
              @else
                <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                  <i class="fa-regular fa-clock"></i> Belum Dikenakan Biaya (Bayar Setelah Terpasang)
                </span>
              @endif
            </div>
            @if(!empty($customerData['admin_notes']))
              <div class="pt-2 border-t border-gray-200">
                <p class="text-gray-400 text-[10px] mb-0.5">Catatan Admin</p>
                <p class="text-gray-700 italic text-[11px]">{{ $customerData['admin_notes'] }}</p>
              </div>
            @endif
          </div>
        </div>

      </div>

      <!-- Rincian Biaya -->
      <div class="pt-4 border-t border-gray-200">
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Rincian Estimasi Biaya</h3>
        <div class="space-y-2 text-xs">
          <div class="flex justify-between text-gray-600">
            <span>Biaya Langganan {{ $customerData['package_name'] ?? 'Paket WiFi' }}</span>
            <span class="font-bold text-black">Rp{{ $customerData['price'] ?? '110.000' }}</span>
          </div>
          <div class="flex justify-between text-gray-600">
            <span>Biaya Pemasangan & Konfigurasi</span>
            <span class="font-bold text-emerald-600">Gratis (Promo Pemasangan)</span>
          </div>
          <div class="border-t border-gray-200 pt-3 flex justify-between items-center text-sm">
            <div>
              <p class="font-black text-black">Total Tagihan Nanti</p>
              <p class="text-[10px] text-gray-500 font-normal">Ditagihkan setelah internet aktif normal</p>
            </div>
            <span class="font-black text-brand text-lg">Rp{{ $customerData['total'] ?? '110.000' }}</span>
          </div>
        </div>
      </div>

      <!-- Tombol Aksi Bawah -->
      <div class="pt-4 flex flex-col sm:flex-row justify-between items-center gap-3 border-t border-gray-100">
        <!-- Hubungi Admin via WA -->
        <a href="https://wa.me/628818679774?text={{ urlencode('Halo Admin BANTERPOOL, saya ingin menanyakan progres status pesanan saya dengan No. Order: ' . ($customerData['order_number'] ?? '') . ' atas nama ' . ($customerData['name'] ?? '') . '.') }}" 
           target="_blank"
           class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-6 py-3 rounded-xl text-xs transition shadow-sm">
          <i class="fa-brands fa-whatsapp text-sm"></i>
          <span>Hubungi Admin via WhatsApp</span>
        </a>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <a href="{{ route('home') }}" class="flex-1 sm:flex-initial border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold px-6 py-3 rounded-xl text-xs transition text-center">
            Ke Beranda
          </a>
          @if($isSelesai)
            <a href="{{ route('tagihan') }}" class="flex-1 sm:flex-initial bg-brand hover:bg-brand-700 text-white font-bold px-6 py-3 rounded-xl text-xs transition text-center shadow-sm inline-flex items-center justify-center gap-2">
              <i class="fa-solid fa-file-invoice-dollar"></i>
              <span>Kelola Tagihan</span>
            </a>
          @endif
        </div>
      </div>

      <!-- Keterangan Kebijakan 1 Akun 1 Layanan -->
      <div class="pt-3 border-t border-gray-100 flex items-center gap-2 text-[11px] text-gray-500">
        <i class="fa-solid fa-circle-info text-amber-500 shrink-0"></i>
        <span>Ketentuan Layanan: 1 akun hanya dapat berlangganan 1 layanan internet Banterpool.</span>
      </div>

    </div>

  @else
    <!-- Empty State Pemesanan Pelanggan -->
    <div class="border border-slate-200 rounded-3xl p-8 sm:p-12 bg-white text-center shadow-xs">
      <div class="w-16 h-16 rounded-2xl bg-red-50 text-brand flex items-center justify-center mx-auto mb-4 text-2xl">
        <i class="fa-solid fa-box-open"></i>
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Belum Ada Pemesanan Ditemukan</h2>
      <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mb-6">
        Nomor pesanan tidak ditemukan atau Anda belum memiliki riwayat pengajuan pesanan. Masukkan nomor order Anda di kolom pencarian di atas atau ajukan pesanan baru sekarang.
      </p>
      <div class="flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('paket') }}" class="inline-flex items-center gap-2 bg-brand hover:bg-brand-700 text-white font-bold text-xs px-6 py-3 rounded-xl transition shadow-xs">
          <i class="fa-solid fa-wifi"></i>
          <span>Pilih Paket Langganan</span>
        </a>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 border border-slate-300 text-slate-700 hover:bg-slate-50 font-bold text-xs px-6 py-3 rounded-xl transition">
          <i class="fa-solid fa-house"></i>
          <span>Ke Beranda</span>
        </a>
      </div>
    </div>
  @endif

</div>
@endsection
