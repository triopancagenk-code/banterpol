@extends('layouts.app')

@section('title', 'WiFi Banterpool - Pembayaran Tagihan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8"
     x-data="{
        step: 1,
        paymentMethod: 'transfer',
        selectedBank: 'bca',
        selectedEwallet: 'gopay',
        selectedVA: 'bca',
        selectedMinimarket: 'alfamart',

        // Timer State (24 Jam)
        totalSeconds: 23 * 3600 + 59 * 60 + 59,
        hours: '23',
        minutes: '59',
        seconds: '59',

        // Toast Feedback
        toast: {
            show: false,
            message: '',
            timeout: null,
            trigger(msg) {
                this.message = msg;
                this.show = true;
                clearTimeout(this.timeout);
                this.timeout = setTimeout(() => { this.show = false; }, 2500);
            }
        },

        // Payment Info Data
        banks: {
            'bca': { name: 'BCA (Bank Central Asia)', norek: '8271 9028 1928 001', owner: 'PT. BANTERPOOL TELEKOMUNIKASI INDONESIA', code: '014' },
            'mandiri': { name: 'Bank Mandiri', norek: '1370 0029 8812 345', owner: 'PT. BANTERPOOL TELEKOMUNIKASI INDONESIA', code: '008' },
            'bni': { name: 'Bank BNI', norek: '0988 7766 5544 332', owner: 'PT. BANTERPOOL TELEKOMUNIKASI INDONESIA', code: '009' },
            'bri': { name: 'Bank BRI', norek: '0123 0100 8899 501', owner: 'PT. BANTERPOOL TELEKOMUNIKASI INDONESIA', code: '002' }
        },
        virtualAccounts: {
            'bca': { name: 'BCA Virtual Account', va: '8800 1209 8876 221', owner: 'BANTERPOOL - {{ auth()->user()->name ?? 'Pelanggan' }}' },
            'mandiri': { name: 'Mandiri Livin VA', va: '8900 9812 3345 678', owner: 'BANTERPOOL - {{ auth()->user()->name ?? 'Pelanggan' }}' },
            'bni': { name: 'BNI Virtual Account', va: '8808 1199 4433 221', owner: 'BANTERPOOL - {{ auth()->user()->name ?? 'Pelanggan' }}' },
            'bri': { name: 'BRI BRIVA', va: '7701 5599 8822 110', owner: 'BANTERPOOL - {{ auth()->user()->name ?? 'Pelanggan' }}' }
        },
        ewallets: {
            'gopay': { name: 'GoPay', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL' },
            'ovo': { name: 'OVO', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL' },
            'dana': { name: 'DANA', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL' },
            'shopeepay': { name: 'ShopeePay', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL' }
        },
        minimarkets: {
            'alfamart': { name: 'Alfamart / Alfamidi', code: 'ALFA-BTR-{{ substr(str_replace("-", "", $billData["invoice"]), -6) }}', instruction: 'Sebutkan pembayaran internet BANTERPOOL kepada kasir Alfamart.' },
            'indomaret': { name: 'Indomaret / Ceriamart', code: 'INDO-BTR-{{ substr(str_replace("-", "", $billData["invoice"]), -6) }}', instruction: 'Sebutkan kode bayar BANTERPOOL kepada kasir Indomaret.' }
        },

        init() {
            setInterval(() => {
                if (this.totalSeconds > 0) {
                    this.totalSeconds--;
                    let h = Math.floor(this.totalSeconds / 3600);
                    let m = Math.floor((this.totalSeconds % 3600) / 60);
                    let s = this.totalSeconds % 60;
                    this.hours = h < 10 ? '0' + h : h;
                    this.minutes = m < 10 ? '0' + m : m;
                    this.seconds = s < 10 ? '0' + s : s;
                }
            }, 1000);
        },

        copyText(text, label) {
            navigator.clipboard.writeText(text);
            this.toast.trigger(label + ' berhasil disalin!');
        }
     }">

  <!-- Toast Notification -->
  <div x-show="toast.show"
       x-transition:enter="transition ease-out duration-300"
       x-transition:enter-start="opacity-0 translate-y-4"
       x-transition:enter-end="opacity-100 translate-y-0"
       x-transition:leave="transition ease-in duration-200"
       x-transition:leave-start="opacity-100 translate-y-0"
       x-transition:leave-end="opacity-0 translate-y-4"
       class="fixed bottom-6 right-6 z-50 bg-gray-900 text-white text-xs font-semibold px-4 py-3 rounded-xl shadow-2xl flex items-center gap-2 border border-gray-700"
       style="display: none;">
    <i class="fa-solid fa-circle-check text-emerald-400 text-sm"></i>
    <span x-text="toast.message"></span>
  </div>

  <!-- BREADCRUMB -->
  <nav class="flex items-center gap-2 text-xs text-gray-500 mb-4 font-medium" aria-label="Breadcrumb">
    <a href="{{ route('home') }}" class="text-brand hover:underline flex items-center gap-1.5 font-semibold">
      <i class="fa-solid fa-house text-brand text-xs"></i>
      <span>Beranda</span>
    </a>
    <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
    <a href="{{ route('tagihan') }}" class="hover:text-black">Tagihan</a>
    <i class="fa-solid fa-chevron-right text-[10px] text-gray-400"></i>
    <span class="text-brand font-bold">Pembayaran</span>
  </nav>

  <!-- TITLE & SUBTITLE -->
  <div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-black text-black">Pembayaran Tagihan</h1>
    <p class="text-xs sm:text-sm text-gray-500 mt-1">Selesaikan pembayaran Anda sebelum waktu berakhir</p>
  </div>

  <!-- Stepper Indicator -->
  <div class="flex items-center justify-between max-w-2xl mb-10 text-xs">
    
    <!-- Step 1: Konfirmasi -->
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs shrink-0">
        <template x-if="step === 1">
          <span>1</span>
        </template>
        <template x-if="step > 1">
          <i class="fa-solid fa-check text-xs"></i>
        </template>
      </div>
      <div>
        <p class="font-bold text-black">Konfirmasi</p>
        <p class="text-[10px] text-gray-400">Pilih Metode</p>
      </div>
    </div>

    <!-- Connector 1 -> 2 -->
    <div class="flex-1 h-[2px] mx-2 sm:mx-3 transition-colors duration-300"
         :class="step >= 2 ? 'bg-brand' : 'bg-gray-200'"></div>

    <!-- Step 2: Pembayaran -->
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
           :class="step >= 2 ? 'bg-brand text-white' : 'border border-black text-black'">
        <template x-if="step <= 2">
          <span>2</span>
        </template>
        <template x-if="step > 2">
          <i class="fa-solid fa-check text-xs"></i>
        </template>
      </div>
      <div>
        <p class="font-bold" :class="step >= 2 ? 'text-black' : 'text-gray-400'">Pembayaran</p>
        <p class="text-[10px] text-gray-400">Instruksi Bayar</p>
      </div>
    </div>

    <!-- Connector 2 -> 3 -->
    <div class="flex-1 h-[2px] mx-2 sm:mx-3 transition-colors duration-300"
         :class="step >= 3 ? 'bg-brand' : 'bg-gray-200'"></div>

    <!-- Step 3: Selesai -->
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-xs shrink-0"
           :class="step === 3 ? 'bg-brand text-white' : 'border border-black text-black'">
        <template x-if="step < 3">
          <span>3</span>
        </template>
        <template x-if="step === 3">
          <i class="fa-solid fa-check text-xs"></i>
        </template>
      </div>
      <div>
        <p class="font-bold" :class="step === 3 ? 'text-black' : 'text-gray-400'">Selesai</p>
        <p class="text-[10px] text-gray-400">Terverifikasi</p>
      </div>
    </div>

  </div>

  <!-- MAIN 2-COLUMN LAYOUT -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- ============================================== -->
    <!-- KOLOM KIRI (DETAIL & METODE PEMBAYARAN)       -->
    <!-- ============================================== -->
    <div class="lg:col-span-8 space-y-6">

      <!-- STEP 1: KONFIRMASI & PILIH METODE -->
      <div x-show="step === 1" class="space-y-6">
        
        <!-- CARD DETAIL & METODE PEMBAYARAN -->
        <div class="bg-white rounded-2xl p-6 sm:p-7 border border-gray-200 shadow-sm space-y-6">

          <!-- BOX DETAIL TAGIHAN -->
          <div>
            <h2 class="text-sm font-bold text-black mb-3">Detail Tagihan</h2>
            
            <div class="border border-gray-200 rounded-2xl p-5 bg-white">
              <!-- Top Row: Wifi Icon + Paket Info + Total Tagihan -->
              <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                  <div class="w-12 h-12 rounded-xl bg-[#fee2e2] flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-wifi text-brand text-xl"></i>
                  </div>
                  <div>
                    <h3 class="text-base font-bold text-black">{{ $billData['package_name'] }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">{{ $billData['period'] }}</p>
                  </div>
                </div>

                <div class="text-right">
                  <p class="text-[11px] text-gray-400 font-medium">Total Tagihan:</p>
                  <p class="text-lg sm:text-xl font-black text-black">Rp{{ $billData['total'] }}</p>
                </div>
              </div>

              <!-- Dashed Divider -->
              <div class="border-t border-dashed border-gray-200 my-4"></div>

              <!-- Breakdown Rows -->
              <div class="space-y-2 text-xs">
                <div class="flex items-center justify-between text-gray-600">
                  <span>Biaya Paket ({{ $billData['package_name'] }})</span>
                  <span class="font-semibold text-gray-900">Rp{{ $billData['price'] }}</span>
                </div>
                <div class="border-t border-gray-100 pt-2 flex items-center justify-between font-bold text-black">
                  <span>Total Pembayaran</span>
                  <span class="text-sm font-black text-brand">Rp{{ $billData['total'] }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- SECTION: PILIH METODE PEMBAYARAN -->
          <div>
            <h3 class="text-sm font-bold text-black mb-1">Pilih Metode Pembayaran</h3>
            <p class="text-xs text-gray-400 mb-4">Pilih metode pembayaran yang paling nyaman untuk Anda.</p>

            <!-- 4 Cards Grid Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              
              <!-- 1. Transfer Bank -->
              <div @click="paymentMethod = 'transfer'"
                   :class="paymentMethod === 'transfer' ? 'border-brand bg-[#fff5f5] ring-2 ring-brand/20' : 'border-gray-200 hover:border-gray-300 bg-white'"
                   class="border rounded-2xl p-4 cursor-pointer relative transition flex flex-col justify-between min-h-[110px] text-left">
                <div class="flex items-center justify-between w-full">
                  <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center">
                    <div x-show="paymentMethod === 'transfer'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
                  </div>
                  <i class="fa-solid fa-building-columns text-brand text-lg"></i>
                </div>
                <div class="mt-3">
                  <p class="text-xs font-bold text-black leading-tight">Transfer Bank</p>
                  <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">BCA, Mandiri, BNI, BRI</p>
                </div>
              </div>

              <!-- 2. E - Wallet -->
              <div @click="paymentMethod = 'ewallet'"
                   :class="paymentMethod === 'ewallet' ? 'border-brand bg-[#fff5f5] ring-2 ring-brand/20' : 'border-gray-200 hover:border-gray-300 bg-white'"
                   class="border rounded-2xl p-4 cursor-pointer relative transition flex flex-col justify-between min-h-[110px] text-left">
                <div class="flex items-center justify-between w-full">
                  <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center">
                    <div x-show="paymentMethod === 'ewallet'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
                  </div>
                  <i class="fa-solid fa-wallet text-cyan-500 text-lg"></i>
                </div>
                <div class="mt-3">
                  <p class="text-xs font-bold text-black leading-tight">E - Wallet</p>
                  <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">GoPay, OVO, Dana, ShopeePay</p>
                </div>
              </div>

              <!-- 3. Virtual Account -->
              <div @click="paymentMethod = 'va'"
                   :class="paymentMethod === 'va' ? 'border-brand bg-[#fff5f5] ring-2 ring-brand/20' : 'border-gray-200 hover:border-gray-300 bg-white'"
                   class="border rounded-2xl p-4 cursor-pointer relative transition flex flex-col justify-between min-h-[110px] text-left">
                <div class="flex items-center justify-between w-full">
                  <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center">
                    <div x-show="paymentMethod === 'va'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
                  </div>
                  <i class="fa-solid fa-credit-card text-blue-600 text-lg"></i>
                </div>
                <div class="mt-3">
                  <p class="text-xs font-bold text-black leading-tight">Virtual Account</p>
                  <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">Semua Bank Indonesia</p>
                </div>
              </div>

              <!-- 4. Alfamart/Indomart -->
              <div @click="paymentMethod = 'minimarket'"
                   :class="paymentMethod === 'minimarket' ? 'border-brand bg-[#fff5f5] ring-2 ring-brand/20' : 'border-gray-200 hover:border-gray-300 bg-white'"
                   class="border rounded-2xl p-4 cursor-pointer relative transition flex flex-col justify-between min-h-[110px] text-left">
                <div class="flex items-center justify-between w-full">
                  <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center">
                    <div x-show="paymentMethod === 'minimarket'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
                  </div>
                  <i class="fa-solid fa-store text-amber-500 text-lg"></i>
                </div>
                <div class="mt-3">
                  <p class="text-xs font-bold text-black leading-tight">Alfamart/Indomart</p>
                  <p class="text-[10px] text-gray-400 mt-0.5 leading-tight">Gerai terdekat</p>
                </div>
              </div>

            </div>

            <!-- SUB-OPTIONS BASED ON SELECTED METHOD -->
            <div class="mt-5 pt-4 border-t border-gray-100">
              
              <!-- Sub-pilihan: Transfer Bank -->
              <div x-show="paymentMethod === 'transfer'" class="space-y-3">
                <p class="text-xs font-semibold text-gray-700">Pilih Rekening Tujuan Bank:</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                  <template x-for="(bank, key) in banks" :key="key">
                    <button type="button"
                            @click="selectedBank = key"
                            :class="selectedBank === key ? 'border-brand bg-red-50/50 text-brand font-bold' : 'border-gray-200 text-gray-700 hover:border-gray-300 font-medium'"
                            class="border rounded-xl p-3 text-xs flex items-center justify-center gap-2 transition">
                      <span class="w-2 h-2 rounded-full" :class="selectedBank === key ? 'bg-brand' : 'bg-gray-300'"></span>
                      <span x-text="key.toUpperCase()"></span>
                    </button>
                  </template>
                </div>
              </div>

              <!-- Sub-pilihan: E-Wallet -->
              <div x-show="paymentMethod === 'ewallet'" class="space-y-3" style="display: none;">
                <p class="text-xs font-semibold text-gray-700">Pilih Layanan Dompet Digital:</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                  <template x-for="(ewallet, key) in ewallets" :key="key">
                    <button type="button"
                            @click="selectedEwallet = key"
                            :class="selectedEwallet === key ? 'border-cyan-500 bg-cyan-50/50 text-cyan-800 font-bold' : 'border-gray-200 text-gray-700 hover:border-gray-300 font-medium'"
                            class="border rounded-xl p-3 text-xs flex items-center justify-center gap-2 transition">
                      <span class="w-2 h-2 rounded-full" :class="selectedEwallet === key ? 'bg-cyan-500' : 'bg-gray-300'"></span>
                      <span x-text="ewallet.name"></span>
                    </button>
                  </template>
                </div>
              </div>

              <!-- Sub-pilihan: Virtual Account -->
              <div x-show="paymentMethod === 'va'" class="space-y-3" style="display: none;">
                <p class="text-xs font-semibold text-gray-700">Pilih Virtual Account:</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                  <template x-for="(va, key) in virtualAccounts" :key="key">
                    <button type="button"
                            @click="selectedVA = key"
                            :class="selectedVA === key ? 'border-blue-500 bg-blue-50/50 text-blue-800 font-bold' : 'border-gray-200 text-gray-700 hover:border-gray-300 font-medium'"
                            class="border rounded-xl p-3 text-xs flex items-center justify-center gap-2 transition">
                      <span class="w-2 h-2 rounded-full" :class="selectedVA === key ? 'bg-blue-500' : 'bg-gray-300'"></span>
                      <span x-text="key.toUpperCase() + ' VA'"></span>
                    </button>
                  </template>
                </div>
              </div>

              <!-- Sub-pilihan: Minimarket -->
              <div x-show="paymentMethod === 'minimarket'" class="space-y-3" style="display: none;">
                <p class="text-xs font-semibold text-gray-700">Pilih Gerai Minimarket:</p>
                <div class="grid grid-cols-2 gap-2.5">
                  <template x-for="(store, key) in minimarkets" :key="key">
                    <button type="button"
                            @click="selectedMinimarket = key"
                            :class="selectedMinimarket === key ? 'border-amber-500 bg-amber-50/50 text-amber-900 font-bold' : 'border-gray-200 text-gray-700 hover:border-gray-300 font-medium'"
                            class="border rounded-xl p-3 text-xs flex items-center justify-center gap-2 transition">
                      <span class="w-2 h-2 rounded-full" :class="selectedMinimarket === key ? 'bg-amber-500' : 'bg-gray-300'"></span>
                      <span x-text="store.name"></span>
                    </button>
                  </template>
                </div>
              </div>

            </div>
          </div>

          <!-- BANNER KEAMANAN -->
          <div class="bg-[#f0fdf4] border border-[#bbf7d0] rounded-xl p-3.5 flex items-center gap-3.5">
            <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 text-base shadow-sm">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
              <p class="text-xs font-bold text-gray-900">Transaksi Anda aman dan terenkripsi</p>
              <p class="text-[11px] text-gray-500 mt-0.5">Kami tidak menyimpan informasi pembayaran kartu kredit atau data pribadi Anda</p>
            </div>
          </div>

        </div>

        <!-- ACTION BUTTONS STEP 1 -->
        <div class="flex items-center justify-between pt-2">
          <a href="{{ route('tagihan') }}"
             class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-black bg-white hover:bg-gray-100 px-5 py-2.5 rounded-xl border border-gray-300 shadow-sm transition">
            <i class="fa-solid fa-arrow-left text-xs text-black"></i>
            <span>Kembali</span>
          </a>

          <button type="button"
                  @click="step = 2"
                  class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-white bg-brand hover:bg-brand-700 px-7 py-2.5 rounded-xl shadow-sm transition duration-200">
            <span>Lanjut</span>
            <i class="fa-solid fa-arrow-right text-xs"></i>
          </button>
        </div>

      </div>

      <!-- ============================================== -->
      <!-- STEP 2: HALAMAN TRANSFER / INSTRUKSI BAYAR     -->
      <!-- ============================================== -->
      <div x-show="step === 2" style="display: none;" class="space-y-6">
        
        <div class="bg-white rounded-2xl p-6 sm:p-7 border border-gray-200 shadow-sm space-y-6">
          
          <!-- Banner Countdown Waktu Pembayaran -->
          <div class="bg-gradient-to-r from-red-50 via-white to-red-50 border border-red-200 rounded-2xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <span class="inline-flex items-center gap-1.5 text-[11px] font-bold text-brand uppercase tracking-wider bg-red-100/60 px-2.5 py-0.5 rounded-md mb-1">
                <i class="fa-regular fa-clock"></i> Batas Waktu Bayar
              </span>
              <p class="text-xs text-gray-600">Selesaikan pembayaran sebelum batas waktu berakhir agar layanan tidak terganggu.</p>
            </div>

            <!-- Countdown Digits -->
            <div class="flex items-center gap-1.5 shrink-0 font-mono">
              <div class="bg-brand text-white text-base font-black px-2.5 py-1.5 rounded-lg shadow-sm" x-text="hours"></div>
              <span class="text-brand font-black">:</span>
              <div class="bg-brand text-white text-base font-black px-2.5 py-1.5 rounded-lg shadow-sm" x-text="minutes"></div>
              <span class="text-brand font-black">:</span>
              <div class="bg-brand text-white text-base font-black px-2.5 py-1.5 rounded-lg shadow-sm" x-text="seconds"></div>
            </div>
          </div>

          <!-- Detail Instruksi Bayar (Tergantung Pilihan) -->
          <div class="border border-gray-200 rounded-2xl p-5 sm:p-6 space-y-5 bg-white">
            
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
              <div>
                <p class="text-xs text-gray-400 font-medium">Metode Pembayaran Terpilih</p>
                <h4 class="text-base font-extrabold text-black mt-0.5">
                  <span x-show="paymentMethod === 'transfer'">Transfer Bank (<span x-text="selectedBank.toUpperCase()"></span>)</span>
                  <span x-show="paymentMethod === 'ewallet'">E-Wallet (<span x-text="ewallets[selectedEwallet].name"></span>)</span>
                  <span x-show="paymentMethod === 'va'">Virtual Account (<span x-text="virtualAccounts[selectedVA].name"></span>)</span>
                  <span x-show="paymentMethod === 'minimarket'">Gerai Minimarket (<span x-text="minimarkets[selectedMinimarket].name"></span>)</span>
                </h4>
              </div>
              <button type="button" @click="step = 1" class="text-xs text-brand hover:underline font-bold">
                Ganti Metode
              </button>
            </div>

            <!-- KONTEN: TRANSFER BANK -->
            <div x-show="paymentMethod === 'transfer'" class="space-y-4">
              <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Nomor Rekening</p>
                    <p class="text-lg sm:text-xl font-mono font-black text-black tracking-wider" x-text="banks[selectedBank].norek"></p>
                  </div>
                  <button type="button"
                          @click="copyText(banks[selectedBank].norek, 'Nomor Rekening')"
                          class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-regular fa-copy"></i> Salin
                  </button>
                </div>
                <div class="border-t border-gray-200 pt-2 text-xs flex justify-between text-gray-600">
                  <span>Nama Rekening:</span>
                  <span class="font-bold text-gray-900" x-text="banks[selectedBank].owner"></span>
                </div>
              </div>

              <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex items-center justify-between">
                <div>
                  <p class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Jumlah yang Harus Ditransfer</p>
                  <p class="text-lg sm:text-xl font-black text-brand">Rp{{ $billData['total'] }}</p>
                </div>
                <button type="button"
                        @click="copyText('{{ $billData['total_raw'] }}', 'Jumlah Pembayaran')"
                        class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>

            <!-- KONTEN: VIRTUAL ACCOUNT -->
            <div x-show="paymentMethod === 'va'" class="space-y-4" style="display: none;">
              <div class="bg-blue-50/50 rounded-xl p-4 border border-blue-200 space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-[11px] text-gray-500 uppercase tracking-wider font-semibold">Nomor Virtual Account</p>
                    <p class="text-lg sm:text-xl font-mono font-black text-blue-900 tracking-wider" x-text="virtualAccounts[selectedVA].va"></p>
                  </div>
                  <button type="button"
                          @click="copyText(virtualAccounts[selectedVA].va, 'Nomor Virtual Account')"
                          class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-regular fa-copy"></i> Salin
                  </button>
                </div>
                <div class="border-t border-blue-100 pt-2 text-xs flex justify-between text-gray-600">
                  <span>Atas Nama:</span>
                  <span class="font-bold text-blue-950" x-text="virtualAccounts[selectedVA].owner"></span>
                </div>
              </div>

              <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex items-center justify-between">
                <div>
                  <p class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Total Tagihan VA</p>
                  <p class="text-lg sm:text-xl font-black text-brand">Rp{{ $billData['total'] }}</p>
                </div>
                <button type="button"
                        @click="copyText('{{ $billData['total_raw'] }}', 'Jumlah Pembayaran')"
                        class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>

            <!-- KONTEN: E-WALLET -->
            <div x-show="paymentMethod === 'ewallet'" class="space-y-4" style="display: none;">
              <div class="bg-cyan-50/50 rounded-xl p-4 border border-cyan-200 space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-[11px] text-cyan-800 uppercase tracking-wider font-semibold">Nomor Telepon Akun E-Wallet</p>
                    <p class="text-lg sm:text-xl font-mono font-black text-cyan-950 tracking-wider" x-text="ewallets[selectedEwallet].number"></p>
                  </div>
                  <button type="button"
                          @click="copyText(ewallets[selectedEwallet].number, 'Nomor E-Wallet')"
                          class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-regular fa-copy"></i> Salin
                  </button>
                </div>
                <div class="border-t border-cyan-100 pt-2 text-xs flex justify-between text-gray-600">
                  <span>Nama Akun:</span>
                  <span class="font-bold text-cyan-950" x-text="ewallets[selectedEwallet].owner"></span>
                </div>
              </div>

              <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex items-center justify-between">
                <div>
                  <p class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Nominal Pembayaran</p>
                  <p class="text-lg sm:text-xl font-black text-brand">Rp{{ $billData['total'] }}</p>
                </div>
                <button type="button"
                        @click="copyText('{{ $billData['total_raw'] }}', 'Jumlah Pembayaran')"
                        class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>

            <!-- KONTEN: MINIMARKET -->
            <div x-show="paymentMethod === 'minimarket'" class="space-y-4" style="display: none;">
              <div class="bg-amber-50/50 rounded-xl p-4 border border-amber-200 space-y-3">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-[11px] text-amber-800 uppercase tracking-wider font-semibold">Kode Pembayaran Gerai</p>
                    <p class="text-lg sm:text-xl font-mono font-black text-amber-950 tracking-wider" x-text="minimarkets[selectedMinimarket].code"></p>
                  </div>
                  <button type="button"
                          @click="copyText(minimarkets[selectedMinimarket].code, 'Kode Pembayaran')"
                          class="bg-amber-600 hover:bg-amber-700 text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                    <i class="fa-regular fa-copy"></i> Salin
                  </button>
                </div>
                <div class="border-t border-amber-100 pt-2 text-xs text-amber-800">
                  <i class="fa-solid fa-circle-info mr-1"></i>
                  <span x-text="minimarkets[selectedMinimarket].instruction"></span>
                </div>
              </div>

              <div class="bg-gray-50 rounded-xl p-4 border border-gray-200 flex items-center justify-between">
                <div>
                  <p class="text-[11px] text-gray-400 uppercase tracking-wider font-semibold">Total Tagihan di Kasir</p>
                  <p class="text-lg sm:text-xl font-black text-brand">Rp{{ $billData['total'] }}</p>
                </div>
                <button type="button"
                        @click="copyText('{{ $billData['total_raw'] }}', 'Jumlah Pembayaran')"
                        class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1.5">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>

            <!-- Panduan Singkat Pembayaran -->
            <div class="pt-2 text-xs text-gray-500 space-y-1.5">
              <p class="font-bold text-gray-800">Petunjuk Pembayaran:</p>
              <ol class="list-decimal list-inside space-y-1 pl-1 text-[11px]">
                <li>Masukkan nominal persis sesuai yang tertera (tanpa pembulatan).</li>
                <li>Simpan bukti transaksi setelah menyelesaikan pembayaran.</li>
                <li>Sistem kami akan memverifikasi pembayaran Anda secara otomatis dalam waktu 1-3 menit.</li>
              </ol>
            </div>

          </div>

        </div>

        <!-- ACTION BUTTONS STEP 2 -->
        <div class="flex items-center justify-between pt-2">
          <button type="button"
                  @click="step = 1"
                  class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-black bg-white hover:bg-gray-100 px-5 py-2.5 rounded-xl border border-gray-300 shadow-sm transition">
            <i class="fa-solid fa-arrow-left text-xs text-black"></i>
            <span>Kembali ke Pilihan Metode</span>
          </button>

          <button type="button"
                  @click="step = 3"
                  class="inline-flex items-center gap-2 text-xs sm:text-sm font-bold text-white bg-brand hover:bg-brand-700 px-7 py-2.5 rounded-xl shadow-sm transition duration-200">
            <span>Saya Sudah Bayar</span>
            <i class="fa-solid fa-check text-xs"></i>
          </button>
        </div>

      </div>

      <!-- ============================================== -->
      <!-- STEP 3: HALAMAN SELESAI / SUKSES               -->
      <!-- ============================================== -->
      <div x-show="step === 3" style="display: none;" class="space-y-6">
        
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-gray-200 shadow-sm text-center space-y-6">
          
          <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center text-3xl shadow-md ring-8 ring-emerald-50">
            <i class="fa-solid fa-check"></i>
          </div>

          <div>
            <span class="inline-block bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-bold px-3 py-1 rounded-full mb-2">
              Pembayaran Terverifikasi
            </span>
            <h3 class="text-2xl font-black text-black">Terima Kasih! Pembayaran Berhasil</h3>
            <p class="text-xs sm:text-sm text-gray-500 mt-1 max-w-md mx-auto">
              Layanan internet {{ $billData['package_name'] }} Anda aktif untuk periode berikutnya.
            </p>
          </div>

          <!-- Box Rincian Pelunasan -->
          <div class="border border-gray-200 rounded-2xl p-5 bg-gray-50 max-w-lg mx-auto text-left space-y-3 text-xs">
            <div class="flex justify-between">
              <span class="text-gray-500">Nomor Invoice:</span>
              <span class="font-bold text-gray-900 font-mono">{{ $billData['invoice'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Paket Layanan:</span>
              <span class="font-bold text-gray-900">{{ $billData['package_name'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Periode Berlangganan:</span>
              <span class="font-bold text-gray-900">{{ $billData['period'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Total Dibayar:</span>
              <span class="font-black text-emerald-600 text-sm">Rp{{ $billData['total'] }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-500">Status:</span>
              <span class="font-bold text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded text-[10px]">LUNAS</span>
            </div>
          </div>

          <!-- Tombol Aksi Selesai -->
          <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
            <button type="button"
                    onclick="window.print()"
                    class="w-full sm:w-auto border border-gray-300 text-gray-700 hover:bg-gray-50 font-bold text-xs sm:text-sm px-6 py-2.5 rounded-xl transition flex items-center justify-center gap-2">
              <i class="fa-solid fa-print"></i> Cetak / Unduh Bukti
            </button>
            <a href="{{ route('tagihan') }}"
               class="w-full sm:w-auto bg-brand hover:bg-brand-700 text-white font-bold text-xs sm:text-sm px-7 py-2.5 rounded-xl shadow-sm transition flex items-center justify-center gap-2">
              <i class="fa-solid fa-arrow-left"></i> Kembali ke Tagihan Saya
            </a>
          </div>

        </div>

      </div>

    </div>

    <!-- ============================================== -->
    <!-- KOLOM KANAN (SIDEBAR RINGKASAN & BANTUAN)     -->
    <!-- ============================================== -->
    <div class="lg:col-span-4 space-y-5">
      
      <!-- CARD 1: RINGKASAN PESANAN -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200 shadow-sm">
        <h3 class="text-sm sm:text-base font-extrabold text-black mb-4">Ringkasan Pesanan</h3>

        <div class="space-y-3 text-xs">
          <!-- Paket 20 Mbps -->
          <div class="flex items-start justify-between">
            <div>
              <p class="font-semibold text-gray-800">{{ $billData['package_name'] }}</p>
              <p class="text-[10px] text-gray-400">/bulan</p>
            </div>
            <span class="font-semibold text-gray-900">Rp{{ $billData['price'] }}</span>
          </div>



          <!-- Divider -->
          <div class="border-t border-gray-200 my-3.5"></div>

          <!-- Total Pembayaran -->
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-gray-800">Total Pembayaran</span>
            <span class="text-lg font-black text-brand">Rp{{ $billData['total'] }}</span>
          </div>
        </div>
      </div>

      <!-- CARD 2: AMAN & TERPERCAYA (TRUST CHECKLIST) -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200 shadow-sm space-y-4">
        
        <!-- Badge Aman & Terpercaya -->
        <div>
          <span class="inline-flex items-center gap-1.5 bg-[#fff1f1] border border-red-200 text-brand px-3 py-1 rounded-full text-xs font-bold">
            <i class="fa-solid fa-shield-halved text-xs"></i>
            Aman & Terpercaya
          </span>
        </div>

        <!-- Checklist -->
        <div class="space-y-3.5 text-xs">
          
          <!-- Item 1: Pembayaran Cepat -->
          <div class="flex items-start gap-2.5">
            <div class="w-4 h-4 rounded-full bg-red-100 flex items-center justify-center text-brand text-[10px] shrink-0 mt-0.5">
              <i class="fa-solid fa-check"></i>
            </div>
            <div>
              <p class="font-bold text-gray-900 leading-tight">Pembayaran Cepat</p>
              <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Proses verifikasi otomatis dalam hitungan menit</p>
            </div>
          </div>

          <!-- Item 2: Keamanan Terjamin -->
          <div class="flex items-start gap-2.5">
            <div class="w-4 h-4 rounded-full bg-red-100 flex items-center justify-center text-brand text-[10px] shrink-0 mt-0.5">
              <i class="fa-solid fa-check"></i>
            </div>
            <div>
              <p class="font-bold text-gray-900 leading-tight">Keamanan Terjamin</p>
              <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Enkripsi SSL 256-bit untuk perlindungan data</p>
            </div>
          </div>

          <!-- Item 3: Bukti Pembayaran -->
          <div class="flex items-start gap-2.5">
            <div class="w-4 h-4 rounded-full bg-red-100 flex items-center justify-center text-brand text-[10px] shrink-0 mt-0.5">
              <i class="fa-solid fa-check"></i>
            </div>
            <div>
              <p class="font-bold text-gray-900 leading-tight">Bukti Pembayaran</p>
              <p class="text-[11px] text-gray-500 mt-0.5 leading-snug">Invoice resmi dikirim ke email & WhatsApp</p>
            </div>
          </div>

        </div>

      </div>

      <!-- CARD 3: BUTUH BANTUAN? -->
      <div class="bg-white rounded-2xl p-5 sm:p-6 border border-gray-200 shadow-sm space-y-3">
        <h4 class="text-sm sm:text-base font-extrabold text-black">Butuh Bantuan?</h4>
        <p class="text-xs text-gray-500 leading-relaxed">
          Tim kami siap membantu Anda jika mengalami kendala pembayaran.
        </p>
        <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20butuh%20bantuan%20terkait%20pembayaran%20tagihan%20{{ $billData['invoice'] }}"
           target="_blank"
           class="w-full border border-brand text-brand hover:bg-brand hover:text-white font-bold text-xs sm:text-sm py-2.5 px-4 rounded-xl transition duration-200 flex items-center justify-center gap-2 text-center">
          <i class="fa-regular fa-comment-dots text-sm"></i>
          <span>Hubungi Kami</span>
        </a>
      </div>

    </div>

  </div>

</div>
@endsection
