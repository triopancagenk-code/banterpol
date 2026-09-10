@extends('layouts.collector')

@section('title', 'Tiket Pemasangan Baru - Kolektor Banterpool')

@section('content')
<div class="space-y-6">

  <!-- ============================================== -->
  <!-- 1. HEADER & SEARCH                             -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Monitoring Tiket Pemasangan Baru</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Koordinasi biaya pasang baru, jadwal aktivasi pelanggan, dan metode pembayaran di wilayah penagihan.
      </p>
    </div>

    <!-- Search Box -->
    <form method="GET" action="{{ route('kolektor.pemasangan') }}" class="flex items-center gap-2">
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, order, alamat..."
               class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-emerald-500 focus:border-emerald-500">
      </div>
      @if($statusFilter !== 'all')
        <input type="hidden" name="status" value="{{ $statusFilter }}">
      @endif
      <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition">
        Cari
      </button>
      @if(!empty($search))
        <a href="{{ route('kolektor.pemasangan', ['status' => $statusFilter]) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-2.5 py-2 rounded-xl text-xs" title="Reset Pencarian">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  <!-- ============================================== -->
  <!-- 2. STATUS FILTER TABS                          -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
    <a href="{{ route('kolektor.pemasangan', ['status' => 'all', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <span>Semua Tiket</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('kolektor.pemasangan', ['status' => 'Menunggu Konfirmasi', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Konfirmasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-regular fa-clock text-xs"></i>
      <span>Menunggu</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Menunggu Konfirmasi' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['menunggu'] }}</span>
    </a>

    <a href="{{ route('kolektor.pemasangan', ['status' => 'Jadwal Teknisi', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-calendar text-xs"></i>
      <span>Jadwal Pasang</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['jadwal'] }}</span>
    </a>

    <a href="{{ route('kolektor.pemasangan', ['status' => 'Sedang Dipasang', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Dipasang' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-person-digging text-xs"></i>
      <span>Sedang Dipasang</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Sedang Dipasang' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['proses'] }}</span>
    </a>

    <a href="{{ route('kolektor.pemasangan', ['status' => 'Selesai', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-circle-check text-xs"></i>
      <span>Selesai</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['selesai'] }}</span>
    </a>
  </div>

  <!-- ============================================== -->
  <!-- 3. LIST DATA PEMASANGAN                        -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($orders as $order)
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-emerald-400 transition">
        <div>
          <!-- Card Header -->
          <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $order->order_number }}</span>
              <h3 class="text-sm font-bold text-slate-900 mt-0.5 leading-snug">{{ $order->customer_name }}</h3>
            </div>

            <!-- Status Badge -->
            @if($order->status === 'Selesai')
              <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-check"></i> Selesai
              </span>
            @elseif($order->status === 'Sedang Dipasang')
              <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-spinner fa-spin"></i> Dipasang
              </span>
            @else
              <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-regular fa-clock"></i> {{ $order->status }}
              </span>
            @endif
          </div>

          <!-- Customer & Location Detail -->
          <div class="space-y-2 mt-3 text-xs">
            <div class="flex items-start gap-2 text-slate-600">
              <i class="fa-solid fa-location-dot text-red-500 mt-0.5 text-xs shrink-0"></i>
              <span class="leading-relaxed">{{ $order->address }}</span>
            </div>

            <!-- Financial info for collector -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 mt-2 space-y-1.5 text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Paket Layanan:</span>
                <span class="font-bold text-slate-900">{{ $order->package_name }} ({{ $order->speed ?? '20 Mbps' }})</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Metode Bayar:</span>
                <span class="font-semibold text-slate-800">{{ $order->payment_method }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Status Bayar:</span>
                <span class="font-bold {{ $order->payment_status === 'Lunas' ? 'text-emerald-600' : 'text-amber-600' }}">
                  {{ $order->payment_status ?? 'Lunas' }}
                </span>
              </div>
              <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 font-bold">
                <span class="text-slate-700">Total Biaya:</span>
                <span class="text-emerald-700 font-black">Rp {{ number_format($order->total, 0, ',', '.') }}</span>
              </div>
            </div>

            @if($order->technician)
              <p class="text-[11px] text-slate-500 pt-1">
                <i class="fa-solid fa-user-gear mr-1 text-slate-400"></i>Teknisi: <span class="font-semibold text-slate-800">{{ $order->technician }}</span>
              </p>
            @endif
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1.5">
            <!-- WA Contact -->
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($order->customer_name) }}%2C%20saya%20petugas%20Banterpool%20mengenai%20jadwal%20pemasangan%20WiFi%20nomor%20{{ urlencode($order->order_number) }}..."
               target="_blank"
               class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>WhatsApp</span>
            </a>

            <!-- Maps -->
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}"
               target="_blank"
               class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold p-2 rounded-xl transition"
               title="Petunjuk Arah Google Maps">
              <i class="fa-solid fa-diamond-turn-right text-sm"></i>
            </a>
          </div>

          <span class="text-[11px] text-slate-400 font-medium">
            Tgl: {{ $order->installation_date ? $order->installation_date->format('d M') : 'Hari ini' }}
          </span>
        </div>

      </div>
    @empty
      <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-8">
        <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-2"></i>
        <h3 class="text-sm font-bold text-slate-800">Tidak ada tiket pemasangan</h3>
        <p class="text-xs text-slate-500 mt-1">Tidak ada data pemasangan baru sesuai filter yang dipilih.</p>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  <div class="pt-2">
    {{ $orders->links() }}
  </div>

</div>
@endsection
