@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Master Data Pelanggan')
@section('page-title', 'Master Data Pelanggan')

@section('content')
<div class="space-y-6"
     x-data="{
        openDetailModal: false,
        openCreateModal: false,
        openEditModal: false,
        openDeleteModal: false,
        openImportModal: false,
        openBulkDeleteModal: false,
        bulkDeleteAll: false,
        selectedIds: [],
        pageCustomerIds: {{ Js::from($customers->pluck('id')->values()->all()) }},
        totalCustomersCount: {{ (int) $stats['total'] }},
        importLoading: false,
        importFileName: '',
        selectedCustomer: null,
        copiedText: '',

        toggleSelectAll(ids) {
            if (this.isPageAllSelected(ids)) {
                this.selectedIds = this.selectedIds.filter(id => !ids.includes(id));
            } else {
                this.selectedIds = [...new Set([...this.selectedIds, ...ids])];
            }
        },

        toggleSingle(id) {
            const index = this.selectedIds.indexOf(id);
            if (index > -1) {
                this.selectedIds.splice(index, 1);
            } else {
                this.selectedIds.push(id);
            }
        },

        isPageAllSelected(ids) {
            return ids.length > 0 && ids.every(id => this.selectedIds.includes(id));
        },

        confirmBulkDelete(all = false) {
            this.bulkDeleteAll = all;
            this.openBulkDeleteModal = true;
        },

        clearSelection() {
            this.selectedIds = [];
            this.bulkDeleteAll = false;
        },
        
        onFileSelect(e) {
            if (e.target.files && e.target.files.length > 0) {
                this.importFileName = e.target.files[0].name;
            } else {
                this.importFileName = '';
            }
        },
        
        viewCustomer(c) {
            this.selectedCustomer = c;
            this.openDetailModal = true;
        },

        editCustomer(c) {
            this.selectedCustomer = Object.assign({}, c);
            this.openEditModal = true;
        },

        confirmDelete(c) {
            this.selectedCustomer = c;
            this.openDeleteModal = true;
        },

        copyToClipboard(text, key) {
            if (!text) return;
            navigator.clipboard.writeText(text);
            this.copiedText = key;
            setTimeout(() => {
                this.copiedText = '';
            }, 2000);
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            try {
                const d = new Date(dateStr);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
            } catch (e) {
                return dateStr;
            }
        },

        calculateAge(dateStr) {
            if (!dateStr) return '-';
            try {
                const birth = new Date(dateStr);
                const diff = Date.now() - birth.getTime();
                const ageDt = new Date(diff);
                return Math.abs(ageDt.getUTCFullYear() - 1970) + ' th';
            } catch (e) {
                return '-';
            }
        }
     }">

  <!-- ============================================== -->
  <!-- 1. ALERT NOTIFIKASI                            -->
  <!-- ============================================== -->
  @if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('success') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl flex items-center justify-between shadow-xs">
      <div class="flex items-center gap-2.5">
        <i class="fa-solid fa-circle-xmark text-red-600 text-base"></i>
        <span class="text-xs font-bold">{{ session('error') }}</span>
      </div>
      <button type="button" @click="$el.parentElement.remove()" class="text-red-500 hover:text-red-700 text-sm">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  @endif

  @if(session('import_warnings') && count(session('import_warnings')) > 0)
    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-2xl shadow-xs" x-data="{ showDetails: false }">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
          <span class="text-xs font-bold">Catatan Import: Ada {{ count(session('import_warnings')) }} baris yang dilewati saat proses import.</span>
        </div>
        <button type="button" @click="showDetails = !showDetails" class="text-xs font-bold text-amber-700 hover:underline">
          <span x-text="showDetails ? 'Sembunyikan Rincian' : 'Lihat Rincian'"></span>
        </button>
      </div>
      <div x-show="showDetails" class="mt-2 pt-2 border-t border-amber-200 text-[11px] text-amber-800 max-h-40 overflow-y-auto space-y-1">
        @foreach(session('import_warnings') as $warn)
          <p class="flex items-center gap-1.5"><i class="fa-solid fa-circle-dot text-[8px] text-amber-500"></i> {{ $warn }}</p>
        @endforeach
      </div>
    </div>
  @endif

  @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl shadow-xs">
      <div class="flex items-center gap-2 mb-1">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span class="text-xs font-bold">Terjadi kesalahan input:</span>
      </div>
      <ul class="list-disc list-inside text-xs text-red-700 pl-4 space-y-0.5">
        @foreach($errors->all() as $err)
          <li>{{ $err }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <!-- ============================================== -->
  <!-- 2. HEADER & ACTION BUTTONS                     -->
  <!-- ============================================== -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2">
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Master Data Pelanggan</h2>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Data pendaftaran pelanggan terintegrasi (No, Nama Pemohon, No KTP, No Handphone, Jenis Layanan, Harga, dan Alamat).
      </p>
    </div>

    <div class="grid grid-cols-2 gap-2 shrink-0">
      <!-- 1. Tambah Pelanggan -->
      <button type="button" @click="openCreateModal = true"
              class="w-full sm:w-48 h-10 bg-brand hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-user-plus"></i>
        <span>Tambah Pelanggan</span>
      </button>

      <!-- 2. Import Excel -->
      <button type="button" @click="openImportModal = true"
              class="w-full sm:w-48 h-10 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-file-import"></i>
        <span>Import Excel</span>
      </button>

      <!-- 3. Export Excel -->
      <a href="{{ route('admin.pelanggan.export', request()->query()) }}"
         class="w-full sm:w-48 h-10 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-file-excel"></i>
        <span>Export Excel (.xls)</span>
      </a>

      <!-- 4. Hapus Semua / Hapus Terpilih -->
      <div>
        <button type="button"
                x-show="selectedIds.length > 0"
                style="display: none;"
                @click="confirmBulkDelete(false)"
                class="w-full sm:w-48 h-10 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm">
          <i class="fa-solid fa-trash-can"></i>
          <span>Hapus (<span x-text="selectedIds.length"></span>)</span>
        </button>

        <button type="button"
                x-show="selectedIds.length === 0"
                @click="confirmBulkDelete(true)"
                @if($stats['total'] == 0) disabled @endif
                class="w-full sm:w-48 h-10 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                title="Hapus Semua Data Pelanggan Sekaligus">
          <i class="fa-solid fa-trash-arrow-up text-white"></i>
          <span>Hapus Semua</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. STATISTIC KPI CARDS                         -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
    
    <!-- Total Pelanggan -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Pelanggan</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Arsip Pendaftaran SIMS</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-users"></i>
      </div>
    </div>

    <!-- Pelanggan Aktif -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pelanggan Aktif</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['active'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Koneksi Aktif / Online</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-circle-check"></i>
      </div>
    </div>

    <!-- KTP Terverifikasi -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">KTP / NIK Valid</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['verified_ktp'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Tercatat di Formulir</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg">
        <i class="fa-regular fa-id-card"></i>
      </div>
    </div>

    <!-- Estimasi MRR Bulanan -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Estimasi MRR</p>
        <h3 class="text-xl sm:text-2xl font-black text-slate-900 mt-1">Rp{{ number_format($stats['total_mrr'] / 1000000, 1, ',', '.') }} Jt</h3>
        <span class="text-[10px] text-slate-500 font-medium">Omset Rutin Bulanan</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg">
        <i class="fa-solid fa-money-bill-trend-up"></i>
      </div>
    </div>

    <!-- Wilayah Cakupan -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between col-span-2 sm:col-span-2 lg:col-span-1">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Wilayah Cakupan</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $stats['wilayah_count'] }} Desa</h3>
        <span class="text-[10px] text-slate-500 font-medium">Banyumas & Sekitarnya</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-red-50 text-brand flex items-center justify-center text-lg">
        <i class="fa-solid fa-map-location-dot"></i>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 4. SEARCH & FILTER BAR                         -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs">
    <form method="GET" action="{{ route('admin.pelanggan') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
      
      <!-- Search Input with Live Autocomplete Suggestions (Sesuai Contoh Pencarian Awalan Abjad) -->
      <div class="sm:col-span-2 lg:col-span-4"
           x-data="{
             searchVal: '{{ addslashes($search) }}',
             suggestions: [],
             showDropdown: false,
             loading: false,
             selectedIndex: -1,

             async onInput() {
               const val = this.searchVal.trim();
               if (!val) {
                 this.suggestions = [];
                 this.showDropdown = false;
                 this.selectedIndex = -1;
                 return;
               }
               this.loading = true;
               try {
                 const res = await fetch(`{{ route('admin.pelanggan') }}?ajax=1&q=${encodeURIComponent(val)}`, {
                   headers: { 'X-Requested-With': 'XMLHttpRequest' }
                 });
                 if (res.ok) {
                   this.suggestions = await res.json();
                   this.showDropdown = this.suggestions.length > 0;
                   this.selectedIndex = -1;
                 }
               } catch (err) {
                 this.suggestions = [];
               } finally {
                 this.loading = false;
               }
             },

             selectItem(item) {
               this.searchVal = item.customer_name;
               this.showDropdown = false;
               $nextTick(() => {
                 $el.closest('form').submit();
               });
             },

             onKeyDown(e) {
               if (!this.showDropdown || this.suggestions.length === 0) return;
               if (e.key === 'ArrowDown') {
                 e.preventDefault();
                 this.selectedIndex = (this.selectedIndex + 1) % this.suggestions.length;
               } else if (e.key === 'ArrowUp') {
                 e.preventDefault();
                 this.selectedIndex = (this.selectedIndex - 1 + this.suggestions.length) % this.suggestions.length;
               } else if (e.key === 'Enter' && this.selectedIndex >= 0) {
                 e.preventDefault();
                 this.selectItem(this.suggestions[this.selectedIndex]);
               } else if (e.key === 'Escape') {
                 this.showDropdown = false;
               }
             },

             highlightPrefix(name, prefix) {
               if (!prefix) return name;
               const lowerName = name.toLowerCase();
               const lowerPrefix = prefix.toLowerCase();
               if (lowerName.startsWith(lowerPrefix)) {
                 const matched = name.substring(0, prefix.length);
                 const rest = name.substring(prefix.length);
                 return `<strong class='font-black text-slate-900'>${matched}</strong>${rest}`;
               }
               return name;
             }
           }"
           @click.outside="showDropdown = false">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Pencarian Data Pelanggan</label>
        <div class="relative">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
          <input type="text" name="q"
                 x-model="searchVal"
                 @input.debounce.150ms="onInput()"
                 @keydown="onKeyDown($event)"
                 @focus="if (suggestions.length > 0) showDropdown = true"
                 autocomplete="off"
                 placeholder="Cari NIK, PPOE, Nama, No. HP, Email, Alamat..."
                 class="w-full pl-9 pr-8 py-2 text-xs bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand transition">

          <!-- Spinner Loading -->
          <div x-show="loading" style="display: none;" class="absolute right-3 top-2.5 text-slate-400 text-xs">
            <i class="fa-solid fa-spinner fa-spin"></i>
          </div>

          <!-- Dropdown Autocomplete Awalan (Seperti Omnibox Google) -->
          <div x-show="showDropdown && suggestions.length > 0"
               style="display: none;"
               x-transition:enter="transition ease-out duration-100"
               x-transition:enter-start="transform opacity-0 scale-98"
               x-transition:enter-end="transform opacity-100 scale-100"
               x-transition:leave="transition ease-in duration-75"
               x-transition:leave-start="transform opacity-100 scale-100"
               x-transition:leave-end="transform opacity-0 scale-98"
               class="absolute left-0 right-0 top-full mt-1.5 bg-white border border-slate-200/90 rounded-2xl shadow-xl z-50 overflow-hidden divide-y divide-slate-100 max-h-72 overflow-y-auto">
            
            <div class="px-3.5 py-1.5 bg-slate-50 text-[10px] font-bold text-slate-400 flex items-center justify-between">
              <span>Hasil Pencarian Awalan "<span class="text-slate-700 font-extrabold" x-text="searchVal"></span>"</span>
              <span class="text-[9px] text-slate-400">Pilih nama atau tekan Enter</span>
            </div>

            <template x-for="(item, idx) in suggestions" :key="item.id">
              <div @click="selectItem(item)"
                   :class="selectedIndex === idx ? 'bg-red-50 text-brand' : 'hover:bg-slate-50 text-slate-700'"
                   class="px-3.5 py-2.5 cursor-pointer flex items-center justify-between text-xs transition">
                <div class="flex items-center gap-2.5 min-w-0">
                  <div class="w-6 h-6 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0 text-[10px]">
                    <i class="fa-solid fa-user"></i>
                  </div>
                  <div class="truncate">
                    <span class="text-xs" x-html="highlightPrefix(item.customer_name, searchVal)"></span>
                    <span class="text-[10px] text-slate-400 ml-1.5" x-text="item.village ? '• ' + item.village : ''"></span>
                  </div>
                </div>
                <div class="text-right shrink-0 ml-2">
                  <span class="text-[10px] text-brand font-mono font-bold block" x-text="item.pppoe ? 'PPOE: ' + item.pppoe : ''"></span>
                  <span class="text-[10px] text-slate-400 font-mono block" x-text="item.customer_phone || item.id_card_number || ''"></span>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>

      <!-- Filter Wilayah / Desa -->
      <div class="sm:col-span-1 lg:col-span-3">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Filter Wilayah / Desa</label>
        <select name="wilayah" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
          <option value="all">Semua Wilayah ({{ $stats['total'] }} Pelanggan)</option>
          @foreach($wilayahList as $key => $name)
            @php $cW = $wilayahCounts[$key] ?? 0; @endphp
            <option value="{{ $key }}" {{ $wilayahFilter === $key ? 'selected' : '' }}>
              {{ $name }} ({{ $cW }} Pelanggan)
            </option>
          @endforeach
        </select>
      </div>

      <!-- Filter Layanan -->
      <div class="sm:col-span-1 lg:col-span-2">
        <label class="block text-[11px] font-bold text-slate-700 mb-1">Jenis Layanan</label>
        <select name="layanan" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
          <option value="all">Semua Layanan</option>
          @foreach($layananList as $key => $label)
            <option value="{{ $key }}" {{ $layananFilter === $key ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <!-- Filter Status & Reset -->
      <div class="sm:col-span-2 lg:col-span-3 flex items-end gap-2">
        <div class="flex-1 min-w-0">
          <label class="block text-[11px] font-bold text-slate-700 mb-1">Status</label>
          <select name="status" onchange="this.form.submit()" class="w-full text-xs py-2 px-3 bg-slate-50 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand cursor-pointer">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status Pelanggan</option>
            <option value="Selesai" {{ $statusFilter === 'Selesai' ? 'selected' : '' }}>Selesai / Aktif (Online)</option>
            <option value="Non-Aktif" {{ $statusFilter === 'Non-Aktif' ? 'selected' : '' }}>Non-Aktif / Isolir</option>
            <option value="Dibatalkan" {{ $statusFilter === 'Dibatalkan' ? 'selected' : '' }}>Dibatalkan</option>
          </select>
        </div>
        @if($search || $wilayahFilter !== 'all' || $layananFilter !== 'all' || $statusFilter !== 'all')
          <a href="{{ route('admin.pelanggan') }}" class="py-2 px-3 bg-slate-100 hover:bg-red-50 text-slate-500 hover:text-red-600 rounded-xl text-xs font-bold transition shrink-0 flex items-center gap-1.5 border border-slate-200" title="Reset Semua Filter">
            <i class="fa-solid fa-arrow-rotate-left text-[11px]"></i>
            <span>Reset</span>
          </a>
        @endif
      </div>

    </form>
  </div>

  <!-- Selection Status Bar (Aktif ketika ada data yang dipilih) -->
  <div x-show="selectedIds.length > 0"
       style="display: none;"
       class="bg-slate-900 text-white p-3.5 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg border border-slate-700">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-xl bg-brand/30 border border-brand/50 text-brand flex items-center justify-center font-black text-xs shrink-0">
        <span x-text="selectedIds.length"></span>
      </div>
      <div class="text-xs">
        <p class="font-extrabold text-white">
          <span x-text="selectedIds.length"></span> data pelanggan dipilih
        </p>
        <template x-if="totalCustomersCount > pageCustomerIds.length">
          <p class="text-[11px] text-slate-400 mt-0.5">
            Ingin menghapus seluruh database?
            <button type="button" @click="confirmBulkDelete(true)" class="text-amber-400 hover:underline font-bold ml-1">
              Pilih & Hapus Seluruh <span x-text="totalCustomersCount"></span> Pelanggan
            </button>
          </p>
        </template>
      </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
      <button type="button" @click="clearSelection()"
              class="bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold px-3 py-1.5 rounded-xl transition">
        Batal Pilihan
      </button>
      <button type="button" @click="confirmBulkDelete(false)"
              class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-1.5 rounded-xl transition flex items-center gap-1.5 shadow-sm">
        <i class="fa-solid fa-trash-can"></i>
        <span>Hapus Terpilih (<span x-text="selectedIds.length"></span>)</span>
      </button>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 5. TABEL MASTER DATA PELANGGAN                 -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-center text-xs">
        <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase text-[10px] border-b border-slate-200 tracking-wider">
          <tr>
            <!-- Checkbox Select All Column -->
            <th class="py-3.5 px-3 text-center w-12">
              <div class="flex items-center justify-center">
                <input type="checkbox"
                       :checked="isPageAllSelected(pageCustomerIds)"
                       @change="toggleSelectAll(pageCustomerIds)"
                       class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand cursor-pointer"
                       title="Pilih Semua di Halaman Ini">
              </div>
            </th>
            <th class="py-3.5 px-4 text-center">Nama Pemohon</th>
            <th class="py-3.5 px-4 text-center">No KTP</th>
            <th class="py-3.5 px-4 text-center">No Handphone</th>
            <th class="py-3.5 px-4 text-center">Jenis Layanan</th>
            <th class="py-3.5 px-4 text-center">Harga</th>
            <th class="py-3.5 px-4 text-center">Alamat</th>
            <th class="py-3.5 px-4 text-center">Status</th>
            <th class="py-3.5 px-4 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 font-medium">
          @forelse($customers as $index => $customer)
            @php
              // Hitung usia
              $age = null;
              if ($customer->birth_date) {
                  try {
                      $age = \Carbon\Carbon::parse($customer->birth_date)->age;
                  } catch (\Exception $e) {}
              }

              // Bersihkan no hp untuk link WhatsApp
              $cleanPhone = preg_replace('/\D/', '', $customer->customer_phone);
              if (str_starts_with($cleanPhone, '0')) {
                  $cleanPhone = '62' . substr($cleanPhone, 1);
              }
            @endphp
            <tr class="hover:bg-slate-50/80 transition duration-150"
                :class="selectedIds.includes({{ $customer->id }}) ? 'bg-red-50/40' : ''">
              
              <!-- Checkbox Select Row -->
              <td class="py-3.5 px-3 text-center w-12">
                <div class="flex items-center justify-center">
                  <input type="checkbox"
                         :value="{{ $customer->id }}"
                         :checked="selectedIds.includes({{ $customer->id }})"
                         @change="toggleSingle({{ $customer->id }})"
                         class="w-4 h-4 rounded border-slate-300 text-brand focus:ring-brand cursor-pointer">
                </div>
              </td>

              <!-- Nama Pemohon -->
              <td class="py-3.5 px-4 text-center">
                <div class="font-bold text-slate-900 text-xs leading-snug">{{ $customer->customer_name }}</div>
                <div class="mt-1 flex items-center justify-center gap-1">
                  <span class="inline-flex items-center gap-1 font-mono text-[11px] font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200 shadow-2xs">
                    <span class="text-[9px] font-extrabold text-brand uppercase tracking-wider">PPOE:</span>
                    <span>{{ $customer->pppoe }}</span>
                  </span>
                  <button type="button"
                          @click="copyToClipboard('{{ $customer->pppoe }}', 'ppoe-{{ $customer->id }}')"
                          class="text-slate-400 hover:text-brand text-xs p-0.5 transition"
                          title="Salin PPOE">
                    <i class="fa-regular fa-copy text-[11px]" x-show="copiedText !== 'ppoe-{{ $customer->id }}'"></i>
                    <i class="fa-solid fa-check text-[11px] text-emerald-600" x-show="copiedText === 'ppoe-{{ $customer->id }}'"></i>
                  </button>
                </div>
              </td>

              <!-- 3. No KTP -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($customer->id_card_number)
                  <div class="inline-flex items-center justify-center gap-1">
                    <span class="font-mono text-xs font-bold bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200">
                      {{ $customer->id_card_number }}
                    </span>
                    <button type="button"
                            @click="copyToClipboard('{{ $customer->id_card_number }}', 'ktp-{{ $customer->id }}')"
                            class="text-slate-400 hover:text-brand text-xs p-1"
                            title="Salin NIK">
                      <i class="fa-regular fa-copy" x-show="copiedText !== 'ktp-{{ $customer->id }}'"></i>
                      <i class="fa-solid fa-check text-emerald-600" x-show="copiedText === 'ktp-{{ $customer->id }}'"></i>
                    </button>
                  </div>
                @else
                  <span class="text-[11px] text-slate-400 italic">-</span>
                @endif
              </td>

              <!-- 4. No Handphone -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($customer->customer_phone && $customer->customer_phone !== '-')
                  <a href="https://wa.me/{{ $cleanPhone }}?text=Halo%20{{ urlencode($customer->customer_name) }},%20kami%20dari%20Banterpool%20Fiber%20Broadband."
                     target="_blank"
                     class="inline-flex items-center justify-center gap-1 text-emerald-600 hover:text-emerald-700 font-bold text-xs bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200/80">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>{{ $customer->customer_phone }}</span>
                  </a>
                @else
                  <span class="text-[11px] text-slate-400 italic">-</span>
                @endif
              </td>

              <!-- 6. Jenis Layanan -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($customer->package_name && $customer->package_name !== '-')
                  <div class="font-bold text-slate-900 text-xs">
                    {{ $customer->package_name }}
                  </div>
                @else
                  <span class="text-[10px] text-slate-400 italic">-</span>
                @endif
              </td>

              <!-- 7. Harga -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if((float) ($customer->price ?? 0) > 0)
                  <div class="text-brand font-black text-xs">
                    Rp{{ number_format((float) $customer->price, 0, ',', '.') }}
                  </div>
                @else
                  <span class="text-xs text-slate-400 font-medium">Rp0</span>
                @endif
              </td>

              <!-- 8. Alamat -->
              <td class="py-3.5 px-4 text-center">
                <p class="text-xs text-slate-700 leading-relaxed max-w-[280px] mx-auto">
                  {{ $customer->address }}
                </p>
              </td>

              <!-- 8. Status -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                @if($customer->status === 'Selesai' || strtolower($customer->status) === 'aktif')
                  <span class="bg-emerald-100 text-emerald-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-emerald-300 inline-flex items-center gap-1">
                    <i class="fa-solid fa-circle text-[6px]"></i> Aktif
                  </span>
                @elseif($customer->status === 'Sedang Dipasang')
                  <span class="bg-indigo-100 text-indigo-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-indigo-300 inline-flex items-center gap-1">
                    <i class="fa-solid fa-screwdriver-wrench text-[9px]"></i> Dipasang
                  </span>
                @elseif($customer->status === 'Jadwal Teknisi')
                  <span class="bg-blue-100 text-blue-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-blue-300 inline-flex items-center gap-1">
                    <i class="fa-solid fa-calendar text-[9px]"></i> Jadwal
                  </span>
                @else
                  <span class="bg-amber-100 text-amber-800 text-[10px] font-extrabold px-2.5 py-1 rounded-full border border-amber-300 inline-flex items-center gap-1">
                    <i class="fa-regular fa-clock text-[9px]"></i> {{ $customer->status }}
                  </span>
                @endif
              </td>

              <!-- 9. Aksi -->
              <td class="py-3.5 px-4 whitespace-nowrap text-center">
                <div class="flex items-center justify-center gap-1.5">
                  
                  <!-- Detail Modal -->
                  <button type="button" @click="viewCustomer({{ Js::from($customer) }})"
                          class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 flex items-center justify-center transition"
                          title="Detail Lengkap & KTP">
                    <i class="fa-solid fa-eye text-xs"></i>
                  </button>

                  <!-- Cetak Formulir SIMS -->
                  <a href="{{ route('admin.pelanggan.formulir', $customer->id) }}" target="_blank"
                     class="w-7 h-7 rounded-lg bg-slate-900 hover:bg-slate-800 text-white flex items-center justify-center transition"
                     title="Cetak Formulir Berlangganan SIMS">
                    <i class="fa-solid fa-print text-xs"></i>
                  </a>

                  <!-- Edit Modal -->
                  <button type="button" @click="editCustomer({{ Js::from($customer) }})"
                          class="w-7 h-7 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-600 flex items-center justify-center transition"
                          title="Ubah Data Pelanggan">
                    <i class="fa-solid fa-pen text-xs"></i>
                  </button>

                  <!-- Hapus -->
                  <button type="button" @click="confirmDelete({{ Js::from($customer) }})"
                          class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-600 flex items-center justify-center transition"
                          title="Hapus Pelanggan">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                  </button>

                </div>
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="9" class="text-center py-16 text-slate-400">
                <i class="fa-solid fa-users-slash text-4xl mb-3 text-slate-300"></i>
                <p class="font-bold text-sm text-slate-600">Tidak ada data pelanggan yang sesuai</p>
                <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter wilayah Anda.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    @if($customers->hasPages())
      <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
        {{ $customers->links() }}
      </div>
    @endif
  </div>

  <!-- ============================================== -->
  <!-- 6. MODAL DETAIL PELANGGAN (KTP & BIODATA)      -->
  <!-- ============================================== -->
  <div x-show="openDetailModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openDetailModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openDetailModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openDetailModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openDetailModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <template x-if="selectedCustomer">
          <div class="space-y-6">
            
            <!-- Modal Header -->
            <div class="border-b border-slate-100 pb-4">
              <div class="flex flex-wrap items-center gap-2 mb-1">
                <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-lg" x-text="selectedCustomer.order_number"></span>
                <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2.5 py-0.5 rounded-lg border border-slate-200 inline-flex items-center gap-1">
                  <span class="text-[9px] font-extrabold text-brand uppercase">PPOE:</span>
                  <span x-text="selectedCustomer.pppoe"></span>
                  <button type="button"
                          @click="copyToClipboard(selectedCustomer.pppoe, 'modal-ppoe')"
                          class="text-slate-400 hover:text-brand ml-0.5 p-0.5"
                          title="Salin PPOE">
                    <i class="fa-regular fa-copy text-[10px]" x-show="copiedText !== 'modal-ppoe'"></i>
                    <i class="fa-solid fa-check text-[10px] text-emerald-600" x-show="copiedText === 'modal-ppoe'"></i>
                  </button>
                </span>
                <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-0.5 rounded-lg border border-emerald-200" x-text="(selectedCustomer.status === 'Selesai' || selectedCustomer.status === 'Aktif') ? 'Aktif' : selectedCustomer.status"></span>
              </div>
              <h3 class="text-xl font-black text-slate-900" x-text="selectedCustomer.customer_name"></h3>
              <p class="text-xs text-slate-500">Biodata Lengkap Pelanggan SIMS Fiber Broadband</p>
            </div>

            <!-- Virtual KTP Identity Card -->
            <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white rounded-2xl p-5 shadow-lg border border-slate-700 relative overflow-hidden">
              <div class="absolute -right-8 -bottom-8 text-white/5 text-9xl font-black pointer-events-none">
                <i class="fa-solid fa-id-card"></i>
              </div>
              
              <div class="flex items-center justify-between border-b border-white/10 pb-3 mb-4">
                <div class="flex items-center gap-2.5">
                  <i class="fa-regular fa-id-card text-red-400 text-lg"></i>
                  <div>
                    <p class="text-[9px] font-bold text-slate-300 uppercase tracking-widest leading-tight">REPUBLIK INDONESIA - KTP ELEKTRONIK</p>
                    <p class="text-xs font-bold text-white leading-tight">IDENTITAS PELANGGAN FIBER BROADBAND</p>
                  </div>
                </div>
                <span class="text-[10px] bg-red-600/30 text-red-300 border border-red-500/30 px-2 py-0.5 rounded font-bold">TERVERIFIKASI</span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 text-xs">
                <div class="sm:col-span-12">
                  <p class="text-[10px] text-slate-400 uppercase font-semibold">Nomor Induk Kependudukan (NIK / No. KTP)</p>
                  <div class="flex items-center gap-3 mt-0.5">
                    <p class="text-xl sm:text-2xl font-mono font-black text-amber-300 tracking-wider" x-text="selectedCustomer.id_card_number || '-'"></p>
                    <button type="button"
                            @click="copyToClipboard(selectedCustomer.id_card_number, 'modal-ktp')"
                            class="bg-white/10 hover:bg-white/20 text-white px-2.5 py-1 rounded text-[10px] font-bold inline-flex items-center gap-1 transition">
                      <i class="fa-regular fa-copy"></i>
                      <span x-text="copiedText === 'modal-ktp' ? 'Disalin!' : 'Salin'"></span>
                    </button>
                  </div>
                </div>

                <div class="sm:col-span-6 space-y-1">
                  <p class="text-[10px] text-slate-400 uppercase">Nama Lengkap</p>
                  <p class="font-bold text-white text-sm" x-text="selectedCustomer.customer_name"></p>
                </div>

                <div class="sm:col-span-6 space-y-1">
                  <p class="text-[10px] text-slate-400 uppercase">Tempat, Tanggal Lahir (Usia)</p>
                  <p class="font-bold text-white" x-text="(selectedCustomer.birth_place ? selectedCustomer.birth_place + ', ' : '') + formatDate(selectedCustomer.birth_date) + ' (' + calculateAge(selectedCustomer.birth_date) + ')'"></p>
                </div>

                <div class="sm:col-span-12 space-y-1 pt-1 border-t border-white/10">
                  <p class="text-[10px] text-slate-400 uppercase">Alamat Sesuai Identitas & Pemasangan</p>
                  <p class="text-slate-200 leading-relaxed" x-text="selectedCustomer.address"></p>
                </div>
              </div>
            </div>

            <!-- Detail Layanan & Kontak Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
              
              <!-- Layanan & Biaya -->
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-2">
                <p class="font-bold text-slate-400 uppercase text-[10px]">Paket & Tarif Layanan</p>
                <div class="flex items-center justify-between">
                  <span class="font-black text-slate-900 text-sm" x-text="selectedCustomer.package_name"></span>
                  <span class="bg-brand text-white font-bold text-[10px] px-2 py-0.5 rounded-full" x-text="selectedCustomer.speed || '20 Mbps'"></span>
                </div>
                <div class="pt-1">
                  <p class="text-slate-500 text-[11px]">Tarif Berlangganan / Bulan:</p>
                  <p class="text-brand font-black text-lg">
                    Rp<span x-text="Number(selectedCustomer.price || 110000).toLocaleString('id-ID')"></span>
                  </p>
                </div>
                <div class="text-[11px] text-slate-500 pt-1 border-t border-slate-200 space-y-0.5">
                  <p>Akun PPOE Internet: <strong class="text-slate-900 font-mono font-bold" x-text="selectedCustomer.pppoe"></strong></p>
                  <p>Status Pembayaran: <strong class="text-emerald-700 font-bold" x-text="selectedCustomer.payment_status || 'Lunas'"></strong></p>
                  <p>Metode Pembayaran: <span x-text="selectedCustomer.payment_method || 'Tunai / Transfer'"></span></p>
                </div>
              </div>

              <!-- Kontak & Komunikasi -->
              <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 space-y-2">
                <p class="font-bold text-slate-400 uppercase text-[10px]">Kontak & Komunikasi</p>
                <div>
                  <p class="text-[11px] text-slate-500">Nomor WhatsApp / HP:</p>
                  <p class="font-mono font-bold text-slate-900 text-sm" x-text="selectedCustomer.customer_phone"></p>
                </div>
                <div>
                  <p class="text-[11px] text-slate-500">Alamat Email:</p>
                  <p class="font-semibold text-blue-600 truncate text-xs" x-text="selectedCustomer.customer_email || '-'"></p>
                </div>
                <div class="pt-2">
                  <a :href="'https://wa.me/' + (selectedCustomer.customer_phone ? selectedCustomer.customer_phone.replace(/^0/, '62').replace(/\D/g, '') : '') + '?text=Halo%20' + encodeURIComponent(selectedCustomer.customer_name) + ',%20kami%20dari%20Banterpool%20Fiber%20Broadband.'"
                     target="_blank"
                     class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2 px-3 rounded-xl transition inline-flex items-center justify-center gap-1.5 shadow-xs">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>Chat WhatsApp Pelanggan</span>
                  </a>
                </div>
              </div>

            </div>

            <!-- Footer Action Buttons -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
              <a :href="'{{ url('admin/pelanggan') }}/' + selectedCustomer.id + '/formulir'"
                 target="_blank"
                 class="bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition inline-flex items-center gap-2 shadow-xs">
                <i class="fa-solid fa-print"></i>
                <span>Cetak Formulir Berlangganan SIMS</span>
              </a>

              <div class="flex items-center gap-2">
                <button type="button" @click="openDetailModal = false; editCustomer(selectedCustomer)"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition inline-flex items-center gap-1.5">
                  <i class="fa-solid fa-pen"></i>
                  <span>Ubah Data</span>
                </button>
                <button type="button" @click="openDetailModal = false"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition">
                  Tutup
                </button>
              </div>
            </div>

          </div>
        </template>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 7. MODAL TAMBAH PELANGGAN BARU                 -->
  <!-- ============================================== -->
  <div x-show="openCreateModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openCreateModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openCreateModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openCreateModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openCreateModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <form method="POST" action="{{ route('admin.pelanggan.store') }}" class="space-y-5 text-xs">
          @csrf

          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-lg font-black text-slate-900">Tambah Pelanggan Baru</h3>
            <p class="text-xs text-slate-500">Input formulir pendaftaran pelanggan baru Banterpool SIMS Fiber</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            
            <!-- Nama Lengkap -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Pelanggan <span class="text-red-500">*</span></label>
              <input type="text" name="customer_name" required placeholder="Contoh: Achmad Sefuloh"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
            </div>

            <!-- Akun PPOE -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">
                <i class="fa-solid fa-network-wired text-brand mr-1"></i> Akun PPOE (PPPoE Secret / Username)
                <span class="text-slate-400 font-normal text-[11px]">(Opsional - otomatis dibuat dari nama jika kosong)</span>
              </label>
              <input type="text" name="pppoe" placeholder="Contoh: achmadsefuloh"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono font-semibold">
            </div>

            <!-- No KTP / NIK -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">No. KTP / NIK (16 Digit)</label>
              <input type="text" name="id_card_number" maxlength="20" placeholder="3302xxxxxxxxxxxx"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono font-semibold">
            </div>

            <!-- No Handphone -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">No. Handphone / WhatsApp</label>
              <input type="text" name="customer_phone" placeholder="08xxxxxxxxxx"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono">
            </div>

            <!-- Tempat Lahir -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tempat Lahir</label>
              <input type="text" name="birth_place" placeholder="Contoh: Banyumas"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
            </div>

            <!-- Tanggal Lahir -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir</label>
              <input type="date" name="birth_date"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
            </div>

            <!-- Email -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
              <input type="text" name="customer_email" placeholder="pelanggan@gmail.com atau -"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
            </div>

            <!-- Pilihan Layanan -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Pilihan Layanan / Paket</label>
              <select name="package_name"
                      @change="
                        if ($el.value.includes('50')) document.getElementById('create_price').value = 220000;
                        else if ($el.value.includes('30')) document.getElementById('create_price').value = 165000;
                        else document.getElementById('create_price').value = 110000;
                      "
                      class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                <option value="Paket 20 Mbps">Paket 20 Mbps (Fiber Home)</option>
                <option value="Paket 30 Mbps">Paket 30 Mbps (Fiber Fast)</option>
                <option value="Paket 50 Mbps">Paket 50 Mbps (Fiber Ultra)</option>
              </select>
            </div>

            <!-- Harga Paket -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp)</label>
              <input type="number" name="price" id="create_price" value="110000"
                     class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-bold text-brand">
            </div>

            <!-- Pilihan Wilayah / Desa Cakupan -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">Wilayah / Desa Cakupan</label>
              <select name="wilayah" id="create_wilayah"
                      @change="
                        let selectedW = $el.value;
                        let addrEl = document.getElementById('create_address');
                        if (selectedW) {
                          if (!addrEl.value || addrEl.value.trim().startsWith('Desa ')) {
                            addrEl.value = 'Desa ' + selectedW + ' RT 01/RW 01, Kec. Cilongok, Kab. Banyumas';
                          } else if (!addrEl.value.toLowerCase().includes(selectedW.toLowerCase())) {
                            addrEl.value = 'Desa ' + selectedW + ', ' + addrEl.value;
                          }
                        }
                      "
                      class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                <option value="">-- Pilih Wilayah / Desa Cakupan --</option>
                @foreach($wilayahList as $wKey => $wName)
                  <option value="{{ $wKey }}">{{ $wName }}</option>
                @endforeach
              </select>
              <p class="text-[10px] text-slate-400 mt-1">Pilih dari daftar desa aktif, atau sesuaikan langsung pada alamat pemasangan.</p>
            </div>

            <!-- Alamat Lengkap -->
            <div class="sm:col-span-2">
              <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Pemasangan</label>
              <textarea name="address" id="create_address" rows="3" placeholder="Nama jalan, RT/RW, Dusun, Desa, Kec. Cilongok"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand"></textarea>
            </div>

            <!-- Status Pelanggan -->
            <div>
              <label class="block font-bold text-slate-700 mb-1">Status Aktivasi</label>
              <select name="status" class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                <option value="Selesai" selected>Selesai / Aktif (Online)</option>
                <option value="Non-Aktif">Non-Aktif / Isolir</option>
              </select>
            </div>

          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" @click="openCreateModal = false"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit"
                    class="bg-brand hover:bg-red-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-xs">
              Simpan Pelanggan
            </button>
          </div>

        </form>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 8. MODAL UBAH / EDIT PELANGGAN                 -->
  <!-- ============================================== -->
  <div x-show="openEditModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openEditModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openEditModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openEditModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-2xl w-full p-6 sm:p-8 relative shadow-2xl max-h-[92vh] overflow-y-auto"
           @click.stop>

        <!-- Close Button -->
        <button type="button" @click="openEditModal = false"
                class="absolute top-6 right-6 text-slate-400 hover:text-slate-700 text-xl font-bold">
          <i class="fa-solid fa-xmark"></i>
        </button>

        <template x-if="selectedCustomer">
          <form :action="'{{ url('admin/pelanggan') }}/' + selectedCustomer.id" method="POST" class="space-y-5 text-xs">
            @csrf
            @method('PUT')

            <div class="border-b border-slate-100 pb-3">
              <div class="flex items-center gap-2 mb-1">
                <span class="text-xs font-mono font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded" x-text="selectedCustomer.order_number"></span>
              </div>
              <h3 class="text-lg font-black text-slate-900">Ubah Data Pelanggan</h3>
              <p class="text-xs text-slate-500">Perbarui informasi KTP, tanggal lahir, kontak, layanan, atau alamat</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              
              <!-- Nama Lengkap -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Pelanggan <span class="text-red-500">*</span></label>
                <input type="text" name="customer_name" x-model="selectedCustomer.customer_name" required
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-semibold">
              </div>

              <!-- Akun PPOE -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">
                  <i class="fa-solid fa-network-wired text-brand mr-1"></i> Akun PPOE (PPPoE Secret / Username)
                </label>
                <input type="text" name="pppoe" x-model="selectedCustomer.pppoe" placeholder="contoh: sriwindiastuti"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono font-bold text-slate-900">
                <p class="text-[11px] text-slate-400 mt-1">Username login PPPoE untuk autentikasi koneksi pelanggan di router/ONT.</p>
              </div>

              <!-- No KTP / NIK -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">No. KTP / NIK (16 Digit)</label>
                <input type="text" name="id_card_number" x-model="selectedCustomer.id_card_number" maxlength="20"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono font-bold text-slate-900">
              </div>

              <!-- No Handphone -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">No. Handphone / WhatsApp</label>
                <input type="text" name="customer_phone" x-model="selectedCustomer.customer_phone"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-mono font-semibold">
              </div>

              <!-- Tempat Lahir -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Tempat Lahir</label>
                <input type="text" name="birth_place" x-model="selectedCustomer.birth_place"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
              </div>

              <!-- Tanggal Lahir -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Tanggal Lahir</label>
                <input type="date" name="birth_date" x-model="selectedCustomer.birth_date"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
              </div>

              <!-- Email -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Alamat Email</label>
                <input type="text" name="customer_email" x-model="selectedCustomer.customer_email" placeholder="pelanggan@gmail.com atau -"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand">
              </div>

              <!-- Pilihan Layanan -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Pilihan Layanan / Paket</label>
                <select name="package_name" x-model="selectedCustomer.package_name"
                        @change="
                          if ($el.value.includes('50')) selectedCustomer.price = 220000;
                          else if ($el.value.includes('30')) selectedCustomer.price = 165000;
                          else selectedCustomer.price = 110000;
                        "
                        class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                  <option value="Paket 20 Mbps">Paket 20 Mbps (Fiber Home)</option>
                  <option value="Paket 30 Mbps">Paket 30 Mbps (Fiber Fast)</option>
                  <option value="Paket 50 Mbps">Paket 50 Mbps (Fiber Ultra)</option>
                </select>
              </div>

              <!-- Harga Paket -->
              <div>
                <label class="block font-bold text-slate-700 mb-1">Harga Bulanan (Rp)</label>
                <input type="number" name="price" x-model="selectedCustomer.price"
                       class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand font-bold text-brand">
              </div>

              <!-- Pilihan Wilayah / Desa Cakupan -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Wilayah / Desa Cakupan</label>
                <select name="wilayah"
                        @change="
                          let selectedW = $el.value;
                          if (selectedW && selectedCustomer) {
                            if (!selectedCustomer.address || selectedCustomer.address.trim().startsWith('Desa ')) {
                              selectedCustomer.address = 'Desa ' + selectedW + ' RT 01/RW 01, Kec. Cilongok, Kab. Banyumas';
                            } else if (!selectedCustomer.address.toLowerCase().includes(selectedW.toLowerCase())) {
                              selectedCustomer.address = 'Desa ' + selectedW + ', ' + selectedCustomer.address;
                            }
                          }
                        "
                        class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white font-semibold">
                  <option value="">-- Sesuaikan / Pilih Wilayah --</option>
                  @foreach($wilayahList as $wKey => $wName)
                    <option value="{{ $wKey }}">{{ $wName }}</option>
                  @endforeach
                </select>
              </div>

              <!-- Alamat Lengkap -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Alamat Lengkap Pemasangan</label>
                <textarea name="address" rows="3" x-model="selectedCustomer.address"
                          class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand"></textarea>
              </div>

              <!-- Status Pelanggan -->
              <div class="sm:col-span-2">
                <label class="block font-bold text-slate-700 mb-1">Status Aktivasi Pelanggan</label>
                <select name="status" x-model="selectedCustomer.status"
                        class="w-full text-xs p-2.5 border border-slate-300 rounded-xl focus:ring-brand focus:border-brand bg-white">
                  <option value="Selesai">Selesai / Aktif (Online)</option>
                  <option value="Non-Aktif">Non-Aktif / Isolir</option>
                  <option value="Dibatalkan">Dibatalkan</option>
                </select>
              </div>

            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
              <button type="button" @click="openEditModal = false"
                      class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition">
                Batal
              </button>
              <button type="submit"
                      class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-xs">
                Simpan Perubahan
              </button>
            </div>

          </form>
        </template>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 9. MODAL HAPUS PELANGGAN                       -->
  <!-- ============================================== -->
  <div x-show="openDeleteModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openDeleteModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openDeleteModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openDeleteModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-md w-full p-6 relative shadow-2xl"
           @click.stop>

        <template x-if="selectedCustomer">
          <div class="text-center space-y-4">
            <div class="w-14 h-14 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl">
              <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div>
              <h3 class="text-lg font-black text-slate-900">Hapus Pelanggan?</h3>
              <p class="text-xs text-slate-500 mt-1">
                Apakah Anda yakin ingin menghapus data pelanggan <strong class="text-slate-900" x-text="selectedCustomer.customer_name"></strong> (<span class="font-mono" x-text="selectedCustomer.id_card_number"></span>)? Tindakan ini tidak dapat dibatalkan.
              </p>
            </div>

            <form :action="'{{ url('admin/pelanggan') }}/' + selectedCustomer.id" method="POST" class="pt-2 flex items-center justify-center gap-2">
              @csrf
              @method('DELETE')

              <button type="button" @click="openDeleteModal = false"
                      class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition">
                Batal
              </button>
              <button type="submit"
                      class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-xs">
                Ya, Hapus
              </button>
            </form>
          </div>
        </template>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 10. MODAL IMPORT EXCEL PELANGGAN               -->
  <!-- ============================================== -->
  <div x-show="openImportModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div x-show="openImportModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="if(!importLoading) openImportModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 sm:p-6">
      <div x-show="openImportModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-xl w-full p-6 sm:p-7 relative shadow-2xl space-y-5"
           @click.stop>

        <!-- Header Modal -->
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0">
              <i class="fa-solid fa-file-import"></i>
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900">Import Data Pelanggan</h3>
              <p class="text-xs text-slate-500 mt-0.5">Unggah berkas Excel (.xlsx, .xls) atau CSV untuk input massal</p>
            </div>
          </div>
          <button type="button" @click="openImportModal = false" :disabled="importLoading"
                  class="text-slate-400 hover:text-slate-700 text-lg p-1 transition disabled:opacity-50">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <!-- Download Template Banner -->
        <div class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-start gap-2.5">
            <i class="fa-solid fa-circle-info text-blue-600 text-sm mt-0.5 shrink-0"></i>
            <div>
              <p class="text-xs font-bold text-blue-950">Gunakan Template Standar</p>
              <p class="text-[11px] text-blue-700 mt-0.5 leading-relaxed">
                Unduh format template resmi dengan kolom yang sudah siap diisi.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('admin.pelanggan.template', ['format' => 'xls']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-blue-200 hover:bg-blue-100 text-blue-700 text-xs font-bold shadow-2xs transition">
              <i class="fa-solid fa-file-excel text-emerald-600"></i>
              <span>Format .xls</span>
            </a>
            <a href="{{ route('admin.pelanggan.template', ['format' => 'csv']) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-white border border-blue-200 hover:bg-blue-100 text-blue-700 text-xs font-bold shadow-2xs transition">
              <i class="fa-solid fa-file-csv text-blue-600"></i>
              <span>Format .csv</span>
            </a>
          </div>
        </div>

        <!-- Form Import -->
        <form action="{{ route('admin.pelanggan.import') }}" method="POST" enctype="multipart/form-data"
              @submit="importLoading = true" class="space-y-4">
          @csrf

          <!-- Upload Drop Zone -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">
              Pilih Berkas File Excel / CSV <span class="text-red-500">*</span>
            </label>
            
            <div class="relative border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl p-6 transition text-center cursor-pointer bg-slate-50 hover:bg-blue-50/40"
                 @click="$refs.fileInput.click()">
              <input type="file" name="file" x-ref="fileInput"
                     accept=".xlsx,.xls,.csv,.txt" required
                     @change="onFileSelect($event)"
                     class="hidden">

              <div class="space-y-2">
                <div class="w-12 h-12 mx-auto rounded-2xl bg-blue-100/80 text-blue-600 flex items-center justify-center text-2xl">
                  <i class="fa-solid fa-cloud-arrow-up" x-show="!importFileName"></i>
                  <i class="fa-solid fa-file-circle-check text-emerald-600" x-show="importFileName"></i>
                </div>

                <template x-if="!importFileName">
                  <div>
                    <p class="text-xs font-bold text-slate-800">
                      Klik untuk memilih berkas atau seret file ke sini
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                      Mendukung format <span class="font-semibold text-slate-600">.xlsx, .xls, .csv</span> (Membaca seluruh sheet sekaligus & tanpa batas ukuran)
                    </p>
                  </div>
                </template>

                <template x-if="importFileName">
                  <div class="space-y-1">
                    <p class="text-xs font-bold text-emerald-700 flex items-center justify-center gap-1.5">
                      <i class="fa-solid fa-circle-check"></i>
                      <span x-text="importFileName"></span>
                    </p>
                    <p class="text-[10px] text-slate-400">Klik lagi jika ingin mengganti berkas yang dipilih</p>
                  </div>
                </template>
              </div>
            </div>

            <div class="flex items-center gap-2 px-3 py-2 bg-blue-50 border border-blue-100 rounded-xl text-[11px] text-blue-800">
              <i class="fa-solid fa-layer-group text-blue-600"></i>
              <span><strong>Mendukung Seluruh Sheet:</strong> Jika berkas Excel memiliki beberapa sheet/lembar kerja, sistem akan membaca dan mengimpor seluruh sheet secara otomatis.</span>
            </div>
          </div>

          <!-- Options -->
          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-3.5 space-y-2">
            <label class="flex items-start gap-2.5 cursor-pointer">
              <input type="checkbox" name="update_existing" value="1" checked
                     class="mt-0.5 rounded text-brand focus:ring-brand border-slate-300">
              <div>
                <span class="text-xs font-bold text-slate-800">Perbarui data pelanggan jika No. KTP / NIK atau ID sudah ada</span>
                <p class="text-[11px] text-slate-500 mt-0.5">
                  Jika dicentang, data pelanggan yang cocok akan diperbarui (nama, kontak, paket, alamat). Jika tidak dicentang, baris duplikat akan dilewati.
                </p>
              </div>
            </label>
          </div>

          <!-- Petunjuk Kolom Header (Collapsible) -->
          <div x-data="{ openGuide: false }" class="border border-slate-200 rounded-2xl overflow-hidden">
            <button type="button" @click="openGuide = !openGuide"
                    class="w-full px-4 py-2.5 bg-slate-50 hover:bg-slate-100 flex items-center justify-between text-xs font-bold text-slate-700 transition">
              <div class="flex items-center gap-2">
                <i class="fa-solid fa-table-list text-slate-400"></i>
                <span>Lihat Format Kolom yang Didukung</span>
              </div>
              <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 transition-transform"
                 :class="openGuide ? 'rotate-180' : ''"></i>
            </button>

            <div x-show="openGuide" class="p-3.5 text-[11px] text-slate-600 space-y-2 border-t border-slate-200 bg-white">
              <p class="font-semibold text-slate-800">Kolom yang dikenali secara otomatis:</p>
              <div class="grid grid-cols-2 gap-2">
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; Nama Lengkap <span class="text-red-500 font-normal">*Wajib</span></p>
                  <p class="text-[10px] text-slate-500">Header: nama_lengkap, nama, customer_name</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; Alamat Pemasangan <span class="text-red-500 font-normal">*Wajib</span></p>
                  <p class="text-[10px] text-slate-500">Header: alamat_lengkap, alamat, address</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; No. KTP / NIK</p>
                  <p class="text-[10px] text-slate-500">Header: no_ktp, nik, id_card_number</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; No. Handphone (WA)</p>
                  <p class="text-[10px] text-slate-500">Header: no_handphone, no_hp, telepon, wa</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; Paket & Kecepatan</p>
                  <p class="text-[10px] text-slate-500">Header: paket, speed (20, 30, atau 50 Mbps)</p>
                </div>
                <div class="bg-slate-50 p-2 rounded-xl border border-slate-100">
                  <p class="font-bold text-slate-900">&bull; TTL & Status</p>
                  <p class="text-[10px] text-slate-500">Header: tempat_lahir, tanggal_lahir, status</p>
                </div>
              </div>
              <p class="text-[10px] text-slate-400 italic">
                Tips: Berkas hasil "Export Excel" dari halaman ini juga dapat langsung diimpor kembali tanpa perlu mengubah format kolom!
              </p>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button type="button" @click="openImportModal = false" :disabled="importLoading"
                    class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold transition disabled:opacity-50">
              Batal
            </button>
            <button type="submit" :disabled="importLoading || !importFileName"
                    class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-5 py-2.5 rounded-xl transition flex items-center gap-2 shadow-sm disabled:opacity-50">
              <i class="fa-solid fa-spinner fa-spin" x-show="importLoading"></i>
              <i class="fa-solid fa-file-import" x-show="!importLoading"></i>
              <span x-text="importLoading ? 'Memproses Import...' : 'Mulai Import Pelanggan'"></span>
            </button>
          </div>
        </form>

      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 10. MODAL HAPUS MASSAL PELANGGAN               -->
  <!-- ============================================== -->
  <div x-show="openBulkDeleteModal" style="display: none;" class="relative z-50" role="dialog" aria-modal="true">
    <div x-show="openBulkDeleteModal"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
         @click="openBulkDeleteModal = false"></div>

    <div class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4">
      <div x-show="openBulkDeleteModal"
           x-transition:enter="ease-out duration-200"
           x-transition:enter-start="opacity-0 scale-95"
           x-transition:enter-end="opacity-100 scale-100"
           x-transition:leave="ease-in duration-150"
           x-transition:leave-start="opacity-100 scale-100"
           x-transition:leave-end="opacity-0 scale-95"
           class="bg-white rounded-3xl max-w-md w-full p-6 relative shadow-2xl"
           @click.stop>

        <div class="text-center space-y-4">
          <div class="w-14 h-14 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl">
            <i class="fa-solid fa-trash-can"></i>
          </div>
          <div>
            <h3 class="text-lg font-black text-slate-900">
              <span x-text="bulkDeleteAll ? 'Hapus Semua Data Pelanggan?' : 'Hapus ' + selectedIds.length + ' Pelanggan Terpilih?'"></span>
            </h3>
            <p class="text-xs text-slate-500 mt-1 leading-relaxed">
              <span x-show="!bulkDeleteAll">
                Apakah Anda yakin ingin menghapus <strong class="text-slate-900"><span x-text="selectedIds.length"></span> data pelanggan</strong> yang dipilih?
              </span>
              <span x-show="bulkDeleteAll">
                Apakah Anda yakin ingin menghapus <strong class="text-red-600">SELURUH data pelanggan</strong> (<span x-text="totalCustomersCount"></span> pelanggan)?
              </span>
              Seluruh riwayat tagihan terkait juga akan dibersihkan. Tindakan ini <strong class="text-red-600">tidak dapat dibatalkan</strong>.
            </p>
          </div>

          <form action="{{ route('admin.pelanggan.bulk-delete') }}" method="POST" class="pt-2 flex items-center justify-center gap-2">
            @csrf
            <input type="hidden" name="delete_all" :value="bulkDeleteAll ? '1' : '0'">
            <template x-for="id in selectedIds" :key="id">
              <input type="hidden" name="ids[]" :value="id">
            </template>

            <button type="button" @click="openBulkDeleteModal = false"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs px-5 py-2.5 rounded-xl transition">
              Batal
            </button>
            <button type="submit"
                    class="bg-red-600 hover:bg-red-700 text-white font-bold text-xs px-5 py-2.5 rounded-xl transition shadow-xs">
              Ya, Hapus Sekarang
            </button>
          </form>
        </div>

      </div>
    </div>
  </div>

</div>
@endsection
