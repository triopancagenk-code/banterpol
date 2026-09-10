@extends('layouts.app')

@section('title', 'WiFi Banterpool - Detail Pemesanan')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

  @if($hasOrder ?? false)
    <!-- Tombol Kembali -->
    <a href="{{ route('payment.status', ['status' => 'success']) }}" class="inline-flex items-center gap-2 text-brand font-bold text-sm mb-6 hover:underline">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>

    <!-- Card Utama Detail Pemesanan -->
    <div class="border border-black rounded-2xl p-6 sm:p-8 bg-white space-y-6 shadow-sm">

      <!-- Header Invoice -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 pb-6 border-b border-gray-200">
        <div>
          <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-extrabold inline-flex items-center gap-1 mb-2">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span> Pembayaran Berhasil
          </span>
          <h1 class="text-2xl font-black text-black">Detail Pemesanan</h1>
          <p class="text-xs text-gray-500 mt-0.5">No. Order: <span class="font-bold text-brand">{{ $customerData['order_number'] ?? '-' }}</span></p>
        </div>

      <div class="text-left sm:text-right">
        <button type="button" onclick="window.print()" class="border border-brand text-brand hover:bg-brand hover:text-white px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
          <i class="fa-solid fa-print"></i> Cetak Bukti
        </button>
      </div>
    </div>

    <!-- Info Paket Internet -->
    <div>
      <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Paket Langganan</h3>
      <div class="border border-black rounded-xl p-4 bg-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-red-50 flex items-center justify-center text-brand">
            <i class="fa-solid fa-wifi text-xl"></i>
          </div>
          <div>
            <h4 class="font-black text-black text-base">{{ $customerData['package_name'] ?? request('package_name', 'Paket 20 Mbps') }}</h4>
            <p class="text-xs text-gray-500">Internet Cepat & Stabil Tanpa Batas</p>
          </div>
        </div>
        <div class="text-left sm:text-right">
          <p class="font-black text-brand text-base">Rp{{ $customerData['price'] ?? request('package_price', '110.000') }} <span class="text-xs text-gray-500 font-normal">/bulan</span></p>
          <p class="text-[10px] text-emerald-600 font-bold"><i class="fa-solid fa-circle-check"></i> Siap Diaktifkan</p>
        </div>
      </div>
    </div>

    <!-- Data Pelanggan & Pemasangan Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">

      <!-- Box Data Pelanggan Dinamis -->
      <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Pelanggan</h3>
        <div class="border border-gray-200 rounded-xl p-4 space-y-3 text-xs bg-gray-50">
          <div>
            <p class="text-gray-400 text-[10px]">Nama Pelanggan</p>
            <p class="font-bold text-black">{{ $customerData['name'] }}</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Nomor Telepon</p>
            <p class="font-bold text-black">{{ $customerData['phone'] }}</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Email</p>
            <p class="font-bold text-black">{{ $customerData['email'] }}</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">No. KTP (NIK)</p>
            <p class="font-bold text-black font-mono">{{ $customerData['id_card_number'] ?? '-' }}</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Tempat, Tanggal Lahir</p>
            <p class="font-bold text-black">
              {{ $customerData['birth_place'] ?? '' }}{{ (!empty($customerData['birth_place']) && !empty($customerData['birth_date'])) ? ', ' : '' }}{{ !empty($customerData['birth_date']) ? \Carbon\Carbon::parse($customerData['birth_date'])->translatedFormat('d F Y') : '-' }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Alamat Pemasangan</p>
            <p class="font-bold text-black">{{ $customerData['address'] }}</p>
            @if(!empty($customerData['latitude']) && !empty($customerData['longitude']))
              <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] font-normal">
                <span class="inline-flex items-center gap-1 bg-red-50 text-brand px-2 py-0.5 rounded border border-red-200">
                  <i class="fa-solid fa-location-dot text-[9px]"></i> Titik GPS: {{ $customerData['latitude'] }}, {{ $customerData['longitude'] }}
                </span>
                <a href="https://www.google.com/maps?q={{ $customerData['latitude'] }},{{ $customerData['longitude'] }}" target="_blank" class="text-brand font-bold hover:underline">
                  Buka di Maps <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                </a>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Box Jadwal & Transaksi -->
      <div>
        <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Informasi Transaksi & Jadwal</h3>
        <div class="border border-gray-200 rounded-xl p-4 space-y-3 text-xs bg-gray-50">
          <div>
            <p class="text-gray-400 text-[10px]">Tanggal Transaksi</p>
            <p class="font-bold text-black">28 Mei 2026, 10:45 WIB</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Metode Pembayaran</p>
            <p class="font-bold text-black">Transfer Bank (BCA)</p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Jadwal Teknisi</p>
            <p class="font-bold text-black">
              @if(!empty($customerData['installation_date']))
                {{ \Carbon\Carbon::parse($customerData['installation_date'])->translatedFormat('l, d F Y') }}
              @else
                Selasa, 28 Agustus 2026
              @endif
              ({{ ($customerData['installation_time'] ?? '') == 'pagi' ? '08:00 - 12:00 WIB' : '13:00 - 16:00 WIB' }})
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px]">Status Teknisi</p>
            <p class="font-bold text-brand"><i class="fa-solid fa-truck-fast"></i> Penjadwalan Teknisi Ke Lokasi</p>
          </div>
        </div>
      </div>

    </div>

    <!-- Rincian Biaya -->
    <div class="pt-4 border-t border-gray-200">
      <h3 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">Rincian Pembayaran</h3>
      <div class="space-y-2 text-xs">
        <div class="flex justify-between text-gray-600">
          <span>Biaya Langganan {{ $customerData['package_name'] ?? request('package_name', 'Paket 20 Mbps') }}</span>
          <span class="font-bold text-black">Rp{{ $customerData['price'] ?? request('package_price', '110.000') }}</span>
        </div>
        <div class="border-t border-gray-200 pt-3 flex justify-between items-center text-sm">
          <span class="font-black text-black">Total Biaya Ditagihkan</span>
          <span class="font-black text-brand text-lg">Rp{{ $customerData['total'] ?? request('package_price', '110.000') }}</span>
        </div>
      </div>
    </div>

    <!-- Tombol Navigasi Bawah -->
    <div class="pt-4 flex flex-col sm:flex-row justify-end gap-3">
      <a href="{{ route('home') }}" class="w-full sm:w-auto bg-brand hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-xl text-xs transition text-center">
        Kembali ke Beranda
      </a>
    </div>

  </div>
  @else
    <!-- Empty State Pemesanan Pelanggan -->
    <div class="border border-slate-200 rounded-3xl p-8 sm:p-12 bg-white text-center shadow-xs">
      <div class="w-16 h-16 rounded-2xl bg-red-50 text-brand flex items-center justify-center mx-auto mb-4 text-2xl">
        <i class="fa-solid fa-box-open"></i>
      </div>
      <h2 class="text-xl sm:text-2xl font-black text-slate-900 mb-2">Belum Ada Pemesanan Aktif</h2>
      <p class="text-xs sm:text-sm text-slate-500 max-w-md mx-auto mb-6">
        Anda belum memiliki data pemesanan paket WiFi Banterpool saat ini. Silakan pilih paket langganan internet super kencang sesuai kebutuhan Anda.
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
