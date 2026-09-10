@extends('layouts.app')

@section('title', 'WiFi Banterpool - Pilih Paket Internet')

@section('content')
<!-- Section Header -->
<section class="relative text-center pt-8 pb-4 px-4 overflow-hidden">
  <div class="max-w-3xl mx-auto relative z-10">
    <h1 class="text-3xl sm:text-4xl font-extrabold text-black tracking-tight">
      Pilih Paket Internet Terbaik Untukmu
    </h1>
    <p class="text-gray-500 text-sm sm:text-base mt-2 font-medium">
      Koneksi cepat, stabil, dan tanpa batas.
    </p>
  </div>

  <!-- Watermark Wifi Icon (Sisi Kanan) -->
  <div class="absolute right-10 top-2 text-red-100 opacity-60 pointer-events-none z-0">
    <i class="fa-solid fa-wifi text-[140px]"></i>
  </div>
</section>

<!-- Feature Pills Bar -->
<div class="max-w-xl mx-auto px-4 my-6">
  <div class="border border-gray-200 rounded-2xl py-3 px-6 bg-white flex flex-wrap justify-around items-center gap-4 shadow-sm">
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

<!-- Pricing Cards Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-stretch">

    @forelse($packages as $package)
      @php
        $isPopular = strtolower($package->name) === '20 mbps' || $package->is_featured;
      @endphp

      <div class="relative rounded-2xl p-6 transition-all duration-200 flex flex-col justify-between border
                  {{ $isPopular ? 'bg-brand text-white border-brand shadow-xl scale-[1.02]' : 'bg-white text-gray-800 border-gray-900 shadow-sm' }}">

        <!-- Badge Populer -->
        @if($isPopular)
          <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-[#e2c091] text-black text-[10px] font-black px-4 py-1 rounded-md tracking-wider uppercase">
            POPULER
          </div>
        @endif

        <div>
          <!-- Header Kartu -->
          <div class="mb-6 text-center">
            <i class="fa-solid fa-wifi text-2xl {{ $isPopular ? 'text-white' : 'text-black' }}"></i>
            <h3 class="text-2xl font-black mt-2 {{ $isPopular ? 'text-white' : 'text-black' }}">
              {{ $package->name }}
            </h3>
            <div class="text-lg font-extrabold mt-3">
              <span>Rp</span>
              <span class="text-2xl {{ $isPopular ? 'text-white' : 'text-brand' }}">
                {{ number_format($package->price, 0, ',', '.') }}
              </span>
              <span class="text-xs font-normal {{ $isPopular ? 'text-white/80' : 'text-gray-500' }}">/bulan</span>
            </div>
          </div>

          <!-- Daftar Fitur -->
          <ul class="space-y-3.5 text-xs font-semibold mb-8">
            <li class="flex items-center gap-2.5">
              <i class="fa-solid fa-circle-check text-sm {{ $isPopular ? 'text-white' : 'text-brand' }}"></i>
              <span>Kecepatan {{ $package->speed }}</span>
            </li>
            @if($package->description)
              @foreach(explode(',', $package->description) as $desc)
                <li class="flex items-center gap-2.5">
                  <i class="fa-solid fa-circle-check text-sm {{ $isPopular ? 'text-white' : 'text-brand' }}"></i>
                  <span>{{ trim($desc) }}</span>
                </li>
              @endforeach
            @endif
          </ul>
        </div>

        <!-- Tombol Aksi -->
        <div>
          @auth
            <a href="#" class="w-full block text-center py-3 rounded-xl font-bold text-sm transition-colors
                              {{ $isPopular ? 'bg-[#e2c091] text-black hover:bg-[#d6b280]' : 'border border-brand text-brand hover:bg-brand hover:text-white' }}">
              Pilih Paket
            </a>
          @else
            <a href="{{ route('login') }}" class="w-full block text-center py-3 rounded-xl font-bold text-sm transition-colors
                              {{ $isPopular ? 'bg-[#e2c091] text-black hover:bg-[#d6b280]' : 'border border-brand text-brand hover:bg-brand hover:text-white' }}">
              Pilih Paket
            </a>
          @endauth
        </div>

      </div>
    @empty
      <!-- Default Static Cards (Jika DB Kosong) -->
      @include('pages.partials.static-packages')
    @endforelse

  </div>
</section>

<!-- Bottom Help Section -->
<section class="text-center my-10 px-4">
  <h3 class="text-base font-extrabold text-black">Perlu bantuan memilih paket?</h3>
  <p class="text-xs text-gray-500 mt-1">Hubungi tim kami untuk rekomendasi paket terbaik sesuai kebutuhanmu.</p>
  <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20ingin%20konsultasi%20paket%20internet%20WiFi." target="_blank" class="inline-flex items-center gap-1.5 text-brand font-bold text-xs mt-3 hover:underline">
    Hubungi Kami <i class="fa-solid fa-arrow-right text-[10px]"></i>
  </a>
</section>
@endsection
