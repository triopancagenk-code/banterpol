@extends('layouts.admin')

@section('title', 'Admin NOC Banterpool - Manajemen Stok Barang')
@section('page-title', 'Stok Barang')

@section('content')
<div class="space-y-6"
     x-data="{
        openCreateModal: false,
        openEditModal: false,
        openAddStockModal: false,
        openReduceStockModal: false,
        openAdjustStockModal: false,
        openDeleteModal: false,
        openBulkDeleteModal: false,
        openHistoryModal: false,
        
        selectedMaterial: null,
        stockAmount: 1,
        stockNotes: '',
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
            this.openAddStockModal = true;
        },

        openReduceStock(m) {
            this.selectedMaterial = m;
            this.stockAmount = 1;
            this.stockNotes = '';
            this.openReduceStockModal = true;
        },

        openAdjustStock(m) {
            this.selectedMaterial = m;
            this.newStockTarget = m.stock;
            this.stockNotes = '';
            this.openAdjustStockModal = true;
        },

        openEdit(m) {
            this.selectedMaterial = Object.assign({}, m);
            this.openEditModal = true;
        },

        openDelete(m) {
            this.selectedMaterial = m;
            this.openDeleteModal = true;
        },

        openHistory(m) {
            this.selectedMaterial = m;
            this.openHistoryModal = true;
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
  <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-red-100 text-brand flex items-center justify-center text-lg shadow-xs">
          <i class="fa-solid fa-boxes-stacked"></i>
        </div>
        <h2 class="text-xl font-black text-slate-900 tracking-tight">Manajemen Stok Barang & Perangkat</h2>
        <span class="bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-black px-2 py-0.5 rounded uppercase tracking-wider">
          POV {{ Auth::user() && Auth::user()->isDirektur() ? 'Direktur Utama' : 'Admin NOC' }}
        </span>
      </div>
      <p class="text-xs text-slate-500 mt-1">
        Kendali inventaris fiber optik, perangkat modem ONT, splitter, konektor, drop core, dan pencatatan mutasi tambah/kurang stok manual.
      </p>
    </div>

    <!-- 4 Tombol Aksi: 2 Susun (2x2 Grid) dengan Ukuran Container Sama -->
    <div class="grid grid-cols-2 gap-2.5 w-full sm:w-[430px] shrink-0">
      <!-- 1. Tambah Barang Baru -->
      <button type="button" @click="openCreateModal = true"
              class="h-10 px-3 bg-brand hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap w-full">
        <i class="fa-solid fa-plus text-xs"></i>
        <span>Tambah Barang Baru</span>
      </button>

      <!-- 2. Barang Keluar & Masuk -->
      <a href="{{ route('admin.material.mutations') }}"
         class="h-10 px-3 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap w-full">
        <i class="fa-solid fa-arrow-right-arrow-left text-xs"></i>
        <span>Barang Keluar & Masuk</span>
      </a>

      <!-- 3. Export Excel -->
      <a href="{{ route('admin.material.export', request()->query()) }}"
         class="h-10 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap w-full">
        <i class="fa-solid fa-file-excel text-xs"></i>
        <span>Export Excel</span>
      </a>

      <!-- 4. Tombol Hapus Terpilih / Hapus Semua -->
      <div class="w-full">
        <button type="button"
                x-show="selectedIds.length > 0"
                style="display: none;"
                @click="confirmBulkDelete(false)"
                class="w-full h-10 px-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm whitespace-nowrap">
          <i class="fa-solid fa-trash-can text-xs"></i>
          <span>Hapus (<span x-text="selectedIds.length"></span>)</span>
        </button>

        <button type="button"
                x-show="selectedIds.length === 0"
                @click="confirmBulkDelete(true)"
                @if($stats['total_items'] == 0) disabled @endif
                class="w-full h-10 px-3 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl transition flex items-center justify-center gap-2 shadow-sm disabled:opacity-40 disabled:cursor-not-allowed whitespace-nowrap"
                title="Hapus / Kosongkan Seluruh Data Stok Barang">
          <i class="fa-solid fa-trash-arrow-up text-xs"></i>
          <span>Hapus Semua</span>
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================== -->
  <!-- 3. STATISTIC KPI CARDS                         -->
  <!-- ============================================== -->
  <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
    
    <!-- Total Jenis Barang -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Jenis Barang</p>
        <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($stats['total_items'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">SKU Terdaftar</span>
      </div>
      <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-cubes"></i>
      </div>
    </div>

    <!-- Total Fisik Unit Stok -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Fisik Stok</p>
        <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ number_format($stats['total_units'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">Unit / Roll / Pcs</span>
      </div>
      <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-warehouse"></i>
      </div>
    </div>

    <!-- Stok Menipis (Kritis) -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-amber-500 uppercase tracking-wider">Stok Menipis</p>
        <h3 class="text-2xl font-black text-amber-500 mt-1">{{ number_format($stats['low_stock_count'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">&le; Batas Minimum</span>
      </div>
      <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-triangle-exclamation"></i>
      </div>
    </div>

    <!-- Stok Habis -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Stok Habis</p>
        <h3 class="text-2xl font-black text-red-600 mt-1">{{ number_format($stats['out_of_stock_count'], 0, ',', '.') }}</h3>
        <span class="text-[10px] text-slate-500 font-medium">0 Unit di Gudang</span>
      </div>
      <div class="w-11 h-11 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-circle-xmark"></i>
      </div>
    </div>

    <!-- Total Nilai Aset Barang -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex items-center justify-between col-span-2 sm:col-span-1">
      <div>
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Nilai Aset Barang</p>
        <h3 class="text-lg sm:text-xl font-black text-slate-900 mt-1 truncate" title="Rp {{ number_format($stats['total_asset_value'], 0, ',', '.') }}">
          Rp {{ number_format($stats['total_asset_value'] / 1000000, 1, ',', '.') }}Jt
        </h3>
        <span class="text-[10px] text-slate-500 font-medium">{{ $stats['mutations_today'] }} Mutasi Hari Ini</span>
      </div>
      <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg shrink-0">
        <i class="fa-solid fa-coins"></i>
      </div>
    </div>

  </div>

  <!-- ============================================== -->
  <!-- 4. FILTER & PENCARIAN                          -->
  <!-- ============================================== -->
  <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
    
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
      
      <!-- Tab Status Filter -->
      <div class="flex items-center gap-1.5 overflow-x-auto pb-1 text-xs">
        <a href="{{ route('admin.material.index', array_merge(request()->query(), ['status' => 'all'])) }}"
           class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $statusFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
          Semua ({{ $stats['total_items'] }})
        </a>
        <a href="{{ route('admin.material.index', array_merge(request()->query(), ['status' => 'aman'])) }}"
           class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $statusFilter === 'aman' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
          <i class="fa-solid fa-circle-check text-[10px] mr-1 text-emerald-400"></i> Aman ({{ $stats['safe_stock_count'] }})
        </a>
        <a href="{{ route('admin.material.index', array_merge(request()->query(), ['status' => 'menipis'])) }}"
           class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $statusFilter === 'menipis' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
          <i class="fa-solid fa-triangle-exclamation text-[10px] mr-1 text-amber-300"></i> Menipis ({{ $stats['low_stock_count'] }})
        </a>
        <a href="{{ route('admin.material.index', array_merge(request()->query(), ['status' => 'habis'])) }}"
           class="px-3 py-1.5 rounded-xl font-bold transition shrink-0 {{ $statusFilter === 'habis' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
          <i class="fa-solid fa-circle-xmark text-[10px] mr-1 text-red-300"></i> Habis ({{ $stats['out_of_stock_count'] }})
        </a>
      </div>

      <!-- Form Search & Category Filter -->
      <form method="GET" action="{{ route('admin.material.index') }}" class="flex flex-wrap sm:flex-nowrap items-center gap-2">
        <input type="hidden" name="status" value="{{ $statusFilter }}">

        <!-- Filter Kategori Dropdown -->
        <select name="category" onchange="this.form.submit()"
                class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium">
          <option value="all" {{ $categoryFilter === 'all' ? 'selected' : '' }}>Semua Kategori</option>
          @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ $categoryFilter === $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>

        <!-- Filter Urutan (Sort) -->
        <select name="sort" onchange="this.form.submit()"
                class="h-9 text-xs rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand font-medium">
          <option value="latest" {{ $sort === 'latest' ? 'selected' : '' }}>Terbaru</option>
          <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>Nama (A &rarr; Z)</option>
          <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>Nama (Z &rarr; A)</option>
          <option value="stock_desc" {{ $sort === 'stock_desc' ? 'selected' : '' }}>Stok Terbanyak</option>
          <option value="stock_asc" {{ $sort === 'stock_asc' ? 'selected' : '' }}>Stok Paling Sedikit</option>
          <option value="price_desc" {{ $sort === 'price_desc' ? 'selected' : '' }}>Harga Tertinggi</option>
        </select>

        <!-- Input Search -->
        <div class="relative flex-1 sm:w-60">
          <input type="text" name="q" value="{{ $search }}"
                 placeholder="Cari nama atau kode barang..."
                 class="h-9 w-full text-xs rounded-xl border-slate-200 bg-slate-50 pl-8 pr-3 focus:border-brand focus:ring-brand font-medium">
          <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
        </div>

        <button type="submit" class="h-9 px-3 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-bold transition">
          Cari
        </button>

        @if(!empty($search) || $categoryFilter !== 'all' || $statusFilter !== 'all' || $sort !== 'latest')
          <a href="{{ route('admin.material.index') }}" title="Reset Filter"
             class="h-9 px-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs flex items-center justify-center transition">
            <i class="fa-solid fa-rotate-left"></i>
          </a>
        @endif
      </form>

    </div>

  </div>

  <!-- ============================================== -->
  <!-- 5. TABEL MASTER STOK BARANG                    -->
  <!-- ============================================== -->
  <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
    
    <div class="overflow-x-auto">
      <table class="w-full text-left border-collapse text-xs">
        
        <thead>
          <tr class="bg-slate-50/80 text-slate-500 font-bold border-b border-slate-200/80">
            <th class="py-3.5 px-4 w-10 text-center">
              <input type="checkbox"
                     @click="toggleSelectAll(pageMaterialIds)"
                     :checked="isPageAllSelected(pageMaterialIds)"
                     class="rounded border-slate-300 text-brand focus:ring-brand">
            </th>
            <th class="py-3.5 px-4">Barang / Perangkat</th>
            <th class="py-3.5 px-4">Kategori</th>
            <th class="py-3.5 px-4 text-center">Satuan</th>
            <th class="py-3.5 px-4 text-center">Stok Fisik</th>
            <th class="py-3.5 px-4 text-right">Harga Satuan</th>
            <th class="py-3.5 px-4 text-right">Nilai Aset</th>
            <th class="py-3.5 px-4 text-center">Aksi Cepat Mutasi & Kelola</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
          @forelse($materials as $material)
            @php
              $isOutOfStock = $material->stock <= 0;
              $isLowStock = $material->stock > 0 && $material->stock <= $material->min_stock;
              $isSafe = $material->stock > $material->min_stock;
            @endphp
            <tr class="hover:bg-slate-50/70 transition" :class="selectedIds.includes({{ $material->id }}) ? 'bg-red-50/30' : ''">
              
              <!-- Checkbox -->
              <td class="py-3.5 px-4 text-center">
                <input type="checkbox"
                       value="{{ $material->id }}"
                       @click="toggleSingle({{ $material->id }})"
                       :checked="selectedIds.includes({{ $material->id }})"
                       class="rounded border-slate-300 text-brand focus:ring-brand">
              </td>

              <!-- Nama & Kode Barang -->
              <td class="py-3.5 px-4">
                <div class="flex items-start gap-3">
                  <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-sm mt-0.5
                              {{ $isOutOfStock ? 'bg-red-100 text-red-600' : ($isLowStock ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600') }}">
                    @if(str_contains(strtolower($material->category), 'modem') || str_contains(strtolower($material->category), 'ont'))
                      <i class="fa-solid fa-wifi"></i>
                    @elseif(str_contains(strtolower($material->category), 'kabel') || str_contains(strtolower($material->category), 'fiber'))
                      <i class="fa-solid fa-bezier-curve"></i>
                    @elseif(str_contains(strtolower($material->category), 'konektor') || str_contains(strtolower($material->category), 'pasif'))
                      <i class="fa-solid fa-plug"></i>
                    @elseif(str_contains(strtolower($material->category), 'tool'))
                      <i class="fa-solid fa-screwdriver-wrench"></i>
                    @else
                      <i class="fa-solid fa-box"></i>
                    @endif
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-extrabold text-slate-900 hover:text-brand transition cursor-pointer"
                            @click="openHistory({{ Js::from($material) }})">
                        {{ $material->name }}
                      </span>
                    </div>
                    <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
                      <span class="font-mono font-bold text-slate-600 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">{{ $material->code }}</span>
                    </div>
                  </div>
                </div>
              </td>

              <!-- Kategori -->
              <td class="py-3.5 px-4">
                <span class="inline-block bg-slate-100 text-slate-700 text-[10px] font-bold px-2 py-0.5 rounded-full border border-slate-200">
                  {{ $material->category }}
                </span>
              </td>

              <!-- Satuan -->
              <td class="py-3.5 px-4 text-center">
                <span class="font-bold text-slate-700 bg-slate-50 border border-slate-200 px-2 py-1 rounded-lg text-xs">
                  {{ $material->unit }}
                </span>
              </td>

              <!-- Stok Fisik & Indikator Status -->
              <td class="py-3.5 px-4 text-center">
                <div class="flex flex-col items-center">
                  <div class="flex items-baseline gap-1">
                    <span class="text-base font-black {{ $isOutOfStock ? 'text-red-600' : ($isLowStock ? 'text-amber-500' : 'text-slate-900') }}">
                      {{ number_format($material->stock, 0, ',', '.') }}
                    </span>
                    <span class="text-[10px] text-slate-400 font-semibold">{{ $material->unit }}</span>
                  </div>

                  <!-- Status Badge -->
                  @if($isOutOfStock)
                    <span class="inline-flex items-center gap-1 bg-red-50 text-red-700 border border-red-200 text-[9px] font-black px-1.5 py-0.5 rounded-full mt-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
                      HABIS
                    </span>
                  @elseif($isLowStock)
                    <span class="inline-flex items-center gap-1 bg-amber-50 text-amber-700 border border-amber-200 text-[9px] font-black px-1.5 py-0.5 rounded-full mt-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                      MENIPIS (Min: {{ $material->min_stock }})
                    </span>
                  @else
                    <span class="inline-flex items-center gap-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[9px] font-black px-1.5 py-0.5 rounded-full mt-1">
                      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                      AMAN
                    </span>
                  @endif
                </div>
              </td>

              <!-- Harga Satuan -->
              <td class="py-3.5 px-4 text-right font-medium text-slate-600">
                Rp {{ number_format($material->unit_price, 0, ',', '.') }}
              </td>

              <!-- Nilai Aset -->
              <td class="py-3.5 px-4 text-right font-bold text-slate-900">
                Rp {{ number_format($material->total_asset_value, 0, ',', '.') }}
              </td>

              <!-- Aksi Cepat Mutasi & Kelola -->
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  
                  <!-- Tombol Tambah Stok Manual -->
                  <button type="button" @click="openAddStock({{ Js::from($material) }})"
                          title="Tambah Stok Manual (Barang Masuk / Pembelian)"
                          class="h-7 px-2 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 hover:border-emerald-600 rounded-lg text-xs font-bold transition flex items-center gap-1">
                    <i class="fa-solid fa-plus text-[10px]"></i>
                    <span class="hidden xl:inline">Tambah</span>
                  </button>

                  <!-- Tombol Kurang Stok Manual -->
                  <button type="button" @click="openReduceStock({{ Js::from($material) }})"
                          title="Kurang Stok Manual (Pemakaian Lapangan / Afkir)"
                          @if($isOutOfStock) disabled @endif
                          class="h-7 px-2 bg-rose-50 hover:bg-rose-600 text-rose-700 hover:text-white border border-rose-200 hover:border-rose-600 rounded-lg text-xs font-bold transition flex items-center gap-1 disabled:opacity-40 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-minus text-[10px]"></i>
                    <span class="hidden xl:inline">Kurang</span>
                  </button>

                  <!-- Tombol Opname / Sesuaikan -->
                  <button type="button" @click="openAdjustStock({{ Js::from($material) }})"
                          title="Penyesuaian Stok Opname Fisik"
                          class="w-7 h-7 bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white border border-blue-200 hover:border-blue-600 rounded-lg flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-sliders"></i>
                  </button>

                  <!-- Tombol Riwayat Mutasi / Log Keluar Masuk -->
                  <button type="button" @click="openHistory({{ Js::from($material) }})"
                          title="Lihat Log Barang Keluar & Masuk"
                          class="w-7 h-7 bg-slate-100 hover:bg-slate-800 text-slate-700 hover:text-white border border-slate-200 hover:border-slate-800 rounded-lg flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                  </button>

                  <!-- Tombol Edit Data -->
                  <button type="button" @click="openEdit({{ Js::from($material) }})"
                          title="Ubah Rincian Barang"
                          class="w-7 h-7 bg-amber-50 hover:bg-amber-600 text-amber-700 hover:text-white border border-amber-200 hover:border-amber-600 rounded-lg flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-pen-to-square"></i>
                  </button>

                  <!-- Tombol Hapus Manual -->
                  <button type="button" @click="openDelete({{ Js::from($material) }})"
                          title="Hapus Material Manual"
                          class="w-7 h-7 bg-red-50 hover:bg-red-600 text-red-600 hover:text-white border border-red-200 hover:border-red-600 rounded-lg flex items-center justify-center text-xs transition">
                    <i class="fa-solid fa-trash-can"></i>
                  </button>

                </div>
              </td>

            </tr>
          @empty
            <tr>
              <td colspan="8" class="py-12 text-center text-slate-400">
                <div class="flex flex-col items-center justify-center space-y-2">
                  <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-2xl">
                    <i class="fa-solid fa-boxes-stacked"></i>
                  </div>
                  <p class="font-bold text-slate-700 text-sm">Tidak Ada Data Barang</p>
                  <p class="text-xs text-slate-400 max-w-sm">
                    Tidak ditemukan data barang yang sesuai filter atau pencarian. Silakan tambah barang baru atau sesuaikan filter.
                  </p>
                  <button type="button" @click="openCreateModal = true"
                          class="mt-2 px-4 py-2 bg-brand text-white text-xs font-bold rounded-xl hover:bg-red-700 transition">
                    <i class="fa-solid fa-plus mr-1"></i> Tambah Barang Baru Sekarang
                  </button>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>

      </table>
    </div>

    <!-- Pagination -->
    @if($materials->hasPages())
      <div class="p-4 border-t border-slate-200/80 bg-slate-50/50">
        {{ $materials->links() }}
      </div>
    @endif

  </div>


  <!-- ============================================== -->
  <!-- 6. MODAL TAMBAH MATERIAL BARU                  -->
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
            <h3 class="font-bold text-slate-900 text-sm">Tambah Barang / Perangkat Baru</h3>
            <p class="text-[11px] text-slate-400">Pendaftaran master barang baru ke inventaris Banterpool</p>
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
              Kode Barang <span class="text-slate-400 font-normal">(Opsional / Otomatis)</span>
            </label>
            <input type="text" name="code" placeholder="Misal: BRG-ONT-001"
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-mono">
          </div>

          <!-- Kategori -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Kategori Barang *</label>
            <input type="text" name="category" list="category_options" placeholder="Pilih / Ketik Kategori" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
            <datalist id="category_options">
              <option value="Modem & ONT">
              <option value="Kabel & Fiber">
              <option value="Konektor & Aksesoris">
              <option value="Pasif & ODP">
              <option value="Perangkat Jaringan">
              <option value="Tools & Alat Kerja">
            </datalist>
          </div>
        </div>

        <!-- Nama Barang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">Nama Barang *</label>
          <input type="text" name="name" placeholder="Misal: Modem ONT Fiberhome HG6243C Gigabit" required
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- Satuan -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Satuan *</label>
            <select name="unit" required class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
              <option value="Unit">Unit</option>
              <option value="Roll">Roll</option>
              <option value="Meter">Meter</option>
              <option value="Pcs">Pcs</option>
              <option value="Box">Box</option>
              <option value="Set">Set</option>
            </select>
          </div>

          <!-- Stok Awal -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Stok Awal Fisik *</label>
            <input type="number" name="stock" value="0" min="0" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>

          <!-- Batas Minimum Stok -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Batas Min Alert *</label>
            <input type="number" name="min_stock" value="5" min="0" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>
        </div>

        <!-- Harga Satuan Estimasi -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Harga Satuan (Rp) <span class="text-slate-400 font-normal">(Opsional)</span>
          </label>
          <input type="number" name="unit_price" value="0" min="0" step="100" placeholder="Rp"
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
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
  <!-- 7. MODAL TAMBAH STOK MANUAL (STOCK IN)         -->
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
            <h3 class="font-bold text-slate-900 text-sm">Tambah Stok Manual (Barang Masuk)</h3>
            <p class="text-[11px] text-slate-400">Penambahan fisik stok barang ke inventaris</p>
          </div>
        </div>
        <button type="button" @click="openAddStockModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Info Material -->
      <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <div class="flex items-center gap-2 text-slate-500 text-[11px]">
          <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200" x-text="selectedMaterial ? selectedMaterial.code : ''"></span>
          <span>&bull;</span>
          <span>Stok Saat Ini: <strong class="text-slate-800" x-text="selectedMaterial ? (selectedMaterial.stock + ' ' + selectedMaterial.unit) : ''"></strong></span>
        </div>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/tambah'" class="space-y-4">
        @csrf

        <!-- Jumlah Tambah -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Jumlah Yang Ditambahkan (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
          </label>
          <div class="relative">
            <input type="number" name="amount" x-model.number="stockAmount" min="1" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900 pl-3 pr-10">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold" x-text="selectedMaterial ? selectedMaterial.unit : ''"></span>
          </div>
          <p class="text-[11px] text-emerald-600 font-medium mt-1">
            Hasil stok baru setelah ditambah: <strong x-text="selectedMaterial ? ((selectedMaterial.stock + (stockAmount || 0)) + ' ' + selectedMaterial.unit) : ''"></strong>
          </p>
        </div>

        <!-- Keterangan / Sumber Barang Masuk -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Keterangan / Sumber Barang Masuk <span class="text-slate-400 font-normal">(Opsional)</span>
          </label>
          <input type="text" name="notes" x-model="stockNotes"
                 placeholder="Misal: Pengadaan baru dari supplier PT Fiber / Restock"
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
            <span>Simpan Penambahan</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- 8. MODAL KURANG STOK MANUAL (STOCK OUT)        -->
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
            <h3 class="font-bold text-slate-900 text-sm">Kurang Stok Manual (Pengeluaran Barang)</h3>
            <p class="text-[11px] text-slate-400">Pengurangan pemakaian lapangan, instalasi, atau afkir</p>
          </div>
        </div>
        <button type="button" @click="openReduceStockModal = false" class="text-slate-400 hover:text-slate-600 text-sm">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <!-- Info Material -->
      <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <div class="flex items-center gap-2 text-slate-500 text-[11px]">
          <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200" x-text="selectedMaterial ? selectedMaterial.code : ''"></span>
          <span>&bull;</span>
          <span>Stok Tersedia: <strong class="text-slate-800" x-text="selectedMaterial ? (selectedMaterial.stock + ' ' + selectedMaterial.unit) : ''"></strong></span>
        </div>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/kurang'" class="space-y-4">
        @csrf

        <!-- Jumlah Kurang -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Jumlah Yang Dikurangkan (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
          </label>
          <div class="relative">
            <input type="number" name="amount" x-model.number="stockAmount" min="1" :max="selectedMaterial ? selectedMaterial.stock : 9999" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900 pl-3 pr-10">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold" x-text="selectedMaterial ? selectedMaterial.unit : ''"></span>
          </div>

          <div class="mt-1">
            <template x-if="selectedMaterial && stockAmount > selectedMaterial.stock">
              <p class="text-[11px] text-red-600 font-bold flex items-center gap-1">
                <i class="fa-solid fa-circle-exclamation"></i>
                Jumlah melebihi stok yang ada (<span x-text="selectedMaterial.stock"></span> <span x-text="selectedMaterial.unit"></span>)!
              </p>
            </template>
            <template x-if="selectedMaterial && stockAmount <= selectedMaterial.stock">
              <p class="text-[11px] text-slate-500 font-medium">
                Sisa stok setelah dikurangi: <strong class="text-slate-800" x-text="(selectedMaterial.stock - (stockAmount || 0)) + ' ' + selectedMaterial.unit"></strong>
              </p>
            </template>
          </div>
        </div>

        <!-- Keterangan / Alasan Pengurangan -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Keterangan / Alasan Pengeluaran <span class="text-slate-400 font-normal">(Opsional)</span>
          </label>
          <input type="text" name="notes" x-model="stockNotes"
                 placeholder="Misal: Pemasangan baru pelanggan Bpk Anton / Rusak"
                 class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" @click="openReduceStockModal = false"
                  class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition">
            Batal
          </button>
          <button type="submit"
                  :disabled="selectedMaterial && stockAmount > selectedMaterial.stock"
                  class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition shadow-sm flex items-center gap-1.5 disabled:opacity-40 disabled:cursor-not-allowed">
            <i class="fa-solid fa-minus"></i>
            <span>Simpan Pengurangan</span>
          </button>
        </div>

      </form>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- 9. MODAL PENYESUAIAN STOK OPNAME (ADJUSTMENT) -->
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

      <div class="bg-slate-50 p-3.5 rounded-2xl border border-slate-200/80 space-y-1 text-xs">
        <p class="font-bold text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></p>
        <p class="text-[11px] text-slate-500">
          Stok di sistem saat ini: <strong class="text-slate-800" x-text="selectedMaterial ? (selectedMaterial.stock + ' ' + selectedMaterial.unit) : ''"></strong>
        </p>
      </div>

      <form method="POST" :action="'{{ url('admin/stok-material') }}/' + (selectedMaterial ? selectedMaterial.id : '') + '/sesuaikan'" class="space-y-4">
        @csrf

        <!-- Nilai Stok Baru -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Jumlah Stok Fisik Sebenarnya (<span x-text="selectedMaterial ? selectedMaterial.unit : 'Unit'"></span>) *
          </label>
          <div class="relative">
            <input type="number" name="new_stock" x-model.number="newStockTarget" min="0" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand font-bold text-slate-900 pl-3 pr-10">
            <span class="absolute right-3 top-2.5 text-xs text-slate-400 font-bold" x-text="selectedMaterial ? selectedMaterial.unit : ''"></span>
          </div>
          <p class="text-[11px] text-slate-500 mt-1" x-show="selectedMaterial">
            Selisih penyesuaian: 
            <strong :class="newStockTarget >= selectedMaterial.stock ? 'text-emerald-600' : 'text-rose-600'"
                    x-text="(newStockTarget >= selectedMaterial.stock ? '+' : '') + (newStockTarget - selectedMaterial.stock) + ' ' + selectedMaterial.unit"></strong>
          </p>
        </div>

        <!-- Alasan Penyesuaian -->
        <div>
          <label class="block text-xs font-bold text-slate-700 mb-1">
            Alasan Penyesuaian Opname *
          </label>
          <input type="text" name="notes" required
                 placeholder="Misal: Hasil Stock Opname Fisik Akhir Bulan / Koreksi"
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
  <!-- 10. MODAL UBAH / EDIT DATA MATERIAL            -->
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
            <p class="text-[11px] text-slate-400">Edit informasi nama, kategori, satuan, dan harga</p>
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
            <input type="text" name="category" list="edit_category_options" x-model="selectedMaterial.category" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
            <datalist id="edit_category_options">
              <option value="Modem & ONT">
              <option value="Kabel & Fiber">
              <option value="Konektor & Aksesoris">
              <option value="Pasif & ODP">
              <option value="Perangkat Jaringan">
              <option value="Tools & Alat Kerja">
            </datalist>
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

          <!-- Batas Minimum Alert -->
          <div>
            <label class="block text-xs font-bold text-slate-700 mb-1">Batas Min Alert *</label>
            <input type="number" name="min_stock" x-model.number="selectedMaterial.min_stock" min="0" required
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-brand focus:ring-brand">
          </div>

          <!-- Harga Satuan -->
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
  <!-- 11. MODAL RIWAYAT MUTASI PER ITEM             -->
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

      <!-- Quick Summary -->
      <div class="grid grid-cols-3 gap-2.5 text-center text-xs">
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
          <span class="text-[10px] text-slate-400 font-bold block uppercase">Stok Sekarang</span>
          <span class="text-base font-black text-slate-900" x-text="selectedMaterial ? (selectedMaterial.stock + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
          <span class="text-[10px] text-slate-400 font-bold block uppercase">Batas Minimum</span>
          <span class="text-base font-black text-amber-500" x-text="selectedMaterial ? (selectedMaterial.min_stock + ' ' + selectedMaterial.unit) : ''"></span>
        </div>
        <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-200/80">
          <span class="text-[10px] text-slate-400 font-bold block uppercase">Harga Satuan</span>
          <span class="text-xs font-bold text-slate-700 truncate block mt-0.5" x-text="selectedMaterial ? formatRupiah(selectedMaterial.unit_price) : 'Rp 0'"></span>
        </div>
      </div>

      <!-- Mutasi List -->
      <div class="max-h-80 overflow-y-auto space-y-2 text-xs divide-y divide-slate-100">
        <template x-if="selectedMaterial && (!selectedMaterial.mutations || selectedMaterial.mutations.length === 0)">
          <div class="py-8 text-center text-slate-400">
            <i class="fa-solid fa-clipboard-list text-2xl text-slate-300 block mb-1"></i>
            <p>Belum ada catatan pergerakan untuk barang ini.</p>
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
                      <span class="font-bold text-slate-900" x-text="mut.type === 'in' ? 'Masuk / Tambah' : (mut.type === 'out' ? 'Keluar / Kurang' : 'Penyesuaian Opname')"></span>
                      <span class="text-[10px] font-mono text-slate-400" x-text="new Date(mut.created_at).toLocaleString('id-ID')"></span>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-0.5" x-text="mut.notes || '-'"></p>
                  </div>
                </div>

                <div class="text-right shrink-0">
                  <span class="font-black text-sm block"
                        :class="mut.type === 'in' ? 'text-emerald-600' : (mut.type === 'out' ? 'text-rose-600' : 'text-blue-600')"
                        x-text="(mut.type === 'in' ? '+' : (mut.type === 'out' ? '-' : '')) + mut.amount + ' ' + selectedMaterial.unit"></span>
                  <span class="text-[10px] text-slate-400 font-medium" x-text="mut.stock_before + ' &rarr; ' + mut.stock_after"></span>
                </div>
              </div>
            </template>
          </div>
        </template>
      </div>

      <div class="flex items-center justify-between pt-3 border-t border-slate-100">
        <a :href="'{{ url('admin/stok-material/mutasi') }}?material_id=' + (selectedMaterial ? selectedMaterial.id : '')"
           class="text-xs font-bold text-brand hover:underline">
          Lihat Semua Log Barang Keluar & Masuk &rarr;
        </a>
        <button type="button" @click="openHistoryModal = false"
                class="px-4 py-2 text-xs font-bold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl transition">
          Tutup
        </button>
      </div>

    </div>

  </div>


  <!-- ============================================== -->
  <!-- 12. MODAL KONFIRMASI HAPUS MATERIAL (SINGLE)   -->
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
        <h3 class="font-extrabold text-slate-900 text-base">Hapus Barang Manual?</h3>
        <p class="text-xs text-slate-500 mt-1">
          Apakah Anda yakin ingin menghapus barang <strong class="text-slate-900" x-text="selectedMaterial ? selectedMaterial.name : ''"></strong> (<span x-text="selectedMaterial ? selectedMaterial.code : ''"></span>)?
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
  <!-- 13. MODAL HAPUS TERPILIH (BULK DELETE)         -->
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
           x-text="bulkDeleteAll ? 'Tindakan ini akan mengosongkan seluruh item inventaris barang beserta riwayat mutasinya secara permanen dari sistem.' : 'Tindakan ini tidak dapat dibatalkan. Seluruh data item barang yang Anda centang akan dihapus secara permanen dari sistem.'">
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

</div>
@endsection
