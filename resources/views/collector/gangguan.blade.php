@extends('layouts.collector')

@section('title', 'Tiket Gangguan Jaringan - Kolektor Banterpool')

@section('content')
<div class="space-y-6">

  <!-- ============================================== -->
  <!-- 1. HEADER & SEARCH                             -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Monitoring Gangguan Jaringan Pelanggan</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Informasi kendala teknis pelanggan di lapangan. Sangat berguna agar kolektor mengetahui situasi sebelum menagih.
      </p>
    </div>

    <!-- Search Box -->
    <form method="GET" action="{{ route('kolektor.gangguan') }}" class="flex items-center gap-2">
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, tiket, kendala, alamat..."
               class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-emerald-500 focus:border-emerald-500">
      </div>
      @if($statusFilter !== 'all')
        <input type="hidden" name="status" value="{{ $statusFilter }}">
      @endif
      <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition">
        Cari
      </button>
      @if(!empty($search))
        <a href="{{ route('kolektor.gangguan', ['status' => $statusFilter]) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-2.5 py-2 rounded-xl text-xs" title="Reset Pencarian">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  <!-- ============================================== -->
  <!-- 2. STATUS FILTER TABS                          -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
    <a href="{{ route('kolektor.gangguan', ['status' => 'all', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <span>Semua Tiket</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('kolektor.gangguan', ['status' => 'Menunggu Respon', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Respon' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-regular fa-clock text-xs"></i>
      <span>Menunggu Respon</span>
    </a>

    <a href="{{ route('kolektor.gangguan', ['status' => 'Sedang Ditangani', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Ditangani' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-person-digging text-xs"></i>
      <span>Sedang Ditangani</span>
    </a>

    <a href="{{ route('kolektor.gangguan', ['status' => 'Selesai', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-circle-check text-xs"></i>
      <span>Selesai Normal</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['selesai'] }}</span>
    </a>

    @if(($counts['kritis'] ?? 0) > 0)
      <!-- Warning Alert Badge -->
      <div class="ml-auto text-xs bg-red-50 border border-red-200 text-red-700 font-bold px-3 py-1.5 rounded-xl flex items-center gap-2">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span>{{ $counts['kritis'] }} Kendala Kritis Aktif di Lapangan</span>
      </div>
    @endif
  </div>

  <!-- ============================================== -->
  <!-- 3. LIST DATA GANGGUAN                          -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($tickets as $ticket)
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-red-400 transition">
        <div>
          <!-- Header Card -->
          <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $ticket['id'] }}</span>
                @if($ticket['priority'] === 'Kritis')
                  <span class="bg-red-100 text-red-700 text-[9px] font-black px-1.5 py-0.5 rounded uppercase">Kritis</span>
                @elseif($ticket['priority'] === 'Tinggi')
                  <span class="bg-amber-100 text-amber-700 text-[9px] font-black px-1.5 py-0.5 rounded uppercase">Tinggi</span>
                @else
                  <span class="bg-slate-100 text-slate-600 text-[9px] font-black px-1.5 py-0.5 rounded uppercase">Normal</span>
                @endif
              </div>
              <h3 class="text-sm font-bold text-slate-900 mt-1 leading-snug">{{ $ticket['type'] }}</h3>
            </div>

            <!-- Status Badge -->
            @if($ticket['status'] === 'Selesai')
              <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-check"></i> Selesai
              </span>
            @elseif($ticket['status'] === 'Sedang Ditangani')
              <span class="bg-blue-100 text-blue-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-person-digging"></i> Diproses
              </span>
            @else
              <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-regular fa-clock"></i> Antre
              </span>
            @endif
          </div>

          <!-- Customer & Problem Detail -->
          <div class="space-y-2 mt-3 text-xs">
            <div>
              <p class="font-bold text-slate-800">{{ $ticket['customer_name'] }}</p>
              <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-location-dot text-red-500 text-xs shrink-0"></i>
                <span class="line-clamp-1">{{ $ticket['address'] }}</span>
              </p>
            </div>

            <div class="bg-red-50/50 border border-red-100 rounded-2xl p-3 text-[11px] text-slate-700">
              <span class="font-bold text-red-700 block mb-0.5">Keluhan Pelanggan:</span>
              <p class="italic">"{{ $ticket['description'] }}"</p>
            </div>

            <div class="bg-slate-50 rounded-2xl p-2.5 border border-slate-100 space-y-1 text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Titik ODP:</span>
                <span class="font-bold text-blue-600">{{ $ticket['odp'] ?? 'ODP Banterpool' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Waktu Lapor:</span>
                <span class="text-slate-600">{{ $ticket['created_at'] }}</span>
              </div>
            </div>

            @if(!empty($ticket['notes']))
              <div class="bg-emerald-50/70 p-2 rounded-xl border border-emerald-200/50 text-[11px] text-emerald-900">
                <span class="font-bold block">Status Teknisi:</span>
                <p>{{ $ticket['notes'] }}</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1.5">
            <!-- WA -->
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket['customer_phone']) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($ticket['customer_name']) }}%2C%20saya%20petugas%20Banterpool%20mengenai%20tiket%20kendala%20{{ urlencode($ticket['id']) }}..."
               target="_blank"
               class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs"
               title="Hubungi Pelanggan via WhatsApp">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>WhatsApp</span>
            </a>

            <!-- Maps -->
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket['address']) }}"
               target="_blank"
               class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold p-2 rounded-xl transition"
               title="Petunjuk Arah Google Maps">
              <i class="fa-solid fa-diamond-turn-right text-sm"></i>
            </a>
          </div>

          <span class="text-[10px] text-slate-400 font-semibold">
            <i class="fa-solid fa-user-gear mr-1"></i>{{ $ticket['technician'] ?? 'Tim Teknisi' }}
          </span>
        </div>

      </div>
    @empty
      <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-8">
        <i class="fa-solid fa-shield-check text-4xl text-emerald-400 mb-2"></i>
        <h3 class="text-sm font-bold text-slate-800">Tidak ada tiket gangguan</h3>
        <p class="text-xs text-slate-500 mt-1">Semua jaringan dalam kondisi prima dan tidak ada laporan kendala.</p>
      </div>
    @endforelse
  </div>

</div>
@endsection
