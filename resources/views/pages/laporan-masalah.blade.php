@extends('layouts.app')

@section('title', 'Laporan Masalah - WiFi Banterpool')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10"
     x-data="{
       selectedCategory: '',
       selectedSubCategory: '',
       fileName: '',
       fileSize: '',
       openHistory: false,
       handleFile(e) {
         const file = e.target.files[0];
         if (file) {
           this.fileName = file.name;
           const kb = (file.size / 1024);
           this.fileSize = kb > 1024 ? (kb / 1024).toFixed(2) + ' MB' : Math.round(kb) + ' KB';
         } else {
           this.fileName = '';
           this.fileSize = '';
         }
       },
       clearFile() {
         this.fileName = '';
         this.fileSize = '';
         this.$refs.fileInput.value = '';
       }
     }">

  <!-- ALERT NOTIFIKASI JIKA BERHASIL SUBMIT -->
  @if(session('ticket_success'))
    <div class="mb-8 bg-emerald-50 border border-emerald-200 rounded-2xl p-4 sm:p-5 flex items-start justify-between gap-4 shadow-xs"
         x-data="{ show: true }" x-show="show" x-transition>
      <div class="flex items-start gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg shrink-0 mt-0.5">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
          <h4 class="font-extrabold text-emerald-950 text-sm">Laporan Berhasil Terkirim!</h4>
          <p class="text-xs text-emerald-800 mt-0.5">
            Nomor Tiket Anda: <strong class="font-mono bg-emerald-100 px-2 py-0.5 rounded text-emerald-900 font-bold">{{ session('ticket_success')['id'] }}</strong>
          </p>
          <p class="text-[11px] text-emerald-700 mt-1">
            {{ session('ticket_success')['message'] }} Tim teknisi kami akan segera memproses kendala Anda.
          </p>
        </div>
      </div>
      <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 text-sm p-1">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  <!-- HEADER SECTION (Title kiri, Card Bantuan Kanan) -->
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
    
    <!-- Sisi Kiri: Judul Halaman & Subtitle -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-extrabold text-black tracking-tight">Laporan Masalah</h1>
      <p class="text-xs sm:text-sm text-gray-500 mt-1.5 max-w-md leading-relaxed">
        Laporkan kendala yang Anda alami. Tim kami akan segera membantu menyelesaikan masalah Anda.
      </p>
      @if(count($myTickets) > 0)
        <button type="button" @click="openHistory = true"
                class="mt-2.5 inline-flex items-center gap-1.5 text-xs font-semibold text-brand hover:underline">
          <i class="fa-solid fa-clock-rotate-left text-[11px]"></i>
          <span>Lihat Riwayat Laporan ({{ count($myTickets) }})</span>
        </button>
      @endif
    </div>

    <!-- Sisi Kanan: Card 'Butuh bantuan cepat?' -->
    <div class="bg-[#fff5f5] border border-[#fed7d7] rounded-2xl p-4 sm:p-5 flex items-center justify-between gap-4 sm:gap-6 shadow-xs w-full lg:max-w-lg shrink-0">
      
      <div class="flex items-center gap-3.5">
        <!-- Headphone Icon Merah -->
        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
          <svg class="w-8 h-8 text-[#d31818]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 18v-6a9 9 0 0 1 18 0v6"></path>
            <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"></path>
          </svg>
        </div>

        <div>
          <h3 class="font-bold text-black text-xs sm:text-sm">Butuh bantuan cepat?</h3>
          <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5 leading-snug">
            Hubungi kami melalui WhatsApp atau Live Chat 24/7.
          </p>
        </div>
      </div>

      <!-- Tombol Hubungi Kami -->
      <a href="https://wa.me/628818679774?text=Halo%20Admin%20BANTERPOOL,%20saya%20butuh%20bantuan%20cepat%20terkait%20layanan%20WiFi."
         target="_blank"
         class="border border-[#d31818] bg-white hover:bg-red-50 text-[#d31818] font-semibold text-xs py-2 px-3.5 sm:px-4 rounded-xl transition duration-150 flex items-center gap-2 shrink-0 shadow-2xs">
        <i class="fa-brands fa-whatsapp text-sm text-[#d31818]"></i>
        <span>Hubungi Kami</span>
      </a>

    </div>

  </div>

  <!-- CENTER FORM CARD: 'Buat Laporan Masalah Baru' -->
  <div class="max-w-2xl mx-auto mt-8 sm:mt-10">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-[0_4px_25px_-4px_rgba(0,0,0,0.06)] p-6 sm:p-8">
      
      <h2 class="text-base sm:text-lg font-bold text-black mb-6">Buat Laporan Masalah Baru</h2>

      <form action="{{ route('laporan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5 text-xs sm:text-sm">
        @csrf

        <!-- 1. Kategori Masalah -->
        <div>
          <label for="category" class="block font-semibold text-gray-800 mb-1.5 text-xs sm:text-sm">Kategori Masalah</label>
          <div class="relative">
            <select id="category" name="category" required x-model="selectedCategory"
                    class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 sm:py-3 px-3.5 pr-10 text-xs sm:text-sm text-gray-700 focus:border-[#d31818] focus:ring-[#d31818] transition">
              <option value="" disabled selected>Pilih Kategori Masalah</option>
              <option value="Koneksi Internet Lambat">Koneksi Internet Lambat</option>
              <option value="Lampu Indikator LOS Merah">Lampu Indikator LOS Merah</option>
              <option value="WiFi Terhubung Tanpa Internet">WiFi Terhubung Tanpa Internet</option>
              <option value="Kabel Fiber Optik Putus / Fisik">Kabel Fiber Optik Putus / Fisik</option>
              <option value="Router / Modem Mati Total">Router / Modem Mati Total</option>
              <option value="Kendala Tagihan / Pembayaran">Kendala Tagihan / Pembayaran</option>
              <option value="Lainnya">Lainnya</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
              <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
          </div>
        </div>

        <!-- 2. Sub Kategori (Opsional) -->
        <div>
          <label for="sub_category" class="block font-semibold text-gray-800 mb-1.5 text-xs sm:text-sm">
            Sub Kategori <span class="text-gray-400 font-normal">(Opsional)</span>
          </label>
          <div class="relative">
            <select id="sub_category" name="sub_category" x-model="selectedSubCategory"
                    class="w-full appearance-none rounded-xl border border-gray-200 bg-white py-2.5 sm:py-3 px-3.5 pr-10 text-xs sm:text-sm text-gray-700 focus:border-[#d31818] focus:ring-[#d31818] transition">
              <option value="" selected>Pilih Sub Kategori</option>
              <option value="LOS Merah Berkedip">LOS Merah Berkedip</option>
              <option value="Kecepatan Drop Jauh">Kecepatan Drop Jauh</option>
              <option value="Ping Tinggi / Lag Game Online">Ping Tinggi / Lag Game Online</option>
              <option value="Sering Request Time Out (RTO)">Sering Request Time Out (RTO)</option>
              <option value="SSID WiFi Hilang / Tidak Terdeteksi">SSID WiFi Hilang / Tidak Terdeteksi</option>
              <option value="Router Panas / Mati Total">Router Panas / Mati Total</option>
              <option value="Kabel Terjepit / Tertimpa Dahan">Kabel Terjepit / Tertimpa Dahan</option>
              <option value="Lainnya">Lainnya</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-400">
              <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
          </div>
        </div>

        <!-- 3. Deskripsi Masalah -->
        <div>
          <label for="description" class="block font-semibold text-gray-800 mb-1.5 text-xs sm:text-sm">Deskripsi Masalah</label>
          <textarea id="description" name="description" rows="4" required
                    placeholder="Jelaskan masalah yang Anda alami secara detail..."
                    class="w-full rounded-xl border border-gray-200 bg-white p-3.5 text-xs sm:text-sm text-gray-700 focus:border-[#d31818] focus:ring-[#d31818] transition resize-none"></textarea>
          <span class="text-[11px] sm:text-xs text-gray-400 mt-1.5 block">
            Mohon berikan informasi selengkap mungkin agar kami dapat membantu lebih cepat.
          </span>
        </div>

        <!-- 4. Lampiran (Opsional) -->
        <div>
          <label class="block font-semibold text-gray-800 mb-1.5 text-xs sm:text-sm">
            Lampiran <span class="text-gray-400 font-normal">(Opsional)</span>
          </label>

          <!-- Dropzone Container -->
          <div @click="$refs.fileInput.click()"
               class="border border-gray-200 rounded-xl bg-[#fafafa] hover:bg-gray-50 hover:border-gray-300 transition duration-150 cursor-pointer p-6 text-center group">
            
            <input type="file" name="attachment" x-ref="fileInput" @change="handleFile($event)"
                   accept=".jpg,.jpeg,.png,.pdf" class="hidden">

            <!-- Tampilan Saat File Belum Dipilih -->
            <div x-show="!fileName" class="space-y-1.5">
              <!-- Icon Upload Merah -->
              <div class="w-8 h-8 mx-auto text-[#d31818] flex items-center justify-center">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"></path>
                  <path d="M12 12v9"></path>
                  <path d="m8 16 4-4 4 4"></path>
                </svg>
              </div>
              <p class="text-xs sm:text-sm text-gray-700 font-medium">Klik untuk upload atau drag & drop file di sini</p>
              <p class="text-[11px] text-gray-400">Format: JPG, PNG, PDF (Maks. 5MB)</p>
            </div>

            <!-- Tampilan Setelah File Dipilih -->
            <div x-show="fileName" style="display: none;" class="flex items-center justify-center gap-3 py-1">
              <div class="w-9 h-9 rounded-lg bg-red-50 text-[#d31818] flex items-center justify-center text-sm shrink-0">
                <i class="fa-solid fa-file-arrow-up"></i>
              </div>
              <div class="text-left max-w-xs truncate">
                <p class="text-xs font-semibold text-gray-900 truncate" x-text="fileName"></p>
                <p class="text-[10px] text-gray-400" x-text="fileSize"></p>
              </div>
              <button type="button" @click.stop="clearFile()" class="text-gray-400 hover:text-red-600 p-1.5 rounded-lg transition ml-2">
                <i class="fa-solid fa-trash-can text-xs"></i>
              </button>
            </div>

          </div>
        </div>

        <!-- 5. Tombol Submit 'Kirim Laporan' -->
        <div class="pt-2">
          <button type="submit"
                  class="w-full bg-[#d31818] hover:bg-[#b01010] text-white font-bold text-xs sm:text-sm py-3 px-4 rounded-xl shadow-xs transition duration-200 flex items-center justify-center gap-2">
            <i class="fa-solid fa-paper-plane text-xs"></i>
            <span>Kirim Laporan</span>
          </button>
        </div>

      </form>

    </div>
  </div>

  <!-- SECTION: INFORMASI PENTING -->
  <div class="mt-16 sm:mt-20 pt-6">
    <h3 class="text-xs sm:text-sm font-bold text-black mb-6">Informasi Penting</h3>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
      
      <!-- 1. Respon Cepat -->
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-full bg-[#fff5f5] text-[#d31818] border border-[#fed7d7] flex items-center justify-center text-lg shrink-0">
          <i class="fa-regular fa-clock"></i>
        </div>
        <div>
          <h4 class="font-bold text-black text-xs sm:text-sm">Respon Cepat</h4>
          <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-relaxed">
            Laporan Anda akan kami respon maksimal dalam 1x24 jam.
          </p>
        </div>
      </div>

      <!-- 2. Notifikasi Email -->
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-[#fff5f5] text-[#d31818] border border-[#fed7d7] flex items-center justify-center text-lg shrink-0">
          <i class="fa-regular fa-envelope"></i>
        </div>
        <div>
          <h4 class="font-bold text-black text-xs sm:text-sm">Notifikasi Email</h4>
          <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-relaxed">
            Update status laporan akan dikirimkan ke email Anda secara berkala.
          </p>
        </div>
      </div>

      <!-- 3. Data Aman -->
      <div class="flex items-start gap-4">
        <div class="w-12 h-12 rounded-2xl bg-[#fff5f5] text-[#d31818] border border-[#fed7d7] flex items-center justify-center text-lg shrink-0">
          <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
          <h4 class="font-bold text-black text-xs sm:text-sm">Data Aman</h4>
          <p class="text-[11px] sm:text-xs text-gray-500 mt-1 leading-relaxed">
            Kami menjaga kerahasiaan data dan laporan Anda dengan aman.
          </p>
        </div>
      </div>

    </div>
  </div>

  <!-- SLIDE-OVER DRAWER RIWAYAT TIKET PELANGGAN -->
  <div x-show="openHistory" style="display: none;" class="relative z-50" aria-labelledby="slide-over-title" role="dialog" aria-modal="true">
    <div x-show="openHistory"
         x-transition:enter="ease-in-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in-out duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-500 bg-opacity-40 transition-opacity"
         @click="openHistory = false"></div>

    <div class="fixed inset-0 overflow-hidden">
      <div class="absolute inset-0 overflow-hidden">
        <div class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10">
          <div x-show="openHistory"
               x-transition:enter="transform transition ease-in-out duration-300"
               x-transition:enter-start="translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transform transition ease-in-out duration-300"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="translate-x-full"
               class="pointer-events-auto w-screen max-w-md bg-white shadow-xl flex flex-col">
            
            <div class="p-5 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-black" id="slide-over-title">Riwayat Laporan Tiket</h3>
                <p class="text-[11px] text-gray-400">Pantau proses perbaikan kendala dari tim NOC</p>
              </div>
              <button type="button" @click="openHistory = false" class="text-gray-400 hover:text-black p-1.5 rounded-lg">
                <i class="fa-solid fa-xmark text-sm"></i>
              </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5 space-y-4 text-xs">
              @forelse($myTickets as $ticket)
                <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 space-y-2.5">
                  <div class="flex items-center justify-between">
                    <span class="font-mono font-bold text-gray-500 text-[11px]">{{ $ticket['id'] }}</span>
                    @if($ticket['status'] === 'Selesai')
                      <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Selesai
                      </span>
                    @elseif($ticket['status'] === 'Sedang Ditangani')
                      <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Sedang Ditangani
                      </span>
                    @else
                      <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        Menunggu Respon
                      </span>
                    @endif
                  </div>

                  <div>
                    <h5 class="font-bold text-black text-xs">{{ $ticket['type'] }}</h5>
                    <p class="text-[11px] text-gray-400 mt-0.5">{{ $ticket['created_at'] }}</p>
                  </div>

                  <p class="text-gray-600 text-xs leading-relaxed bg-white p-2.5 rounded-xl border border-gray-100">
                    {{ $ticket['description'] }}
                  </p>

                  @if(!empty($ticket['attachment_url']))
                    <div class="mt-2 pt-2 border-t border-gray-100">
                      <span class="text-[10px] font-semibold text-gray-500 block mb-1">Foto Bukti Terlampir:</span>
                      @if(($ticket['attachment_type'] ?? '') === 'pdf')
                        <a href="{{ $ticket['attachment_url'] }}" target="_blank"
                           class="inline-flex items-center gap-1.5 text-xs text-slate-700 bg-white border border-gray-200 px-2.5 py-1.5 rounded-xl hover:border-brand transition">
                          <i class="fa-solid fa-file-pdf text-red-500"></i>
                          <span>{{ $ticket['attachment'] ?? 'Dokumen PDF' }}</span>
                        </a>
                      @else
                        <a href="{{ $ticket['attachment_url'] }}" target="_blank"
                           class="inline-flex items-center gap-2 bg-white border border-gray-200 p-1.5 rounded-xl hover:border-brand transition group">
                          <img src="{{ $ticket['attachment_url'] }}" alt="Bukti" class="w-10 h-10 rounded-lg object-cover border border-gray-200">
                          <div>
                            <span class="text-[11px] text-gray-900 font-semibold block group-hover:text-brand transition">{{ $ticket['attachment'] ?? 'Foto Bukti' }}</span>
                            <span class="text-[10px] text-brand flex items-center gap-1">
                              <span>Buka Foto Asli</span>
                              <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                            </span>
                          </div>
                        </a>
                      @endif
                    </div>
                  @endif

                  @if(!empty($ticket['technician']))
                    <p class="text-[11px] text-brand font-medium">
                      <i class="fa-solid fa-user-gear mr-1"></i> Teknisi: {{ $ticket['technician'] }}
                    </p>
                  @endif
                </div>
              @empty
                <div class="text-center py-12 text-gray-400">
                  <i class="fa-solid fa-inbox text-3xl mb-2 text-gray-300"></i>
                  <p>Belum ada riwayat laporan gangguan.</p>
                </div>
              @endforelse
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection

