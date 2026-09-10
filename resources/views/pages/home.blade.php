@extends('layouts.app')

@section('title', 'WiFi Banterpool - Beranda')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

        <!-- HERO SECTION -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center mb-12" id="home-main-container">

            <!-- Sisi Kiri: Text & Feature Cards -->
            <div class="lg:col-span-6 space-y-6">

                <!-- Subtitle & Title dengan Icon Sinyal WiFi di atas huruf R -->
                <div>
                    <p class="text-brand font-extrabold text-xs tracking-widest uppercase mb-2">SELAMAT DATANG DI</p>

                    <h1 class="text-4xl sm:text-6xl font-black text-black leading-none tracking-tight">
                        WiFi <br>
                        <span class="text-brand inline-flex items-baseline">
                            BANTE<span class="relative">R<i
                                    class="fa-solid fa-wifi text-3xl sm:text-4xl absolute -top-7 sm:-top-9 left-1/2 -translate-x-1/2 text-brand"></i></span>POOL
                        </span>
                    </h1>
                </div>

                <!-- Description Text -->
                <p class="text-gray-500 text-xs sm:text-sm max-w-md font-medium leading-relaxed">
                    Internet cepat, stabil, dan tanpa batas untuk semua kebutuhanmu.
                </p>

                <!-- Router Image Visual: Ditampilkan di atas 'Super Cepat' pada tampilan handphone -->
                <div class="block lg:hidden my-4 flex justify-center items-center">
                    <div class="block">
                        <img src="{{ asset('image/router.png') }}" alt="Router Banterpool"
                            class="w-full max-w-xs sm:max-w-sm h-auto object-contain mx-auto">
                    </div>
                </div>

                <!-- 3 Feature Pills Cards (Plain non-clickable info cards) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2 max-w-lg">

                    <!-- Card 1: Super Cepat -->
                    <div class="border border-gray-200 rounded-2xl p-3 bg-white flex items-center gap-3 shadow-xs">
                        <div class="w-8 h-8 rounded-xl bg-red-50 text-brand flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-bolt text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-black leading-tight">Super Cepat</p>
                            <p class="text-[9px] text-gray-400 leading-tight">Kecepatan tinggi tanpa hambatan</p>
                        </div>
                    </div>

                    <!-- Card 2: Stabil & Aman -->
                    <div class="border border-gray-200 rounded-2xl p-3 bg-white flex items-center gap-3 shadow-xs">
                        <div class="w-8 h-8 rounded-xl bg-red-50 text-brand flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-shield-halved text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-black leading-tight">Stabil & Aman</p>
                            <p class="text-[9px] text-gray-400 leading-tight">Koneksi konsisten sepanjang hari</p>
                        </div>
                    </div>

                    <!-- Card 3: Tanpa Batas -->
                    <div class="border border-gray-200 rounded-2xl p-3 bg-white flex items-center gap-3 shadow-xs">
                        <div class="w-8 h-8 rounded-xl bg-red-50 text-brand flex items-center justify-center shrink-0">
                            <i class="fa-solid fa-infinity text-sm"></i>
                        </div>
                        <div>
                            <p class="text-[11px] font-black text-black leading-tight">Tanpa Batas</p>
                            <p class="text-[9px] text-gray-400 leading-tight">Akses internet sepuasnya tanpa FUP</p>
                        </div>
                    </div>

                </div>

                <!-- CTA Button (Satu-satunya tombol yang bisa diklik di beranda) -->
                <div class="pt-2">
                    <a href="{{ route('paket') }}"
                        class="inline-flex items-center gap-2.5 bg-brand hover:bg-brand-700 text-white font-extrabold px-6 py-3 rounded-xl text-xs sm:text-sm transition shadow-lg shadow-brand/20">
                        <i class="fa-solid fa-cart-shopping"></i>
                        <span>PESAN SEKARANG</span>
                    </a>
                </div>

            </div>

            <!-- Sisi Kanan: Router Image Visual (Desktop lg+) -->
            <div class="hidden lg:flex lg:col-span-6 justify-center items-center">
                <div class="block">
                    <img src="{{ asset('image/router.png') }}" alt="Router Banterpool"
                        class="w-full max-w-lg h-auto object-contain">
                </div>
            </div>

        </div>

        <!-- STATS BAR SECTION (Plain non-clickable info cards) -->
        <div class="border border-gray-200 rounded-3xl p-6 sm:p-8 bg-white shadow-xs mt-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 divide-y md:divide-y-0 md:divide-x divide-gray-100">

                <!-- Stat 1 -->
                <div class="flex items-center justify-center gap-4 pt-4 md:pt-0">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-brand flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div>
                        <h4 class="text-2xl font-black text-black">500+</h4>
                        <p class="text-xs text-gray-400 font-medium">Pelanggan Aktif</p>
                    </div>
                </div>

                <!-- Stat 2 -->
                <div class="flex items-center justify-center gap-4 pt-4 md:pt-0">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-brand flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-gauge-high"></i>
                    </div>
                    <div>
                        <h4 class="text-2xl font-black text-black">100Mbps+</h4>
                        <p class="text-xs text-gray-400 font-medium">Kecepatan Maksimal</p>
                    </div>
                </div>

                <!-- Stat 3 -->
                <div class="flex items-center justify-center gap-4 pt-4 md:pt-0">
                    <div class="w-12 h-12 rounded-2xl bg-red-50 text-brand flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div>
                        <h4 class="text-2xl font-black text-black">99.9%</h4>
                        <p class="text-xs text-gray-400 font-medium">Jaringan Terjaga</p>
                    </div>
                </div>

            </div>
        </div>

    </div>
@endsection
