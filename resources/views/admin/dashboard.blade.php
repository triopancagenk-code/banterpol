@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Dashboard Operasional')
@section('page-title', 'Dashboard Operasional NOC')

@section('content')
<div class="space-y-8">

  <!-- ============================================== -->
  <!-- 1. KPI CARDS (STATISTIK UTAMA)                 -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
    
    <!-- KPI 1: Pelanggan Aktif -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Pelanggan Aktif</p>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ $stats['active_customers'] }} <span class="text-xs font-medium text-slate-400">/ {{ $stats['total_customers'] }}</span></h3>
        <p class="text-[10px] sm:text-[11px] text-emerald-600 font-bold mt-1 flex items-center gap-1">
          <i class="fa-solid fa-arrow-trend-up"></i> +14 bulan ini
        </p>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-users"></i>
      </div>
    </div>

    <!-- KPI 2: Pesanan Baru Masuk -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Pesanan Masuk</p>
        <h3 class="text-xl sm:text-2xl font-black text-emerald-600 mt-1">{{ $stats['pending_orders_count'] ?? 0 }} <span class="text-xs font-normal text-slate-400">Baru</span></h3>
        <a href="{{ route('admin.pesanan') }}" class="text-[10px] sm:text-[11px] text-brand hover:underline font-bold mt-1 inline-block">
          Pantau Pesanan &rarr;
        </a>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-cart-shopping"></i>
      </div>
    </div>

    <!-- KPI 3: Tagihan Menunggu Verifikasi -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Tagihan Dicek</p>
        <h3 class="text-xl sm:text-2xl font-black text-amber-500 mt-1">{{ $stats['pending_bills_count'] }} <span class="text-xs font-normal text-slate-400">Inv</span></h3>
        <a href="{{ route('admin.tagihan', ['status' => 'Menunggu Verifikasi']) }}" class="text-[10px] sm:text-[11px] text-brand hover:underline font-bold mt-1 inline-block">
          Verifikasi &rarr;
        </a>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-receipt"></i>
      </div>
    </div>

    <!-- KPI 4: Laporan Gangguan Terbuka -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Laporan Masalah</p>
        <h3 class="text-xl sm:text-2xl font-black text-red-600 mt-1">{{ $stats['open_tickets_count'] }} <span class="text-xs font-normal text-slate-400">Tiket</span></h3>
        <p class="text-[10px] sm:text-[11px] text-red-500 font-bold mt-1 flex items-center gap-1">
          <i class="fa-solid fa-triangle-exclamation"></i> {{ $stats['critical_tickets_count'] }} kritis
        </p>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-red-50 text-brand flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-headset"></i>
      </div>
    </div>

    <!-- KPI 5: MRTG Total Bandwidth -->
    <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-semibold text-slate-500">Trafik Bandwidth</p>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">{{ $stats['bandwidth_in'] }}</h3>
        <p class="text-[10px] sm:text-[11px] text-slate-500 font-medium mt-1">
          <span class="text-emerald-600 font-bold">In</span> &bull; 
          <span class="text-blue-600 font-bold">{{ $stats['bandwidth_out'] }} Out</span>
        </p>
      </div>
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-lg sm:text-xl shrink-0">
        <i class="fa-solid fa-network-wired"></i>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 2. QUICK ACCESS CARDS: MRTG & ODC MAP          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    
    <!-- Preview MRTG Mini-Chart (Col 7) -->
    <div class="lg:col-span-7 bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
      <div class="flex items-center justify-between mb-4">
        <div>
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <h3 class="text-sm font-extrabold text-slate-900">MRTG Real-Time Traffic (Core Router)</h3>
          </div>
          <p class="text-xs text-slate-400 mt-0.5">Throughput uplink 10G SFP+ ISP Tier-1</p>
        </div>
        <a href="{{ route('admin.mrtg') }}" class="text-xs text-brand hover:underline font-bold flex items-center gap-1">
          Buka MRTG Lengkap <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </a>
      </div>

      <div class="h-56 relative">
        <canvas id="dashboardMrtgChart"></canvas>
      </div>

      <div class="grid grid-cols-3 gap-3 pt-4 border-t border-slate-100 text-center text-xs mt-3">
        <div class="bg-slate-50 p-2 rounded-xl">
          <p class="text-[10px] text-slate-400 font-medium">Kapasitas Port</p>
          <p class="font-extrabold text-slate-800 mt-0.5">10.000 Mbps</p>
        </div>
        <div class="bg-emerald-50/60 p-2 rounded-xl border border-emerald-100">
          <p class="text-[10px] text-emerald-700 font-medium">Download (Inbound)</p>
          <p class="font-extrabold text-emerald-600 mt-0.5">{{ $stats['bandwidth_in'] }}</p>
        </div>
        <div class="bg-blue-50/60 p-2 rounded-xl border border-blue-100">
          <p class="text-[10px] text-blue-700 font-medium">Upload (Outbound)</p>
          <p class="font-extrabold text-blue-600 mt-0.5">{{ $stats['bandwidth_out'] }}</p>
        </div>
      </div>
    </div>

    <!-- Preview ODC GIS Map Banner (Col 5) -->
    <div class="lg:col-span-5 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-950 rounded-2xl p-6 text-white shadow-md flex flex-col justify-between relative overflow-hidden">
      <!-- Background Graphic -->
      <div class="absolute -right-6 -bottom-6 opacity-10 text-white pointer-events-none">
        <i class="fa-solid fa-map-location-dot text-[180px]"></i>
      </div>

      <div>
        <div class="inline-flex items-center gap-2 bg-red-500/20 text-red-300 border border-red-500/30 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider mb-4">
          <i class="fa-solid fa-satellite-dish"></i> Infrastruktur Fiber Optik
        </div>
        <h3 class="text-xl font-black text-white leading-snug">Peta Persebaran ODC & ODP Fiber</h3>
        <p class="text-xs text-slate-300 mt-2 leading-relaxed">
          Pantau {{ $stats['odc_count'] }} Optical Distribution Cabinet (ODC) dan {{ $stats['odp_count'] }} Optical Distribution Point (ODP) di seluruh area jaringan Banterpool.
        </p>

        <!-- Status Warning Fiber Cut -->
        <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-3 mt-4 flex items-center gap-3 text-xs">
          <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
          </div>
          <div>
            <p class="font-bold text-red-300">Peringatan: ODP-CLK-08 Terputus</p>
            <p class="text-[11px] text-slate-300">Redaman -99.0 dBm pada area Jatisaba, Cilongok</p>
          </div>
        </div>
      </div>

      <div class="pt-6">
        <a href="{{ route('admin.odc-map') }}"
           class="w-full bg-brand hover:bg-brand-700 text-white font-bold text-xs py-3 px-4 rounded-xl shadow-lg transition flex items-center justify-center gap-2">
          <i class="fa-solid fa-map-location-dot"></i>
          <span>Buka Peta Interaktif ODC & Jalur Fiber</span>
        </a>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. TABEL MONITORING PESANAN TERBARU            -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
          <h3 class="text-sm font-extrabold text-slate-900">Pesanan Pelanggan Baru Masuk (Live Monitoring)</h3>
        </div>
        <p class="text-xs text-slate-400 mt-0.5">Daftar pemesanan paket WiFi dari web yang baru masuk</p>
      </div>
      <a href="{{ route('admin.pesanan') }}" class="text-xs text-brand hover:underline font-bold flex items-center gap-1">
        Lihat Semua Pesanan ({{ $stats['total_orders'] ?? 0 }}) &rarr;
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-100">
          <tr>
            <th class="px-5 py-3">No. Order & Waktu</th>
            <th class="px-4 py-3">Pelanggan</th>
            <th class="px-4 py-3">Paket Internet</th>
            <th class="px-4 py-3">Jadwal Pasang</th>
            <th class="px-4 py-3">Pembayaran</th>
            <th class="px-4 py-3">Status Pesanan</th>
            <th class="px-4 py-3 text-right">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($recentOrders as $rOrder)
            <tr class="hover:bg-slate-50 transition">
              <td class="px-5 py-3.5 whitespace-nowrap">
                <span class="font-mono font-bold text-slate-800 text-xs block">{{ $rOrder->order_number }}</span>
                <span class="text-[10px] text-slate-400">{{ $rOrder->created_at ? $rOrder->created_at->diffForHumans() : '-' }}</span>
              </td>
              <td class="px-4 py-3.5">
                <p class="font-bold text-slate-900">{{ $rOrder->customer_name }}</p>
                <p class="text-[10px] text-slate-400">{{ $rOrder->customer_phone }}</p>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="font-bold text-slate-800 block">{{ $rOrder->package_name }}</span>
                <span class="text-brand font-black text-xs">Rp{{ number_format($rOrder->total, 0, ',', '.') }}</span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                <span class="text-slate-700 block">{{ $rOrder->installation_date ? $rOrder->installation_date->format('d M Y') : '-' }}</span>
                <span class="text-[10px] text-slate-400 uppercase font-bold">{{ $rOrder->installation_time ?? 'pagi' }}</span>
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                @if($rOrder->payment_status === 'Lunas')
                  <span class="bg-emerald-50 text-emerald-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-emerald-200">Lunas</span>
                @else
                  <span class="bg-amber-50 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-amber-200">{{ $rOrder->payment_status }}</span>
                @endif
              </td>
              <td class="px-4 py-3.5 whitespace-nowrap">
                @if($rOrder->status === 'Menunggu Konfirmasi')
                  <span class="bg-amber-100 text-amber-800 font-extrabold text-[10px] px-2 py-0.5 rounded-full animate-pulse">Menunggu Konfirmasi</span>
                @elseif($rOrder->status === 'Selesai')
                  <span class="bg-emerald-100 text-emerald-800 font-bold text-[10px] px-2 py-0.5 rounded-full">Selesai / Aktif</span>
                @else
                  <span class="bg-blue-50 text-blue-700 font-bold text-[10px] px-2 py-0.5 rounded-full">{{ $rOrder->status }}</span>
                @endif
              </td>
              <td class="px-4 py-3.5 text-right whitespace-nowrap">
                <a href="{{ route('admin.pesanan', ['q' => $rOrder->order_number]) }}" class="text-xs text-brand hover:underline font-bold">
                  Kelola &rarr;
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center py-6 text-slate-400">Belum ada pesanan masuk.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 4. DUA TABEL PENGINTAIAN: TAGIHAN & TIKET     -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- Kolom Kiri (7 Col): Pengintaian Tagihan Terbaru -->
    <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900">Pengintaian Tagihan Pelanggan</h3>
          <p class="text-xs text-slate-400 mt-0.5">Status pembayaran invoice pelanggan terkini</p>
        </div>
        <a href="{{ route('admin.tagihan') }}" class="text-xs text-brand hover:underline font-bold">
          Lihat Semua &rarr;
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-100">
            <tr>
              <th class="px-5 py-3">Invoice & Pelanggan</th>
              <th class="px-4 py-3">Paket</th>
              <th class="px-4 py-3">Nominal</th>
              <th class="px-4 py-3">Status</th>
              <th class="px-4 py-3 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 font-medium">
            @foreach(array_slice($bills, 0, 5) as $bill)
              <tr class="hover:bg-slate-50/70 transition">
                <td class="px-5 py-3.5">
                  <p class="font-bold text-slate-900 leading-tight">{{ $bill['customer_name'] }}</p>
                  <p class="text-[10px] text-slate-400 font-mono mt-0.5">{{ $bill['id'] }}</p>
                </td>
                <td class="px-4 py-3.5 text-slate-600">{{ $bill['package_name'] }}</td>
                <td class="px-4 py-3.5 font-bold text-slate-900">Rp{{ $bill['total'] }}</td>
                <td class="px-4 py-3.5">
                  @if($bill['status'] === 'Lunas')
                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Lunas</span>
                  @elseif($bill['status'] === 'Menunggu Verifikasi')
                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Verifikasi</span>
                  @elseif($bill['status'] === 'Jatuh Tempo')
                    <span class="bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Jatuh Tempo</span>
                  @else
                    <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2 py-0.5 rounded-md">Belum Bayar</span>
                  @endif
                </td>
                <td class="px-4 py-3.5 text-right">
                  <a href="{{ route('admin.tagihan', ['q' => $bill['id']]) }}" class="text-xs text-brand hover:underline font-bold">
                    Cek
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <!-- Kolom Kanan (5 Col): Laporan Gangguan / Trouble Ticket Terkini -->
    <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
      <div class="p-5 border-b border-slate-100 flex items-center justify-between">
        <div>
          <h3 class="text-sm font-extrabold text-slate-900">Tiket Gangguan Pelanggan</h3>
          <p class="text-xs text-slate-400 mt-0.5">Laporan masalah jaringan masuk</p>
        </div>
        <a href="{{ route('admin.laporan') }}" class="text-xs text-brand hover:underline font-bold">
          Kelola Tiket &rarr;
        </a>
      </div>

      <div class="divide-y divide-slate-100 text-xs">
        @foreach(array_slice($tickets, 0, 4) as $ticket)
          <div class="p-4 hover:bg-slate-50 transition">
            <div class="flex items-start justify-between gap-2">
              <div>
                <span class="text-[10px] font-bold font-mono text-slate-400">{{ $ticket['id'] }}</span>
                <h4 class="font-bold text-slate-900 mt-0.5">{{ $ticket['type'] }}</h4>
              </div>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-md shrink-0 {{ $ticket['priority'] === 'Kritis' ? 'bg-red-100 text-red-700' : ($ticket['priority'] === 'Tinggi' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700') }}">
                {{ $ticket['priority'] }}
              </span>
            </div>
            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $ticket['description'] }}</p>
            <div class="flex items-center justify-between mt-2.5 pt-2 border-t border-slate-50 text-[10px] text-slate-400">
              <span class="font-medium text-slate-600">{{ $ticket['customer_name'] }} ({{ $ticket['odp'] }})</span>
              <span class="font-semibold text-brand">{{ $ticket['status'] }}</span>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  </div>

</div>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('dashboardMrtgChart');
    if (ctx) {
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: @json($mrtg['interfaces'][0]['history']['labels']),
          datasets: [
            {
              label: 'Download / Inbound (Mbps)',
              data: @json($mrtg['interfaces'][0]['history']['inbound']),
              borderColor: '#10b981',
              backgroundColor: 'rgba(16, 185, 129, 0.15)',
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 2,
            },
            {
              label: 'Upload / Outbound (Mbps)',
              data: @json($mrtg['interfaces'][0]['history']['outbound']),
              borderColor: '#3b82f6',
              backgroundColor: 'rgba(59, 130, 246, 0.15)',
              borderWidth: 2,
              fill: true,
              tension: 0.35,
              pointRadius: 2,
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
              labels: { font: { family: 'Poppins', size: 10 }, boxWidth: 12 }
            },
            tooltip: {
              callbacks: {
                label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y} Mbps`
              }
            }
          },
          scales: {
            x: { grid: { display: false }, ticks: { font: { size: 9 } } },
            y: {
              grid: { color: '#f1f5f9' },
              ticks: { font: { size: 9 }, callback: (v) => v + 'M' }
            }
          }
        }
      });
    }
  });
</script>
@endpush
@endsection
