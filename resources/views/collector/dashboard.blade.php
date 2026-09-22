@extends('layouts.collector')

@section('title', 'Dashboard Kolektor Lapangan - Banterpool')

@section('content')
<div class="space-y-6">

  <!-- ============================================== -->
  <!-- 1. GREETING BANNER                             -->
  <!-- ============================================== -->
  <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-lg">
    <div class="absolute -right-6 -bottom-10 opacity-10 text-white pointer-events-none">
      <i class="fa-solid fa-money-bill-wave text-9xl"></i>
    </div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold mb-2 border border-emerald-500/30">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Status Kolektor: Aktif di Lapangan</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ auth()->user()->name }}! 💵</h1>
        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
          Ada <span class="text-emerald-400 font-bold">{{ $stats['unpaid_count'] + $stats['due_count'] }} pelanggan</span> dengan total <span class="text-emerald-400 font-bold">Rp {{ number_format($stats['unpaid_nominal'] + $stats['due_nominal'], 0, ',', '.') }}</span> yang belum tertagih. Utamakan pelanggan berstatus jatuh tempo.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('kolektor.tagihan') }}"
           class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md flex items-center gap-2">
          <i class="fa-solid fa-hand-holding-dollar"></i>
          <span>Kelola Tagihan & Tunai</span>
        </a>
        <a href="{{ route('kolektor.pemasangan') }}"
           class="bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs px-4 py-2.5 rounded-xl transition border border-slate-700 flex items-center gap-2">
          <i class="fa-solid fa-calendar-check text-amber-400"></i>
          <span>Tiket Pasang @if(($stats['pending_installations'] ?? 0) > 0)({{ $stats['pending_installations'] }})@endif</span>
        </a>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 2. METRIC STATS CARDS                          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    
    <!-- Total Tunai Diterima Hari Ini -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-vault"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Tunai Hari Ini</p>
        <h3 class="text-xl sm:text-2xl font-black text-emerald-600 leading-tight mt-0.5">Rp {{ number_format($stats['total_cash_today'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">{{ $stats['cash_count_today'] }} transaksi tunai</span>
      </div>
    </div>

    <!-- Tagihan Belum Bayar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-clock"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Belum Bayar</p>
        <h3 class="text-xl sm:text-2xl font-black text-amber-600 leading-tight mt-0.5">{{ $stats['unpaid_count'] }} Tagihan</h3>
        <span class="text-[10px] text-slate-500 font-medium">Rp {{ number_format($stats['unpaid_nominal'], 0, ',', '.') }}</span>
      </div>
    </div>

    <!-- Tagihan Jatuh Tempo -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Jatuh Tempo</p>
        <h3 class="text-xl sm:text-2xl font-black text-red-600 leading-tight mt-0.5">{{ $stats['due_count'] }} Tagihan</h3>
        <span class="text-[10px] text-red-600 font-bold">Rp {{ number_format($stats['due_nominal'], 0, ',', '.') }}</span>
      </div>
    </div>

    <!-- Sudah Lunas Terverifikasi -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Lunas</p>
        <h3 class="text-xl sm:text-2xl font-black text-blue-600 leading-tight mt-0.5">{{ $stats['paid_count'] }} Tagihan</h3>
        <span class="text-[10px] text-blue-600 font-medium">Dari {{ $stats['total_bills'] }} total tagihan</span>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. WORK QUEUES & ACTIONS (2 COLUMNS)           -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Kolom Kiri (2 Kolom): Antrean Prioritas Penagihan Tunai -->
    <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">
              <i class="fa-solid fa-route"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">Rute Prioritas Kunjungan Penagihan</h3>
              <p class="text-[11px] text-slate-400">Pelanggan jatuh tempo dan belum bayar yang harus dikunjungi</p>
            </div>
          </div>

          <a href="{{ route('kolektor.tagihan') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1">
            <span>Lihat Semua</span>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
          </a>
        </div>

        <div class="space-y-3">
          @forelse($priorityBills as $bill)
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $bill->bill_number }}</span>
                    @if($bill->status === 'Jatuh Tempo')
                      <span class="bg-red-100 text-red-700 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Jatuh Tempo</span>
                    @else
                      <span class="bg-amber-100 text-amber-800 text-[9px] font-black px-2 py-0.5 rounded-full uppercase">Belum Bayar</span>
                    @endif
                  </div>
                  <h4 class="text-sm font-bold text-slate-900 mt-1">{{ $bill->customer_name }}</h4>
                  <p class="text-[11px] text-slate-600 mt-0.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-location-dot text-red-500 text-xs shrink-0"></i>
                    <span class="line-clamp-1">{{ $bill->address }}</span>
                  </p>
                </div>

                <div class="text-right shrink-0">
                  <p class="text-[10px] text-slate-400 font-bold uppercase">Total Iuran</p>
                  <p class="text-sm sm:text-base font-black text-emerald-700">Rp {{ number_format($bill->total, 0, ',', '.') }}</p>
                </div>
              </div>

              <!-- Meta & Actions -->
              <div class="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-3 text-slate-500 text-[11px]">
                  <span><i class="fa-solid fa-wifi text-emerald-500 mr-1"></i>{{ $bill->package_name }}</span>
                  <span><i class="fa-solid fa-calendar mr-1 text-slate-400"></i>Tempo: {{ $bill->due_date }}</span>
                </div>

                <div class="flex items-center gap-1.5">
                  <!-- WhatsApp Tagih -->
                  <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $bill->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($bill->customer_name) }}%2C%20saya%20{{ urlencode(Auth::user()->name ?? 'Petugas Kolektor') }}%20dari%20Banterpool%20mengenai%20iuran%20WiFi%20Banterpool%20nomor%20{{ urlencode($bill->bill_number) }}%20sebesar%20Rp%20{{ number_format($bill->total, 0, ',', '.') }}.%20Apakah%20bisa%20saya%20kunjungi%20ke%20rumah%20untuk%20serah%20terima%20tunai%3F%20Terima%20kasih."
                     target="_blank"
                     class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1"
                     title="Kirim Pesan Penagihan Sopan via WA">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WA</span>
                  </a>

                  <!-- Maps -->
                  <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($bill->address) }}"
                     target="_blank"
                     class="bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1"
                     title="Petunjuk Arah Maps">
                    <i class="fa-solid fa-diamond-turn-right"></i>
                    <span>Maps</span>
                  </a>

                  <!-- Input Bayar Tunai Shortcut -->
                  <a href="{{ route('kolektor.tagihan', ['q' => $bill->bill_number]) }}"
                     class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-black px-3 py-1.5 rounded-lg transition shadow-xs flex items-center gap-1">
                    <i class="fa-solid fa-money-bill-wave"></i>
                    <span>Terima Tunai</span>
                  </a>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-8 text-slate-400">
              <i class="fa-solid fa-circle-check text-4xl text-emerald-400 mb-2"></i>
              <p class="text-xs font-semibold">Semua tagihan prioritas sudah berhasil dilunasi!</p>
            </div>
          @endforelse
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-slate-100 text-center">
        <a href="{{ route('kolektor.tagihan') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
          Buka Seluruh Tagihan & Penagihan Tunai &rarr;
        </a>
      </div>
    </div>

    <!-- Kolom Kanan: Monitoring Lapangan & Aksi Cepat -->
    <div class="space-y-6">
      
      <!-- Quick Action Card -->
      <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl p-5 text-white shadow-md">
        <h3 class="text-sm font-black flex items-center gap-2 mb-1">
          <i class="fa-solid fa-bolt"></i>
          <span>Aksi Penagihan Tunai</span>
        </h3>
        <p class="text-[11px] text-emerald-100 leading-relaxed mb-4">
          Pelanggan membayar uang fisik di tempat? Gunakan fitur terima tunai untuk langsung menerbitkan kuitansi resmi.
        </p>

        <div class="space-y-2">
          <a href="{{ route('kolektor.tagihan') }}"
             class="w-full bg-white hover:bg-emerald-50 text-emerald-950 font-bold text-xs py-2.5 px-4 rounded-xl flex items-center justify-center gap-2 transition shadow-xs">
            <i class="fa-solid fa-hand-holding-dollar text-emerald-600"></i>
            <span>Cari Tagihan & Terima Tunai</span>
          </a>
          <a href="{{ route('kolektor.tagihan') }}#input-manual"
             class="w-full bg-emerald-800/60 hover:bg-emerald-800 text-white font-semibold text-xs py-2 px-4 rounded-xl flex items-center justify-center gap-2 transition border border-emerald-400/30">
            <i class="fa-solid fa-plus text-xs"></i>
            <span>Input Tagihan Manual Baru</span>
          </a>
        </div>
      </div>

      <!-- Pemantauan Gangguan Jaringan (Kolektor Info) -->
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-red-100 text-red-600 flex items-center justify-center text-xs">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
              <h4 class="text-xs font-bold text-slate-900">Info Kendala Jaringan</h4>
              <p class="text-[10px] text-slate-400">Cek sebelum menagih pelanggan</p>
            </div>
          </div>
          <a href="{{ route('kolektor.gangguan') }}" class="text-[11px] font-bold text-red-600 hover:text-red-700">
            Lihat
          </a>
        </div>

        <div class="space-y-2.5">
          @forelse($urgentTickets as $ticket)
            <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50 text-xs">
              <div class="flex items-center justify-between text-[10px]">
                <span class="font-bold text-red-600 uppercase">{{ $ticket['id'] }}</span>
                <span class="bg-red-100 text-red-700 px-1.5 py-0.5 rounded font-bold">{{ $ticket['priority'] }}</span>
              </div>
              <p class="font-bold text-slate-800 mt-0.5 text-xs truncate">{{ $ticket['customer_name'] }}</p>
              <p class="text-[10px] text-slate-500 truncate">{{ $ticket['type'] }}</p>
            </div>
          @empty
            <p class="text-center text-xs text-slate-400 py-3">Tidak ada kendala aktif.</p>
          @endforelse
        </div>
      </div>

      <!-- Pemantauan Pemasangan Baru -->
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
          <div class="flex items-center gap-2">
            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs">
              <i class="fa-solid fa-calendar-check"></i>
            </div>
            <div>
              <h4 class="text-xs font-bold text-slate-900">Jadwal Pasang Baru</h4>
              <p class="text-[10px] text-slate-400">Koordinasi biaya pasang tunai</p>
            </div>
          </div>
          <a href="{{ route('kolektor.pemasangan') }}" class="text-[11px] font-bold text-amber-600 hover:text-amber-700">
            Lihat
          </a>
        </div>

        <div class="space-y-2.5">
          @forelse($recentOrders as $ord)
            <div class="p-2.5 rounded-xl border border-slate-100 bg-slate-50 text-xs">
              <div class="flex items-center justify-between text-[10px]">
                <span class="font-bold text-slate-500 uppercase">{{ $ord->order_number }}</span>
                <span class="bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded font-bold">{{ $ord->status }}</span>
              </div>
              <p class="font-bold text-slate-800 mt-0.5 text-xs truncate">{{ $ord->customer_name }}</p>
              <p class="text-[10px] text-slate-500">{{ $ord->package_name }} • {{ $ord->payment_method }}</p>
            </div>
          @empty
            <p class="text-center text-xs text-slate-400 py-3">Tidak ada pesanan baru.</p>
          @endforelse
        </div>
      </div>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 4. PANDUAN STANDAR KOLEKTOR                    -->
  <!-- ============================================== -->
  <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs">
    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">Standard Operating Procedure (SOP) Penagihan Tunai</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 flex items-start gap-3">
        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black shrink-0">
          <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
          <p class="font-bold text-emerald-900">1. Terbitkan Kuitansi Resmi</p>
          <p class="text-[11px] text-emerald-700 mt-0.5">Wajib mencatat pembayaran di sistem dan membagikan tautan/cetak kuitansi tanda terima kepada pelanggan.</p>
        </div>
      </div>

      <div class="bg-blue-50 border border-blue-200 rounded-2xl p-3 flex items-start gap-3">
        <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-black shrink-0">
          <i class="fa-solid fa-comments"></i>
        </div>
        <div>
          <p class="font-bold text-blue-900">2. Komunikasi Sopan</p>
          <p class="text-[11px] text-blue-700 mt-0.5">Gunakan tombol WhatsApp dengan template pesan sopan sebelum melakukan kunjungan ke rumah pelanggan.</p>
        </div>
      </div>

      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 flex items-start gap-3">
        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-black shrink-0">
          <i class="fa-solid fa-headset"></i>
        </div>
        <div>
          <p class="font-bold text-amber-900">3. Tangani Keluhan Pelanggan</p>
          <p class="text-[11px] text-amber-700 mt-0.5">Jika pelanggan komplain internet putus/lambat, cek menu Tiket Gangguan dan koordinasikan langsung dengan teknisi jaga.</p>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
