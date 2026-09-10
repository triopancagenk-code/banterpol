@extends('layouts.technician')

@section('title', 'Tiket Gangguan Jaringan - Teknisi Banterpool')

@section('content')
<div class="space-y-6"
     x-data="{
        openModal: false,
        selectedTicket: null,
        statusVal: '',
        opmVal: '',
        notesVal: '',

        editTicket(ticket) {
            this.selectedTicket = ticket;
            this.statusVal = ticket.status;
            this.opmVal = ticket.opm_result || '';
            this.notesVal = ticket.notes || '';
            this.openModal = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. HEADER & SEARCH                             -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-red-500 animate-pulse"></span>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Tiket Gangguan Jaringan (Trouble Tickets)</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Laporan kendala pelanggan, lampu LOS merah, putus kabel drop core, dan degradasi sinyal fiber optik.
      </p>
    </div>

    <!-- Search Box -->
    <form method="GET" action="{{ route('teknisi.gangguan') }}" class="flex items-center gap-2">
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari tiket, nama, ODP, alamat..."
               class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-red-500 focus:border-red-500">
      </div>
      @if($statusFilter !== 'all')
        <input type="hidden" name="status" value="{{ $statusFilter }}">
      @endif
      <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition">
        Cari
      </button>
      @if(!empty($search))
        <a href="{{ route('teknisi.gangguan', ['status' => $statusFilter]) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-2.5 py-2 rounded-xl text-xs" title="Reset Pencarian">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  <!-- ============================================== -->
  <!-- 2. STATUS FILTER TABS                          -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
    <a href="{{ route('teknisi.gangguan', ['status' => 'all', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <span>Semua Tiket</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('teknisi.gangguan', ['status' => 'Menunggu Respon', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Respon' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-regular fa-clock text-xs"></i>
      <span>Menunggu Respon</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Menunggu Respon' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['menunggu'] }}</span>
    </a>

    <a href="{{ route('teknisi.gangguan', ['status' => 'Sedang Ditangani', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Ditangani' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-person-digging text-xs"></i>
      <span>Sedang Ditangani</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Sedang Ditangani' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['proses'] }}</span>
    </a>

    <a href="{{ route('teknisi.gangguan', ['status' => 'Selesai', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-circle-check text-xs"></i>
      <span>Selesai Normal</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['selesai'] }}</span>
    </a>

    @if(($counts['kritis'] ?? 0) > 0)
      <span class="ml-auto text-xs text-red-600 font-bold bg-red-50 border border-red-200 px-3 py-1.5 rounded-xl flex items-center gap-1.5">
        <i class="fa-solid fa-bolt"></i>
        <span>{{ $counts['kritis'] }} Gangguan Kritis Aktif</span>
      </span>
    @endif
  </div>

  <!-- ============================================== -->
  <!-- 3. LIST TIKET GANGGUAN                         -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($tickets as $ticket)
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-red-400 transition">
        <div>
          <!-- Card Header -->
          <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $ticket['id'] }}</span>
                @if($ticket['priority'] === 'Kritis')
                  <span class="bg-red-100 text-red-700 text-[9px] font-black px-1.5 py-0.5 rounded uppercase flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-ping"></span> Kritis
                  </span>
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

          <!-- Description & Detail -->
          <div class="space-y-2 mt-3 text-xs">
            <!-- Customer info -->
            <div>
              <p class="font-bold text-slate-800">{{ $ticket['customer_name'] }}</p>
              <p class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                <i class="fa-solid fa-location-dot text-red-500 text-xs shrink-0"></i>
                <span class="line-clamp-1">{{ $ticket['address'] }}</span>
              </p>
            </div>

            <!-- Problem Description Quote -->
            <div class="bg-red-50/50 border border-red-100 rounded-2xl p-3 text-[11px] text-slate-700">
              <span class="font-bold text-red-700 block mb-0.5">Keluhan Pelanggan:</span>
              <p class="italic">"{{ $ticket['description'] }}"</p>
            </div>

            <!-- ODP & Technical Info -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 space-y-1.5 text-[11px]">
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Titik ODP:</span>
                <span class="font-bold text-blue-600">{{ $ticket['odp'] ?? 'ODP Banterpool' }}</span>
              </div>
              @if(!empty($ticket['opm_result']))
                <div class="flex items-center justify-between">
                  <span class="text-slate-500">Hasil Redaman OPM:</span>
                  <span class="font-bold {{ str_contains($ticket['opm_result'], '-24') ? 'text-amber-600' : 'text-emerald-600' }}">
                    {{ $ticket['opm_result'] }}
                  </span>
                </div>
              @endif
              <div class="flex items-center justify-between">
                <span class="text-slate-500">Waktu Lapor:</span>
                <span class="text-slate-600">{{ $ticket['created_at'] }}</span>
              </div>
            </div>

            @if(!empty($ticket['notes']))
              <div class="bg-emerald-50/70 p-2.5 rounded-xl border border-emerald-200/50 text-[11px] text-emerald-900">
                <span class="font-bold block">Tindakan Lapangan:</span>
                <p>{{ $ticket['notes'] }}</p>
              </div>
            @endif
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1.5">
            <!-- Tombol WA -->
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $ticket['customer_phone']) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($ticket['customer_name']) }}%2C%20saya%20teknisi%20Banterpool%20mengenai%20tiket%20kendala%20{{ urlencode($ticket['id']) }}..."
               target="_blank"
               class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs"
               title="Hubungi Pelanggan via WhatsApp">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>WhatsApp</span>
            </a>

            <!-- Tombol Maps -->
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($ticket['address']) }}"
               target="_blank"
               class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold p-2 rounded-xl transition"
               title="Petunjuk Arah Google Maps">
              <i class="fa-solid fa-diamond-turn-right text-sm"></i>
            </a>
          </div>

          <!-- Tombol Tindak Lanjuti -->
          <button type="button" @click="editTicket({{ json_encode($ticket) }})"
                  class="bg-red-600 hover:bg-red-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
            <i class="fa-solid fa-wrench"></i>
            <span>Tindakan</span>
          </button>
        </div>

      </div>
    @empty
      <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-8">
        <i class="fa-solid fa-shield-check text-4xl text-emerald-400 mb-2"></i>
        <h3 class="text-sm font-bold text-slate-800">Tidak ada tiket gangguan</h3>
        <p class="text-xs text-slate-500 mt-1">Semua jaringan dalam kondisi normal dan tidak ada laporan kendala aktif.</p>
      </div>
    @endforelse
  </div>

  <!-- ============================================== -->
  <!-- 4. MODAL UPDATE PENANGANAN GANGGUAN            -->
  <!-- ============================================== -->
  <div x-show="openModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto"
       aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
      <!-- Backdrop -->
      <div x-show="openModal"
           @click="openModal = false"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal Panel -->
      <div x-show="openModal"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           class="relative inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full p-6">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900" id="modal-title">Tindakan Perbaikan Gangguan</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedTicket ? selectedTicket.id + ' - ' + selectedTicket.customer_name : ''"></p>
          </div>
          <button type="button" @click="openModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form :action="'{{ url('/teknisi/gangguan') }}/' + (selectedTicket ? selectedTicket.id : '') + '/status'" method="POST" class="mt-4 space-y-4 text-xs">
          @csrf

          <!-- Status Penanganan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Penanganan Kendala</label>
            <select name="status" x-model="statusVal" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-red-500 focus:border-red-500">
              <option value="Menunggu Respon">Menunggu Respon (Dalam Antrean)</option>
              <option value="Sedang Ditangani">Sedang Ditangani (Teknisi Sedang Memperbaiki)</option>
              <option value="Selesai">Selesai (Gangguan Berhasil Diperbaiki & Normal)</option>
            </select>
          </div>

          <!-- Pengukuran Redaman OPM -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Hasil Akhir Ukur Redaman OPM (dBm)</label>
            <input type="text" name="opm_result" x-model="opmVal" placeholder="Contoh: -18.8 dBm"
                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-red-500 focus:border-red-500 font-bold text-emerald-700">
            <span class="text-[10px] text-slate-400">Diisi setelah proses perbaikan sambungan/splicing selesai</span>
          </div>

          <!-- Catatan Solusi / Tindakan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Laporan Tindakan & Solusi Lapangan</label>
            <textarea name="notes" x-model="notesVal" rows="3" required placeholder="Contoh: Telah dilakukan splicing ulang core ke-2 di ODP-CLK-08 akibat sambungan terlepas. Redaman normal -18.8 dBm, lampu LOS mati, koneksi kembali lancar."
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs focus:ring-red-500 focus:border-red-500"></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="openModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit" class="bg-red-600 hover:bg-red-500 text-white font-bold px-5 py-2.5 rounded-xl transition shadow-md flex items-center gap-1.5">
              <i class="fa-solid fa-floppy-disk"></i>
              <span>Simpan & Selesaikan</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

</div>
@endsection
