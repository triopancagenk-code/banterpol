@extends('layouts.app')

@section('title', 'Tentang Kami - BANTERPOOL')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">

  <!-- BREADCRUMB -->
  <nav class="flex items-center gap-2 text-xs text-gray-500 mb-6 font-medium">
    <a href="{{ route('home') }}" class="hover:text-brand transition flex items-center gap-1.5">
      <i class="fa-solid fa-house text-xs"></i>
      <span>Beranda</span>
    </a>
    <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
    <span class="text-brand font-bold">Tentang Kami</span>
  </nav>

  <!-- HERO SECTION: TITLE & BANNER -->
  <section class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center mb-12">
    <!-- Sisi Kiri: Judul & Deskripsi -->
    <div class="lg:col-span-6 space-y-3">
      <h1 class="text-3xl sm:text-5xl font-black text-black tracking-tight leading-tight">
        Tentang Kami
      </h1>
      <p class="text-gray-600 text-sm sm:text-base leading-relaxed max-w-xl font-normal">
        BANTERPOOL hadir untuk memberikan layanan internet yang cepat, stabil, dan tanpa batas untuk semua kebutuhan Anda.
      </p>
    </div>

    <!-- Sisi Kanan: Foto Gedung Kantor Banterpool -->
    <div class="lg:col-span-6 flex justify-center lg:justify-end">
      <div class="w-full max-w-xl rounded-3xl overflow-hidden shadow-sm border border-gray-100 relative group bg-white">
        <img src="{{ asset('image/about-hero.jpg') }}" 
             alt="Kantor Pusat Banterpool" 
             class="w-full h-64 sm:h-80 object-cover transform group-hover:scale-105 transition-transform duration-500">
      </div>
    </div>
  </section>

  <!-- SECTION SIAPA KAMI (CARD BESAR) -->
  <section class="bg-white border border-gray-200 rounded-3xl p-6 sm:p-10 shadow-xs mb-14">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 items-center">
      
      <!-- Kolom Kiri: Foto Front Desk / Lobby Banterpool -->
      <div class="lg:col-span-5">
        <div class="rounded-2xl overflow-hidden shadow-xs border border-gray-100 relative h-64 sm:h-80 lg:h-92 group bg-gray-50">
          <img src="{{ asset('image/about-team.jpg') }}" 
               alt="Kantor Pelayanan Banterpool" 
               class="w-full h-full object-cover transform group-hover:scale-105 transition duration-500">
        </div>
      </div>

      <!-- Kolom Kanan: Uraian Siapa Kami -->
      <div class="lg:col-span-7 space-y-4">
        <div>
          <h2 class="text-2xl sm:text-3xl font-black text-black tracking-tight">Siapa Kami</h2>
        </div>

        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          PT Saga Infrastruktur Media Selaras (SIMS) adalah penyedia layanan internet berbasis Fiber Optik (FFTH) yang berkomitmen menghadirkan konektivitas berkualitas tinggi dan aman bagi masyarakat serta pelaku usaha UMKM Indonesia.
        </p>
        
        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
          Melalui brand Banterpol, kami memperluas jangkauan jaringan agar seluruh lapisan masyarakat dapat menikmati internet cepat tanpa FUP dengan biaya terjangkau. Layanan kami dilengkapi solusi CCTV terpadu untuk keamanan maksimal.
        </p>

        <div class="pt-3 border-t border-gray-100">
          <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
            LAYANAN
          </h3>
          <p class="text-xs sm:text-sm text-gray-700 leading-relaxed">
            Internet FFTH &bull; Koneksi UNLIMITED &bull; <strong>Instalasi CCTV &bull; Kemitraan UMKM</strong> &bull; Packaging Design &bull; Art Direction &bull; Figma &bull; Photoshop
          </p>
        </div>
      </div>

    </div>
  </section>

  <!-- SECTION MENGAPA MEMILIH BANTERPOOL? -->
  <section class="mb-14">
    <!-- Header Terpusat -->
    <div class="text-center max-w-2xl mx-auto mb-8">
      <h2 class="text-2xl sm:text-3xl font-black text-black tracking-tight">
        Mengapa Memilih <span class="text-brand">BANTERPOOL</span>?
      </h2>
    </div>

    <!-- 6 Feature Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      
      <!-- 1. Kecepatan Tinggi -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-rocket"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Kecepatan Tinggi
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Nikmati internet super cepat untuk semua aktivitas online.
          </p>
        </div>
      </div>

      <!-- 2. Jaringan Stabil -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-shield-halved"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Jaringan Stabil
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Koneksi andal tanpa putus, setiap saat.
          </p>
        </div>
      </div>

      <!-- 3. Tanpa Batas Kuota -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-infinity"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Tanpa Batas Kuota
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Internet unlimited untuk kebutuhan tanpa batas.
          </p>
        </div>
      </div>

      <!-- 4. Support 24/7 -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-headset"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Support 24/7
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Tim kami siap membantu kapan pun Anda butuh.
          </p>
        </div>
      </div>

      <!-- 5. Harga Terjangkau -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Harga Terjangkau
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Paket internet berkualitas dengan harga terbaik.
          </p>
        </div>
      </div>

      <!-- 6. Jangkauan Luas -->
      <div class="bg-white border border-gray-200/80 rounded-2xl p-6 shadow-xs hover:shadow-md hover:border-red-200 transition duration-200 group flex flex-col justify-between">
        <div>
          <div class="w-12 h-12 rounded-full bg-red-50 group-hover:bg-red-100 text-brand flex items-center justify-center text-xl mb-4 transition-colors">
            <i class="fa-solid fa-location-dot"></i>
          </div>
          <h3 class="text-base font-extrabold text-black mb-2 group-hover:text-brand transition-colors">
            Jangkauan Luas
          </h3>
          <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
            Layanan tersedia di banyak wilayah untuk Anda.
          </p>
        </div>
      </div>

    </div>
  </section>

  <!-- BOTTOM ACHIEVEMENT / METRICS STATS BAR -->
  <section class="bg-white border border-gray-200 rounded-3xl p-6 sm:p-8 shadow-xs">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center divide-y lg:divide-y-0 lg:divide-x divide-gray-100">
      
      <!-- Metric 1: 50.000+ Pelanggan Terpercaya -->
      <div class="pt-4 lg:pt-0 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-red-50 text-brand flex items-center justify-center text-lg mb-2">
          <i class="fa-solid fa-users"></i>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-black tracking-tight">50.000+</p>
        <p class="text-xs font-semibold text-gray-500 mt-1">Pelanggan Terpercaya</p>
      </div>

      <!-- Metric 2: 150+ Area Layanan -->
      <div class="pt-4 lg:pt-0 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-red-50 text-brand flex items-center justify-center text-lg mb-2">
          <i class="fa-solid fa-map-location-dot"></i>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-black tracking-tight">150+</p>
        <p class="text-xs font-semibold text-gray-500 mt-1">Area Layanan</p>
      </div>

      <!-- Metric 3: 99.9% Jaringan Stabil -->
      <div class="pt-4 lg:pt-0 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-red-50 text-brand flex items-center justify-center text-lg mb-2">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-black tracking-tight">99.9%</p>
        <p class="text-xs font-semibold text-gray-500 mt-1">Jaringan Stabil</p>
      </div>

      <!-- Metric 4: 24/7 Support Aktif -->
      <div class="pt-4 lg:pt-0 flex flex-col items-center">
        <div class="w-10 h-10 rounded-full bg-red-50 text-brand flex items-center justify-center text-lg mb-2">
          <i class="fa-solid fa-headset"></i>
        </div>
        <p class="text-2xl sm:text-3xl font-black text-black tracking-tight">24/7</p>
        <p class="text-xs font-semibold text-gray-500 mt-1">Support Aktif</p>
      </div>

    </div>
  </section>

</div>
@endsection
