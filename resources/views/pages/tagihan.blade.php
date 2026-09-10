@extends('layouts.app')

@section('title', 'WiFi Banterpool - Tagihan Saya')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
     x-data="{
       openDetail: false,
       openReceipt: false,
       selectedReceipt: null,
       selectedStatus: 'all',
       selectedPeriod: 'all',
       historyBills: {{ Js::from($historyBills) }},
       get filteredBills() {
         return this.historyBills.filter(bill => {
           const matchStatus = this.selectedStatus === 'all' || bill.status === this.selectedStatus;
           const matchPeriod = this.selectedPeriod === 'all' || bill.year === this.selectedPeriod;
           return matchStatus && matchPeriod;
         });
       },
       viewReceipt(item) {
         this.selectedReceipt = item;
         this.openReceipt = true;
       }
     }">

  <!-- HEADER & SUMMARY CARD -->
  <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">

    <!-- Header Text (Sisi Kiri) -->
    <div>
      <h1 class="text-2xl sm:text-3xl font-black text-black tracking-tight">Tagihan Saya</h1>
      <p class="text-xs sm:text-sm text-gray-500 mt-1.5 max-w-lg">
        Kelola dan lihat semua tagihan serta riwayat pembayaran layanan internet Anda di sini.
      </p>
    </div>

    <!-- Card Ringkasan Tagihan Belum Dibayar (Sisi Kanan) -->
    @if($activeBill['has_unpaid'] ?? true)
      <div class="bg-[#fff1f1] border border-[#fecaca] rounded-2xl p-5 sm:p-6 flex items-center justify-between gap-6 shadow-sm w-full lg:max-w-md shrink-0">
        <div class="flex items-center gap-3.5">
          <!-- Ilustrasi Smartphone & Kartu Pembayaran -->
          <div class="w-14 h-14 relative shrink-0 flex items-center justify-center">
            <svg class="w-14 h-14" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
              <!-- Smartphone outline -->
              <rect x="11" y="7" width="22" height="38" rx="4" fill="#ffffff" stroke="#262626" stroke-width="1.8"/>
              <!-- Screen area -->
              <rect x="14" y="12" width="16" height="24" rx="2" fill="#ef4444"/>
              <text x="22" y="28" text-anchor="middle" fill="#ffffff" font-size="12" font-weight="bold" font-family="sans-serif">$</text>
              <rect x="19" y="9" width="6" height="1.5" rx="0.75" fill="#9ca3af"/>
              <!-- Overlapping Payment Card -->
              <rect x="20" y="23" width="25" height="17" rx="3" fill="#fde047" stroke="#262626" stroke-width="1.8"/>
              <line x1="20" y1="28" x2="45" y2="28" stroke="#262626" stroke-width="1.5"/>
              <rect x="23" y="32" width="5" height="4" rx="1" fill="#ca8a04"/>
            </svg>
          </div>

          <!-- Rincian Jumlah -->
          <div>
            <p class="text-xs text-gray-700 font-semibold">Total Tagihan Belum Dibayar</p>
            <h3 class="text-2xl sm:text-3xl font-black text-brand tracking-tight my-0.5">Rp{{ $activeBill['total'] }}</h3>
            <p class="text-[11px] text-gray-500">1 tagihan belum dibayar</p>
          </div>
        </div>

        <!-- Tombol Bayar Cepat -->
        <a href="{{ route('tagihan.payment', ['total' => $activeBill['total_raw'], 'package' => $activeBill['package_name'], 'invoice' => $activeBill['id']]) }}"
           class="border border-brand text-brand hover:bg-brand hover:text-white font-bold text-xs px-5 py-2.5 rounded-xl transition duration-200 shrink-0 text-center">
          Bayar Sekarang
        </a>
      </div>
    @else
      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 sm:p-6 flex items-center justify-between gap-6 shadow-sm w-full lg:max-w-md shrink-0">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-check text-2xl"></i>
          </div>
          <div>
            <p class="text-xs text-emerald-800 font-semibold">Semua Tagihan Lunas</p>
            <h3 class="text-xl sm:text-2xl font-black text-emerald-700 tracking-tight my-0.5">Tidak Ada Tunggakan</h3>
            <p class="text-[11px] text-emerald-600">Terima kasih atas pembayaran tepat waktu</p>
          </div>
        </div>
      </div>
    @endif

  </div>

  <!-- SECTION: TAGIHAN BELUM DIBAYAR -->
  <div>
    <h2 class="text-sm sm:text-base font-bold text-black mb-3">Tagihan Belum Dibayar</h2>

    @if($activeBill['has_unpaid'] ?? true)
      <div class="border border-gray-300 rounded-2xl p-5 sm:p-6 bg-white flex flex-col md:flex-row md:items-center justify-between gap-5 shadow-sm hover:border-gray-400 transition">
        
        <!-- Sisi Kiri: Ikon & Detail Paket -->
        <div class="flex items-start sm:items-center gap-4">
          <div class="w-13 h-13 sm:w-14 sm:h-14 rounded-full bg-[#fee2e2] flex items-center justify-center shrink-0">
            <i class="fa-solid fa-wifi text-brand text-xl sm:text-2xl"></i>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base font-bold text-black">{{ $activeBill['package_name'] }}</h3>
              <span class="text-[10px] font-mono text-gray-400">({{ $activeBill['id'] }})</span>
            </div>
            <p class="text-xs text-gray-500 mt-0.5 mb-2.5">{{ $activeBill['period'] }}</p>
            <span class="inline-block bg-[#fff1f1] text-brand border border-red-200 text-[11px] font-semibold px-3 py-1 rounded-md">
              Jatuh tempo {{ $activeBill['due_date'] }}
            </span>
          </div>
        </div>

        <!-- Sisi Kanan: Total & Aksi -->
        <div class="flex flex-col md:items-end gap-3 shrink-0">
          <div class="text-left md:text-right">
            <p class="text-[11px] text-gray-400">Total Tagihan</p>
            <p class="text-xl sm:text-2xl font-black text-brand mt-0.5">Rp{{ $activeBill['total'] }}</p>
          </div>
          <div class="flex items-center gap-2.5">
            <button type="button" @click="openDetail = true"
                    class="border border-brand text-brand hover:bg-red-50 text-xs font-bold px-5 py-2 rounded-xl transition">
              Lihat Detail
            </button>
            <a href="{{ route('tagihan.payment', ['total' => $activeBill['total_raw'], 'package' => $activeBill['package_name'], 'invoice' => $activeBill['id']]) }}"
               class="bg-brand hover:bg-brand-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition shadow-sm">
              Bayar Sekarang
            </a>
          </div>
        </div>

      </div>
    @else
      <div class="border border-emerald-200 rounded-2xl p-6 bg-white flex items-center gap-4 shadow-sm">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-shield-check text-2xl"></i>
        </div>
        <div>
          <h4 class="font-bold text-sm text-emerald-900">Layanan Aktif & Tidak Ada Tagihan Tertunggak</h4>
          <p class="text-xs text-gray-500 mt-1">
            Semua tagihan layanan internet Anda sudah terbayar. Tagihan berikutnya akan diterbitkan menjelang periode berikutnya.
          </p>
        </div>
      </div>
    @endif
  </div>

  <!-- SECTION: RIWAYAT TAGIHAN -->
  <div>

    <!-- Baris Judul & Filter Dropdown -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
      <h2 class="text-sm sm:text-base font-bold text-black">Riwayat Tagihan</h2>

      <div class="flex items-center gap-2.5">
        <!-- Filter Status -->
        <div class="relative">
          <select x-model="selectedStatus"
                  class="appearance-none border border-gray-400 rounded-xl pl-4 pr-9 py-2 text-xs font-semibold text-gray-700 bg-white hover:border-black transition focus:outline-none focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all">Semua Status</option>
            <option value="Lunas">Lunas</option>
            <option value="Belum Dibayar">Belum Dibayar</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px] pointer-events-none"></i>
        </div>

        <!-- Filter Periode -->
        <div class="relative">
          <select x-model="selectedPeriod"
                  class="appearance-none border border-gray-400 rounded-xl pl-4 pr-9 py-2 text-xs font-semibold text-gray-700 bg-white hover:border-black transition focus:outline-none focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all">Semua Periode</option>
            <option value="2026">Tahun 2026</option>
            <option value="2025">Tahun 2025</option>
          </select>
          <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 text-[10px] pointer-events-none"></i>
        </div>
      </div>
    </div>

    <!-- Tabel Riwayat Tagihan -->
    <div class="border border-gray-400/80 rounded-2xl overflow-hidden bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="bg-[#f3f4f6] text-gray-700 text-[11px] font-bold uppercase tracking-wider border-b border-gray-200">
              <th class="py-3.5 px-5">PERIODE</th>
              <th class="py-3.5 px-5">PAKET</th>
              <th class="py-3.5 px-5">TANGGAL TAGIHAN</th>
              <th class="py-3.5 px-5">JATUH TEMPO</th>
              <th class="py-3.5 px-5">TOTAL</th>
              <th class="py-3.5 px-5 text-center">STATUS</th>
              <th class="py-3.5 px-5 text-center">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 text-xs">
            <template x-for="item in filteredBills" :key="item.id">
              <tr class="hover:bg-gray-50/70 transition font-medium text-gray-800">
                <td class="py-3.5 px-5 font-semibold" x-text="item.period"></td>
                <td class="py-3.5 px-5" x-text="item.package_name"></td>
                <td class="py-3.5 px-5 text-gray-600" x-text="item.bill_date"></td>
                <td class="py-3.5 px-5 text-gray-600" x-text="item.due_date"></td>
                <td class="py-3.5 px-5 font-bold text-black" x-text="item.total"></td>
                <td class="py-3.5 px-5 text-center">
                  <span class="inline-block bg-[#dcfce7] text-[#16a34a] text-[10px] font-bold px-3 py-0.5 rounded-full" x-text="item.status"></span>
                </td>
                <td class="py-3.5 px-5 text-center">
                  <button type="button" @click="viewReceipt(item)"
                          class="text-gray-400 hover:text-brand transition p-1 text-xs font-semibold"
                          title="Lihat Bukti Pembayaran">
                    <i class="fa-regular fa-file-lines text-sm"></i>
                  </button>
                </td>
              </tr>
            </template>
            <template x-if="filteredBills.length === 0">
              <tr>
                <td colspan="7" class="py-8 text-center text-gray-400 text-xs">
                  Tidak ada data riwayat tagihan untuk filter yang dipilih.
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- SECTION: BANNER INFORMASI KETENTUAN TAGIHAN (BAGIAN BAWAH) -->
  <div class="bg-[#fff1f1] border border-[#fecaca] rounded-2xl p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-center">

    <!-- Kolom 1: Tagihan Dibuat (dengan ikon invoice/tagihan merah) -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-brand shrink-0">
        <svg class="w-5 h-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
          <polyline points="14 2 14 8 20 8"/>
          <line x1="16" y1="13" x2="8" y2="13"/>
          <line x1="16" y1="17" x2="8" y2="17"/>
          <line x1="10" y1="9" x2="8" y2="9"/>
        </svg>
      </div>
      <div>
        <h4 class="font-bold text-xs text-black">Tagihan Dibuat</h4>
        <p class="text-[11px] text-gray-600 mt-1">Setiap Tanggal 28 setiap bulannya</p>
      </div>
    </div>

    <!-- Kolom 2: Jatuh Tempo (dengan ikon jam merah) -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-brand shrink-0">
        <svg class="w-5 h-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/>
          <path d="M3 3v5h5"/>
          <path d="M12 7v5l3 3"/>
        </svg>
      </div>
      <div>
        <h4 class="font-bold text-xs text-black">Jatuh Tempo</h4>
        <p class="text-[11px] text-gray-600 mt-1">Pembayaran jatuh tempo pada tanggal 5 setiap bulan</p>
      </div>
    </div>

    <!-- Kolom 3: Pengingat (dengan ikon lonceng/notifikasi merah) -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-brand shrink-0">
        <svg class="w-5 h-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
          <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
        </svg>
      </div>
      <div>
        <h4 class="font-bold text-xs text-black">Pengingat</h4>
        <p class="text-[11px] text-gray-600 mt-1">Kami akan mengirim pengingat sebelum jatuh tempo</p>
      </div>
    </div>

    <!-- Kolom 4: Keamanan Terjamin -->
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full border-2 border-brand flex items-center justify-center text-brand shrink-0">
        <svg class="w-5 h-5 text-brand" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
      </div>
      <div>
        <h4 class="font-bold text-xs text-black">Keamanan Terjamin</h4>
        <p class="text-[11px] text-gray-600 mt-1">Transaksi aman dengan enkripsi berstandar tinggi</p>
      </div>
    </div>

  </div>

  <!-- MODAL: DETAIL TAGIHAN AKTIF -->
  <div x-show="openDetail"
       x-transition:enter="ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
       style="display: none;">

    <div @click.away="openDetail = false"
         class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 relative shadow-2xl">

      <!-- Tombol Close (X) -->
      <button type="button" @click="openDetail = false"
              class="absolute top-6 right-6 text-gray-500 hover:text-red-600 text-xl font-bold">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-full bg-red-100 text-brand flex items-center justify-center">
          <i class="fa-solid fa-file-invoice text-lg"></i>
        </div>
        <div>
          <h3 class="text-base font-black text-black">Detail Tagihan</h3>
          <p class="text-xs text-gray-400 font-mono">{{ $activeBill['id'] }}</p>
        </div>
      </div>

      <!-- Info Pelanggan & Layanan -->
      <div class="space-y-3 text-xs border-y border-gray-100 py-4">
        <div class="flex justify-between">
          <span class="text-gray-500">Layanan</span>
          <span class="font-bold text-black">{{ $activeBill['package_name'] }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Periode</span>
          <span class="font-semibold text-black">{{ $activeBill['period'] }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Tanggal Tagihan</span>
          <span class="font-semibold text-black">{{ $activeBill['bill_date'] }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Jatuh Tempo</span>
          <span class="font-semibold text-brand">{{ $activeBill['due_date'] }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Status</span>
          <span class="bg-red-50 text-brand border border-red-200 px-2.5 py-0.5 rounded-full text-[10px] font-bold">{{ $activeBill['status'] }}</span>
        </div>
      </div>

      <!-- Rincian Biaya -->
      <div class="py-4 space-y-2 text-xs border-b border-gray-100">
        <div class="flex justify-between text-gray-600">
          <span>Biaya Langganan (Internet)</span>
          <span class="font-bold text-black">Rp{{ $activeBill['price'] }}</span>
        </div>
        <div class="flex justify-between text-sm font-black text-black pt-2 border-t border-dashed border-gray-200">
          <span>Total Pembayaran</span>
          <span class="text-brand text-base">Rp{{ $activeBill['total'] }}</span>
        </div>
      </div>

      <!-- Tombol Aksi Modal -->
      <div class="flex items-center gap-3 pt-5">
        <button type="button" @click="openDetail = false"
                class="w-1/2 border border-gray-400 text-black font-bold py-2.5 rounded-xl text-xs hover:bg-gray-100 transition">
          Tutup
        </button>
        <a href="{{ route('tagihan.payment', ['total' => $activeBill['total_raw'], 'package' => $activeBill['package_name'], 'invoice' => $activeBill['id']]) }}"
           class="w-1/2 bg-brand hover:bg-brand-700 text-white font-bold py-2.5 rounded-xl text-xs text-center transition shadow-sm">
          Bayar Sekarang
        </a>
      </div>

    </div>
  </div>

  <!-- MODAL: BUKTI PEMBAYARAN LUNAS -->
  <div x-show="openReceipt"
       x-transition:enter="ease-out duration-300"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-200"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
       style="display: none;">

    <div @click.away="openReceipt = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 relative shadow-2xl">

      <!-- Tombol Close (X) -->
      <button type="button" @click="openReceipt = false"
              class="absolute top-6 right-6 text-gray-500 hover:text-red-600 text-xl font-bold">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="text-center pb-4 border-b border-gray-100">
        <div class="w-12 h-12 rounded-full bg-green-100 text-green-600 mx-auto flex items-center justify-center mb-3">
          <i class="fa-solid fa-circle-check text-2xl"></i>
        </div>
        <h3 class="text-base font-black text-black">Bukti Pembayaran Lunas</h3>
        <p class="text-xs text-gray-400 font-mono mt-0.5" x-text="selectedReceipt?.id"></p>
      </div>

      <div class="py-4 space-y-2.5 text-xs">
        <div class="flex justify-between">
          <span class="text-gray-500">Paket</span>
          <span class="font-bold text-black" x-text="selectedReceipt?.package_name"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Periode Tagihan</span>
          <span class="font-semibold text-black" x-text="selectedReceipt?.period"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Tanggal Bayar</span>
          <span class="font-semibold text-black" x-text="selectedReceipt?.paid_date"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-500">Metode Pembayaran</span>
          <span class="font-semibold text-black" x-text="selectedReceipt?.payment_method"></span>
        </div>
        <div class="flex justify-between text-sm font-black pt-3 border-t border-dashed border-gray-200">
          <span>Jumlah Dibayar</span>
          <span class="text-brand" x-text="selectedReceipt?.total"></span>
        </div>
      </div>

      <div class="pt-4 flex items-center gap-3">
        <button type="button" @click="openReceipt = false"
                class="w-1/2 border border-gray-400 text-black font-bold py-2.5 rounded-xl text-xs hover:bg-gray-100 transition">
          Tutup
        </button>
        <button type="button" @click="window.print()"
                class="w-1/2 bg-gray-900 hover:bg-black text-white font-bold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-print"></i> Cetak Bukti
        </button>
      </div>

    </div>
  </div>

</div>
@endsection
