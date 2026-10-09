@extends('layouts.app')

@section('title', 'WiFi Banterpool - Berlangganan Paket Internet')

@section('content')
    <div x-data="paketBookingApp()" x-init="initApp()">

        <!-- Section Header -->
        <section class="relative text-center pt-8 pb-4 px-4 overflow-hidden">
            <div class="max-w-3xl mx-auto relative z-10">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-black tracking-tight">
                    Pilih Paket Berlangganan Terbaik Untukmu
                </h1>
                <p class="text-gray-500 text-sm sm:text-base mt-2 font-medium">
                    Koneksi cepat, stabil, dan tanpa batas.
                </p>
            </div>

            <!-- Watermark Wifi Icon -->
            <div class="absolute right-10 top-2 text-red-100 opacity-60 pointer-events-none z-0">
                <i class="fa-solid fa-wifi text-[140px]"></i>
            </div>
        </section>

        <!-- Feature Pills Bar -->
        <div class="max-w-xl mx-auto px-4 my-6">
            <div
                class="border border-gray-200 rounded-2xl py-3 px-6 bg-white flex flex-wrap justify-around items-center gap-4 shadow-sm">
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                    <span>Internet Unlimited</span>
                </div>
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                    <span>Jaringan Stabil</span>
                </div>
                <div class="flex items-center gap-2 text-brand font-bold text-sm">
                    <i class="fa-solid fa-shield-halved text-base"></i>
                    <span>Support 24/7</span>
                </div>
            </div>
        </div>

        <!-- Pricing Cards Grid (3 Pilihan Paket) -->
        <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8 items-stretch">

                <!-- 1. 20 Mbps -->
                <div x-data="{ openFeatures: true }" 
                     class="rounded-3xl bg-white border border-gray-200 shadow-md hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col justify-between group">
                    
                    <div>
                        <!-- Image Banner with Watermark & Speed Badge -->
                        <div class="relative w-full h-52 sm:h-56 overflow-hidden bg-slate-900">
                            <img src="{{ asset('image/packages/package-20mbps.jpg') }}" 
                                 alt="Paket 20 Mbps" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            
                            <!-- Ambient Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent"></div>

                            <!-- Logo Watermark Top-Left -->
                            <div class="absolute top-3 left-3 bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-xl flex items-center gap-1.5 text-white text-[11px] font-bold border border-white/10 shadow-sm">
                                <i class="fa-solid fa-wifi text-brand"></i>
                                <span>BANTER<span class="text-brand font-black">POOL</span></span>
                                <span class="text-[9px] text-red-300 font-semibold uppercase tracking-wider ml-0.5">Fiber</span>
                            </div>

                            <!-- Floating Speed Badge (Bottom Left Overlay) -->
                            <div class="absolute bottom-3 left-3 bg-gradient-to-r from-brand via-red-600 to-rose-600 text-white px-3.5 py-2 rounded-2xl shadow-xl border border-white/20">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-red-100 leading-none block mb-0.5">
                                    Kecepatan Hingga
                                </span>
                                <div class="flex items-baseline gap-1 leading-none">
                                    <span class="text-2xl sm:text-3xl font-black tracking-tight">20</span>
                                    <span class="text-xs sm:text-sm font-bold text-white/90">Mbps</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content Area -->
                        <div class="p-6">
                            <!-- Title & Speed Subtitle (Center) -->
                            <div class="text-center">
                                <h3 class="text-2xl font-black text-brand tracking-tight">Paket 20 Mbps</h3>
                                <div class="flex items-center justify-center gap-2 mt-1 text-sm font-bold">
                                    <span class="line-through text-gray-400 text-xs">15 Mbps</span>
                                    <span class="text-slate-900 font-extrabold">20 Mbps Simetris</span>
                                </div>

                                <!-- Price -->
                                <div class="mt-3">
                                    <span class="text-2xl sm:text-3xl font-black text-black tracking-tight">Rp 110.000</span>
                                    <span class="text-xs sm:text-sm font-medium text-gray-500 ml-1">/ Bulan</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Harga belum termasuk PPN 11%</p>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-5">
                                <button type="button"
                                    @click="selectPaket('Paket 20 Mbps', '20 Mbps', '110.000', false, ['Kecepatan 20 Mbps', 'Untuk Penggunaan Ringan & Keluarga Kecil', '3 - 5 Perangkat', 'Support Prioritas'])"
                                    class="w-full bg-brand hover:bg-brand-700 text-white font-extrabold py-3.5 px-4 rounded-xl text-xs sm:text-sm transition shadow-sm cursor-pointer active:scale-95 flex items-center justify-center gap-2">
                                    <span>Langganan Sekarang</span>
                                </button>
                            </div>

                            <!-- Fitur dan Benefit Accordion -->
                            <div class="pt-5 mt-5 border-t border-gray-100">
                                <button type="button" 
                                        @click="openFeatures = !openFeatures" 
                                        class="w-full flex items-center justify-between text-left select-none group/acc cursor-pointer">
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 group-hover/acc:text-brand transition">Fitur dan Benefit</span>
                                    <i class="fa-solid fa-chevron-up text-xs text-gray-500 transition-transform duration-200" 
                                       :class="openFeatures ? '' : 'rotate-180'"></i>
                                </button>

                                <ul x-show="openFeatures" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="space-y-3 pt-3.5 text-xs text-gray-700 font-medium">
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-bolt"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Kecepatan 20 Mbps</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-house-user"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Untuk Penggunaan Ringan & Keluarga Kecil</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-mobile-screen"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">3 - 5 Perangkat</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-headset"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Support Prioritas</span>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- 2. 30 Mbps (POPULER) -->
                <div x-data="{ openFeatures: true }" 
                     class="rounded-3xl bg-white border-2 border-brand shadow-xl hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col justify-between group relative lg:-translate-y-2">
                    
                    <!-- Floating POPULER Badge on Top Right of Card -->
                    <div class="absolute top-3 right-3 z-10 bg-gradient-to-r from-amber-400 to-[#e2c091] text-black text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-wider shadow-lg flex items-center gap-1 border border-white/40">
                        <i class="fa-solid fa-crown text-[10px] text-amber-800"></i> POPULER
                    </div>

                    <div>
                        <!-- Image Banner with Watermark & Speed Badge -->
                        <div class="relative w-full h-52 sm:h-56 overflow-hidden bg-slate-900">
                            <img src="{{ asset('image/packages/package-30mbps.jpg') }}" 
                                 alt="Paket 30 Mbps" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            
                            <!-- Ambient Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent"></div>

                            <!-- Logo Watermark Top-Left -->
                            <div class="absolute top-3 left-3 bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-xl flex items-center gap-1.5 text-white text-[11px] font-bold border border-white/10 shadow-sm">
                                <i class="fa-solid fa-wifi text-brand"></i>
                                <span>BANTER<span class="text-brand font-black">POOL</span></span>
                                <span class="text-[9px] text-red-300 font-semibold uppercase tracking-wider ml-0.5">Fiber</span>
                            </div>

                            <!-- Floating Speed Badge (Bottom Left Overlay) -->
                            <div class="absolute bottom-3 left-3 bg-gradient-to-r from-brand via-red-600 to-rose-600 text-white px-3.5 py-2 rounded-2xl shadow-xl border border-white/20">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-red-100 leading-none block mb-0.5">
                                    Kecepatan Hingga
                                </span>
                                <div class="flex items-baseline gap-1 leading-none">
                                    <span class="text-2xl sm:text-3xl font-black tracking-tight">30</span>
                                    <span class="text-xs sm:text-sm font-bold text-white/90">Mbps</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content Area -->
                        <div class="p-6">
                            <!-- Title & Speed Subtitle (Center) -->
                            <div class="text-center">
                                <h3 class="text-2xl font-black text-brand tracking-tight">Paket 30 Mbps</h3>
                                <div class="flex items-center justify-center gap-2 mt-1 text-sm font-bold">
                                    <span class="line-through text-gray-400 text-xs">25 Mbps</span>
                                    <span class="text-slate-900 font-extrabold">30 Mbps Simetris</span>
                                </div>

                                <!-- Price -->
                                <div class="mt-3">
                                    <span class="text-2xl sm:text-3xl font-black text-black tracking-tight">Rp 165.000</span>
                                    <span class="text-xs sm:text-sm font-medium text-gray-500 ml-1">/ Bulan</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Harga belum termasuk PPN 11%</p>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-5">
                                <button type="button"
                                    @click="selectPaket('Paket 30 Mbps', '30 Mbps', '165.000', true, ['Kecepatan 30 Mbps', 'Untuk Kebutuhan Keluarga', '5 - 7 Perangkat', 'Support Prioritas'])"
                                    class="w-full bg-brand hover:bg-brand-700 text-white font-extrabold py-3.5 px-4 rounded-xl text-xs sm:text-sm transition shadow-md cursor-pointer active:scale-95 flex items-center justify-center gap-2">
                                    <span>Langganan Sekarang</span>
                                </button>
                            </div>

                            <!-- Fitur dan Benefit Accordion -->
                            <div class="pt-5 mt-5 border-t border-gray-100">
                                <button type="button" 
                                        @click="openFeatures = !openFeatures" 
                                        class="w-full flex items-center justify-between text-left select-none group/acc cursor-pointer">
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 group-hover/acc:text-brand transition">Fitur dan Benefit</span>
                                    <i class="fa-solid fa-chevron-up text-xs text-gray-500 transition-transform duration-200" 
                                       :class="openFeatures ? '' : 'rotate-180'"></i>
                                </button>

                                <ul x-show="openFeatures" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="space-y-3 pt-3.5 text-xs text-gray-700 font-medium">
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-bolt"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Kecepatan 30 Mbps</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-users"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Untuk Kebutuhan Keluarga</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-mobile-screen"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">5 - 7 Perangkat</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-headset"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Support Prioritas</span>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- 3. 50 Mbps -->
                <div x-data="{ openFeatures: true }" 
                     class="rounded-3xl bg-white border border-gray-200 shadow-md hover:shadow-2xl transition duration-300 overflow-hidden flex flex-col justify-between group">
                    
                    <div>
                        <!-- Image Banner with Watermark & Speed Badge -->
                        <div class="relative w-full h-52 sm:h-56 overflow-hidden bg-slate-900">
                            <img src="{{ asset('image/packages/package-50mbps.jpg') }}" 
                                 alt="Paket 50 Mbps" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700">
                            
                            <!-- Ambient Gradient Overlay -->
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/20 to-transparent"></div>

                            <!-- Logo Watermark Top-Left -->
                            <div class="absolute top-3 left-3 bg-black/50 backdrop-blur-md px-2.5 py-1 rounded-xl flex items-center gap-1.5 text-white text-[11px] font-bold border border-white/10 shadow-sm">
                                <i class="fa-solid fa-wifi text-brand"></i>
                                <span>BANTER<span class="text-brand font-black">POOL</span></span>
                                <span class="text-[9px] text-red-300 font-semibold uppercase tracking-wider ml-0.5">Fiber</span>
                            </div>

                            <!-- Floating Speed Badge (Bottom Left Overlay) -->
                            <div class="absolute bottom-3 left-3 bg-gradient-to-r from-brand via-red-600 to-rose-600 text-white px-3.5 py-2 rounded-2xl shadow-xl border border-white/20">
                                <span class="text-[9px] font-bold uppercase tracking-wider text-red-100 leading-none block mb-0.5">
                                    Kecepatan Hingga
                                </span>
                                <div class="flex items-baseline gap-1 leading-none">
                                    <span class="text-2xl sm:text-3xl font-black tracking-tight">50</span>
                                    <span class="text-xs sm:text-sm font-bold text-white/90">Mbps</span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Content Area -->
                        <div class="p-6">
                            <!-- Title & Speed Subtitle (Center) -->
                            <div class="text-center">
                                <h3 class="text-2xl font-black text-brand tracking-tight">Paket 50 Mbps</h3>
                                <div class="flex items-center justify-center gap-2 mt-1 text-sm font-bold">
                                    <span class="line-through text-gray-400 text-xs">40 Mbps</span>
                                    <span class="text-slate-900 font-extrabold">50 Mbps Simetris</span>
                                </div>

                                <!-- Price -->
                                <div class="mt-3">
                                    <span class="text-2xl sm:text-3xl font-black text-black tracking-tight">Rp 220.000</span>
                                    <span class="text-xs sm:text-sm font-medium text-gray-500 ml-1">/ Bulan</span>
                                </div>
                                <p class="text-[11px] text-gray-500 mt-1">Harga belum termasuk PPN 11%</p>
                            </div>

                            <!-- Action Button -->
                            <div class="mt-5">
                                <button type="button"
                                    @click="selectPaket('Paket 50 Mbps', '50 Mbps', '220.000', false, ['Kecepatan 50 Mbps', 'Streaming & Gaming Lancar', '7 - 10 Perangkat', 'Support 24/7'])"
                                    class="w-full bg-brand hover:bg-brand-700 text-white font-extrabold py-3.5 px-4 rounded-xl text-xs sm:text-sm transition shadow-sm cursor-pointer active:scale-95 flex items-center justify-center gap-2">
                                    <span>Langganan Sekarang</span>
                                </button>
                            </div>

                            <!-- Fitur dan Benefit Accordion -->
                            <div class="pt-5 mt-5 border-t border-gray-100">
                                <button type="button" 
                                        @click="openFeatures = !openFeatures" 
                                        class="w-full flex items-center justify-between text-left select-none group/acc cursor-pointer">
                                    <span class="font-extrabold text-xs sm:text-sm text-slate-900 group-hover/acc:text-brand transition">Fitur dan Benefit</span>
                                    <i class="fa-solid fa-chevron-up text-xs text-gray-500 transition-transform duration-200" 
                                       :class="openFeatures ? '' : 'rotate-180'"></i>
                                </button>

                                <ul x-show="openFeatures" 
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="space-y-3 pt-3.5 text-xs text-gray-700 font-medium">
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-bolt"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Kecepatan 50 Mbps</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-gamepad"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Streaming & Gaming Lancar</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-mobile-screen"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">7 - 10 Perangkat</span>
                                    </li>
                                    <li class="flex items-center gap-3">
                                        <div class="w-7 h-7 rounded-lg bg-red-50 text-brand flex items-center justify-center shrink-0 text-xs">
                                            <i class="fa-solid fa-headset"></i>
                                        </div>
                                        <span class="font-semibold text-slate-800">Support 24/7</span>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- MODAL POPUP PEMILIHAN PAKET -->
        <div x-show="openModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            style="display: none;">

            <div @click.away="openModal = false"
                class="bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto">

                <!-- Tombol Close (X) -->
                <button type="button" @click="openModal = false"
                    class="absolute top-6 right-6 text-gray-800 hover:text-red-600 text-2xl font-bold">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">

                    <!-- Sisi Kiri: Detail Paket Yang Dipilih -->
                    <div class="md:col-span-4 flex flex-col justify-between border-r border-gray-100 pr-0 md:pr-6">
                        <div>
                            <!-- Badge POPULER jika paket populer -->
                            <template x-if="selectedPaket.isPopular">
                                <div
                                    class="inline-block bg-[#e2c091] text-black text-[10px] font-black px-3 py-1 rounded-md uppercase mb-2">
                                    POPULER
                                </div>
                            </template>

                            <div class="flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-wifi text-brand text-xl"></i>
                                <h3 class="text-2xl font-black text-black" x-text="selectedPaket.name"></h3>
                            </div>

                            <div class="text-xl font-black text-brand mb-6">
                                <span class="text-black text-base font-normal">Rp</span><span
                                    x-text="selectedPaket.price"></span> <span
                                    class="text-xs font-normal text-gray-500">/bulan</span>
                            </div>

                            <!-- List Fitur Dinamis -->
                            <ul class="space-y-3 text-xs font-semibold text-gray-800 mb-6">
                                <template x-for="feature in selectedPaket.features" :key="feature">
                                    <li class="flex items-center gap-2">
                                        <i class="fa-solid fa-circle-check text-brand text-sm"></i>
                                        <span x-text="feature"></span>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <!-- Gambar Router di Bawah Detail (Sudah Menggunakan Path Folder image/router.png) -->
                        <div class="text-center mt-4">
                            <img src="{{ asset('image/router.png') }}" alt="Router Banterpool" class="w-48 mx-auto">
                        </div>
                    </div>

                    <!-- Sisi Kanan: Form Data Berlangganan -->
                    <div class="md:col-span-8">
                        <h3 class="text-lg font-black text-black">Lengkapi Data untuk Berlangganan</h3>
                        <p class="text-xs text-gray-500 mb-6">Silahkan lengkapi data di bawah untuk melanjutkan.</p>

                        <form action="{{ route('checkout') }}" method="GET" class="space-y-4">

                            <!-- Data Pelanggan -->
                            <div>
                                <label class="block text-xs font-bold text-black mb-2">Data Pelanggan</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">Nama Lengkap</label>
                                        <input type="text" name="name" value="{{ auth()->check() ? auth()->user()->name : '' }}" placeholder="Masukan nama lengkap" required
                                            class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">Nomor HP</label>
                                        <input type="tel" name="phone" value="{{ auth()->check() ? (auth()->user()->phone ?? '') : '' }}" placeholder="08xxxxxxxxxx" required
                                            class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand">
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <label class="text-[10px] font-bold text-gray-600 block mb-1">Email</label>
                                    <input type="email" name="email" value="{{ auth()->check() ? auth()->user()->email : '' }}" placeholder="emailanda@gmail.com" required
                                        class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand">
                                </div>

                                <!-- No. KTP & Tempat Tanggal Lahir (Formulir Pendaftaran Berlangganan) -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">
                                            <i class="fa-regular fa-id-card text-brand mr-1"></i>No. KTP (NIK)
                                        </label>
                                        <input type="text" name="id_card_number" maxlength="16" value="{{ auth()->check() ? (auth()->user()->id_card_number ?? '') : '' }}"
                                            placeholder="Masukan 16 digit No. KTP" required
                                            class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">
                                            <i class="fa-regular fa-calendar text-brand mr-1"></i>Tempat, Tanggal Lahir
                                        </label>
                                        <div class="grid grid-cols-2 gap-2">
                                            <input type="text" name="birth_place" value="{{ auth()->check() ? (auth()->user()->birth_place ?? '') : '' }}" placeholder="Tempat Lahir (Kota)" required
                                                class="w-full px-2.5 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand">
                                            <input type="date" name="birth_date" value="{{ auth()->check() ? (auth()->user()->birth_date ?? '') : '' }}" required
                                                class="w-full px-2 py-2 border border-gray-400 rounded-lg text-xs text-gray-600 focus:ring-brand focus:border-brand">
                                        </div>
                                    </div>
                                </div>

                                <!-- Input Alamat Lengkap & Maps Penanda Titik Rumah -->
                                <div class="mt-3 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <label class="text-[10px] font-bold text-gray-600 block">Alamat Lengkap
                                            Pemasangan</label>
                                        <span class="text-[10px] text-gray-400 font-normal">Nama Jalan, No. Rumah, RT/RW,
                                            Patokan</span>
                                    </div>
                                    <textarea name="address" x-model="address" rows="2"
                                        placeholder="Contoh: Jl. Raya Pernasidi No. 45, RT 02/RW 03, Kec. Cilongok, Kab. Banyumas (Samping Toko Berkah)"
                                        required
                                        class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs focus:ring-brand focus:border-brand resize-none"></textarea>

                                    <!-- Box Peta Interaktif Titik Rumah -->
                                    <div class="border border-gray-300 rounded-xl p-3 bg-gray-50/70 space-y-2">
                                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                            <div>
                                                <div class="flex items-center gap-1.5 text-xs font-bold text-black">
                                                    <i class="fa-solid fa-map-location-dot text-brand text-sm"></i>
                                                    <span>Tandai Titik Rumah di Peta (Cilongok, Kab. Banyumas)</span>
                                                </div>
                                                <p class="text-[10px] text-gray-500">Geser pin merah tepat di atas
                                                    atap/lokasi rumah Anda di area Cilongok dan sekitarnya.</p>
                                            </div>

                                            <!-- Tombol GPS -->
                                            <button type="button" @click="detectCurrentLocation()"
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-[11px] font-bold bg-white border border-gray-300 hover:border-brand text-gray-700 hover:text-brand rounded-lg shadow-sm transition shrink-0"
                                                :disabled="isLocating">
                                                <template x-if="isLocating">
                                                    <i class="fa-solid fa-circle-notch fa-spin text-brand"></i>
                                                </template>
                                                <template x-if="!isLocating">
                                                    <i class="fa-solid fa-crosshairs text-brand"></i>
                                                </template>
                                                <span
                                                    x-text="isLocating ? 'Mendeteksi GPS...' : 'Gunakan Lokasi Saya'"></span>
                                            </button>
                                        </div>

                                        <!-- Input Pencarian Lokasi -->
                                        <div class="flex items-center gap-1.5">
                                            <div class="relative flex-1">
                                                <i
                                                    class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-gray-400 text-xs"></i>
                                                <input type="text" x-model="searchQuery"
                                                    @keydown.enter.prevent="searchAddress()"
                                                    placeholder="Cari jalan, desa di Cilongok (contoh: Pernasidi, Panembangan, Jatisaba)..."
                                                    class="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-brand focus:border-brand bg-white">
                                            </div>
                                            <button type="button" @click="searchAddress()"
                                                class="px-3.5 py-1.5 bg-brand hover:bg-brand-700 text-white text-xs font-bold rounded-lg transition shrink-0 flex items-center gap-1"
                                                :disabled="isSearching">
                                                <template x-if="isSearching">
                                                    <i class="fa-solid fa-spinner fa-spin"></i>
                                                </template>
                                                <span x-text="isSearching ? 'Mencari...' : 'Cari'"></span>
                                            </button>
                                        </div>

                                        <!-- Quick Jump Shortcut Desa Cilongok -->
                                        <div
                                            class="flex items-center gap-1 overflow-x-auto pb-1 text-[10px] text-gray-500">
                                            <span class="font-bold text-gray-700 shrink-0 text-[10px] mr-1"><i
                                                    class="fa-solid fa-location-arrow text-brand mr-1"></i>Pilih
                                                Desa:</span>
                                            <button type="button"
                                                @click="goToLocation(-7.413200, 109.138800, 'Pernasidi (Pusat Cilongok)')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Pernasidi
                                                (Pusat)</button>
                                            <button type="button"
                                                @click="goToLocation(-7.401000, 109.146500, 'Panembangan, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Panembangan</button>
                                            <button type="button"
                                                @click="goToLocation(-7.430500, 109.148000, 'Jatisaba, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Jatisaba</button>
                                            <button type="button"
                                                @click="goToLocation(-7.423500, 109.127000, 'Pageraji, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Pageraji</button>
                                            <button type="button"
                                                @click="goToLocation(-7.391000, 109.151500, 'Karanglo, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Karanglo</button>
                                            <button type="button"
                                                @click="goToLocation(-7.404500, 109.157000, 'Cikidang, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Cikidang</button>
                                            <button type="button"
                                                @click="goToLocation(-7.435000, 109.139000, 'Pejogol, Cilongok')"
                                                class="px-2 py-0.5 bg-white hover:bg-red-50 hover:text-brand border border-gray-200 rounded-md shrink-0 transition font-medium">Pejogol</button>
                                        </div>

                                        <!-- Wadah Peta Google Maps Titik Rumah -->
                                        <div
                                            class="relative w-full h-48 sm:h-56 rounded-xl overflow-hidden border border-gray-300 shadow-sm z-0">
                                            <div id="map-house-picker" class="w-full h-full"></div>
                                        </div>

                                        <!-- Status Koordinat & Alamat Terdeteksi -->
                                        <div class="space-y-1.5 pt-1">
                                            <div class="flex items-center justify-between text-[11px] text-gray-600">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-bold text-gray-700">Titik Koordinat:</span>
                                                    <span
                                                        class="font-mono text-brand font-bold bg-white px-1.5 py-0.5 rounded border border-gray-200"
                                                        x-text="latitude + ', ' + longitude"></span>
                                                </div>
                                                <span class="text-[10px] text-gray-400 italic">Klik peta atau geser pin
                                                    merah</span>
                                            </div>

                                            <template x-if="detectedAddress">
                                                <div
                                                    class="flex items-center justify-between gap-2 p-2 bg-white rounded-lg border border-brand/30 text-[11px]">
                                                    <div class="truncate text-gray-700">
                                                        <span class="font-bold text-brand">Terdeteksi:</span>
                                                        <span x-text="detectedAddress"></span>
                                                    </div>
                                                    <button type="button" @click="useDetectedAddress()"
                                                        class="shrink-0 text-[10px] bg-brand text-white font-bold px-2 py-1 rounded hover:bg-brand-700 transition">
                                                        Gunakan Alamat Ini
                                                    </button>
                                                </div>
                                            </template>
                                        </div>

                                        <!-- Hidden Inputs -->
                                        <input type="hidden" name="latitude" :value="latitude">
                                        <input type="hidden" name="longitude" :value="longitude">
                                        <input type="hidden" name="package_name" :value="selectedPaket.name">
                                        <input type="hidden" name="package_speed" :value="selectedPaket.speed">
                                        <input type="hidden" name="package_price" :value="selectedPaket.price">
                                    </div>
                                </div>
                            </div>

                            <!-- Pilih Metode Pemasangan -->
                            <div>
                                <label class="block text-xs font-bold text-black mb-2">Pilih Metode Pemasangan</label>
                                <div class="border border-brand rounded-xl p-3 bg-white flex items-start gap-3">
                                    <div class="w-4 h-4 rounded-full border-4 border-brand bg-white mt-0.5"></div>
                                    <div>
                                        <h4 class="text-xs font-bold text-black">Pemasangan Baru</h4>
                                        <p class="text-[10px] text-gray-500">Untuk pelanggan baru/pemasangan pertama kali
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Jadwal Pemasangan -->
                            <div>
                                <label class="block text-xs font-bold text-black mb-2">Jadwal Pemasangan</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">Pilih Tanggal</label>
                                        <input type="date" name="installation_date" required
                                            class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs text-gray-600 focus:ring-brand focus:border-brand">
                                    </div>
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-600 block mb-1">Pilih Waktu</label>
                                        <select name="installation_time" required
                                            class="w-full px-3 py-2 border border-gray-400 rounded-lg text-xs text-gray-600 focus:ring-brand focus:border-brand">
                                            <option value="">Pilih waktu</option>
                                            <option value="pagi">Pagi (08:00 - 12:00 WIB)</option>
                                            <option value="siang">Siang (13:00 - 16:00 WIB)</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Catatan Tim -->
                                <div class="border border-gray-300 rounded-lg p-3 text-center text-xs text-gray-600 mt-3">
                                    Tim kami akan menghubungi Anda untuk konfirmasi pemasangan.
                                </div>
                            </div>

                            <!-- Tombol Batal & Lanjutkan -->
                            <div class="flex items-center justify-end gap-3 pt-2">
                                <button type="button" @click="openModal = false"
                                    class="w-1/2 border border-gray-400 text-black font-bold py-2.5 rounded-xl text-xs hover:bg-gray-100 transition">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="w-1/2 bg-brand hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-1 transition">
                                    Lanjutkan <i class="fa-solid fa-arrow-right text-xs"></i>
                                </button>
                            </div>

                        </form>
                    </div>

                </div>

            </div>
        </div>

        <!-- Bottom Help Section -->
        <section class="text-center my-10 px-4">
            <h3 class="text-base font-extrabold text-black">Perlu bantuan memilih paket?</h3>
            <p class="text-xs text-gray-500 mt-1">Hubungi tim kami untuk rekomendasi paket terbaik sesuai kebutuhanmu.</p>
            <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20ingin%20konsultasi%20paket%20internet%20WiFi." target="_blank"
                class="inline-flex items-center gap-1.5 text-brand font-bold text-xs mt-3 hover:underline">
                Hubungi Kami <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </section>

    </div>
@endsection

@push('styles')
    <style>
        #map-house-picker {
            width: 100%;
            height: 100%;
        }
        /* Hilangkan watermark & overlay abu-abu 'For development purposes only' Google Maps */
        .gm-style-pbc,
        .gm-style-moc {
            display: none !important;
        }
        .gm-style div[style*="background-color: rgba(0, 0, 0"] {
            background-color: transparent !important;
        }
        .gm-style div[style*="z-index: 1000001"] {
            display: none !important;
        }
        .gm-err-container,
        .gm-err-content {
            display: none !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Cegah popup alert error bawaan Google Maps jika belum ada kartu kredit
        const _origAlert = window.alert;
        window.alert = function(msg) {
            if (typeof msg === 'string' && (msg.includes("Google Maps") || msg.includes("development purposes") || msg.includes("billing"))) {
                console.warn("Google Maps notice suppressed:", msg);
                return;
            }
            _origAlert.apply(window, arguments);
        };
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google.maps_key') }}&libraries=places,geometry"></script>
    <script>
        function paketBookingApp() {
            return {
                openModal: false,
                selectedPaket: {
                    name: 'Paket 30 Mbps',
                    speed: '30 Mbps',
                    price: '165.000',
                    isPopular: true,
                    features: [
                        'Kecepatan 30 Mbps',
                        'Untuk Kebutuhan Keluarga',
                        '5 - 7 Perangkat',
                        'Support Prioritas'
                    ]
                },
                address: @json(auth()->check() && auth()->user()->address ? auth()->user()->address : ''),
                latitude: '-7.413200',
                longitude: '109.138800',
                searchQuery: '',
                detectedAddress: '',
                isSearching: false,
                isLocating: false,
                map: null,
                marker: null,
                infoWindow: null,

                initApp() {
                    this.$watch('openModal', (isOpen) => {
                        if (isOpen) {
                            setTimeout(() => {
                                this.initMap();
                                if (this.map && window.google && window.google.maps) {
                                    google.maps.event.trigger(this.map, 'resize');
                                    const lat = parseFloat(this.latitude) || -7.413200;
                                    const lng = parseFloat(this.longitude) || 109.138800;
                                    this.map.setCenter({ lat, lng });
                                }
                            }, 350);
                        }
                    });
                },

                selectPaket(name, speed, price, isPopular, features) {
                    this.selectedPaket = {
                        name,
                        speed,
                        price,
                        isPopular,
                        features
                    };
                    this.openModal = true;
                },

                initMap() {
                    const container = document.getElementById('map-house-picker');
                    if (!container) return;

                    if (!window.google || !window.google.maps) {
                        window.addEventListener('load', () => this.initMap());
                        return;
                    }

                    const lat = parseFloat(this.latitude) || -7.413200;
                    const lng = parseFloat(this.longitude) || 109.138800;

                    if (this.map) {
                        google.maps.event.trigger(this.map, 'resize');
                        this.map.setCenter({ lat, lng });
                        if (this.marker) {
                            this.marker.setPosition({ lat, lng });
                        }
                        return;
                    }

                    // Custom Red House Marker Icon for Google Maps
                    const houseIcon = {
                        url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                            <svg xmlns="http://www.w3.org/2000/svg" width="38" height="46" viewBox="0 0 38 46">
                                <filter id="houseShadow" x="-20%" y="-10%" width="140%" height="140%">
                                    <feDropShadow dx="0" dy="3" stdDeviation="3" flood-color="#000000" flood-opacity="0.35"/>
                                </filter>
                                <path d="M19 0 C8.5 0 0 8.5 0 19 C0 31 19 46 19 46 C19 46 38 31 38 19 C38 8.5 29.5 0 19 0 Z" fill="#DC2626" filter="url(#houseShadow)" stroke="#FFFFFF" stroke-width="2"/>
                                <path d="M19 7 L10 15 L12.5 15 L12.5 23 L16.5 23 L16.5 18 L21.5 18 L21.5 23 L25.5 23 L25.5 15 L28 15 Z" fill="#FFFFFF"/>
                            </svg>
                        `),
                        scaledSize: new google.maps.Size(38, 46),
                        anchor: new google.maps.Point(19, 46)
                    };

                    this.map = new google.maps.Map(container, {
                        center: { lat, lng },
                        zoom: 16,
                        mapTypeId: google.maps.MapTypeId.ROADMAP,
                        mapTypeControl: true,
                        mapTypeControlOptions: {
                            style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
                            position: google.maps.ControlPosition.TOP_RIGHT,
                        },
                        streetViewControl: false,
                        fullscreenControl: true,
                        zoomControl: true,
                    });

                    this.marker = new google.maps.Marker({
                        position: { lat, lng },
                        map: this.map,
                        draggable: true,
                        title: 'Titik Rumah Anda',
                        icon: houseIcon,
                        animation: google.maps.Animation.DROP
                    });

                    this.infoWindow = new google.maps.InfoWindow({
                        content: '<div style="font-family:\'Poppins\',sans-serif; text-align:center; font-size:11px; line-height:1.3; padding:2px;"><b style="color:#0f172a;">Titik Rumah Anda (Cilongok)</b><br><span style="color:#64748b;">Geser pin ini tepat ke atas atap rumah Anda</span></div>'
                    });
                    this.infoWindow.open(this.map, this.marker);

                    this.marker.addListener('dragend', (e) => {
                        const newLat = e.latLng.lat();
                        const newLng = e.latLng.lng();
                        this.updateCoords(newLat, newLng, true);
                    });

                    this.map.addListener('click', (e) => {
                        const newLat = e.latLng.lat();
                        const newLng = e.latLng.lng();
                        this.marker.setPosition({ lat: newLat, lng: newLng });
                        this.updateCoords(newLat, newLng, true);
                    });
                },

                goToLocation(lat, lng, label) {
                    this.updateCoords(lat, lng, true);
                    if (this.map && this.marker) {
                        const pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
                        this.marker.setPosition(pos);
                        this.map.panTo(pos);
                        this.map.setZoom(16);
                        if (this.infoWindow) {
                            this.infoWindow.open(this.map, this.marker);
                        }
                    }
                },

                updateCoords(lat, lng, fetchReverse = true) {
                    this.latitude = typeof lat === 'number' ? lat.toFixed(6) : parseFloat(lat).toFixed(6);
                    this.longitude = typeof lng === 'number' ? lng.toFixed(6) : parseFloat(lng).toFixed(6);

                    if (fetchReverse) {
                        if (window.google && window.google.maps && window.google.maps.Geocoder) {
                            const geocoder = new google.maps.Geocoder();
                            geocoder.geocode({ location: { lat: parseFloat(lat), lng: parseFloat(lng) } }, (results, status) => {
                                if (status === 'OK' && results && results[0]) {
                                    this.detectedAddress = results[0].formatted_address;
                                    if (!this.address || this.address.trim() === '') {
                                        this.address = results[0].formatted_address;
                                    }
                                } else {
                                    this.fetchReverseNominatim(lat, lng);
                                }
                            });
                        } else {
                            this.fetchReverseNominatim(lat, lng);
                        }
                    }
                },

                fetchReverseNominatim(lat, lng) {
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
                        .then(r => r.json())
                        .then(data => {
                            if (data && data.display_name) {
                                this.detectedAddress = data.display_name;
                                if (!this.address || this.address.trim() === '') {
                                    this.address = data.display_name;
                                }
                            }
                        })
                        .catch(() => {});
                },

                detectCurrentLocation() {
                    if (!navigator.geolocation) {
                        alert('Browser Anda tidak mendukung deteksi lokasi (Geolocation).');
                        return;
                    }
                    this.isLocating = true;
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            const lat = pos.coords.latitude;
                            const lng = pos.coords.longitude;
                            this.updateCoords(lat, lng, true);

                            if (this.map && this.marker) {
                                const latLng = { lat, lng };
                                this.marker.setPosition(latLng);
                                this.map.panTo(latLng);
                                this.map.setZoom(17);
                                if (this.infoWindow) {
                                    this.infoWindow.open(this.map, this.marker);
                                }
                            }
                            this.isLocating = false;
                        },
                        (err) => {
                            this.isLocating = false;
                            console.warn('Geolocation error:', err);
                            alert('Tidak dapat mendeteksi lokasi GPS secara otomatis. Silakan cari lokasi atau geser pin merah pada peta.');
                        }, {
                            enableHighAccuracy: true,
                            timeout: 10000
                        }
                    );
                },

                searchAddress() {
                    if (!this.searchQuery || !this.searchQuery.trim()) return;
                    this.isSearching = true;
                    let query = this.searchQuery.trim();
                    if (!query.toLowerCase().includes('cilongok') && !query.toLowerCase().includes('banyumas')) {
                        query += ', Cilongok, Banyumas';
                    }

                    if (window.google && window.google.maps && window.google.maps.Geocoder) {
                        const geocoder = new google.maps.Geocoder();
                        geocoder.geocode({
                            address: query,
                            componentRestrictions: { country: 'ID' }
                        }, (results, status) => {
                            if (status === 'OK' && results && results.length > 0) {
                                this.isSearching = false;
                                const loc = results[0].geometry.location;
                                const lat = loc.lat();
                                const lng = loc.lng();
                                this.updateCoords(lat, lng, false);
                                this.detectedAddress = results[0].formatted_address;
                                if (!this.address || this.address.trim() === '') {
                                    this.address = results[0].formatted_address;
                                }

                                if (this.map && this.marker) {
                                    const latLng = { lat, lng };
                                    this.marker.setPosition(latLng);
                                    this.map.panTo(latLng);
                                    this.map.setZoom(16);
                                    if (this.infoWindow) {
                                        this.infoWindow.open(this.map, this.marker);
                                    }
                                }
                                return;
                            }
                            // Fallback jika Google Geocoder tidak menemukan
                            this.searchNominatim(query);
                        });
                    } else {
                        this.searchNominatim(query);
                    }
                },

                searchNominatim(query) {
                    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=id&limit=1`)
                        .then(r => r.json())
                        .then(data => {
                            if (!data || data.length === 0) {
                                return fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.searchQuery)}&countrycodes=id&limit=1`).then(r => r.json());
                            }
                            return data;
                        })
                        .then(data => {
                            this.isSearching = false;
                            if (data && data.length > 0) {
                                const item = data[0];
                                const lat = parseFloat(item.lat);
                                const lng = parseFloat(item.lon);
                                this.updateCoords(lat, lng, false);
                                this.detectedAddress = item.display_name;
                                if (!this.address || this.address.trim() === '') {
                                    this.address = item.display_name;
                                }

                                if (this.map && this.marker) {
                                    const latLng = { lat, lng };
                                    this.marker.setPosition(latLng);
                                    this.map.panTo(latLng);
                                    this.map.setZoom(16);
                                    if (this.infoWindow) {
                                        this.infoWindow.open(this.map, this.marker);
                                    }
                                }
                            } else {
                                alert('Lokasi tidak ditemukan. Coba ketikkan nama jalan atau desa di Cilongok, Banyumas.');
                            }
                        })
                        .catch(() => {
                            this.isSearching = false;
                            alert('Gagal mencari lokasi. Silakan periksa koneksi internet Anda.');
                        });
                },

                useDetectedAddress() {
                    if (this.detectedAddress) {
                        this.address = this.detectedAddress;
                    }
                }
            };
        }
    </script>
@endpush
