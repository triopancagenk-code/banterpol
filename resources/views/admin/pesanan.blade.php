@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Monitoring Pesanan')
@section('page-title', 'Monitoring Pemesanan Pelanggan')

@section('content')
<div class="space-y-6"
     x-data="{
        openModal: false,
        openCreateModal: false,
        selectedOrder: null,

        // Form Create Pesanan Manual
        createForm: {
            packageName: 'Paket 20 Mbps',
            speed: '20 Mbps',
            price: 110000,
            installationFee: 0,
            total() {
                return Number(this.price || 0) + Number(this.installationFee || 0);
            }
        },

        onPackageChange(event) {
            const val = event.target.value;
            this.createForm.packageName = val;
            if (val.includes('50')) {
                this.createForm.speed = '50 Mbps';
                this.createForm.price = 220000;
            } else if (val.includes('30')) {
                this.createForm.speed = '30 Mbps';
                this.createForm.price = 165000;
            } else {
                this.createForm.speed = '20 Mbps';
                this.createForm.price = 110000;
            }
        },
        
        viewOrder(order) {
            this.selectedOrder = order;
            this.openModal = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. HEADER & EXPORT ACTIONS                     -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-black text-slate-900 tracking-tight">Monitoring Pemesanan Pelanggan</h2>
      <p class="text-xs text-slate-500 mt-0.5">
        Pantau pesanan baru yang masuk dari web, atur jadwal survei, tugaskan teknisi, dan pantau progres aktivasi.
      </p>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <!-- Tombol Input Manual Pesanan -->
      <button type="button" @click="openCreateModal = true"
              class="bg-brand hover:bg-red-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-cart-plus"></i>
        <span>Tambah Pesanan Manual</span>
      </button>

      <a href="{{ route('admin.pesanan.export', request()->query()) }}"
         class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-file-excel"></i>
        <span>Export Excel</span>
      </a>
      <button type="button" onclick="window.print()"
              class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-print"></i>
        <span>Cetak Rekap</span>
      </button>
      <a href="{{ route('admin.pesanan') }}"
         class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold p-2 rounded-xl transition" title="Refresh">
        <i class="fa-solid fa-rotate-right"></i>
      </a>
    </div>
  </div>

  <!-- Alert Notifications -->
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

  @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl shadow-xs">
      <div class="flex items-center gap-2 mb-1">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span class="text-xs font-bold">Terjadi kesalahan input data:</span>
      </div>
      <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. STATISTIC METRIC CARDS                      -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
    
    <!-- Total Pesanan -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-2xs">
      <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pesanan</p>
      <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $counts['all'] }}</h3>
      <span class="text-[10px] text-slate-500 font-medium">Semua transaksi</span>
    </div>

    <!-- Menunggu Konfirmasi -->
    <div class="bg-amber-50/60 p-4 rounded-2xl border border-amber-200/70 shadow-2xs">
      <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">Menunggu</p>
      <h3 class="text-2xl font-black text-amber-900 mt-1">{{ $counts['menunggu'] }}</h3>
      <span class="text-[10px] text-amber-700 font-medium">Perlu verifikasi</span>
    </div>

    <!-- Jadwal Teknisi -->
    <div class="bg-blue-50/60 p-4 rounded-2xl border border-blue-200/70 shadow-2xs">
      <p class="text-[10px] font-bold text-blue-700 uppercase tracking-wider">Jadwal Teknisi</p>
      <h3 class="text-2xl font-black text-blue-900 mt-1">{{ $counts['jadwal'] }}</h3>
      <span class="text-[10px] text-blue-700 font-medium">Siap pasang</span>
    </div>

    <!-- Sedang Dipasang -->
    <div class="bg-indigo-50/60 p-4 rounded-2xl border border-indigo-200/70 shadow-2xs">
      <p class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Dipasang</p>
      <h3 class="text-2xl font-black text-indigo-900 mt-1">{{ $counts['proses'] }}</h3>
      <span class="text-[10px] text-indigo-700 font-medium">Tim di lokasi</span>
    </div>

    <!-- Selesai / Aktif -->
    <div class="bg-emerald-50/60 p-4 rounded-2xl border border-emerald-200/70 shadow-2xs">
      <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">Selesai / Aktif</p>
      <h3 class="text-2xl font-black text-emerald-900 mt-1">{{ $counts['selesai'] }}</h3>
      <span class="text-[10px] text-emerald-700 font-medium">Online</span>
    </div>

    <!-- Dibatalkan -->
    <div class="bg-red-50/60 p-4 rounded-2xl border border-red-200/70 shadow-2xs">
      <p class="text-[10px] font-bold text-red-700 uppercase tracking-wider">Dibatalkan</p>
      <h3 class="text-2xl font-black text-red-900 mt-1">{{ $counts['batal'] }}</h3>
      <span class="text-[10px] text-red-700 font-medium">Gagal / batal</span>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 3. FILTER TABS & SEARCH BAR                    -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col lg:flex-row lg:items-center justify-between gap-4">
    
    <!-- Status Filter Pills -->
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
      
      <a href="{{ route('admin.pesanan', ['status' => 'all', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Semua</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['all'] }}</span>
      </a>

      <a href="{{ route('admin.pesanan', ['status' => 'Menunggu Konfirmasi', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Konfirmasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
        <i class="fa-regular fa-clock"></i>
        <span>Menunggu</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Konfirmasi' ? 'bg-white/20' : 'bg-amber-200' }}">{{ $counts['menunggu'] }}</span>
      </a>

      <a href="{{ route('admin.pesanan', ['status' => 'Jadwal Teknisi', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-blue-50 text-blue-800 border border-blue-200 hover:bg-blue-100' }}">
        <i class="fa-solid fa-calendar-check"></i>
        <span>Jadwal Teknisi</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Jadwal Teknisi' ? 'bg-white/20' : 'bg-blue-200' }}">{{ $counts['jadwal'] }}</span>
      </a>

      <a href="{{ route('admin.pesanan', ['status' => 'Sedang Dipasang', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Sedang Dipasang' ? 'bg-indigo-600 text-white font-bold shadow-xs' : 'bg-indigo-50 text-indigo-800 border border-indigo-200 hover:bg-indigo-100' }}">
        <i class="fa-solid fa-screwdriver-wrench"></i>
        <span>Sedang Dipasang</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Sedang Dipasang' ? 'bg-white/20' : 'bg-indigo-200' }}">{{ $counts['proses'] }}</span>
      </a>

      <a href="{{ route('admin.pesanan', ['status' => 'Selesai', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Selesai' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
        <i class="fa-solid fa-circle-check"></i>
        <span>Selesai / Aktif</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Selesai' ? 'bg-white/20' : 'bg-emerald-200' }}">{{ $counts['selesai'] }}</span>
      </a>

      <a href="{{ route('admin.pesanan', ['status' => 'Dibatalkan', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Dibatalkan' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100' }}">
        <i class="fa-solid fa-ban"></i>
        <span>Dibatalkan</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Dibatalkan' ? 'bg-white/20' : 'bg-red-200' }}">{{ $counts['batal'] }}</span>
      </a>

    </div>

    <!-- Search Box -->
    <form method="GET" action="{{ route('admin.pesanan') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      <div class="relative w-full sm:w-72">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}"
               placeholder="Cari no. order, nama, hp, paket..."
               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
      </div>
      <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition">
        Cari
      </button>
      @if($search)
        <a href="{{ route('admin.pesanan', ['status' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-500 font-bold">Reset</a>
      @endif
    </form>

  </div>

  <!-- ============================================== -->
  <!-- 4. TABEL MONITORING PESANAN                     -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-center text-xs">
        <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] border-b border-slate-200 tracking-wider">
          <tr>
            <th class="py-3.5 px-4 text-center">No. Order & Tanggal</th>
            <th class="py-3.5 px-4 text-center">Pelanggan</th>
            <th class="py-3.5 px-4 text-center">Paket & Biaya</th>
            <th class="py-3.5 px-4 text-center">Jadwal Pasang</th>
            <th class="py-3.5 px-4 text-center">Pembayaran</th>
            <th class="py-3.5 px-4 text-center">Status Pesanan</th>
            <th class="py-3.5 px-4 text-center">Teknisi / ODP</th>
            <th class="py-3.5 px-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($orders as $order)
            <tr class="hover:bg-slate-50/80 transition duration-150">
              
              <!-- 1. Order Number & Date -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <span class="font-mono font-bold text-slate-900 text-xs block">{{ $order->order_number }}</span>
                <span class="text-[10px] text-slate-400">{{ $order->created_at ? $order->created_at->translatedFormat('d M Y, H:i') : '-' }} WIB</span>
              </td>

              <!-- 2. Customer Info -->
              <td class="py-3.5 px-4 text-center">
                <div class="font-bold text-slate-900 text-xs">{{ $order->customer_name }}</div>
                <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                  <a href="https://wa.me/{{ preg_replace('/^0/', '62', preg_replace('/\D/', '', $order->customer_phone)) }}?text=Halo%20{{ urlencode($order->customer_name) }},%20kami%20dari%20Banterpool%20terkait%20pesanan%20{{ $order->order_number }}"
                     target="_blank"
                     class="text-emerald-600 hover:text-emerald-700 font-semibold inline-flex items-center gap-1">
                    <i class="fa-brands fa-whatsapp text-xs"></i>
                    <span>{{ $order->customer_phone }}</span>
                  </a>
                </div>
                <p class="text-[10px] text-slate-400 truncate max-w-xs mx-auto mt-0.5" title="{{ $order->address }}">
                  <i class="fa-solid fa-location-dot text-red-500 mr-0.5"></i> {{ $order->address }}
                </p>
              </td>

              <!-- 3. Package & Total -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <span class="font-bold text-slate-900 block">{{ $order->package_name }}</span>
                <span class="text-brand font-black text-xs block">Rp{{ number_format($order->total, 0, ',', '.') }}</span>
                <span class="text-[10px] text-slate-400">{{ $order->speed ?? '-' }}</span>
              </td>

              <!-- 4. Installation Schedule -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($order->installation_date)
                  <span class="font-semibold text-slate-800 block">
                    <i class="fa-regular fa-calendar text-slate-400 mr-1"></i>
                    {{ $order->installation_date->translatedFormat('d M Y') }}
                  </span>
                  <span class="text-[10px] font-bold uppercase tracking-wider {{ $order->installation_time === 'pagi' ? 'text-amber-600' : 'text-blue-600' }}">
                    <i class="fa-regular fa-clock mr-0.5"></i>
                    {{ $order->installation_time === 'pagi' ? 'Pagi (08-12)' : 'Siang (13-16)' }}
                  </span>
                @else
                  <span class="text-slate-400 italic">Belum ditentukan</span>
                @endif
              </td>

              <!-- 5. Payment Status -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($order->payment_status === 'Lunas')
                  <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-solid fa-check text-xs"></i> Lunas
                  </span>
                @elseif($order->payment_status === 'Gagal')
                  <span class="bg-red-50 text-red-700 border border-red-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-solid fa-xmark text-xs"></i> Gagal
                  </span>
                @else
                  <span class="bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-regular fa-clock text-xs"></i> Menunggu
                  </span>
                @endif
                <span class="block text-[10px] text-slate-400 mt-1">{{ $order->payment_method ?? 'Metode -' }}</span>
              </td>

              <!-- 6. Order Status -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($order->status === 'Menunggu Konfirmasi')
                  <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-extrabold px-2.5 py-1 rounded-full inline-flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-bell text-xs"></i> Menunggu Konfirmasi
                  </span>
                @elseif($order->status === 'Jadwal Teknisi')
                  <span class="bg-blue-50 text-blue-700 border border-blue-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-solid fa-calendar-check text-xs"></i> Jadwal Teknisi
                  </span>
                @elseif($order->status === 'Sedang Dipasang')
                  <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-solid fa-screwdriver-wrench text-xs"></i> Sedang Dipasang
                  </span>
                @elseif($order->status === 'Selesai')
                  <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    <i class="fa-solid fa-wifi text-xs"></i> Selesai / Aktif
                  </span>
                @else
                  <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                    {{ $order->status }}
                  </span>
                @endif
              </td>

              <!-- 7. Technician & ODP -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($order->technician)
                  <span class="font-bold text-slate-900 block text-[11px]">{{ $order->technician }}</span>
                @else
                  <span class="text-slate-400 italic text-[11px]">Belum ditugaskan</span>
                @endif
                @if($order->assigned_odp)
                  <span class="bg-slate-100 text-slate-700 text-[9px] font-bold px-1.5 py-0.5 rounded mt-0.5 inline-block">
                    ODP: {{ $order->assigned_odp }}
                  </span>
                @endif
              </td>

              <!-- 8. Actions -->
              <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <div class="flex items-center justify-center gap-1.5">
                  <a href="{{ route('admin.pesanan.formulir', $order->id) }}"
                     target="_blank"
                     title="Cetak Formulir Pendaftaran / Berlangganan"
                     class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold text-xs px-2.5 py-1.5 rounded-xl transition inline-flex items-center gap-1.5 shadow-2xs">
                    <i class="fa-solid fa-print text-slate-500 text-xs"></i>
                    <span>Formulir</span>
                  </a>
                  <button type="button" @click="viewOrder({{ Js::from($order) }})"
                          class="bg-brand hover:bg-brand-700 text-white font-bold text-xs px-3 py-1.5 rounded-xl transition inline-flex items-center gap-1 shadow-2xs">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i>
                    <span>Kelola</span>
                  </button>
                </div>
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="8" class="text-center py-16 text-slate-400">
                <i class="fa-solid fa-box-open text-4xl mb-3 text-slate-300"></i>
                <p class="font-bold text-sm text-slate-600">Tidak ada pesanan yang ditemukan</p>
                <p class="text-xs text-slate-400 mt-1">{{ $search ? 'Coba sesuaikan kata kunci pencarian Anda.' : 'Belum ada antrean pemesanan baru dari pelanggan saat ini.' }}</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. MODAL DETAIL & KELOLA PESANAN               -->
  <!-- ============================================== -->
  <div x-show="openModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <template x-if="selectedOrder">
          <div class="space-y-6">
            
            <!-- Header Modal -->
            <div class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <div class="flex items-center gap-2 mb-1">
                  <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-lg" x-text="selectedOrder.order_number"></span>
                  <span class="text-xs font-bold text-brand" x-text="selectedOrder.package_name"></span>
                </div>
                <h3 class="text-lg font-black text-slate-900">Kelola & Detail Pesanan Pelanggan</h3>
                <p class="text-xs text-slate-400">Atur progres instalasi dan penugasan teknisi Banterpool</p>
              </div>
              <div class="pr-8 sm:pr-0">
                <a :href="'{{ url('admin/pesanan') }}/' + selectedOrder.id + '/formulir'"
                   target="_blank"
                   class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition inline-flex items-center gap-1.5 shadow-2xs">
                  <i class="fa-solid fa-print"></i>
                  <span>Cetak Formulir</span>
                </a>
              </div>
            </div>

            <!-- Customer & Installation Overview -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
              <div class="space-y-2">
                <p class="font-bold text-slate-400 uppercase text-[10px]">Data Pelanggan</p>
                <p class="font-black text-slate-900 text-sm" x-text="selectedOrder.customer_name"></p>
                <p class="text-slate-600"><i class="fa-solid fa-phone text-slate-400 mr-1"></i> <span x-text="selectedOrder.customer_phone"></span></p>
                <p class="text-slate-600"><i class="fa-solid fa-envelope text-slate-400 mr-1"></i> <span x-text="selectedOrder.customer_email"></span></p>
                <template x-if="selectedOrder.id_card_number">
                  <p class="text-slate-600"><i class="fa-regular fa-id-card text-slate-400 mr-1"></i> KTP: <span class="font-mono font-bold" x-text="selectedOrder.id_card_number"></span></p>
                </template>
                <template x-if="selectedOrder.birth_place || selectedOrder.birth_date">
                  <p class="text-slate-600"><i class="fa-regular fa-calendar text-slate-400 mr-1"></i> TTL: <span x-text="(selectedOrder.birth_place ? selectedOrder.birth_place + ', ' : '') + (selectedOrder.birth_date ? new Date(selectedOrder.birth_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '')"></span></p>
                </template>
                <p class="text-slate-600 leading-relaxed"><i class="fa-solid fa-location-dot text-brand mr-1"></i> <span x-text="selectedOrder.address"></span></p>
                
                <template x-if="selectedOrder.latitude && selectedOrder.longitude">
                  <div class="pt-1">
                    <a :href="'https://www.google.com/maps?q=' + selectedOrder.latitude + ',' + selectedOrder.longitude"
                       target="_blank"
                       class="inline-flex items-center gap-1.5 bg-white border border-slate-200 text-brand font-bold px-2.5 py-1 rounded-lg text-[10px] hover:bg-red-50">
                      <i class="fa-solid fa-map-location-dot"></i>
                      <span>Buka Google Maps (<span x-text="selectedOrder.latitude + ', ' + selectedOrder.longitude"></span>)</span>
                    </a>
                  </div>
                </template>
              </div>

              <div class="space-y-2 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-4">
                <p class="font-bold text-slate-400 uppercase text-[10px]">Paket & Jadwal Pasang</p>
                <p class="font-bold text-slate-900" x-text="selectedOrder.package_name + ' (' + (selectedOrder.speed || '20 Mbps') + ')'"></p>
                <p class="text-brand font-black text-base">
                  Rp<span x-text="Number(selectedOrder.total).toLocaleString('id-ID')"></span>
                </p>
                <div class="pt-1 space-y-1">
                  <p class="text-slate-600">
                    <strong>Jadwal:</strong> 
                    <span x-text="selectedOrder.installation_date ? new Date(selectedOrder.installation_date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) : '-'"></span>
                    (<span x-text="selectedOrder.installation_time === 'pagi' ? 'Pagi 08-12 WIB' : 'Siang 13-16 WIB'"></span>)
                  </p>
                  <p class="text-slate-600">
                    <strong>Metode Bayar:</strong> <span x-text="selectedOrder.payment_method || '-'"></span>
                  </p>
                </div>
              </div>
            </div>

            <!-- Form Update Status & Assignment -->
            <form :action="'{{ url('admin/pesanan') }}/' + selectedOrder.id + '/status'" method="POST" class="space-y-4 text-xs">
              @csrf

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Status Pesanan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Pengerjaan Pesanan</label>
                  <select name="status" x-model="selectedOrder.status" required
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                    <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                    <option value="Jadwal Teknisi">Jadwal Teknisi</option>
                    <option value="Sedang Dipasang">Sedang Dipasang</option>
                    <option value="Selesai">Selesai / Aktif</option>
                    <option value="Dibatalkan">Dibatalkan</option>
                  </select>
                </div>

                <!-- Paket Berlangganan (Sinkron POV Pelanggan) -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Paket Berlangganan</label>
                  <select name="package_name" x-model="selectedOrder.package_name"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold text-slate-800">
                    <option value="Paket 20 Mbps">Paket 20 Mbps (Rp 110.000 / bln)</option>
                    <option value="Paket 30 Mbps">Paket 30 Mbps (Rp 165.000 / bln)</option>
                    <option value="Paket 50 Mbps">Paket 50 Mbps (Rp 220.000 / bln)</option>
                  </select>
                </div>

              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                
                <!-- Status Pembayaran -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Pembayaran</label>
                  <select name="payment_status" x-model="selectedOrder.payment_status"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                    <option value="Lunas">Lunas</option>
                    <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
                    <option value="Gagal">Gagal / Kadaluarsa</option>
                  </select>
                </div>

                <!-- Teknisi yang Ditugaskan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tugaskan Teknisi</label>
                  <input type="text" name="technician" x-model="selectedOrder.technician"
                         placeholder="Contoh: Mamat (Tim Fiber)"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

              </div>

              <!-- Titik ODP Penugasan -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Titik ODP Penyambungan (GIS Cilongok)</label>
                <select name="assigned_odp" x-model="selectedOrder.assigned_odp"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                  <option value="">Pilih ODP Terdekat</option>
                  @foreach($odpList as $odpId => $odpName)
                    <option value="{{ $odpId }}">{{ $odpId }} - {{ $odpName }}</option>
                  @endforeach
                </select>
              </div>

              <!-- Catatan Admin / NOC -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Catatan Lapangan / Admin</label>
                <textarea name="admin_notes" x-model="selectedOrder.admin_notes" rows="2"
                          placeholder="Catatan kendala tiang, drop core, redaman, atau verifikasi..."
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
              </div>

              <!-- Buttons -->
              <div class="pt-2 flex items-center justify-between gap-3">
                <button type="button" @click="openModal = false"
                        class="px-4 py-2.5 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">
                  Tutup
                </button>

                <div class="flex items-center gap-2">
                  <button type="submit"
                          class="bg-brand hover:bg-brand-700 text-white font-bold px-5 py-2.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                  </button>
                </div>
              </div>

            </form>

            <!-- WhatsApp Direct Button -->
            <div class="pt-2 border-t border-slate-100 flex items-center justify-between">
              <span class="text-slate-400 text-[11px]">Hubungi pelanggan secara langsung:</span>
              <a :href="'https://wa.me/' + (selectedOrder.customer_phone ? selectedOrder.customer_phone.replace(/^0/, '62').replace(/\D/g, '') : '') + '?text=Halo%20' + encodeURIComponent(selectedOrder.customer_name) + ',%20kami%20dari%20Banterpool%20mengenai%20pesanan%20WiFi%20' + selectedOrder.order_number"
                 target="_blank"
                 class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
                <i class="fa-brands fa-whatsapp text-sm"></i>
                <span>Chat WhatsApp Pelanggan</span>
              </a>
            </div>

          </div>
        </template>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 7. MODAL INPUT MANUAL PESANAN BARU             -->
  <!-- ============================================== -->
  <div x-show="openCreateModal"
       style="display: none;"
       class="relative z-50"
       role="dialog"
       aria-modal="true">
    <div x-show="openCreateModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openCreateModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
         @click.self="openCreateModal = false">
      <div x-show="openCreateModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-3xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openCreateModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="space-y-6">
          <!-- Header Modal -->
          <div class="border-b border-slate-100 pb-4">
            <div class="flex items-center gap-2 mb-1">
              <span class="text-xs font-bold bg-red-100 text-brand px-2.5 py-0.5 rounded-lg flex items-center gap-1">
                <i class="fa-solid fa-cart-plus"></i> Input Manual
              </span>
              <span class="text-xs font-bold text-slate-400">POV Admin NOC</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">Tambah Pesanan Pelanggan Baru</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Daftarkan pesanan pemasangan baru pelanggan secara manual langsung ke dalam sistem monitoring Banterpool.
            </p>
          </div>

          <!-- Form Store Pesanan -->
          <form action="{{ route('admin.pesanan.store') }}" method="POST" class="space-y-5 text-xs">
            @csrf

            <!-- Section 1: Data Identitas Pelanggan -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-user text-brand"></i>
                <span>1. Data Identitas & Kontak Pelanggan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Nama Pelanggan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Pelanggan <span class="text-red-500">*</span></label>
                  <input type="text" name="customer_name" required placeholder="Contoh: Budi Santoso"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- No KTP / NIK -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">No. KTP / NIK (16 Digit)</label>
                  <input type="text" name="id_card_number" maxlength="20" placeholder="Contoh: 3302172311940001"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono">
                </div>

                <!-- No WhatsApp / HP -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                  <input type="tel" name="customer_phone" required placeholder="Contoh: 081234567890"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono">
                </div>

                <!-- Email -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
                  <input type="email" name="customer_email" placeholder="Contoh: budi@gmail.com (opsional)"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Tempat Lahir -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tempat Lahir</label>
                  <input type="text" name="birth_place" placeholder="Contoh: Banyumas"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Tanggal Lahir -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir</label>
                  <input type="date" name="birth_date"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>
              </div>

              <!-- Alamat Lengkap -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Pemasangan <span class="text-red-500">*</span></label>
                <textarea name="address" required rows="2" placeholder="Nama Jalan, RT/RW, Dusun, Desa (cth: Batuanten / Jatisaba / Panusupan), Kec. Cilongok"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
              </div>


            </div>

            <!-- Section 2: Paket Layanan & Biaya -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-wifi text-brand"></i>
                <span>2. Pilihan Paket Layanan & Tarif</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Paket Layanan -->
                <div class="sm:col-span-1">
                  <label class="block font-bold text-slate-700 mb-1">Pilih Paket Layanan <span class="text-red-500">*</span></label>
                  <select name="package_name" required @change="onPackageChange($event)"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-slate-800">
                    <option value="Paket 20 Mbps">Paket 20 Mbps (Rp 110.000)</option>
                    <option value="Paket 30 Mbps">Paket 30 Mbps (Rp 165.000)</option>
                    <option value="Paket 50 Mbps">Paket 50 Mbps (Rp 220.000)</option>
                  </select>
                </div>

                <!-- Tarif Bulanan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tarif Bulanan (Rp) <span class="text-red-500">*</span></label>
                  <input type="number" name="price" x-model="createForm.price" required min="0" step="1000"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold font-mono">
                </div>

                <!-- Biaya Pasang Baru -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Biaya Pasang Baru (Rp)</label>
                  <input type="number" name="installation_fee" x-model="createForm.installationFee" min="0" step="1000" placeholder="0"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono">
                  <span class="text-[10px] text-slate-400">0 = Gratis Promo Pemasangan</span>
                </div>
              </div>

              <!-- Total Rangkuman -->
              <div class="p-3 bg-red-50/50 rounded-xl border border-red-100 flex items-center justify-between">
                <span class="font-bold text-slate-700">Total Biaya Awal:</span>
                <span class="text-brand font-black text-base">
                  Rp<span x-text="createForm.total().toLocaleString('id-ID')"></span>
                </span>
              </div>
            </div>

            <!-- Section 3: Penjadwalan & NOC Assignment -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-calendar-days text-brand"></i>
                <span>3. Penjadwalan & Penugasan Teknisi</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Tanggal Pasang -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Rencana Tanggal Pasang</label>
                  <input type="date" name="installation_date" value="{{ date('Y-m-d') }}"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Waktu Pasang -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Sesi Waktu</label>
                  <select name="installation_time"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                    <option value="pagi">Pagi (08:00 - 12:00 WIB)</option>
                    <option value="siang">Siang (13:00 - 16:00 WIB)</option>
                  </select>
                </div>

                <!-- Teknisi Ditugaskan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Teknisi Ditugaskan</label>
                  <select name="technician"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-medium">
                    @foreach($technicians as $techName)
                      <option value="{{ $techName }}">{{ $techName }}</option>
                    @endforeach
                  </select>
                </div>

                <!-- Titik ODP -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Titik ODP Penyambungan</label>
                  <select name="assigned_odp"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                    <option value="ODP-CLK-01">ODP-CLK-01 - ODP Cilongok 01</option>
                    @foreach($odpList as $odpId => $odpName)
                      @if($odpId !== 'ODP-CLK-01')
                        <option value="{{ $odpId }}">{{ $odpId }} - {{ $odpName }}</option>
                      @endif
                    @endforeach
                  </select>
                </div>
              </div>
            </div>

            <!-- Section 4: Status & Pembayaran -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-money-check-dollar text-brand"></i>
                <span>4. Status Pengerjaan & Pembayaran</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Status Pesanan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Pesanan <span class="text-red-500">*</span></label>
                  <select name="status" required
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold">
                    <option value="Menunggu Konfirmasi">Menunggu Konfirmasi</option>
                    <option value="Jadwal Teknisi">Jadwal Teknisi</option>
                    <option value="Sedang Dipasang">Sedang Dipasang</option>
                    <option value="Selesai">Selesai / Aktif (Auto-Terbit Tagihan)</option>
                  </select>
                </div>

                <!-- Status Pembayaran -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Pembayaran <span class="text-red-500">*</span></label>
                  <select name="payment_status" required
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold">
                    <option value="Lunas">Lunas</option>
                    <option value="Menunggu Pembayaran">Menunggu Pembayaran</option>
                  </select>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                  <select name="payment_method"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                    <option value="BCA Virtual Account">BCA Virtual Account</option>
                    <option value="Transfer Bank (BCA)">Transfer Bank (BCA)</option>
                    <option value="Tunai (Kolektor/Admin)">Tunai (Kolektor/Admin)</option>
                    <option value="QRIS">QRIS</option>
                  </select>
                </div>
              </div>

              <!-- Catatan Admin -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Catatan Admin / Keterangan Tambahan</label>
                <textarea name="admin_notes" rows="2" placeholder="Catatan khusus pelanggan, lokasi rumah, atau instruksi..."
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-3 border-t border-slate-200 flex items-center justify-end gap-2.5">
              <button type="button" @click="openCreateModal = false"
                      class="px-5 py-2.5 border border-slate-300 text-slate-700 font-bold rounded-xl hover:bg-slate-50 transition">
                Batal
              </button>
              <button type="submit"
                      class="bg-brand hover:bg-red-700 text-white font-bold px-6 py-2.5 rounded-xl shadow-xs transition flex items-center gap-1.5">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Pesanan</span>
              </button>
            </div>

          </form>

        </div>

      </div>
    </div>
  </div>

</div>
@endsection
