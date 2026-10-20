@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Manajemen Stok Barang')
@section('page-title', 'Stok Barang')

@section('content')
<div class="space-y-6"
     x-data="{
        tab: '{{ $tab }}',
        openCreateModal: false,
        openEditModal: false,
        openAddStockModal: false,
        openReduceStockModal: false,
        openAdjustStockModal: false,
        openDeleteModal: false,
        openBulkDeleteModal: false,
        openHistoryModal: false,
        openImportModal: false,
        openIncomingModal: false,
        openOutgoingModal: false,
        openEditMutationModal: false,
        
        selectedMaterial: null,
        selectedMutation: null,
        editMutationMaterialId: '',
        editMutationAmount: 1,
        editMutationNotes: '',
        editMutationDate: '{{ date('Y-m-d') }}',
        editMutationType: 'in',
        stockAmount: 1,
        stockNotes: '',
        transactionDate: '{{ date('Y-m-d') }}',
        newStockTarget: 0,
        
        selectedIds: [],
        bulkDeleteAll: false,
        pageMaterialIds: {{ Js::from($materials->pluck('id')->values()->all()) }},

        confirmBulkDelete(all = false) {
            this.bulkDeleteAll = all;
            this.openBulkDeleteModal = true;
        },

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

        openAddStock(m) {
            this.selectedMaterial = m;
            this.stockAmount = 1;
            this.stockNotes = '';
            this.transactionDate = '{{ date('Y-m-d') }}';
            this.openAddStockModal = true;
        },

        openReduceStock(m) {
            this.selectedMaterial = m;
            this.stockAmount = 1;
            this.stockNotes = '';
            this.transactionDate = '{{ date('Y-m-d') }}';
            this.openReduceStockModal = true;
        },

        openAdjustStock(m) {
            this.selectedMaterial = m;
            this.newStockTarget = m.computed_stock ?? m.stock;
            this.stockNotes = '';
            this.openAdjustStockModal = true;
        },

        openEdit(m) {
            this.selectedMaterial = Object.assign({}, m);
            this.openEditModal = true;
        },

        openEditMutation(mut) {
            this.selectedMutation = Object.assign({}, mut);
            this.editMutationMaterialId = mut.material_id;
            this.editMutationAmount = mut.amount;
            this.editMutationNotes = mut.notes ? mut.notes.replace(' (Barang Masuk)', '').replace(' (Barang Keluar)', '') : '';
            this.editMutationDate = mut.transaction_date ? String(mut.transaction_date).substring(0, 10) : (mut.created_at ? String(mut.created_at).substring(0, 10) : '{{ date('Y-m-d') }}');
            this.editMutationType = mut.type;
            this.openEditMutationModal = true;
        },

        openDelete(m) {
            this.selectedMaterial = m;
            this.openDeleteModal = true;
        },

        openHistory(m) {
            this.selectedMaterial = m;
            this.openHistoryModal = true;
        },

        formatQty(num) {
            if (num === null || num === undefined) return '0';
            return (Math.round(num) === Number(num)) 
                ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(num)
                : new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(num);
        },

        formatRupiah(number) {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(number || 0);
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

  @if(isset($errors) && $errors->any())
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl shadow-xs">
      <div class="flex items-center gap-2 mb-1">
        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
        <span class="text-xs font-bold">Terjadi kesalahan validasi:</span>
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
  <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-black text-slate-900 tracking-tight">Laporan & Manajemen Stok Barang</h2>
      <p class="text-xs text-slate-500 mt-1">
        Sinkronisasi inventaris fiber optik, perangkat modem ONT, splitter, drop core, serta rekonsiliasi barang masuk & keluar bulanan sesuai berkas CSV Banterpool.
      </p>
    </div>

    <!-- Tombol Aksi Toolbar -->
    <div class="flex flex-wrap items-center gap-2 shrink-0">
      
      <!-- 1. Tambah Barang Baru -->
      <button type="button" @click="openCreateModal = true"
              class="h-9 px-3 bg-brand hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
        <i class="fa-solid fa-plus text-xs"></i>
        <span>Tambah Barang</span>
      </button>

      <!-- 2. Catat Barang Masuk -->
      <button type="button" @click="openIncomingModal = true"
              class="h-9 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
        <i class="fa-solid fa-arrow-down text-xs"></i>
        <span>Catat Masuk</span>
      </button>

      <!-- 3. Catat Barang Keluar -->
      <button type="button" @click="openOutgoingModal = true"
              class="h-9 px-3 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
        <i class="fa-solid fa-arrow-up text-xs"></i>
        <span>Catat Keluar</span>
      </button>

      <!-- 4. Import File Excel & CSV -->
      <button type="button" @click="openImportModal = true"
              class="h-9 px-3 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
        <i class="fa-solid fa-file-import text-xs"></i>
        <span>Import Excel / CSV</span>
      </button>

      <!-- 5. Export Dropdown Menu (Excel & CSV) -->
      <div class="relative" x-data="{ openExport: false }">
        <button type="button" @click="openExport = !openExport" @click.away="openExport = false"
                class="h-9 px-3 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition flex items-center gap-1.5 border border-slate-200 whitespace-nowrap">
          <i class="fa-solid fa-file-export text-xs text-slate-500"></i>
          <span>Export ({{ $monthName }})</span>
          <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-0.5"></i>
        </button>

        <div x-show="openExport"
             style="display: none;"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute right-0 mt-1 w-52 bg-white rounded-2xl shadow-xl border border-slate-100 p-1.5 z-30 text-xs">
          <a href="{{ route('admin.material.export', array_merge(request()->query(), ['month' => $selectedMonth, 'year' => $selectedYear])) }}"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-emerald-50 hover:text-emerald-700 font-bold transition">
            <i class="fa-solid fa-file-excel text-emerald-600"></i>
            <span>Export Excel (.xls) - {{ $monthName }}</span>
          </a>
          <a href="{{ route('admin.material.export-csv', array_merge(request()->query(), ['month' => $selectedMonth, 'year' => $selectedYear])) }}"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-blue-50 hover:text-blue-700 font-bold transition">
            <i class="fa-solid fa-file-csv text-blue-600"></i>
            <span>Export CSV (.csv) - {{ $monthName }}</span>
          </a>
          <a href="{{ route('admin.material.mutations') }}"
             class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-slate-700 hover:bg-slate-100 font-bold transition border-t border-slate-100 mt-1">
            <i class="fa-solid fa-clock-rotate-left text-slate-400"></i>
            <span>Log Audit Mutasi Lengkap</span>
          </a>
        </div>
      </div>

      <!-- 6. Hapus Terpilih / Kosongkan -->
      <div>
        <button type="button"
                x-show="selectedIds.length > 0"
                style="display: none;"
                @click="confirmBulkDelete(false)"
                class="h-9 px-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
          <i class="fa-solid fa-trash-can text-xs"></i>
          <span>Hapus (<span x-text="selectedIds.length"></span>)</span>
        </button>

        <button type="button"
                x-show="selectedIds.length === 0"
                @click="confirmBulkDelete(true)"
                @if($stats['total_items'] == 0) disabled @endif
                class="h-9 px-3 bg-slate-100 hover:bg-red-50 text-slate-600 hover:text-red-600 border border-slate-200 text-xs font-bold rounded-xl transition flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                title="Kosongkan Data Stok">
          <i class="fa-solid fa-trash-arrow-up text-xs"></i>
          <span class="hidden sm:inline">Kosongkan</span>
        </button>
      </div>

    </div>
  </div>


  <!-- ============================================== -->
  <!-- 5. NAVIGATION TABS (SESUAI STRUKTUR CSV)       -->
  <!-- ============================================== -->
  <div class="border-b border-slate-200">
    <div class="flex items-center gap-2 overflow-x-auto pb-px">
      
      <!-- Tab 1: Laporan Stok Barang (Format Master CSV) -->
      <a href="{{ route('admin.material.index', array_merge(request()->query(), ['tab' => 'laporan'])) }}"
         class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition whitespace-nowrap {{ $tab === 'laporan' ? 'border-brand text-brand bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
        <i class="fa-solid fa-table-list"></i>
        <span>Laporan Stok Barang</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'laporan' ? 'bg-red-100 text-brand' : 'bg-slate-100 text-slate-600' }}">
          {{ $stats['total_items'] }}
        </span>
      </a>

      <!-- Tab 2: Barang Masuk (Sesuai Kolom CSV) -->
      <a href="{{ route('admin.material.index', array_merge(request()->query(), ['tab' => 'masuk'])) }}"
         class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition whitespace-nowrap {{ $tab === 'masuk' ? 'border-emerald-600 text-emerald-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
        <i class="fa-solid fa-arrow-down"></i>
        <span>Barang Masuk ({{ $monthName }})</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'masuk' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
          {{ $stats['incoming_count'] }}
        </span>
      </a>

      <!-- Tab 3: Barang Keluar (Sesuai Kolom CSV) -->
      <a href="{{ route('admin.material.index', array_merge(request()->query(), ['tab' => 'keluar'])) }}"
         class="px-4 py-2.5 rounded-t-xl font-bold text-xs flex items-center gap-2 border-b-2 transition whitespace-nowrap {{ $tab === 'keluar' ? 'border-rose-600 text-rose-700 bg-white' : 'border-transparent text-slate-500 hover:text-slate-800 hover:bg-slate-50' }}">
        <i class="fa-solid fa-arrow-up"></i>
        <span>Barang Keluar ({{ $monthName }})</span>
        <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'keluar' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600' }}">
          {{ $stats['outgoing_count'] }}
        </span>
      </a>



    </div>
  </div>


  <!-- ============================================== -->
  <!-- 6. KONTEN TAB 1: LAPORAN STOK BARANG           -->
  <!-- ============================================== -->
  @if($tab === 'laporan')
    <div class="space-y-4">
      
      <!-- Filter Toolbar -->
      <div class="bg-white p-3 sm:p-3.5 rounded-2xl border border-slate-200/80 shadow-xs relative z-30">
        <form method="GET" action="{{ route('admin.material.index') }}" class="flex items-center gap-2 w-full flex-nowrap">
          <input type="hidden" name="tab" value="laporan">

          <!-- 1. Dropdown Bulan (Januari - Desember) -->
          <select id="filter-month" name="month" onchange="this.form.submit()"
                  class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-bold text-slate-800 px-2.5 pr-7 shrink-0">
            <option value="all" {{ $selectedMonth === 'all' ? 'selected' : '' }}>Semua Periode</option>
            @foreach(\App\Http\Controllers\Admin\MaterialController::MONTHS as $mNum => $mTxt)
              <option value="{{ $mNum }}" {{ (string)$selectedMonth === (string)$mNum ? 'selected' : '' }}>
                {{ $mTxt }}
              </option>
            @endforeach
          </select>

          <!-- 2. Dropdown Tahun -->
          <select id="filter-year" name="year" onchange="this.form.submit()"
                  class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-bold text-slate-800 px-2.5 pr-7 shrink-0">
            <option value="all" {{ (string)$selectedYear === 'all' ? 'selected' : '' }}>Semua Tahun</option>
            @foreach([2025, 2026, 2027, 2028] as $yVal)
              <option value="{{ $yVal }}" {{ (string)$selectedYear === (string)$yVal ? 'selected' : '' }}>{{ $yVal }}</option>
            @endforeach
          </select>

          <!-- 3. Dropdown Status Stok -->
          <select id="filter-status" name="status" onchange="this.form.submit()"
                  class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-bold text-slate-800 px-2.5 pr-7 shrink-0">
            <option value="all" {{ $statusFilter === 'all' ? 'selected' : '' }}>Semua Status ({{ $stats['total_items'] }})</option>
            <option value="aman" {{ $statusFilter === 'aman' ? 'selected' : '' }}>🟢 Aman ({{ $stats['safe_stock_count'] }})</option>
            <option value="menipis" {{ $statusFilter === 'menipis' ? 'selected' : '' }}>🟡 Menipis ({{ $stats['low_stock_count'] }})</option>
            <option value="habis" {{ $statusFilter === 'habis' ? 'selected' : '' }}>🔴 Habis ({{ $stats['out_of_stock_count'] }})</option>
            <option value="minus" {{ $statusFilter === 'minus' ? 'selected' : '' }}>🟣 Minus ({{ $stats['negative_stock_count'] }})</option>
          </select>

          <!-- 4. Filter Kategori Dropdown -->
          <select name="category" onchange="this.form.submit()"
                  class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium px-2.5 pr-7 shrink-0">
            <option value="all" {{ $categoryFilter === 'all' ? 'selected' : '' }}>Semua Kategori</option>
            @foreach($categories as $cat)
              <option value="{{ $cat }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>

          <!-- 5. Filter Urutan (Sort) -->
          <select name="sort" onchange="this.form.submit()"
                  class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium px-2.5 pr-7 shrink-0">
            <option value="code_asc" {{ $sort === 'code_asc' ? 'selected' : '' }}>Kode (A001 &rarr; A135)</option>
            <option value="code_desc" {{ $sort === 'code_desc' ? 'selected' : '' }}>Kode (A135 &rarr; A001)</option>
            <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Nama Barang (A &rarr; Z)</option>
            <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Nama Barang (Z &rarr; A)</option>
            <option value="month_asc" {{ $sort === 'month_asc' ? 'selected' : '' }}>Bulan (Jan &rarr; Des)</option>
            <option value="month_desc" {{ $sort === 'month_desc' ? 'selected' : '' }}>Bulan (Des &rarr; Jan)</option>
            <option value="category_asc" {{ $sort === 'category_asc' ? 'selected' : '' }}>Kategori (A &rarr; Z)</option>
            <option value="category_desc" {{ $sort === 'category_desc' ? 'selected' : '' }}>Kategori (Z &rarr; A)</option>
            <option value="status_asc" {{ $sort === 'status_asc' ? 'selected' : '' }}>Status (Aman &rarr; Minus)</option>
            <option value="status_desc" {{ $sort === 'status_desc' ? 'selected' : '' }}>Status (Minus &rarr; Aman)</option>
            <option value="stock_desc" {{ $sort === 'stock_desc' ? 'selected' : '' }}>Stok Akhir Terbanyak</option>
            <option value="stock_asc" {{ $sort === 'stock_asc' ? 'selected' : '' }}>Stok Sedikit / Minus</option>
            <option value="in_desc" {{ $sort === 'in_desc' ? 'selected' : '' }}>Masuk Terbanyak</option>
            <option value="out_desc" {{ $sort === 'out_desc' ? 'selected' : '' }}>Keluar Terbanyak</option>
          </select>

          <!-- 6. Input Search with Live Autocomplete Suggestions (Sesuai Fitur Data Pelanggan) -->
          <div class="relative flex-1 min-w-[140px]"
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
                     const res = await fetch(`{{ route('admin.material.index') }}?ajax=1&q=${encodeURIComponent(val)}`, {
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
                   this.searchVal = item.name;
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
                     const firstChar = name.charAt(0).toUpperCase();
                     const restMatched = name.substring(1, prefix.length);
                     const matched = firstChar + restMatched;
                     const rest = name.substring(prefix.length);
                     return `<strong class='font-black text-slate-900'>${matched}</strong>${rest}`;
                   }
                   const wordIndex = lowerName.indexOf(' ' + lowerPrefix);
                   if (wordIndex !== -1) {
                     const before = name.substring(0, wordIndex + 1);
                     const matched = name.substring(wordIndex + 1, wordIndex + 1 + prefix.length);
                     const rest = name.substring(wordIndex + 1 + prefix.length);
                     return `${before}<strong class='font-black text-slate-900'>${matched}</strong>${rest}`;
                   }
                   return name;
                 }
               }"
               @click.outside="showDropdown = false">
            
            <div class="relative w-full">
              <input type="text" name="q"
                     x-model="searchVal"
                     @input.debounce.150ms="onInput()"
                     @keydown="onKeyDown($event)"
                     @focus="if (suggestions.length > 0) showDropdown = true"
                     autocomplete="off"
                     placeholder="Cari inisial A-Z, nama, kode..."
                     class="h-9 w-full text-xs rounded-xl border-slate-200 bg-slate-50 pl-8 pr-7 focus:border-brand focus:ring-brand font-medium">
              <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>

              <!-- Spinner Loading -->
              <div x-show="loading" style="display: none;" class="absolute right-2.5 top-2.5 text-slate-400 text-xs">
                <i class="fa-solid fa-spinner fa-spin"></i>
              </div>

              <!-- Dropdown Autocomplete Awalan (Sama Persis Seperti Menu Data Pelanggan) -->
              <div x-show="showDropdown && suggestions.length > 0"
                   style="display: none;"
                   x-transition:enter="transition ease-out duration-100"
                   x-transition:enter-start="transform opacity-0 scale-98"
                   x-transition:enter-end="transform opacity-100 scale-100"
                   x-transition:leave="transition ease-in duration-75"
                   x-transition:leave-start="transform opacity-100 scale-100"
                   x-transition:leave-end="transform opacity-0 scale-98"
                   class="absolute left-0 top-full mt-1.5 bg-white border border-slate-200/90 rounded-2xl shadow-xl z-50 overflow-hidden divide-y divide-slate-100 max-h-72 overflow-y-auto w-full min-w-[340px] sm:min-w-[400px]">
                
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
                        <i class="fa-solid fa-box text-slate-500"></i>
                      </div>
                      <div class="truncate">
                        <span class="text-xs font-semibold" x-html="highlightPrefix(item.name, searchVal)"></span>
                        <span class="text-[10px] text-slate-400 ml-1.5" x-text="item.category ? '• ' + item.category : ''"></span>
                      </div>
                    </div>
                    <div class="text-right shrink-0 ml-3">
                      <span class="text-[10px] text-brand font-mono font-bold block" x-text="item.code ? 'KODE: ' + item.code : ''"></span>
                      <span class="text-[10px] text-slate-400 font-mono block" x-text="'Stok: ' + (item.stock !== undefined && item.stock !== null ? formatQty(item.stock) : 0) + ' ' + (item.unit || 'Unit')"></span>
                    </div>
                  </div>
                </template>
              </div>
            </div>
          </div>

          <!-- 7. Tombol Cari -->
          <button type="submit" class="h-9 px-3.5 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition shrink-0 shadow-xs flex items-center justify-center whitespace-nowrap">
            Cari
          </button>

          <!-- 8. Tombol Reset Semua Filter & Sortir -->
          @if($selectedMonth != 10 || (string)$selectedYear !== '2026' || !empty($search) || $categoryFilter !== 'all' || $statusFilter !== 'all' || $sort !== 'code_asc')
            <a href="{{ route('admin.material.index', ['tab' => 'laporan']) }}" title="Reset Filter & Sortir"
               class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center gap-1.5 shrink-0 whitespace-nowrap">
              <i class="fa-solid fa-rotate-left text-[10px]"></i>
              <span>Reset</span>
            </a>
          @endif

        </form>
      </div>

      <!-- Tabel Master Laporan Stok Barang Sesuai Format CSV -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <!-- 1. Kode Barang (Paling Kiri) -->
                <th class="py-3.5 px-3 text-center w-28">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'code_asc' ? 'code_desc' : 'code_asc']) }}"
                     class="inline-flex items-center justify-center gap-1 hover:text-slate-900 transition w-full" title="Sortir Kode">
                    <span>Kode Barang</span>
                    <i class="fa-solid {{ $sort === 'code_asc' ? 'fa-arrow-up-short-wide text-brand' : ($sort === 'code_desc' ? 'fa-arrow-down-wide-short text-brand' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 2. Bulan -->
                <th class="py-3.5 px-3 text-center w-28">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'month_asc' ? 'month_desc' : 'month_asc']) }}"
                     class="inline-flex items-center justify-center gap-1 hover:text-slate-900 transition w-full" title="Sortir Bulan">
                    <span>Bulan</span>
                    <i class="fa-solid {{ $sort === 'month_asc' ? 'fa-arrow-up text-brand' : ($sort === 'month_desc' ? 'fa-arrow-down text-brand' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 3. Nama Barang -->
                <th class="py-3.5 px-4 text-center min-w-[200px]">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'name_asc' ? 'name_desc' : 'name_asc']) }}"
                     class="inline-flex items-center justify-center gap-1 hover:text-slate-900 transition w-full" title="Sortir Nama Barang">
                    <span>Nama Barang</span>
                    <i class="fa-solid {{ $sort === 'name_asc' ? 'fa-arrow-up-a-z text-brand' : ($sort === 'name_desc' ? 'fa-arrow-down-z-a text-brand' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 4. Stok Awal -->
                <th class="py-3.5 px-3 text-center whitespace-nowrap font-black text-slate-700 bg-slate-100/50" style="text-align: center !important;">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'initial_desc' ? 'initial_asc' : 'initial_desc']) }}"
                     class="inline-flex items-center justify-center gap-1.5 hover:text-slate-900 transition whitespace-nowrap" title="Sortir Stok Awal">
                    <span>Stok Awal</span>
                    <i class="fa-solid {{ $sort === 'initial_desc' ? 'fa-arrow-down-wide-short text-brand' : ($sort === 'initial_asc' ? 'fa-arrow-up-short-wide text-brand' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 5. Barang Masuk -->
                <th class="py-3.5 px-3 text-center whitespace-nowrap font-black text-emerald-700 bg-emerald-50/50" style="text-align: center !important;">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'in_desc' ? 'in_asc' : 'in_desc']) }}"
                     class="inline-flex items-center justify-center gap-1.5 hover:text-emerald-900 transition whitespace-nowrap" title="Sortir Barang Masuk">
                    <span>Barang Masuk</span>
                    <i class="fa-solid {{ $sort === 'in_desc' ? 'fa-arrow-down-wide-short text-emerald-700' : ($sort === 'in_asc' ? 'fa-arrow-up-short-wide text-emerald-700' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 6. Barang Keluar -->
                <th class="py-3.5 px-3 text-center whitespace-nowrap font-black text-rose-700 bg-rose-50/50" style="text-align: center !important;">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'out_desc' ? 'out_asc' : 'out_desc']) }}"
                     class="inline-flex items-center justify-center gap-1.5 hover:text-rose-900 transition whitespace-nowrap" title="Sortir Barang Keluar">
                    <span>Barang Keluar</span>
                    <i class="fa-solid {{ $sort === 'out_desc' ? 'fa-arrow-down-wide-short text-rose-700' : ($sort === 'out_asc' ? 'fa-arrow-up-short-wide text-rose-700' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 7. Stok Akhir -->
                <th class="py-3.5 px-3 text-center whitespace-nowrap font-black text-slate-900 bg-amber-50/40" style="text-align: center !important;">
                  <a href="{{ request()->fullUrlWithQuery(['sort' => $sort === 'stock_desc' ? 'stock_asc' : 'stock_desc']) }}"
                     class="inline-flex items-center justify-center gap-1.5 hover:text-brand transition whitespace-nowrap" title="Sortir Stok Akhir">
                    <span>Stok Akhir</span>
                    <i class="fa-solid {{ $sort === 'stock_desc' ? 'fa-arrow-down-wide-short text-brand' : ($sort === 'stock_asc' ? 'fa-arrow-up-short-wide text-brand' : 'fa-sort text-slate-300') }} text-[10px]"></i>
                  </a>
                </th>

                <!-- 8. Aksi (Edit) -->
                <th class="py-3.5 px-3 text-center w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
              @forelse($materials as $material)
                @php
                  $stockVal = $material->computed_stock ?? $material->stock;
                  $isNegative = $stockVal < 0;
                  $isZero = $stockVal == 0;
                  $isLow = $stockVal > 0 && $stockVal <= $material->min_stock;
                  $isSafe = $stockVal > $material->min_stock;
                @endphp
                <tr class="hover:bg-slate-50/70 transition">
                  
                  <!-- 1. Kode Barang (Paling Kiri) -->
                  <td class="py-3 px-3 text-center">
                    <span class="font-mono font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                      {{ $material->code }}
                    </span>
                  </td>

                  <!-- 2. Bulan -->
                  <td class="py-3 px-3 text-center">
                    <span class="inline-block bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded border border-slate-200 whitespace-nowrap">
                      {{ $material->display_month ?? $material->month ?? $monthName }}
                    </span>
                  </td>

                  <!-- 3. Nama Barang -->
                  <td class="py-3 px-4 text-center">
                    <div class="font-extrabold text-slate-900 hover:text-brand transition cursor-pointer"
                         @click="openHistory({{ Js::from($material) }})">
                      {{ $material->name }}
                    </div>
                  </td>

                  <!-- 4. Stok Awal -->
                  <td class="py-3 px-3 text-center font-mono font-bold text-slate-700 bg-slate-100/30" style="text-align: center !important;">
                    <div class="w-full text-center" style="text-align: center !important;">
                      {{ \App\Models\Material::formatQty($material->computed_initial_stock ?? $material->initial_stock) }}
                    </div>
                  </td>

                  <!-- 5. Barang Masuk -->
                  <td class="py-3 px-3 text-center font-mono font-bold bg-emerald-50/30" style="text-align: center !important;">
                    @php $inVal = $material->computed_total_in ?? $material->total_in; @endphp
                    <div class="w-full text-center" style="text-align: center !important;">
                      @if($inVal > 0)
                        <span class="text-emerald-700 font-black">+{{ \App\Models\Material::formatQty($inVal) }}</span>
                      @else
                        <span class="text-slate-400">0</span>
                      @endif
                    </div>
                  </td>

                  <!-- 6. Barang Keluar -->
                  <td class="py-3 px-3 text-center font-mono font-bold bg-rose-50/30" style="text-align: center !important;">
                    @php $outVal = $material->computed_total_out ?? $material->total_out; @endphp
                    <div class="w-full text-center" style="text-align: center !important;">
                      @if($outVal > 0)
                        <span class="text-rose-700 font-black">-{{ \App\Models\Material::formatQty($outVal) }}</span>
                      @else
                        <span class="text-slate-400">0</span>
                      @endif
                    </div>
                  </td>

                  <!-- 7. Stok Akhir -->
                  <td class="py-3 px-3 text-center font-mono font-black text-sm bg-amber-50/20" style="text-align: center !important;">
                    <div class="w-full text-center" style="text-align: center !important;">
                      <span class="{{ $isNegative ? 'text-rose-600 underline font-black' : ($isZero ? 'text-red-500' : ($isLow ? 'text-amber-500' : 'text-slate-900')) }}">
                        {{ \App\Models\Material::formatQty($stockVal) }}
                      </span>
                    </div>
                  </td>

                  <!-- 8. Aksi (Edit) -->
                  <td class="py-3 px-3 text-center">
                    <div class="flex items-center justify-center">
                      <button type="button" @click="openEdit({{ Js::from($material) }})"
                              title="Edit Barang"
                              class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-200 rounded-lg flex items-center gap-1.5 transition text-xs font-bold shadow-2xs">
                        <i class="fa-solid fa-pen text-[10px]"></i>
                        <span>Edit</span>
                      </button>
                    </div>
                  </td>

                </tr>
              @empty
                <tr>
                  <td colspan="8" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center space-y-2">
                      <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-xl">
                        <i class="fa-solid fa-boxes-stacked"></i>
                      </div>
                      <p class="font-bold text-slate-700 text-sm">Tidak Ada Data Barang</p>
                      <p class="text-xs text-slate-400 max-w-sm">
                        Tidak ditemukan data barang yang sesuai filter pada periode {{ $monthName }} {{ $selectedYear }}.
                      </p>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($materials->hasPages())
          <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
            {{ $materials->links() }}
          </div>
        @endif
      </div>

    </div>
  @endif


  <!-- ============================================== -->
  <!-- 7. KONTEN TAB 2: BARANG MASUK (PER BULAN)      -->
  <!-- ============================================== -->
  @if($tab === 'masuk')
    <div class="space-y-4">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
          <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs">
              <i class="fa-solid fa-arrow-down"></i>
            </span>
            <span>Tabel Log Barang Masuk (Periode {{ $monthName }} {{ $selectedYear }})</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Format CSV: <strong>Tgl, Nama Barang, Jumlah Masuk, Catatan</strong> &bull; Total: <strong>+{{ \App\Models\Material::formatQty($stats['total_in']) }}</strong> ({{ $stats['incoming_count'] }} transaksi).
          </p>
        </div>

        <button type="button" @click="openIncomingModal = true"
                class="h-9 px-4 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
          <i class="fa-solid fa-plus"></i>
          <span>Catat Barang Masuk Baru</span>
        </button>
      </div>

      <!-- Tabel Barang Masuk -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-32 text-center">Tgl</th>
                <th class="py-3 px-4 text-center">Nama Barang</th>
                <th class="py-3 px-4 text-center w-36">Jumlah Masuk</th>
                <th class="py-3 px-4 text-center">Catatan</th>
                <th class="py-3 px-4 w-24 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
              @forelse($incomingMutations as $idx => $in)
                <tr class="hover:bg-emerald-50/20 transition">
                  <td class="py-3 px-4 text-center text-slate-400">{{ $incomingMutations->firstItem() + $idx }}</td>
                  
                  <!-- Tanggal (Tgl) -->
                  <td class="py-3 px-4 text-center font-mono font-bold text-slate-900">
                    {{ $in->transaction_date ? $in->transaction_date->format('d/m/Y') : $in->created_at->format('d/m/Y') }}
                  </td>

                  <!-- Nama Barang -->
                  <td class="py-3 px-4 text-center font-extrabold text-slate-900">
                    {{ $in->material->name ?? 'Barang Dihapus' }}
                  </td>

                  <!-- Jumlah Masuk -->
                  <td class="py-3 px-4 text-center">
                    <span class="font-black text-sm text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                      +{{ \App\Models\Material::formatQty($in->amount) }}
                    </span>
                    <span class="text-[10px] text-slate-400 ml-1">{{ $in->material->unit ?? 'Unit' }}</span>
                  </td>

                  <!-- Catatan (e.g. Pak Mono, Aris) -->
                  <td class="py-3 px-4 text-center text-slate-800">
                    <span class="font-semibold">{{ str_replace(' (Barang Masuk)', '', $in->notes) }}</span>
                  </td>

                  <!-- Aksi: Edit & Hapus -->
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button type="button" @click="openEditMutation({{ Js::from($in) }})"
                              title="Edit Catatan Barang Masuk"
                              class="w-7 h-7 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-200 rounded-lg flex items-center justify-center transition text-xs shadow-2xs">
                        <i class="fa-solid fa-pen text-[10px]"></i>
                      </button>
                      <form method="POST" action="{{ route('admin.material.mutation.destroy', $in->id) }}" onsubmit="return confirm('Hapus catatan mutasi masuk ini? Stok barang akan disesuaikan kembali.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-7 h-7 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200 rounded-lg transition inline-flex items-center justify-center text-xs shadow-2xs" title="Hapus Catatan Mutasi">
                          <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center space-y-1.5">
                      <i class="fa-solid fa-inbox text-2xl text-slate-300"></i>
                      <p class="font-bold text-slate-700 text-xs">Tidak Ada Transaksi Barang Masuk</p>
                      <p class="text-[11px] text-slate-400">Belum ada catatan barang masuk pada bulan <strong>{{ $monthName }} {{ $selectedYear }}</strong>.</p>
                      <button type="button" @click="openIncomingModal = true" class="mt-2 px-3 py-1.5 bg-emerald-600 text-white text-xs font-bold rounded-xl">
                        + Catat Barang Masuk
                      </button>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($incomingMutations->hasPages())
          <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
            {{ $incomingMutations->links() }}
          </div>
        @endif
      </div>

    </div>
  @endif


  <!-- ============================================== -->
  <!-- 8. KONTEN TAB 3: BARANG KELUAR (PER BULAN)     -->
  <!-- ============================================== -->
  @if($tab === 'keluar')
    <div class="space-y-4">
      
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
          <h3 class="font-black text-slate-900 text-sm flex items-center gap-2">
            <span class="w-6 h-6 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center text-xs">
              <i class="fa-solid fa-arrow-up"></i>
            </span>
            <span>Tabel Log Barang Keluar (Periode {{ $monthName }} {{ $selectedYear }})</span>
          </h3>
          <p class="text-xs text-slate-400 mt-0.5">
            Format CSV: <strong>Tgl, Nama Barang, Jumlah Keluar, Catatan</strong> &bull; Total: <strong>-{{ \App\Models\Material::formatQty($stats['total_out']) }}</strong> ({{ $stats['outgoing_count'] }} transaksi).
          </p>
        </div>

        <button type="button" @click="openOutgoingModal = true"
                class="h-9 px-4 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition flex items-center gap-1.5 shadow-sm whitespace-nowrap">
          <i class="fa-solid fa-minus"></i>
          <span>Catat Barang Keluar Baru</span>
        </button>
      </div>

      <!-- Tabel Barang Keluar -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
                <th class="py-3 px-4 w-12 text-center">No</th>
                <th class="py-3 px-4 w-32 text-center">Tgl</th>
                <th class="py-3 px-4 text-center">Nama Barang</th>
                <th class="py-3 px-4 text-center w-36">Jumlah Keluar</th>
                <th class="py-3 px-4 text-center">Catatan</th>
                <th class="py-3 px-4 w-24 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
              @forelse($outgoingMutations as $idx => $out)
                <tr class="hover:bg-rose-50/20 transition">
                  <td class="py-3 px-4 text-center text-slate-400">{{ $outgoingMutations->firstItem() + $idx }}</td>
                  
                  <!-- Tanggal (Tgl) -->
                  <td class="py-3 px-4 text-center font-mono font-bold text-slate-900">
                    {{ $out->transaction_date ? $out->transaction_date->format('d/m/Y') : $out->created_at->format('d/m/Y') }}
                  </td>

                  <!-- Nama Barang -->
                  <td class="py-3 px-4 text-center font-extrabold text-slate-900">
                    {{ $out->material->name ?? 'Barang Dihapus' }}
                  </td>

                  <!-- Jumlah Keluar -->
                  <td class="py-3 px-4 text-center">
                    <span class="font-black text-sm text-rose-600 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                      -{{ \App\Models\Material::formatQty($out->amount) }}
                    </span>
                    <span class="text-[10px] text-slate-400 ml-1">{{ $out->material->unit ?? 'Unit' }}</span>
                  </td>

                  <!-- Catatan (e.g. Okta_ThreeFive, Bagas_ThreeFive) -->
                  <td class="py-3 px-4 text-center text-slate-800">
                    <span class="inline-block px-2 py-0.5 rounded-lg bg-slate-100 font-semibold text-slate-700 border border-slate-200 text-[11px]">
                      <i class="fa-solid fa-user-gear text-[10px] text-slate-400 mr-1"></i>
                      {{ str_replace(' (Barang Keluar)', '', $out->notes) }}
                    </span>
                  </td>

                  <!-- Aksi: Edit & Hapus -->
                  <td class="py-3 px-4 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                      <button type="button" @click="openEditMutation({{ Js::from($out) }})"
                              title="Edit Catatan Barang Keluar"
                              class="w-7 h-7 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-200 rounded-lg flex items-center justify-center transition text-xs shadow-2xs">
                        <i class="fa-solid fa-pen text-[10px]"></i>
                      </button>
                      <form method="POST" action="{{ route('admin.material.mutation.destroy', $out->id) }}" onsubmit="return confirm('Hapus catatan mutasi keluar ini? Stok barang akan dikembalikan ke posisi semula.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-7 h-7 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200 rounded-lg transition inline-flex items-center justify-center text-xs shadow-2xs" title="Hapus Catatan Mutasi">
                          <i class="fa-solid fa-trash-can text-[10px]"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="6" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center space-y-1.5">
                      <i class="fa-solid fa-inbox text-2xl text-slate-300"></i>
                      <p class="font-bold text-slate-700 text-xs">Tidak Ada Transaksi Barang Keluar</p>
                      <p class="text-[11px] text-slate-400">Belum ada catatan barang keluar pada bulan <strong>{{ $monthName }} {{ $selectedYear }}</strong>.</p>
                      <button type="button" @click="openOutgoingModal = true" class="mt-2 px-3 py-1.5 bg-rose-600 text-white text-xs font-bold rounded-xl">
                        - Catat Barang Keluar
                      </button>
                    </div>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        @if($outgoingMutations->hasPages())
          <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
            {{ $outgoingMutations->links() }}
          </div>
        @endif
      </div>

    </div>
  @endif





  <!-- ============================================== -->
  <!-- MODAL 1: TAMBAH MASTER BARANG BARU            -->
  <!-- ============================================== -->
  <div x-show="openCreateModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openCreateModal = false">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-brand/10 text-brand flex items-center justify-center text-sm">
            <i class="fa-solid fa-boxes-stacked"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Tambah Master Barang Baru</h3>
            <p class="text-[11px] text-slate-400">Pendaftaran item barang ke inventaris Banterpool</p>
          </div>
        </div>
        <button type="button" @click="openCreateModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('admin.material.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Kode Barang -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Kode Barang <span class="text-slate-400 font-normal">(Opsional / Otomatis AXXX)</span>
            </label>
            <input type="text" name="code" placeholder="Misal: A133"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Kategori -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Barang *</label>
            <input type="text" name="category" list="category_options" placeholder="Pilih / Ketik Kategori" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
            <datalist id="category_options">
              <option value="Tiang & Bracket">
              <option value="Aksesoris & Bracket">
              <option value="Clamp & Aksesoris">
              <option value="Joint Closure">
              <option value="ODP & Box Pasif">
              <option value="Kabel Drop & Fiber Optik">
              <option value="Kabel LAN & UTP">
              <option value="Patchcord & Pigtail">
              <option value="Adapter & Konektor Optik">
              <option value="Splitter Optik">
              <option value="Router & AP Wireless">
              <option value="Modem & ONT GPON">
              <option value="Perangkat Jaringan & Switch">
              <option value="Media Converter (HTB)">
              <option value="Power & Adaptor">
              <option value="Protection Sleeve (Smuft)">
              <option value="Kabel Ties">
              <option value="Material Pembantu & Instalasi">
            </datalist>
          </div>
        </div>

        <!-- Nama Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang *</label>
          <input type="text" name="name" placeholder="Misal: Tiang T7 (3INC - 6M) / Modem ZTE F672Y" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Satuan -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Satuan *</label>
            <select name="unit" required class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
              <option value="Unit">Unit</option>
              <option value="Pcs">Pcs</option>
              <option value="Meter">Meter</option>
              <option value="Roll">Roll</option>
              <option value="Batang">Batang</option>
              <option value="Box">Box</option>
              <option value="Set">Set</option>
            </select>
          </div>

          <!-- Stok Awal -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal Fisik *</label>
            <input type="number" step="any" name="initial_stock" value="0" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold">
          </div>

          <!-- Batas Minimum Alert -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Batas Min Alert</label>
            <input type="number" step="any" name="min_stock" value="5"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Bulan Periode -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Bulan Periode</label>
            <input type="text" name="month" value="{{ $monthName }}" placeholder="Misal: Oktober / Oktober 2026"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
          </div>

          <!-- Harga Satuan Estimasi -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Harga Satuan (Rp) <span class="text-slate-400 font-normal">(Opsional)</span>
            </label>
            <input type="number" name="unit_price" value="0" min="0" step="100" placeholder="Rp"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openCreateModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-brand hover:bg-red-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Simpan Barang</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 2: CATAT BARANG MASUK (GLOBAL)           -->
  <!-- ============================================== -->
  <div x-show="openIncomingModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openIncomingModal = false">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-sm">
            <i class="fa-solid fa-arrow-down"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Catat Transaksi Barang Masuk</h3>
            <p class="text-[11px] text-slate-400">Pengadaan, pembelian supplier, atau restock gudang</p>
          </div>
        </div>
        <button type="button" @click="openIncomingModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('admin.material.store-in') }}" class="space-y-4">
        @csrf

        <!-- Pilih Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Barang / Material *</label>
          <select name="material_id" required
                  class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
            <option value="">-- Pilih Barang --</option>
            @foreach($allMaterialsList as $item)
              <option value="{{ $item->id }}">
                {{ $item->code }} - {{ $item->name }} (Stok Saat Ini: {{ \App\Models\Material::formatQty($item->stock) }} {{ $item->unit }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tanggal (Tgl) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Masuk (Tgl) *</label>
            <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Jumlah Masuk -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Masuk *</label>
            <input type="number" step="any" name="amount" min="0.01" required placeholder="Misal: 900"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          </div>
        </div>

        <!-- Catatan / PIC / Sumber (e.g. Pak Mono, Aris) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Supplier / Pengirim *</label>
          <input type="text" name="notes" placeholder="Misal: Pak Mono / Aris / Restock PT Fiber" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openIncomingModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-down"></i>
            <span>Simpan Barang Masuk</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 3: CATAT BARANG KELUAR (GLOBAL)          -->
  <!-- ============================================== -->
  <div x-show="openOutgoingModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openOutgoingModal = false">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center text-sm">
            <i class="fa-solid fa-arrow-up"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Catat Transaksi Barang Keluar</h3>
            <p class="text-[11px] text-slate-400">Pengeluaran untuk teknisi lapangan, instalasi, atau perbaikan</p>
          </div>
        </div>
        <button type="button" @click="openOutgoingModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" action="{{ route('admin.material.store-out') }}" class="space-y-4">
        @csrf

        <!-- Pilih Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Barang / Material *</label>
          <select name="material_id" required
                  class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
            <option value="">-- Pilih Barang --</option>
            @foreach($allMaterialsList as $item)
              <option value="{{ $item->id }}">
                {{ $item->code }} - {{ $item->name }} (Stok Saat Ini: {{ \App\Models\Material::formatQty($item->stock) }} {{ $item->unit }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tanggal (Tgl) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Keluar (Tgl) *</label>
            <input type="date" name="transaction_date" value="{{ date('Y-m-d') }}" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Jumlah Keluar -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Jumlah Keluar *</label>
            <input type="number" step="any" name="amount" min="0.01" required placeholder="Misal: 1 atau 0.5"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          </div>
        </div>

        <!-- Catatan / Teknisi (e.g. Okta_ThreeFive, Bagas_ThreeFive) -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Teknisi Penerima *</label>
          <input type="text" name="notes" placeholder="Misal: Okta_ThreeFive / Bagas_ThreeFive / Danu_ThreeFive" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openOutgoingModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-up"></i>
            <span>Simpan Barang Keluar</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 4: TAMBAH STOK BARANG PER BARIS (+ MASUK) -->
  <!-- ============================================== -->
  <div x-show="openAddStockModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openAddStockModal = false"
         x-show="selectedMaterial">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm">
            <i class="fa-solid fa-arrow-down"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Tambah Stok Barang (Barang Masuk)</h3>
            <p class="text-[11px] text-slate-400">Pencatatan penambahan stok fisik</p>
          </div>
        </div>
        <button type="button" @click="openAddStockModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Info Material -->
      <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <div class="flex items-center gap-2 text-slate-500 text-[11px]">
          <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200" x-text="selectedMaterial ? selectedMaterial.code : ''"></span>
          <span>&bull;</span>
          <span>Stok Saat Ini: <strong class="text-slate-800" x-text="selectedMaterial ? (formatQty(selectedMaterial.computed_stock ?? selectedMaterial.stock) + ' ' + selectedMaterial.unit) : ''"></strong></span>
        </div>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/tambah'" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
          <!-- Tanggal -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Masuk *</label>
            <input type="date" name="transaction_date" x-model="transactionDate" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Jumlah Tambah -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Jumlah (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
            </label>
            <input type="number" step="any" name="amount" x-model.number="stockAmount" min="0.01" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          </div>
        </div>

        <p class="text-[11px] text-emerald-600 font-medium">
          Stok akhir setelah ditambah: <strong x-text="selectedMaterial ? (formatQty((selectedMaterial.computed_stock ?? selectedMaterial.stock) + (stockAmount || 0)) + ' ' + selectedMaterial.unit) : ''"></strong>
        </p>

        <!-- Keterangan / Sumber Barang Masuk -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Supplier / Pengirim</label>
          <input type="text" name="notes" x-model="stockNotes"
                 placeholder="Misal: Pak Mono / Aris / Restock"
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openAddStockModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-plus"></i>
            <span>Simpan Masuk</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 5: KURANG STOK BARANG PER BARIS (- KELUAR) -->
  <!-- ============================================== -->
  <div x-show="openReduceStockModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openReduceStockModal = false"
         x-show="selectedMaterial">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center text-sm">
            <i class="fa-solid fa-arrow-up"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Kurang Stok Barang (Barang Keluar)</h3>
            <p class="text-[11px] text-slate-400">Pencatatan pemakaian lapangan atau instalasi</p>
          </div>
        </div>
        <button type="button" @click="openReduceStockModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Info Material -->
      <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <div class="flex items-center gap-2 text-slate-500 text-[11px]">
          <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200" x-text="selectedMaterial ? selectedMaterial.code : ''"></span>
          <span>&bull;</span>
          <span>Stok Saat Ini: <strong class="text-slate-800" x-text="selectedMaterial ? (formatQty(selectedMaterial.computed_stock ?? selectedMaterial.stock) + ' ' + selectedMaterial.unit) : ''"></strong></span>
        </div>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/kurang'" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-3">
          <!-- Tanggal -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Keluar *</label>
            <input type="date" name="transaction_date" x-model="transactionDate" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Jumlah Kurang -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Jumlah (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
            </label>
            <input type="number" step="any" name="amount" x-model.number="stockAmount" min="0.01" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          </div>
        </div>

        <p class="text-[11px] text-slate-600 font-medium">
          Stok akhir setelah dikurangi: <strong class="text-slate-900" x-text="selectedMaterial ? (formatQty((selectedMaterial.computed_stock ?? selectedMaterial.stock) - (stockAmount || 0)) + ' ' + selectedMaterial.unit) : ''"></strong>
        </p>

        <!-- Keterangan / Teknisi -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan / Teknisi / Proyek *</label>
          <input type="text" name="notes" x-model="stockNotes" required
                 placeholder="Misal: Okta_ThreeFive / Bagas_ThreeFive / Aji_Jatilawang"
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openReduceStockModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-minus"></i>
            <span>Simpan Pengurangan</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 6: PENYESUAIAN STOK OPNAME (MANUAL)      -->
  <!-- ============================================== -->
  <div x-show="openAdjustStockModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openAdjustStockModal = false"
         x-show="selectedMaterial">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-sm">
            <i class="fa-solid fa-sliders"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Penyesuaian Stok Opname (Manual)</h3>
            <p class="text-[11px] text-slate-400">Sinkronisasi hasil perhitungan fisik langsung</p>
          </div>
        </div>
        <button type="button" @click="openAdjustStockModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <p class="text-[11px] text-slate-500">
          Stok di sistem saat ini: <strong class="text-slate-800" x-text="selectedMaterial ? (formatQty(selectedMaterial.computed_stock ?? selectedMaterial.stock) + ' ' + selectedMaterial.unit) : ''"></strong>
        </p>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/sesuaikan'" class="space-y-4">
        @csrf

        <!-- Nilai Stok Baru -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Jumlah Stok Fisik Sebenarnya (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
          </label>
          <input type="number" step="any" name="new_stock" x-model.number="newStockTarget" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          <p class="text-[11px] text-slate-500 mt-1" x-show="selectedMaterial">
            Selisih: 
            <strong :class="newStockTarget >= (selectedMaterial.computed_stock ?? selectedMaterial.stock) ? 'text-emerald-600' : 'text-rose-600'"
                    x-text="(newStockTarget >= (selectedMaterial.computed_stock ?? selectedMaterial.stock) ? '+' : '') + formatQty(newStockTarget - (selectedMaterial.computed_stock ?? selectedMaterial.stock)) + ' ' + selectedMaterial.unit"></strong>
          </p>
        </div>

        <!-- Alasan Penyesuaian -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Alasan Penyesuaian Opname *</label>
          <input type="text" name="notes" required
                 placeholder="Misal: Hasil Stock Opname Fisik Closing / Koreksi"
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openAdjustStockModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-check"></i>
            <span>Simpan Penyesuaian</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 7: UBAH RINCIAN BARANG                   -->
  <!-- ============================================== -->
  <div x-show="openEditModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openEditModal = false"
         x-show="selectedMaterial">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-sm">
            <i class="fa-solid fa-pen-to-square"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Ubah Rincian Barang</h3>
            <p class="text-[11px] text-slate-400">Edit informasi kode, nama, stok awal, dan satuan</p>
          </div>
        </div>
        <button type="button" @click="openEditModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '')" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Kode Barang -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kode Barang *</label>
            <input type="text" name="code" x-model="selectedMaterial.code" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Kategori -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Barang *</label>
            <input type="text" name="category" list="category_options" x-model="selectedMaterial.category" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <!-- Nama Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang *</label>
          <input type="text" name="name" x-model="selectedMaterial.name" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Satuan -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Satuan *</label>
            <input type="text" name="unit" x-model="selectedMaterial.unit" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>

          <!-- Stok Awal -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal *</label>
            <input type="number" step="any" name="initial_stock" x-model.number="selectedMaterial.initial_stock" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold">
          </div>

          <!-- Batas Minimum Alert -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Batas Min Alert</label>
            <input type="number" step="any" name="min_stock" x-model.number="selectedMaterial.min_stock"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <!-- Bulan Periode -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Bulan Periode</label>
            <input type="text" name="month" x-model="selectedMaterial.month" placeholder="Misal: Oktober / Oktober 2026"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
          </div>

          <!-- Harga Satuan Estimasi -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Harga Satuan (Rp)</label>
            <input type="number" name="unit_price" x-model.number="selectedMaterial.unit_price" min="0" step="100"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openEditModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-save"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 8: RIWAYAT MUTASI PER BARANG             -->
  <!-- ============================================== -->
  <div x-show="openHistoryModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-100 space-y-4"
         @click.away="openHistoryModal = false"
         x-show="selectedMaterial">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Riwayat Barang Keluar & Masuk</h3>
            <p class="text-[11px] text-slate-400" x-text="selectedMaterial ? (selectedMaterial.code + ' - ' + selectedMaterial.name) : ''"></p>
          </div>
        </div>
        <button type="button" @click="openHistoryModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Quick Summary Rekonsiliasi -->
      <div class="grid grid-cols-4 gap-2 text-center text-xs">
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
          <span class="text-[9px] text-slate-400 font-bold block uppercase">Stok Awal</span>
          <span class="text-sm font-black text-slate-700" x-text="selectedMaterial ? (formatQty(selectedMaterial.computed_initial_stock ?? selectedMaterial.initial_stock) + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
        <div class="bg-emerald-50 p-2.5 rounded-xl border border-emerald-200/80">
          <span class="text-[9px] text-emerald-700 font-bold block uppercase">Total Masuk</span>
          <span class="text-sm font-black text-emerald-700" x-text="selectedMaterial ? ('+' + formatQty(selectedMaterial.computed_total_in ?? selectedMaterial.total_in) + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
        <div class="bg-rose-50 p-2.5 rounded-xl border border-rose-200/80">
          <span class="text-[9px] text-rose-700 font-bold block uppercase">Total Keluar</span>
          <span class="text-sm font-black text-rose-700" x-text="selectedMaterial ? ('-' + formatQty(selectedMaterial.computed_total_out ?? selectedMaterial.total_out) + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
        <div class="bg-amber-50 p-2.5 rounded-xl border border-amber-200/80">
          <span class="text-[9px] text-amber-700 font-bold block uppercase">Stok Akhir</span>
          <span class="text-sm font-black text-slate-900" x-text="selectedMaterial ? (formatQty(selectedMaterial.computed_stock ?? selectedMaterial.stock) + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
      </div>

      <!-- Mutasi List -->
      <div class="max-h-80 overflow-y-auto space-y-2 text-xs divide-y divide-slate-100">
        <template x-if="selectedMaterial && (!selectedMaterial.mutations || selectedMaterial.mutations.length === 0)">
          <div class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-clipboard-list text-2xl text-slate-300 block mb-1"></i>
            <p>Belum ada catatan mutasi pergerakan untuk barang ini.</p>
          </div>
        </template>

        <template x-if="selectedMaterial && selectedMaterial.mutations && selectedMaterial.mutations.length > 0">
          <div class="space-y-2 pt-1">
            <template x-for="mut in selectedMaterial.mutations" :key="mut.id">
              <div class="p-3 bg-slate-50/70 hover:bg-slate-100/70 rounded-xl border border-slate-200/60 flex items-center justify-between gap-3 transition">
                <div class="flex items-center gap-3">
                  <div class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0 text-xs font-bold"
                       :class="mut.type === 'in' ? 'bg-emerald-100 text-emerald-700' : (mut.type === 'out' ? 'bg-rose-100 text-rose-700' : 'bg-blue-100 text-blue-700')">
                    <i class="fa-solid" :class="mut.type === 'in' ? 'fa-arrow-down' : (mut.type === 'out' ? 'fa-arrow-up' : 'fa-sliders')"></i>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-slate-900" x-text="mut.type === 'in' ? 'Barang Masuk' : (mut.type === 'out' ? 'Barang Keluar' : 'Penyesuaian Opname')"></span>
                      <span class="text-[10px] font-mono text-slate-400" x-text="mut.transaction_date || new Date(mut.created_at).toLocaleDateString('id-ID')"></span>
                    </div>
                    <p class="text-[11px] text-slate-600 mt-0.5" x-text="mut.notes || '-'"></p>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <span class="font-black text-sm block"
                        :class="mut.type === 'in' ? 'text-emerald-600' : (mut.type === 'out' ? 'text-rose-600' : 'text-blue-600')"
                        x-text="(mut.type === 'in' ? '+' : (mut.type === 'out' ? '-' : '')) + formatQty(mut.amount) + ' ' + selectedMaterial.unit"></span>
                </div>
              </div>
            </template>
          </div>
        </template>
      </div>

      <div class="flex items-center justify-between pt-3 border-t border-slate-100">
        <a :href="'{{ url('admin/stok-material/mutasi') }}?material_id=' + (selectedMaterial ? selectedMaterial.id : '')"
           class="text-xs font-bold text-brand hover:underline">
          Lihat Semua Log Mutasi &rarr;
        </a>
        <button type="button" @click="openHistoryModal = false"
                class="px-4 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition">
          Tutup
        </button>
      </div>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 9: KONFIRMASI HAPUS MATERIAL (SINGLE)    -->
  <!-- ============================================== -->
  <div x-show="openDeleteModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4"
         @click.away="openDeleteModal = false"
         x-show="selectedMaterial">
      
      <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 mx-auto flex items-center justify-center text-xl">
        <i class="fa-solid fa-trash-can"></i>
      </div>

      <div>
        <h3 class="font-extrabold text-slate-900 text-base">Hapus Barang?</h3>
        <p class="text-xs text-slate-500 mt-1">
          Hapus <strong class="text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></strong> (<span x-text="selectedMaterial ? selectedMaterial.code : ''"></span>)?
          Semua riwayat mutasi terkait juga akan terhapus.
        </p>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '')" class="flex items-center justify-center gap-2 pt-2">
        @csrf
        @method('DELETE')

        <button type="button" @click="openDeleteModal = false"
                class="w-1/2 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
          Batal
        </button>

        <button type="submit"
                class="w-1/2 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm">
          Ya, Hapus
        </button>
      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 10: HAPUS TERPILIH (BULK DELETE)        -->
  <!-- ============================================== -->
  <div x-show="openBulkDeleteModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 text-center space-y-4"
         @click.away="openBulkDeleteModal = false">
      
      <div class="w-12 h-12 rounded-2xl bg-red-100 text-red-600 mx-auto flex items-center justify-center text-xl">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>

      <div>
        <h3 class="font-extrabold text-slate-900 text-base"
            x-text="bulkDeleteAll ? 'Kosongkan Seluruh Stok Barang?' : ('Hapus ' + selectedIds.length + ' Barang Terpilih?')"></h3>
        <p class="text-xs text-slate-500 mt-1"
           x-text="bulkDeleteAll ? 'Tindakan ini akan mengosongkan seluruh master barang dan seluruh riwayat mutasi dari sistem.' : 'Seluruh data barang yang dicentang akan dihapus permanen dari sistem.'">
        </p>
      </div>

      <form method="POST" action="{{ route('admin.material.bulk-delete') }}" class="flex items-center justify-center gap-2 pt-2">
        @csrf
        <input type="hidden" name="delete_all" :value="bulkDeleteAll ? '1' : '0'">
        
        <template x-if="!bulkDeleteAll">
          <div>
            <template x-for="id in selectedIds" :key="id">
              <input type="hidden" name="ids[]" :value="id">
            </template>
          </div>
        </template>

        <button type="button" @click="openBulkDeleteModal = false"
                class="w-1/2 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
          Batal
        </button>

        <button type="submit"
                class="w-1/2 py-2.5 text-xs font-bold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm"
                x-text="bulkDeleteAll ? 'Ya, Kosongkan Semua' : 'Ya, Hapus Terpilih'">
        </button>
      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL 11: IMPORT BERKAS EXCEL & CSV            -->
  <!-- ============================================== -->
  <div x-show="openImportModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openImportModal = false">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl bg-slate-800 text-white flex items-center justify-center text-sm">
            <i class="fa-solid fa-file-excel"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm">Import Berkas Excel & CSV Stok Barang</h3>
            <p class="text-[11px] text-slate-400">Sinkronisasi data master dan mutasi dari template Excel (.xlsx, .xls) atau CSV (.csv, .txt)</p>
          </div>
        </div>
        <button type="button" @click="openImportModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Info Format Excel & CSV -->
      <div class="bg-blue-50/70 border border-blue-200 text-blue-900 p-3.5 rounded-2xl text-xs space-y-2">
        <div class="flex items-center gap-2 font-bold text-blue-800">
          <i class="fa-solid fa-circle-info"></i>
          <span>Format Berkas Excel & CSV 1 File Lengkap:</span>
        </div>
        <p class="text-[11px] text-blue-700 leading-relaxed">
          Sistem otomatis membaca 3 bagian data sekaligus dalam <strong>1 file yang sama</strong>:
          <span class="font-bold text-slate-800">Laporan Stok Barang</span>, <span class="font-bold text-emerald-800">Barang Masuk</span>, dan <span class="font-bold text-rose-800">Barang Keluar</span>.
        </p>
        <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-2.5 text-[11px] text-emerald-900 space-y-1">
          <div class="font-bold flex items-center gap-1.5 text-emerald-800">
            <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
            <span>Penanganan Otomatis Data Tidak Lengkap:</span>
          </div>
          <p class="text-[10px] text-emerald-700 leading-normal">
            Jika keterangan berkas tidak lengkap (misal: kode barang kosong, stok kosong, atau catatan tidak diisi), sistem akan otomatis melengkapi, mengenerate kode barang unik, menghitung saldo akhir, serta mengelompokkan seluruh data ke 1 periode bulan yang dipilih.
          </p>
        </div>
        <div class="pt-0.5">
          <a href="{{ route('admin.material.export-csv', ['month' => $selectedMonth, 'year' => $selectedYear]) }}" class="text-[11px] font-bold text-blue-800 hover:underline flex items-center gap-1">
            <i class="fa-solid fa-download text-[10px]"></i>
            <span>Unduh Contoh Berkas CSV Template ({{ $monthName }} {{ $selectedYear }})</span>
          </a>
        </div>
      </div>

      <form method="POST" action="{{ route('admin.material.import-csv') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <!-- Input File -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih File Excel / CSV (.xlsx, .xls, .csv, .txt) *</label>
          <input type="file" name="csv_file" accept=".xlsx,.xls,.csv,.txt" required
                 class="w-full text-xs rounded-xl border border-slate-200 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-slate-800 file:text-white hover:file:bg-slate-900 file:cursor-pointer cursor-pointer">
        </div>

        <!-- Pilihan Periode Bulan & Tahun Impor -->
        <div class="grid grid-cols-2 gap-3 bg-slate-50 p-3 rounded-xl border border-slate-200">
          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">Bulan Periode Berkas</label>
            <select name="import_month" class="w-full text-xs rounded-lg border-slate-300 focus:border-brand focus:ring-brand">
              <option value="auto">Otomatis Deteksi dari Berkas</option>
              @foreach(\App\Http\Controllers\Admin\MaterialController::MONTHS as $mNum => $mName)
                <option value="{{ $mNum }}" {{ (string)$selectedMonth === (string)$mNum ? 'selected' : '' }}>{{ $mName }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label class="block text-[11px] font-bold text-slate-700 mb-1">Tahun Periode</label>
            <select name="import_year" class="w-full text-xs rounded-lg border-slate-300 focus:border-brand focus:ring-brand">
              <option value="auto">Otomatis Deteksi dari Berkas</option>
              @for($y = 2024; $y <= 2030; $y++)
                <option value="{{ $y }}" {{ (int)$selectedYear === $y ? 'selected' : '' }}>{{ $y }}</option>
              @endfor
            </select>
          </div>
          <p class="col-span-2 text-[10px] text-slate-500">Pilih periode jika berkas tidak memiliki header bulan yang jelas agar seluruh data stok dan mutasi masuk ke periode tersebut.</p>
        </div>

        <!-- Opsi Timpa Data Periode Terpilih -->
        <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
          <label class="flex items-start gap-2.5 cursor-pointer">
            <input type="checkbox" name="replace_all" value="1" class="mt-0.5 rounded border-slate-300 text-brand focus:ring-brand">
            <div>
              <span class="text-xs font-bold text-slate-800 block">Perbarui / Timpa Data Stok Bulan Ini</span>
              <span class="text-[10px] text-slate-500">Centang opsi ini jika ingin memperbarui seluruh stok pada bulan yang diimpor. <strong>Data bulan lain (Januari, Februari, dsb.) tetap tersimpan aman dan tidak akan hilang.</strong></span>
            </div>
          </label>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openImportModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 bg-brand hover:bg-red-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5">
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <span>Proses Impor Berkas</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- MODAL: EDIT TRANSAKSI MUTASI (MASUK / KELUAR)  -->
  <!-- ============================================== -->
  <div x-show="openEditMutationModal"
       style="display: none;"
       class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
       x-transition:enter="transition ease-out duration-200"
       x-transition:enter-start="opacity-0"
       x-transition:enter-end="opacity-100"
       x-transition:leave="transition ease-in duration-150"
       x-transition:leave-start="opacity-100"
       x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-5"
         @click.away="openEditMutationModal = false"
         x-show="selectedMutation">
      
      <div class="flex items-center justify-between pb-3 border-b border-slate-100">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl flex items-center justify-center text-sm"
               :class="editMutationType === 'in' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
            <i class="fa-solid" :class="editMutationType === 'in' ? 'fa-arrow-down' : 'fa-arrow-up'"></i>
          </div>
          <div>
            <h3 class="font-bold text-slate-900 text-sm"
                x-text="editMutationType === 'in' ? 'Edit Transaksi Barang Masuk' : 'Edit Transaksi Barang Keluar'">
            </h3>
            <p class="text-[11px] text-slate-400">Sesuaikan data barang, tanggal, jumlah, atau catatan transaksi</p>
          </div>
        </div>
        <button type="button" @click="openEditMutationModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material/mutasi') }}/' + (selectedMutation ? selectedMutation.id : '')" class="space-y-4">
        @csrf
        @method('PUT')

        <!-- Pilih Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Barang / Material *</label>
          <select name="material_id" x-model="editMutationMaterialId" required
                  class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-semibold">
            <option value="">-- Pilih Barang --</option>
            @foreach($allMaterialsList as $item)
              <option value="{{ $item->id }}">
                {{ $item->code }} - {{ $item->name }} (Stok Saat Ini: {{ \App\Models\Material::formatQty($item->stock) }} {{ $item->unit }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Tanggal (Tgl) -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Tanggal Transaksi (Tgl) *</label>
            <input type="date" name="transaction_date" x-model="editMutationDate" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono font-bold">
          </div>

          <!-- Jumlah -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">
              Jumlah <span x-text="editMutationType === 'in' ? 'Masuk' : 'Keluar'"></span> *
            </label>
            <input type="number" step="any" name="amount" x-model.number="editMutationAmount" min="0.01" required placeholder="Misal: 10"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900">
          </div>
        </div>

        <!-- Catatan -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Catatan</label>
          <input type="text" name="notes" x-model="editMutationNotes" placeholder="Catatan transaksi..."
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openEditMutationModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  class="px-5 py-2.5 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5"
                  :class="editMutationType === 'in' ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-rose-600 hover:bg-rose-700'">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Simpan Perubahan</span>
          </button>
        </div>

      </form>

    </div>

  </div>

</div>
@endsection
