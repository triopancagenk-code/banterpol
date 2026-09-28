@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Pengintaian Tagihan')
@section('page-title', 'Pengintaian & Manajemen Tagihan Pelanggan')

@section('content')
<div class="space-y-6"
     x-data="{
        openModal: false,
        openCreateModal: false,
        selectedBill: null,
        openProofLightbox: false,
        proofUrl: '',
        
        // Form Input Tagihan Manual
        billForm: {
            mode: 'registered', // 'registered' | 'manual'
            orderId: '',
            customerName: '',
            customerPhone: '',
            customerEmail: '',
            address: '',
            packageName: 'Paket 20 Mbps',
            speed: '20 Mbps',
            period: '01 ' + new Date().toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }) + ' – 01 ' + new Date(new Date().getFullYear(), new Date().getMonth() + 1, 1).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
            dueDate: '05 ' + new Date(new Date().getFullYear(), new Date().getMonth() + 1, 5).toLocaleDateString('id-ID', { month: 'short', year: 'numeric' }),
            billDate: new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }),
            amount: 110000,
            tax: 0,
            status: 'Belum Bayar',
            paymentMethod: 'Transfer Bank (BCA)',
            collectorNotes: '',
            total() {
                return Number(this.amount || 0) + Number(this.tax || 0);
            }
        },

        onSelectCustomer(e) {
            const opt = e.target.selectedOptions ? e.target.selectedOptions[0] : null;
            if (!opt || !opt.value) {
                this.billForm.orderId = '';
                return;
            }
            this.billForm.orderId = opt.value;
            this.billForm.customerName = opt.getAttribute('data-name') || '';
            this.billForm.customerPhone = opt.getAttribute('data-phone') || '';
            this.billForm.customerEmail = opt.getAttribute('data-email') || '';
            this.billForm.address = opt.getAttribute('data-address') || '';
            this.billForm.packageName = opt.getAttribute('data-package') || 'Paket 20 Mbps';
            this.billForm.speed = opt.getAttribute('data-speed') || '20 Mbps';
            this.billForm.amount = Number(opt.getAttribute('data-price') || 110000);
        },

        onPackageSelect(e) {
            const val = e.target.value;
            this.billForm.packageName = val;
            if (val.includes('50')) {
                this.billForm.speed = '50 Mbps';
                this.billForm.amount = 220000;
            } else if (val.includes('30')) {
                this.billForm.speed = '30 Mbps';
                this.billForm.amount = 165000;
            } else {
                this.billForm.speed = '20 Mbps';
                this.billForm.amount = 110000;
            }
        },

        viewBill(bill) {
            this.selectedBill = bill;
            this.openModal = true;
        },

        viewProof(url) {
            this.proofUrl = url;
            this.openProofLightbox = true;
        }
     }">

  <!-- ============================================== -->
  <!-- 1. SUMMARY BADGES & FILTER BAR                 -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">
          @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
            Riwayat & Data Rekap Pembayaran
          @else
            Daftar Monitoring Tagihan Pelanggan
          @endif
        </h2>
        @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
          <span class="bg-emerald-100 text-emerald-800 text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border border-emerald-300">
            Terverifikasi Lunas
          </span>
        @else
          <span class="bg-brand/10 text-brand text-[11px] font-extrabold px-2.5 py-0.5 rounded-full border border-brand/20">
            Tagihan Aktif
          </span>
        @endif
      </div>
      <p class="text-xs text-slate-500 mt-0.5">
        @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
          Rekapitulasi seluruh tagihan yang telah diverifikasi dan lunas per bulannya.
        @else
          Pantau status tagihan aktif, konfirmasi transfer pelanggan, dan verifikasi pelunasan invoice.
        @endif
      </p>
    </div>

    <!-- Export or Action Buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Tombol Input Manual Tagihan -->
      <button type="button" @click="openCreateModal = true"
              class="bg-brand hover:bg-red-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-file-invoice-dollar"></i>
        <span>Tambah Tagihan Manual</span>
      </button>

      <a href="{{ route('admin.tagihan.export', request()->query()) }}"
         class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-file-excel"></i>
        <span>Export Excel</span>
      </a>
      <button type="button" onclick="window.print()" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5 shadow-2xs">
        <i class="fa-solid fa-print"></i> Cetak Laporan
      </button>
      <a href="{{ route('admin.tagihan', ['status' => $statusFilter]) }}" class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold p-2 rounded-xl transition" title="Refresh">
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
        <span class="text-xs font-bold">Terjadi kesalahan input tagihan:</span>
      </div>
      <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- Filter Tabs Bar -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
    
    <!-- Status Pills -->
    <div class="flex flex-wrap items-center gap-2 text-xs font-semibold">
      <a href="{{ route('admin.tagihan', ['status' => 'all', 'q' => $search]) }}"
         title="Semua Tagihan Aktif Belum Lunas"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'all' ? 'bg-brand text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Semua</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'all' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['all'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Menunggu Verifikasi', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-amber-500 text-white font-bold shadow-xs' : 'bg-amber-50 text-amber-800 border border-amber-200 hover:bg-amber-100' }}">
        <i class="fa-regular fa-clock"></i>
        <span>Menunggu Verifikasi</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Menunggu Verifikasi' ? 'bg-white/20' : 'bg-amber-200' }}">{{ $counts['menunggu'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Belum Bayar', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Belum Bayar' ? 'bg-slate-800 text-white font-bold shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
        <span>Belum Bayar</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Belum Bayar' ? 'bg-white/20' : 'bg-slate-200' }}">{{ $counts['belum_bayar'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'Jatuh Tempo', 'q' => $search]) }}"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ $statusFilter === 'Jatuh Tempo' ? 'bg-red-600 text-white font-bold shadow-xs' : 'bg-red-50 text-red-800 border border-red-200 hover:bg-red-100' }}">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <span>Jatuh Tempo</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ $statusFilter === 'Jatuh Tempo' ? 'bg-white/20' : 'bg-red-200' }}">{{ $counts['jatuh_tempo'] }}</span>
      </a>

      <a href="{{ route('admin.tagihan', ['status' => 'rekap', 'q' => $search]) }}"
         title="Data Rekapitulasi Tagihan yang Sudah Terverifikasi Lunas"
         class="px-3.5 py-1.5 rounded-xl transition flex items-center gap-1.5 {{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'bg-emerald-600 text-white font-bold shadow-xs' : 'bg-emerald-50 text-emerald-800 border border-emerald-200 hover:bg-emerald-100' }}">
        <i class="fa-solid fa-receipt"></i>
        <span>Riwayat / Rekap Pembayaran</span>
        <span class="text-[10px] px-1.5 py-0.2 rounded-full {{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 'bg-white/20' : 'bg-emerald-200' }}">{{ $counts['rekap'] ?? $counts['lunas'] }}</span>
      </a>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.tagihan') }}" class="flex items-center gap-2">
      <input type="hidden" name="status" value="{{ $statusFilter }}">
      @if(!empty($monthFilter) && $monthFilter !== 'all')
        <input type="hidden" name="month" value="{{ $monthFilter }}">
      @endif
      <div class="relative w-full sm:w-64">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama, invoice, no hp..."
               class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
      </div>
      <button type="submit" class="bg-brand text-white px-3 py-1.5 rounded-xl text-xs font-bold hover:bg-brand-700 transition">
        Cari
      </button>
      @if($search || (($monthFilter ?? 'all') !== 'all'))
        <a href="{{ route('admin.tagihan', ['status' => $statusFilter]) }}" class="text-xs text-slate-400 hover:text-red-500 font-bold">Reset</a>
      @endif
    </form>

  </div>

  <!-- Banner & Filter Rekap Bulanan (Muncul Khusus di Tab Riwayat/Rekap Pembayaran) -->
  @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
    <div class="bg-emerald-50/70 border border-emerald-200/90 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 shadow-2xs">
      <div class="flex items-center gap-3.5">
        <div class="w-11 h-11 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-lg shrink-0 shadow-xs">
          <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-emerald-950 uppercase tracking-wider">Rekapitulasi Pembayaran Per Bulan</span>
            <span class="bg-emerald-200/70 text-emerald-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Terverifikasi Lunas</span>
          </div>
          <p class="text-xs text-slate-600 mt-0.5">
            Total Pemasukan: <span class="font-black text-emerald-700 text-sm">Rp{{ number_format($rekapNominal ?? 0, 0, ',', '.') }}</span>
            &bull; <span class="font-bold text-slate-700">{{ $rekapCount ?? 0 }} Tagihan</span> Telah Terverifikasi
          </p>
        </div>
      </div>

      <!-- Dropdown Pilih Bulan Rekap -->
      <form method="GET" action="{{ route('admin.tagihan') }}" class="flex items-center gap-2 shrink-0">
        <input type="hidden" name="status" value="rekap">
        @if(!empty($search))
          <input type="hidden" name="q" value="{{ $search }}">
        @endif
        <div class="flex items-center gap-1.5 bg-white border border-emerald-300 rounded-xl px-3 py-1.5 shadow-2xs">
          <i class="fa-regular fa-calendar text-emerald-600 text-xs"></i>
          <span class="text-xs font-bold text-slate-600">Bulan:</span>
          <select name="month" onchange="this.form.submit()"
                  class="text-xs font-bold bg-transparent border-0 p-0 text-slate-800 focus:ring-0 cursor-pointer">
            <option value="all">Semua Bulan</option>
            @foreach($availableMonths ?? [] as $m)
              <option value="{{ $m['key'] }}" {{ ($monthFilter ?? 'all') === $m['key'] ? 'selected' : '' }}>
                {{ $m['label'] }}
              </option>
            @endforeach
          </select>
        </div>
        @if(($monthFilter ?? 'all') !== 'all')
          <a href="{{ route('admin.tagihan', ['status' => 'rekap', 'q' => $search]) }}"
             class="text-xs text-slate-500 hover:text-red-600 font-bold px-2 py-1 rounded-lg hover:bg-red-50 transition" title="Tampilkan Semua Bulan">
            <i class="fa-solid fa-xmark"></i>
          </a>
        @endif
      </form>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. TABEL PENGINTAIAN & REKAP TAGIHAN           -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-center text-xs">
        <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
          <tr>
            <th class="px-5 py-3.5 text-center">Invoice & Tanggal</th>
            <th class="px-4 py-3.5 text-center">Pelanggan</th>
            <th class="px-4 py-3.5 text-center">Paket & ODP</th>
            @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
              <th class="px-4 py-3.5 text-center">Periode Tagihan</th>
              <th class="px-4 py-3.5 text-center">Waktu Pelunasan / Rekap</th>
              <th class="px-4 py-3.5 text-center">Total Dibayar</th>
              <th class="px-4 py-3.5 text-center">Metode & Kuitansi</th>
            @else
              <th class="px-4 py-3.5 text-center">Jatuh Tempo</th>
              <th class="px-4 py-3.5 text-center">Total Tagihan</th>
              <th class="px-4 py-3.5 text-center">Metode Bayar</th>
            @endif
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

              <!-- Pelanggan -->
              <td class="px-4 py-4 text-center">
                <p class="font-bold text-slate-900">{{ $bill['customer_name'] }}</p>
                <div class="flex items-center justify-center gap-1.5 text-[11px] text-slate-500 mt-0.5">
                  <i class="fa-brands fa-whatsapp text-emerald-600"></i>
                  <span>{{ $bill['customer_phone'] }}</span>
                </div>
              </td>

              <!-- Paket & ODP -->
              <td class="px-4 py-4 text-center">
                <span class="font-semibold text-slate-800">{{ $bill['package_name'] }}</span>
                <p class="text-[10px] text-slate-400 mt-0.5">
                  <i class="fa-solid fa-network-wired text-brand text-[9px]"></i> {{ $bill['odp'] }}
                </p>
              </td>

              @if($statusFilter === 'rekap' || $statusFilter === 'Lunas')
                <!-- Periode Tagihan -->
                <td class="px-4 py-4 text-slate-600 text-center font-medium">
                  {{ $bill['period'] }}
                </td>

                <!-- Waktu Pelunasan / Rekap (Kolom Riwayat Rekap) -->
                <td class="px-4 py-4 text-center">
                  <p class="font-bold text-emerald-800">{{ $bill['paid_date'] ?? '-' }}</p>
                  <p class="text-[10px] text-slate-400 mt-0.5">{{ $bill['paid_at_formatted'] ?? '-' }}</p>
                </td>

                <!-- Total Dibayar -->
                <td class="px-4 py-4 font-black text-slate-900 text-sm text-center">
                  Rp{{ $bill['total'] }}
                </td>

                <!-- Metode & Kuitansi -->
                <td class="px-4 py-4 text-center">
                  <p class="text-slate-700 font-medium">{{ $bill['payment_method'] }}</p>
                  <span class="text-[10px] font-mono text-slate-400 font-bold">{{ $bill['receipt_number'] ?? '-' }}</span>
                </td>
              @else
                <!-- Jatuh Tempo -->
                <td class="px-4 py-4 text-slate-600 text-center">
                  {{ $bill['due_date'] }}
                </td>

                <!-- Total Tagihan -->
                <td class="px-4 py-4 font-black text-slate-900 text-sm text-center">
                  Rp{{ $bill['total'] }}
                </td>

                <!-- Metode Bayar & Bukti -->
                <td class="px-4 py-4 text-center">
                  <p class="text-slate-700 font-medium">{{ $bill['payment_method'] }}</p>
                  @if($bill['proof_image'])
                    <button type="button" @click="viewProof('{{ $bill['proof_image'] }}')"
                            class="inline-flex items-center justify-center gap-1 text-[10px] text-blue-600 hover:underline font-bold mt-1">
                      <i class="fa-regular fa-image"></i> Lihat Bukti
                    </button>
                  @endif
                </td>
              @endif

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
                <div class="flex items-center justify-center gap-2">
                  <button type="button" @click="viewBill(@js($bill))"
                          class="bg-brand/10 hover:bg-brand text-brand hover:text-white font-bold text-xs px-3 py-1.5 rounded-lg transition flex items-center gap-1">
                    <i class="fa-regular fa-eye"></i> Detail
                  </button>

                  <a href="https://wa.me/{{ preg_replace('/^0/', '62', $bill['customer_phone']) }}?text={{ urlencode(($bill['status'] === 'Lunas' ? 'Halo Bapak/Ibu ' . $bill['customer_name'] . ', terima kasih tagihan internet ' . $bill['id'] . ' sebesar Rp' . $bill['total'] . ' telah lunas terverifikasi.' : 'Halo Bapak/Ibu ' . $bill['customer_name'] . ', kami dari Banterpool mengingatkan tagihan internet ' . $bill['id'] . ' sebesar Rp' . $bill['total'] . ' jatuh tempo pada ' . $bill['due_date'] . '. Terima kasih.')) }}"
                     target="_blank" title="{{ $bill['status'] === 'Lunas' ? 'Kirim Konfirmasi Lunas WhatsApp' : 'Kirim Pengingat WhatsApp' }}"
                     class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white flex items-center justify-center transition">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                  </a>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ ($statusFilter === 'rekap' || $statusFilter === 'Lunas') ? 9 : 8 }}" class="text-center py-12 text-slate-400">
                <i class="fa-solid fa-receipt text-3xl mb-2 text-slate-300"></i>
                <p>Tidak ada tagihan yang cocok dengan filter atau pencarian.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. MODAL DETAIL & VERIFIKASI TAGIHAN           -->
  <!-- ============================================== -->
  <div x-show="openModal" style="display: none;"
       x-transition:enter="ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0"
       class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/70 backdrop-blur-xs">

    <div @click.away="openModal = false"
         class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 shadow-2xl relative space-y-5"
         x-show="selectedBill">

      <!-- Header Modal -->
      <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div>
          <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">Rincian Tagihan</span>
          <h3 class="text-base font-black text-slate-900" x-text="selectedBill?.id"></h3>
        </div>
        <button type="button" @click="openModal = false" class="text-slate-400 hover:text-black text-xl">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Content Rincian -->
      <div class="space-y-4 text-xs">
        
        <!-- Info Pelanggan -->
        <div class="bg-slate-50 rounded-xl p-4 space-y-2 border border-slate-200/80">
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Nama Pelanggan:</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.customer_name"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Nomor Telepon:</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.customer_phone"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Alamat Pemasangan:</span>
            <span class="font-medium text-slate-800 text-right max-w-xs" x-text="selectedBill?.address"></span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500 font-medium">Titik ODP:</span>
            <span class="font-bold text-brand" x-text="selectedBill?.odp"></span>
          </div>
        </div>

        <!-- Rincian Biaya -->
        <div class="space-y-2 border border-slate-200/80 rounded-xl p-4">
          <div class="flex justify-between text-slate-600">
            <span>Paket Layanan</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.package_name"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Periode Berlangganan</span>
            <span class="text-slate-800 font-medium" x-text="selectedBill?.period"></span>
          </div>
          <div class="flex justify-between text-slate-600">
            <span>Metode Pembayaran</span>
            <span class="font-bold text-slate-900" x-text="selectedBill?.payment_method"></span>
          </div>
          <div class="border-t border-slate-100 pt-2 flex justify-between font-black text-sm text-slate-900">
            <span>Total Tagihan:</span>
            <span class="text-brand text-base" x-text="'Rp' + selectedBill?.total"></span>
          </div>
        </div>

        <!-- Informasi Riwayat Rekap Pelunasan jika Lunas -->
        <template x-if="selectedBill?.status === 'Lunas'">
          <div class="border border-emerald-200 bg-emerald-50/70 rounded-xl p-4 space-y-2.5">
            <div class="flex items-center justify-between pb-2 border-b border-emerald-200/60">
              <span class="inline-flex items-center gap-1.5 text-emerald-800 font-bold text-xs">
                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i> Pembayaran Terverifikasi Lunas
              </span>
              <span class="text-[10px] font-mono font-bold bg-white text-emerald-800 px-2 py-0.5 rounded border border-emerald-300"
                    x-text="selectedBill?.receipt_number"></span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-slate-600">
              <div>
                <span class="text-[10px] text-slate-400 block">Waktu Pelunasan:</span>
                <span class="font-bold text-slate-800" x-text="selectedBill?.paid_at_formatted || selectedBill?.paid_date || '-'"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Bulan Rekap:</span>
                <span class="font-bold text-emerald-700" x-text="selectedBill?.month_label"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Diverifikasi Oleh:</span>
                <span class="font-bold text-slate-800" x-text="selectedBill?.collected_by || 'Admin NOC'"></span>
              </div>
              <div>
                <span class="text-[10px] text-slate-400 block">Catatan:</span>
                <span class="font-medium text-slate-700 truncate block" x-text="selectedBill?.collector_notes || '-'"></span>
              </div>
            </div>
          </div>
        </template>

        <!-- Bukti Pembayaran Box jika ada -->
        <template x-if="selectedBill?.proof_image">
          <div class="border border-amber-200 bg-amber-50/50 rounded-xl p-3 space-y-2">
            <div class="flex items-center justify-between">
              <span class="font-bold text-amber-900 text-xs">Bukti Transfer Pelanggan</span>
              <button type="button" @click="viewProof(selectedBill.proof_image)" class="text-brand hover:underline font-bold text-[11px]">
                Perbesar Gambar &rarr;
              </button>
            </div>
            <img :src="selectedBill.proof_image" alt="Bukti Transfer" class="w-full h-36 object-cover rounded-lg border border-amber-200">
          </div>
        </template>

      </div>

      <!-- Action Buttons -->
      <div class="pt-2 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
        <!-- Tombol Verifikasi jika status belum lunas -->
        <template x-if="selectedBill?.status !== 'Lunas'">
          <form :action="'/admin/tagihan/' + selectedBill?.id + '/status'" method="POST" class="w-full sm:flex-1 flex items-center gap-2">
            @csrf
            <input type="hidden" name="status" value="Lunas">
            <button type="submit"
                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
              <i class="fa-solid fa-check"></i> Verifikasi & Pindahkan ke Rekap
            </button>
          </form>
        </template>

        <!-- Aksi jika sudah lunas -->
        <template x-if="selectedBill?.status === 'Lunas'">
          <div class="w-full sm:flex-1 flex items-center gap-2">
            <a :href="'https://wa.me/' + (selectedBill?.customer_phone ? selectedBill.customer_phone.replace(/^0/, '62') : '') + '?text=' + encodeURIComponent('Halo Bapak/Ibu ' + selectedBill?.customer_name + ', terima kasih tagihan internet ' + selectedBill?.id + ' sebesar Rp' + selectedBill?.total + ' telah terverifikasi LUNAS dengan No Kuitansi ' + selectedBill?.receipt_number + '. Terima kasih.')"
               target="_blank"
               class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs py-2.5 px-4 rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
              <i class="fa-brands fa-whatsapp text-sm"></i> Kirim Bukti Lunas WA
            </a>
          </div>
        </template>

        <button type="button" @click="openModal = false"
                class="w-full sm:w-auto border border-slate-300 text-slate-700 font-bold text-xs py-2.5 px-4 rounded-xl hover:bg-slate-50 transition">
          Tutup
        </button>
      </div>

    </div>

  </div>

  <!-- LIGHTBOX BUKTI BAYAR -->
  <div x-show="openProofLightbox" style="display: none;"
       @click.away="openProofLightbox = false"
       class="fixed inset-0 z-60 overflow-y-auto flex items-center justify-center p-4 bg-black/85 backdrop-blur-sm">
    <div class="relative max-w-2xl w-full">
      <button type="button" @click="openProofLightbox = false" class="absolute -top-10 right-0 text-white text-2xl font-bold">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <img :src="proofUrl" alt="Bukti Transfer" class="w-full max-h-[85vh] object-contain rounded-2xl shadow-2xl">
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 4. MODAL INPUT MANUAL TAGIHAN BARU             -->
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
                <i class="fa-solid fa-file-invoice-dollar"></i> Input Tagihan Manual
              </span>
              <span class="text-xs font-bold text-slate-400">Keuangan & Billing NOC</span>
            </div>
            <h3 class="text-xl font-black text-slate-900">Terbitkan Tagihan Baru</h3>
            <p class="text-xs text-slate-500 mt-0.5">
              Pilih dari data pelanggan terdaftar atau input manual tagihan untuk pelanggan fisik luar sistem.
            </p>
          </div>

          <!-- Mode Toggle: Terdaftar vs Manual -->
          <div class="flex items-center gap-2 p-1.5 bg-slate-100 rounded-2xl">
            <button type="button" @click="billForm.mode = 'registered'"
                    :class="billForm.mode === 'registered' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex-1 py-2 px-3 text-xs rounded-xl transition flex items-center justify-center gap-1.5">
              <i class="fa-solid fa-users text-brand"></i>
              <span>Pilih Pelanggan Terdaftar (Otomatis)</span>
            </button>
            <button type="button" @click="billForm.mode = 'manual'; billForm.orderId = ''"
                    :class="billForm.mode === 'manual' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-500 hover:text-slate-800 font-semibold'"
                    class="flex-1 py-2 px-3 text-xs rounded-lg transition flex items-center justify-center gap-1.5">
              <i class="fa-solid fa-pen-to-square text-brand"></i>
              <span>Input Bebas Manual</span>
            </button>
          </div>

          <!-- Form Store Tagihan -->
          <form action="{{ route('admin.tagihan.store') }}" method="POST" class="space-y-5 text-xs">
            @csrf
            <input type="hidden" name="order_id" x-model="billForm.orderId">

            <!-- Dropdown Pilihan Pelanggan Terdaftar (Jika Mode Registered) -->
            <div x-show="billForm.mode === 'registered'" class="p-3.5 bg-blue-50/60 rounded-2xl border border-blue-200/80 space-y-1.5">
              <label class="block font-bold text-blue-950 text-xs">
                <i class="fa-solid fa-magnifying-glass mr-1"></i> Cari & Pilih Pelanggan Terdaftar
              </label>
              <select @change="onSelectCustomer($event)"
                      x-model="billForm.orderId"
                      class="w-full text-xs p-2.5 border border-blue-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-slate-800 shadow-2xs">
                <option value="">-- Klik untuk memilih pelanggan ({{ count($registeredCustomers ?? []) }} Pelanggan) --</option>
                @foreach($registeredCustomers ?? [] as $c)
                  <option value="{{ $c->id }}"
                          data-name="{{ $c->customer_name }}"
                          data-phone="{{ $c->customer_phone }}"
                          data-email="{{ $c->customer_email }}"
                          data-address="{{ $c->address }}"
                          data-package="{{ $c->package_name }}"
                          data-speed="{{ $c->speed }}"
                          data-price="{{ $c->price }}">
                    {{ $c->customer_name }} ({{ $c->order_number }}) - {{ $c->package_name }} - {{ Str::limit($c->address, 35) }}
                  </option>
                @endforeach
              </select>
              <p class="text-[10px] text-blue-700">Nama, No. WhatsApp, Alamat, Paket, dan Biaya akan terisi otomatis begitu dipilih.</p>
            </div>

            <!-- Section 1: Data Identitas Pelanggan -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-user text-brand"></i>
                <span>1. Data Pelanggan Penerima Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Nama Pelanggan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Nama Pelanggan <span class="text-red-500">*</span></label>
                  <input type="text" name="customer_name" x-model="billForm.customerName" required placeholder="Nama lengkap pelanggan"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                </div>

                <!-- No WhatsApp / HP -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP <span class="text-red-500">*</span></label>
                  <input type="tel" name="customer_phone" x-model="billForm.customerPhone" required placeholder="08xxxxxxxxxx"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono font-semibold">
                </div>

                <!-- Email Pelanggan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Email Pelanggan (Opsional)</label>
                  <input type="email" name="customer_email" x-model="billForm.customerEmail" placeholder="pelanggan@gmail.com"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Alamat Pemasangan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Alamat Pemasangan <span class="text-red-500">*</span></label>
                  <textarea name="address" x-model="billForm.address" required rows="2" placeholder="Nama Jalan, RT/RW, Dusun, Desa, Kec. Cilongok"
                            class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
                </div>
              </div>
            </div>

            <!-- Section 2: Informasi Paket & Periode Invoice -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-wifi text-brand"></i>
                <span>2. Paket Layanan & Periode Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <!-- Paket Layanan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Paket Layanan <span class="text-red-500">*</span></label>
                  <select name="package_name" x-model="billForm.packageName" required @change="onPackageSelect($event)"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-slate-800">
                    <option value="Paket 20 Mbps">Paket 20 Mbps</option>
                    <option value="Paket 30 Mbps">Paket 30 Mbps</option>
                    <option value="Paket 50 Mbps">Paket 50 Mbps</option>
                  </select>
                </div>

                <!-- Kecepatan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Kecepatan</label>
                  <input type="text" name="speed" x-model="billForm.speed" placeholder="20 Mbps"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Tanggal Terbit Tagihan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tanggal Terbit</label>
                  <input type="text" name="bill_date" x-model="billForm.billDate" placeholder="Contoh: 11 Sep 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                </div>

                <!-- Periode Tagihan -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Periode Pemakaian <span class="text-red-500">*</span></label>
                  <input type="text" name="period" x-model="billForm.period" required placeholder="Contoh: 01 Sep 2026 – 01 Okt 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                </div>

                <!-- Batas Jatuh Tempo -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Jatuh Tempo <span class="text-red-500">*</span></label>
                  <input type="text" name="due_date" x-model="billForm.dueDate" required placeholder="Contoh: 05 Okt 2026"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold text-red-600">
                  <span class="text-[10px] text-slate-400">Standar: Tanggal 05 bulan depan</span>
                </div>
              </div>
            </div>

            <!-- Section 3: Rincian Nominal & Total -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-calculator text-brand"></i>
                <span>3. Nominal & Total Tagihan</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Tarif Paket -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Tarif Langganan (Rp) <span class="text-red-500">*</span></label>
                  <input type="number" name="amount" x-model="billForm.amount" required min="0" step="1000"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold font-mono">
                </div>

                <!-- Biaya Lain / Pajak -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Biaya Tambahan / Pajak (Rp)</label>
                  <input type="number" name="tax" x-model="billForm.tax" min="0" step="1000" placeholder="0"
                         class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-mono">
                </div>
              </div>

              <!-- Input Hidden Total & Preview Box -->
              <input type="hidden" name="total" :value="billForm.total()">
              <div class="p-3 bg-red-50/60 rounded-xl border border-red-100 flex items-center justify-between">
                <div>
                  <span class="font-bold text-slate-700">Total Nominal Tagihan:</span>
                  <p class="text-[10px] text-slate-400">Nominal yang wajib dibayar pelanggan sebelum jatuh tempo</p>
                </div>
                <span class="text-brand font-black text-xl">
                  Rp<span x-text="billForm.total().toLocaleString('id-ID')"></span>
                </span>
              </div>
            </div>

            <!-- Section 4: Status Tagihan & Pembayaran -->
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-3">
              <div class="flex items-center gap-1.5 font-bold text-slate-800 text-xs border-b border-slate-200/60 pb-2">
                <i class="fa-solid fa-receipt text-brand"></i>
                <span>4. Status & Metode Pembayaran</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Status Tagihan -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Status Tagihan <span class="text-red-500">*</span></label>
                  <select name="status" x-model="billForm.status" required
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-bold">
                    <option value="Belum Bayar">Belum Bayar</option>
                    <option value="Lunas">Lunas (Terbit Kuitansi Langsung)</option>
                    <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                    <option value="Jatuh Tempo">Jatuh Tempo</option>
                  </select>
                </div>

                <!-- Metode Pembayaran -->
                <div>
                  <label class="block font-bold text-slate-700 mb-1">Metode Pembayaran</label>
                  <select name="payment_method" x-model="billForm.paymentMethod"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                    <option value="Transfer Bank (BCA)">Transfer Bank (BCA)</option>
                    <option value="Tunai (Kolektor/Admin)">Tunai (Kolektor/Admin)</option>
                    <option value="QRIS">QRIS</option>
                    <option value="Verifikasi Admin">Verifikasi Admin</option>
                  </select>
                </div>

                <!-- Catatan Kolektor / Admin -->
                <div class="sm:col-span-2">
                  <label class="block font-bold text-slate-700 mb-1">Catatan Tagihan / Keterangan</label>
                  <textarea name="collector_notes" x-model="billForm.collectorNotes" rows="2" placeholder="Catatan bukti setor, nomor referensi, atau perjanjian bayar..."
                            class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white resize-none"></textarea>
                </div>
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
                <i class="fa-solid fa-file-invoice"></i>
                <span>Terbitkan Tagihan</span>
              </button>
            </div>

          </form>

        </div>

      </div>
    </div>
  </div>

</div>
@endsection
