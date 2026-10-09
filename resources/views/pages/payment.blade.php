@extends('layouts.app')

@section('title', 'WiFi Banterpool - Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{
    paymentMethod: 'va',
    activeGuideTab: 'brimo',

    // Countdown Timer Data
    totalSeconds: 24 * 3600,
    hours: '23',
    minutes: '59',
    seconds: '59',

    briVA: {
        name: 'BRI Virtual Account',
        code: 'BRIVA',
        va: '7701 5544 3322 1100',
        owner: 'BANTERPOOL - {{ strtoupper($customerData['name'] ?? 'PELANGGAN') }}',
        logo: '{{ asset('image/banks/bri.svg') }}'
    },

    isProcessing: false,
    copiedToast: false,
    toastMessage: '',

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

    copyToClipboard(text, label = 'Nomor Virtual Account') {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        }
        this.toastMessage = label + ' berhasil disalin!';
        this.copiedToast = true;
        setTimeout(() => { this.copiedToast = false; }, 2500);
    },

    getPaymentMethodLabel() {
        return 'BRI Virtual Account';
    },

    submitPayment(status) {
        this.isProcessing = true;
        const currentUrl = new URL(window.location.href);
        const searchParams = new URLSearchParams(currentUrl.search);
        searchParams.set('status', status);
        searchParams.set('payment_method', this.getPaymentMethodLabel());
        window.location.href = '{{ route("payment.status") }}?' + searchParams.toString();
    }
}">

  <!-- Tombol Kembali -->
  <a href="{{ route('checkout', request()->query()) }}" class="inline-flex items-center gap-2 text-brand font-bold text-sm mb-6 hover:underline">
    <i class="fa-solid fa-arrow-left"></i> Kembali
  </a>

  <!-- Header Section -->
  <div class="mb-6">
    <h1 class="text-2xl sm:text-3xl font-black text-black">Pembayaran</h1>
    <p class="text-xs sm:text-sm text-gray-500 mt-1">Selesaikan pembayaran untuk mengaktifkan layanan internet anda.</p>
  </div>

  <!-- Stepper Indicator -->
  <div class="flex items-center justify-between max-w-2xl mb-8 text-xs">
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold"><i class="fa-solid fa-check text-xs"></i></div>
      <div><p class="font-bold text-black">Pilih Paket</p><p class="text-[10px] text-gray-400">{{ request('package_name', 'Paket 20 Mbps') }}</p></div>
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
      <div class="w-7 h-7 rounded-full bg-black text-white flex items-center justify-center font-bold text-xs">4</div>
      <div><p class="font-bold text-black">Selesai</p><p class="text-[10px] text-gray-400">Terhubung</p></div>
    </div>
  </div>

  <!-- Banner Keamanan -->
  <div class="bg-green-100 border border-green-200 rounded-xl p-3 mb-6 flex items-center gap-2 text-xs text-green-800">
    <i class="fa-solid fa-lock text-green-600"></i>
    <span>Transaksi anda aman dan terenkripsi. Data pembayaran hanya digunakan untuk proses ini.</span>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Sisi Kiri: Pilihan Metode Pembayaran & Tampilan Dinamis -->
    <div class="lg:col-span-8 border border-black rounded-2xl p-6 bg-white space-y-6">

      <div>
        <div class="flex items-center justify-between mb-1">
          <h3 class="text-sm font-bold text-black">Metode Pembayaran</h3>
          <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full">
            <i class="fa-solid fa-bolt text-[10px]"></i> Verifikasi Otomatis
          </span>
        </div>
        <p class="text-xs text-gray-400 mb-4">Pembayaran pesanan khusus menggunakan Virtual Account Bank BRI (BRIVA).</p>

        <!-- Card Bank BRI Virtual Account (Satu-satunya Metode Aktif Pelanggan) -->
        <div class="border-2 border-brand bg-red-50/20 rounded-2xl p-4 sm:p-5 relative transition shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-white border border-gray-200 p-2 flex items-center justify-center shrink-0 shadow-xs">
              <img src="{{ asset('image/banks/bri.svg') }}" alt="Bank BRI" class="h-8 max-w-[50px] object-contain">
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h4 class="text-sm sm:text-base font-black text-black">BRI Virtual Account (BRIVA)</h4>
                <span class="bg-brand text-white text-[10px] font-extrabold px-2 py-0.5 rounded-md uppercase tracking-wider">Aktif</span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">Dapat dibayar melalui BRImo, ATM BRI, dan Transfer Antar Bank</p>
            </div>
          </div>
          <div class="flex items-center gap-2 self-end sm:self-center">
            <div class="w-6 h-6 rounded-full bg-brand text-white flex items-center justify-center text-xs shadow-xs">
              <i class="fa-solid fa-check"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Section Instruksi Rekening / Virtual Account BRI -->
      <div class="border border-black rounded-2xl p-5 bg-white space-y-4">
        <div>
          <h3 class="text-sm font-bold text-black">Instruksi Pembayaran</h3>
          <p class="text-xs text-gray-400">Lakukan pembayaran sebelum waktu berakhir.</p>
        </div>

        <div class="border border-black rounded-xl p-5 bg-white space-y-5">
          <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
            <div class="md:col-span-3 flex items-center">
              <img :src="briVA.logo" alt="Bank BRI" class="h-9 max-w-[120px] object-contain">
            </div>
            <div class="md:col-span-5 space-y-1">
              <p class="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Nomor Virtual Account (BRIVA)</p>
              <p class="text-base sm:text-lg font-black text-black tracking-wider font-mono" x-text="briVA.va"></p>
            </div>
            <div class="md:col-span-4 flex items-center justify-between md:justify-end gap-3">
              <div>
                <p class="text-[10px] text-gray-400 font-medium">Atas Nama</p>
                <p class="text-xs font-black text-black" x-text="briVA.owner"></p>
              </div>
              <button type="button" @click="copyToClipboard(briVA.va, 'Nomor Virtual Account')" class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 shrink-0">
                <i class="fa-regular fa-copy"></i> Salin
              </button>
            </div>
          </div>

          <!-- Petunjuk Langkah Pembayaran BRIVA -->
          <div class="pt-4 border-t border-gray-200 space-y-3">
            <p class="text-xs font-bold text-gray-900">Petunjuk Cara Pembayaran:</p>
            <div class="flex items-center gap-2 border-b border-gray-200 pb-2 overflow-x-auto text-xs">
              <button type="button" @click="activeGuideTab = 'brimo'"
                      :class="activeGuideTab === 'brimo' ? 'border-b-2 border-brand text-brand font-bold' : 'text-gray-500 hover:text-black'"
                      class="pb-1.5 px-2 transition whitespace-nowrap">
                <i class="fa-solid fa-mobile-screen mr-1"></i> BRImo (Mobile Banking)
              </button>
              <button type="button" @click="activeGuideTab = 'atm'"
                      :class="activeGuideTab === 'atm' ? 'border-b-2 border-brand text-brand font-bold' : 'text-gray-500 hover:text-black'"
                      class="pb-1.5 px-2 transition whitespace-nowrap">
                <i class="fa-solid fa-credit-card mr-1"></i> ATM BRI
              </button>
              <button type="button" @click="activeGuideTab = 'other'"
                      :class="activeGuideTab === 'other' ? 'border-b-2 border-brand text-brand font-bold' : 'text-gray-500 hover:text-black'"
                      class="pb-1.5 px-2 transition whitespace-nowrap">
                <i class="fa-solid fa-building-columns mr-1"></i> Transfer Antar Bank
              </button>
            </div>

            <!-- Tab 1: BRImo -->
            <div x-show="activeGuideTab === 'brimo'" class="space-y-1.5 text-xs text-gray-600">
              <ol class="list-decimal list-inside space-y-1 text-[11px] text-gray-600 leading-relaxed">
                <li>Buka aplikasi <strong class="text-gray-900">BRImo</strong> dan login ke akun Anda.</li>
                <li>Pilih menu <strong class="text-gray-900">Tagihan</strong> lalu pilih fitur <strong class="text-gray-900">BRIVA</strong>.</li>
                <li>Pilih <strong class="text-gray-900">Pembayaran Baru</strong> dan masukkan Nomor BRIVA: <span class="font-mono font-bold text-brand" x-text="briVA.va"></span>.</li>
                <li>Periksa kesesuaian rincian pembayaran (Nama: <span class="font-bold text-gray-900" x-text="briVA.owner"></span> & Total Tagihan).</li>
                <li>Masukkan <strong class="text-gray-900">PIN BRImo</strong> Anda dan konfirmasi pembayaran.</li>
                <li>Simpan bukti transaksi setelah pembayaran selesai.</li>
              </ol>
            </div>

            <!-- Tab 2: ATM BRI -->
            <div x-show="activeGuideTab === 'atm'" style="display: none;" class="space-y-1.5 text-xs text-gray-600">
              <ol class="list-decimal list-inside space-y-1 text-[11px] text-gray-600 leading-relaxed">
                <li>Masukkan kartu ATM BRI dan PIN Anda di mesin ATM BRI.</li>
                <li>Pilih menu <strong class="text-gray-900">Transaksi Lain</strong> > <strong class="text-gray-900">Pembayaran</strong> > <strong class="text-gray-900">Lainnya</strong> > <strong class="text-gray-900">BRIVA</strong>.</li>
                <li>Masukkan Nomor Virtual Account BRIVA: <span class="font-mono font-bold text-brand" x-text="briVA.va"></span> lalu tekan <strong class="text-gray-900">Benar</strong>.</li>
                <li>Periksa data konfirmasi pembayaran pada layar mesin ATM, lalu tekan <strong class="text-gray-900">Ya</strong>.</li>
                <li>Ambil struk transaksi sebagai bukti pembayaran.</li>
              </ol>
            </div>

            <!-- Tab 3: Bank Lain -->
            <div x-show="activeGuideTab === 'other'" style="display: none;" class="space-y-1.5 text-xs text-gray-600">
              <ol class="list-decimal list-inside space-y-1 text-[11px] text-gray-600 leading-relaxed">
                <li>Buka aplikasi Mobile Banking / ATM bank lain Anda (BCA, Mandiri, BNI, dll).</li>
                <li>Pilih menu <strong class="text-gray-900">Transfer Antar Bank</strong> / <strong class="text-gray-900">Ke Rekening Bank Lain</strong>.</li>
                <li>Pilih bank tujuan <strong class="text-gray-900">Bank BRI</strong> (Kode Bank: <strong class="text-gray-900">002</strong>).</li>
                <li>Masukkan Nomor Virtual Account: <span class="font-mono font-bold text-brand" x-text="briVA.va"></span> pada kolom nomor rekening tujuan.</li>
                <li>Masukkan nominal transfer persis sesuai total tagihan (<strong class="text-brand">Rp{{ request('package_price', '110.000') }}</strong>).</li>
                <li>Ikuti konfirmasi sampai transaksi berhasil dan simpan bukti transfer.</li>
              </ol>
            </div>
          </div>

          <div class="border-t border-gray-200 mt-5 pt-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <p class="text-[10px] text-gray-400 font-medium">Total Pembayaran</p>
              <p class="text-xl font-black text-brand">Rp{{ request('package_price', '110.000') }}</p>
            </div>

            <div class="text-left sm:text-right">
              <p class="text-[10px] text-gray-400 font-medium">Batas Waktu Pembayaran</p>
              <p class="text-base font-black text-brand flex items-center gap-1.5 sm:justify-end">
                <i class="fa-regular fa-clock"></i>
                <span x-text="hours"></span> : <span x-text="minutes"></span> : <span x-text="seconds"></span>
              </p>
              <p class="text-[10px] text-gray-400">Selesaikan sebelum {{ now('Asia/Jakarta')->addHours(24)->translatedFormat('d F Y, H:i') }} WIB</p>
            </div>
          </div>
        </div>
      </div>

      <div class="bg-blue-50/80 border border-blue-200/80 rounded-2xl p-4 flex items-center gap-3 text-xs text-blue-900 shadow-xs">
        <div class="w-8 h-8 rounded-xl bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
          <i class="fa-solid fa-circle-info text-sm"></i>
        </div>
        <p class="leading-relaxed">Setelah pembayaran berhasil, layanan internet dan jadwal pemasangan akan segera divalidasi oleh sistem dalam <strong>5–10 menit</strong>.</p>
      </div>

      <!-- Tombol Aksi Pembayaran Sempurna & Responsif -->
      <div class="pt-3 border-t border-gray-200/70 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
        
        <!-- Tombol Kembali -->
        <a href="{{ route('checkout', request()->query()) }}"
           class="inline-flex items-center justify-center gap-2 border border-gray-300 hover:border-gray-400 text-gray-700 hover:text-black bg-white hover:bg-gray-50 px-5 py-3.5 rounded-2xl text-xs sm:text-sm font-bold transition duration-150 shadow-xs active:scale-[0.98]">
          <i class="fa-solid fa-arrow-left text-xs text-gray-400"></i>
          <span>Kembali</span>
        </a>

        <!-- Konfirmasi Pembayaran Berhasil -->
        <button type="button"
                @click="submitPayment('success')"
                :disabled="isProcessing"
                class="inline-flex items-center justify-center gap-2.5 bg-gradient-to-r from-red-600 via-brand to-red-700 hover:from-red-700 hover:to-red-800 text-white font-extrabold px-8 py-3.5 rounded-2xl shadow-md shadow-red-600/25 hover:shadow-lg hover:shadow-red-600/35 transition-all duration-150 active:scale-[0.98] text-xs sm:text-sm cursor-pointer disabled:opacity-60">
          <template x-if="!isProcessing">
            <i class="fa-solid fa-circle-check text-base"></i>
          </template>
          <template x-if="isProcessing">
            <i class="fa-solid fa-circle-notch fa-spin text-base"></i>
          </template>
          <span x-text="isProcessing ? 'Memproses Pesanan...' : 'Saya Sudah Membayar'">Saya Sudah Membayar</span>
        </button>

      </div>

      <!-- Security Trust Guarantee Below Buttons -->
      <div class="flex items-center justify-end gap-2 text-[11px] text-gray-400 pt-1">
        <i class="fa-solid fa-shield-halved text-emerald-600 text-xs"></i>
        <span>Enkripsi 256-bit • Pembayaran Aman & Terverifikasi Langsung</span>
      </div>

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
                <p class="font-bold text-black">{{ request('package_name', 'Paket 20 Mbps') }}</p>
                <p class="text-[10px] text-gray-400">Internet cepat & stabil</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-bold text-black">Rp{{ request('package_price', '110.000') }}</p>
              <p class="text-[10px] text-gray-400">/bulan</p>
            </div>
          </div>

          <div class="border-t border-gray-200 pt-3 flex justify-between items-center text-sm">
            <span class="font-bold text-black">Total Pembayaran</span>
            <span class="font-black text-brand text-base">Rp{{ request('package_price', '110.000') }} <span class="text-xs font-normal text-gray-500">/bulan</span></span>
          </div>
        </div>

        <div class="bg-red-50 rounded-xl p-3 flex items-start gap-2 text-xs text-gray-700">
          <i class="fa-solid fa-circle-check text-brand mt-0.5"></i>
          <div>
            <p class="font-bold text-black">Aman & Terpercaya</p>
            <p class="text-[10px] text-gray-500 leading-tight">Data anda aman bersama kami. Pembayaran terenkripsi dengan sistem keamanan terbaik</p>
          </div>
        </div>

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

        <div class="text-center pt-2">
          <p class="text-xs font-bold text-black">Butuh Bantuan?</p>
          <p class="text-[10px] text-gray-400 mb-3">Hubungi tim kami untuk lebih lanjut</p>

          <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20butuh%20bantuan%20terkait%20pembayaran%20pemasangan%20WiFi." target="_blank" class="w-full border border-brand text-brand font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-brand hover:text-white transition">
            <i class="fa-solid fa-headset"></i> Hubungi Kami
          </a>
        </div>
      </div>
    </div>

  <!-- Floating Toast Notifikasi Nomor Tersalin -->
  <div x-show="copiedToast"
       x-cloak
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0 translate-y-3 scale-95"
       x-transition:enter-end="opacity-100 translate-y-0 scale-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100 translate-y-0 scale-100"
       x-transition:leave-end="opacity-0 translate-y-3 scale-95"
       class="fixed bottom-6 right-6 z-50 bg-slate-900/95 backdrop-blur-md text-white text-xs font-semibold px-4 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border border-slate-700">
    <div class="w-6 h-6 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-xs">
      <i class="fa-solid fa-check"></i>
    </div>
    <span x-text="toastMessage || 'Nomor Virtual Account BRI berhasil disalin!'"></span>
  </div>

</div>
@endsection
