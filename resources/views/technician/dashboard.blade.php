@extends('layouts.technician')

@section('title', 'Dashboard Teknisi Lapangan - Banterpool')

@section('content')
<div class="space-y-6">

  <!-- ============================================== -->
  <!-- 1. GREETING BANNER                             -->
  <!-- ============================================== -->
  <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white relative overflow-hidden shadow-lg">
    <div class="absolute -right-6 -bottom-10 opacity-10 text-white pointer-events-none">
      <i class="fa-solid fa-screwdriver-wrench text-9xl"></i>
    </div>

    <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-semibold mb-2 border border-amber-500/30">
          <span class="w-2 h-2 rounded-full bg-amber-400 animate-pulse"></span>
          <span>Status Tugas: Siap di Lapangan</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-black tracking-tight">Halo, {{ auth()->user()->name }}! 👋</h1>
        <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-xl">
          Ada <span class="text-amber-400 font-bold">{{ $stats['total_tasks_today'] }} tugas operasional</span> yang memerlukan penanganan Anda hari ini. Utamakan gangguan kategori kritis terlebih dahulu.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <a href="{{ route('teknisi.pemasangan') }}"
           class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md flex items-center gap-2">
          <i class="fa-solid fa-calendar-check"></i>
          <span>Tiket Pasang Baru</span>
        </a>
        <a href="{{ route('teknisi.gangguan') }}"
           class="bg-red-600 hover:bg-red-500 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-md flex items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation"></i>
          <span>Tiket Gangguan</span>
        </a>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- BANNER NOTIFIKASI TIKET DITUGASKAN DARI ADMIN  -->
  <!-- ============================================== -->
  @if(isset($newAssignedOrders) && $newAssignedOrders->isNotEmpty())
    <div class="bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent border border-amber-400/40 rounded-3xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div class="flex items-start sm:items-center gap-3.5">
        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-slate-950 flex items-center justify-center text-lg font-black shrink-0 shadow-sm animate-bounce">
          <i class="fa-solid fa-bell"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="bg-amber-500 text-slate-950 font-black text-[9px] px-2 py-0.5 rounded uppercase tracking-wider">Tiket Baru</span>
            <h4 class="font-extrabold text-sm text-slate-900">Anda Memiliki {{ $newAssignedOrders->count() }} Tugas Pemasangan Baru dari Admin NOC!</h4>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Pelanggan: <strong class="text-slate-800">{{ $newAssignedOrders->first()->customer_name }}</strong> ({{ $newAssignedOrders->first()->order_number }}) • {{ $newAssignedOrders->first()->package_name }}
          </p>
        </div>
      </div>
      <a href="{{ route('teknisi.pemasangan', ['q' => $newAssignedOrders->first()->order_number, 'scope' => 'my']) }}"
         class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-xs flex items-center justify-center gap-2 shrink-0">
        <i class="fa-solid fa-arrow-right text-amber-400"></i>
        <span>Buka & Kerjakan Tiket</span>
      </a>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. METRIC STATS CARDS                          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
    
    <!-- Total Tugas Hari Ini -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-800 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-list-check"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Tugas</p>
        <h3 class="text-2xl font-black text-slate-900 leading-tight mt-0.5">{{ $stats['total_tasks_today'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Pasang & Kendala</span>
      </div>
    </div>

    <!-- Tiket Pemasangan Aktif -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-calendar-plus"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Pasang Baru</p>
        <h3 class="text-2xl font-black text-amber-600 leading-tight mt-0.5">{{ $stats['pending_installations'] }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Perlu instalasi</span>
      </div>
    </div>

    <!-- Tiket Gangguan Aktif -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Gangguan</p>
        <h3 class="text-2xl font-black text-red-600 leading-tight mt-0.5">{{ $stats['active_troubles'] }}</h3>
        <span class="text-[10px] text-red-600 font-bold">{{ $stats['critical_troubles'] }} Kritis (LOS)</span>
      </div>
    </div>

    <!-- Selesai Hari Ini -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center gap-4">
      <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
        <i class="fa-solid fa-circle-check"></i>
      </div>
      <div>
        <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Selesai</p>
        <h3 class="text-2xl font-black text-emerald-600 leading-tight mt-0.5">{{ $stats['completed_installations'] }}</h3>
        <span class="text-[10px] text-emerald-600 font-medium">Instalasi aktif</span>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. WORK QUEUES (2 COLUMNS)                     -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Kolom Kiri: Antrean Pemasangan Baru -->
    <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm">
              <i class="fa-solid fa-wifi"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">Jadwal Pemasangan Baru</h3>
              <p class="text-[11px] text-slate-400">Instalasi pelanggan yang siap dikerjakan</p>
            </div>
          </div>

          <a href="{{ route('teknisi.pemasangan') }}" class="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
            <span>Lihat Semua</span>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
          </a>
        </div>

        <div class="space-y-3">
          @forelse($activeOrders as $order)
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $order->order_number }}</span>
                    @if($order->technician)
                      <span class="bg-amber-100 text-amber-900 border border-amber-300/80 font-bold px-1.5 py-0.2 rounded-md text-[9px] flex items-center gap-1">
                        <i class="fa-solid fa-user-check text-[8px] text-amber-600"></i>
                        <span>{{ ($order->technician_id === auth()->id() || $order->technician === auth()->user()->name || str_contains($order->technician, explode(' ', auth()->user()->name)[0])) ? 'Ditugaskan ke Anda' : $order->technician }}</span>
                      </span>
                    @endif
                  </div>
                  <h4 class="text-xs sm:text-sm font-bold text-slate-900 mt-0.5">{{ $order->customer_name }}</h4>
                  <p class="text-[11px] text-slate-600 mt-0.5 flex items-center gap-1.5">
                    <i class="fa-solid fa-location-dot text-red-500 text-xs shrink-0"></i>
                    <span class="line-clamp-1">{{ $order->address }}</span>
                  </p>
                </div>

                @if($order->status === 'Sedang Dipasang')
                  <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0">Sedang Dipasang</span>
                @elseif($order->status === 'Kendala Lapangan')
                  <span class="bg-red-100 text-red-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0">Kendala Lapangan</span>
                @elseif($order->status === 'Menunggu Konfirmasi')
                  <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                    <i class="fa-regular fa-clock text-[9px]"></i> Menunggu Konfirmasi
                  </span>
                @else
                  <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0">Jadwal Pasang</span>
                @endif
              </div>

              <!-- Meta info & Actions -->
              <div class="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                <div class="flex items-center gap-3 text-slate-500 text-[11px]">
                  <span><i class="fa-solid fa-bolt text-amber-500 mr-1"></i>{{ $order->speed ?? '20 Mbps' }}</span>
                  <span><i class="fa-solid fa-box text-blue-500 mr-1"></i>{{ $order->assigned_odp ?? 'ODP-CLK-01' }}</span>
                </div>

                <div class="flex items-center gap-1.5">
                  <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($order->customer_name) }}%2C%20saya%20teknisi%20Banterpool%20mengenai%20jadwal%20pemasangan%20WiFi..."
                     target="_blank"
                     class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WA</span>
                  </a>
                  <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}"
                     target="_blank"
                     class="bg-slate-800 hover:bg-slate-700 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-solid fa-diamond-turn-right"></i>
                    <span>Maps</span>
                  </a>
                  <a href="{{ route('teknisi.pemasangan', ['q' => $order->order_number]) }}"
                     class="bg-amber-500 hover:bg-amber-400 text-slate-950 text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition">
                    Proses
                  </a>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-8 text-slate-400">
              <i class="fa-solid fa-circle-check text-4xl text-emerald-400 mb-2"></i>
              <p class="text-xs font-semibold">Tidak ada antrean pemasangan tertunda saat ini.</p>
            </div>
          @endforelse
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-slate-100 text-center">
        <a href="{{ route('teknisi.pemasangan') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
          Buka Seluruh Tiket Pemasangan &rarr;
        </a>
      </div>
    </div>

    <!-- Kolom Kanan: Antrean Tiket Gangguan Kritis -->
    <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-red-100 text-red-700 flex items-center justify-center text-sm">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900">Laporan Gangguan & Perbaikan</h3>
              <p class="text-[11px] text-slate-400">Kendala sinyal, LOS merah, dan kabel FO</p>
            </div>
          </div>

          <a href="{{ route('teknisi.gangguan') }}" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
            <span>Lihat Semua</span>
            <i class="fa-solid fa-chevron-right text-[10px]"></i>
          </a>
        </div>

        <div class="space-y-3">
          @forelse($urgentTickets as $ticket)
            <div class="p-4 rounded-2xl border border-slate-100 bg-slate-50/60 hover:bg-slate-50 transition">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <div class="flex items-center gap-1.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $ticket['id'] }}</span>
                    @if($ticket['priority'] === 'Kritis')
                      <span class="bg-red-100 text-red-700 text-[9px] font-black px-1.5 py-0.5 rounded uppercase">Kritis (LOS)</span>
                    @elseif($ticket['priority'] === 'Tinggi')
                      <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-1.5 py-0.5 rounded uppercase">Tinggi</span>
                    @endif
                  </div>
                  <h4 class="text-xs sm:text-sm font-bold text-slate-900 mt-1">{{ $ticket['type'] }}</h4>
                  <p class="text-[11px] text-slate-600 mt-0.5 font-medium">{{ $ticket['customer_name'] }} • {{ $ticket['address'] }}</p>
                </div>

                @if($ticket['status'] === 'Sedang Ditangani')
                  <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2 py-0.5 rounded-full shrink-0">Sedang Ditangani</span>
                @else
                  <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2 py-0.5 rounded-full shrink-0">Menunggu Respon</span>
                @endif
              </div>

              <p class="text-[11px] text-slate-500 mt-2 line-clamp-2 bg-white p-2.5 rounded-xl border border-slate-200/60">
                "{{ $ticket['description'] }}"
              </p>

              <!-- Actions -->
              <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
                <span class="text-[11px] text-slate-500 font-semibold">
                  <i class="fa-solid fa-server mr-1 text-slate-400"></i>{{ $ticket['odp'] ?? 'ODP Banterpool' }}
                </span>

                <div class="flex items-center gap-1.5">
                  <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket['customer_phone']) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($ticket['customer_name']) }}%2C%20saya%20teknisi%20Banterpool%20mengenai%20laporan%20gangguan%20{{ urlencode($ticket['id']) }}..."
                     target="_blank"
                     class="bg-emerald-600 hover:bg-emerald-500 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-brands fa-whatsapp"></i>
                    <span>WA</span>
                  </a>
                  <a href="{{ route('teknisi.gangguan', ['q' => $ticket['id']]) }}"
                     class="bg-red-600 hover:bg-red-500 text-white text-[11px] font-bold px-2.5 py-1.5 rounded-lg transition">
                    Tindak Lanjuti
                  </a>
                </div>
              </div>
            </div>
          @empty
            <div class="text-center py-8 text-slate-400">
              <i class="fa-solid fa-shield-heart text-4xl text-emerald-400 mb-2"></i>
              <p class="text-xs font-semibold">Semua tiket gangguan telah tertangani dengan baik.</p>
            </div>
          @endforelse
        </div>
      </div>

      <div class="mt-4 pt-3 border-t border-slate-100 text-center">
        <a href="{{ route('teknisi.gangguan') }}" class="text-xs font-bold text-slate-600 hover:text-slate-900">
          Buka Seluruh Tiket Gangguan &rarr;
        </a>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 4. PANDUAN STANDAR LAPANGAN                    -->
  <!-- ============================================== -->
  <div class="bg-white rounded-3xl border border-slate-200 p-5 sm:p-6 shadow-xs">
    <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">Panduan Standar Operasional Redaman (OPM)</h3>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
      <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-black">
          <i class="fa-solid fa-check"></i>
        </div>
        <div>
          <p class="font-bold text-emerald-900">Sangat Bagus (-16 s/d -21 dBm)</p>
          <p class="text-[11px] text-emerald-700">Koneksi prima, GPON full bandwidth stabil.</p>
        </div>
      </div>

      <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-black">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
          <p class="font-bold text-amber-900">Batas Toleransi (-22 s/d -25 dBm)</p>
          <p class="text-[11px] text-amber-700">Cek lekukan kabel drop core sebelum aktivasi.</p>
        </div>
      </div>

      <div class="bg-red-50 border border-red-200 rounded-2xl p-3 flex items-center gap-3">
        <div class="w-8 h-8 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-black">
          <i class="fa-solid fa-xmark"></i>
        </div>
        <div>
          <p class="font-bold text-red-900">Kritis / Drop (&lt; -26 dBm / LOS)</p>
          <p class="text-[11px] text-red-700">Wajib splicing ulang fast connector / sambungan core.</p>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
