@extends('layouts.app')

@section('title', 'WiFi Banterpool - Tagihan Berlangganan')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
     x-data="{
       openDetail: false,
       selectedBill: null,
       openReceipt: false,
       selectedReceipt: null,
       historyBills: {{ Js::from($historyBills ?? []) }},

       viewBill(bill) {
         this.selectedBill = bill;
         this.openDetail = true;
       },
       viewReceipt(bill) {
         this.selectedReceipt = bill;
         this.openReceipt = true;
       }
     }">

  <!-- ============================================== -->
  <!-- 1. HEADER & TOMBOL AKSI CEPAT                  -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Tagihan Berlangganan</h1>
        <span class="bg-brand/10 text-brand text-xs font-extrabold px-2.5 py-0.5 rounded-full border border-brand/20">
          Pelanggan
        </span>
      </div>
      <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-xl">
        Pantau status pembayaran invoice dan rincian tagihan layanan internet WiFi Anda secara transparan.
      </p>
    </div>

    <!-- Tombol Aksi Tambahan -->
    <div class="flex items-center gap-2">
      <button type="button" onclick="window.print()"
              class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Laporan</span>
      </button>
      <a href="{{ route('tagihan') }}"
         class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold p-2.5 rounded-xl transition"
         title="Muat Ulang Data Tagihan">
        <i class="fa-solid fa-rotate-right"></i>
      </a>
    </div>
  </div>

  <!-- Banner Mode Pratinjau Admin -->
  @if(!empty($isAdminPreview) && !empty($adminPreviewCustomer))
    <div class="bg-slate-900 text-white p-4 rounded-2xl flex flex-col sm:flex-row items-center justify-between gap-3 shadow-md border border-slate-800 text-xs">
      <div class="flex items-center gap-3">
        <span class="bg-brand text-white px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase tracking-wider flex items-center gap-1 shadow-xs">
          <i class="fa-solid fa-eye"></i> Mode Pratinjau Admin
        </span>
        <span class="text-slate-300">
          Menampilkan POV Pelanggan untuk: <strong class="text-white">{{ $adminPreviewCustomer['name'] }}</strong> ({{ $adminPreviewCustomer['email'] }})
        </span>
      </div>
      @if(count($availablePreviewCustomers ?? []) > 1)
        <form method="GET" action="{{ route('tagihan') }}" class="flex items-center gap-2">
          <span class="text-slate-400 font-medium text-[11px]">Pilih Pelanggan:</span>
          <select name="customer_email" onchange="this.form.submit()" class="bg-slate-800 text-white border border-slate-700 rounded-xl px-3 py-1.5 text-xs font-semibold focus:ring-brand focus:border-brand cursor-pointer">
            @foreach($availablePreviewCustomers as $cust)
              <option value="{{ $cust->customer_email }}" {{ ($adminPreviewCustomer['email'] === $cust->customer_email) ? 'selected' : '' }}>
                {{ $cust->customer_name }} ({{ $cust->customer_email }})
              </option>
            @endforeach
          </select>
        </form>
      @endif
    </div>
  @endif

  <!-- Alert Notifikasi Jika Ada -->
  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('success') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  @if(session('info'))
    <div class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-info text-blue-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('info') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-blue-500 hover:text-blue-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. CARD RINGKASAN STATUS TAGIHAN UTAMA         -->
  <!-- ============================================== -->
  @if($activeBill['has_unpaid'] ?? false)
    <div class="bg-[#fff1f1] border border-[#fecaca] rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xs">
      <div class="flex items-center gap-4">
        <!-- Ilustrasi Smartphone & Kartu Pembayaran -->
        <div class="w-14 h-14 relative shrink-0 flex items-center justify-center">
          <svg class="w-14 h-14" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="11" y="7" width="22" height="38" rx="4" fill="#ffffff" stroke="#262626" stroke-width="1.8"/>
            <rect x="14" y="12" width="16" height="24" rx="2" fill="#ef4444"/>
            <text x="22" y="28" text-anchor="middle" fill="#ffffff" font-size="12" font-weight="bold" font-family="sans-serif">$</text>
            <rect x="19" y="9" width="6" height="1.5" rx="0.75" fill="#9ca3af"/>
            <rect x="20" y="23" width="25" height="17" rx="3" fill="#fde047" stroke="#262626" stroke-width="1.8"/>
            <line x1="20" y1="28" x2="45" y2="28" stroke="#262626" stroke-width="1.5"/>
            <rect x="23" y="32" width="5" height="4" rx="1" fill="#ca8a04"/>
          </svg>
        </div>

        <!-- Rincian Jumlah -->
        <div>
          <div class="flex items-center gap-2">
            <p class="text-xs text-gray-700 font-bold uppercase tracking-wider">Total Tagihan Belum Dibayar</p>
            <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
              Jatuh Tempo {{ $activeBill['due_date'] }}
            </span>
          </div>
          <h3 class="text-2xl sm:text-3xl font-black text-brand tracking-tight my-0.5">Rp{{ $activeBill['total'] }}</h3>
          <p class="text-xs text-gray-500">
            <span>1 tagihan belum dibayar</span> &bull; 
            <span class="font-semibold text-slate-800">{{ $activeBill['package_name'] }}</span> 
            <span class="font-mono text-[11px] text-gray-400">({{ $activeBill['id'] }})</span>
          </p>
        </div>
      </div>

      <!-- Tombol Aksi Bayar & Detail -->
      <div class="flex items-center gap-2.5 shrink-0">
        <button type="button" @click="viewBill(@js($activeBill))"
                class="border border-slate-300 hover:bg-white text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition">
          Lihat Detail
        </button>
        <a href="{{ route('tagihan.payment', ['total' => $activeBill['total_raw'], 'package' => $activeBill['package_name'], 'invoice' => $activeBill['id']]) }}"
           class="bg-brand hover:bg-brand-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition duration-200 shadow-sm flex items-center gap-1.5 text-center">
          <i class="fa-solid fa-credit-card"></i>
          <span>Bayar Sekarang</span>
        </a>
      </div>
    </div>
  @elseif(!empty($inProgressOrder))
    <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xs">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-screwdriver-wrench text-xl"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <p class="text-xs text-blue-800 font-bold uppercase tracking-wider">Proses Pemasangan Berjalan</p>
            <span class="bg-blue-600 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-full">
              {{ $inProgressOrder->status }}
            </span>
          </div>
          <h3 class="text-base sm:text-lg font-bold text-slate-800 tracking-tight my-0.5">
            {{ $inProgressOrder->package_name }} ({{ $inProgressOrder->speed ?? '20 Mbps' }})
          </h3>
          <p class="text-xs text-slate-600">
            Pesanan <span class="font-mono font-semibold text-slate-700">{{ $inProgressOrder->order_number }}</span> sedang diproses. Tagihan bulanan pertama akan otomatis muncul setelah status pesanan selesai dipasang.
          </p>
        </div>
      </div>
      <div class="text-xs text-blue-700 font-medium bg-white border border-blue-200 px-3.5 py-2 rounded-xl shrink-0">
        <i class="fa-solid fa-clock-rotate-left text-blue-600 mr-1"></i>
        Belum ada tagihan aktif untuk pesanan ini.
      </div>
    </div>
  @else
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-6 shadow-xs">
      <div class="flex items-center gap-3.5">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0">
          <i class="fa-solid fa-circle-check text-2xl"></i>
        </div>
        <div>
          <p class="text-xs text-emerald-800 font-semibold uppercase tracking-wider">Semua Tagihan Lunas</p>
          <h3 class="text-xl sm:text-2xl font-black text-emerald-700 tracking-tight my-0.5">Tidak Ada Tunggakan</h3>
          <p class="text-xs text-emerald-600">Terima kasih atas pembayaran tepat waktu. Layanan internet aktif normal.</p>
        </div>
      </div>
      <div class="text-xs text-emerald-700 font-medium bg-white/70 border border-emerald-200 px-3.5 py-2 rounded-xl shrink-0">
        <i class="fa-solid fa-calendar-check text-emerald-600 mr-1"></i>
        Tagihan bulan selanjutnya otomatis terbit setiap jatuh tempo tanggal 05 setelah pembayaran lunas.
      </div>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 3. FILTER TABS & SEARCH BAR (PERSIS ADMIN/DIR) -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
    
    <!-- Status Pills Filter -->
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
      <a href="{{ route('tagihan', ['status' => 'all', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Semua</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['all'] ?? 0 }}</span>
      </a>

      <a href="{{ route('tagihan', ['status' => 'Menunggu Verifikasi', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
        <i class="fa-regular fa-clock"></i>
        <span>Menunggu Verifikasi</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-white/20' : 'bg-amber-200' }}">{{ $counts['menunggu'] ?? 0 }}</span>
      </a>

      <a href="{{ route('tagihan', ['status' => 'Belum Bayar', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Belum Bayar' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Belum Bayar</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Belum Bayar' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['belum_bayar'] ?? 0 }}</span>
      </a>

      <a href="{{ route('tagihan', ['status' => 'Lunas', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Lunas' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
        <i class="fa-solid fa-check"></i>
        <span>Lunas</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Lunas' ? 'bg-white/20' : 'bg-emerald-200' }}">{{ $counts['lunas'] ?? 0 }}</span>
      </a>

      <a href="{{ route('tagihan', ['status' => 'Jatuh Tempo', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jatuh Tempo' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Jatuh Tempo</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Jatuh Tempo' ? 'bg-white/20' : 'bg-red-200' }}">{{ $counts['jatuh_tempo'] ?? 0 }}</span>
      </a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('tagihan') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari invoice, paket, status..."
               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
      </div>
      <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition">
        Cari
      </button>
      @if($search)
        <a href="{{ route('tagihan', ['status' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-500 font-bold">Reset</a>
      @endif
    </form>

  </div>

  <!-- ============================================== -->
  <!-- 4. TABEL TAGIHAN BERLANGGANAN (PERSIS ADMIN/DIR)-->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-center text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
          <tr>
            <th class="px-5 py-3.5 text-center">Invoice & Tanggal</th>
            <th class="px-4 py-3.5 text-center">Paket & Kecepatan</th>
            <th class="px-4 py-3.5 text-center">Periode Berlangganan</th>
            <th class="px-4 py-3.5 text-center">Jatuh Tempo</th>
            <th class="px-4 py-3.5 text-center">Total Tagihan</th>
            <th class="px-4 py-3.5 text-center">Metode Bayar</th>
            <th class="px-4 py-3.5 text-center">Status</th>
            <th class="px-5 py-3.5 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($bills as $bill)
            <tr class="hover:bg-slate-50/70 transition">
              <!-- Invoice & Tanggal -->
              <td class="px-5 py-4 text-center">
                <p class="font-bold text-slate-900 font-mono leading-tight">{{ $bill['id'] }}</p>
                <p class="text-[10px] text-slate-400 mt-0.5">{{ $bill['created_at'] }}</p>
              </td>

              <!-- Paket & Kecepatan -->
              <td class="px-4 py-4 text-center">
                <span class="font-semibold text-slate-800">{{ $bill['package_name'] }}</span>
                <p class="text-[10px] text-slate-500 mt-0.5">
                  <i class="fa-solid fa-wifi text-brand text-[9px]"></i> {{ $bill['speed'] }}
                </p>
              </td>

              <!-- Periode Berlangganan -->
              <td class="px-4 py-4 text-slate-600 text-center font-medium">
                {{ $bill['period'] }}
              </td>

              <!-- Jatuh Tempo -->
              <td class="px-4 py-4 text-slate-700 text-center font-semibold">
                {{ $bill['due_date'] }}
              </td>

              <!-- Total Tagihan -->
              <td class="px-4 py-4 font-black text-slate-900 text-sm text-center">
                Rp{{ $bill['total'] }}
              </td>

              <!-- Metode Bayar -->
              <td class="px-4 py-4 text-center">
                <p class="text-slate-700 font-medium">{{ $bill['payment_method'] }}</p>
                @if(!empty($bill['paid_date']) && $bill['paid_date'] !== '-')
                  <p class="text-[10px] text-emerald-600 font-medium mt-0.5">Lunas: {{ $bill['paid_date'] }}</p>
                @endif
              </td>

              <!-- Status -->
              <td class="px-4 py-4 text-center">
                @if($bill['status'] === 'Lunas')
                  <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    <i class="fa-solid fa-check text-[9px]"></i> Lunas
                  </span>
                @elseif($bill['status'] === 'Menunggu Verifikasi')
                  <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-md animate-pulse">
                    <i class="fa-regular fa-clock text-[9px]"></i> Menunggu Verifikasi
                  </span>
                @elseif($bill['status'] === 'Jatuh Tempo')
                  <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    <i class="fa-solid fa-triangle-exclamation text-[9px]"></i> Jatuh Tempo
                  </span>
                @else
                  <span class="inline-flex items-center gap-1 bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2.5 py-1 rounded-md">
                    Belum Bayar
                  </span>
                @endif
              </td>

              <!-- Aksi -->
              <td class="px-5 py-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button type="button" @click="viewBill(@js($bill))"
                          class="bg-brand/10 hover:bg-brand text-brand hover:text-white font-bold text-xs px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-regular fa-eye"></i> Detail
                  </button>

                  @if($bill['status'] === 'Belum Bayar' || $bill['status'] === 'Jatuh Tempo')
                    <a href="{{ route('tagihan.payment', ['invoice' => $bill['id'], 'total' => $bill['total_raw'], 'package' => $bill['package_name']]) }}"
                       class="bg-brand hover:bg-brand-700 text-white font-bold text-xs px-2.5 py-1.5 rounded-lg transition shadow-2xs flex items-center gap-1">
                      <i class="fa-solid fa-credit-card text-[10px]"></i> Bayar
                    </a>
                  @elseif($bill['status'] === 'Lunas')
                    <button type="button" @click="viewReceipt(@js($bill))"
                            class="bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold text-xs px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                      <i class="fa-solid fa-receipt text-[10px]"></i> Kuitansi
                    </button>
                  @endif
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                <p class="font-medium text-slate-600">Tidak ada tagihan yang cocok dengan filter atau pencarian.</p>
                <p class="text-xs text-slate-400 mt-1">Tidak ada data riwayat tagihan untuk filter yang dipilih.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. BANNER INFORMASI KETENTUAN TAGIHAN          -->
  <!-- ============================================== -->
  <div class="bg-[#fff1f1] border border-[#fecaca] rounded-2xl p-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 items-center">

    <!-- Kolom 1: Tagihan Dibuat -->
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

    <!-- Kolom 2: Jatuh Tempo -->
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

    <!-- Kolom 3: Pengingat -->
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

  <!-- ============================================== -->
  <!-- 6. MODAL DETAIL RINCIAN TAGIHAN (PERSIS ADMIN) -->
  <!-- ============================================== -->
  <div x-show="openDetail" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">

    <div @click.away="openDetail = false"
         class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-7 shadow-2xl relative space-y-5"
         x-show="selectedBill">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-full bg-red-100 text-brand flex items-center justify-center shrink-0">
            <i class="fa-solid fa-file-invoice text-lg"></i>
          </div>
          <div>
            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">Rincian Invoice</span>
            <h3 class="text-base font-black text-slate-900" x-text="selectedBill?.id"></h3>
          </div>
        </div>
        <button type="button" @click="openDetail = false" class="text-slate-400 hover:text-black text-xl">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Rincian Data -->
      <div class="space-y-3.5 text-xs">
        
        <!-- Info Layanan -->
        <div class="bg-slate-50 rounded-xl p-4 space-y-2 border border-slate-200/80">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Layanan Internet:</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.package_name"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Kecepatan:</span>
            <span class="font-bold text-brand" x-text="selectedBill?.speed || '20 Mbps'"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Periode Tagihan:</span>
            <span class="font-semibold text-slate-800" x-text="selectedBill?.period"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Jatuh Tempo:</span>
            <span class="font-bold text-red-600" x-text="selectedBill?.due_date"></span>
          </div>
          <div class="flex justify-between items-center pt-1 border-t border-slate-200/60">
            <span class="text-slate-500 font-medium">Status Pembayaran:</span>
            <span class="px-2.5 py-0.5 rounded-md font-bold text-[10px]"
                  :class="{
                    'bg-emerald-50 text-emerald-700 border border-emerald-200': selectedBill?.status === 'Lunas',
                    'bg-amber-50 text-amber-700 border border-amber-200': selectedBill?.status === 'Menunggu Verifikasi',
                    'bg-red-50 text-red-700 border border-red-200': selectedBill?.status === 'Jatuh Tempo',
                    'bg-slate-100 text-slate-700 border border-slate-200': selectedBill?.status === 'Belum Bayar' || selectedBill?.status === 'Belum Dibayar'
                  }"
                  x-text="selectedBill?.status"></span>
          </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="space-y-2 border border-slate-200/80 rounded-xl p-4">
          <div class="flex justify-between text-slate-600">
            <span>Biaya Langganan (Internet)</span>
            <span class="font-bold text-slate-900" x-text="'Rp' + (selectedBill?.amount || selectedBill?.price || selectedBill?.total)"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Pajak (PPN 11%)</span>
            <span class="font-medium text-slate-500" x-text="'Rp' + (selectedBill?.tax || '0')"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Metode Pembayaran</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.payment_method || 'BRI Virtual Account'"></span>
          </div>
          <div class="border-t border-slate-100 pt-2.5 flex justify-between font-black text-sm text-slate-900">
            <span>Total Pembayaran:</span>
            <span class="text-brand text-base" x-text="'Rp' + (selectedBill?.total || '0')"></span>
          </div>
        </div>

      </div>

      <!-- Action Buttons Modal -->
      <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" @click="openDetail = false"
                class="border border-slate-300 text-slate-700 font-bold text-xs py-2 px-4 rounded-xl hover:bg-slate-50 transition">
          Tutup
        </button>

        <template x-if="selectedBill?.status === 'Belum Bayar' || selectedBill?.status === 'Jatuh Tempo' || selectedBill?.status === 'Belum Dibayar'">
          <a :href="'/tagihan/pembayaran?invoice=' + selectedBill?.id + '&package=' + encodeURIComponent(selectedBill?.package_name || '')"
             class="bg-brand hover:bg-brand-700 text-white font-bold text-xs py-2 px-4 rounded-xl transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-credit-card"></i>
            <span>Bayar Sekarang</span>
          </a>
        </template>

        <template x-if="selectedBill?.status === 'Lunas'">
          <button type="button" @click="openDetail = false; viewReceipt(selectedBill)"
                  class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2 px-4 rounded-xl transition flex items-center gap-1.5 shadow-sm">
            <i class="fa-solid fa-receipt"></i>
            <span>Lihat Kuitansi</span>
          </button>
        </template>
      </div>

    </div>
  </div>

  <!-- ============================================== -->
  <!-- 7. MODAL BUKTI PEMBAYARAN LUNAS / KUITANSI     -->
  <!-- ============================================== -->
  <div x-show="openReceipt" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">

    <div @click.away="openReceipt = false"
         class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 relative shadow-2xl space-y-4">

      <button type="button" @click="openReceipt = false"
              class="absolute top-6 right-6 text-slate-400 hover:text-black text-xl font-bold">
        <i class="fa-solid fa-xmark"></i>
      </button>

      <div class="text-center pb-3 border-b border-slate-100">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center mb-2.5">
          <i class="fa-solid fa-circle-check text-2xl"></i>
        </div>
        <h3 class="text-base font-black text-slate-900">Bukti Pembayaran Lunas</h3>
        <p class="text-xs text-slate-400 font-mono mt-0.5" x-text="selectedReceipt?.id"></p>
      </div>

      <div class="py-2 space-y-2 text-xs">
        <div class="flex justify-between">
          <span class="text-slate-500">Layanan Paket:</span>
          <span class="font-bold text-slate-900" x-text="selectedReceipt?.package_name"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">Periode Tagihan:</span>
          <span class="font-semibold text-slate-800" x-text="selectedReceipt?.period"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">Tanggal Pelunasan:</span>
          <span class="font-semibold text-emerald-700" x-text="selectedReceipt?.paid_date || '-'"></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">Metode Pembayaran:</span>
          <span class="font-semibold text-slate-900" x-text="selectedReceipt?.payment_method || 'BRI Virtual Account'"></span>
        </div>
        <div class="flex justify-between text-sm font-black pt-3 border-t border-dashed border-slate-200">
          <span>Jumlah Dibayar:</span>
          <span class="text-emerald-700" x-text="'Rp' + (selectedReceipt?.total || '0')"></span>
        </div>
      </div>

      <div class="pt-3 flex items-center gap-3">
        <button type="button" @click="openReceipt = false"
                class="w-1/2 border border-slate-300 text-slate-700 font-bold py-2.5 rounded-xl text-xs hover:bg-slate-50 transition">
          Tutup
        </button>
        <button type="button" @click="window.print()"
                class="w-1/2 bg-slate-900 hover:bg-black text-white font-bold py-2.5 rounded-xl text-xs transition flex items-center justify-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-print"></i> Cetak Bukti
        </button>
      </div>

    </div>
  </div>

</div>
@endsection
