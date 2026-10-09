@extends('layouts.technician')

@section('title', 'Tiket Pemasangan Baru - Teknisi Banterpool')

@section('content')
<div class="space-y-6"
     x-data="{
        openModal: false,
        selectedOrder: null,
        statusVal: '',
        ontSnVal: '',
        opmDbmVal: '',
        odpVal: '',
        notesVal: '',

        editOrder(order) {
            this.selectedOrder = order;
            this.statusVal = order.status;
            this.ontSnVal = order.ont_sn || '';
            this.opmDbmVal = order.opm_dbm || '';
            this.odpVal = order.assigned_odp || '';
            this.notesVal = order.technician_notes || '';
            this.openModal = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. HEADER & SEARCH                             -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Tiket Pemasangan WiFi Pelanggan Baru</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Daftar pekerjaan instalasi jaringan fiber optik, penarikan drop core, dan aktivasi ONT pelanggan.
      </p>
    </div>

    <!-- Search Box -->
    <form method="GET" action="{{ route('teknisi.pemasangan') }}" class="flex items-center gap-2">
      <input type="hidden" name="scope" value="{{ $scope }}">
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, order, ODP, alamat..."
               class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-amber-500 focus:border-amber-500">
      </div>
      @if($statusFilter !== 'all')
        <input type="hidden" name="status" value="{{ $statusFilter }}">
      @endif
      <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition">
        Cari
      </button>
      @if(!empty($search))
        <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => $statusFilter]) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-2.5 py-2 rounded-xl text-xs" title="Reset Pencarian">
          <i class="fa-solid fa-xmark"></i>
        </a>
      @endif
    </form>
  </div>

  <!-- ============================================== -->
  <!-- 2. SCOPE TABS (TUGAS SAYA VS SEMUA LAPANGAN)   -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2">
    <a href="{{ route('teknisi.pemasangan', ['scope' => 'my', 'status' => $statusFilter, 'q' => $search]) }}"
       class="px-4 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-xs {{ $scope === 'my' ? 'bg-slate-900 text-amber-400 border border-slate-800' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-user-check text-amber-400"></i>
      <span>Tiket Ditugaskan ke Saya</span>
      <span class="px-2 py-0.5 rounded-full text-[10px] {{ $scope === 'my' ? 'bg-amber-400 text-slate-950 font-black' : 'bg-slate-100 text-slate-600' }}">{{ $counts['my_total'] }}</span>
    </a>

    <a href="{{ route('teknisi.pemasangan', ['scope' => 'all', 'status' => $statusFilter, 'q' => $search]) }}"
       class="px-4 py-2 rounded-2xl text-xs font-bold transition flex items-center gap-2 shadow-xs {{ $scope === 'all' ? 'bg-slate-900 text-amber-400 border border-slate-800' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-users text-blue-400"></i>
      <span>Semua Tiket Lapangan</span>
      <span class="px-2 py-0.5 rounded-full text-[10px] {{ $scope === 'all' ? 'bg-slate-700 text-slate-200 font-bold' : 'bg-slate-100 text-slate-600' }}">{{ $counts['all_total'] }}</span>
    </a>
  </div>

  <!-- ============================================== -->
  <!-- 3. STATUS FILTER TABS                          -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
    <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => 'all', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <span>Semua Status</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'all' ? 'bg-slate-950/15' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => 'Jadwal Teknisi', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-amber-500 text-slate-950 font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-regular fa-calendar text-xs"></i>
      <span>Jadwal Pasang</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-slate-950/15' : 'bg-slate-100' }}">{{ $counts['jadwal'] }}</span>
    </a>

    <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => 'Sedang Dipasang', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Dipasang' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-person-digging text-xs"></i>
      <span>Sedang Dipasang</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Sedang Dipasang' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['proses'] }}</span>
    </a>

    <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => 'Selesai', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-circle-check text-xs"></i>
      <span>Selesai Terpasang</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['selesai'] }}</span>
    </a>

    <a href="{{ route('teknisi.pemasangan', ['scope' => $scope, 'status' => 'Kendala Lapangan', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Kendala Lapangan' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-triangle-exclamation text-xs"></i>
      <span>Kendala Lapangan</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Kendala Lapangan' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['kendala'] }}</span>
    </a>
  </div>

  <!-- ============================================== -->
  <!-- 4. LIST TIKET PEMASANGAN                       -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($orders as $order)
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-amber-400 transition">
        <div>
          <!-- Card Header -->
          <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-1.5 flex-wrap">
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $order->order_number }}</span>
                @php
                  $isMyTask = auth()->user() && ($order->technician_id === auth()->id() || $order->technician === auth()->user()->name || str_contains($order->technician ?? '', explode(' ', auth()->user()->name)[0]));
                @endphp
                @if($isMyTask)
                  <span class="bg-amber-100 text-amber-900 border border-amber-300 font-black px-2 py-0.5 rounded-md text-[9px] flex items-center gap-1">
                    <i class="fa-solid fa-user-check text-amber-600"></i>
                    <span>Tugas Anda</span>
                  </span>
                @elseif($order->technician)
                  <span class="bg-slate-100 text-slate-700 border border-slate-200 font-medium px-2 py-0.5 rounded-md text-[9px]">
                    Teknisi: {{ $order->technician }}
                  </span>
                @else
                  <span class="bg-red-50 text-red-700 border border-red-200 font-medium px-2 py-0.5 rounded-md text-[9px]">
                    Belum Ditugaskan
                  </span>
                @endif
              </div>
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
            @elseif($order->status === 'Kendala Lapangan')
              <span class="bg-red-100 text-red-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-xmark"></i> Kendala
              </span>
            @else
              <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-regular fa-clock"></i> Jadwal
              </span>
            @endif
          </div>

          <!-- Customer & Location Detail -->
          <div class="space-y-2 mt-3 text-xs">
            <div class="flex items-start gap-2 text-slate-600">
              <i class="fa-solid fa-location-dot text-red-500 mt-0.5 text-xs shrink-0"></i>
              <span class="leading-relaxed">{{ $order->address }}</span>
            </div>

            <div class="flex items-center justify-between text-[11px] pt-1 text-slate-500">
              <span><i class="fa-solid fa-calendar-day mr-1 text-slate-400"></i>{{ $order->installation_date ? $order->installation_date->format('d M Y') : 'Hari ini' }}</span>
              <span><i class="fa-solid fa-clock mr-1 text-slate-400"></i>{{ ucfirst($order->installation_time ?? 'Pagi') }}</span>
            </div>

            <!-- Paket & ODP Assigned -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 mt-2 space-y-1.5">
              <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500">Paket Layanan:</span>
                <span class="font-bold text-slate-900">{{ $order->package_name }} ({{ $order->speed ?? '20 Mbps' }})</span>
              </div>
              <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500">Titik ODP:</span>
                <span class="font-bold text-blue-600">{{ $order->assigned_odp ?? 'Belum Diatur NOC' }}</span>
              </div>
              <div class="flex items-center justify-between text-[11px]">
                <span class="text-slate-500">Teknisi Bertugas:</span>
                <span class="font-bold {{ $isMyTask ? 'text-amber-700' : 'text-slate-800' }}">
                  {{ $order->technician ?: 'Belum ditentukan' }}
                </span>
              </div>
              @if($order->opm_dbm)
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-500">Redaman OPM:</span>
                  <span class="font-bold text-emerald-600">{{ $order->opm_dbm }}</span>
                </div>
              @endif
              @if($order->ont_sn)
                <div class="flex items-center justify-between text-[11px]">
                  <span class="text-slate-500">SN Modem (ONT):</span>
                  <span class="font-mono text-[10px] text-slate-700 bg-white px-1.5 py-0.5 rounded border border-slate-200">{{ $order->ont_sn }}</span>
                </div>
              @endif
            </div>

            @if($order->admin_notes)
              <p class="text-[11px] text-slate-500 italic bg-amber-50/70 p-2.5 rounded-xl border border-amber-200/50 mt-2">
                <span class="font-bold text-amber-800 not-italic">Catatan NOC:</span> {{ $order->admin_notes }}
              </p>
            @endif

            @if($order->technician_notes)
              <p class="text-[11px] text-emerald-800 bg-emerald-50/70 p-2.5 rounded-xl border border-emerald-200/50 mt-1">
                <span class="font-bold">Laporan Teknisi:</span> {{ $order->technician_notes }}
              </p>
            @endif
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1.5">
            <!-- Tombol WA -->
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($order->customer_name) }}%2C%20saya%20teknisi%20Banterpool%20mengenai%20jadwal%20pemasangan%20WiFi..."
               target="_blank"
               class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs"
               title="Chat WhatsApp Pelanggan">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>WhatsApp</span>
            </a>

            <!-- Tombol Maps -->
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($order->address) }}"
               target="_blank"
               class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold p-2 rounded-xl transition"
               title="Petunjuk Arah Google Maps">
              <i class="fa-solid fa-diamond-turn-right text-sm"></i>
            </a>
          </div>

          <!-- Tombol Update Teknisi -->
          <button type="button" @click="editOrder({{ json_encode($order) }})"
                  class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Update</span>
          </button>
        </div>

      </div>
    @empty
      <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-8">
        <i class="fa-solid fa-calendar-xmark text-4xl text-slate-300 mb-2"></i>
        <h3 class="text-sm font-bold text-slate-800">Tidak ada tiket pemasangan</h3>
        <p class="text-xs text-slate-500 mt-1">Tidak ada data pesanan sesuai kriteria pencarian atau filter yang dipilih.</p>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  <div class="pt-2">
    {{ $orders->links() }}
  </div>

  <!-- ============================================== -->
  <!-- 4. MODAL UPDATE PROGRES TEKNISI                -->
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
            <h3 class="text-base font-black text-slate-900" id="modal-title">Update Pekerjaan Pemasangan</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedOrder ? selectedOrder.order_number + ' - ' + selectedOrder.customer_name : ''"></p>
          </div>
          <button type="button" @click="openModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Info Paket & Biaya Langganan (POV Pelanggan) -->
        <template x-if="selectedOrder">
          <div class="mt-3 p-3 bg-amber-50/70 border border-amber-200/60 rounded-xl flex items-center justify-between text-xs">
            <div>
              <span class="text-slate-500 text-[10px] uppercase font-bold block">Paket Berlangganan:</span>
              <span class="font-bold text-slate-900 text-xs" x-text="selectedOrder.package_name + ' (' + (selectedOrder.speed || '20 Mbps') + ')'"></span>
            </div>
            <div class="text-right">
              <span class="text-slate-500 text-[10px] uppercase font-bold block">Tarif Paket:</span>
              <span class="font-black text-amber-700 text-xs" x-text="'Rp ' + Number(selectedOrder.total || 110000).toLocaleString('id-ID')"></span>
            </div>
          </div>
        </template>

        <form :action="'{{ url('/teknisi/pemasangan') }}/' + (selectedOrder ? selectedOrder.id : '') + '/status'" method="POST" class="mt-4 space-y-4 text-xs">
          @csrf

          <!-- Status Pekerjaan -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Status Pekerjaan Lapangan</label>
            <select name="status" x-model="statusVal" required
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-amber-500 focus:border-amber-500">
              <option value="Jadwal Teknisi">Jadwal Teknisi (Menunggu Dikerjakan)</option>
              <option value="Sedang Dipasang">Sedang Dipasang (Teknisi di Lokasi)</option>
              <option value="Selesai">Selesai (Aktivasi Berhasil - Resmi Masuk Data Pelanggan)</option>
              <option value="Kendala Lapangan">Kendala Lapangan (ODP Penuh / Jalur Terhalang)</option>
            </select>
          </div>

          <!-- ODP Port -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Titik ODP & Port Terkoneksi</label>
            <input type="text" name="assigned_odp" x-model="odpVal" placeholder="Contoh: ODP-CLK-01 (Port 04)"
                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-amber-500 focus:border-amber-500">
          </div>

          <!-- Grid: OPM & Serial Number -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Hasil Redaman OPM</label>
              <div class="relative">
                <input type="text" name="opm_dbm" x-model="opmDbmVal" placeholder="-18.5 dBm"
                       class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-amber-500 focus:border-amber-500 font-bold text-emerald-700">
              </div>
              <span class="text-[10px] text-slate-400">Standar aman: -16 s/d -22 dBm</span>
            </div>

            <div>
              <label class="block font-bold text-slate-700 mb-1">Serial Number Modem (ONT)</label>
              <input type="text" name="ont_sn" x-model="ontSnVal" placeholder="ZTEGC1234567"
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-mono uppercase focus:ring-amber-500 focus:border-amber-500">
              <span class="text-[10px] text-slate-400">Label SN di belakang modem</span>
            </div>
          </div>

          <!-- Catatan Teknisi -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Pemasangan Teknisi</label>
            <textarea name="technician_notes" x-model="notesVal" rows="3" placeholder="Contoh: Kabel drop core terpakai 85m, modem dipasang di ruang tamu, speedtest simetris lancar..."
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs focus:ring-amber-500 focus:border-amber-500"></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="openModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-5 py-2.5 rounded-xl transition shadow-md flex items-center gap-1.5">
              <i class="fa-solid fa-floppy-disk"></i>
              <span>Simpan Laporan</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

</div>
@endsection
