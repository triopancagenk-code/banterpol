@extends('layouts.app')

@section('title', 'WiFi Banterpool - Pembayaran')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6" x-data="{
    paymentMethod: 'transfer',
    selectedBank: 'bca',
    selectedEwallet: 'gopay',

    // Countdown Timer Data
    totalSeconds: 24 * 3600,
    hours: '23',
    minutes: '59',
    seconds: '59',

    banks: {
        'bca': { name: 'BCA', norek: '1234 5678 9012 3456', owner: 'PT. SAGA INFRASTRUKTUR', logo: '{{ asset('image/banks/bca.svg') }}' },
        'mandiri': { name: 'Mandiri', norek: '1370 0000 9876 5432', owner: 'PT. SAGA INFRASTRUKTUR', logo: '{{ asset('image/banks/mandiri.svg') }}' },
        'bni': { name: 'BNI', norek: '0098 7654 3210 0001', owner: 'PT. SAGA INFRASTRUKTUR', logo: '{{ asset('image/banks/bni.svg') }}' },
        'bri': { name: 'BRI', norek: '0123 0100 9988 501', owner: 'PT. SAGA INFRASTRUKTUR', logo: '{{ asset('image/banks/bri.svg') }}' }
    },
    ewallets: {
        'gopay': { name: 'GoPay', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL', logo: '{{ asset('image/banks/gopay.svg') }}' },
        'ovo': { name: 'OVO', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL', logo: '{{ asset('image/banks/ovo.svg') }}' },
        'dana': { name: 'DANA', number: '0812 3456 7890', owner: 'BANTERPOOL OFFICIAL', logo: '{{ asset('image/banks/dana.svg') }}' }
    },
    virtualAccounts: {
        'bca': { name: 'BCA Virtual Account', va: '8800 1234 5678 9101', owner: 'BANTERPOOL - RAFI', logo: '{{ asset('image/banks/bca.svg') }}' },
        'mandiri': { name: 'Mandiri Livin VA', va: '8900 9876 5432 1011', owner: 'BANTERPOOL - RAFI', logo: '{{ asset('image/banks/mandiri.svg') }}' },
        'bni': { name: 'BNI Virtual Account', va: '8808 1122 3344 5566', owner: 'BANTERPOOL - RAFI', logo: '{{ asset('image/banks/bni.svg') }}' },
        'bri': { name: 'BRI BRIVA', va: '7701 5544 3322 1100', owner: 'BANTERPOOL - RAFI', logo: '{{ asset('image/banks/bri.svg') }}' }
    },
    qris: {
        name: 'QRIS',
        merchant: 'PT BANTERPOOL TELEKOMUNIKASI',
        nmid: 'ID1020038829101',
        logo: '{{ asset('image/banks/qris.svg') }}',
        qrImage: '{{ asset('image/banks/qris-code.svg') }}'
    },

    isProcessing: false,
    copiedToast: false,

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

    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text);
        }
        this.copiedToast = true;
        setTimeout(() => { this.copiedToast = false; }, 2500);
    },

    getPaymentMethodLabel() {
        if (this.paymentMethod === 'transfer') {
            return 'Transfer Bank (' + (this.banks[this.selectedBank]?.name || 'BCA') + ')';
        } else if (this.paymentMethod === 'ewallet') {
            return 'E-Wallet (' + (this.ewallets[this.selectedEwallet]?.name || 'GoPay') + ')';
        } else if (this.paymentMethod === 'va') {
            return 'Virtual Account (' + (this.virtualAccounts[this.selectedBank]?.name || 'BCA') + ')';
        } else if (this.paymentMethod === 'qris') {
            return 'Scan QRIS';
        }
        return 'Transfer Bank (BCA)';
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
        <h3 class="text-sm font-bold text-black mb-1">Pilih Metode Pembayaran</h3>
        <p class="text-xs text-gray-400 mb-4">Pilih metode pembayaran yang paling nyaman untuk anda.</p>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
          <!-- Transfer Bank -->
          <div @click="paymentMethod = 'transfer'"
               :class="paymentMethod === 'transfer' ? 'border-brand ring-2 ring-brand/20 bg-red-50/20' : 'border-gray-300 hover:border-black'"
               class="border rounded-2xl p-4 cursor-pointer text-center relative transition flex flex-col items-center justify-between min-h-[120px]">
            <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center absolute top-3 left-3">
              <div x-show="paymentMethod === 'transfer'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
            </div>
            <i class="fa-solid fa-building-columns text-2xl text-black mt-4"></i>
            <div>
              <p class="font-bold text-xs text-black mt-2">Transfer Bank</p>
              <p class="text-[10px] text-gray-400">Semua Bank</p>
            </div>
          </div>

          <!-- E-Wallet -->
          <div @click="paymentMethod = 'ewallet'"
               :class="paymentMethod === 'ewallet' ? 'border-brand ring-2 ring-brand/20 bg-red-50/20' : 'border-gray-300 hover:border-black'"
               class="border rounded-2xl p-4 cursor-pointer text-center relative transition flex flex-col items-center justify-between min-h-[120px]">
            <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center absolute top-3 left-3">
              <div x-show="paymentMethod === 'ewallet'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
            </div>
            <i class="fa-solid fa-wallet text-2xl text-blue-500 mt-4"></i>
            <div>
              <p class="font-bold text-xs text-black mt-2">E - Wallet</p>
              <p class="text-[10px] text-gray-400">OVO, Dana, GoPay</p>
            </div>
          </div>

          <!-- Virtual Account -->
          <div @click="paymentMethod = 'va'"
               :class="paymentMethod === 'va' ? 'border-brand ring-2 ring-brand/20 bg-red-50/20' : 'border-gray-300 hover:border-black'"
               class="border rounded-2xl p-4 cursor-pointer text-center relative transition flex flex-col items-center justify-between min-h-[120px]">
            <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center absolute top-3 left-3">
              <div x-show="paymentMethod === 'va'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
            </div>
            <span class="font-black text-blue-800 text-xl tracking-tighter mt-4 border border-blue-800 px-2 rounded">VA</span>
            <div>
              <p class="font-bold text-xs text-black mt-2">Virtual Account</p>
              <p class="text-[10px] text-gray-400">BCA, Mandiri, BNI, BRI</p>
            </div>
          </div>

          <!-- Scan QRIS -->
          <div @click="paymentMethod = 'qris'"
               :class="paymentMethod === 'qris' ? 'border-brand ring-2 ring-brand/20 bg-red-50/20' : 'border-gray-300 hover:border-black'"
               class="border rounded-2xl p-4 cursor-pointer text-center relative transition flex flex-col items-center justify-between min-h-[120px]">
            <div class="w-4 h-4 rounded-full border border-gray-400 flex items-center justify-center absolute top-3 left-3">
              <div x-show="paymentMethod === 'qris'" class="w-2.5 h-2.5 rounded-full bg-brand"></div>
            </div>
            <i class="fa-solid fa-qrcode text-2xl text-red-600 mt-4"></i>
            <div>
              <p class="font-bold text-xs text-black mt-2">Scan QRIS</p>
              <p class="text-[10px] text-gray-400">Semua E-Wallet & Bank</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Sub-Pilihan Berdasarkan Metode -->
      <template x-if="paymentMethod === 'transfer'">
        <div class="pt-2">
          <label class="block text-xs font-bold text-black mb-2">Pilih Bank Tujuan:</label>
          <div class="flex flex-wrap gap-2.5">
            <template x-for="(b, key) in banks" :key="key">
              <button type="button" @click="selectedBank = key"
                      :class="selectedBank === key ? 'border-brand bg-red-50 ring-1 ring-brand' : 'border-gray-300 bg-white hover:border-gray-400'"
                      class="px-4 py-2.5 border rounded-xl transition flex items-center justify-center h-12 min-w-[100px] shadow-sm">
                <img :src="b.logo" :alt="b.name" class="h-6 max-w-[85px] object-contain">
              </button>
            </template>
          </div>
        </div>
      </template>

      <template x-if="paymentMethod === 'ewallet'">
        <div class="pt-2">
          <label class="block text-xs font-bold text-black mb-2">Pilih Aplikasi E-Wallet:</label>
          <div class="flex flex-wrap gap-2.5">
            <template x-for="(ew, key) in ewallets" :key="key">
              <button type="button" @click="selectedEwallet = key"
                      :class="selectedEwallet === key ? 'border-brand bg-red-50 ring-1 ring-brand' : 'border-gray-300 bg-white hover:border-gray-400'"
                      class="px-4 py-2.5 border rounded-xl transition flex items-center justify-center h-12 min-w-[100px] shadow-sm">
                <img :src="ew.logo" :alt="ew.name" class="h-6 max-w-[85px] object-contain">
              </button>
            </template>
          </div>
        </div>
      </template>

      <template x-if="paymentMethod === 'va'">
        <div class="pt-2">
          <label class="block text-xs font-bold text-black mb-2">Pilih Bank Virtual Account:</label>
          <div class="flex flex-wrap gap-2.5">
            <template x-for="(va, key) in virtualAccounts" :key="key">
              <button type="button" @click="selectedBank = key"
                      :class="selectedBank === key ? 'border-brand bg-red-50 ring-1 ring-brand' : 'border-gray-300 bg-white hover:border-gray-400'"
                      class="px-4 py-2.5 border rounded-xl transition flex items-center justify-center h-12 min-w-[100px] shadow-sm">
                <img :src="va.logo" :alt="va.name" class="h-6 max-w-[85px] object-contain">
              </button>
            </template>
          </div>
        </div>
      </template>

      <template x-if="paymentMethod === 'qris'">
        <div class="pt-2">
          <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2.5">
              <img src="{{ asset('image/banks/qris.svg') }}" alt="QRIS" class="h-6 w-auto object-contain">
              <div>
                <p class="font-bold text-gray-900">Pembayaran Instan & Otomatis</p>
                <p class="text-[11px] text-gray-500">Mendukung BCA, Mandiri, BRI, BNI, GoPay, OVO, DANA, ShopeePay, LinkAja</p>
              </div>
            </div>
            <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-lg shrink-0">
              <i class="fa-solid fa-bolt"></i> Terverifikasi Otomatis
            </span>
          </div>
        </div>
      </template>

      <!-- Section Instruksi Rekening -->
      <div class="border border-black rounded-2xl p-5 bg-white space-y-4">
        <div>
          <h3 class="text-sm font-bold text-black">Instruksi Pembayaran</h3>
          <p class="text-xs text-gray-400">Lakukan pembayaran sebelum waktu berakhir.</p>
        </div>

        <div class="border border-black rounded-xl p-5 bg-white">
          <template x-if="paymentMethod === 'transfer'">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
              <div class="md:col-span-3 flex items-center">
                <img :src="banks[selectedBank].logo" :alt="banks[selectedBank].name" class="h-9 max-w-[120px] object-contain">
              </div>
              <div class="md:col-span-5 space-y-1">
                <p class="text-[10px] text-gray-400 font-medium">Nomor Rekening</p>
                <p class="text-base font-black text-black tracking-wider" x-text="banks[selectedBank].norek"></p>
              </div>
              <div class="md:col-span-4 flex items-center justify-between md:justify-end gap-3">
                <div>
                  <p class="text-[10px] text-gray-400 font-medium">Atas Nama</p>
                  <p class="text-xs font-black text-black" x-text="banks[selectedBank].owner"></p>
                </div>
                <button type="button" @click="copyToClipboard(banks[selectedBank].norek)" class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>
          </template>

          <template x-if="paymentMethod === 'ewallet'">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
              <div class="md:col-span-3 flex items-center">
                <img :src="ewallets[selectedEwallet].logo" :alt="ewallets[selectedEwallet].name" class="h-9 max-w-[120px] object-contain">
              </div>
              <div class="md:col-span-5 space-y-1">
                <p class="text-[10px] text-gray-400 font-medium">Nomor HP / E-Wallet</p>
                <p class="text-base font-black text-black tracking-wider" x-text="ewallets[selectedEwallet].number"></p>
              </div>
              <div class="md:col-span-4 flex items-center justify-between md:justify-end gap-3">
                <div>
                  <p class="text-[10px] text-gray-400 font-medium">Atas Nama</p>
                  <p class="text-xs font-black text-black" x-text="ewallets[selectedEwallet].owner"></p>
                </div>
                <button type="button" @click="copyToClipboard(ewallets[selectedEwallet].number)" class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>
          </template>

          <template x-if="paymentMethod === 'va'">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-center">
              <div class="md:col-span-3 flex items-center">
                <img :src="virtualAccounts[selectedBank].logo" :alt="virtualAccounts[selectedBank].name" class="h-9 max-w-[120px] object-contain">
              </div>
              <div class="md:col-span-5 space-y-1">
                <p class="text-[10px] text-gray-400 font-medium">Nomor Virtual Account</p>
                <p class="text-base font-black text-black tracking-wider" x-text="virtualAccounts[selectedBank].va"></p>
              </div>
              <div class="md:col-span-4 flex items-center justify-between md:justify-end gap-3">
                <div>
                  <p class="text-[10px] text-gray-400 font-medium">Atas Nama</p>
                  <p class="text-xs font-black text-black" x-text="virtualAccounts[selectedBank].owner"></p>
                </div>
                <button type="button" @click="copyToClipboard(virtualAccounts[selectedBank].va)" class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                  <i class="fa-regular fa-copy"></i> Salin
                </button>
              </div>
            </div>
          </template>

          <template x-if="paymentMethod === 'qris'">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
              <div class="md:col-span-4 flex flex-col items-center justify-center text-center">
                <img :src="qris.qrImage" alt="QRIS Code" class="w-48 sm:w-52 h-auto rounded-xl shadow-sm border border-gray-200">
                <a :href="qris.qrImage" download="QRIS-Banterpool.svg" class="mt-2 text-[11px] font-bold text-brand hover:underline inline-flex items-center gap-1">
                  <i class="fa-solid fa-download"></i> Unduh QR Code
                </a>
              </div>
              <div class="md:col-span-8 space-y-3">
                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                  <div class="flex items-center gap-2.5">
                    <img :src="qris.logo" alt="QRIS" class="h-8 max-w-[100px] object-contain">
                    <div>
                      <p class="text-[10px] text-gray-400 font-medium">Nama Merchant</p>
                      <p class="text-xs sm:text-sm font-black text-black" x-text="qris.merchant"></p>
                    </div>
                  </div>
                  <button type="button" @click="copyToClipboard(qris.nmid)" class="border border-brand text-brand hover:bg-brand hover:text-white px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1">
                    <i class="fa-regular fa-copy"></i> Salin NMID
                  </button>
                </div>

                <div class="space-y-1.5 text-xs text-gray-600">
                  <p class="font-bold text-gray-900 text-[11px]">Cara Pembayaran:</p>
                  <ol class="list-decimal list-inside space-y-1 text-[11px] text-gray-600 leading-relaxed">
                    <li>Buka aplikasi Mobile Banking (BCA, Livin, BRImo, BNI) atau E-Wallet (GoPay, OVO, DANA, ShopeePay).</li>
                    <li>Pilih menu <strong class="text-gray-900">Scan QR / QRIS</strong>.</li>
                    <li>Arahkan kamera ke kode QR di samping.</li>
                    <li>Pastikan nama merchant <strong class="text-gray-900">PT BANTERPOOL TELEKOMUNIKASI</strong> dan nominal sesuai.</li>
                    <li>Konfirmasi dan selesaikan pembayaran.</li>
                  </ol>
                </div>
              </div>
            </div>
          </template>

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
    <span>Nomor rekening / kode bayar berhasil disalin!</span>
  </div>

</div>
@endsection
