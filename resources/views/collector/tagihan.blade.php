@extends('layouts.collector')

@section('title', 'Tagihan & Input Tunai - Kolektor Banterpool')

@section('content')
<div class="space-y-6"
     x-data="{
        openPayModal: false,
        openManualModal: false,
        selectedBill: null,
        cashAmountVal: 0,
        paidDateVal: '{{ now()->format('Y-m-d') }}',
        notesVal: '',

        initiatePayment(bill) {
            this.selectedBill = bill;
            this.cashAmountVal = bill.total;
            this.notesVal = 'Diterima tunai oleh ' + '{{ auth()->user()->name }}' + ' di rumah pelanggan.';
            this.openPayModal = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. HEADER & SEARCH & QUICK ACTIONS             -->
  <!-- ============================================== -->
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Manajemen Tagihan & Penerimaan Tunai</h2>
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        Pencatatan uang tunai pelanggan, terbitkan kuitansi digital, dan input tagihan manual lapangan.
      </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <button type="button" @click="openManualModal = true"
              class="bg-white hover:bg-slate-50 text-slate-800 border border-slate-300 px-3.5 py-2 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-plus text-emerald-600"></i>
        <span>Input Tagihan Manual</span>
      </button>

      <!-- Search Box -->
      <form method="GET" action="{{ route('kolektor.tagihan') }}" class="flex items-center gap-1.5">
        <div class="relative w-full sm:w-64">
          <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, invoice, kuitansi..."
                 class="w-full pl-9 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        @if($statusFilter !== 'all')
          <input type="hidden" name="status" value="{{ $statusFilter }}">
        @endif
        <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-xl text-xs font-bold transition">
          Cari
        </button>
        @if(!empty($search))
          <a href="{{ route('kolektor.tagihan', ['status' => $statusFilter]) }}" class="bg-slate-200 hover:bg-slate-300 text-slate-700 px-2.5 py-2 rounded-xl text-xs" title="Reset Pencarian">
            <i class="fa-solid fa-xmark"></i>
          </a>
        @endif
      </form>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 2. STATUS FILTER TABS                          -->
  <!-- ============================================== -->
  <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
    <a href="{{ route('kolektor.tagihan', ['status' => 'all', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <span>Semua Tagihan</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['all'] }}</span>
    </a>

    <a href="{{ route('kolektor.tagihan', ['status' => 'Belum Bayar', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Belum Bayar' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-regular fa-clock text-xs"></i>
      <span>Belum Bayar</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Belum Bayar' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['belum_bayar'] }}</span>
    </a>

    <a href="{{ route('kolektor.tagihan', ['status' => 'Jatuh Tempo', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jatuh Tempo' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-triangle-exclamation text-xs"></i>
      <span>Jatuh Tempo</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Jatuh Tempo' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['jatuh_tempo'] }}</span>
    </a>

    <a href="{{ route('kolektor.tagihan', ['status' => 'Lunas', 'q' => $search]) }}"
       class="px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Lunas' ? 'bg-blue-600 text-white font-bold shadow-xs' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
      <i class="fa-solid fa-circle-check text-xs"></i>
      <span>Lunas</span>
      <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $statusFilter === 'Lunas' ? 'bg-white/20' : 'bg-slate-100' }}">{{ $counts['lunas'] }}</span>
    </a>

    <!-- Info Tunai Terkumpul -->
    <div class="ml-auto text-xs bg-emerald-50 border border-emerald-200 text-emerald-800 font-bold px-3.5 py-1.5 rounded-xl flex items-center gap-2">
      <i class="fa-solid fa-money-bill-trend-up text-emerald-600"></i>
      <span>Total Setoran Tunai: Rp {{ number_format($counts['total_tunai'], 0, ',', '.') }}</span>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. LIST DATA TAGIHAN                           -->
  <!-- ============================================== -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($bills as $bill)
      <div class="bg-white rounded-3xl border border-slate-200 p-5 shadow-xs flex flex-col justify-between hover:border-emerald-400 transition">
        <div>
          <!-- Card Header -->
          <div class="flex items-start justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $bill->bill_number }}</span>
              <h3 class="text-sm font-bold text-slate-900 mt-0.5 leading-snug">{{ $bill->customer_name }}</h3>
            </div>

            <!-- Status Badge -->
            @if($bill->status === 'Lunas')
              <span class="bg-emerald-100 text-emerald-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-check"></i> Lunas
              </span>
            @elseif($bill->status === 'Jatuh Tempo')
              <span class="bg-red-100 text-red-700 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-solid fa-triangle-exclamation"></i> Tempo
              </span>
            @else
              <span class="bg-amber-100 text-amber-800 text-[10px] font-black px-2.5 py-1 rounded-full shrink-0 flex items-center gap-1">
                <i class="fa-regular fa-clock"></i> Belum Bayar
              </span>
            @endif
          </div>

          <!-- Customer & Bill Detail -->
          <div class="space-y-2 mt-3 text-xs">
            <div class="flex items-start gap-2 text-slate-600">
              <i class="fa-solid fa-location-dot text-red-500 mt-0.5 text-xs shrink-0"></i>
              <span class="leading-relaxed">{{ $bill->address }}</span>
            </div>

            <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
              <span><i class="fa-solid fa-wifi mr-1 text-emerald-600"></i>{{ $bill->package_name }} ({{ $bill->speed ?? '20 Mbps' }})</span>
              <span><i class="fa-solid fa-phone mr-1 text-slate-400"></i>{{ $bill->customer_phone }}</span>
            </div>

            <!-- Total Box -->
            <div class="bg-slate-50 rounded-2xl p-3 border border-slate-100 mt-2 space-y-1">
              <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>Periode:</span>
                <span class="font-medium text-slate-700">{{ $bill->period }}</span>
              </div>
              <div class="flex items-center justify-between text-[11px] text-slate-500">
                <span>Batas Waktu:</span>
                <span class="font-bold {{ $bill->status === 'Jatuh Tempo' ? 'text-red-600' : 'text-slate-700' }}">{{ $bill->due_date }}</span>
              </div>
              <div class="flex items-center justify-between text-xs pt-1 border-t border-slate-200/60">
                <span class="font-bold text-slate-700">Total Tagihan:</span>
                <span class="text-sm font-black text-emerald-700">Rp {{ number_format($bill->total, 0, ',', '.') }}</span>
              </div>
            </div>

            <!-- Detail Lunas / Tunai -->
            @if($bill->status === 'Lunas')
              <div class="bg-emerald-50/70 p-2.5 rounded-xl border border-emerald-200/50 text-[11px] text-emerald-900 space-y-1">
                <div class="flex items-center justify-between">
                  <span class="font-bold">No. Kuitansi:</span>
                  <span class="font-mono font-bold">{{ $bill->receipt_number ?? '-' }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span>Metode:</span>
                  <span class="font-semibold">{{ $bill->payment_method }}</span>
                </div>
                @if($bill->collected_by)
                  <p class="text-[10px] text-emerald-700 pt-0.5">Diterima oleh: {{ $bill->collected_by }}</p>
                @endif
              </div>
            @elseif($bill->collector_notes)
              <p class="text-[11px] text-slate-500 italic bg-amber-50/70 p-2 rounded-xl border border-amber-200/50">
                <span class="font-bold text-amber-800 not-italic">Catatan:</span> {{ $bill->collector_notes }}
              </p>
            @endif
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex items-center gap-1.5">
            <!-- Tombol WA -->
            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $bill->customer_phone) }}?text=Halo%20Bpk%2FIbu%20{{ urlencode($bill->customer_name) }}%2C%20saya%20Bayu%20dari%20Kolektor%20Banterpool%20mengenai%20tagihan%20WiFi%20nomor%20{{ urlencode($bill->bill_number) }}%20sebesar%20Rp%20{{ number_format($bill->total, 0, ',', '.') }}.%20Apakah%20bisa%20saya%20kunjungi%20ke%20rumah%20untuk%20serah%20terima%20tunai%3F%20Terima%20kasih."
               target="_blank"
               class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs"
               title="Hubungi Pelanggan via WhatsApp">
              <i class="fa-brands fa-whatsapp text-sm"></i>
              <span>WA</span>
            </a>

            <!-- Tombol Maps -->
            <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($bill->address) }}"
               target="_blank"
               class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold p-2 rounded-xl transition"
               title="Petunjuk Arah Maps">
              <i class="fa-solid fa-diamond-turn-right text-sm"></i>
            </a>
          </div>

          <!-- Tombol Aksi Utama -->
          @if($bill->status === 'Lunas')
            <a href="{{ route('kolektor.tagihan.kuitansi', $bill->id) }}"
               class="bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-receipt"></i>
              <span>Kuitansi</span>
            </a>
          @else
            <button type="button" @click="initiatePayment({{ json_encode($bill) }})"
                    class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
              <i class="fa-solid fa-money-bill-wave"></i>
              <span>Terima Tunai</span>
            </button>
          @endif
        </div>

      </div>
    @empty
      <div class="col-span-full text-center py-12 bg-white rounded-3xl border border-slate-200 p-8">
        <i class="fa-solid fa-receipt text-4xl text-slate-300 mb-2"></i>
        <h3 class="text-sm font-bold text-slate-800">Tidak ada data tagihan</h3>
        <p class="text-xs text-slate-500 mt-1">Tidak ada data tagihan sesuai kriteria filter atau pencarian Anda.</p>
      </div>
    @endforelse
  </div>

  <!-- Pagination -->
  <div class="pt-2">
    {{ $bills->links() }}
  </div>

  <!-- ============================================== -->
  <!-- 4. MODAL INPUT PEMBAYARAN TUNAI               -->
  <!-- ============================================== -->
  <div x-show="openPayModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto"
       aria-labelledby="modal-pay-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
      <!-- Backdrop -->
      <div x-show="openPayModal"
           @click="openPayModal = false"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal Panel -->
      <div x-show="openPayModal"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           class="relative inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full p-6">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900" id="modal-pay-title">Input Pembayaran Tunai Pelanggan</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-text="selectedBill ? selectedBill.bill_number + ' • ' + selectedBill.customer_name : ''"></p>
          </div>
          <button type="button" @click="openPayModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form action="{{ route('kolektor.tagihan.bayar-tunai') }}" method="POST" class="mt-4 space-y-4 text-xs">
          @csrf
          <input type="hidden" name="bill_id" :value="selectedBill ? selectedBill.id : ''">

          <!-- Preview Info Pelanggan -->
          <div class="bg-emerald-50 rounded-2xl p-4 border border-emerald-200/80">
            <div class="flex items-center justify-between mb-1">
              <span class="text-slate-600">Pelanggan:</span>
              <span class="font-bold text-slate-900" x-text="selectedBill ? selectedBill.customer_name : ''"></span>
            </div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-slate-600">Paket Layanan:</span>
              <span class="font-bold text-slate-900" x-text="selectedBill ? selectedBill.package_name : ''"></span>
            </div>
            <div class="flex items-center justify-between pt-2 border-t border-emerald-200/80">
              <span class="font-bold text-emerald-900">Total Yang Harus Dibayar:</span>
              <span class="font-black text-base text-emerald-700" x-text="selectedBill ? 'Rp ' + Number(selectedBill.total).toLocaleString('id-ID') : ''"></span>
            </div>
          </div>

          <!-- Nominal Uang Tunai Yang Diterima -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Jumlah Uang Tunai Diterima (Rp)</label>
            <div class="relative">
              <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-xs">Rp</span>
              <input type="number" name="cash_amount" x-model="cashAmountVal" required min="1000"
                     class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold text-slate-900 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <span class="text-[10px] text-slate-400">Pastikan uang fisik telah dihitung pas di depan pelanggan</span>
          </div>

          <!-- Tanggal Terima -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Tanggal Serah Terima Uang</label>
            <input type="date" name="paid_date" x-model="paidDateVal" required
                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
          </div>

          <!-- Catatan Serah Terima -->
          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Serah Terima Kolektor</label>
            <textarea name="collector_notes" x-model="notesVal" rows="2"
                      placeholder="Contoh: Diterima tunai pas oleh Kolektor Bayu di rumah pelanggan, titip ke istri..."
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs focus:ring-emerald-500 focus:border-emerald-500"></textarea>
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="openPayModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2.5 rounded-xl transition shadow-md flex items-center gap-1.5">
              <i class="fa-solid fa-receipt"></i>
              <span>Simpan & Terbitkan Kuitansi</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. MODAL INPUT TAGIHAN MANUAL BARU             -->
  <!-- ============================================== -->
  <div x-show="openManualModal"
       x-cloak
       class="fixed inset-0 z-50 overflow-y-auto"
       aria-labelledby="modal-manual-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      
      <!-- Backdrop -->
      <div x-show="openManualModal"
           @click="openManualModal = false"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" aria-hidden="true"></div>

      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal Panel -->
      <div x-show="openManualModal"
           x-transition:enter="ease-out duration-300"
           x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave="ease-in duration-200"
           x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
           x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
           class="relative inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full p-6">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div>
            <h3 class="text-base font-black text-slate-900" id="modal-manual-title">Buat Tagihan Manual Baru</h3>
            <p class="text-xs text-slate-500 mt-0.5">Khusus pelanggan offline / pembayaran tunai non-sistem</p>
          </div>
          <button type="button" @click="openManualModal = false" class="text-slate-400 hover:text-slate-600 text-lg">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <form action="{{ route('kolektor.tagihan.input-manual') }}" method="POST" class="mt-4 space-y-3.5 text-xs">
          @csrf

          <div>
            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Pelanggan</label>
            <input type="text" name="customer_name" required placeholder="Contoh: Bpk. Suwarto"
                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP</label>
              <input type="text" name="customer_phone" required placeholder="081234567890"
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Email (Opsional)</label>
              <input type="email" name="customer_email" placeholder="email@gmail.com"
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
            </div>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Alamat Pemasangan</label>
            <textarea name="address" required rows="2" placeholder="Contoh: RT 03/RW 02 Desa Karanglo, Cilongok"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs focus:ring-emerald-500 focus:border-emerald-500"></textarea>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Paket Layanan</label>
              <select name="package_name" id="collector_package_select" required
                      onchange="document.getElementById('collector_total_input').value = this.options[this.selectedIndex].getAttribute('data-price');"
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold focus:ring-emerald-500 focus:border-emerald-500">
                <option value="Paket 20 Mbps" data-price="110000" selected>Paket 20 Mbps - Rp 110.000 / bln (20 Mbps)</option>
                <option value="Paket 30 Mbps" data-price="165000">Paket 30 Mbps - Rp 165.000 / bln (30 Mbps)</option>
                <option value="Paket 50 Mbps" data-price="220000">Paket 50 Mbps - Rp 220.000 / bln (50 Mbps)</option>
              </select>
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Total Tagihan (Rp)</label>
              <input type="number" name="total" id="collector_total_input" value="110000" required min="1000"
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-emerald-700 focus:ring-emerald-500 focus:border-emerald-500">
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-slate-700 mb-1">Periode Tagihan</label>
              <input type="text" name="period" value="{{ now()->format('01 M Y') }} – {{ now()->addMonth()->format('01 M Y') }}" required
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
            </div>
            <div>
              <label class="block font-bold text-slate-700 mb-1">Jatuh Tempo</label>
              <input type="text" name="due_date" value="{{ now()->format('05 M Y') }}" required
                     class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs focus:ring-emerald-500 focus:border-emerald-500">
            </div>
          </div>

          <!-- Checkbox Langsung Lunas Tunai -->
          <div class="bg-emerald-50/70 p-3 rounded-xl border border-emerald-200">
            <label class="flex items-center gap-2 cursor-pointer font-bold text-emerald-900">
              <input type="checkbox" name="is_paid_immediately" value="1" checked
                     class="w-4 h-4 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
              <span>Pelanggan langsung bayar tunai di tempat (Terbitkan Kuitansi)</span>
            </label>
            <p class="text-[10px] text-emerald-700 ml-6 mt-0.5">Jika dicentang, status tagihan otomatis Lunas dan kuitansi langsung terbit.</p>
          </div>

          <div>
            <label class="block font-bold text-slate-700 mb-1">Catatan Kolektor</label>
            <input type="text" name="collector_notes" placeholder="Catatan tambahan penagihan..."
                   class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs focus:ring-emerald-500 focus:border-emerald-500">
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
            <button type="button" @click="openManualModal = false" class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-4 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-bold px-5 py-2.5 rounded-xl transition shadow-md flex items-center gap-1.5">
              <i class="fa-solid fa-floppy-disk"></i>
              <span>Simpan Tagihan</span>
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

</div>
@endsection
