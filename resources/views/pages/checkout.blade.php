@extends('layouts.app')

@section('title', 'WiFi Banterpool - Konfirmasi Pemesanan')

@section('content')
<div x-data="checkoutApp()" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 relative">

  <!-- Tombol Kembali -->
  <a href="{{ route('paket') }}" class="inline-flex items-center gap-2 text-brand font-bold text-sm mb-6 hover:underline">
    <i class="fa-solid fa-arrow-left"></i> Kembali
  </a>

  <!-- Header Section -->
  <div class="mb-8">
    <h1 class="text-2xl sm:text-3xl font-black text-black">Lengkapi Pemesanan</h1>
    <p class="text-xs sm:text-sm text-gray-500 mt-1">Langkah terakhir untuk menikmati internet super cepat dari BANTERPOOL</p>
  </div>

  <!-- Stepper Indicator -->
  <div class="flex items-center justify-between max-w-2xl mb-10 text-xs">
    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold">
        <i class="fa-solid fa-check text-xs"></i>
      </div>
      <div>
        <p class="font-bold text-black">Pilih Paket</p>
        <p class="text-[10px] text-gray-400">{{ request('package_name', 'Paket 20 Mbps') }}</p>
      </div>
    </div>
    <div class="flex-1 h-[2px] bg-brand mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold">
        <i class="fa-solid fa-check text-xs"></i>
      </div>
      <div>
        <p class="font-bold text-black">Data Pelanggan</p>
        <p class="text-[10px] text-gray-400">Lengkap</p>
      </div>
    </div>
    <div class="flex-1 h-[2px] bg-brand mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full bg-brand text-white flex items-center justify-center font-bold text-xs">
        3
      </div>
      <div>
        <p class="font-bold text-black">Konfirmasi</p>
        <p class="text-[10px] text-gray-400">Pembayaran</p>
      </div>
    </div>
    <div class="flex-1 h-[2px] bg-gray-200 mx-3"></div>

    <div class="flex items-center gap-2">
      <div class="w-7 h-7 rounded-full border border-black text-black flex items-center justify-center font-bold text-xs">
        4
      </div>
      <div>
        <p class="font-bold text-gray-400">Selesai</p>
        <p class="text-[10px] text-gray-400">Terhubung</p>
      </div>
    </div>
  </div>

  <!-- Grid Konten Utama -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

    <!-- Sisi Kiri: Detail Pemesanan & Form Konfirmasi -->
    <div class="lg:col-span-8 border border-black rounded-2xl p-6 bg-white space-y-6">

      <!-- Detail Pemesanan Card -->
      <div>
        <h3 class="text-sm font-bold text-black mb-3">Detail Pemesanan</h3>
        <div class="border border-black rounded-xl p-4 bg-white">
          <div class="flex items-start justify-between pb-4 border-b border-gray-200">
            <div class="flex items-center gap-3">
              <i class="fa-solid fa-wifi text-brand text-2xl"></i>
              <div>
                <h4 class="font-black text-black text-base">{{ request('package_name', 'Paket 20 Mbps') }}</h4>
                <p class="text-xs text-gray-500">Internet cepat & stabil untuk kebutuhan Anda</p>
              </div>
            </div>
            <div class="text-right">
              <p class="font-black text-brand text-base">Rp{{ request('package_price', '110.000') }}</p>
              <p class="text-[10px] text-gray-500">/bulan</p>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-3 text-[11px] font-semibold text-gray-700">
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-wifi text-brand"></i> Kecepatan {{ request('package_speed', '20 Mbps') }}
            </div>
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-users text-brand"></i> Unlimited Tanpa FUP
            </div>
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-mobile-screen-button text-brand"></i> Multi Perangkat
            </div>
            <div class="flex items-center gap-1.5">
              <i class="fa-solid fa-circle-check text-brand"></i> Support Prioritas
            </div>
          </div>
        </div>
      </div>

      <!-- Informasi Pelanggan Card (Dinamis) -->
      <div>
        <h3 class="text-sm font-bold text-black mb-3">Informasi Pelanggan</h3>
        <div class="border border-black rounded-xl p-4 bg-white grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">Nama Lengkap</p>
            <p class="font-bold text-black flex items-center gap-2">
              <i class="fa-regular fa-user text-brand"></i> {{ $customerData['name'] ?? 'Nama Pelanggan' }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">Nomor HP</p>
            <p class="font-bold text-black flex items-center gap-2">
              <i class="fa-solid fa-phone text-brand"></i> {{ $customerData['phone'] ?? '08xxxxxxxxxx' }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">E-mail</p>
            <p class="font-bold text-black flex items-center gap-2">
              <i class="fa-regular fa-envelope text-brand"></i> {{ $customerData['email'] ?? 'emailpelanggan@gmail.com' }}
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">Alamat Lengkap</p>
            <div class="font-bold text-black flex items-start gap-2">
              <i class="fa-solid fa-location-dot text-brand mt-0.5 shrink-0"></i>
              <div>
                <span>{{ $customerData['address'] ?? 'Jl. Raya Pernasidi No. 45, Kec. Cilongok, Kab. Banyumas' }}</span>
                @if(!empty($customerData['latitude']) && !empty($customerData['longitude']))
                  <div class="mt-1 flex flex-wrap items-center gap-1.5 text-[10px] font-normal">
                    <span class="inline-flex items-center gap-1 bg-red-50 text-brand px-2 py-0.5 rounded border border-red-200">
                      <i class="fa-solid fa-crosshairs text-[9px]"></i> Titik GPS: {{ $customerData['latitude'] }}, {{ $customerData['longitude'] }}
                    </span>
                    <a href="https://www.google.com/maps?q={{ $customerData['latitude'] }},{{ $customerData['longitude'] }}" target="_blank" class="inline-flex items-center gap-1 text-brand font-bold hover:underline">
                      <span>Buka Maps</span>
                      <i class="fa-solid fa-arrow-up-right-from-square text-[8px]"></i>
                    </a>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Metode & Jadwal Pemasangan Card -->
      <div>
        <h3 class="text-sm font-bold text-black mb-3">Metode & Jadwal Pemasangan</h3>
        <div class="border border-black rounded-xl p-4 bg-white grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">Metode Pemasangan</p>
            <p class="font-bold text-black flex items-center gap-2">
              <i class="fa-solid fa-wrench text-brand"></i> Pemasangan Baru
            </p>
          </div>
          <div>
            <p class="text-gray-400 text-[10px] mb-0.5">Jadwal Pemasangan</p>
            <p class="font-bold text-black flex items-center gap-2">
              <i class="fa-regular fa-calendar text-brand"></i>
              @if(!empty($customerData['installation_date']))
                {{ \Carbon\Carbon::parse($customerData['installation_date'])->translatedFormat('l, d F Y') }}
              @else
                Selasa, 28 Agustus 2026
              @endif
              <span class="text-gray-500 font-normal ml-2">
                {{ ($customerData['installation_time'] ?? '') == 'pagi' ? '08:00 - 12:00 WIB' : '13:00 - 16:00 WIB' }}
              </span>
            </p>
          </div>
        </div>
      </div>

      <!-- Checkbox & Tombol Bayar -->
      <div class="pt-2">
        <div class="rounded-xl transition-all duration-300"
             :class="hasError ? 'p-3.5 bg-red-50/90 border border-brand/50 ring-2 ring-brand/20 shadow-sm' : 'py-1'">
          
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <label class="flex items-start sm:items-center gap-2.5 text-xs text-gray-700 cursor-pointer select-none">
              <input type="checkbox" 
                     id="termsCheckbox"
                     x-model="agreed" 
                     @change="if(agreed) hasError = false"
                     class="rounded border-gray-400 text-brand focus:ring-brand w-4 h-4 mt-0.5 sm:mt-0 cursor-pointer">
              <span>Saya telah membaca dan setuju dengan <a href="javascript:void(0)" @click.prevent="openTermsModal = true" class="text-brand font-semibold hover:underline">Syarat & Ketentuan</a> serta <a href="javascript:void(0)" @click.prevent="openPrivacyModal = true" class="text-brand font-semibold hover:underline">Kebijakan Privasi</a></span>
            </label>

            <!-- Tombol Bayar Sekarang -->
            <button type="button" 
                    id="btnBayarSekarang"
                    @click="handlePayment()" 
                    class="w-full sm:w-auto shrink-0 bg-brand hover:bg-brand-700 text-white font-bold px-8 py-3 rounded-xl text-xs transition text-center shadow-sm cursor-pointer active:scale-95">
              Bayar Sekarang
            </button>
          </div>

          <!-- Pesan Peringatan Inline jika belum centang -->
          <div x-show="hasError" 
               x-transition:enter="ease-out duration-200"
               x-transition:enter-start="opacity-0 -translate-y-1"
               x-transition:enter-end="opacity-100 translate-y-0"
               class="flex items-center gap-2 text-brand text-xs font-semibold mt-2.5 pt-2 border-t border-red-200" 
               style="display: none;">
            <i class="fa-solid fa-circle-exclamation text-sm shrink-0"></i>
            <span>Harap klik kotak persetujuan terlebih dahulu agar bisa bayar sekarang.</span>
          </div>

        </div>
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

          <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20butuh%20bantuan%20terkait%20pemesanan%20paket%20WiFi." target="_blank" class="w-full border border-brand text-brand font-bold py-2.5 rounded-xl text-xs flex items-center justify-center gap-2 hover:bg-brand hover:text-white transition">
            <i class="fa-solid fa-headset"></i> Hubungi Kami
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal Notifikasi Peringatan Jika Belum Centang Persetujuan -->
  <template x-teleport="body">
    <div x-show="showWarningModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">

      <div @click.away="showWarningModal = false" 
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="my-auto bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 text-center relative shadow-2xl border border-gray-100">
        
        <!-- Icon Peringatan -->
        <div class="w-16 h-16 rounded-full bg-red-50 border-2 border-brand/20 text-brand flex items-center justify-center mx-auto mb-4 text-3xl shadow-sm">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h3 class="text-lg sm:text-xl font-black text-black mb-2">Persetujuan Diperlukan</h3>
        
        <p class="text-xs text-gray-600 leading-relaxed mb-6">
          Harap klik dan centang kotak <br>
          <span class="font-bold text-black bg-gray-100 px-2 py-1 rounded inline-block my-1 border border-gray-200">
            "Saya telah membaca dan setuju dengan Syarat & Ketentuan serta Kebijakan Privasi"
          </span><br>
          terlebih dahulu agar bisa melanjutkan ke pembayaran.
        </p>

        <div class="flex flex-col sm:flex-row gap-3">
          <button type="button" 
                  @click="agreeAndProceed()" 
                  class="flex-1 bg-brand hover:bg-brand-700 text-white font-bold py-3 px-4 rounded-xl text-xs transition shadow-sm cursor-pointer flex items-center justify-center gap-1.5">
            <i class="fa-solid fa-check"></i> Setujui & Bayar Sekarang
          </button>
          <button type="button" 
                  @click="closeWarningModal()" 
                  class="flex-1 border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold py-3 px-4 rounded-xl text-xs transition cursor-pointer">
            Saya Paham
          </button>
        </div>

      </div>
    </div>
  </template>

  <!-- Modal Syarat & Ketentuan -->
  <template x-teleport="body">
    <div x-show="openTermsModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">
      <div @click.away="openTermsModal = false" class="my-auto bg-white rounded-3xl max-w-lg w-full p-6 relative shadow-2xl text-xs text-gray-700">
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
          <h3 class="text-base font-black text-black flex items-center gap-2">
            <i class="fa-solid fa-file-contract text-brand"></i> Syarat & Ketentuan Layanan
          </h3>
          <button type="button" @click="openTermsModal = false" class="text-gray-400 hover:text-black font-bold text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="space-y-3 max-h-64 overflow-y-auto pr-2 text-gray-600 leading-relaxed">
          <p>1. <strong>Layanan Internet:</strong> Layanan internet BANTERPOOL disediakan dengan kecepatan bandwidth simetris sesuai paket berlangganan tanpa pembatasan kuota (Unlimited tanpa FUP).</p>
          <p>2. <strong>Pemasangan & Aktivasi:</strong> Teknisi akan melakukan instalasi perangkat fiber optik dan router di lokasi yang telah ditentukan sesuai jadwal.</p>
          <p>3. <strong>Pembayaran:</strong> Pembayaran tagihan bulanan dilakukan tepat waktu sesuai tanggal jatuh tempo yang tertera pada faktur/invoice.</p>
          <p>4. <strong>Perangkat:</strong> Modem ONT dan adaptor merupakan inventaris pinjaman selama masa berlangganan aktif.</p>
          <p>5. <strong>Dukungan Pelanggan:</strong> Dukungan teknis siap melayani 24/7 jika pelanggan mengalami kendala koneksi.</p>
        </div>
        <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end gap-2">
          <button type="button" @click="agreed = true; hasError = false; openTermsModal = false;" class="bg-brand hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
            Saya Setuju
          </button>
          <button type="button" @click="openTermsModal = false" class="border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-xs transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </template>

  <!-- Modal Kebijakan Privasi -->
  <template x-teleport="body">
    <div x-show="openPrivacyModal" 
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[100] overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none;">
      <div @click.away="openPrivacyModal = false" class="my-auto bg-white rounded-3xl max-w-lg w-full p-6 relative shadow-2xl text-xs text-gray-700">
        <div class="flex justify-between items-center mb-4 pb-2 border-b border-gray-100">
          <h3 class="text-base font-black text-black flex items-center gap-2">
            <i class="fa-solid fa-shield-halved text-brand"></i> Kebijakan Privasi
          </h3>
          <button type="button" @click="openPrivacyModal = false" class="text-gray-400 hover:text-black font-bold text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="space-y-3 max-h-64 overflow-y-auto pr-2 text-gray-600 leading-relaxed">
          <p>1. <strong>Keamanan Data:</strong> Kami menghormati privasi Anda dan berkomitmen menjaga kerahasiaan data pribadi pelanggan sesuai regulasi perlindungan data yang berlaku.</p>
          <p>2. <strong>Pemanfaatan Informasi:</strong> Informasi nama lengkap, nomor HP, email, alamat, dan koordinat GPS hanya digunakan untuk kebutuhan pemasangan, verifikasi teknis, serta komunikasi tagihan layanan.</p>
          <p>3. <strong>Perlindungan Informasi:</strong> BANTERPOOL tidak akan memperjualbelikan atau memberikan data Anda kepada pihak ketiga manapun untuk keperluan komersial tanpa izin tertulis Anda.</p>
        </div>
        <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end gap-2">
          <button type="button" @click="agreed = true; hasError = false; openPrivacyModal = false;" class="bg-brand hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl text-xs transition">
            Saya Setuju
          </button>
          <button type="button" @click="openPrivacyModal = false" class="border border-gray-300 hover:bg-gray-100 text-gray-700 font-bold px-4 py-2.5 rounded-xl text-xs transition">
            Tutup
          </button>
        </div>
      </div>
    </div>
  </template>

</div>
@endsection

@push('scripts')
<script>
function checkoutApp() {
    return {
        agreed: false,
        hasError: false,
        showWarningModal: false,
        openTermsModal: false,
        openPrivacyModal: false,
        paymentUrl: @json(route('payment', request()->query())),

        handlePayment() {
            if (!this.agreed) {
                this.hasError = true;
                this.showWarningModal = true;
                return;
            }
            window.location.href = this.paymentUrl;
        },

        closeWarningModal() {
            this.showWarningModal = false;
            setTimeout(() => {
                const cb = document.getElementById('termsCheckbox');
                if (cb) {
                    cb.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    cb.focus();
                }
            }, 150);
        },

        agreeAndProceed() {
            this.agreed = true;
            this.hasError = false;
            this.showWarningModal = false;
            window.location.href = this.paymentUrl;
        }
    };
}
</script>
@endpush
